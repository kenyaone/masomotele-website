<?php
defined( 'ABSPATH' ) || die();

use APITestCode\PayU as WLSM_PayU_SDK;

if ( ! class_exists( '\APITestCode\PayU' ) ) {
	require_once WLSM_PLUGIN_DIR_PATH . 'includes/libs/payu-sdk/PayU.php';
}

class WLSM_Payu {
	/**
	 * Field order recommended by PayU for generating request hashes.
	 *
	 * @var array
	 */
	private static $request_sequence = array(
		'key',
		'txnid',
		'amount',
		'productinfo',
		'firstname',
		'email',
		'udf1',
		'udf2',
		'udf3',
		'udf4',
		'udf5',
		'udf6',
		'udf7',
		'udf8',
		'udf9',
		'udf10',
	);

	/**
	 * Normalises a value so it is suitable for hashing and output.
	 *
	 * @param mixed $value Value to normalise.
	 *
	 * @return string
	 */
	private static function stringify_value( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		if ( is_scalar( $value ) || null === $value ) {
			return (string) $value;
		}

		return wp_json_encode( $value );
	}

	/**
	 * Ensures every field required for the hash exists and is a string.
	 *
	 * @param array $params Raw parameters.
	 *
	 * @return array
	 */
	private static function normalize_request_params( $params ) {
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		foreach ( self::$request_sequence as $field ) {
			if ( isset( $params[ $field ] ) ) {
				$params[ $field ] = self::stringify_value( $params[ $field ] );
			} else {
				$params[ $field ] = '';
			}
		}

		return $params;
	}

	/**
	 * Instantiates the official PayU SDK configured for the given credentials.
	 *
	 * @param string $merchant_key  Merchant key provided by PayU.
	 * @param string $merchant_salt Merchant salt provided by PayU.
	 * @param string $mode          Operating mode: 'live' or 'test'.
	 *
	 * @return WLSM_PayU_SDK
	 */
	private static function get_sdk( $merchant_key, $merchant_salt, $mode = 'test' ) {
		$instance = new WLSM_PayU_SDK();
		$instance->key = $merchant_key;
		$instance->salt = $merchant_salt;
		$instance->env_prod = ( 'live' === strtolower( $mode ) );
		$instance->initGateway();

		return $instance;
	}

	/**
	 * Generates a request hash using the official ordering.
	 *
	 * @param array  $params Request parameters.
	 * @param string $salt   Merchant salt.
	 *
	 * @return string
	 */
	public static function generate_hash( $params, $salt ) {
		$params = self::normalize_request_params( $params );

		$hash_parts = array();
		foreach ( self::$request_sequence as $field ) {
			$hash_parts[] = $params[ $field ];
		}
		$hash_parts[] = $salt;

		return strtolower( hash( 'sha512', implode( '|', $hash_parts ) ) );
	}

	/**
	 * Builds the auto-submitting PayU form HTML using the official SDK.
	 *
	 * @param array  $fields        Fields expected by PayU.
	 * @param string $merchant_key  Merchant key.
	 * @param string $merchant_salt Merchant salt.
	 * @param string $mode          Operating mode: 'live' or 'test'.
	 *
	 * @return string Rendered HTML form ready to be injected into the page.
	 */
	public static function build_payment_form( $fields, $merchant_key, $merchant_salt, $mode = 'test' ) {
		$sdk = self::get_sdk( $merchant_key, $merchant_salt, $mode );

		ob_start();
		$sdk->showPaymentForm( $fields );

		$form_html = ob_get_clean();

		return is_string( $form_html ) ? trim( $form_html ) : '';
	}

	/**
	 * Validates the hash in a PayU callback response using the official SDK logic.
	 *
	 * @param array  $posted Posted data received from PayU.
	 * @param string $merchant_salt Merchant salt.
	 *
	 * @return bool
	 */
	public static function verify_hash( $posted, $merchant_salt ) {
		if ( empty( $posted ) || ! is_array( $posted ) ) {
			return false;
		}

		$merchant_key = isset( $posted['key'] ) ? $posted['key'] : '';
		$sdk          = self::get_sdk( $merchant_key, $merchant_salt );

		return (bool) $sdk->verifyHash( $posted );
	}
}
