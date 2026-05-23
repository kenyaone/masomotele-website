<?php
defined('ABSPATH') || die();

$payment_amount = '';

// Initialize amounts and dates via helpers (centralized logic).
$due_date_amount = 0;
$due             = 0;
$is_past_due     = false;
$can_partial     = true;

if ( $invoice ) {
	// Compute current due including penalty when today >= due date.
	$due = WLSM_M_Invoice::current_due_amount( $invoice );

	// Determine flags.
	$is_past_due = WLSM_M_Invoice::is_past_due( $invoice );
	$can_partial = WLSM_M_Invoice::can_accept_partial( $invoice );

	// Extract penalty value for placeholder computation below.
	$base_due = isset( $invoice->due ) ? (float) $invoice->due : 0.0;
	$computed_due = (float) $due;
	$due_date_amount = max( $computed_due - $base_due, 0.0 );
}

// When partial is not allowed, default amount to full current due.
if ( $invoice && ! $can_partial ) {
	$payment_amount = $due;
}

// Get all payment methods
$collect_payment_methods = WLSM_M_Invoice::collect_payment_methods();

// Get payment gateway settings
// Razorpay settings
$settings_razorpay      = WLSM_M_Setting::get_settings_razorpay($school_id);
$school_razorpay_enable = $settings_razorpay['enable'];

// Paytm settings
$settings_paytm      	= WLSM_M_Setting::get_settings_paytm($school_id);
$school_paytm_enable 	= $settings_paytm['enable'];

// Stripe settings
$settings_stripe      	= WLSM_M_Setting::get_settings_stripe($school_id);
$school_stripe_enable 	= $settings_stripe['enable'];

// PayU settings
$settings_payu      = WLSM_M_Setting::get_settings_payu($school_id);
$school_payu_enable = $settings_payu['enable'];

// PayPal settings
$settings_paypal      	= WLSM_M_Setting::get_settings_paypal($school_id);
$school_paypal_enable 	= $settings_paypal['enable'];

// Amberpay settings
$settings_amberpay      = WLSM_M_Setting::get_settings_amberpay($school_id);
$school_amberpay_enable = $settings_amberpay['enable'];

// Pesapal settings
$settings_pesapal      	= WLSM_M_Setting::get_settings_pesapal($school_id);
$school_pesapal_enable 	= $settings_pesapal['enable'];

// Sslcommerz settings
$settings_sslcommerz      = WLSM_M_Setting::get_settings_sslcommerz($school_id);
$school_sslcommerz_enable = $settings_sslcommerz['enable'];

// Paystack settings
$settings_paystack      = WLSM_M_Setting::get_settings_paystack($school_id);
$school_paystack_enable = $settings_paystack['enable'];

// Authorize settings
$settings_authorize      = WLSM_M_Setting::get_settings_authorize($school_id);
$school_authorize_enable = $settings_authorize['enable'];

// Bank transfer settings
$settings_bank_transfer      = WLSM_M_Setting::get_settings_bank_transfer($school_id);
$school_bank_transfer_enable = $settings_bank_transfer['enable'];

// UPI transfer settings
$settings_upi_transfer      = WLSM_M_Setting::get_settings_upi_transfer($school_id);
$school_upi_transfer_enable = $settings_upi_transfer['enable'];

// Cash payment settings
$settings_cash = WLSM_M_Setting::get_settings_cash($school_id);
$school_cash_enable = $settings_cash['enable'];

// Card payment settings
$settings_card = WLSM_M_Setting::get_settings_card($school_id);
$school_card_enable = $settings_card['enable'];

// Check payment settings
$settings_check = WLSM_M_Setting::get_settings_check($school_id);
$school_check_enable = $settings_check['enable'];

// Demand Draft payment settings
$settings_demand_draft = WLSM_M_Setting::get_settings_demand_draft($school_id);
$school_demand_draft_enable = $settings_demand_draft['enable'];

// Create an array of enabled payment methods
$enabled_payment_methods = array();

// Add other payment gateways if they are enabled
if ($school_bank_transfer_enable && isset($collect_payment_methods['bank-transfer'])) {
	$enabled_payment_methods['bank-transfer'] = $collect_payment_methods['bank-transfer'];
}

if ($school_upi_transfer_enable && isset($collect_payment_methods['upi-transfer'])) {
	$enabled_payment_methods['upi-transfer'] = $collect_payment_methods['upi-transfer'];
}

if ($school_stripe_enable && isset($collect_payment_methods['stripe'])) {
	$enabled_payment_methods['stripe'] = $collect_payment_methods['stripe'];
}

if ($school_payu_enable && isset($collect_payment_methods['payu'])) {
	$enabled_payment_methods['payu'] = $collect_payment_methods['payu'];
}

if ($school_paypal_enable && isset($collect_payment_methods['paypal'])) {
	$enabled_payment_methods['paypal'] = $collect_payment_methods['paypal'];
}

if ($school_razorpay_enable && isset($collect_payment_methods['razorpay'])) {
	$enabled_payment_methods['razorpay'] = $collect_payment_methods['razorpay'];
}

if ($school_paytm_enable && isset($collect_payment_methods['paytm'])) {
	$enabled_payment_methods['paytm'] = $collect_payment_methods['paytm'];
}

if ($school_pesapal_enable && isset($collect_payment_methods['pesapal'])) {
	$enabled_payment_methods['pesapal'] = $collect_payment_methods['pesapal'];
}

if ($school_paystack_enable && isset($collect_payment_methods['paystack'])) {
	$enabled_payment_methods['paystack'] = $collect_payment_methods['paystack'];
}

if ($school_sslcommerz_enable && isset($collect_payment_methods['sslcommerz'])) {
	$enabled_payment_methods['sslcommerz'] = $collect_payment_methods['sslcommerz'];
}

if ($school_amberpay_enable && isset($collect_payment_methods['amberpay'])) {
	$enabled_payment_methods['amberpay'] = $collect_payment_methods['amberpay'];
}

if ($school_authorize_enable && isset($collect_payment_methods['authorize'])) {
	$enabled_payment_methods['authorize'] = $collect_payment_methods['authorize'];
}
// Add cash payment method if enabled
if ($school_cash_enable && isset($collect_payment_methods['cash'])) {
    $enabled_payment_methods['cash'] = $collect_payment_methods['cash'];
}

// Add card payment method if enabled
if ($school_card_enable && isset($collect_payment_methods['card'])) {
    $enabled_payment_methods['card'] = $collect_payment_methods['card'];
}

// Add check payment method if enabled
if ($school_check_enable && isset($collect_payment_methods['check'])) {
    $enabled_payment_methods['check'] = $collect_payment_methods['check'];
}

// Add demand-draft payment method if enabled
if ($school_demand_draft_enable && isset($collect_payment_methods['demand-draft'])) {
    $enabled_payment_methods['demand-draft'] = $collect_payment_methods['demand-draft'];
}
?>

<!-- Collect Payment -->
<div class="wlsm-form-section wlsm-invoice-payments" id="wlsm-collect-payment">
	<?php if (! $invoice) { ?>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<input class="form-check-input mt-1" type="checkbox" name="collect_invoice_payment" id="wlsm_collect_invoice_payment" value="1">
					<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_collect_invoice_payment">
						<?php esc_html_e('Collect Payment?', 'school-management'); ?>
					</label>
				</div>
				<hr>
			</div>
		</div>
		<div class="wlsm-collect-invoice-payment">
		<?php } ?>
		<div class="row">
			<div class="col-md-12">
				<div class="wlsm-form-sub-heading wlsm-font-bold">
					<?php esc_html_e('Add New Payment', 'school-management'); ?>
				</div>
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-6">
				<div class="form-group">
					<label for="wlsm_payment_amount" class="wlsm-font-bold">
						<?php esc_html_e('Amount', 'school-management'); ?>:
					</label>
					<input
						<?php
						// Lock amount field when partial is not allowed per helper.
						if ( $invoice && ! $can_partial ) {
							echo 'readonly';
						}
						?>
						type="number" step="any" min="0" name="payment_amount" class="form-control" id="wlsm_payment_amount" placeholder="<?php esc_attr_e('Enter amount', 'school-management'); ?>" value="<?php echo esc_attr( WLSM_Config::sanitize_money( $payment_amount ) ); ?>">
						<?php
						// Show note only when past due and partial is not allowed.
						if ( $invoice && $is_past_due && ! $can_partial ) {
						?>
							<p>
								<span class="text-danger"><strong><?php esc_html_e( 'Note: After due date partial payment is disabled.', 'school-management' ); ?></strong></span>
							</p>
						<?php } ?>
				</div>

				<div class="form-group" id="wlsm_group_bank_name" style="display:none">
					<label for="wlsm_bank_name" class="wlsm-font-bold">
						<?php esc_html_e('Bank Name', 'school-management'); ?>:
					</label>
					<input type="text" name="bank_name" class="form-control" id="wlsm_bank_name" placeholder="<?php esc_attr_e('Enter bank name', 'school-management'); ?>">
				</div>

				<div class="form-group" id="wlsm_group_cheque_date" style="display:none">
					<label for="wlsm_cheque_date" class="wlsm-font-bold">
						<?php esc_html_e('Cheque Date', 'school-management'); ?>:
					</label>
					<input type="text" name="cheque_date" class="form-control" id="wlsm_cheque_date" placeholder="<?php esc_attr_e('Enter cheque date', 'school-management'); ?>">
				</div>

				<div class="form-group">
					<label for="wlsm_payment_method" class="wlsm-font-bold">
						<?php esc_html_e('Payment Method', 'school-management'); ?>:
					</label>
					<select name="payment_method" class="form-control selectpicker" id="wlsm_payment_method" data-nonce="<?php echo esc_attr(wp_create_nonce('get-class-fee-total')); ?>">
						<option value=""><?php esc_html_e('Select Payment Method', 'school-management'); ?></option>
						<?php foreach ($enabled_payment_methods as $key => $value) { ?>
							<option value="<?php echo esc_attr($key); ?>">
								<?php echo esc_html($value); ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="form-group">
					<label for="wlsm_payment_date" class="wlsm-font-bold">
						<?php esc_html_e('Payment Date', 'school-management'); ?>:
					</label>
					<input type="text" name="payment_date" class="form-control" id="wlsm_payment_date-" placeholder="<?php esc_attr_e('Enter payment date', 'school-management'); ?>" value="<?php echo esc_attr(WLSM_Config::get_date_text(current_time('Y-m-d H:i:s'))); ?>" readonly>
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label for="wlsm_transaction_id" class="wlsm-font-bold">
						<?php esc_html_e('Transaction ID', 'school-management'); ?>:
					</label>
					<input type="text" name="transaction_id" class="form-control" id="wlsm_transaction_id" placeholder="<?php esc_attr_e('Enter transaction ID', 'school-management'); ?>">
				</div>

				<div class="form-group" id="wlsm_group_cheque_number" style="display:none">
					<label for="wlsm_cheque_number" class="wlsm-font-bold">
						<?php esc_html_e('Cheque Number', 'school-management'); ?>:
					</label>
					<input type="text" name="cheque_number" class="form-control" id="wlsm_cheque_number" placeholder="<?php esc_attr_e('Enter cheque number', 'school-management'); ?>">
				</div>

				<div class="form-group">
					<label for="wlsm_authorized_by" class="wlsm-font-bold">
						<?php esc_html_e('Authorized By', 'school-management'); ?>:
					</label>
					<input type="text" name="authorized_by" class="form-control" id="wlsm_authorized_by" placeholder="<?php esc_attr_e('Enter authorized by', 'school-management'); ?>">
				</div>

				<div class="form-group">
					<label for="wlsm_payment_note" class="wlsm-font-bold">
						<?php esc_html_e('Additional Note', 'school-management'); ?>:
					</label>
					<textarea name="payment_note" class="form-control" id="wlsm_payment_note" cols="30" rows="3" placeholder="<?php esc_attr_e('Enter additional note', 'school-management'); ?>"></textarea>
				</div>

			</div>
		</div>
		<?php if (! $invoice) { ?>
		</div>
	<?php } ?>
</div>
