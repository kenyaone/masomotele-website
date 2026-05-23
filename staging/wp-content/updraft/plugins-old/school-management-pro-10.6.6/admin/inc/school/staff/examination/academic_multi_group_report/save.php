<?php
defined('ABSPATH') || die();

global $wpdb;

$page_url = WLSM_M_Staff_Examination::get_academic_multi_group_report_page_url();

$school_id = $current_school['id'];
$session_id = $current_session['ID'];

$multi_group_report = null;
$id = 0;

$nonce_action = 'add-academic-multi-group-report';

$exam_title   = '';
$class_id    = '';
$selected_session_id = $session_id;
$selected_groups = array();
$exam_groups = WLSM_M_Staff_Examination::fetch_exams_groups($school_id);
$sessions = WLSM_M_Session::fetch_sessions($school_id);

if (isset($_GET['id']) && ! empty($_GET['id'])) {
	$id   = absint($_GET['id']);
	$multi_group_report = WLSM_M_Staff_Examination::get_academic_multi_group_report($school_id, $id);

	if ($multi_group_report) {
		$nonce_action = 'edit-academic-multi-group-report' . $multi_group_report->ID;
		$exam_title   = $multi_group_report->label;
		$class_id     = $multi_group_report->class_id;
		$selected_session_id = $multi_group_report->session_id;
		$selected_groups = json_decode($multi_group_report->exam_groups, true);
		$selected_groups = (is_array($selected_groups)) ? array_map('absint', $selected_groups) : array();
	}
}

if (! current_user_can('administrator')) {
	$current_user 	= WLSM_M_Role::can('assigned_class');
	if ($current_user) {
		$role 			= $current_user['school']['role'];
	}
	if ($current_user && $role !== 'admin') {
		$classes  = WLSM_M_Staff_Class::fetch_class_by_section_id($school_id, absint($current_school['section_id']));
	} else {
		$classes  = WLSM_M_Staff_Class::fetch_classes($school_id);
	}
} else {
	$classes  = WLSM_M_Staff_Class::fetch_classes($school_id);
}

// $classes = WLSM_M_Staff_Class::fetch_classes( $school_id );
?>
<div class="row">
	<div class="col-md-12">
		<div class="mt-3 text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading-box">
				<span class="wlsm-section-heading">
					<?php
					if ($multi_group_report) {
						printf(
							wp_kses(
								/* translators: 1: exam title, 2: start date, 3: end date */
								__('Edit Report: %1$s ', 'school-management'),
								array(
									'span' => array('class' => array()),
								)
							),
							esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
							esc_html(''),
							esc_html('')
						);
					} else {
						esc_html_e('Add New Academic Multi Group Report', 'school-management');
					}
					?>
				</span>
			</span>
			<span class="float-md-right">
				<a href="<?php echo esc_url($page_url); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-clock"></i>&nbsp;
					<?php esc_html_e('View All', 'school-management'); ?>
				</a>
			</span>
		</div>
		<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-save-report-form">

			<?php $nonce = wp_create_nonce($nonce_action); ?>
			<input type="hidden" name="<?php echo esc_attr($nonce_action); ?>" value="<?php echo esc_attr($nonce); ?>">

			<input type="hidden" name="action" value="wlsm-save-academic-multi-group-report">
			<input type="hidden" name="report_id" value="<?php echo esc_attr($id) ?>">

			<!-- Report Detail -->
			<div class="wlsm-form-section">
				<div class="row">
					<div class="col-md-12">
						<div class="wlsm-form-sub-heading wlsm-font-bold">
							<?php esc_html_e('Report Detail', 'school-management'); ?>
						</div>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="wlsm_report_title" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Report Title', 'school-management'); ?>:
						</label>
						<input type="text" name="label" class="form-control" id="wlsm_report_title" placeholder="<?php esc_attr_e('Enter report title', 'school-management'); ?>" value="<?php echo esc_attr(stripslashes($exam_title)); ?>">
					</div>
					<div class="form-group col-md-6">
						<label for="wlsm_session_report" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Session', 'school-management'); ?>:
						</label>
						<select name="session_id" class="form-control selectpicker" id="wlsm_session_report" data-actions-box="true" data-none-selected-text="<?php esc_attr_e('Select', 'school-management'); ?>">
							<option value=""><?php esc_attr_e('Select Session', 'school-management'); ?></option>
							<?php foreach ($sessions as $session) {
								$session_value = absint($session->ID);
							?>
								<option <?php selected($session_value, $selected_session_id); ?> value="<?php echo esc_attr($session_value); ?>">
									<?php echo esc_html(WLSM_M_Session::get_label_text($session->label)); ?>
								</option>
							<?php } ?>
						</select>
						<p><?php esc_html_e('Select the academic session for this report.', 'school-management'); ?></p>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="wlsm_classes" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Class', 'school-management'); ?>:
						</label>
						<select name="class_id" class="form-control selectpicker" id="wlsm_class_report" data-actions-box="true" data-none-selected-text="<?php esc_attr_e('Select', 'school-management'); ?>">
							<option value=""><?php esc_attr_e('Select Class', 'school-management'); ?></option>
							<?php foreach ($classes as $class) {
								$class_value = absint($class->ID);
							?>
								<option <?php selected($class_value, $class_id); ?> value="<?php echo esc_attr($class_value); ?>">
									<?php echo esc_html(WLSM_M_Class::get_label_text($class->label)); ?>
								</option>
							<?php } ?>
						</select>
						<p><?php esc_html_e('This report is generated for a single class.', 'school-management'); ?></p>
					</div>
					<div class="form-group col-md-6">
						<label for="wlsm_exam_groups" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Exam Groups', 'school-management'); ?>:
						</label>
						<select name="exam_groups[]" class="form-control selectpicker" id="wlsm_exam_groups" data-actions-box="true" data-none-selected-text="<?php esc_attr_e('Select', 'school-management'); ?>" multiple>
							<?php foreach ($exam_groups as $group) {
								$group_value   = absint($group->ID);
								$is_group_selected = in_array($group_value, $selected_groups, true);
							?>
								<option <?php selected($is_group_selected); ?> value="<?php echo esc_attr($group_value); ?>">
									<?php echo esc_html(WLSM_M_Class::get_label_text($group->label)); ?>
								</option>
							<?php } ?>
						</select>
						<p><?php esc_html_e('Select one or more groups. All exams belonging to the selected groups for this class will be included automatically.', 'school-management'); ?></p>
					</div>
				</div>

			</div>

			<div class="row mt-2">
				<div class="col-md-12 text-center">
					<button type="submit" class="btn btn-primary" id="wlsm-save-report-btn">
						<?php
						if ($multi_group_report) {
						?>
							<i class="fas fa-save"></i>&nbsp;
						<?php
							esc_html_e('Update Multi Group Report', 'school-management');
						} else {
						?>
							<i class="fas fa-plus-square"></i>&nbsp;
						<?php
							esc_html_e('Add New Multi Group Report', 'school-management');
						}
						?>
					</button>
				</div>
			</div>

		</form>
	</div>
</div>
