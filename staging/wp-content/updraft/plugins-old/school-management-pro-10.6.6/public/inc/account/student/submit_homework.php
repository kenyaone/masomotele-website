<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/student/partials/navigation.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Class.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M.php';

$student_id = $student->ID;
$nonce_action  = 'submit-student-homework';
$school_id  = $student->school_id;

global $wpdb;

$query = WLSM_M_Staff_Class::fetch_homework_query($school_id, $session_id);
$query_filter = $wpdb->get_results($query);
$section_id = $student->section_id;
$homeworks_per_page = WLSM_M::homeworks_per_page();
$homeworks_query = WLSM_M::homeworks_query();
$homework = '';

$homeworks_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$homeworks_query}) AS combined_table", $school_id, $session_id, $section_id));
$homeworks_page = isset($_GET['homeworks_page']) ? absint($_GET['homeworks_page']) : 1;
$homeworks_page_offset = ($homeworks_page * $homeworks_per_page) - $homeworks_per_page;
// $id = '';
if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
	$id    = absint( $_GET['id'] );

	$homeworks = $wpdb->get_results(('SELECT hw.ID, hs.attachments, hw.title, hs.student_id, hs.created_at, hs.description FROM ' .WLSM_HOMEWORK_SUBMISSION.' as hs
	JOIN ' . WLSM_HOMEWORK . ' as hw ON hw.ID = hs.submission_id
	WHERE hs.ID = '.$id.' ORDER BY hs.ID DESC'));
} else {
	$homeworks = $wpdb->get_results($wpdb->prepare($homeworks_query . ' ORDER BY hw.homework_date DESC LIMIT %d, %d', $school_id, $session_id, $section_id, $homeworks_page_offset, $homeworks_per_page));
}
?>
<div class="wlsm-content-area wlsm-section-leave-request wlsm-student-leave-request">
	<div class="wlsm-registration-section">
		<div class="wlsm-registration-section-header">
			<h3 class="wlsm-registration-section-title">
				<?php esc_html_e('Submit Homework & Assignment', 'school-management'); ?>
			</h3>
		</div>

		<div class="wlsm-registration-section-content">
			<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-submit-student-homework-submission-form" enctype="multipart/form-data">

			<?php $nonce = wp_create_nonce($nonce_action); ?>
			<input type="hidden" name="<?php echo esc_attr($nonce_action); ?>" value="<?php echo esc_attr($nonce); ?>">

			<input type="hidden" name="action" value="wlsm-p-st-submit-studennt-homework-submission">
			<input type="hidden" name="homework_sub_id" value="<?php if(isset( $id ) && !empty($id) ) {echo($id);} ?>">
			<input type="hidden" name="user_id" value="<?php esc_attr_e($user_id); ?>">


			<div class="wlsm-form-group">
				<label for="wlsm_submission_subject" class="wlsm-form-label wlsm-font-medium">
					<?php esc_html_e('Submission Subject', 'school-management'); ?>:
				</label>
				<select name="submission_id" class="wlsm-form-control-select wlsm-w-100" data-live-search="true" title="<?php esc_attr_e('Select', 'school-management'); ?>">
					<option value=""><?php esc_html_e('Select Subject', 'school-management'); ?></option>
					<?php foreach ($homeworks as $key => $homework) { ?>
						<option value="<?php echo $homework->ID; ?>" <?php if ($homework->ID) { echo "selected";} ?>><?php echo $homework->title; ?></option>
					<?php } ?>
				</select>
			</div>

			<div class="wlsm-form-group">
				<label for="wlsm_homework_file" class="wlsm-form-label wlsm-font-medium">
					<?php esc_html_e('Upload File', 'school-management'); ?>:
					<small class="wlsm-font-small" style="color: #6c757d;">(<?php esc_html_e('Doc, Docx and PDF files only', 'school-management'); ?>)</small>
				</label>

				<?php foreach ($homeworks as $key => $homework) { ?>
					<?php if ( ! empty ( $homework->attachments ) ) { ?>
						<div class="wlsm-form-group" style="margin-top: 0.5rem;">
							<label class="wlsm-font-small-medium" style="color: #28a745;">
								<?php esc_html_e('Current File:', 'school-management'); ?>
							</label>
							<div style="border: 1px solid #e1e5e9; border-radius: 4px; padding: 1rem; background-color: #f8f9fa;">
								<iframe src="<?php echo esc_url( wp_get_attachment_url( $homework->attachments ) ); ?>" style="width: 100%; height: 300px; border: none; border-radius: 4px;"></iframe>
							</div>
						</div>
					<?php } ?>
				<?php } ?>

				<input type="file" name="attachments" class="wlsm-form-control-select wlsm-w-100" accept=".doc,.docx,.pdf" style="padding: 0.5rem;">
			</div>

			<div class="wlsm-form-group">
				<label for="wlsm_description" class="wlsm-form-label wlsm-font-medium">
					<span class="wlsm-text-danger">*</span> <?php esc_html_e('Description Or Additional Comment', 'school-management'); ?>:
				</label>

				<?php if (isset($id)) {
					foreach ($homeworks as $key => $homework) { ?>
						<input type="hidden" name="homework_update" value="<?php echo($homework->ID) ?>">
						<textarea required name="description" class="wlsm-form-control-select wlsm-w-100" id="wlsm_description" placeholder="<?php esc_attr_e('Enter your description here...', 'school-management'); ?>" rows="5" style="min-height: 120px; resize: vertical;"><?php if ( $homework->description && isset($id)) { echo esc_textarea($homework->description); } ?></textarea>
					<?php }
				} else { ?>
					<textarea required name="description" class="wlsm-form-control-select wlsm-w-100" id="wlsm_description" placeholder="<?php esc_attr_e('Enter your description here...', 'school-management'); ?>" rows="5" style="min-height: 120px; resize: vertical;"><?php if ( $homework && isset($id)) { echo esc_textarea($homework->description); } ?></textarea>
				<?php } ?>
			</div>
			<div class="wlsm-form-group" style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e1e5e9;">
				<button data-confirm="<?php esc_attr_e('Confirm! Are you sure?', 'school-management'); ?>" class="wlsm-st-submit-btn" type="submit" id="wlsm-submit-student-homework-submission-btn">
					<i class="fas fa-upload"></i>
					<?php esc_html_e('Submit Homework', 'school-management'); ?>
				</button>
			</div>
		</form>
	</div>
</div>
