<?php
defined('ABSPATH') || die();

// Get Firebase settings
$settings_firebase = WLSM_M_Setting::get_settings_firebase($school_id);
$json_file = $settings_firebase['json_file'];
$enable_firebase = $settings_firebase['enable_firebase'];
?>

<div class="tab-pane fade" id="wlsm-school-firebase" role="tabpanel" aria-labelledby="wlsm-school-firebase-tab">
    <div class="row">
        <div class="col-md-12">
            <div class="wlsm-form-section">
                <div class="row">
                    <div class="col-md-12">
                        <div class="wlsm-form-sub-heading wlsm-font-bold">
                            <span class="border-bottom"><?php esc_html_e('Firebase Notification Settings', 'school-management'); ?></span>
                        </div>
                    </div>
                </div>

                <form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-save-school-firebase-settings-form" enctype="multipart/form-data">
                    <?php
                    $nonce_action = 'save-school-firebase-settings';
                    $nonce = wp_create_nonce($nonce_action);
                    ?>
                    <input type="hidden" name="<?php echo esc_attr($nonce_action); ?>" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="action" value="wlsm-save-school-firebase-settings">

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label for="wlsm_json_file" class="wlsm-font-bold">
                                <?php esc_html_e('Firebase Configuration (JSON File)', 'school-management'); ?>:
                            </label>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="wlsm_json_file" name="json_file">
                                    <label class="custom-file-label" for="wlsm_json_file" id="wlsm_json_file_label">
                                        <?php echo $json_file ? esc_html(basename($json_file)) : esc_html__('Choose File', 'school-management'); ?>
                                    </label>
                                </div>
                                <?php if ($json_file) : ?>
                                <p class="text-secondary mt-1">
                                    <span class="text-success">
                                        <i class="fas fa-check-circle"></i>
                                        <?php esc_html_e('File uploaded successfully', 'school-management'); ?>
                                    </span>
                                </p>
                                <?php endif; ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input position-static" name="remove-json" id="remove-json" type="checkbox">
                                    <label class="form-check-label text-danger ml-2" for="remove-json">
                                        <?php esc_html_e("Remove uploaded JSON file", "school-management") ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="wlsm-font-bold" for="enable_firebase">
                                <?php esc_html_e('Enable Firebase Notifications', 'school-management'); ?>:
                            </label>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input position-static" type="checkbox" name="enable_firebase" id="enable_firebase" value="1" <?php checked($enable_firebase, 1, true); ?>>
                                    <label class="form-check-label text-secondary ml-2" for="enable_firebase">
                                        <?php esc_html_e('Enable push notifications via Firebase', 'school-management'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading mb-2">
                                    <i class="fas fa-info-circle"></i>
                                    <?php esc_html_e('Firebase Configuration Instructions', 'school-management'); ?>
                                </h6>
                                <ol class="mb-0 pl-3">
                                    <li><?php esc_html_e('Create a project in the Firebase console', 'school-management'); ?></li>
                                    <li><?php esc_html_e('Go to Project Settings → Service accounts', 'school-management'); ?></li>
                                    <li><?php esc_html_e('Generate a new private key (JSON file)', 'school-management'); ?></li>
                                    <li><?php esc_html_e('Upload the downloaded JSON file using the form above', 'school-management'); ?></li>
                                </ol>
                                <hr>
                                <a class="btn btn-sm btn-outline-info" target="_blank" href="https://youtu.be/qV4SZPHUypw?si=0lOhSYxIYyZ0zrLj">
                                    <i class="fab fa-youtube"></i> <?php esc_html_e('Watch Video Tutorial', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary" id="wlsm-save-school-firebase-settings-btn">
                                <i class="fas fa-save"></i>&nbsp;
                                <?php esc_html_e('Save Settings', 'school-management'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update file input label when file is selected
    $('#wlsm_json_file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).next('#wlsm_json_file_label').html(fileName);
        } else {
            $(this).next('#wlsm_json_file_label').html('<?php esc_html_e('Choose File', 'school-management'); ?>');
        }
    });
});
</script>
