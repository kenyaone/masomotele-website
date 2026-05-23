<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/global.php';
global $wpdb;

$school_id  = $current_school['id'];
$session_id = $current_session['ID'];

// Fetch ticket statistics
$total_tickets = WLSM_M_Staff_Tickets::get_tickets_count($school_id);
$stats = WLSM_M_Staff_Tickets::get_tickets_stats($school_id);

// Prepare data for the charts
$chart_data = [
    'labels' => ['Open', 'In Progress', 'Resolved', 'Closed'],
    'datasets' => [
        [
            'label' => 'Tickets', // Add label here
            'data' => [
                $stats['open'],
                $stats['in_progress'],
                $stats['resolved'],
                $stats['closed']
            ],
            'backgroundColor' => [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0'
            ]
        ]
    ]
];
?>

<div class="wlsm container-fluid">
    <?php require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/partials/header.php'; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="text-center wlsm-section-heading-block">
                <span class="wlsm-section-heading">
                    <i class="fas fa-ticket-alt"></i>
                    <?php esc_html_e('Tickets', 'school-management'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Ticket Chart Section -->
    <div class="row mt-3">
        <div class="col-md-6">
            <canvas id="ticketPieChart"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="ticketBarChart"></canvas>
        </div>
    </div>

    <!-- Ticket Stats Section -->
    <div class="row mt-1 wlsm-stats-blocks">
        <div class="col-md-4 col-lg-3">
            <div class="wlsm-stats-block">
                <i class="fas fa-ticket-alt wlsm-stats-icon"></i>
                <div class="wlsm-stats-counter"><?php echo esc_html($total_tickets); ?></div>
                <div class="wlsm-stats-label">
                    <?php esc_html_e('Total Tickets', 'school-management'); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3">
            <div class="wlsm-stats-block">
                <i class="fas fa-folder-open wlsm-stats-icon"></i>
                <div class="wlsm-stats-counter"><?php echo esc_html($stats['open']); ?></div>
                <div class="wlsm-stats-label">
                    <?php esc_html_e('Open Tickets', 'school-management'); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3">
            <div class="wlsm-stats-block">
                <i class="fas fa-spinner wlsm-stats-icon"></i>
                <div class="wlsm-stats-counter"><?php echo esc_html($stats['in_progress']); ?></div>
                <div class="wlsm-stats-label">
                    <?php esc_html_e('In Progress Tickets', 'school-management'); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3">
            <div class="wlsm-stats-block">
                <i class="fas fa-check-circle wlsm-stats-icon"></i>
                <div class="wlsm-stats-counter"><?php echo esc_html($stats['resolved']); ?></div>
                <div class="wlsm-stats-label">
                    <?php esc_html_e('Resolved Tickets', 'school-management'); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3">
            <div class="wlsm-stats-block">
                <i class="fas fa-times-circle wlsm-stats-icon"></i>
                <div class="wlsm-stats-counter"><?php echo esc_html($stats['closed']); ?></div>
                <div class="wlsm-stats-label">
                    <?php esc_html_e('Closed Tickets', 'school-management'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var pieCtx = document.getElementById('ticketPieChart').getContext('2d');
        var barCtx = document.getElementById('ticketBarChart').getContext('2d');
        var chartData = <?php echo json_encode($chart_data); ?>;

        new Chart(pieCtx, {
            type: 'pie',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: '<?php esc_html_e('Ticket Status Distribution', 'school-management'); ?>'
                    }
                }
            }
        });

        new Chart(barCtx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: '<?php esc_html_e('Ticket Status Distribution', 'school-management'); ?>'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
