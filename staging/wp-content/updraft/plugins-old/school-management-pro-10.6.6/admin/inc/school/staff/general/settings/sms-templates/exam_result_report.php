<?php
defined( 'ABSPATH' ) || die();

// SMS Exam Result Report settings.
$settings_sms_exam_result_report = WLSM_M_Setting::get_settings_sms_exam_result_report( $school_id );
$sms_exam_result_report_enable   = $settings_sms_exam_result_report['enable'];
$sms_exam_result_report_message  = $settings_sms_exam_result_report['message'];
$sms_exam_template_id_result_message  = $settings_sms_exam_result_report['template_id'];

$sms_exam_result_report_placeholders = WLSM_SMS::exam_result_report_placeholders();
?>
<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_sms_exam_result_report_fields" aria-expanded="true" aria-controls="wlsm_sms_exam_result_report_fields">
	<?php esc_html_e( 'Exam Result Report Notification', 'school-management' ); ?>
</button>

<div class="collapse border border-top-0 border-primary p-3" id="wlsm_sms_exam_result_report_fields">

	<div class="wlsm_sms_template wlsm_sms_exam_result_report">
		<div class="row">
			<div class="col-md-3">
				<label for="wlsm_sms_exam_result_report_enable" class="wlsm-font-bold">
					<?php esc_html_e( 'Exam Result Report SMS', 'school-management' ); ?>:
				</label>
			</div>
			<div class="col-md-9">
				<div class="form-group">
					<label for="wlsm_sms_exam_result_report_enable" class="wlsm-font-bold">
						<input <?php checked( $sms_exam_result_report_enable, true, true ); ?> type="checkbox" name="sms_exam_result_report_enable" id="wlsm_sms_exam_result_report_enable" value="1">
						<?php esc_html_e( 'Enable', 'school-management' ); ?>
					</label>
				</div>
			</div>
		</div>
	</div>

	<div class="wlsm_sms_template wlsm_sms_exam_result_report mb-3">
		<div class="row">
			<div class="col-md-12">
				<span class="wlsm-font-bold text-dark"><?php esc_html_e( 'You can use the following variables:', 'school-management' ); ?></span>
				<div class="row">
					<?php foreach ( $sms_exam_result_report_placeholders as $key => $value ) { ?>
					<div class="col-sm-6 col-md-3 pb-1 pt-1 border">
						<span class="wlsm-font-bold text-secondary"><?php echo esc_html( $value ); ?></span>
						<br>
						<span><?php echo esc_html( $key ); ?></span>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>

	<div class="wlsm_sms_template wlsm_sms_exam_result_report">
		<div class="row">
			<div class="col-md-3">
				<label for="wlsm_sms_exam_result_report_message" class="wlsm-font-bold"><?php esc_html_e( 'SMS Message', 'school-management' ); ?>:</label>
			</div>
			<div class="col-md-9">
				<div class="form-group">
					<textarea name="sms_exam_result_report_message" id="wlsm_sms_exam_result_report_message" class="form-control" rows="6" placeholder="<?php esc_attr_e( 'SMS Message', 'school-management' ); ?>"><?php echo esc_html( $sms_exam_result_report_message ); ?></textarea>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3">
				<label for="wlsm_sms_exam_template_id_result_message" class="wlsm-font-bold"><?php esc_html_e( 'Template ID', 'school-management' ); ?>:</label>
			</div>
			<div class="col-md-9">
				<div class="form-group">
					<textarea name="sms_exam_template_id_result_message" id="wlsm_sms_exam_template_id_result_message" class="form-control" rows="1" placeholder="<?php esc_attr_e( 'SMS Template ID', 'school-management' ); ?>"><?php echo esc_html( $sms_exam_template_id_result_message ); ?></textarea>
				</div>
			</div>
		</div>
	</div>

	<?php
	$sms_template = 'exam_result_report';
	require WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/general/settings/sms-templates/test_sms.php';
	?>

</div>