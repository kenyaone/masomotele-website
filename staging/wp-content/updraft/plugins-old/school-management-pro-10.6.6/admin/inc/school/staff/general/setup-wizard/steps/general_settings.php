<?php
defined( 'ABSPATH' ) || die();

// Get current settings
$settings = WLSM_M_Setting::get_settings_general( $school_id );
?>

<form id="wlsm-step-general_settings-form" class="wlsm-setup-step-form" data-step="general_settings">
    <div class="mb-4">
        <h5 class="text-primary mb-3">
            <i class="fas fa-cog mr-2"></i>
            <?php esc_html_e( 'General School Settings', 'school-management' ); ?>
        </h5>
        <p class="text-muted">
            <?php esc_html_e( 'Configure basic settings for your school. These are optional and can be modified later.', 'school-management' ); ?>
        </p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Basic School Information -->
            <div class="settings-section bg-white  rounded p-4 mb-4">
                <div class="section-header mb-3">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-school mr-2"></i>
                        <?php esc_html_e( 'School Information', 'school-management' ); ?>
                    </h6>
                </div>
                <div class="section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="school_address"><?php esc_html_e( 'School Address', 'school-management' ); ?>:</label>
                                <textarea class="form-control" 
                                          id="school_address" 
                                          name="school_address" 
                                          rows="3" 
                                          placeholder="<?php esc_attr_e( 'Enter school address', 'school-management' ); ?>"><?php echo esc_textarea( $school->address ?? '' ); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="school_website"><?php esc_html_e( 'School Website', 'school-management' ); ?>:</label>
                                <input type="url" 
                                       class="form-control" 
                                       id="school_website" 
                                       name="school_website" 
                                       placeholder="<?php esc_attr_e( 'https://yourschool.com', 'school-management' ); ?>"
                                       value="<?php echo esc_url( $school->website ?? '' ); ?>">
                            </div>
                            <div class="form-group">
                                <label for="school_logo"><?php esc_html_e( 'School Logo URL', 'school-management' ); ?>:</label>
                                <input type="url" 
                                       class="form-control" 
                                       id="school_logo" 
                                       name="school_logo" 
                                       placeholder="<?php esc_attr_e( 'https://yourschool.com/logo.png', 'school-management' ); ?>"
                                       value="<?php echo esc_url( $school->logo ?? '' ); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="settings-section">
                <div class="section-header mb-3">
                    <h6 class="mb-0 text-info">
                        <i class="fas fa-tools mr-2"></i>
                        <?php esc_html_e( 'System Settings', 'school-management' ); ?>
                    </h6>
                </div>
                <div class="section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_format"><?php esc_html_e( 'Date Format', 'school-management' ); ?>:</label>
                                <select class="form-control" id="date_format" name="date_format">
                                    <option value="d/m/Y">DD/MM/YYYY</option>
                                    <option value="m/d/Y">MM/DD/YYYY</option>
                                    <option value="Y-m-d" selected>YYYY-MM-DD</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currency"><?php esc_html_e( 'Currency Symbol', 'school-management' ); ?>:</label>
                                <select class="form-control" id="currency" name="currency">
                                    <option value="₹" selected>₹ (Indian Rupee)</option>
                                    <option value="$">$ (US Dollar)</option>
                                    <option value="€">€ (Euro)</option>
                                    <option value="£">£ (British Pound)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="enable_student_portal" name="enable_student_portal" checked>
                                <label class="custom-control-label" for="enable_student_portal">
                                    <?php esc_html_e( 'Enable Student Portal', 'school-management' ); ?>
                                </label>
                            </div>
                            <small class="text-muted"><?php esc_html_e( 'Allow students to access their accounts', 'school-management' ); ?></small>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="enable_parent_portal" name="enable_parent_portal" checked>
                                <label class="custom-control-label" for="enable_parent_portal">
                                    <?php esc_html_e( 'Enable Parent Portal', 'school-management' ); ?>
                                </label>
                            </div>
                            <small class="text-muted"><?php esc_html_e( 'Allow parents to access student information', 'school-management' ); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Settings Summary -->
            <div class="settings-section">
                <div class="section-header mb-3">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-eye mr-2"></i>
                        <?php esc_html_e( 'Settings Summary', 'school-management' ); ?>
                    </h6>
                </div>
                <div class="section-body" id="settings-summary">
                    <p class="text-muted text-center">
                        <i class="fas fa-info-circle"></i><br>
                        <?php esc_html_e( 'Your settings will be summarized here.', 'school-management' ); ?>
                    </p>
                </div>
            </div>
            
            <!-- Help Section -->
            <div class="settings-section mt-3">
                <div class="section-header mb-3">
                    <h6 class="mb-0 text-secondary">
                        <i class="fas fa-question-circle mr-2"></i>
                        <?php esc_html_e( 'Need Help?', 'school-management' ); ?>
                    </h6>
                </div>
                <div class="section-body">
                    <small class="text-muted">
                        <?php esc_html_e( 'These settings are optional for now. You can always configure them later from the school settings page.', 'school-management' ); ?>
                    </small>
                    <hr>
                    <small class="text-muted">
                        <strong><?php esc_html_e( 'Quick Tips:', 'school-management' ); ?></strong><br>
                        • <?php esc_html_e( 'Settings can be changed anytime', 'school-management' ); ?><br>
                        • <?php esc_html_e( 'Enable portals for better engagement', 'school-management' ); ?><br>
                        • <?php esc_html_e( 'Choose appropriate date format', 'school-management' ); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</form>
