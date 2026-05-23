<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Config.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Payment.php';

class WLSM_M_Invoice {
	private static $paid           = 'paid';
	private static $unpaid         = 'unpaid';
	private static $partially_paid = 'partially_paid';

	public static function get_status() {
		return array(
			self::$paid           => esc_html__( 'Paid', 'school-management' ),
			self::$unpaid         => esc_html__( 'Unpaid', 'school-management' ),
			self::$partially_paid => esc_html__( 'Partially Paid', 'school-management' ),
		);
	}

	public static function get_due_amount( $data ) {
		$due = $data['total'] - $data['discount'];
		return WLSM_Config::sanitize_money( $due );
	}

	public static function get_invoice_number( $school_id ) {
		global $wpdb;

		$last_invoice_count = $wpdb->get_var(
			$wpdb->prepare( 'SELECT last_invoice_count FROM ' . WLSM_SCHOOLS . ' as s WHERE s.ID = %d', $school_id )
		);

		$new_invoice_count = absint( $last_invoice_count ) + 1;

		$data = array(
			'last_invoice_count' => $new_invoice_count,
		);

		// Invoice number formatting.
		$invoice_number = str_pad( $new_invoice_count, 5 , '0', STR_PAD_LEFT );

		$success = $wpdb->update( WLSM_SCHOOLS, $data, array( 'ID' => $school_id ) );

		$buffer = ob_get_clean();
		if ( ! empty( $buffer ) ) {
			throw new Exception( $buffer );
		}

		if ( false === $success ) {
			throw new Exception( $wpdb->last_error );
		}

		return $invoice_number;
	}

	public static function get_receipt_number( $school_id ) {
		global $wpdb;

		$last_payment_count = $wpdb->get_var(
			$wpdb->prepare( 'SELECT last_payment_count FROM ' . WLSM_SCHOOLS . ' as s WHERE s.ID = %d', $school_id )
		);

		$new_payment_count = absint( $last_payment_count ) + 1;

		$data = array(
			'last_payment_count' => $new_payment_count,
		);

		// Receipt number formatting.
		$payment_number = str_pad( $new_payment_count, 6 , '0', STR_PAD_LEFT );

		$success = $wpdb->update( WLSM_SCHOOLS, $data, array( 'ID' => $school_id ) );

		$buffer = ob_get_clean();
		if ( ! empty( $buffer ) ) {
			throw new Exception( $buffer );
		}

		if ( false === $success ) {
			throw new Exception( $wpdb->last_error );
		}

		return $payment_number;
	}

	public static function get_status_key( $payable, $paid ) {
		$due = $payable - $paid;
		if ( $due <= 0 ) {
			return self::get_paid_key();
		} else if ( $due == $payable ) {
			return self::get_unpaid_key();
		} else {
			return self::get_partially_paid_key();
		}
	}

	public static function get_status_text( $status, $color = true ) {
		if ( array_key_exists( $status, self::get_status() ) ) {
			$status_text = self::get_status()[ $status ];
			if ( ! $color ) {
				return $status_text;
			}

			if ( self::$paid == $status ) {
				$status_text = '<span class="text-success wlsm-font-bold">' . esc_html( $status_text ) . '</span>';
			} else if ( self::$unpaid == $status ) {
				$status_text = '<span class="text-danger wlsm-font-bold">' . esc_html( $status_text ) . '</span>';
			} else {
				$status_text = '<span class="text-primary wlsm-font-bold">' . esc_html( $status_text ) . '</span>';
			}

			return $status_text;
		}

		return '';
	}

	public static function get_paid_key() {
		return self::$paid;
	}

	public static function get_unpaid_key() {
		return self::$unpaid;
	}

	public static function get_partially_paid_key() {
		return self::$partially_paid;
	}

	public static function get_paid_text() {
		return self::get_status()[ self::$paid ];
	}

	public static function get_unpaid_text() {
		return self::get_status()[ self::$unpaid ];
	}

	public static function get_partially_paid_text() {
		return self::get_status()[ self::$partially_paid ];
	}

	public static function collect_payment_methods() {
		return array(
			'cash'          => esc_html__('Cash', 'school-management'),
			'card'          => esc_html__('Card', 'school-management'),
			'check'         => esc_html__('Cheque', 'school-management'),
			'demand-draft'  => esc_html__('Demand Draft', 'school-management'),
			'bank-transfer' => esc_html__('Bank Transfer', 'school-management'),
			'upi-transfer'  => esc_html__('UPI Transfer', 'school-management'),
			'stripe'        => esc_html__('Stripe', 'school-management'),
			'payu'          => esc_html__('PayU', 'school-management'),
			'paypal'        => esc_html__('PayPal', 'school-management'),
			'razorpay'      => esc_html__('Razorpay', 'school-management'),
			'paytm'         => esc_html__('Paytm', 'school-management'),
			'pesapal'       => esc_html__('Pesapal', 'school-management'),
			'paystack'      => esc_html__('Paystack', 'school-management'),
			'sslcommerz'    => esc_html__('SSLCommerz', 'school-management'),
			'amberpay'      => esc_html__('AmberPay', 'school-management'),
		);
	}

	public static function get_payment_method_text( $key ) {
		$all_payment_methods = array(
			'cash'          => esc_html__( 'Cash', 'school-management' ),
			'check'        => esc_html__( 'Cheque', 'school-management' ),
			'card'          => esc_html__( 'Card', 'school-management' ),
			'bank-transfer' => esc_html__( 'Bank Transfer', 'school-management' ),
			'upi-transfer'  => esc_html__( 'Upi Transfer', 'school-management' ),
			'demand-draft'  => esc_html__( 'Demand Draft', 'school-management' ),
			'razorpay'      => esc_html__( 'Razorpay', 'school-management' ),
			'stripe'        => esc_html__( 'Stripe', 'school-management' ),
			'payu'          => esc_html__( 'PayU', 'school-management' ),
			'paypal'        => esc_html__( 'PayPal', 'school-management' ),
			'pesapal'       => esc_html__( 'Pesapal', 'school-management' ),
			'sslcommerz'    => esc_html__( 'SSLCOMMERZ', 'school-management' ),
			'paystack'      => esc_html__( 'Paystack', 'school-management' ),
			'paytm'         => esc_html__( 'Paytm', 'school-management' ),
			'amberpay'      => esc_html__( 'Amberpay', 'school-management' ),
		);

		if ( array_key_exists( $key, $all_payment_methods ) ) {
			return $all_payment_methods[ $key ];
		}

		return '';
	}

	public static function get_receipt_number_text( $receipt_number ) {
		if ( $receipt_number ) {
			return $receipt_number;
		}
		return '-';
	}

	public static function get_transaction_id_text( $transaction_id ) {
		if ( $transaction_id ) {
			return $transaction_id;
		}
		return '-';
	}

	public static function get_total_paid_amount( $invoice_id, $school_id ) {
		global $wpdb;

		$total_paid = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT SUM(amount) FROM ' . WLSM_PAYMENTS . ' WHERE invoice_id = %d AND school_id = %d',
				$invoice_id,
				$school_id
			)
		);

		if ( is_null( $total_paid ) ) {
			return 0;
		}

		return $total_paid;
	}

	public static function get_due_after_payment( $invoice_id, $school_id, $payment_id, $invoice_payable, $student_record_id = 0 ) {
		if ( ! $invoice_id || ! $payment_id || ! $school_id ) {
			return null;
		}

		global $wpdb;

		$invoice_id        = absint( $invoice_id );
		$school_id         = absint( $school_id );
		$payment_id        = absint( $payment_id );
		$student_record_id = absint( $student_record_id );
		$invoice_payable   = (float) $invoice_payable;

		$conditions = array( 'invoice_id = %d', 'school_id = %d', 'ID <= %d' );
		$args       = array( $invoice_id, $school_id, $payment_id );

		if ( $student_record_id ) {
			$conditions[] = 'student_record_id = %d';
			$args[]       = $student_record_id;
		}

		$sql   = 'SELECT SUM(amount) FROM ' . WLSM_PAYMENTS . ' WHERE ' . implode( ' AND ', $conditions );
		$query = $wpdb->prepare( $sql, $args );

		$total_paid_till = $wpdb->get_var( $query );

		if ( is_null( $total_paid_till ) ) {
			return null;
		}

		$due = $invoice_payable - (float) $total_paid_till;

		return WLSM_Config::sanitize_money( $due );
	}

	/**
	 * Get due date as DateTimeImmutable in site timezone, normalized to Y-m-d.
	 *
	 * @param object $invoice
	 * @return DateTimeImmutable|null
	 */
	public static function get_due_date_obj( $invoice ) {
		if ( ! $invoice || empty( $invoice->due_date ) ) {
			return null;
		}
		// WP current_time respects site timezone.
		$today_ts = current_time( 'timestamp' );
		$tz       = wp_timezone(); // WP 5.3+ helper.
		// due_date is stored as Y-m-d. Create with site tz.
		try {
			$due = new DateTimeImmutable( $invoice->due_date, $tz );
		} catch ( Exception $e ) {
			return null;
		}
		// Normalize to midnight.
		return DateTimeImmutable::createFromFormat( 'Y-m-d', $due->format( 'Y-m-d' ), $tz );
	}

	/**
	 * True when today's date equals invoice due date (site timezone).
	 *
	 * @param object $invoice
	 * @return bool
	 */
	public static function is_due_today( $invoice ) {
		$due = self::get_due_date_obj( $invoice );
		if ( ! $due ) {
			return false;
		}
		$tz        = wp_timezone();
		$today_str = gmdate( 'Y-m-d', current_time( 'timestamp', true ) ); // current_time with $gmt = true gives timestamp already adjusted; keep consistent via wp_timezone below.
		$today     = DateTimeImmutable::createFromFormat( 'Y-m-d', $today_str, $tz );
		return $today && ( $today->format( 'Y-m-d' ) === $due->format( 'Y-m-d' ) );
	}

	/**
	 * True when today's date is after due date (site timezone).
	 *
	 * @param object $invoice
	 * @return bool
	 */
	public static function is_past_due( $invoice ) {
		$due = self::get_due_date_obj( $invoice );
		if ( ! $due ) {
			return false;
		}
		$tz        = wp_timezone();
		$today_str = gmdate( 'Y-m-d', current_time( 'timestamp', true ) );
		$today     = DateTimeImmutable::createFromFormat( 'Y-m-d', $today_str, $tz );
		return $today && ( $today > $due );
	}

	/**
	 * Business rule: partial payment allowance.
	 * - On due date: always allow partial.
	 * - After due date: allow partial only if invoice->partial_payment truthy.
	 * - Before due date: follow invoice->partial_payment.
	 *
	 * @param object $invoice
	 * @return bool
	 */
	public static function can_accept_partial( $invoice ) {
		$invoice_allows = isset( $invoice->partial_payment ) ? (bool) $invoice->partial_payment : false;

		if ( self::is_due_today( $invoice ) ) {
			return true;
		}

		if ( self::is_past_due( $invoice ) ) {
			return $invoice_allows;
		}

		// Before due date.
		return $invoice_allows;
	}

	/**
	 * Compute current payable due amount including due_date penalty when today >= due date.
	 * Falls back to $invoice->due if set; otherwise derive from payable and paid.
	 *
	 * @param object $invoice
	 * @return float
	 */
	public static function current_due_amount( $invoice ) {
		$base_due = 0.0;

		if ( isset( $invoice->due ) ) {
			$base_due = (float) $invoice->due;
		} else {
			$payable  = isset( $invoice->payable ) ? (float) $invoice->payable : 0.0;
			$paid     = isset( $invoice->paid ) ? (float) $invoice->paid : 0.0;
			$base_due = max( $payable - $paid, 0.0 );
		}

		$penalty = 0.0;
		if ( self::is_due_today( $invoice ) || self::is_past_due( $invoice ) ) {
			$penalty = isset( $invoice->due_date_amount ) ? (float) $invoice->due_date_amount : 0.0;
		}

		return WLSM_Config::sanitize_money( $base_due + $penalty );
	}
}
