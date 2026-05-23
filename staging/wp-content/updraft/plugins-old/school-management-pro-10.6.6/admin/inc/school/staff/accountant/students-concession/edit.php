<?php
defined( 'ABSPATH' ) || die();

$page_url = WLSM_M_Staff_Accountant::get_students_concession_page_url();

$school_id  = $current_school['id'];
$session_id = $current_session['ID'];

$student_concession = NULL;

$nonce_action = 'edit-student-concession';

$id = 0;
if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
}

if ( $id ) {
	$student_concession = WLSM_M_Staff_Accountant::get_student_concession( $school_id, $session_id, $id );

	if ( $student_concession ) {
		$nonce_action = 'edit-student-concession-' . $student_concession->ID;
	}
}

if ( ! $student_concession ) {
	die();
}

// Initialize concession fields from existing data
$concession_id = $student_concession->concession_type_id;
$concession_status = $student_concession->status;
$concession_valid_from = $student_concession->valid_from ?? '';
$concession_valid_until = $student_concession->valid_until ?? '';
$concession_remarks = $student_concession->remarks ?? '';
$concession_approved_by = $student_concession->approved_by ?? '';
$concession_approval_date = $student_concession->approval_date ?? '';
$concession_rejection_reason = $student_concession->rejection_reason ?? '';
$concession_amount = $student_concession->amount ?? '';

// Get student details
$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_concession->student_record_id );

// Get concession types for the student's class
$concession_types = WLSM_M_Staff_Accountant::get_concessions_by_class( $school_id, $student->class_id );

// Get session data for date defaults
$session_data = WLSM_M_Session::get_session_dates( $session_id );
$session_start_date = $session_data ? $session_data->start_date : '';
$session_end_date = $session_data ? $session_data->end_date : '';
?>

<div class="row">
	<div class="col-md-12">
		<div class="mt-3 text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading-box">
				<span class="wlsm-section-heading">
					<i class="fas fa-edit"></i>
					<?php esc_html_e( 'Edit Student Concession', 'school-management' ); ?>
				</span>
			</span>
			<span class="float-md-right">
				<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-arrow-left"></i>&nbsp;
					<?php echo esc_html__( 'Back to List', 'school-management' ); ?>
				</a>
			</span>
		</div>

		<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-edit-student-concession-form" data-return-url="<?php echo esc_url( $page_url ); ?>">
			<?php wp_nonce_field( $nonce_action, 'edit-student-concession' ); ?>
			<input type="hidden" name="action" value="wlsm-edit-student-concession">
			<input type="hidden" name="concession_record_id" value="<?php echo esc_attr( $student_concession->ID ); ?>">

			<!-- Student Information -->
			<div class="wlsm-form-section">
				<div class="row">
					<div class="col-md-12">
						<div class="wlsm-form-sub-heading wlsm-font-bold">
							<?php esc_html_e( 'Student Information', 'school-management' ); ?>
						</div>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-4">
						<label class="wlsm-font-bold"><?php esc_html_e( 'Student Name', 'school-management' ); ?>:</label>
						<p class="form-control-plaintext"><?php echo esc_html( $student->student_name ); ?></p>
					</div>
					<div class="form-group col-md-4">
						<label class="wlsm-font-bold"><?php esc_html_e( 'Admission Number', 'school-management' ); ?>:</label>
						<p class="form-control-plaintext"><?php echo esc_html( $student->admission_number ); ?></p>
					</div>
					<div class="form-group col-md-4">
						<label class="wlsm-font-bold"><?php esc_html_e( 'Class', 'school-management' ); ?>:</label>
						<p class="form-control-plaintext"><?php echo esc_html( $student->class_label . ' - ' . $student->section_label ); ?></p>
					</div>
				</div>
			</div>

			<!-- Concession Details -->
			<div class="wlsm-form-section">
				<div class="row">
					<div class="col-md-12">
						<div class="wlsm-form-sub-heading wlsm-font-bold">
							<?php esc_html_e( 'Concession Details', 'school-management' ); ?>
						</div>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="wlsm_concession_id" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e( 'Concession Type', 'school-management' ); ?>:
						</label>
						<select name="concession_id" class="form-control selectpicker" id="wlsm_concession_id" data-nonce="<?php echo esc_attr( wp_create_nonce( 'get-class-concessions' ) ); ?>" data-live-search="true">
							<option value=""><?php esc_html_e( 'Select Concession Type', 'school-management' ); ?></option>
							<?php foreach ( $concession_types as $concession_type ) { ?>
								<option value="<?php echo esc_attr( $concession_type->ID ); ?>" <?php selected( $concession_id, $concession_type->ID ); ?>>
									<?php echo esc_html( $concession_type->concession_name ); ?>
								</option>
							<?php } ?>
						</select>
					</div>

					<div class="form-group col-md-6">
						<label for="wlsm_concession_status" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e( 'Status', 'school-management' ); ?>:
						</label>
						<select name="concession_status" class="form-control selectpicker" id="wlsm_concession_status">
							<option value="pending" <?php selected( $concession_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'school-management' ); ?></option>
							<option value="approved" <?php selected( $concession_status, 'approved' ); ?>><?php esc_html_e( 'Approved', 'school-management' ); ?></option>
							<option value="rejected" <?php selected( $concession_status, 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'school-management' ); ?></option>
							<option value="expired" <?php selected( $concession_status, 'expired' ); ?>><?php esc_html_e( 'Expired', 'school-management' ); ?></option>
						</select>
					</div>
				</div>

				<div class="form-row" id="approval-details-row" style="<?php echo ( $concession_status === 'approved' ) ? '' : 'display: none;'; ?>">
					<div class="form-group col-md-6">
						<label for="wlsm_concession_approval_date" class="wlsm-font-bold">
							<?php esc_html_e( 'Approval Date', 'school-management' ); ?>:
						</label>
						<input type="text" name="concession_approval_date" class="form-control" id="wlsm_concession_approval_date" value="<?php echo esc_attr(WLSM_Config::get_date_text($concession_approval_date)); ?>" >
					</div>

					<div class="form-group col-md-6">
						<label for="wlsm_concession_approved_by" class="wlsm-font-bold">
							<?php esc_html_e( 'Approved By', 'school-management' ); ?>:
						</label>
						<input type="text" name="concession_approved_by_display" class="form-control" id="wlsm_concession_approved_by_display" value="<?php echo esc_attr( $concession_approved_by ? get_userdata($concession_approved_by)->display_name : 'Current user' ); ?>" readonly>
						<input type="hidden" name="concession_approved_by" value="<?php echo esc_attr( $concession_approved_by ?: get_current_user_id() ); ?>">
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-12">
						<label for="wlsm_concession_remarks" class="wlsm-font-bold">
							<?php esc_html_e( 'Remarks', 'school-management' ); ?>:
						</label>
						<textarea name="concession_remarks" class="form-control" id="wlsm_concession_remarks" rows="3" placeholder="<?php esc_attr_e( 'Enter any remarks or notes', 'school-management' ); ?>"><?php echo esc_textarea( $concession_remarks ); ?></textarea>
					</div>
				</div>

				<div class="form-row" id="rejection-reason-row" style="<?php echo ( $concession_status === 'rejected' ) ? '' : 'display: none;'; ?>">
					<div class="form-group col-md-12">
						<label for="wlsm_concession_rejection_reason" class="wlsm-font-bold">
							<?php esc_html_e( 'Rejection Reason', 'school-management' ); ?>:
						</label>
						<textarea name="concession_rejection_reason" class="form-control" id="wlsm_concession_rejection_reason" rows="3" placeholder="<?php esc_attr_e( 'Enter reason for rejection', 'school-management' ); ?>"><?php echo esc_textarea( $concession_rejection_reason ); ?></textarea>
					</div>
				</div>
			</div>

			<div class="row mt-2">
				<div class="col-md-12 text-center">
					<button type="submit" class="btn btn-primary" id="wlsm-edit-student-concession-btn">
						<i class="fas fa-save"></i>&nbsp;
						<?php esc_html_e( 'Update Concession', 'school-management' ); ?>
					</button>
				</div>
			</div>

		</form>
	</div>
</div>
