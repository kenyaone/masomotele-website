<?php
defined('ABSPATH') || die();

// Get current registration settings
$settings_registration = WLSM_M_Setting::get_settings_registration($school_id);
?>

<div class="wlsm-registration-settings-step">
    <div class="text-center mb-4">
        <i class="fas fa-user-plus text-primary mb-3" style="font-size: 3rem;"></i>
        <h3 class="text-primary"><?php esc_html_e('Student Registration Settings', 'school-management'); ?></h3>
        <p class="text-muted"><?php esc_html_e('Configure how students can register at your school and what information they need to provide.', 'school-management'); ?></p>
    </div>

    <form id="wlsm-step-registration-settings-form" class="wlsm-setup-step-form" data-step="registration_settings" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">
        <?php
        $nonce_action = 'save-school-registration-settings';
        $nonce        = wp_create_nonce($nonce_action);
        ?>
        <input type="hidden" name="<?php echo esc_attr($nonce_action); ?>" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="action" value="wlsm-save-school-registration-settings">
        
        <!-- Form Title & Basic Settings -->
        <div class="mb-4 border rounded p-4 bg-white">
            <div class="mb-3">
                <h5 class="mb-0 text-primary"><i class="fas fa-cog mr-2"></i><?php esc_html_e('Basic Registration Settings', 'school-management'); ?></h5>
            </div>
            <div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="registration_form_title"><?php esc_html_e('Registration Form Title', 'school-management'); ?></label>
                            <input type="text" class="form-control" id="registration_form_title" name="registration_form_title"
                                value="<?php echo esc_attr($settings_registration['form_title']); ?>"
                                placeholder="<?php esc_attr_e('e.g., Student Registration Form', 'school-management'); ?>">
                            <small class="text-muted"><?php esc_html_e('This title will appear on the registration form.', 'school-management'); ?></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="registration_admin_email"><?php esc_html_e('Admin Email for Notifications', 'school-management'); ?></label>
                            <input type="email" class="form-control" id="registration_admin_email" name="registration_admin_email"
                                value="<?php echo esc_attr($settings_registration['admin_email']); ?>"
                                placeholder="<?php esc_attr_e('admin@school.com', 'school-management'); ?>">
                            <small class="text-muted"><?php esc_html_e('Email to receive registration notifications.', 'school-management'); ?></small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="registration_admin_phone"><?php esc_html_e('Admin Phone for SMS Notifications', 'school-management'); ?></label>
                            <input type="text" class="form-control" id="registration_admin_phone" name="registration_admin_phone"
                                value="<?php echo esc_attr($settings_registration['admin_phone']); ?>"
                                placeholder="<?php esc_attr_e('+1234567890', 'school-management'); ?>">
                            <small class="text-muted"><?php esc_html_e('Phone number to receive SMS notifications.', 'school-management'); ?></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="redirect_url"><?php esc_html_e('Redirect URL After Registration', 'school-management'); ?></label>
                            <input type="url" class="form-control" id="redirect_url" name="redirect_url"
                                value="<?php echo esc_attr($settings_registration['redirect_url']); ?>"
                                placeholder="<?php esc_attr_e('https://school.com/thank-you', 'school-management'); ?>">
                            <small class="text-muted"><?php esc_html_e('Where to redirect students after successful registration.', 'school-management'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration Options -->
        <div class="mb-4 border rounded p-4 bg-white">
            <div class="mb-3">
                <h5 class="mb-0 text-success"><i class="fas fa-toggle-on mr-2"></i><?php esc_html_e('Registration Options', 'school-management'); ?></h5>
            </div>
            <div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="registration_login_user" name="registration_login_user"
                                value="1" <?php checked($settings_registration['login_user'], true); ?>>
                            <label class="custom-control-label" for="registration_login_user">
                                <strong><?php esc_html_e('Auto-login After Registration', 'school-management'); ?></strong>
                            </label>
                            <small class="d-block text-muted"><?php esc_html_e('Automatically log in students after they register.', 'school-management'); ?></small>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="student_aprove" name="student_aprove"
                                value="1" <?php checked($settings_registration['student_aprove'], true); ?>>
                            <label class="custom-control-label" for="student_aprove">
                                <strong><?php esc_html_e('Require Admin Approval', 'school-management'); ?></strong>
                            </label>
                            <small class="d-block text-muted"><?php esc_html_e('Students will be inactive until approved by admin.', 'school-management'); ?></small>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="registration_create_invoice" name="registration_create_invoice"
                                value="1" <?php checked($settings_registration['create_invoice'], true); ?>>
                            <label class="custom-control-label" for="registration_create_invoice">
                                <strong><?php esc_html_e('Auto-create Invoices', 'school-management'); ?></strong>
                            </label>
                            <small class="d-block text-muted"><?php esc_html_e('Automatically create invoices based on fee types.', 'school-management'); ?></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="registration_auto_admission_number" name="registration_auto_admission_number"
                                value="1" <?php checked($settings_registration['auto_admission_number'], true); ?>>
                            <label class="custom-control-label" for="registration_auto_admission_number">
                                <strong><?php esc_html_e('Auto-generate Admission Numbers', 'school-management'); ?></strong>
                            </label>
                            <small class="d-block text-muted"><?php esc_html_e('Automatically generate admission numbers for new students.', 'school-management'); ?></small>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="registration_auto_roll_number" name="registration_auto_roll_number"
                                value="1" <?php checked($settings_registration['auto_roll_number'], true); ?>>
                            <label class="custom-control-label" for="registration_auto_roll_number">
                                <strong><?php esc_html_e('Auto-generate Roll Numbers', 'school-management'); ?></strong>
                            </label>
                            <small class="d-block text-muted"><?php esc_html_e('Automatically generate roll numbers for new students.', 'school-management'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Required Information -->
        <div class="mb-4 border rounded p-3 bg-light">
            <div class="mb-3">
                <h5 class="mb-0 text-info"><i class="fas fa-list mr-2"></i><?php esc_html_e('Required Student Information', 'school-management'); ?></h5>
                <small class="text-muted"><?php esc_html_e('Select which fields students must fill during registration', 'school-management'); ?></small>
            </div>
            <div>
                <div class="row">
                    <div class="col-md-4 d-flex flex-column gap-2">
                        <h6 class="text-primary mb-3"><?php esc_html_e('Personal Information', 'school-management'); ?></h6>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_dob" name="registration_dob"
                                value="1" <?php checked($settings_registration['dob'], true); ?>>
                            <label class="form-check-label" for="registration_dob">
                                <?php esc_html_e('Date of Birth', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_religion" name="registration_religion"
                                value="1" <?php checked($settings_registration['religion'], true); ?>>
                            <label class="form-check-label" for="registration_religion">
                                <?php esc_html_e('Religion', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_caste" name="registration_caste"
                                value="1" <?php checked($settings_registration['caste'], true); ?>>
                            <label class="form-check-label" for="registration_caste">
                                <?php esc_html_e('Caste/Sub-caste', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_blood_group" name="registration_blood_group"
                                value="1" <?php checked($settings_registration['blood_group'], true); ?>>
                            <label class="form-check-label" for="registration_blood_group">
                                <?php esc_html_e('Blood Group', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_id_number" name="registration_id_number"
                                value="1" <?php checked($settings_registration['id_number'], true); ?>>
                            <label class="form-check-label" for="registration_id_number">
                                <?php esc_html_e('ID Number/Proof', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_student_photo" name="registration_student_photo"
                                value="1" <?php checked($settings_registration['student_photo'], true); ?>>
                            <label class="form-check-label" for="registration_student_photo">
                                <?php esc_html_e('Student Photo', 'school-management'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4 d-flex flex-column gap-2">
                        <h6 class="text-primary mb-3"><?php esc_html_e('Contact Information', 'school-management'); ?></h6>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_phone" name="registration_phone"
                                value="1" <?php checked($settings_registration['phone'], true); ?>>
                            <label class="form-check-label" for="registration_phone">
                                <?php esc_html_e('Phone Number', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_city" name="registration_city"
                                value="1" <?php checked($settings_registration['city'], true); ?>>
                            <label class="form-check-label" for="registration_city">
                                <?php esc_html_e('City', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_state" name="registration_state"
                                value="1" <?php checked($settings_registration['state'], true); ?>>
                            <label class="form-check-label" for="registration_state">
                                <?php esc_html_e('State', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_country" name="registration_country"
                                value="1" <?php checked($settings_registration['country'], true); ?>>
                            <label class="form-check-label" for="registration_country">
                                <?php esc_html_e('Country', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_address" name="registration_address"
                                value="1" <?php checked($settings_registration['address'], true); ?>>
                            <label class="form-check-label" for="registration_address">
                                <?php esc_html_e('Full Address', 'school-management'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4 d-flex flex-column gap-2">
                        <h6 class="text-primary mb-3"><?php esc_html_e('Additional Panels', 'school-management'); ?></h6>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_parent_detail" name="registration_parent_detail"
                                value="1" <?php checked($settings_registration['parent_detail'], true); ?>>
                            <label class="form-check-label" for="registration_parent_detail">
                                <?php esc_html_e('Parent Details Panel', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_parent_login" name="registration_parent_login"
                                value="1" <?php checked($settings_registration['parent_login'], true); ?>>
                            <label class="form-check-label" for="registration_parent_login">
                                <?php esc_html_e('Parent Login Panel', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_student_login" name="registration_student_login"
                                value="1" <?php checked($settings_registration['student_login'], true); ?>>
                            <label class="form-check-label" for="registration_student_login">
                                <?php esc_html_e('Student Login Panel', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_transport" name="registration_transport"
                                value="1" <?php checked($settings_registration['transport'], true); ?>>
                            <label class="form-check-label" for="registration_transport">
                                <?php esc_html_e('Transport Details', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_fees" name="registration_fees"
                                value="1" <?php checked($settings_registration['fees'], true); ?>>
                            <label class="form-check-label" for="registration_fees">
                                <?php esc_html_e('Fees Panel', 'school-management'); ?>
                            </label>
                        </div>

                        <div class="form-check form-check-inline mb-2">
                            <input type="checkbox" class="form-check-input" id="registration_survey" name="registration_survey"
                                value="1" <?php checked($settings_registration['survey'], true); ?>>
                            <label class="form-check-label" for="registration_survey">
                                <?php esc_html_e('Survey Panel', 'school-management'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <div class="mb-4 border rounded p-4 bg-white">
            <div class="mb-3">
                <h5 class="mb-0 text-success"><i class="fas fa-check-circle mr-2"></i><?php esc_html_e('Registration Success Message', 'school-management'); ?></h5>
            </div>
            <div>
                <div class="form-group">
                    <label for="registration_success_message"><?php esc_html_e('Success Message', 'school-management'); ?></label>
                    <textarea class="form-control" id="registration_success_message" name="registration_success_message" rows="4"
                        placeholder="<?php esc_attr_e('Thank you for registering! We will contact you soon...', 'school-management'); ?>"><?php echo esc_textarea($settings_registration['success_message']); ?></textarea>
                    <small class="text-muted">
                        <?php esc_html_e('This message will be displayed to students after successful registration.', 'school-management'); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Recommended Settings Preview -->
        <div class="border border-info rounded p-4 bg-white">
            <div class="mb-3 bg-info text-white p-2 rounded">
                <h6 class="mb-0"><i class="fas fa-lightbulb mr-2"></i><?php esc_html_e('Recommended Settings', 'school-management'); ?></h6>
            </div>
            <div>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success"><?php esc_html_e('✓ Enable These Fields:', 'school-management'); ?></h6>
                        <ul class="list-unstyled small">
                            <li>• <?php esc_html_e('Date of Birth', 'school-management'); ?></li>
                            <li>• <?php esc_html_e('Phone Number', 'school-management'); ?></li>
                            <li>• <?php esc_html_e('Parent Details Panel', 'school-management'); ?></li>
                            <li>• <?php esc_html_e('Auto-generate Admission Numbers', 'school-management'); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-warning"><?php esc_html_e('⚠ Consider Carefully:', 'school-management'); ?></h6>
                        <ul class="list-unstyled small">
                            <li>• <?php esc_html_e('Require Admin Approval (delays student access)', 'school-management'); ?></li>
                            <li>• <?php esc_html_e('Too many required fields (complex forms)', 'school-management'); ?></li>
                            <li>• <?php esc_html_e('Student Photo (may reduce registrations)', 'school-management'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="invalid-feedback d-block" id="registration-settings-error" style="display: none !important;"></div>
    </form>
</div>