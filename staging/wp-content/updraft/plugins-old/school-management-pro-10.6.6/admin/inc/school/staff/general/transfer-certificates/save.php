<?php
defined( 'ABSPATH' ) || die();

$page_url = WLSM_M_Staff_General::get_transfer_certificate_page_url();

$school_id  = $current_school['id'];
$session_id = $current_session['ID'];

$nonce_action = 'transfer-certificate-' . $session_id;

if ( ! current_user_can( 'administrator' ) ) {
	$current_user 	= WLSM_M_Role::can('assigned_class');
	if ( $current_user ) {
		$role 			= $current_user['school']['role'];
	}
	if ( $current_user && $role !== 'admin' ) {
		$classes  = WLSM_M_Staff_Class::fetch_class_by_section_id( $school_id, absint($current_school['section_id']) );
	}else{
		$classes  = WLSM_M_Staff_Class::fetch_classes( $school_id );
	}
}else{
	$classes  = WLSM_M_Staff_Class::fetch_classes( $school_id );
}

$certificates = WLSM_M_Staff_General::get_school_certificates( $school_id );

?>
<div class="row">
	<div class="col-md-12">
		<div class="mt-2 text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-sign-in-alt"></i>
				<?php esc_html_e( 'Transfer Certificate', 'school-management' ); ?>
			</span>
			<span class="float-md-right">
				<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-users"></i>&nbsp;
					<?php echo esc_html__( 'View Transferred certificates', 'school-management' ); ?>
				</a>
			</span>
		</div>

		<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" id="wlsm-transfer-certificate-form">

			<?php $nonce = wp_create_nonce( $nonce_action ); ?>
			<input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">

			<input type="hidden" name="action" value="<?php echo esc_attr( 'wlsm-transfer-certificate' ); ?>">

			<!-- Step 1: Select Student -->
			<div class="wlsm-form-section">
				<div class="wlsm-section-heading-block mb-3">
					<span class="wlsm-section-heading">
						<?php esc_html_e( 'Step 1: Select Student', 'school-management' ); ?>
					</span>
					<small class="text-muted ml-2"><?php esc_html_e( 'Choose the class, section, and student', 'school-management' ); ?></small>
				</div>

				<div class="form-row">
					<div class="form-group col-md-4">
						<label for="wlsm_class" class="wlsm-font-bold">
							<?php esc_html_e( 'Class', 'school-management' ); ?>:
						</label>
						<select name="class_id" class="form-control selectpicker" data-nonce="<?php echo esc_attr( wp_create_nonce( 'get-class-sections' ) ); ?>" id="wlsm_class" data-live-search="true">
							<option value=""><?php esc_html_e( 'Select Class', 'school-management' ); ?></option>
							<?php foreach ( $classes as $class ) { ?>
							<option value="<?php echo esc_attr( $class->ID ); ?>">
								<?php echo esc_html( WLSM_M_Class::get_label_text( $class->label ) ); ?>
							</option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group col-md-4">
						<label for="wlsm_section" class="wlsm-font-bold">
							<?php esc_html_e( 'Section', 'school-management' ); ?>:
						</label>
						<select name="section_id" class="form-control selectpicker wlsm_section" id="wlsm_section" data-live-search="true" title="<?php esc_attr_e( 'All Sections', 'school-management' ); ?>" data-all-sections="1" data-only-active="0" data-fetch-students="1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'get-section-students' ) ); ?>">
						</select>
					</div>
					<div class="form-group col-md-4 wlsm-student-select-block">
						<label for="wlsm_student" class="wlsm-font-bold" data-single-label="<?php esc_attr_e( 'Student', 'school-management' ); ?>">
							<?php esc_html_e( 'Student', 'school-management' ); ?>:
						</label>
						<select name="student" class="form-control selectpicker" id="wlsm_student" data-live-search="true" data-none-selected-text="<?php esc_attr_e( 'Select', 'school-management' ); ?>">
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-12 text-center">
						<button type="button" class="btn btn-primary btn-lg" id="wlsm-fetch-student-details-btn" disabled>
							<i class="fas fa-search"></i>&nbsp;
							<?php esc_html_e( 'Fetch Student Details', 'school-management' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Step 2: Student Details (Initially Hidden) -->
			<div id="wlsm-student-details-section" class="wlsm-form-section" style="display: none;">
				<div class="wlsm-section-heading-block mb-3">
					<span class="wlsm-section-heading">
						<?php esc_html_e( 'Step 2: Student Information', 'school-management' ); ?>
					</span>
					<small class="text-muted ml-2"><?php esc_html_e( 'Review student details before issuing certificates', 'school-management' ); ?></small>
				</div>

				<!-- Basic Student Info -->
				<div class="mb-4">
					<h6 class="wlsm-font-bold text-secondary border-bottom pb-2 mb-3">
						<?php esc_html_e( 'Basic Information', 'school-management' ); ?>
					</h6>
					<div id="wlsm-basic-student-info">
						<!-- Student basic info will be loaded here -->
					</div>
				</div>

				<!-- Accounting Details -->
				<div class="mb-4">
					<h6 class="wlsm-font-bold text-secondary border-bottom pb-2 mb-3">
						<?php esc_html_e( 'Accounting Details', 'school-management' ); ?>
					</h6>
					<div id="wlsm-student-accounting-details">
						<!-- Student accounting details will be loaded here -->
					</div>
				</div>

				<!-- Exam Details -->
				<div class="mb-4">
					<h6 class="wlsm-font-bold text-secondary border-bottom pb-2 mb-3">
						<?php esc_html_e( 'Exam Details', 'school-management' ); ?>
					</h6>
					<div id="wlsm-student-exam-details">
						<!-- Student exam details will be loaded here -->
					</div>
				</div>
			</div>

			<!-- Step 3: Certificate Selection (Initially Hidden) -->
			<div id="wlsm-certificate-selection-section" class="wlsm-form-section" style="display: none;">
				<div class="wlsm-section-heading-block mb-3">
					<span class="wlsm-section-heading">
						<?php esc_html_e( 'Step 3: Issue Transfer Certificates', 'school-management' ); ?>
					</span>
					<small class="text-muted ml-2"><?php esc_html_e( 'Select certificates and set student status', 'school-management' ); ?></small>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="wlsm_certificate_id" class="wlsm-font-bold">
							<?php esc_html_e( 'Certificates to Issue', 'school-management' ); ?>:
						</label>
						<select name="certificate_ids[]" class="form-control selectpicker" id="wlsm_certificate_id" data-live-search="true" multiple data-none-selected-text="<?php esc_attr_e( 'Select Certificates', 'school-management' ); ?>" data-actions-box="true" data-select-all-text="<?php esc_attr_e( 'Select All', 'school-management' ); ?>" data-deselect-all-text="<?php esc_attr_e( 'Deselect All', 'school-management' ); ?>">
							<?php foreach ( $certificates as $certificate ) { ?>
							<option value="<?php echo esc_attr( $certificate->ID ); ?>">
								<?php echo esc_html( $certificate->label ); ?>
							</option>
							<?php } ?>
						</select>
						<small class="form-text text-muted">
							<?php esc_html_e( 'You can select multiple certificates to issue at once', 'school-management' ); ?>
						</small>
					</div>

					<div class="form-group col-md-6">
						<label class="wlsm-font-bold">
							<?php esc_html_e('Student Status After Transfer', 'school-management'); ?>:
						</label>
						<div class="mt-2">
							<div class="form-check form-check-inline">
								<input type="radio" name="student_status" id="wlsm_status_active" value="1" class="form-check-input" checked>
								<label class="form-check-label text-success font-weight-bold" for="wlsm_status_active">
									<?php esc_html_e('Keep Active', 'school-management'); ?>
								</label>

							</div>
							<div class="form-check  mt-2 form-check-inline">
								<input type="radio" name="student_status" id="wlsm_status_inactive" value="0" class="form-check-input">
								<label class="form-check-label text-warning font-weight-bold" for="wlsm_status_inactive">
									<?php esc_html_e('Make Inactive', 'school-management'); ?>
								</label>

							</div>
						</div>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-12">
						<label for="wlsm_remarks" class="wlsm-font-bold">
							<?php esc_html_e('Remarks (Optional)', 'school-management'); ?>:
						</label>
						<textarea name="remarks" id="wlsm_remarks" class="form-control" rows="3" placeholder="<?php esc_attr_e('Enter any additional remarks or notes about the transfer...', 'school-management'); ?>"></textarea>
					</div>
				</div>
			</div>

			<!-- Step 4: Issue Certificates -->
			<div id="wlsm-issue-certificates-section" class="row mt-4" style="display: none;">
				<div class="col-md-12 text-center">
					<button type="button" class="btn btn-success btn-lg" id="wlsm-transfer-certificate-btn" data-message-title="<?php esc_attr_e( 'Confirm Transfer Certificate Issue!', 'school-management' ); ?>" data-message-content="<?php esc_attr_e( 'Are you sure you want to issue the selected transfer certificates to this student?', 'school-management' ); ?>" data-submit="<?php esc_attr_e( 'Issue Certificates', 'school-management' ); ?>" data-cancel="<?php esc_attr_e( 'Cancel', 'school-management' ); ?>">
						<?php esc_html_e( 'Issue Transfer Certificates', 'school-management' ); ?>
					</button>
				</div>
			</div>

		</form>
	</div>
</div>

<script type="text/javascript">
// Transfer certificates configuration
window.wlsm_transfer_certificates_config = {
    ajaxurl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
    nonce: '<?php echo esc_attr(wp_create_nonce('fetch-student-transfer-details')); ?>',
    session_id: '<?php echo esc_attr($session_id); ?>',
    transfer_nonce: '<?php echo esc_attr(wp_create_nonce('transfer-certificate-' . $session_id)); ?>',
    messages: {
        select_student: '<?php esc_html_e('Please select a student first.', 'school-management'); ?>',
        loading: '<?php esc_html_e('Loading...', 'school-management'); ?>',
        success: '<?php esc_html_e('Student details loaded successfully.', 'school-management'); ?>',
        error: '<?php esc_html_e('Failed to load student details.', 'school-management'); ?>',
        ajax_error: '<?php esc_html_e('An error occurred while fetching student details.', 'school-management'); ?>',
        fetch_details: '<?php esc_html_e('Fetch Student Details', 'school-management'); ?>'
    }
};
</script>
