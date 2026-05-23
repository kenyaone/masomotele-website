<?php
defined('ABSPATH') || die();
$settings_registration                 = WLSM_M_Setting::get_settings_registration($school_id);
$school_registration_dob               = $settings_registration['dob'];
$registration_declaration_message               = $settings_registration['registration_declaration_message'];
$school_gender                         = $settings_registration['gender'];
$school_registration_religion          = $settings_registration['religion'];
$school_registration_caste             = $settings_registration['caste'];
$school_registration_blood_group       = $settings_registration['blood_group'];
$school_registration_phone             = $settings_registration['phone'];
$school_registration_city              = $settings_registration['city'];
$school_registration_state             = $settings_registration['state'];
$school_registration_country           = $settings_registration['country'];
$school_registration_transport         = $settings_registration['transport'];
$school_registration_parent_detail     = $settings_registration['parent_detail'];
$school_registration_parent_occupation = $settings_registration['parent_occupation'];
$school_registration_parent_login      = $settings_registration['parent_login'];
$school_registration_id_number         = $settings_registration['id_number'];
$school_registration_dob_in_words      = $settings_registration['dob_in_words'];
$school_registration_birth_place 	   = $settings_registration['birth_place'];
$school_registration_mother_tongue     = $settings_registration['mother_tongue'];
$school_registration_school_details    = $settings_registration['school_details'];

?>
<div class="wlsm-student-detail-container">

	<?php require WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/partials/school_header.php'; ?>

	<div class="row">
		<div class="col mx-auto">
			<div class="wlsm-student-detail-details "><?php esc_html_e('Basic Details', 'school-management'); ?></div>
		</div>
	</div>
	<div class="row mt-3">
		<div class="col-9 mx-auto">
			<ul>
				<li class="student-detail_list">
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Student Name', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)); ?></span>
					</span>
				</li>

				<li class="student-detail_list">
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Class', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->class_label)); ?></span>
					</span>
					<span class="pl-3">
						<span class="wlsm-font-bold"><?php esc_html_e('Section', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->section_label)); ?></span>
					</span>
					<?php if ($school_registration_dob) : ?>
						<span class="pl-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Date Of Birth', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->dob)); ?></span>
						</span>
					<?php endif ?>

				</li>

				<?php if ($school_registration_dob_in_words) : ?>
					<li class="student-detail_list">
						<span>
							<span class="wlsm-font-bold"><?php esc_html_e('Date Of Birth In Words', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->dob_in_words)); ?></span>
						</span>
					</li>
				<?php endif ?>

				<li class="student-detail_list">
					<?php if ($school_registration_mother_tongue) : ?>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Mother Tongue', 'school-management'); ?>:</span>
							<span><?php echo esc_html($student->mother_tongue); ?></span>
						</span>
					<?php endif ?>
					<?php if ($school_registration_birth_place) : ?>
						<span class="pl-3">
							<span class="wlsm-font-bold"><?php esc_html_e('Birth Place', 'school-management'); ?>:</span>
							<span><?php echo esc_html($student->birth_place); ?></span>
						</span>
					<?php endif ?>
					<?php if ($school_registration_blood_group) : ?>
						<span class="pl-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Blood Group', 'school-management'); ?>:</span>
							<span><?php echo esc_html($student->blood_group); ?></span>
						</span>
					<?php endif ?>
				</li>

				<li class="student-detail_list">
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Enrollment Number', 'school-management'); ?>:</span>
						<span><?php echo esc_html($student->enrollment_number); ?></span>
					</span>
					<span class="pr-3">
						<span class="wlsm-font-bold"><?php esc_html_e('Roll Number', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)); ?></span>
					</span>
				</li>

				<?php if ($school_registration_phone) : ?>
					<li class="student-detail_list">
						<span class="wlsm-font-bold"><?php esc_html_e('Phone', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Staff_Class::get_phone_text($student->phone)); ?></span>
					</li>
				<?php endif ?>
			</ul>
		</div>

		<div class="col-3 wlsm-student-detail-left">
			<div class="wlsm-student-detail-photo-box">
				<?php if (!empty($photo_id)) { ?>
					<img src="<?php echo esc_url(wp_get_attachment_url($photo_id)); ?>" class="wlsm-student-detail-photo">
				<?php } ?>
			</div>
		</div>
	</div>

	<!-- Student Previous School Detail -->
	<?php if ($school_registration_school_details) : ?>
	<div class="row">
		<div class="col mx-auto mt-3">
			<div class="wlsm-student-detail-details "><?php esc_html_e('Previous School Detail', 'school-management'); ?></div>
		</div>
	</div>
	<div class="row mt-3">
		<div class="col mx-auto">
			<ul>
				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('School Name', 'school-management'); ?>:</span>
					<span><?php echo esc_html($student->school_name); ?></span>
				</li>
				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('School Address', 'school-management'); ?>:</span>
					<span><?php echo esc_html($student->school_address); ?></span>
				</li>
				<li class="student-detail_list">
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Class at School', 'school-management'); ?>:</span>
						<span><?php echo esc_html($student->school_class); ?></span>
					</span>
					<span class="pl-3">
						<span class="wlsm-font-bold"><?php esc_html_e('Passout Year', 'school-management'); ?>:</span>
						<span><?php echo esc_html($student->pass_out_year); ?></span>
					</span>
				</li>
			</ul>
		</div>
	</div>
	<?php endif ?>

	<!-- Student Admission Detail -->
	<div class="row">
		<div class="col mx-auto mt-3">
			<div class="wlsm-student-detail-details "><?php esc_html_e('Admission Detail', 'school-management'); ?></div>
		</div>
	</div>
	<div class="row mt-3">
		<div class="col mx-auto">
			<ul>
				<li class="student-detail_list">
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Admission Number', 'school-management'); ?>:</span>
						<span><?php echo esc_html($student->admission_number); ?></span>
					</span>
					<span class="pr-3">
						<span class="wlsm-font-bold"><?php esc_html_e('Admission Date', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_Config::get_date_text($student->admission_date)); ?></span>
					</span>
					<span class="pl-3">
						<span class="wlsm-font-bold"><?php esc_html_e('Email', 'school-management'); ?>:</span>
						<span><?php echo esc_html($student->email); ?></span>
					</span>
				</li>

				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('Address', 'school-management'); ?>:</span>
					<span><?php echo esc_html($student->address); ?></span>
				</li>

				<li class="student-detail_list">
					<?php if ($school_registration_city) : ?>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('City', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->city)); ?></span>
						</span>
					<?php endif ?>

					<?php if ($school_registration_state) : ?>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('State', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->state)); ?></span>
						</span>
					<?php endif ?>


					<?php if ($school_registration_country) : ?>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Country', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->country)); ?></span>
						</span>
					<?php endif ?>

					<?php if ($school_registration_religion) : ?>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Religion', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->religion)); ?></span>
						</span>
					<?php endif ?>

					<?php if ($school_registration_caste) : ?>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Caste', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->caste)); ?></span>
						</span>
					<?php endif ?>

				</li>
			</ul>
		</div>
	</div>

	<?php if ($school_registration_parent_detail) : ?>
		<!-- Parents Details -->
		<div class="row">
			<div class="col mx-auto mt-3">
				<div class="wlsm-student-detail-details "><?php esc_html_e('Parents Details', 'school-management'); ?></div>
			</div>
		</div>
		<div class="row mt-3">
			<div class="col mx-auto">
				<ul>
					<li class="student-detail_list">
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Father\'s Name', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->father_name)); ?></span>
						</span>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Father\'s Phone', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->father_phone)); ?></span>
						</span>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Father\'s Occupation', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->father_occupation)); ?></span>
						</span>
					</li>
					<li class="student-detail_list">
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Mother\'s Name', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->mother_name)); ?></span>
						</span>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Mother\'s Phone', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->mother_phone)); ?></span>
						</span>
						<span class="pr-5">
							<span class="wlsm-font-bold"><?php esc_html_e('Mother\'s Occupation ', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->mother_occupation)); ?></span>
						</span>
					</li>

				</ul>
			</div>
		</div>
	<?php endif ?>

	<div class="row">
		<div class="col mx-auto mt-3">
			<div class="wlsm-student-detail-details "><?php esc_html_e('Declaration', 'school-management'); ?></div>
		</div>
	</div>

	<div class="declaration mt-3">
		<span class="wlsm-font-bold">
			<h6>
				<?php echo esc_html($registration_declaration_message); ?>
			</h6>
		</span>
	</div>

	<div class="row mt-3">
		<div class="col-6 mx-auto mt-5">
			<span class="wlsm-font-bold"><?php esc_html_e('Parent Signature', 'school-management'); ?></span>
			<br><br>
			<div class="wlsm-student-detail-photo-box">
				<?php // if (!empty($parent_signature)) { ?>
					<!-- <img src="<?php // echo esc_url(wp_get_attachment_url($parent_signature)); ?>" class="wlsm-student-detail-photo"> -->
				<?php // } ?>
			</div>
		</div>
		<div class="col-6 text-right pr-5 mt-5">
			<span class="wlsm-font-bold"><?php esc_html_e('Authorized By', 'school-management'); ?></span>
			<br><br>
			<div class="wlsm-student-detail-photo-box">
				<?php // if (!empty($school_signature)) { ?>
					<!-- <img src="<?php // echo esc_url(wp_get_attachment_url($school_signature)); ?>" class="wlsm-student-detail-photo"> -->
				<?php // } ?>
			</div>
		</div>
	</div>
</div>
