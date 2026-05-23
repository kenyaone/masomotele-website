<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Class.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/vendor/autoload.php';

try {
    // Get session ID
    if (isset($attr['session_id'])) {
        $session_id = absint($attr['session_id']);
    } else {
        $session_id = get_option('wlsm_current_session');
    }

    // Get staff details
    $user_id = get_current_user_id();
    $staff_id = WLSM_M_Staff_General::get_staff_id_from_user($user_id);

    if (!$staff_id || !$staff_id->user_id) {
        throw new Exception(__('Staff not found.', 'school-management'));
    }

    $staff_id = $staff_id->user_id;

    // Get Zoom credentials
    $settings_zoom_api_key = get_user_meta($staff_id, 'api_key', true);
    $settings_zoom_api_secret = get_user_meta($staff_id, 'api_secret', true);
    $settings_zoom_api_url = get_user_meta($staff_id, 'redirect_url', true);

    if (empty($settings_zoom_api_key) || empty($settings_zoom_api_secret) || empty($settings_zoom_api_url)) {
        throw new Exception(__('Zoom API credentials not configured properly.', 'school-management'));
    }

    // Initialize Guzzle client with timeout and SSL verification
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://zoom.us',
        'timeout' => 30,
        'verify' => true
    ]);

    if (isset($_GET['code'])) {
        // Log request data for debugging
        error_log('Zoom OAuth Request - Code: ' . $_GET['code']);

        try {
            $response = $client->request(
                'POST',
                '/oauth/token',
                [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($settings_zoom_api_key . ':' . $settings_zoom_api_secret),
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'code' => sanitize_text_field($_GET['code']),
                        'redirect_uri' => $settings_zoom_api_url,
                    ],
                ]
            );

            $token_data = json_decode($response->getBody()->getContents(), true);

            if (!isset($token_data['access_token'])) {
                throw new Exception(__('Invalid response from Zoom API.', 'school-management'));
            }

            // Save token and expiration
            update_user_meta($staff_id, 'token', $token_data['access_token']);
            update_user_meta($staff_id, 'token_expiration', time() + 3600);

            ?>
            <div class="notice notice-success">
                <p><?php esc_html_e('Successfully updated Zoom access token.', 'school-management'); ?></p>
            </div>
            <?php

        } catch (GuzzleHttp\Exception\ClientException $e) {
            $error_response = json_decode($e->getResponse()->getBody()->getContents(), true);
            $error_message = isset($error_response['reason']) ?
                           $error_response['reason'] :
                           __('Error obtaining access token.', 'school-management');

            ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
            <?php

            error_log('Zoom OAuth Error: ' . $e->getMessage());
        }
    }

} catch (Exception $e) {
    ?>
    <div class="notice notice-error">
        <p><?php echo esc_html($e->getMessage()); ?></p>
    </div>
    <?php
    error_log('Zoom OAuth General Error: ' . $e->getMessage());
}

return ob_get_clean();
