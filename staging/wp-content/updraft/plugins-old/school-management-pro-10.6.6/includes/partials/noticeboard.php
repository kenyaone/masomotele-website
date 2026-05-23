<?php
defined('ABSPATH') || die();

global $wpdb;

// Helper Functions
function get_notice_link($notice) {
    if (empty($notice->link_to)) {
        return '#';
    }
    if ('url' === $notice->link_to && !empty($notice->url)) {
        return $notice->url;
    }
    if ('attachment' === $notice->link_to && !empty($notice->attachment)) {
        return wp_get_attachment_url($notice->attachment);
    }
    return '#';
}

function is_new_notice($created_at) {
    $notice_date = DateTime::createFromFormat('Y-m-d H:i:s', $created_at);
    if (!$notice_date) {
        return false;
    }
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $notice_date->setTime(0, 0, 0);
    return $today->diff($notice_date)->days < 7;
}

// Pagination Setup
$notices_per_page = WLSM_M::notices_per_page();
$current_page = isset($_GET['notices_page']) ? absint($_GET['notices_page']) : 1;
$offset = ($current_page * $notices_per_page) - $notices_per_page;

// Fetch Notices
$notices_query = WLSM_M::notices_query();
$notices_total = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(1) FROM ({$notices_query}) AS combined_table", 
        $student->class_id, 
        $school_id
    )
);

$notices = $wpdb->get_results(
    $wpdb->prepare(
        $notices_query . ' ORDER BY n.ID DESC LIMIT %d, %d', 
        $offset, 
        $notices_per_page
    )
);

// Filter Notices for Student's Class
$filtered_notices = array_filter($notices ?? [], function($notice) use ($student) {
    // Try to unserialize notice data with error suppression
    $notice_data = @unserialize($notice->notice_data);
    
    // If notice data is not an array or empty, show notice to all
    if (!is_array($notice_data) || empty($notice_data)) {
        return true;
    }
    
    // If classes array doesn't exist or is empty, show notice to all
    if (!isset($notice_data['classes']) || empty($notice_data['classes'])) {
        return true;
    }
    
    // Check if notice is for student's class or for all classes
    return in_array($student->class_id, $notice_data['classes']) || 
           in_array('all', $notice_data['classes']);
});

?>
<div class="wlsm-content-area wlsm-section-noticeboard wlsm-student-noticeboard">
    <div class="wlsm-st-main-title">
        <span><?php esc_html_e('Noticeboard', 'school-management'); ?></span>
    </div>

    <div class="wlsm-st-notices-section">
        <?php if (!empty($filtered_notices)): ?>
            <ul class="wlst-st-list wlsm-st-notices">
                <?php foreach ($filtered_notices as $notice): ?>
                    <li>
                        <span>
                            <a target="_blank" href="<?php echo esc_url(get_notice_link($notice)); ?>">
                                <?php echo esc_html(stripslashes($notice->title)); ?>
                                <span class="wlsm-st-notice-date wlsm-font-bold">
                                    <?php echo esc_html(WLSM_Config::get_date_text($notice->created_at)); ?>
                                </span>
                            </a>

                            <?php if (is_new_notice($notice->created_at)): ?>
                                <img class="wlsm-st-notice-new" src="<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/images/newicon.gif'); ?>">
                            <?php endif; ?>

                            <?php if (!empty($notice->description)): ?>
                                <p><?php echo esc_html(stripslashes($notice->description)); ?></p>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="wlsm-text-right wlsm-font-medium wlsm-font-bold wlsm-mt-2">
                <?php
                echo paginate_links([
                    'base'      => add_query_arg('notices_page', '%#%'),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => ceil($notices_total / $notices_per_page),
                    'current'   => $current_page
                ]);
                ?>
            </div>
        <?php else: ?>
            <div>
                <span class="wlsm-font-medium wlsm-font-bold">
                    <?php esc_html_e('There is no notice.', 'school-management'); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>


<style>
	
</style>