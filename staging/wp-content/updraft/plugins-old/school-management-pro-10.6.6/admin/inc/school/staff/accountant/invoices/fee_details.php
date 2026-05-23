<?php
defined('ABSPATH') || die();

$page_url = WLSM_M_Staff_Accountant::get_invoices_page_url();
$current_user = WLSM_M_Role::can('view_invoices');

if (!$current_user) {
	die;
}

$school_id  = $current_user['school']['id'];
$session_id = $current_user['session']['ID'];

$student_id = isset($_GET['student_id']) ? absint($_GET['student_id']) : 0;
$nonce      = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';

$error      = '';
$content    = '';
$student    = null;

try {
	if (!$student_id) {
		throw new Exception(esc_html__('Student not defined.', 'school-management'));
	}

	if (!wp_verify_nonce($nonce, 'view-invoice-fee-details')) {
		throw new Exception(esc_html__('Invalid request.', 'school-management'));
	}

	$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id);

	if (!$student) {
		throw new Exception(esc_html__('Student not found.', 'school-management'));
	}

	$content = WLSM_Staff_Accountant::get_student_fee_details_html($school_id, $session_id, $student_id, false, $student);
} catch (Exception $exception) {
	$error = $exception->getMessage();
}
?>

<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-file-invoice"></i>
				<?php esc_html_e( 'Student Fee Details', 'school-management' ); ?>
			</span>
			<span class="float-md-right">
				<a href="<?php echo esc_url($page_url); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-arrow-left"></i>
					<?php esc_html_e('Back to Invoices', 'school-management'); ?>
				</a>
			</span>
		</div>

	</div>
</div>

<div class="wlsm-staff-general wlsm-student-fee-details-page">
	<div class="wlsm-fee-details-wrapper">
		<?php if ($error) { ?>
			<div class="alert alert-danger">
				<?php echo esc_html($error); ?>
			</div>
		<?php } else { ?>
			<?php echo wp_kses_post($content); ?>
		<?php } ?>
	</div>
</div>
