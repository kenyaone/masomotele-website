<?php
defined('ABSPATH') || die();

$page_url = WLSM_M_Staff_Accountant::get_invoices_page_url();

WLSM_Helper::enqueue_datatable_assets();

global $wpdb;

$school_id  = $current_school['id'];
$session_id = $current_session['ID'];

// Get all payment methods
$collect_payment_methods = WLSM_M_Invoice::collect_payment_methods();

// Get payment gateway settings
// Razorpay settings
$settings_razorpay      = WLSM_M_Setting::get_settings_razorpay($school_id);
$school_razorpay_enable = $settings_razorpay['enable'];

// Paytm settings
$settings_paytm      = WLSM_M_Setting::get_settings_paytm($school_id);
$school_paytm_enable = $settings_paytm['enable'];

// Stripe settings
$settings_stripe      = WLSM_M_Setting::get_settings_stripe($school_id);
$school_stripe_enable = $settings_stripe['enable'];

// PayU settings
$settings_payu      = WLSM_M_Setting::get_settings_payu($school_id);
$school_payu_enable = $settings_payu['enable'];

// PayPal settings
$settings_paypal      = WLSM_M_Setting::get_settings_paypal($school_id);
$school_paypal_enable = $settings_paypal['enable'];

// Amberpay settings
$settings_amberpay      = WLSM_M_Setting::get_settings_amberpay($school_id);
$school_amberpay_enable = $settings_amberpay['enable'];

// Pesapal settings
$settings_pesapal      = WLSM_M_Setting::get_settings_pesapal($school_id);
$school_pesapal_enable = $settings_pesapal['enable'];

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

// Get payment statistics
$payment_statistics = WLSM_M_Staff_Accountant::get_payment_method_statistics($school_id, $session_id);

if ( ! current_user_can( 'administrator' ) ) {
	$current_user 	= WLSM_M_Role::can('assigned_class');
	if ( $current_user ) {
		$role 			= $current_user['school']['role'];
	}
	if ( $current_user && $role !== 'admin' ) {
		$classes  = WLSM_M_Staff_Class::fetch_class_by_section_id( $school_id, absint($current_school['section_id']) );	
	}else{
		$classes  = WLSM_M_Staff_Class::fetch_classes( $school_id );	
	}
}else{
	$classes  = WLSM_M_Staff_Class::fetch_classes( $school_id );	
}

// $classes = WLSM_M_Staff_Class::fetch_classes($school_id);
?>

<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-file-invoice"></i>
				<?php esc_html_e('Student Fee Report', 'school-management'); ?>
			</span>
		</div>

		<div class="row mt-3 mb-4">
			<?php
			// Define icons for payment methods - using only standard Font Awesome icons
			$payment_icons = array(
				'cash' => 'fa-money-bill-alt',
				'card' => 'fa-credit-card',
				'check' => 'fa-money-check',
				'demand-draft' => 'fa-file-invoice',
				'bank-transfer' => 'fa-university',
				'upi-transfer' => 'fa-exchange-alt',
				'stripe' => 'fa-credit-card',       // Changed to standard credit card icon
				'payu' => 'fa-rupee-sign',
				'paypal' => 'fa-money-check-alt',    // Changed to standard icon
				'razorpay' => 'fa-money-bill',       // Changed to standard icon
				'paytm' => 'fa-wallet',
				'pesapal' => 'fa-money-check-alt',
				'paystack' => 'fa-layer-group',
				'sslcommerz' => 'fa-shield-alt',
				'amberpay' => 'fa-money-bill-wave',
				'authorize' => 'fa-check-circle',
				'sampat_bank' => 'fa-landmark'
			);

			// Define only standard Bootstrap colors for payment methods
			$icon_colors = array(
				'cash' => 'success',         // Green
				'card' => 'primary',         // Blue
				'check' => 'info',           // Light Blue
				'demand-draft' => 'dark',    // Dark (changed from indigo)
				'bank-transfer' => 'warning', // Yellow
				'upi-transfer' => 'danger',  // Red (changed from pink)
				'stripe' => 'primary',       // Blue (changed from purple)
				'payu' => 'warning',
				'paypal' => 'info',          // Light Blue
				'razorpay' => 'success',     // Green
				'paytm' => 'primary',        // Blue (changed from cyan)
				'pesapal' => 'success',      // Green
				'paystack' => 'info',        // Light Blue
				'sslcommerz' => 'warning',   // Yellow
				'amberpay' => 'warning',     // Yellow
				'authorize' => 'dark',       // Dark
				'sampat_bank' => 'success'   // Green (changed from teal)
			);

			// Total of all payments
			$total_payments = 0;
			$total_count = 0;

			// Get Bootstrap standard colors for fallback
			$bootstrap_colors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
			$index = 0;

			// Display card for each payment method
			foreach ($enabled_payment_methods as $method_key => $method_name) {
				$icon = isset($payment_icons[$method_key]) ? $payment_icons[$method_key] : 'fa-money-bill';

				// Get color class (use fallback if color not defined)
				$color = isset($icon_colors[$method_key]) ? $icon_colors[$method_key] : $bootstrap_colors[$index % count($bootstrap_colors)];
				$text_color_class = "text-$color"; // Use only standard Bootstrap text colors

				$total_paid = isset($payment_statistics[$method_key]) ? $payment_statistics[$method_key]['amount'] : 0;
				$count 		= isset($payment_statistics[$method_key]) ? $payment_statistics[$method_key]['count'] : 0;

				// Add to totals
				$total_payments += $total_paid;
				$total_count += $count;
				$index++;
			?>
				<div class="col-md-4 col-lg-3 mb-3">
					<div class="wlsm-stats-block"
						data-payment-method="<?php echo esc_attr($method_key); ?>"
						data-payment-name="<?php echo esc_attr($method_name); ?>"
						style="border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); cursor: pointer;">
						<i class="fas <?php echo esc_attr($icon); ?> wlsm-stats-icon <?php echo esc_attr($text_color_class); ?>" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
						<div class="wlsm-stats-counter" style="font-size: 1.5rem; font-weight: bold;"><?php echo esc_html(WLSM_Config::get_money_text($total_paid, $school_id)); ?></div>
						<div class="wlsm-stats-label">
							<div class="wlsm-stats-label">
								<?php
								printf(
									wp_kses(
										/* translators: %1$s: payment method, %2$s: session label */
										__('%1$s <br><small class="text-secondary">Session: %2$s</small>', 'school-management'),
										array('small' => array('class' => array()), 'br' => array())
									),
									esc_html($method_name),
									esc_html(WLSM_M_Session::get_label_text($current_session['label']))
								);
								?>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>

			<!-- Total Payments Card -->
			<!-- <div class="col-md-4 col-lg-3 mb-3">
					<div class="wlsm-stats-block bg-primary text-white" style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 20px rgba(0, 0, 0, 0.3)';" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 10px rgba(0, 0, 0, 0.2)';">
						<i class="fas fa-money-bill-wave wlsm-stats-icon" style="font-size: 2.8rem; margin-bottom: 12px; opacity: 0.9;"></i>
						<div class="wlsm-stats-counter" style="font-size: 1.75rem; font-weight: bold;"><?php echo esc_html(WLSM_Config::get_money_text($total_payments, $school_id)); ?></div>
						<div class="wlsm-stats-label" style="font-weight: 500;">
							<?php
							printf(
								wp_kses(
									/* translators: %1$s: payment count, %2$s: session label */
									__('Total Payments <br><small style="color: rgba(255,255,255,0.8) !important">%1$s payments - Session: %2$s</small>', 'school-management'),
									array('small' => array('style' => array()), 'br' => array())
								),
								esc_html($total_count),
								esc_html(WLSM_M_Session::get_label_text($current_session['label']))
							);
							?>
						</div>
					</div>
				</div> -->
		</div>

		<div class="wlsm-table-block">
			<div class="row">
				<div class="col-md-12">
					<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-get-invoices-report-form" class="mb-3">
						<?php
						$nonce_action = 'get-invoices-report';
						?>
						<?php $nonce = wp_create_nonce($nonce_action); ?>
						<input type="hidden" name="<?php echo esc_attr($nonce_action); ?>" value="<?php echo esc_attr($nonce); ?>">
						<input type="hidden" name="action" value="wlsm-get-invoices-report">

						<div class="form-row">
							<div class="form-group col-md-3">
								<label for="wlsm_class" class="wlsm-font-bold">
									<?php esc_html_e('Class', 'school-management'); ?>:
								</label>
								<select name="class_id" class="form-control selectpicker" data-nonce="<?php echo esc_attr(wp_create_nonce('get-class-sections')); ?>" id="wlsm_class" data-live-search="true">
									<option value=""><?php esc_html_e('Select Class', 'school-management'); ?></option>
									<?php foreach ($classes as $class) { ?>
										<option value="<?php echo esc_attr($class->ID); ?>">
											<?php echo esc_html(WLSM_M_Class::get_label_text($class->label)); ?>
										</option>
									<?php } ?>
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="wlsm_section" class="wlsm-font-bold">
									<?php esc_html_e('Section', 'school-management'); ?>:
								</label>
								<select name="section_id" class="form-control selectpicker" id="wlsm_section" data-live-search="true" title="<?php esc_attr_e('All Sections', 'school-management'); ?>" data-all-sections="1">
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="wlsm_status" class="wlsm-font-bold">
									<?php esc_html_e('Status', 'school-management'); ?>:
								</label>
								<select name="status" class="form-control selectpicker" id="wlsm_status" data-live-search="true" title="<?php esc_attr_e('All Status', 'school-management'); ?>">
									<option value="paid"><?php esc_html_e('Paid', 'school-management'); ?></option>
									<option value="unpaid"><?php esc_html_e('UnPaid', 'school-management'); ?></option>
									<option value="partially_paid"><?php esc_html_e('Partially Paid', 'school-management'); ?></option>
								</select>
							</div>

							<div class="form-group col-md-3">
								<label for="wlsm_payment_method" class="wlsm-font-bold">
									<?php esc_html_e('Payment Method', 'school-management'); ?>:
								</label>
								<select name="payment_method" class="form-control selectpicker" id="wlsm_payment_method" data-nonce="<?php echo esc_attr(wp_create_nonce('get-class-fee-total')); ?>">
									<option value=""><?php esc_html_e('Select Payment Method', 'school-management'); ?></option>
									<?php foreach ($enabled_payment_methods as $key => $value) { ?>
										<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-row">
							<div class="col-md-6">
								<ul>
									<li>
										<label for="student_total_pending"><?php esc_html_e('Total Pending:', 'school-management') ?></label>
										<span id="fees_report_total_pending"></span>
									</li>
									<li>
										<label for="student_total_paid"><?php esc_html_e('Total Paid:', 'school-management') ?></label>
										<span id="fees_report_total_paid"></span>
									</li>
								</ul>
							</div>
						</div>

						<div class="form-row">
							<div class="col-md-12">
								<button type="button" class="btn btn-sm btn-outline-primary wlsm-get-invoice_total" id="wlsm-get-invoices-report-btn">
									<i class="fas fa-file-invoice"></i>&nbsp;
									<?php esc_html_e('Fetch Invoices', 'school-management'); ?>
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
			<table class="table table-hover table-bordered" id="wlsm-staff-invoices-report-table">
				<thead>
					<tr class="text-white bg-primary">
						<th scope="col"><?php esc_html_e('Student Name', 'school-management'); ?></th>
						<th scope="col"><?php esc_html_e('Enrollment Number', 'school-management'); ?></th>
						<th scope="col"><?php esc_html_e('Father\'s Name', 'school-management'); ?></th>
						<th scope="col"><?php esc_html_e('Admission Number', 'school-management'); ?></th>
						<th scope="col"><?php esc_html_e('Payable', 'school-management'); ?></th>
						<th scope="col"><?php esc_html_e('Paid', 'school-management'); ?></th>
						<th scope="col"><?php esc_html_e('Due', 'school-management'); ?></th>
						<th scope="col"><?php esc_html_e('Phone', 'school-management'); ?></th>
						<th scope="col"><?php esc_html_e('Class', 'school-management'); ?></th>
						<th scope="col"><?php esc_html_e('Section', 'school-management'); ?></th>
						<!-- <th scope="col" class="text-nowrap"><?php esc_html_e('Action', 'school-management'); ?></th> -->
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>
