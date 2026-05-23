<?php
defined('ABSPATH') || die();

// Registration settings.
$settings_registration                     = WLSM_M_Setting::get_settings_registration($school_id);
$school_registration_form_title            = $settings_registration['form_title'];
$school_registration_declaration_message            = $settings_registration['registration_declaration_message'];
$school_registration_login_user            = $settings_registration['login_user'];
$school_registration_redirect_url          = $settings_registration['redirect_url'];
$school_registration_create_invoice        = $settings_registration['create_invoice'];
$school_registration_auto_admission_number = $settings_registration['auto_admission_number'];
$school_registration_auto_roll_number      = $settings_registration['auto_roll_number'];
$school_registration_admin_phone           = $settings_registration['admin_phone'];
$school_registration_admin_email           = $settings_registration['admin_email'];
$school_registration_success_message       = $settings_registration['success_message'];
$school_student_aprove                     = $settings_registration['student_aprove'];

$school_registration_dob                  = $settings_registration['dob'];
$school_gender                            = $settings_registration['gender'];
$school_registration_religion             = $settings_registration['religion'];
$school_registration_caste                = $settings_registration['caste'];
$school_registration_blood_group          = $settings_registration['blood_group'];
$school_registration_phone                = $settings_registration['phone'];
$school_registration_city                 = $settings_registration['city'];
$school_registration_state                = $settings_registration['state'];
$school_registration_country              = $settings_registration['country'];
$school_registration_transport            = $settings_registration['transport'];
$school_registration_parent_detail        = $settings_registration['parent_detail'];
$school_registration_parent_occupation    = $settings_registration['parent_occupation'];
$school_registration_student_login        = $settings_registration['student_login'];
$school_registration_parent_login         = $settings_registration['parent_login'];
$school_registration_id_number            = $settings_registration['id_number'];
$school_registration_survey               = $settings_registration['survey'];
$school_registration_fees                 = $settings_registration['fees'];
$school_registration_medium               = $settings_registration['medium'];
$school_registration_pan                  = $settings_registration['pan'];
$school_registration_dob_in_words         = $settings_registration['dob_in_words'];
$school_registration_birth_place          = $settings_registration['birth_place'];
$school_registration_mother_tongue        = $settings_registration['mother_tongue'];
$school_registration_school_details       = $settings_registration['school_details'];
$school_registration_category             = $settings_registration['category'];
$school_registration_student_type         = $settings_registration['student_type'];
$school_registration_address              = $settings_registration['address'];
$school_registration_activity             = $settings_registration['activity'];
$school_registration_student_photo        = $settings_registration['student_photo'];
$school_registration_auto_select_subjects = $settings_registration['student_auto_subjects'];

$school_registration_pen    			= $settings_registration['pen'];
$school_registration_apaar    			= $settings_registration['apaar'];
$school_registration_parent_id_number   = $settings_registration['parent_id_number'];
$school_registration_add_sibling_button = $settings_registration['add_sibling_button'];
$school_registration_gender_list        = $settings_registration['gender_list'];

$school_registration_success_placeholders = WLSM_Helper::registration_success_message_placeholders();
?>
<div class="tab-pane fade" id="wlsm-school-registration" role="tabpanel" aria-labelledby="wlsm-school-registration-tab">

	<div class="row">
		<div class="col-md-9">
			<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-save-school-registration-settings-form">
				<?php
				$nonce_action = 'save-school-registration-settings';
				$nonce        = wp_create_nonce($nonce_action);
				?>
				<input type="hidden" name="<?php echo esc_attr($nonce_action); ?>" value="<?php echo esc_attr($nonce); ?>">

				<input type="hidden" name="action" value="wlsm-save-school-registration-settings">

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_form_title" class="wlsm-font-bold"><?php esc_html_e('Registration Form Title', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input name="registration_form_title" type="text" id="wlsm_registration_form_title" value="<?php echo esc_attr($school_registration_form_title); ?>" class="form-control" placeholder="<?php esc_attr_e('Registration form title', 'school-management'); ?>">
							<p class="description">
								<?php esc_html_e('Works only when school_id is specified in the registration shortcode.', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_login_user" class="wlsm-font-bold"><?php esc_html_e('Login after Registration', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input <?php checked($school_registration_login_user, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_login_user" id="wlsm_registration_login_user" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_login_user">
								<?php esc_html_e('Login after Registration', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('This will login the student after registration.', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_redirect_url" class="wlsm-font-bold"><?php esc_html_e('Redirect URL', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input name="redirect_url" type="text" id="wlsm_redirect_url" value="<?php echo esc_attr($school_registration_redirect_url); ?>" class="form-control" placeholder="<?php esc_attr_e('Redirect URL', 'school-management'); ?>">
							<p class="description">
								<?php esc_html_e('Enter URL where to redirect the student after registration.', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_create_invoice" class="wlsm-font-bold"><?php esc_html_e('Create Invoice from Fee Type', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input <?php checked($school_registration_create_invoice, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_create_invoice" id="wlsm_registration_create_invoice" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_create_invoice">
								<?php esc_html_e('Create Invoice from Fee Type?', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('For every fee type, an invoice will be created. This is valid only for registrations from front registration form.', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_auto_admission_number" class="wlsm-font-bold"><?php esc_html_e('Auto Generate Admission Number', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input <?php checked($school_registration_auto_admission_number, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_auto_admission_number" id="wlsm_registration_auto_admission_number" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_auto_admission_number">
								<?php esc_html_e('Auto Generate Admission Number for Back-end Form?', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission number is auto-generated in front-end form. With this, you can auto-generate admission number in back-end form also.', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_auto_roll_number" class="wlsm-font-bold"><?php esc_html_e('Auto Generate Roll Number', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input <?php checked($school_registration_auto_roll_number, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_auto_roll_number" id="wlsm_registration_auto_roll_number" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_auto_roll_number">
								<?php esc_html_e('Auto Generate roll Number for Back-end Form?', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Roll number is auto-generated.', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_admin_phone" class="wlsm-font-bold"><?php esc_html_e('Admin Phone Number', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input name="registration_admin_phone" type="text" id="wlsm_registration_admin_phone" value="<?php echo esc_attr($school_registration_admin_phone); ?>" class="form-control" placeholder="<?php esc_attr_e('Admin phone number to receive registration notification', 'school-management'); ?>">
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_admin_email" class="wlsm-font-bold"><?php esc_html_e('Admin Email Address', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input name="registration_admin_email" type="email" id="wlsm_registration_admin_email" value="<?php echo esc_attr($school_registration_admin_email); ?>" class="form-control" placeholder="<?php esc_attr_e('Admin email address to receive registration notification', 'school-management'); ?>">
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_success_message" class="wlsm-font-bold"><?php esc_html_e('Success Message', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="mb-1">
							<span class="wlsm-font-bold text-dark"><?php esc_html_e('You can use the following variables:', 'school-management'); ?></span>
							<div class="d-flex">
								<?php foreach ($school_registration_success_placeholders as $key => $value) { ?>
									<div class="col-sm-6 col-md-3 pb-1 pt-1 border">
										<span class="wlsm-font-bold text-secondary"><?php echo esc_html($value); ?></span>
										<br>
										<span><?php echo esc_html($key); ?></span>
									</div>
								<?php } ?>
							</div>
						</div>

						<div class="form-group">
							<textarea name="registration_success_message" id="wlsm_registration_success_message" class="form-control" rows="6" placeholder="<?php esc_attr_e('Success Message', 'school-management'); ?>"><?php echo esc_html($school_registration_success_message); ?></textarea>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_declaration_message" class="wlsm-font-bold"><?php esc_html_e('Declaration Message', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">


						<div class="form-group">
							<textarea name="registration_declaration_message" id="wlsm_registration_declaration_message" class="form-control" rows="6" placeholder="<?php esc_attr_e('declaration Message', 'school-management'); ?>"><?php echo esc_html($school_registration_declaration_message); ?></textarea>
						</div>
					</div>
				</div>



				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_student_aprove" class="wlsm-font-bold"><?php esc_html_e('Student Approval', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input <?php checked($school_student_aprove, true, true); ?> class="form-check-input mt-1" type="checkbox" name="student_aprove" id="wlsm_student_aprove" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_student_aprove">
								<?php esc_html_e('If Checked Student Will Be Inactive After Registration From Front End.  ( It Will Require Admin or Staff approval )', 'school-management'); ?>
							</label>
						</div>
					</div>
				</div>

				<!-- Addmission form options -->
				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_dob" class="wlsm-font-bold"><?php esc_html_e('Date Of Birth', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_dob, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_dob" id="wlsm_registration_dob" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_dob">
								<?php esc_html_e('  Show Date Of Birth', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable date of birth field', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_religion" class="wlsm-font-bold"><?php esc_html_e('Religion', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_religion, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_religion" id="wlsm_registration_religion" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_religion">
								<?php esc_html_e('  Show Religion', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable Religion', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<!-- Gender Management Section -->
				<div class="row mb-4">
					<div class="col-md-12">
						<div class="">
							<!-- <div class="card-header bg-primary text-white">
								<h5 class="mb-0"><i class="fas fa-venus-mars mr-2"></i><?php esc_html_e('Gender Options Management', 'school-management'); ?></h5>
							</div> -->
							<div class="card-body">
								<p class="text-muted mb-3"><?php esc_html_e('Customize the gender options available in the registration form. Male and Female are required and cannot be removed.', 'school-management'); ?></p>

								<!-- Show Gender Field Checkbox -->
								<div class="row mb-3">
									<div class="col-md-6">
										<div class="form-group">
											<input <?php checked($school_gender, true, true); ?> class="form-check-input mt-1" type="checkbox" name="gender" id="wlsm_gender" value="1">
											<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_gender">
												<?php esc_html_e('Show Gender Field', 'school-management'); ?>
											</label>
											<p class="description ml-4">
												<?php esc_html_e('Enable/disable the gender field in registration form', 'school-management'); ?>
											</p>
										</div>
									</div>
								</div>

								<!-- Current Gender Options -->
								<div class="row">
									<div class="col-md-8">
										<label class="wlsm-font-bold"><?php esc_html_e('Current Gender Options:', 'school-management'); ?></label>
										<div id="wlsm-gender-options-list" class="border rounded p-3 mb-3" style="min-height: 100px;">
											<?php if (!empty($school_registration_gender_list) && is_array($school_registration_gender_list)): ?>
												<?php foreach ($school_registration_gender_list as $key => $label): ?>
													<div class="wlsm-gender-option-item d-flex align-items-center justify-content-between border rounded p-2 mb-2" data-key="<?php echo esc_attr($key); ?>">
														<div class="d-flex align-items-center">
															<i class="fas fa-grip-vertical text-muted mr-2 wlsm-drag-handle" style="cursor: move;"></i>
															<span class="wlsm-gender-key badge badge-secondary mr-2"><?php echo esc_html($key); ?></span>
															<span class="wlsm-gender-label"><?php echo esc_html($label); ?></span>
														</div>
														<div>
															<?php if (in_array($key, ['male', 'female'])): ?>
																<button type="button" class="btn btn-sm btn-outline-primary wlsm-edit-gender" title="<?php esc_attr_e('Edit', 'school-management'); ?>">
																	<i class="fas fa-edit"></i>
																</button>
																<span class="badge badge-warning ml-1"><?php esc_html_e('Required', 'school-management'); ?></span>
															<?php else: ?>
																<button type="button" class="btn btn-sm btn-outline-primary wlsm-edit-gender" title="<?php esc_attr_e('Edit', 'school-management'); ?>">
																	<i class="fas fa-edit"></i>
																</button>
																<button type="button" class="btn btn-sm btn-outline-danger wlsm-delete-gender ml-1" title="<?php esc_attr_e('Delete', 'school-management'); ?>">
																	<i class="fas fa-trash"></i>
																</button>
															<?php endif; ?>
														</div>
													</div>
												<?php endforeach; ?>
											<?php else: ?>
												<p class="text-muted"><?php esc_html_e('No custom gender options defined. Using default options.', 'school-management'); ?></p>
											<?php endif; ?>
										</div>
									</div>

									<!-- Add New Gender Option -->
									<div class="col-md-4">
										<label class="wlsm-font-bold"><?php esc_html_e('Add New Gender Option:', 'school-management'); ?></label>
										<div class="form-group">
											<label for="wlsm_new_gender_key" class="small"><?php esc_html_e('Key (lowercase, no spaces):', 'school-management'); ?></label>
											<input type="text" class="form-control form-control-sm" id="wlsm_new_gender_key" placeholder="<?php esc_attr_e('e.g., non_binary', 'school-management'); ?>" pattern="[a-z_]+" title="<?php esc_attr_e('Only lowercase letters and underscores allowed', 'school-management'); ?>">
										</div>
										<div class="form-group">
											<label for="wlsm_new_gender_label" class="small"><?php esc_html_e('Display Label:', 'school-management'); ?></label>
											<input type="text" class="form-control form-control-sm" id="wlsm_new_gender_label" placeholder="<?php esc_attr_e('e.g., Non-Binary', 'school-management'); ?>">
										</div>
										<button type="button" class="btn btn-primary btn-sm" id="wlsm_add_gender_option">
											<i class="fas fa-plus mr-1"></i><?php esc_html_e('Add Option', 'school-management'); ?>
										</button>
									</div>
								</div>

								<!-- Hidden field to store gender list -->
								<input type="hidden" name="gender_list" id="wlsm_gender_list_data" value="<?php echo esc_attr(json_encode($school_registration_gender_list)); ?>">

								<!-- Preview Section -->
								<div class="mt-4">
									<label class="wlsm-font-bold"><?php esc_html_e('Preview:', 'school-management'); ?></label>
									<div class="border rounded p-3 bg-light">
										<div id="wlsm-gender-preview">
											<strong><?php esc_html_e('Gender:', 'school-management'); ?></strong><br>
											<?php if (!empty($school_registration_gender_list) && is_array($school_registration_gender_list)): ?>
												<?php foreach ($school_registration_gender_list as $key => $label): ?>
													<label class="mr-3">
														<input type="radio" name="preview_gender" value="<?php echo esc_attr($key); ?>" disabled> <?php echo esc_html($label); ?>
													</label>
												<?php endforeach; ?>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<!-- <div class="col-md-3">
						<label for="wlsm_gender" class="wlsm-font-bold"><?php esc_html_e('Gender', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_gender, true, true); ?> class="form-check-input mt-1" type="checkbox" name="gender" id="wlsm_gender" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_gender">
								<?php esc_html_e('  Show Gender', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Gender form will disable date of birth field', 'school-management'); ?>
							</p>
						</div>
					</div> -->

				</div>
				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_caste" class="wlsm-font-bold"><?php esc_html_e('Caste/Sub caste', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_caste, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_caste" id="wlsm_registration_caste" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_caste">
								<?php esc_html_e('  Show caste/sub caste', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable caste/sub caste', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_blood_group" class="wlsm-font-bold"><?php esc_html_e('Blood Group', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_blood_group, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_blood_group" id="wlsm_registration_blood_group" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_blood_group">
								<?php esc_html_e('  Show blood_group', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable blood_group', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_phone" class="wlsm-font-bold"><?php esc_html_e('Phone', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_phone, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_phone" id="wlsm_registration_phone" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_phone">
								<?php esc_html_e('  Show phone', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable phone', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_city" class="wlsm-font-bold"><?php esc_html_e('City', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_city, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_city" id="wlsm_registration_city" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_city">
								<?php esc_html_e('  Show city', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable city', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_state" class="wlsm-font-bold"><?php esc_html_e('State', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_state, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_state" id="wlsm_registration_state" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_state">
								<?php esc_html_e('  Show state', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable state', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_country" class="wlsm-font-bold"><?php esc_html_e('Country', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_country, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_country" id="wlsm_registration_country" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_country">
								<?php esc_html_e('  Show Country', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable Country field', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_id_number" class="wlsm-font-bold"><?php esc_html_e('Id Number / ID Proof', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_id_number, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_id_number" id="wlsm_registration_id_number" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_id_number">
								<?php esc_html_e('  Show Id Number', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable id Number field field', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_transport" class="wlsm-font-bold"><?php esc_html_e('Transport Detail', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_transport, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_transport" id="wlsm_registration_transport" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_transport">
								<?php esc_html_e('  Show Transport Detail', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable Transport panel', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_survey" class="wlsm-font-bold"><?php esc_html_e('Survey Detail', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_survey, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_survey" id="wlsm_registration_survey" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_survey">
								<?php esc_html_e('  Show Survey Detail', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable Survey panel', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_student_login" class="wlsm-font-bold"><?php esc_html_e('Student login Panel', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_student_login, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_student_login" id="wlsm_registration_student_login" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_student_login">
								<?php esc_html_e('  Show Student Login Panel', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will not show students signup/login detail', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_parent_detail" class="wlsm-font-bold"><?php esc_html_e('Parents Detail Panel', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_parent_detail, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_parent_detail" id="wlsm_registration_parent_detail" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_parent_detail">
								<?php esc_html_e('  Show Parent Detail Panel', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Registration form will not show entire parent panel', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_parent_login" class="wlsm-font-bold"><?php esc_html_e('Parents login Panel', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_parent_login, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_parent_login" id="wlsm_registration_parent_login" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_parent_login">
								<?php esc_html_e('  Show Parents Login Panel', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will not show parents signup/login detail', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_parent_occupation" class="wlsm-font-bold"><?php esc_html_e('Occupation', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_parent_occupation, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_parent_occupation" id="wlsm_registration_parent_occupation" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_parent_occupation">
								<?php esc_html_e('  Show Occupation', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Registration form will not show entire parent occupation', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_fees" class="wlsm-font-bold"><?php esc_html_e('Fees Detail', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_fees, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_fees" id="wlsm_registration_fees" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_fees">
								<?php esc_html_e('  Show Fees Detail', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable Fees panel', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_medium" class="wlsm-font-bold"><?php esc_html_e('Medium Detail', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_medium, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_medium" id="wlsm_registration_medium" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_medium">
								<?php esc_html_e('  Show Medium', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable Fees panel', 'school-management'); ?>
							</p>
						</div>
					</div>
					<!-- <div class="col-md-3">
						<label for="wlsm_registration_pan" class="wlsm-font-bold"><?php // esc_html_e('Pan Detail', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php // checked($school_registration_pan, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_pan" id="wlsm_registration_pan" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_pan">
								<?php // esc_html_e('  Show Pan', 'school-management'); ?>
							</label>
							<p class="description">
								<?php // esc_html_e('Admission pan form will disable Show Pan', 'school-management'); ?>
							</p>
						</div>
					</div> -->
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_dob_in_words" class="wlsm-font-bold"><?php esc_html_e('Date of Birth in Words', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_dob_in_words, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_dob_in_words" id="wlsm_registration_dob_in_words" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_dob_in_words">
								<?php esc_html_e('  Show Date of Birth in Words', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable Date of Birth in Words field', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_birth_place" class="wlsm-font-bold"><?php esc_html_e('Birth Place', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_birth_place, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_birth_place" id="wlsm_registration_birth_place" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_birth_place">
								<?php esc_html_e('  Show Birth Place', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable Birth Place field', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_mother_tongue" class="wlsm-font-bold"><?php esc_html_e('Mother Tongue', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_mother_tongue, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_mother_tongue" id="wlsm_registration_mother_tongue" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_mother_tongue">
								<?php esc_html_e('  Show Mother Tongue', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable Mother Tongue field', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_school_details" class="wlsm-font-bold"><?php esc_html_e('Previous School Details', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_school_details, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_school_details" id="wlsm_registration_school_details" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_school_details">
								<?php esc_html_e('  Show school details', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable school details field', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_category" class="wlsm-font-bold"><?php esc_html_e('Category', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_category, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_category" id="wlsm_registration_category" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_category">
								<?php esc_html_e('  Show category', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable category field', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_address" class="wlsm-font-bold"><?php esc_html_e('Address', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_address, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_address" id="wlsm_registration_address" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_address">
								<?php esc_html_e('  Show address', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable address field', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_student_type" class="wlsm-font-bold"><?php esc_html_e('Student Type', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_student_type, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_student_type" id="wlsm_registration_student_type" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_student_type">
								<?php esc_html_e('  Show student type', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable student_type field', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_activity" class="wlsm-font-bold"><?php esc_html_e('Activity', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_activity, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_activity" id="wlsm_registration_activity" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_activity">
								<?php esc_html_e('  Show activity', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable activity field', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_student_photo" class="wlsm-font-bold"><?php esc_html_e('Student Photo', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_student_photo, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_student_photo" id="wlsm_registration_student_photo" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_student_photo">
								<?php esc_html_e('  Show student photo', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Admission Registration form will disable student_photo field', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_auto_select_subjects" class="wlsm-font-bold"><?php esc_html_e('Auto Select Subjects', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_auto_select_subjects, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_auto_select_subjects" id="wlsm_registration_auto_select_subjects" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_auto_select_subjects">
								<?php esc_html_e('  Auto Select Subjects', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Automatically select subjects for the student during registration.', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_pen" class="wlsm-font-bold"><?php esc_html_e('PAN/PEN Number', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_pen, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_pen" id="wlsm_registration_pen" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_pen">
								<?php esc_html_e('  Show PAN/PEN Number', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Registration form will not show pan/pen number', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_apaar" class="wlsm-font-bold"><?php esc_html_e('APAAR Number', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_apaar, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_apaar" id="wlsm_registration_apaar" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_apaar">
								<?php esc_html_e('  Show APAAR Number', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Registration form will not show apaar number', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_registration_parent_id_number" class="wlsm-font-bold"><?php esc_html_e('Parent ID Number', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_parent_id_number, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_parent_id_number" id="wlsm_registration_parent_id_number" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_parent_id_number">
								<?php esc_html_e('  Show Parent ID Number', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Registration form will not show parent id number', 'school-management'); ?>
							</p>
						</div>
					</div>

					<div class="col-md-3">
						<label for="wlsm_registration_add_sibling_button" class="wlsm-font-bold"><?php esc_html_e('Add Sibling Button', 'school-management'); ?>:</label>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<input <?php checked($school_registration_add_sibling_button, true, true); ?> class="form-check-input mt-1" type="checkbox" name="registration_add_sibling_button" id="wlsm_registration_add_sibling_button" value="1">
							<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_registration_add_sibling_button">
								<?php esc_html_e('  Show Add Sibling Button', 'school-management'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Registration form will not show add sibling button', 'school-management'); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12 text-center">
						<button type="submit" class="btn btn-primary" id="wlsm-save-school-registration-settings-btn">
							<i class="fas fa-save"></i>&nbsp;
							<?php esc_html_e('Save', 'school-management'); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>

</div>
