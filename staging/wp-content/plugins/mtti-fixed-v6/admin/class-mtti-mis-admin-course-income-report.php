<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Course Income Report
 * Shows income collected, expected fees, outstanding balances,
 * collection rates and enrollment counts — grouped by course.
 *
 * Menu slug: mtti-mis-course-income-report
 */
class MTTI_MIS_Admin_Course_Income_Report {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /* ─── Helpers ───────────────────────────────────────────────────── */

    private function kes( $n ) {
        return 'KES ' . number_format( (float) $n, 2 );
    }

    /* ─── Data layer ─────────────────────────────────────────────────── */

    /**
     * Returns one row per course with aggregated income figures.
     * Joins: courses → enrollments → student_balances → payments
     */
    private function get_course_income( $filters = [] ) {
        global $wpdb;
        $p = $wpdb->prefix . 'mtti_';

        /* Optional WHERE clauses */
        $where_course = [];
        $where_args   = [];

        if ( ! empty( $filters['category'] ) ) {
            $where_course[] = 'c.category = %s';
            $where_args[]   = $filters['category'];
        }
        if ( ! empty( $filters['status'] ) ) {
            $where_course[] = 'c.status = %s';
            $where_args[]   = $filters['status'];
        }
        if ( ! empty( $filters['date_from'] ) ) {
            $where_course[] = 'e.enrollment_date >= %s';
            $where_args[]   = $filters['date_from'];
        }
        if ( ! empty( $filters['date_to'] ) ) {
            $where_course[] = 'e.enrollment_date <= %s';
            $where_args[]   = $filters['date_to'];
        }

        $where_sql = $where_course
            ? 'WHERE ' . implode( ' AND ', $where_course )
            : '';

        $sql = "
            SELECT
                c.course_id,
                c.course_code,
                c.course_name,
                c.category,
                c.fee          AS course_fee,
                c.status       AS course_status,

                COUNT( DISTINCT e.enrollment_id )   AS total_enrollments,
                COUNT( DISTINCT e.student_id )      AS total_students,

                /* Expected = fee × enrollments (excluding dropped students) */
                COALESCE( SUM( CASE WHEN e.status NOT IN ('Dropped','Cancelled')
                                    THEN c.fee ELSE 0 END ), 0 )   AS expected_income,

                /* Collected = sum of completed payments linked via enrollment_id */
                COALESCE( (
                    SELECT SUM( pay.amount )
                    FROM   {$p}payments pay
                    INNER JOIN {$p}enrollments enr ON enr.enrollment_id = pay.enrollment_id
                    WHERE  enr.course_id = c.course_id
                      AND  pay.status    = 'Completed'
                ), 0 )   AS collected_income,

                /* Discounts granted on this course */
                COALESCE( (
                    SELECT SUM( pay.discount )
                    FROM   {$p}payments pay
                    INNER JOIN {$p}enrollments enr ON enr.enrollment_id = pay.enrollment_id
                    WHERE  enr.course_id = c.course_id
                      AND  pay.status    = 'Completed'
                ), 0 )   AS total_discounts,

                /* Most recent payment date for this course */
                (
                    SELECT MAX( pay.payment_date )
                    FROM   {$p}payments pay
                    INNER JOIN {$p}enrollments enr ON enr.enrollment_id = pay.enrollment_id
                    WHERE  enr.course_id = c.course_id
                      AND  pay.status    = 'Completed'
                )   AS last_payment_date

            FROM      {$p}courses c
            LEFT JOIN {$p}enrollments e ON e.course_id = c.course_id
            $where_sql
            GROUP BY  c.course_id
            ORDER BY  collected_income DESC
        ";

        if ( $where_args ) {
            $sql = $wpdb->prepare( $sql, ...$where_args ); // phpcs:ignore
        }

        return $wpdb->get_results( $sql );
    }

    private function get_categories() {
        global $wpdb;
        return $wpdb->get_col(
            "SELECT DISTINCT category FROM {$wpdb->prefix}mtti_courses WHERE category != '' ORDER BY category"
        );
    }

    /* ─── CSV export ──────────────────────────────────────────────────── */

    private function maybe_export_csv( $rows ) {
        if ( empty( $_GET['export'] ) || $_GET['export'] !== 'csv' ) return;
        if ( ! current_user_can( 'manage_payments' ) ) wp_die( 'Permission denied.' );

        $filename = 'course-income-report-' . date( 'Y-m-d' ) . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, [
            'Course Code', 'Course Name', 'Category', 'Status',
            'Course Fee (KES)', 'Enrollments', 'Students',
            'Expected Income (KES)', 'Collected Income (KES)',
            'Discounts (KES)', 'Outstanding Balance (KES)',
            'Collection Rate (%)', 'Last Payment Date',
        ] );
        foreach ( $rows as $r ) {
            $expected   = (float) $r->expected_income;
            $collected  = (float) $r->collected_income;
            $discounts  = (float) $r->total_discounts;
            $balance    = max( 0, $expected - $discounts - $collected );
            $rate       = $expected > 0 ? round( $collected / $expected * 100, 1 ) : 0;
            fputcsv( $out, [
                $r->course_code,
                $r->course_name,
                $r->category,
                $r->course_status,
                number_format( $r->course_fee, 2 ),
                $r->total_enrollments,
                $r->total_students,
                number_format( $expected, 2 ),
                number_format( $collected, 2 ),
                number_format( $discounts, 2 ),
                number_format( $balance, 2 ),
                $rate,
                $r->last_payment_date ?: '-',
            ] );
        }
        fclose( $out );
        exit;
    }

    /* ─── Main display ───────────────────────────────────────────────── */

    public function display() {
        if ( ! current_user_can( 'manage_payments' ) && ! current_user_can( 'manage_mtti' ) ) {
            wp_die( __( 'You do not have permission to view this page.' ) );
        }

        /* Filters */
        $f_category  = sanitize_text_field( $_GET['category']  ?? '' );
        $f_status    = sanitize_text_field( $_GET['status']    ?? '' );
        $f_date_from = sanitize_text_field( $_GET['date_from'] ?? '' );
        $f_date_to   = sanitize_text_field( $_GET['date_to']   ?? '' );

        $filters = array_filter( [
            'category'  => $f_category,
            'status'    => $f_status,
            'date_from' => $f_date_from,
            'date_to'   => $f_date_to,
        ] );

        $rows       = $this->get_course_income( $filters );
        $categories = $this->get_categories();

        /* CSV export (happens before any HTML) */
        $this->maybe_export_csv( $rows );

        /* Totals */
        $grand_expected   = 0;
        $grand_collected  = 0;
        $grand_discounts  = 0;
        $grand_balance    = 0;
        $grand_enrollments = 0;

        foreach ( $rows as $r ) {
            $expected  = (float) $r->expected_income;
            $collected = (float) $r->collected_income;
            $discounts = (float) $r->total_discounts;
            $balance   = max( 0, $expected - $discounts - $collected );

            $grand_expected    += $expected;
            $grand_collected   += $collected;
            $grand_discounts   += $discounts;
            $grand_balance     += $balance;
            $grand_enrollments += (int) $r->total_enrollments;
        }
        $grand_rate = $grand_expected > 0
            ? round( $grand_collected / $grand_expected * 100, 1 )
            : 0;

        /* Current page URL for filter form */
        $page_url = admin_url( 'admin.php?page=mtti-mis-course-income-report' );
        ?>
        <div class="wrap">
            <h1 style="display:flex;align-items:center;gap:12px;">
                📊 Course Income Report
                <a href="<?php echo esc_url( add_query_arg( array_merge( $_GET, ['export' => 'csv'] ) ) ); ?>"
                   class="page-title-action" style="background:#1D9E75;color:#fff;border-color:#1D9E75;">
                    ⬇ Export CSV
                </a>
            </h1>

            <!-- ── Filter bar ─────────────────────────────────────── -->
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin:16px 0 20px;">
                <input type="hidden" name="page" value="mtti-mis-course-income-report">

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Category</label>
                    <select name="category" style="height:34px;padding:0 8px;">
                        <option value="">All categories</option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr($cat); ?>" <?php selected($f_category,$cat); ?>>
                                <?php echo esc_html($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Course Status</label>
                    <select name="status" style="height:34px;padding:0 8px;">
                        <option value="">All statuses</option>
                        <option value="Active"   <?php selected($f_status,'Active'); ?>>Active</option>
                        <option value="Inactive" <?php selected($f_status,'Inactive'); ?>>Inactive</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Enrolled from</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($f_date_from); ?>"
                           style="height:34px;padding:0 8px;">
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Enrolled to</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($f_date_to); ?>"
                           style="height:34px;padding:0 8px;">
                </div>

                <div style="display:flex;gap:8px;">
                    <button type="submit" class="button button-primary">🔍 Apply</button>
                    <a href="<?php echo esc_url($page_url); ?>" class="button">✕ Reset</a>
                </div>
            </form>

            <!-- ── Summary cards ──────────────────────────────────── -->
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:24px;">

                <?php
                $cards = [
                    [ 'label' => 'Total collected',    'value' => $this->kes($grand_collected),  'color' => '#1D9E75', 'sub' => number_format($grand_enrollments) . ' enrollments' ],
                    [ 'label' => 'Expected income',    'value' => $this->kes($grand_expected),   'color' => '#185FA5', 'sub' => 'At full capacity' ],
                    [ 'label' => 'Outstanding balance','value' => $this->kes($grand_balance),    'color' => '#A32D2D', 'sub' => 'Fees not yet paid' ],
                    [ 'label' => 'Total discounts',    'value' => $this->kes($grand_discounts),  'color' => '#854F0B', 'sub' => 'Across all courses' ],
                    [ 'label' => 'Overall collection', 'value' => $grand_rate . '%',             'color' => '#533AB7', 'sub' => count($rows) . ' courses shown' ],
                ];
                foreach ( $cards as $card ) : ?>
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:16px 20px;min-width:160px;flex:1;border-top:3px solid <?php echo esc_attr($card['color']); ?>;">
                    <div style="font-size:12px;color:#666;margin-bottom:6px;"><?php echo esc_html($card['label']); ?></div>
                    <div style="font-size:22px;font-weight:600;color:#1d2327;"><?php echo esc_html($card['value']); ?></div>
                    <div style="font-size:11px;color:#999;margin-top:4px;"><?php echo esc_html($card['sub']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Bar chart ──────────────────────────────────────── -->
            <?php if ( $rows ) : ?>
            <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;margin-bottom:24px;">
                <h3 style="margin:0 0 16px;font-size:14px;font-weight:600;">Income by course</h3>
                <div style="position:relative;width:100%;height:280px;">
                    <canvas id="mtti-course-income-chart"
                            role="img"
                            aria-label="Bar chart showing collected vs expected income per course">
                    </canvas>
                </div>
                <!-- Legend -->
                <div style="display:flex;gap:20px;margin-top:12px;font-size:12px;color:#555;">
                    <span><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:#1D9E75;vertical-align:middle;margin-right:5px;"></span>Collected</span>
                    <span><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:#e0e0e0;vertical-align:middle;margin-right:5px;"></span>Expected</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Table ──────────────────────────────────────────── -->
            <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:0;overflow:hidden;">
                <table class="wp-list-table widefat fixed striped" style="margin:0;border:none;">
                    <thead>
                        <tr>
                            <th style="width:220px;">Course</th>
                            <th style="text-align:right;width:100px;">Fee (KES)</th>
                            <th style="text-align:right;width:90px;">Students</th>
                            <th style="text-align:right;width:150px;">Expected (KES)</th>
                            <th style="text-align:right;width:150px;">Collected (KES)</th>
                            <th style="text-align:right;width:120px;">Discounts (KES)</th>
                            <th style="text-align:right;width:150px;">Balance (KES)</th>
                            <th style="width:160px;">Collection Rate</th>
                            <th style="text-align:center;width:100px;">Status</th>
                            <th style="text-align:center;width:100px;">Last Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ( empty( $rows ) ) : ?>
                        <tr><td colspan="10" style="text-align:center;padding:40px;color:#666;">
                            No courses found. Try adjusting your filters.
                        </td></tr>
                    <?php else :
                        foreach ( $rows as $r ) :
                            $expected  = (float) $r->expected_income;
                            $collected = (float) $r->collected_income;
                            $discounts = (float) $r->total_discounts;
                            $balance   = max( 0, $expected - $discounts - $collected );
                            $rate      = $expected > 0 ? round( $collected / $expected * 100, 1 ) : 0;

                            if ( $rate >= 90 ) {
                                $rate_color = '#1D9E75'; $rate_label = 'Good';      $rate_bg = '#EAF3DE';
                            } elseif ( $rate >= 50 ) {
                                $rate_color = '#854F0B'; $rate_label = 'Partial';   $rate_bg = '#FAEEDA';
                            } else {
                                $rate_color = '#A32D2D'; $rate_label = 'Low';       $rate_bg = '#FCEBEB';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($r->course_name); ?></strong><br>
                                <span style="font-size:11px;color:#888;"><?php echo esc_html($r->course_code); ?></span>
                                &nbsp;
                                <span style="font-size:11px;background:#e8f4fc;color:#185FA5;padding:1px 6px;border-radius:9999px;"><?php echo esc_html($r->category); ?></span>
                            </td>
                            <td style="text-align:right;"><?php echo number_format($r->course_fee, 2); ?></td>
                            <td style="text-align:right;"><?php echo (int)$r->total_students; ?></td>
                            <td style="text-align:right;"><?php echo number_format($expected, 2); ?></td>
                            <td style="text-align:right;font-weight:600;"><?php echo number_format($collected, 2); ?></td>
                            <td style="text-align:right;color:#854F0B;"><?php echo number_format($discounts, 2); ?></td>
                            <td style="text-align:right;color:<?php echo $balance > 0 ? '#A32D2D' : '#1D9E75'; ?>;font-weight:600;">
                                <?php echo number_format($balance, 2); ?>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="flex:1;background:#f0f0f0;border-radius:4px;height:8px;overflow:hidden;">
                                        <div style="height:8px;border-radius:4px;width:<?php echo min($rate,100); ?>%;background:<?php echo esc_attr($rate_color); ?>;"></div>
                                    </div>
                                    <span style="font-size:12px;color:#333;min-width:36px;"><?php echo $rate; ?>%</span>
                                </div>
                                <span style="font-size:11px;background:<?php echo esc_attr($rate_bg); ?>;color:<?php echo esc_attr($rate_color); ?>;padding:1px 7px;border-radius:9999px;margin-top:4px;display:inline-block;">
                                    <?php echo esc_html($rate_label); ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <?php if ( $r->course_status === 'Active' ) : ?>
                                    <span style="color:#1D9E75;font-weight:600;">● Active</span>
                                <?php else : ?>
                                    <span style="color:#999;">○ <?php echo esc_html($r->course_status); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;font-size:12px;color:#666;">
                                <?php echo $r->last_payment_date ? esc_html(date('d M Y', strtotime($r->last_payment_date))) : '—'; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>

                    <?php if ( $rows ) : ?>
                    <tfoot style="background:#f9f9f9;font-weight:600;border-top:2px solid #ccc;">
                        <tr>
                            <td>Totals — <?php echo count($rows); ?> course(s)</td>
                            <td></td>
                            <td style="text-align:right;"><?php echo number_format($grand_enrollments); ?></td>
                            <td style="text-align:right;"><?php echo number_format($grand_expected, 2); ?></td>
                            <td style="text-align:right;color:#1D9E75;"><?php echo number_format($grand_collected, 2); ?></td>
                            <td style="text-align:right;color:#854F0B;"><?php echo number_format($grand_discounts, 2); ?></td>
                            <td style="text-align:right;color:#A32D2D;"><?php echo number_format($grand_balance, 2); ?></td>
                            <td colspan="3" style="color:#533AB7;"><?php echo $grand_rate; ?>% overall collection</td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div><!-- /table wrapper -->

        </div><!-- /wrap -->

        <!-- ── Chart.js ────────────────────────────────────────────── -->
        <?php if ( $rows ) : ?>
        <script>
        (function() {
            var labels   = <?php echo json_encode( array_map( fn($r) => $r->course_code, $rows ) ); ?>;
            var collected = <?php echo json_encode( array_map( fn($r) => round( (float)$r->collected_income, 2 ), $rows ) ); ?>;
            var expected  = <?php echo json_encode( array_map( fn($r) => round( (float)$r->expected_income,  2 ), $rows ) ); ?>;

            function initChart() {
                if ( typeof Chart === 'undefined' ) {
                    setTimeout( initChart, 200 );
                    return;
                }
                var ctx = document.getElementById('mtti-course-income-chart');
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Collected',
                                data: collected,
                                backgroundColor: '#1D9E75',
                                borderRadius: 4,
                            },
                            {
                                label: 'Expected',
                                data: expected,
                                backgroundColor: '#e0e0e0',
                                borderRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.dataset.label + ': KES ' + ctx.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { font: { size: 11 }, maxRotation: 35, autoSkip: false },
                                grid: { display: false }
                            },
                            y: {
                                ticks: {
                                    font: { size: 11 },
                                    callback: function(v) {
                                        return v >= 1000000
                                            ? 'KES ' + (v/1000000).toFixed(1) + 'M'
                                            : 'KES ' + (v/1000).toFixed(0) + 'K';
                                    }
                                },
                                grid: { color: 'rgba(0,0,0,0.06)' }
                            }
                        }
                    }
                });
            }
            initChart();
        })();
        </script>
        <?php endif; ?>
        <?php
    }
}
