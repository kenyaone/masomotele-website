<?php
defined( 'ABSPATH' ) || die();

$student = WLSM_M_Parent::fetch_student( $student_id );
$school_id  = $student->school_id;
if ( ! $student ) {
	die;
}
?>

<div class="wlsm-parent-student-entity">
<?php
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/parent/partials/student.php';
?>
</div>

<div class="wlsm-content-area wlsm-section-ticket-history wlsm-student-ticket-history">
    <div class="wlsm-st-main-title">
        <span>
            <?php esc_html_e('Add Ticket', 'school-management'); ?>
        </span>
    </div>

    <form id="addTicketForm" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
        <div class="form-group">
            <label for="ticketTitle"><?php esc_html_e('Title', 'school-management'); ?></label>
            <input type="text" class="form-control" id="ticketTitle" name="title" required>
        </div>
        <div class="form-group">
            <label for="ticketDescription"><?php esc_html_e('Description', 'school-management'); ?></label>
            <textarea class="form-control" id="ticketDescription" name="description" rows="3" required></textarea>
        </div>
        <input type="hidden" name="action" value="wlsm_save_ticket">
        <input type="hidden" name="student_id" value="<?php echo esc_attr($student_id); ?>">
        <?php wp_nonce_field('add-ticket', 'add_ticket_nonce'); ?>
        <button type="submit" id="wlsm-submit-ticket-btn" class="btn btn-primary" data-confirm="<?php esc_attr_e('Are you sure you want to submit this ticket?', 'school-management'); ?>">
            <?php esc_html_e('Submit', 'school-management'); ?>
        </button>
    </form>
</div>
