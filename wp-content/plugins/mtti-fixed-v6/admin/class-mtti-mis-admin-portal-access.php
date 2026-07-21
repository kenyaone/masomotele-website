<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Portal Access Dashboard
 * Read-only view of every student's login activity on the student portal
 * (wpcu_mtti_student_accounts.last_login) — covers ALL students regardless
 * of delivery mode, unlike the Online Students report which is scoped to
 * online enrollments only.
 *
 * Joined via admission_number rather than student_accounts.student_id —
 * a chunk of legacy account rows carry stale/dangling student_id values
 * (pre-dating the "never hard-delete" policy), so admission_number is the
 * only reliably current key between the two tables.
 *
 * Menu slug: mtti-mis-portal-access
 */
class MTTI_MIS_Admin_Portal_Access {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /* ─── Data layer ─────────────────────────────────────────────────── */

    private function get_students_with_login_status( $filters = [] ) {
        global $wpdb;
        $p = $wpdb->prefix . 'mtti_';

        $where = [ 's.deleted_at IS NULL' ];
        $args  = [];

        if ( ! empty( $filters['search'] ) ) {
            $where[] = '(u.display_name LIKE %s OR s.admission_number LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $args[]  = $like; $args[] = $like;
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $where );

        $sql = "
            SELECT
                s.student_id, u.display_name, s.admission_number, s.status AS student_status,
                GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') AS courses,
                GROUP_CONCAT(DISTINCT e.delivery_mode ORDER BY e.delivery_mode SEPARATOR ',') AS delivery_modes,
                a.last_login, a.login_attempts, a.locked_until, a.must_change_password,
                (a.account_id IS NOT NULL) AS has_account
            FROM {$p}students s
            INNER JOIN {$wpdb->users} u ON u.ID = s.user_id
            LEFT JOIN {$p}student_accounts a ON a.admission_number = s.admission_number
            LEFT JOIN {$p}enrollments e ON e.student_id = s.student_id AND e.deleted_at IS NULL
            LEFT JOIN {$p}courses c ON c.course_id = e.course_id
            $where_sql
            GROUP BY s.student_id
            ORDER BY a.last_login IS NULL, a.last_login DESC
        ";

        if ( $args ) {
            $sql = $wpdb->prepare( $sql, ...$args ); // phpcs:ignore
        }

        $rows = $wpdb->get_results( $sql );

        foreach ( $rows as $r ) {
            $modes = array_filter( array_unique( explode( ',', (string) $r->delivery_modes ) ) );
            if ( empty( $modes ) ) {
                $r->delivery_mode_label = '—';
            } elseif ( count( $modes ) > 1 ) {
                $r->delivery_mode_label = 'Mixed';
            } else {
                $r->delivery_mode_label = ucfirst( reset( $modes ) );
            }
        }

        if ( ! empty( $filters['activity'] ) ) {
            $rows = array_values( array_filter( $rows, function ( $r ) use ( $filters ) {
                $never  = empty( $r->last_login );
                $active = ! $never && strtotime( $r->last_login ) >= strtotime( '-7 days' );
                if ( $filters['activity'] === 'never' )   return $never;
                if ( $filters['activity'] === 'active_7d' ) return $active;
                if ( $filters['activity'] === 'inactive' )  return ! $never && ! $active;
                return true;
            } ) );
        }

        if ( ! empty( $filters['delivery_mode'] ) ) {
            $rows = array_values( array_filter( $rows, fn( $r ) => $r->delivery_mode_label === $filters['delivery_mode'] ) );
        }

        return $rows;
    }

    /* ─── Main display ───────────────────────────────────────────────── */

    public function display() {
        if ( ! current_user_can( 'manage_students' ) && ! current_user_can( 'manage_mtti' ) ) {
            wp_die( __( 'You do not have permission to view this page.' ) );
        }

        $filters = array_filter( [
            'search'        => sanitize_text_field( $_GET['search']        ?? '' ),
            'activity'      => sanitize_text_field( $_GET['activity']      ?? '' ),
            'delivery_mode' => sanitize_text_field( $_GET['delivery_mode'] ?? '' ),
        ] );

        $rows = $this->get_students_with_login_status( $filters );

        $total          = count( $rows );
        $never_logged_in = count( array_filter( $rows, fn( $r ) => empty( $r->last_login ) ) );
        $active_7d      = count( array_filter( $rows, fn( $r ) => ! empty( $r->last_login ) && strtotime( $r->last_login ) >= strtotime( '-7 days' ) ) );
        $locked_now     = count( array_filter( $rows, fn( $r ) => ! empty( $r->locked_until ) && strtotime( $r->locked_until ) > time() ) );

        $page_url = admin_url( 'admin.php?page=mtti-mis-portal-access' );
        ?>
        <div class="wrap">
            <h1>🔑 Portal Access</h1>
            <p style="color:#666;margin-top:-6px;">Every student's login activity on the student portal — regardless of delivery mode. Data comes from the portal's own login tracking, not enrollment records.</p>

            <!-- ── Summary cards ──────────────────────────────────── -->
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin:16px 0 24px;">
                <?php
                $cards = [
                    [ 'label' => 'Total students',    'value' => number_format( $total ),           'color' => '#185FA5', 'sub' => 'All delivery modes' ],
                    [ 'label' => 'Active in last 7d',  'value' => number_format( $active_7d ),        'color' => '#1D9E75', 'sub' => 'Logged in recently' ],
                    [ 'label' => 'Never logged in',    'value' => number_format( $never_logged_in ),  'color' => '#A32D2D', 'sub' => 'No portal account activity' ],
                    [ 'label' => 'Currently locked',   'value' => number_format( $locked_now ),       'color' => '#854F0B', 'sub' => 'Too many failed attempts' ],
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
                <input type="hidden" name="page" value="mtti-mis-portal-access">

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Activity</label>
                    <select name="activity" style="height:34px;padding:0 8px;">
                        <option value="">All</option>
                        <option value="active_7d" <?php selected( $filters['activity'] ?? '', 'active_7d' ); ?>>Active in last 7 days</option>
                        <option value="inactive"  <?php selected( $filters['activity'] ?? '', 'inactive' ); ?>>Logged in before, now inactive</option>
                        <option value="never"     <?php selected( $filters['activity'] ?? '', 'never' ); ?>>Never logged in</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Delivery Mode</label>
                    <select name="delivery_mode" style="height:34px;padding:0 8px;">
                        <option value="">All</option>
                        <?php foreach ( [ 'Physical', 'Online', 'Mixed' ] as $m ) : ?>
                            <option value="<?php echo esc_attr($m); ?>" <?php selected( $filters['delivery_mode'] ?? '', $m ); ?>><?php echo esc_html($m); ?></option>
                        <?php endforeach; ?>
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
                            <th>Course(s)</th>
                            <th style="width:100px;">Mode</th>
                            <th style="width:140px;">Last Login</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ( empty( $rows ) ) : ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:#666;">
                            No students found. Try adjusting your filters.
                        </td></tr>
                    <?php else :
                        foreach ( $rows as $r ) :
                            $locked = ! empty( $r->locked_until ) && strtotime( $r->locked_until ) > time();
                            if ( $locked ) {
                                $status_label = 'Locked'; $status_color = '#A32D2D'; $status_bg = '#FCEBEB';
                            } elseif ( empty( $r->last_login ) ) {
                                $status_label = 'Never logged in'; $status_color = '#A32D2D'; $status_bg = '#FCEBEB';
                            } elseif ( strtotime( $r->last_login ) >= strtotime( '-7 days' ) ) {
                                $status_label = 'Active'; $status_color = '#1D9E75'; $status_bg = '#EAF3DE';
                            } else {
                                $status_label = 'Inactive'; $status_color = '#854F0B'; $status_bg = '#FAEEDA';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $r->display_name ); ?></strong><br>
                                <span style="font-size:11px;color:#888;"><?php echo esc_html( $r->admission_number ); ?></span>
                            </td>
                            <td style="font-size:12px;"><?php echo esc_html( $r->courses ?: '—' ); ?></td>
                            <td style="font-size:12px;">
                                <?php
                                $mode_colors = [ 'Physical' => '#185FA5', 'Online' => '#1D9E75', 'Mixed' => '#7B1FA2' ];
                                $mode_color  = $mode_colors[ $r->delivery_mode_label ] ?? '#999';
                                ?>
                                <span style="font-size:11px;color:<?php echo esc_attr($mode_color); ?>;font-weight:600;"><?php echo esc_html( $r->delivery_mode_label ); ?></span>
                            </td>
                            <td style="font-size:12px;color:#666;">
                                <?php echo $r->last_login ? esc_html( date( 'd M Y, H:i', strtotime( $r->last_login ) ) ) : '—'; ?>
                            </td>
                            <td>
                                <span style="font-size:11px;background:<?php echo esc_attr($status_bg); ?>;color:<?php echo esc_attr($status_color); ?>;padding:2px 8px;border-radius:9999px;font-weight:600;">
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="button button-small" onclick="mttiShowPortalAccessActivity(<?php echo (int) $r->student_id; ?>, '<?php echo esc_js( $r->display_name ); ?>')">🔍 Details</button>
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
        <div id="mtti-portal-activity-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:12px;padding:28px 32px;max-width:720px;width:94%;max-height:88vh;overflow-y:auto;position:relative;box-shadow:0 8px 40px rgba(0,0,0,.3);">
                <button type="button" onclick="document.getElementById('mtti-portal-activity-modal').style.display='none';" style="position:absolute;top:14px;right:18px;background:none;border:none;font-size:22px;cursor:pointer;color:#666;">✕</button>
                <h2 style="margin:0 0 4px;font-size:18px;">📋 Learner Activity</h2>
                <p id="mtti-portal-activity-subtitle" style="color:#666;font-size:13px;margin:0 0 18px;"></p>
                <div id="mtti-portal-activity-spinner" style="text-align:center;padding:40px;color:#888;font-size:13px;">⏳ Loading…</div>
                <div id="mtti-portal-activity-body"></div>
            </div>
        </div>
        <script>
        function mttiShowPortalAccessActivity(studentId, label) {
            document.getElementById('mtti-portal-activity-modal').style.display = 'flex';
            document.getElementById('mtti-portal-activity-subtitle').innerText = label;
            document.getElementById('mtti-portal-activity-spinner').style.display = 'block';
            document.getElementById('mtti-portal-activity-body').innerHTML = '';
            jQuery.ajax({
                url: ajaxurl, method: 'POST',
                data: {
                    action: 'mtti_portal_access_activity',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'mtti_portal_access_activity' ) ); ?>',
                    student_id: studentId
                },
                success: function ( r ) {
                    document.getElementById('mtti-portal-activity-spinner').style.display = 'none';
                    document.getElementById('mtti-portal-activity-body').innerHTML = r.success ? r.data.html : '<p style="color:#A32D2D;">' + (r.data && r.data.message ? r.data.message : 'Failed to load activity.') + '</p>';
                },
                error: function () {
                    document.getElementById('mtti-portal-activity-spinner').style.display = 'none';
                    document.getElementById('mtti-portal-activity-body').innerHTML = '<p style="color:#A32D2D;">Network error. Please try again.</p>';
                }
            });
        }
        </script>
        <?php
    }

    /**
     * AJAX: full activity drill-down for one student, across every course
     * they're enrolled in (any delivery mode) — login info once at the top,
     * then a section per course reusing the same lesson/quiz/assignment/
     * discussion breakdown the Online Students "Details" modal uses.
     */
    public function ajax_get_activity() {
        if ( ! current_user_can( 'manage_students' ) && ! current_user_can( 'manage_mtti' ) ) {
            wp_send_json_error( [ 'message' => 'You do not have permission to view this.' ] );
        }
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mtti_portal_access_activity' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed. Please refresh the page.' ] );
        }

        $student_id = isset( $_POST['student_id'] ) ? (int) $_POST['student_id'] : 0;
        if ( ! $student_id ) {
            wp_send_json_error( [ 'message' => 'Invalid student.' ] );
        }

        global $wpdb;
        $p = $wpdb->prefix . 'mtti_';

        $student = $wpdb->get_row( $wpdb->prepare(
            "SELECT s.student_id, s.admission_number
             FROM {$p}students s
             WHERE s.student_id = %d AND s.deleted_at IS NULL",
            $student_id
        ) );

        if ( ! $student ) {
            wp_send_json_error( [ 'message' => 'Student not found.' ] );
        }

        $account = $wpdb->get_row( $wpdb->prepare(
            "SELECT last_login, login_attempts, locked_until FROM {$p}student_accounts WHERE admission_number = %s",
            $student->admission_number
        ) );

        $enrollments = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.course_id, e.delivery_mode, c.course_code, c.course_name
             FROM {$p}enrollments e
             INNER JOIN {$p}courses c ON c.course_id = e.course_id
             WHERE e.student_id = %d AND e.deleted_at IS NULL
             ORDER BY c.course_name",
            $student_id
        ) );

        require_once MTTI_MIS_PLUGIN_DIR . 'admin/class-mtti-mis-admin-online-students.php';

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

        <?php if ( empty( $enrollments ) ) : ?>
            <p style="font-size:12px;color:#999;">No enrollments found for this student.</p>
        <?php else : foreach ( $enrollments as $e ) : ?>
            <div style="margin-bottom:24px;border-top:1px solid #eee;padding-top:16px;">
                <h3 style="font-size:14px;margin:0 0 12px;color:#1d2327;">
                    🎓 <?php echo esc_html( $e->course_name . ' (' . $e->course_code . ')' ); ?>
                    <span style="font-size:11px;font-weight:600;color:#666;margin-left:6px;">— <?php echo esc_html( ucfirst( $e->delivery_mode ) ); ?></span>
                </h3>
                <?php echo MTTI_MIS_Admin_Online_Students::render_course_activity_html( $student_id, (int) $e->course_id ); ?>
            </div>
        <?php endforeach; endif; ?>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }
}
