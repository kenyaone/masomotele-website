<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Online Students Dashboard
 * Read-only oversight view of every enrollment with delivery_mode='online':
 * payment status, lesson progress (same topic-based formula the learner
 * portal itself uses), and last activity date. No editing happens here —
 * each row links to the existing Students admin page for actual changes.
 *
 * Menu slug: mtti-mis-online-students
 */
class MTTI_MIS_Admin_Online_Students {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /* ─── Data layer ─────────────────────────────────────────────────── */

    private function get_courses_with_online_enrollments() {
        global $wpdb;
        $p = $wpdb->prefix . 'mtti_';
        return $wpdb->get_results(
            "SELECT DISTINCT c.course_id, c.course_code, c.course_name
             FROM {$p}courses c
             INNER JOIN {$p}enrollments e ON e.course_id = c.course_id
             WHERE e.delivery_mode = 'online' AND e.deleted_at IS NULL
             ORDER BY c.course_name"
        );
    }

    private function get_online_enrollments( $filters = [] ) {
        global $wpdb;
        $p = $wpdb->prefix . 'mtti_';

        $where = [ "e.delivery_mode = 'online'", 'e.deleted_at IS NULL', 's.deleted_at IS NULL' ];
        $args  = [];

        if ( ! empty( $filters['course_id'] ) ) {
            $where[] = 'e.course_id = %d';
            $args[]  = (int) $filters['course_id'];
        }
        if ( ! empty( $filters['status'] ) ) {
            $where[] = 'e.status = %s';
            $args[]  = $filters['status'];
        }
        if ( ! empty( $filters['payment'] ) && $filters['payment'] === 'balance_due' ) {
            $where[] = 'COALESCE(sb.balance, 0) > 0';
        } elseif ( ! empty( $filters['payment'] ) && $filters['payment'] === 'fully_paid' ) {
            $where[] = 'COALESCE(sb.balance, 0) <= 0';
        }
        if ( ! empty( $filters['search'] ) ) {
            $where[] = "(u.display_name LIKE %s OR s.admission_number LIKE %s)";
            $like    = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $args[]  = $like; $args[] = $like;
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $where );

        $sql = "
            SELECT
                e.enrollment_id, e.student_id, e.course_id, e.enrollment_date, e.status AS enrollment_status,
                u.display_name, s.admission_number,
                c.course_code, c.course_name,
                sb.total_fee, sb.total_paid, sb.balance,
                sa.last_login
            FROM {$p}enrollments e
            INNER JOIN {$p}students s ON s.student_id = e.student_id
            INNER JOIN {$wpdb->users} u ON u.ID = s.user_id
            INNER JOIN {$p}courses  c ON c.course_id  = e.course_id
            LEFT JOIN  {$p}student_balances sb ON sb.enrollment_id = e.enrollment_id AND sb.deleted_at IS NULL
            LEFT JOIN  {$p}student_accounts sa ON sa.admission_number = s.admission_number
            $where_sql
            ORDER BY e.enrollment_date DESC
        ";

        if ( $args ) {
            $sql = $wpdb->prepare( $sql, ...$args ); // phpcs:ignore
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Attaches progress_pct, total_topics, completed_topics, and
     * last_activity to each enrollment row, batch-fetched (no N+1) — the
     * same topic-based completion rule as get_course_progress() in
     * class-mtti-mis-learner-portal.php (quiz needs a passed attempt,
     * everything else just needs to have been opened; a "topic" is a run
     * starting at each objectives row).
     */
    private function attach_progress( $rows ) {
        global $wpdb;
        $p = $wpdb->prefix . 'mtti_';
        if ( empty( $rows ) ) return $rows;

        $course_ids  = array_unique( array_map( fn( $r ) => (int) $r->course_id, $rows ) );
        $student_ids = array_unique( array_map( fn( $r ) => (int) $r->student_id, $rows ) );

        /* All published lessons for the relevant courses, grouped into topics per course */
        $course_ids_sql = implode( ',', array_map( 'intval', $course_ids ) );
        $lessons = $wpdb->get_results(
            "SELECT lesson_id, course_id, content_type, interactive_role
             FROM {$p}lessons
             WHERE course_id IN ($course_ids_sql) AND status = 'Published' AND deleted_at IS NULL
               AND title NOT LIKE '🤖 Quiz:%'
             ORDER BY course_id, order_number ASC"
        );
        $topics_by_course = [];
        $current_course = null;
        $current_topic  = [];
        foreach ( $lessons as $l ) {
            if ( $l->course_id != $current_course ) {
                if ( $current_course !== null && ! empty( $current_topic ) ) {
                    $topics_by_course[ $current_course ][] = $current_topic;
                }
                $current_course = $l->course_id;
                $current_topic  = [];
                if ( ! isset( $topics_by_course[ $current_course ] ) ) $topics_by_course[ $current_course ] = [];
            }
            if ( $l->content_type === 'objectives' && ! empty( $current_topic ) ) {
                $topics_by_course[ $current_course ][] = $current_topic;
                $current_topic = [];
            }
            $current_topic[] = $l;
        }
        if ( $current_course !== null && ! empty( $current_topic ) ) {
            $topics_by_course[ $current_course ][] = $current_topic;
        }

        /* Every viewed lesson per student (also doubles as "last activity") */
        $student_ids_sql = implode( ',', array_map( 'intval', $student_ids ) );
        $views = $wpdb->get_results(
            "SELECT student_id, lesson_id, MAX(viewed_at) AS last_viewed
             FROM {$p}lesson_views WHERE student_id IN ($student_ids_sql) GROUP BY student_id, lesson_id"
        );
        $viewed_by_student  = [];
        $last_view_by_student = [];
        foreach ( $views as $v ) {
            $viewed_by_student[ $v->student_id ][ (int) $v->lesson_id ] = true;
            if ( empty( $last_view_by_student[ $v->student_id ] ) || $v->last_viewed > $last_view_by_student[ $v->student_id ] ) {
                $last_view_by_student[ $v->student_id ] = $v->last_viewed;
            }
        }

        /* Passed quiz attempts per student (also doubles as "last activity") */
        $attempts = $wpdb->get_results(
            "SELECT student_id, lesson_id, passed, MAX(attempted_at) AS last_attempt
             FROM {$p}quiz_attempts WHERE student_id IN ($student_ids_sql) GROUP BY student_id, lesson_id, passed"
        );
        $passed_by_student = [];
        $last_attempt_by_student = [];
        foreach ( $attempts as $a ) {
            if ( (int) $a->passed === 1 ) {
                $passed_by_student[ $a->student_id ][ (int) $a->lesson_id ] = true;
            }
            if ( empty( $last_attempt_by_student[ $a->student_id ] ) || $a->last_attempt > $last_attempt_by_student[ $a->student_id ] ) {
                $last_attempt_by_student[ $a->student_id ] = $a->last_attempt;
            }
        }

        foreach ( $rows as $r ) {
            $topics = $topics_by_course[ $r->course_id ] ?? [];
            $viewed  = $viewed_by_student[ $r->student_id ] ?? [];
            $passed  = $passed_by_student[ $r->student_id ] ?? [];

            $total_topics = count( $topics );
            $completed_topics = 0;
            foreach ( $topics as $topic_lessons ) {
                $all_done = true;
                foreach ( $topic_lessons as $l ) {
                    $done = ( $l->interactive_role === 'quiz' )
                        ? ! empty( $passed[ (int) $l->lesson_id ] )
                        : ! empty( $viewed[ (int) $l->lesson_id ] );
                    if ( ! $done ) { $all_done = false; break; }
                }
                if ( $all_done ) $completed_topics++;
            }

            $r->total_topics     = $total_topics;
            $r->completed_topics = $completed_topics;
            $r->progress_pct     = $total_topics > 0 ? min( 100, round( $completed_topics / $total_topics * 100 ) ) : 0;

            $last_view    = $last_view_by_student[ $r->student_id ] ?? null;
            $last_attempt = $last_attempt_by_student[ $r->student_id ] ?? null;
            $r->last_activity = null;
            if ( $last_view && $last_attempt ) {
                $r->last_activity = max( $last_view, $last_attempt );
            } else {
                $r->last_activity = $last_view ?: $last_attempt;
            }
        }

        return $rows;
    }

    /* ─── Main display ───────────────────────────────────────────────── */

    public function display() {
        if ( ! current_user_can( 'manage_students' ) && ! current_user_can( 'manage_mtti' ) ) {
            wp_die( __( 'You do not have permission to view this page.' ) );
        }

        $filters = array_filter( [
            'course_id' => isset( $_GET['course_id'] ) ? (int) $_GET['course_id'] : 0,
            'status'    => sanitize_text_field( $_GET['status']  ?? '' ),
            'payment'   => sanitize_text_field( $_GET['payment'] ?? '' ),
            'search'    => sanitize_text_field( $_GET['search']  ?? '' ),
        ] );

        $rows    = $this->get_online_enrollments( $filters );
        $rows    = $this->attach_progress( $rows );
        $courses = $this->get_courses_with_online_enrollments();

        $total_students   = count( $rows );
        $behind_on_payment = count( array_filter( $rows, fn( $r ) => (float) $r->balance > 0 ) );
        $avg_progress = $total_students > 0
            ? round( array_sum( array_map( fn( $r ) => $r->progress_pct, $rows ) ) / $total_students )
            : 0;
        $inactive_7d = count( array_filter( $rows, fn( $r ) =>
            empty( $r->last_activity ) || strtotime( $r->last_activity ) < strtotime( '-7 days' )
        ) );

        $page_url = admin_url( 'admin.php?page=mtti-mis-online-students' );
        ?>
        <div class="wrap">
            <h1>💻 Online Students</h1>
            <p style="color:#666;margin-top:-6px;">Every online-delivery enrollment — payment status, lesson progress, and last activity. This is a view-only oversight report; edit a student from the <a href="<?php echo esc_url( admin_url('admin.php?page=mtti-mis-students') ); ?>">Students</a> page.</p>

            <!-- ── Summary cards ──────────────────────────────────── -->
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin:16px 0 24px;">
                <?php
                $cards = [
                    [ 'label' => 'Online students shown', 'value' => number_format( $total_students ), 'color' => '#185FA5', 'sub' => count($courses) . ' course(s)' ],
                    [ 'label' => 'Behind on payment',      'value' => number_format( $behind_on_payment ), 'color' => '#A32D2D', 'sub' => 'Balance > KES 0' ],
                    [ 'label' => 'Average progress',       'value' => $avg_progress . '%',          'color' => '#1D9E75', 'sub' => 'Across all shown' ],
                    [ 'label' => 'Inactive 7+ days',        'value' => number_format( $inactive_7d ), 'color' => '#854F0B', 'sub' => 'No view/quiz activity' ],
                ];
                foreach ( $cards as $card ) : ?>
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:16px 20px;min-width:160px;flex:1;border-top:3px solid <?php echo esc_attr($card['color']); ?>;">
                    <div style="font-size:12px;color:#666;margin-bottom:6px;"><?php echo esc_html($card['label']); ?></div>
                    <div style="font-size:22px;font-weight:600;color:#1d2327;"><?php echo esc_html($card['value']); ?></div>
                    <div style="font-size:11px;color:#999;margin-top:4px;"><?php echo esc_html($card['sub']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Filter bar ─────────────────────────────────────── -->
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin:16px 0 20px;">
                <input type="hidden" name="page" value="mtti-mis-online-students">

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Course</label>
                    <select name="course_id" style="height:34px;padding:0 8px;">
                        <option value="">All courses</option>
                        <?php foreach ( $courses as $c ) : ?>
                            <option value="<?php echo (int) $c->course_id; ?>" <?php selected( $filters['course_id'] ?? 0, $c->course_id ); ?>>
                                <?php echo esc_html( $c->course_code . ' — ' . $c->course_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Enrollment Status</label>
                    <select name="status" style="height:34px;padding:0 8px;">
                        <option value="">All statuses</option>
                        <?php foreach ( [ 'Enrolled', 'Active', 'In-Progress', 'Completed', 'Dropped' ] as $s ) : ?>
                            <option value="<?php echo esc_attr($s); ?>" <?php selected( $filters['status'] ?? '', $s ); ?>><?php echo esc_html($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Payment</label>
                    <select name="payment" style="height:34px;padding:0 8px;">
                        <option value="">All</option>
                        <option value="balance_due" <?php selected( $filters['payment'] ?? '', 'balance_due' ); ?>>Balance due</option>
                        <option value="fully_paid"  <?php selected( $filters['payment'] ?? '', 'fully_paid' ); ?>>Fully paid</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Search</label>
                    <input type="text" name="search" placeholder="Name or admission no." value="<?php echo esc_attr( $filters['search'] ?? '' ); ?>" style="height:34px;padding:0 8px;">
                </div>

                <div style="display:flex;gap:8px;">
                    <button type="submit" class="button button-primary">🔍 Apply</button>
                    <a href="<?php echo esc_url($page_url); ?>" class="button">✕ Reset</a>
                </div>
            </form>

            <!-- ── Table ──────────────────────────────────────────── -->
            <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:0;overflow:hidden;">
                <table class="wp-list-table widefat fixed striped" style="margin:0;border:none;">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th style="width:110px;">Enrolled</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:160px;">Payment</th>
                            <th style="width:180px;">Progress</th>
                            <th style="width:120px;">Last Activity</th>
                            <th style="width:120px;">Last Login</th>
                            <th style="width:90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ( empty( $rows ) ) : ?>
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:#666;">
                            No online students found. Try adjusting your filters.
                        </td></tr>
                    <?php else :
                        foreach ( $rows as $r ) :
                            $balance   = (float) ( $r->balance ?? 0 );
                            $total_fee = (float) ( $r->total_fee ?? 0 );
                            $paid_pct  = $total_fee > 0 ? round( ( ( $r->total_paid ?? 0 ) / $total_fee ) * 100 ) : null;

                            if ( is_null( $r->total_fee ) ) {
                                $pay_label = 'No balance record'; $pay_color = '#999'; $pay_bg = '#f0f0f0';
                            } elseif ( $balance <= 0 ) {
                                $pay_label = 'Fully Paid'; $pay_color = '#1D9E75'; $pay_bg = '#EAF3DE';
                            } elseif ( $paid_pct > 0 ) {
                                $pay_label = "Partial ({$paid_pct}%)"; $pay_color = '#854F0B'; $pay_bg = '#FAEEDA';
                            } else {
                                $pay_label = 'Unpaid'; $pay_color = '#A32D2D'; $pay_bg = '#FCEBEB';
                            }

                            $progress = (int) $r->progress_pct;
                            $prog_color = $progress >= 75 ? '#1D9E75' : ( $progress >= 30 ? '#854F0B' : '#A32D2D' );

                            $inactive = empty( $r->last_activity ) || strtotime( $r->last_activity ) < strtotime( '-7 days' );
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $r->display_name ); ?></strong><br>
                                <span style="font-size:11px;color:#888;"><?php echo esc_html( $r->admission_number ); ?></span>
                            </td>
                            <td>
                                <?php echo esc_html( $r->course_name ); ?><br>
                                <span style="font-size:11px;color:#888;"><?php echo esc_html( $r->course_code ); ?></span>
                            </td>
                            <td><?php echo esc_html( date( 'd M Y', strtotime( $r->enrollment_date ) ) ); ?></td>
                            <td><?php echo esc_html( $r->enrollment_status ); ?></td>
                            <td>
                                <span style="font-size:11px;background:<?php echo esc_attr($pay_bg); ?>;color:<?php echo esc_attr($pay_color); ?>;padding:2px 8px;border-radius:9999px;font-weight:600;">
                                    <?php echo esc_html( $pay_label ); ?>
                                </span>
                                <?php if ( $balance > 0 ) : ?>
                                    <div style="font-size:11px;color:#A32D2D;margin-top:3px;">Bal: KES <?php echo number_format($balance, 2); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="flex:1;background:#f0f0f0;border-radius:4px;height:8px;overflow:hidden;">
                                        <div style="height:8px;border-radius:4px;width:<?php echo min($progress,100); ?>%;background:<?php echo esc_attr($prog_color); ?>;"></div>
                                    </div>
                                    <span style="font-size:12px;color:#333;min-width:36px;"><?php echo $progress; ?>%</span>
                                </div>
                                <span style="font-size:11px;color:#999;"><?php echo (int) $r->completed_topics; ?>/<?php echo (int) $r->total_topics; ?> topics</span>
                            </td>
                            <td style="font-size:12px;<?php echo $inactive ? 'color:#A32D2D;' : 'color:#666;'; ?>">
                                <?php echo $r->last_activity ? esc_html( date( 'd M Y', strtotime( $r->last_activity ) ) ) : 'Never'; ?>
                            </td>
                            <td style="font-size:12px;color:#666;">
                                <?php echo $r->last_login ? esc_html( date( 'd M Y', strtotime( $r->last_login ) ) ) : '—'; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small" onclick="mttiShowOnlineStudentActivity(<?php echo (int) $r->enrollment_id; ?>, '<?php echo esc_js( $r->display_name . ' — ' . $r->course_code ); ?>')">🔍 Details</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div><!-- /table wrapper -->

        </div><!-- /wrap -->
        <?php
        $this->render_activity_modal();
    }

    /* ─── Activity detail modal ──────────────────────────────────────── */

    private function render_activity_modal() { ?>
        <div id="mtti-activity-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:12px;padding:28px 32px;max-width:720px;width:94%;max-height:88vh;overflow-y:auto;position:relative;box-shadow:0 8px 40px rgba(0,0,0,.3);">
                <button type="button" onclick="document.getElementById('mtti-activity-modal').style.display='none';" style="position:absolute;top:14px;right:18px;background:none;border:none;font-size:22px;cursor:pointer;color:#666;">✕</button>
                <h2 style="margin:0 0 4px;font-size:18px;">📋 Learner Activity</h2>
                <p id="mtti-activity-subtitle" style="color:#666;font-size:13px;margin:0 0 18px;"></p>
                <div id="mtti-activity-spinner" style="text-align:center;padding:40px;color:#888;font-size:13px;">⏳ Loading…</div>
                <div id="mtti-activity-body"></div>
            </div>
        </div>
        <script>
        function mttiShowOnlineStudentActivity(enrollmentId, label) {
            document.getElementById('mtti-activity-modal').style.display = 'flex';
            document.getElementById('mtti-activity-subtitle').innerText = label;
            document.getElementById('mtti-activity-spinner').style.display = 'block';
            document.getElementById('mtti-activity-body').innerHTML = '';
            jQuery.ajax({
                url: ajaxurl, method: 'POST',
                data: {
                    action: 'mtti_online_student_activity',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'mtti_online_student_activity' ) ); ?>',
                    enrollment_id: enrollmentId
                },
                success: function ( r ) {
                    document.getElementById('mtti-activity-spinner').style.display = 'none';
                    document.getElementById('mtti-activity-body').innerHTML = r.success ? r.data.html : '<p style="color:#A32D2D;">' + (r.data && r.data.message ? r.data.message : 'Failed to load activity.') + '</p>';
                },
                error: function () {
                    document.getElementById('mtti-activity-spinner').style.display = 'none';
                    document.getElementById('mtti-activity-body').innerHTML = '<p style="color:#A32D2D;">Network error. Please try again.</p>';
                }
            });
        }
        </script>
        <?php
    }

    /**
     * AJAX: full activity drill-down for one enrollment — every lesson
     * viewed (with timestamp), every quiz attempt, assignment submissions,
     * discussion posts, and portal login info. Called from the "Details"
     * button; kept separate from the list query above since it's only
     * fetched on demand (would be an expensive N+1 to preload for every row).
     */
    public function ajax_get_activity() {
        if ( ! current_user_can( 'manage_students' ) && ! current_user_can( 'manage_mtti' ) ) {
            wp_send_json_error( [ 'message' => 'You do not have permission to view this.' ] );
        }
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mtti_online_student_activity' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed. Please refresh the page.' ] );
        }

        $enrollment_id = isset( $_POST['enrollment_id'] ) ? (int) $_POST['enrollment_id'] : 0;
        if ( ! $enrollment_id ) {
            wp_send_json_error( [ 'message' => 'Invalid enrollment.' ] );
        }

        global $wpdb;
        $p = $wpdb->prefix . 'mtti_';

        $enrollment = $wpdb->get_row( $wpdb->prepare(
            "SELECT e.enrollment_id, e.student_id, e.course_id, s.admission_number, u.display_name, c.course_name
             FROM {$p}enrollments e
             INNER JOIN {$p}students s ON s.student_id = e.student_id
             INNER JOIN {$wpdb->users} u ON u.ID = s.user_id
             INNER JOIN {$p}courses c ON c.course_id = e.course_id
             WHERE e.enrollment_id = %d",
            $enrollment_id
        ) );

        if ( ! $enrollment ) {
            wp_send_json_error( [ 'message' => 'Enrollment not found.' ] );
        }

        $student_id = (int) $enrollment->student_id;
        $course_id  = (int) $enrollment->course_id;

        /* Portal login / account status */
        $account = $wpdb->get_row( $wpdb->prepare(
            "SELECT last_login, login_attempts, locked_until FROM {$p}student_accounts WHERE admission_number = %s",
            $enrollment->admission_number
        ) );

        ob_start();
        ?>
        <div style="margin-bottom:20px;">
            <h3 style="font-size:13px;margin:0 0 8px;color:#333;">🔑 Portal Login</h3>
            <?php if ( $account ) : ?>
                <p style="font-size:12px;color:#666;margin:0;">
                    Last login: <strong><?php echo $account->last_login ? esc_html( date( 'd M Y, H:i', strtotime( $account->last_login ) ) ) : 'Never'; ?></strong>
                    &nbsp;·&nbsp; Failed attempts: <strong><?php echo (int) $account->login_attempts; ?></strong>
                    <?php if ( ! empty( $account->locked_until ) && strtotime( $account->locked_until ) > time() ) : ?>
                        &nbsp;·&nbsp; <span style="color:#A32D2D;font-weight:600;">🔒 Locked until <?php echo esc_html( date( 'd M Y, H:i', strtotime( $account->locked_until ) ) ); ?></span>
                    <?php endif; ?>
                </p>
            <?php else : ?>
                <p style="font-size:12px;color:#999;margin:0;">No portal account record found.</p>
            <?php endif; ?>
        </div>

        <?php echo self::render_course_activity_html( $student_id, $course_id ); ?>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }

    /**
     * Renders the lessons/quiz-attempts/assignments/discussions block for
     * one student in one course. Shared by the Online Students "Details"
     * modal (single course) and the Portal Access "Details" modal (loops
     * this per enrolled course, since a student can be in more than one).
     */
    public static function render_course_activity_html( $student_id, $course_id ) {
        global $wpdb;
        $p = $wpdb->prefix . 'mtti_';
        $student_id = (int) $student_id;
        $course_id  = (int) $course_id;

        /* Lesson-by-lesson view status */
        $lessons = $wpdb->get_results( $wpdb->prepare(
            "SELECT l.lesson_id, l.title, l.content_type, l.interactive_role,
                    lv.viewed_at,
                    (SELECT MAX(qa.attempted_at) FROM {$p}quiz_attempts qa WHERE qa.lesson_id = l.lesson_id AND qa.student_id = %d) AS last_attempt_at,
                    (SELECT MAX(qa.percent) FROM {$p}quiz_attempts qa WHERE qa.lesson_id = l.lesson_id AND qa.student_id = %d AND qa.passed = 1) AS best_pass_pct
             FROM {$p}lessons l
             LEFT JOIN {$p}lesson_views lv ON lv.lesson_id = l.lesson_id AND lv.student_id = %d
             WHERE l.course_id = %d AND l.status = 'Published' AND l.deleted_at IS NULL
             ORDER BY l.order_number ASC",
            $student_id, $student_id, $student_id, $course_id
        ) );

        /* Full quiz attempt history (every attempt, not just the passing one) */
        $quiz_attempts = $wpdb->get_results( $wpdb->prepare(
            "SELECT qa.lesson_id, l.title AS lesson_title, qa.score, qa.total, qa.percent, qa.passed, qa.attempted_at
             FROM {$p}quiz_attempts qa
             LEFT JOIN {$p}lessons l ON l.lesson_id = qa.lesson_id
             WHERE qa.student_id = %d AND l.course_id = %d
             ORDER BY qa.attempted_at DESC
             LIMIT 25",
            $student_id, $course_id
        ) );

        /* Assignment submissions in this course */
        $submissions = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.title, sub.score, a.max_score, sub.status, sub.submitted_at, sub.graded_at
             FROM {$p}assignment_submissions sub
             INNER JOIN {$p}assignments a ON a.assignment_id = sub.assignment_id
             WHERE sub.student_id = %d AND a.course_id = %d
             ORDER BY sub.submitted_at DESC",
            $student_id, $course_id
        ) );

        /* Discussion / study-chat activity in this course */
        $discussion_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}discussions WHERE student_id = %d AND course_id = %d",
            $student_id, $course_id
        ) );
        $recent_posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT message, created_at FROM {$p}discussions
             WHERE student_id = %d AND course_id = %d
             ORDER BY created_at DESC LIMIT 5",
            $student_id, $course_id
        ) );

        ob_start();
        ?>
        <div style="margin-bottom:20px;">
            <h3 style="font-size:13px;margin:0 0 8px;color:#333;">📚 Lessons (<?php echo count( array_filter( $lessons, fn( $l ) => ! empty( $l->viewed_at ) || ! empty( $l->best_pass_pct ) ) ); ?>/<?php echo count( $lessons ); ?> touched)</h3>
            <?php if ( empty( $lessons ) ) : ?>
                <p style="font-size:12px;color:#999;margin:0;">No published lessons in this course.</p>
            <?php else : ?>
                <div style="max-height:220px;overflow-y:auto;border:1px solid #eee;border-radius:6px;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <?php foreach ( $lessons as $l ) :
                        $is_quiz = $l->interactive_role === 'quiz';
                        if ( $is_quiz ) {
                            $done = ! empty( $l->best_pass_pct );
                            $status = $done ? 'Passed (' . round( $l->best_pass_pct ) . '%)' : ( $l->last_attempt_at ? 'Attempted, not passed' : 'Not attempted' );
                            $when = $l->last_attempt_at;
                        } else {
                            $done = ! empty( $l->viewed_at );
                            $status = $done ? 'Viewed' : 'Not viewed';
                            $when = $l->viewed_at;
                        }
                    ?>
                    <tr style="border-bottom:1px solid #f2f2f2;">
                        <td style="padding:6px 10px;"><?php echo $is_quiz ? '📝' : '📄'; ?> <?php echo esc_html( $l->title ); ?></td>
                        <td style="padding:6px 10px;color:<?php echo $done ? '#1D9E75' : '#999'; ?>;white-space:nowrap;"><?php echo esc_html( $status ); ?></td>
                        <td style="padding:6px 10px;color:#999;white-space:nowrap;text-align:right;"><?php echo $when ? esc_html( date( 'd M Y', strtotime( $when ) ) ) : '—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-bottom:20px;">
            <h3 style="font-size:13px;margin:0 0 8px;color:#333;">🧪 Quiz Attempts (last <?php echo count( $quiz_attempts ); ?>)</h3>
            <?php if ( empty( $quiz_attempts ) ) : ?>
                <p style="font-size:12px;color:#999;margin:0;">No quiz attempts yet.</p>
            <?php else : ?>
                <div style="max-height:180px;overflow-y:auto;border:1px solid #eee;border-radius:6px;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <?php foreach ( $quiz_attempts as $a ) : ?>
                    <tr style="border-bottom:1px solid #f2f2f2;">
                        <td style="padding:6px 10px;"><?php echo esc_html( $a->lesson_title ?: '—' ); ?></td>
                        <td style="padding:6px 10px;white-space:nowrap;"><?php echo esc_html( $a->score . '/' . $a->total . ' (' . round( $a->percent ) . '%)' ); ?></td>
                        <td style="padding:6px 10px;white-space:nowrap;color:<?php echo $a->passed ? '#1D9E75' : '#A32D2D'; ?>;"><?php echo $a->passed ? '✅ Passed' : '❌ Failed'; ?></td>
                        <td style="padding:6px 10px;color:#999;white-space:nowrap;text-align:right;"><?php echo esc_html( date( 'd M Y, H:i', strtotime( $a->attempted_at ) ) ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-bottom:20px;">
            <h3 style="font-size:13px;margin:0 0 8px;color:#333;">📎 Assignment Submissions</h3>
            <?php if ( empty( $submissions ) ) : ?>
                <p style="font-size:12px;color:#999;margin:0;">No assignments submitted in this course.</p>
            <?php else : ?>
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <?php foreach ( $submissions as $sub ) : ?>
                    <tr style="border-bottom:1px solid #f2f2f2;">
                        <td style="padding:6px 10px;"><?php echo esc_html( $sub->title ); ?></td>
                        <td style="padding:6px 10px;white-space:nowrap;"><?php echo esc_html( $sub->status ); ?></td>
                        <td style="padding:6px 10px;white-space:nowrap;"><?php echo is_null( $sub->score ) ? 'Ungraded' : esc_html( $sub->score . '/' . $sub->max_score ); ?></td>
                        <td style="padding:6px 10px;color:#999;white-space:nowrap;text-align:right;"><?php echo esc_html( date( 'd M Y', strtotime( $sub->submitted_at ) ) ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div>
            <h3 style="font-size:13px;margin:0 0 8px;color:#333;">💬 Discussion Activity (<?php echo $discussion_count; ?> post<?php echo $discussion_count === 1 ? '' : 's'; ?> total)</h3>
            <?php if ( empty( $recent_posts ) ) : ?>
                <p style="font-size:12px;color:#999;margin:0;">No discussion posts in this course.</p>
            <?php else : ?>
                <?php foreach ( $recent_posts as $post ) : ?>
                    <div style="font-size:12px;padding:8px 10px;background:#fafafa;border-radius:6px;margin-bottom:6px;">
                        <div style="color:#333;"><?php echo esc_html( wp_trim_words( $post->message, 24 ) ); ?></div>
                        <div style="color:#999;font-size:11px;margin-top:3px;"><?php echo esc_html( date( 'd M Y, H:i', strtotime( $post->created_at ) ) ); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
