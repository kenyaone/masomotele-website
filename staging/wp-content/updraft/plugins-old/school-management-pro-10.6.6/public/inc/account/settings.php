<?php
defined( 'ABSPATH' ) || die();

$user             = wp_get_current_user();
$account_email    = $user->user_email;
$account_username = $user->user_login;

// Get comprehensive student data if logged in as student
$student_details = null;
if (isset($student) && $student) {
	global $wpdb;
	// Get comprehensive student data including all personal and parent details
	$student_details = $wpdb->get_row($wpdb->prepare(
		'SELECT sr.ID, sr.name as student_name, sr.email, sr.phone, sr.address, sr.city, sr.state, sr.country,
		sr.father_name, sr.father_phone, sr.father_occupation, sr.father_id_number,
		sr.mother_name, sr.mother_phone, sr.mother_occupation, sr.mother_id_number,
		sr.admission_number, sr.enrollment_number, sr.gender, sr.dob, sr.religion, sr.caste, sr.blood_group
		FROM ' . WLSM_STUDENT_RECORDS . ' as sr
		WHERE sr.ID = %d AND sr.is_active = 1',
		$student->ID
	));
}

$nonce_action = 'save-account-settings';
?>
<div class="wlsm-content-area wlsm-section-settings">
	<div class="wlsm-registration-section">
		<div class="wlsm-registration-section-header">
			<h3 class="wlsm-registration-section-title">
				<?php esc_html_e( 'Account Settings', 'school-management' ); ?>
			</h3>
		</div>

		<div class="wlsm-registration-section-content">
			<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" id="wlsm-save-settings-form">

			<?php $nonce = wp_create_nonce( $nonce_action ); ?>
			<input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">

			<input type="hidden" name="action" value="wlsm-p-save-account-settings">

			<?php if ($student_details): ?>
			<!-- Student Profile Section -->
			<div class="wlsm-form-group wlsm-settings-section">
				<h5 class="wlsm-font-medium wlsm-settings-section-title">
					<?php esc_html_e('Student Profile Information', 'school-management'); ?>
				</h5>

				<!-- Student Basic Information -->
				<div class="wlsm-form-group">
					<label for="wlsm_student_name" class="wlsm-form-label wlsm-font-medium">
						<?php esc_html_e( 'Student Name', 'school-management' ); ?>:
					</label>
					<input type="text" name="student_name" class="wlsm-form-control-select wlsm-w-100" id="wlsm_student_name"
						value="<?php echo esc_attr($student_details->student_name ?? ''); ?>">
				</div>

				<div class="wlsm-form-group">
					<label for="wlsm_student_phone" class="wlsm-form-label wlsm-font-medium">
						<?php esc_html_e( 'Phone Number', 'school-management' ); ?>:
					</label>
					<input type="tel" name="student_phone" class="wlsm-form-control-select wlsm-w-100" id="wlsm_student_phone"
						value="<?php echo esc_attr($student_details->phone ?? ''); ?>">
				</div>

				<div class="wlsm-form-group">
					<label for="wlsm_student_email" class="wlsm-form-label wlsm-font-medium">
						<?php esc_html_e( 'Student Email', 'school-management' ); ?>:
					</label>
					<input type="email" name="student_email" class="wlsm-form-control-select wlsm-w-100" id="wlsm_student_email"
						value="<?php echo esc_attr($student_details->email ?? ''); ?>">
				</div>

				<!-- Address Information -->
				<div class="wlsm-form-group">
					<label for="wlsm_student_address" class="wlsm-form-label wlsm-font-medium">
						<?php esc_html_e( 'Address', 'school-management' ); ?>:
					</label>
					<textarea name="student_address" class="wlsm-form-control-select wlsm-w-100 wlsm-settings-textarea" id="wlsm_student_address" rows="3"><?php echo esc_textarea($student_details->address ?? ''); ?></textarea>
				</div>

				<div class="wlsm-settings-grid-3">
					<div class="wlsm-form-group">
						<label for="wlsm_student_city" class="wlsm-form-label wlsm-font-medium">
							<?php esc_html_e( 'City', 'school-management' ); ?>:
						</label>
						<input type="text" name="student_city" class="wlsm-form-control-select wlsm-w-100" id="wlsm_student_city"
							value="<?php echo esc_attr($student_details->city ?? ''); ?>">
					</div>

					<div class="wlsm-form-group">
						<label for="wlsm_student_state" class="wlsm-form-label wlsm-font-medium">
							<?php esc_html_e( 'State', 'school-management' ); ?>:
						</label>
						<input type="text" name="student_state" class="wlsm-form-control-select wlsm-w-100" id="wlsm_student_state"
							value="<?php echo esc_attr($student_details->state ?? ''); ?>">
					</div>

					<div class="wlsm-form-group">
						<label for="wlsm_student_country" class="wlsm-form-label wlsm-font-medium">
							<?php esc_html_e( 'Country', 'school-management' ); ?>:
						</label>
						<input type="text" name="student_country" class="wlsm-form-control-select wlsm-w-100" id="wlsm_student_country"
							value="<?php echo esc_attr($student_details->country ?? ''); ?>">
					</div>
				</div>

				<!-- Father Details -->
				<h6 class="wlsm-font-medium wlsm-settings-subsection-title">
					<?php esc_html_e('Father Details', 'school-management'); ?>
				</h6>

				<div class="wlsm-settings-grid-2">
					<div class="wlsm-form-group">
						<label for="wlsm_father_name" class="wlsm-form-label wlsm-font-medium">
							<?php esc_html_e( 'Father\'s Name', 'school-management' ); ?>:
						</label>
						<input type="text" name="father_name" class="wlsm-form-control-select wlsm-w-100" id="wlsm_father_name"
							value="<?php echo esc_attr($student_details->father_name ?? ''); ?>">
					</div>

					<div class="wlsm-form-group">
						<label for="wlsm_father_phone" class="wlsm-form-label wlsm-font-medium">
							<?php esc_html_e( 'Father\'s Phone', 'school-management' ); ?>:
						</label>
						<input type="tel" name="father_phone" class="wlsm-form-control-select wlsm-w-100" id="wlsm_father_phone"
							value="<?php echo esc_attr($student_details->father_phone ?? ''); ?>">
					</div>
				</div>

				<div class="wlsm-form-group">
					<label for="wlsm_father_occupation" class="wlsm-form-label wlsm-font-medium">
						<?php esc_html_e( 'Father\'s Occupation', 'school-management' ); ?>:
					</label>
					<input type="text" name="father_occupation" class="wlsm-form-control-select wlsm-w-100" id="wlsm_father_occupation"
						value="<?php echo esc_attr($student_details->father_occupation ?? ''); ?>">
				</div>

				<!-- Mother Details -->
				<h6 class="wlsm-font-medium wlsm-settings-subsection-title">
					<?php esc_html_e('Mother Details', 'school-management'); ?>
				</h6>

				<div class="wlsm-settings-grid-2">
					<div class="wlsm-form-group">
						<label for="wlsm_mother_name" class="wlsm-form-label wlsm-font-medium">
							<?php esc_html_e( 'Mother\'s Name', 'school-management' ); ?>:
						</label>
						<input type="text" name="mother_name" class="wlsm-form-control-select wlsm-w-100" id="wlsm_mother_name"
							value="<?php echo esc_attr($student_details->mother_name ?? ''); ?>">
					</div>

					<div class="wlsm-form-group">
						<label for="wlsm_mother_phone" class="wlsm-form-label wlsm-font-medium">
							<?php esc_html_e( 'Mother\'s Phone', 'school-management' ); ?>:
						</label>
						<input type="tel" name="mother_phone" class="wlsm-form-control-select wlsm-w-100" id="wlsm_mother_phone"
							value="<?php echo esc_attr($student_details->mother_phone ?? ''); ?>">
					</div>
				</div>

				<div class="wlsm-form-group">
					<label for="wlsm_mother_occupation" class="wlsm-form-label wlsm-font-medium">
						<?php esc_html_e( 'Mother\'s Occupation', 'school-management' ); ?>:
					</label>
					<input type="text" name="mother_occupation" class="wlsm-form-control-select wlsm-w-100" id="wlsm_mother_occupation"
						value="<?php echo esc_attr($student_details->mother_occupation ?? ''); ?>">
				</div>
			</div>
			<?php endif; ?>

			<div class="wlsm-form-group wlsm-settings-section wlsm-settings-section-white">
				<h5 class="wlsm-font-medium wlsm-settings-section-title">
					<?php esc_html_e( 'Account Information', 'school-management' ); ?>
				</h5>

				<div class="wlsm-form-group">
					<label class="wlsm-form-label wlsm-font-medium wlsm-text-secondary">
						<?php esc_html_e( 'Username', 'school-management' ); ?>:
					</label>
					<div class="wlsm-settings-username-display">
						<?php echo esc_html( $account_username ); ?>
					</div>
				</div>

				<div class="wlsm-form-group">
					<label for="wlsm_account_email" class="wlsm-form-label wlsm-font-medium">
						<span class="wlsm-text-danger">*</span> <?php esc_html_e( 'Email Address', 'school-management' ); ?>:
					</label>
					<input type="email" name="email" class="wlsm-form-control-select wlsm-w-100" id="wlsm_account_email" value="<?php echo esc_attr( $account_email ); ?>" required>
				</div>

				<div class="wlsm-settings-grid-2">
					<div class="wlsm-form-group">
						<label for="wlsm_account_password" class="wlsm-form-label wlsm-font-medium">
							<span class="wlsm-text-danger">*</span> <?php esc_html_e( 'Password', 'school-management' ); ?>:
						</label>
						<input type="password" name="password" class="wlsm-form-control-select wlsm-w-100" id="wlsm_account_password" placeholder="<?php esc_attr_e('Enter new password', 'school-management'); ?>">
					</div>

					<div class="wlsm-form-group">
						<label for="wlsm_account_password_confirm" class="wlsm-form-label wlsm-font-medium">
							<span class="wlsm-text-danger">*</span> <?php esc_html_e( 'Confirm Password', 'school-management' ); ?>:
						</label>
						<input type="password" name="password_confirm" class="wlsm-form-control-select wlsm-w-100" id="wlsm_account_password_confirm" placeholder="<?php esc_attr_e('Confirm new password', 'school-management'); ?>">
					</div>
				</div>
			</div>

			<div class="wlsm-form-group wlsm-settings-submit-section">
				<button class="wlsm-st-submit-btn" type="submit" id="wlsm-save-settings-btn">
					<?php esc_html_e( 'Save Settings', 'school-management' ); ?>
				</button>
			</div>

		</form>
	</div>
</div>
