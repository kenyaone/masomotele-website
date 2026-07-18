<?php
/**
 * PesaPal API v3 client — auth token, IPN registration, order submission,
 * and transaction status lookup. Used by the online self-checkout flow
 * ([mtti_course_checkout]) to take real payment instead of the manual
 * "staff confirms M-Pesa receipt" bridge.
 *
 * Credentials (PESAPAL_CONSUMER_KEY / PESAPAL_CONSUMER_SECRET / PESAPAL_ENV)
 * live in wp-config.php, never in this file.
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Pesapal {

    private static function base_url() {
        return (defined('PESAPAL_ENV') && PESAPAL_ENV === 'live')
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';
    }

    /**
     * Bearer token — PesaPal tokens are short-lived (~5 minutes), so we
     * cache in a transient and re-request a little before real expiry.
     */
    public static function get_token() {
        $cached = get_transient('mtti_pesapal_token');
        if ($cached) return $cached;

        if (!defined('PESAPAL_CONSUMER_KEY') || !defined('PESAPAL_CONSUMER_SECRET')) {
            return new WP_Error('pesapal_config', 'PesaPal credentials are not configured.');
        }

        $response = wp_remote_post(self::base_url() . '/api/Auth/RequestToken', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'consumer_key'    => PESAPAL_CONSUMER_KEY,
                'consumer_secret' => PESAPAL_CONSUMER_SECRET,
            )),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['token'])) {
            $msg = $body['error']['message'] ?? ($body['message'] ?? 'PesaPal did not return a token.');
            return new WP_Error('pesapal_auth', $msg, $body);
        }

        // Cache for 4 minutes — safely inside PesaPal's ~5 minute expiry.
        set_transient('mtti_pesapal_token', $body['token'], 4 * MINUTE_IN_SECONDS);
        return $body['token'];
    }

    /**
     * Registers our IPN callback URL with PesaPal and caches the returned
     * ipn_id (needed on every order submission). Only re-registers if the
     * stored URL doesn't match the current one (e.g. domain change).
     */
    public static function get_ipn_id() {
        $ipn_url = home_url('/wp-admin/admin-ajax.php?action=mtti_pesapal_ipn');

        $stored = get_option('mtti_pesapal_ipn');
        if (!empty($stored['ipn_id']) && ($stored['url'] ?? '') === $ipn_url) {
            return $stored['ipn_id'];
        }

        $token = self::get_token();
        if (is_wp_error($token)) return $token;

        $response = wp_remote_post(self::base_url() . '/api/URLSetup/RegisterIPN', array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ),
            'body' => wp_json_encode(array(
                'url'                  => $ipn_url,
                'ipn_notification_type' => 'GET',
            )),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['ipn_id'])) {
            $msg = $body['error']['message'] ?? ($body['message'] ?? 'PesaPal did not return an ipn_id.');
            return new WP_Error('pesapal_ipn_register', $msg, $body);
        }

        update_option('mtti_pesapal_ipn', array('url' => $ipn_url, 'ipn_id' => $body['ipn_id']), false);
        return $body['ipn_id'];
    }

    /**
     * Submits an order and returns ['order_tracking_id' => ..., 'redirect_url' => ...]
     * or a WP_Error. $args: merchant_reference, amount, description, email,
     * phone, first_name, last_name.
     */
    public static function submit_order($args) {
        $token = self::get_token();
        if (is_wp_error($token)) return $token;

        $ipn_id = self::get_ipn_id();
        if (is_wp_error($ipn_id)) return $ipn_id;

        $billing = array(
            'phone_number' => $args['phone'] ?? '',
            'country_code' => 'KE',
            'first_name'   => $args['first_name'] ?? '',
            'last_name'    => $args['last_name'] ?? '',
        );
        if (!empty($args['email'])) {
            $billing['email_address'] = $args['email'];
        }

        $payload = array(
            'id'              => $args['merchant_reference'],
            'currency'        => 'KES',
            'amount'          => (float) $args['amount'],
            'description'     => substr($args['description'] ?? 'MTTI course enrollment', 0, 100),
            'callback_url'    => home_url('/wp-admin/admin-ajax.php?action=mtti_pesapal_return&ref=' . rawurlencode($args['merchant_reference'])),
            'notification_id' => $ipn_id,
            'billing_address' => $billing,
        );

        $response = wp_remote_post(self::base_url() . '/api/Transactions/SubmitOrderRequest', array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ),
            'body'    => wp_json_encode($payload),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['redirect_url']) || empty($body['order_tracking_id'])) {
            $msg = $body['error']['message'] ?? ($body['message'] ?? 'PesaPal did not return a redirect URL.');
            return new WP_Error('pesapal_submit_order', $msg, $body);
        }

        return array(
            'order_tracking_id' => $body['order_tracking_id'],
            'redirect_url'      => $body['redirect_url'],
        );
    }

    /**
     * Looks up live status for an order. Returns an array with at least
     * 'status_code' (1=Completed, 2=Failed, 0=Invalid, 3=Reversed) and
     * 'payment_status_description', or a WP_Error.
     */
    public static function get_transaction_status($order_tracking_id) {
        $token = self::get_token();
        if (is_wp_error($token)) return $token;

        $response = wp_remote_get(
            self::base_url() . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . rawurlencode($order_tracking_id),
            array(
                'headers' => array(
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
                'timeout' => 20,
            )
        );

        if (is_wp_error($response)) return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['status_code']) && !isset($body['payment_status_description'])) {
            $msg = $body['error']['message'] ?? ($body['message'] ?? 'PesaPal did not return a transaction status.');
            return new WP_Error('pesapal_status', $msg, $body);
        }

        return $body;
    }
}
