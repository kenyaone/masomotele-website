<?php
defined( 'ABSPATH' ) || die();

// Razorpay settings.
$settings_razorpay      = WLSM_M_Setting::get_settings_razorpay( $school_id );
$school_razorpay_enable = $settings_razorpay['enable'];
$school_razorpay_key    = $settings_razorpay['razorpay_key'];
$school_razorpay_secret = $settings_razorpay['razorpay_secret'];

// Paytm settings.
$settings_paytm                = WLSM_M_Setting::get_settings_paytm( $school_id );
$school_paytm_enable           = $settings_paytm['enable'];
$school_paytm_merchant_id      = $settings_paytm['merchant_id'];
$school_paytm_merchant_key     = $settings_paytm['merchant_key'];
$school_paytm_industry_type_id = $settings_paytm['industry_type_id'];
$school_paytm_website          = $settings_paytm['website'];
$school_paytm_mode             = $settings_paytm['mode'];

// Stripe settings.
$settings_stripe               = WLSM_M_Setting::get_settings_stripe( $school_id );
$school_stripe_enable          = $settings_stripe['enable'];
$school_stripe_publishable_key = $settings_stripe['publishable_key'];
$school_stripe_secret_key      = $settings_stripe['secret_key'];

// PayU settings.
$settings_payu          = WLSM_M_Setting::get_settings_payu( $school_id );
$school_payu_enable     = $settings_payu['enable'];
$school_payu_key        = $settings_payu['merchant_key'];
$school_payu_salt       = $settings_payu['merchant_salt'];
$school_payu_mode       = $settings_payu['mode'];

// PayPal settings.
$settings_paypal              = WLSM_M_Setting::get_settings_paypal( $school_id );
$school_paypal_enable         = $settings_paypal['enable'];
$school_paypal_business_email = $settings_paypal['business_email'];
$school_paypal_mode           = $settings_paypal['mode'];
$school_paypal_notify_url     = $settings_paypal['notify_url'];

// Amberpay settings.
$settings_amberpay             = WLSM_M_Setting::get_settings_amberpay( $school_id );
$school_amberpay_enable        = $settings_amberpay['enable'];
$school_amberpay_client_id     = $settings_amberpay['client_id'];
$school_amberpay_signature_url = $settings_amberpay['signature_url'];
$school_amberpay_payment_url   = $settings_amberpay['payment_url'];
$school_amberpay_api_key       = $settings_amberpay['api_key'];
$school_amberpay_mode          = $settings_amberpay['mode'];

// Pesapal settings.
$settings_pesapal               = WLSM_M_Setting::get_settings_pesapal( $school_id );
$school_pesapal_enable          = $settings_pesapal['enable'];
$school_pesapal_consumer_key    = $settings_pesapal['consumer_key'];
$school_pesapal_consumer_secret = $settings_pesapal['consumer_secret'];
$school_pesapal_mode            = $settings_pesapal['mode'];
$school_pesapal_notify_url      = $settings_pesapal['notify_url'];

// sslcommerzal settings.
$settings_sslcommerz            = WLSM_M_Setting::get_settings_sslcommerz( $school_id );
$school_sslcommerz_enable       = $settings_sslcommerz['enable'];
$school_sslcommerz_store_id     = $settings_sslcommerz['store_id'];
$school_sslcommerz_store_passwd = $settings_sslcommerz['store_passwd'];
$school_sslcommerz_mode         = $settings_sslcommerz['mode'];
$school_sslcommerz_notify_url   = $settings_sslcommerz['notify_url'];

// Paystack settings.
$settings_paystack          = WLSM_M_Setting::get_settings_paystack( $school_id );
$school_paystack_enable     = $settings_paystack['enable'];
$school_paystack_public_key = $settings_paystack['paystack_public_key'];
$school_paystack_secret_key = $settings_paystack['paystack_secret_key'];


// authorize settings.
$settings_authorize          = WLSM_M_Setting::get_settings_authorize( $school_id );
$school_authorize_enable     = $settings_authorize['enable'];
$school_authorize_public_key = $settings_authorize['authorize_public_key'];
$school_authorize_secret_key = $settings_authorize['authorize_secret_key'];


// Bank transfer settings.
$settings_bank_transfer       = WLSM_M_Setting::get_settings_bank_transfer( $school_id );
$school_bank_transfer_enable  = $settings_bank_transfer['enable'];
$school_bank_transfer_branch  = $settings_bank_transfer['branch'];
$school_bank_transfer_account = $settings_bank_transfer['account'];
$school_bank_transfer_name    = $settings_bank_transfer['name'];
$school_bank_transfer_message = $settings_bank_transfer['message'];

// Upi transfer settings.
$settings_upi_transfer       = WLSM_M_Setting::get_settings_upi_transfer( $school_id );
$school_upi_transfer_enable  = $settings_upi_transfer['enable'];
$school_upi_transfer_qr  = $settings_upi_transfer['qr'];
$school_upi_transfer_id = $settings_upi_transfer['id'];
$school_upi_transfer_name    = $settings_upi_transfer['name'];
$school_upi_transfer_message = $settings_upi_transfer['message'];

// Cash payment settings
$settings_cash = WLSM_M_Setting::get_settings_cash($school_id);
$school_cash_enable = $settings_cash['enable'];
$school_cash_instructions = $settings_cash['instructions'];

// Check/Cheque payment settings
$settings_check = WLSM_M_Setting::get_settings_check($school_id);
$school_check_enable = $settings_check['enable'];
$school_check_instructions = $settings_check['instructions'];

// Demand draft payment settings
$settings_demand_draft = WLSM_M_Setting::get_settings_demand_draft($school_id);
$school_demand_draft_enable = $settings_demand_draft['enable'];
$school_demand_draft_instructions = $settings_demand_draft['instructions'];

// Card payment settings
$settings_card = WLSM_M_Setting::get_settings_card($school_id);
$school_card_enable = $settings_card['enable'];
$school_card_instructions = $settings_card['instructions'];
?>

<div class="tab-pane fade" id="wlsm-school-payment-method" role="tabpanel" aria-labelledby="wlsm-school-payment-method-tab">

	<div class="row">
		<div class="col-md-12">
			<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" id="wlsm-save-school-payment-method-settings-form">
				<?php
				$nonce_action = 'save-school-payment-method-settings';
				$nonce        = wp_create_nonce( $nonce_action );
				?>
				<input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">

				<input type="hidden" name="action" value="wlsm-save-school-payment-method-settings">

				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_stripe_fields" aria-expanded="true" aria-controls="wlsm_stripe_fields">
					<?php esc_html_e( 'Stripe Payment Gateway ( Global ) ', 'school-management' ); ?>
					<u><a class="text-white" href="https://stripe.com/global"><?php esc_html_e( 'Available countries', 'school-management' ); ?></a></u>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_stripe_fields">

					<div class="wlsm_payment_method wlsm_stripe">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_stripe_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'Stripe Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_stripe_enable" class="wlsm-font-bold">
										<input <?php checked( $school_stripe_enable, true, true ); ?> type="checkbox" name="stripe_enable" id="wlsm_stripe_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
									<?php if ( ! WLSM_Payment::currency_supports_stripe( $currency ) ) { ?>
									<br>
									<small class="text-secondary">
										<?php
										printf(
											/* translators: %s: currency code */
											__( 'Stripe does not support currency %s.', 'school-management' ),
											esc_html( $currency )
										);
										?>
									</small>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_stripe">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_stripe_publishable_key" class="wlsm-font-bold"><?php esc_html_e( 'Stripe Publishable Key', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="stripe_publishable_key" type="text" id="wlsm_stripe_publishable_key" value="<?php echo esc_attr( $school_stripe_publishable_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Stripe Publishable Key', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_stripe">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_stripe_secret_key" class="wlsm-font-bold"><?php esc_html_e( 'Stripe Secret Key', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="stripe_secret_key" type="text" id="wlsm_stripe_secret_key" value="<?php echo esc_attr( $school_stripe_secret_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Stripe Secret Key', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

				</div>


				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_payu_fields" aria-expanded="false" aria-controls="wlsm_payu_fields">
					<?php esc_html_e( 'PayU Payment Gateway ( India )', 'school-management' ); ?>
					<u><a class="text-white" href="https://www.payu.in/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more', 'school-management' ); ?></a></u>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_payu_fields">

					<div class="wlsm_payment_method wlsm_payu">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_payu_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'PayU Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_payu_enable" class="wlsm-font-bold">
										<input <?php checked( $school_payu_enable, true, true ); ?> type="checkbox" name="payu_enable" id="wlsm_payu_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_payu">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_payu_merchant_key" class="wlsm-font-bold"><?php esc_html_e( 'PayU Merchant Key', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="payu_merchant_key" type="text" id="wlsm_payu_merchant_key" value="<?php echo esc_attr( $school_payu_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'PayU Merchant Key', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_payu">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_payu_merchant_salt" class="wlsm-font-bold"><?php esc_html_e( 'PayU Merchant Salt', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="payu_merchant_salt" type="text" id="wlsm_payu_merchant_salt" value="<?php echo esc_attr( $school_payu_salt ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'PayU Merchant Salt', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_payu">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_payu_mode" class="wlsm-font-bold"><?php esc_html_e( 'PayU Mode', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<select name="payu_mode" id="wlsm_payu_mode" class="form-control">
										<option value="test" <?php selected( 'test', $school_payu_mode ); ?>><?php esc_html_e( 'Test', 'school-management' ); ?></option>
										<option value="live" <?php selected( 'live', $school_payu_mode ); ?>><?php esc_html_e( 'Live', 'school-management' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>

				</div>

				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_paypal_fields" aria-expanded="true" aria-controls="wlsm_paypal_fields">
					<?php esc_html_e( 'PayPal Payment Gateway ( Global )', 'school-management' ); ?>
					<u><a class="text-white" href="https://www.paypal.com/in/webapps/mpp/country-worldwide"><?php esc_html_e( 'Available countries', 'school-management' ); ?></a></u>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_paypal_fields">

					<div class="wlsm_payment_method wlsm_paypal">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paypal_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'PayPal Payment ', 'school-management' ); ?>:

								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_paypal_enable" class="wlsm-font-bold">
										<input <?php checked( $school_paypal_enable, true, true ); ?> type="checkbox" name="paypal_enable" id="wlsm_paypal_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
									<?php if ( ! WLSM_Payment::currency_supports_paypal( $currency ) ) { ?>
									<br>
									<small class="text-secondary">
										<?php
										printf(
											/* translators: %s: currency code */
											__( 'PayPal does not support currency %s.', 'school-management' ),
											esc_html( $currency )
										);
										?>
									</small>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paypal">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paypal_business_email" class="wlsm-font-bold"><?php esc_html_e( 'PayPal Business Email', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="paypal_business_email" type="email" class="form-control" id="wlsm_paypal_business_email" value="<?php echo esc_attr( $school_paypal_business_email ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'PayPal Business Email', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paypal">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paypal_mode" class="wlsm-font-bold"><?php esc_html_e( 'Payment Mode', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<select name="paypal_mode" class="form-control" id="wlsm_paypal_mode">
										<option <?php selected( $school_paypal_mode, 'sandbox', true ); ?> value="sandbox"><?php esc_html_e( 'Sandbox', 'school-management' ); ?></option>
										<option <?php selected( $school_paypal_mode, 'live', true ); ?> value="live"><?php esc_html_e( 'Live', 'school-management' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paypal">
						<div class="row">
							<div class="col-md-12">
								<label class="wlsm-font-bold"><?php esc_html_e( 'PayPal Notify URL', 'school-management' ); ?>: </label><br>
								<span class="text-primary"><?php echo esc_url( $school_paypal_notify_url ); ?></span><br>
								<small class="font-weight-bold">
									( <?php esc_html_e( 'To save transactions, you need to enable PayPal IPN (Instant Payment Notification) in your PayPal Business Account and use this notify URL', 'school-management' ); ?>
									)
								</small>
								<small>
									<ol>
										<li><?php esc_html_e( 'Log into your PayPal account.', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Go to Profile then "My Selling Tools".', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Look for an option labelled "Instant Payment Notification". Click on the update button for that option.', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Click "Choose IPN Settings".', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Enter the URL given above and hit "Save".', 'school-management' ); ?></li>
									</ol>
								</small>
							</div>
						</div>
					</div>

				</div>

				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_razorpay_fields" aria-expanded="true" aria-controls="wlsm_razorpay_fields">
					<?php esc_html_e( 'Razorpay Payment Gateway (International)', 'school-management' ); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_razorpay_fields">

					<div class="wlsm_payment_method wlsm_razorpay">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_razorpay_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'Razorpay Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_razorpay_enable" class="wlsm-font-bold">
										<input <?php checked( $school_razorpay_enable, true, true ); ?> type="checkbox" name="razorpay_enable" id="wlsm_razorpay_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
									<?php if ( ! WLSM_Payment::currency_supports_razorpay( $currency ) ) { ?>
									<br>
									<small class="text-secondary">
										<?php
										printf(
											/* translators: %s: currency code */
											__( 'Razorpay does not support currency %s.', 'school-management' ),
											esc_html( $currency )
										);
										?>
									</small>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_razorpay">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_razorpay_key" class="wlsm-font-bold"><?php esc_html_e( 'Razorpay Key', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="razorpay_key" type="text" id="wlsm_razorpay_key" value="<?php echo esc_attr( $school_razorpay_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Razorpay Key', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_razorpay">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_razorpay_secret" class="wlsm-font-bold"><?php esc_html_e( 'Razorpay Secret', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="razorpay_secret" type="text" id="wlsm_razorpay_secret" value="<?php echo esc_attr( $school_razorpay_secret ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Razorpay Secret', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

				</div>

				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_paytm_fields" aria-expanded="true" aria-controls="wlsm_paytm_fields">
					<?php esc_html_e( 'Paytm Payment Gateway (India)', 'school-management' ); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_paytm_fields">

					<div class="wlsm_payment_method wlsm_paytm">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paytm_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'Paytm Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_paytm_enable" class="wlsm-font-bold">
										<input <?php checked( $school_paytm_enable, true, true ); ?> type="checkbox" name="paytm_enable" id="wlsm_paytm_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
									<?php if ( ! WLSM_Payment::currency_supports_paytm( $currency ) ) { ?>
									<br>
									<small class="text-secondary">
										<?php
										printf(
											/* translators: %s: currency code */
											__( 'Paytm does not support currency %s.', 'school-management' ),
											esc_html( $currency )
										);
										?>
									</small>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paytm">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paytm_merchant_id" class="wlsm-font-bold"><?php esc_html_e( 'Paytm Merchant ID', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="paytm_merchant_id" type="text" id="wlsm_paytm_merchant_id" value="<?php echo esc_attr( $school_paytm_merchant_id ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Paytm Merchant ID', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paytm">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paytm_merchant_key" class="wlsm-font-bold"><?php esc_html_e( 'Paytm Merchant Key', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="paytm_merchant_key" type="text" id="wlsm_paytm_merchant_key" value="<?php echo esc_attr( $school_paytm_merchant_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Paytm Merchant Key', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paytm">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paytm_industry_type_id" class="wlsm-font-bold"><?php esc_html_e( 'Paytm Industry Type ID', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="paytm_industry_type_id" type="text" id="wlsm_paytm_industry_type_id" value="<?php echo esc_attr( $school_paytm_industry_type_id ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'For staging environment: "Retail"', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paytm">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paytm_website" class="wlsm-font-bold"><?php esc_html_e( 'Paytm Website', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="paytm_website" type="text" id="wlsm_paytm_website" value="<?php echo esc_attr( $school_paytm_website ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'For staging environment: "WEBSTAGING"', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paytm">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paytm_mode" class="wlsm-font-bold"><?php esc_html_e( 'Payment Mode', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<select name="paytm_mode" class="form-control" id="wlsm_paytm_mode">
										<option <?php selected( $school_paytm_mode, 'staging', true ); ?> value="staging"><?php esc_html_e( 'Staging', 'school-management' ); ?></option>
										<option <?php selected( $school_paytm_mode, 'production', true ); ?> value="production"><?php esc_html_e( 'Production', 'school-management' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>

				</div>

				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_pesapal_fields" aria-expanded="true" aria-controls="wlsm_pesapal_fields">
					<?php esc_html_e( 'Pesapal Payment Gateway ( Kenya,
Malawi,
Rwanda,
Tanzania,
Uganda,
Zambia,
Zimbabwe)', 'school-management' ); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_pesapal_fields">

					<div class="wlsm_payment_method wlsm_pesapal">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_pesapal_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'Pesapal Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_pesapal_enable" class="wlsm-font-bold">
										<input <?php checked( $school_pesapal_enable, true, true ); ?> type="checkbox" name="pesapal_enable" id="wlsm_pesapal_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
									<?php if ( ! WLSM_Payment::currency_supports_pesapal( $currency ) ) { ?>
									<br>
									<small class="text-secondary">
										<?php
										printf(
											/* translators: %s: currency code */
											__( 'Pesapal does not support currency %s.', 'school-management' ),
											esc_html( $currency )
										);
										?>
									</small>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_pesapal">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_pesapal_consumer_key" class="wlsm-font-bold"><?php esc_html_e( 'Pesapal Consumer Key', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="pesapal_consumer_key" type="text" class="form-control" id="wlsm_pesapal_consumer_key" value="<?php echo esc_attr( $school_pesapal_consumer_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Pesapal Consumer Key', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_pesapal">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_pesapal_consumer_secret" class="wlsm-font-bold"><?php esc_html_e( 'Pesapal Consumer Secret', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="pesapal_consumer_secret" type="text" class="form-control" id="wlsm_pesapal_consumer_secret" value="<?php echo esc_attr( $school_pesapal_consumer_secret ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Pesapal Consumer Secret', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_pesapal">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_pesapal_mode" class="wlsm-font-bold"><?php esc_html_e( 'Payment Mode', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<select name="pesapal_mode" class="form-control" id="wlsm_pesapal_mode">
										<option <?php selected( $school_pesapal_mode, 'sandbox', true ); ?> value="sandbox"><?php esc_html_e( 'Sandbox', 'school-management' ); ?></option>
										<option <?php selected( $school_pesapal_mode, 'live', true ); ?> value="live"><?php esc_html_e( 'Live', 'school-management' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_pesapal">
						<div class="row">
							<div class="col-md-12">
								<label class="wlsm-font-bold"><?php esc_html_e( 'Pesapal Notify URL', 'school-management' ); ?>: </label><br>
								<span class="text-primary"><?php echo esc_url( $school_pesapal_notify_url ); ?></span><br>
								<small class="font-weight-bold">
									( <?php esc_html_e( 'To save transactions, you need to enable Pesapal IPN (Instant Payment Notification) in your Pesapal Account and use this notify URL', 'school-management' ); ?>
									)
								</small>
								<small>
									<ol>
										<li><?php esc_html_e( 'Log into your Pesapal account.', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Go to "My Account" then "Account Settings".', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Look for an option labelled "IPN Settings". Click on the update button for that option.', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Click "Choose IPN Settings".', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Enter the "Website Domain" and URL given above in "IPN Listener Url" and hit "Save URL".', 'school-management' ); ?></li>
									</ol>
								</small>
							</div>
						</div>
					</div>

				</div>

				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_paystack_fields" aria-expanded="true" aria-controls="wlsm_paystack_fields">
					<?php esc_html_e( 'Paystack Payment Gateway ( Nigeria )', 'school-management' ); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_paystack_fields">

					<div class="wlsm_payment_method wlsm_paystack">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paystack_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'Paystack Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_paystack_enable" class="wlsm-font-bold">
										<input <?php checked( $school_paystack_enable, true, true ); ?> type="checkbox" name="paystack_enable" id="wlsm_paystack_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
									<?php if ( ! WLSM_Payment::currency_supports_paystack( $currency ) ) { ?>
									<br>
									<small class="text-secondary">
										<?php
										printf(
											/* translators: %s: currency code */
											__( 'Paystack does not support currency %s.', 'school-management' ),
											esc_html( $currency )
										);
										?>
									</small>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paystack">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paystack_public_key" class="wlsm-font-bold"><?php esc_html_e( 'Public Key', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="paystack_public_key" type="text" id="wlsm_paystack_public_key" value="<?php echo esc_attr( $school_paystack_public_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Public Key', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_paystack">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_paystack_secret_key" class="wlsm-font-bold"><?php esc_html_e( 'Secret Key', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="paystack_secret_key" type="text" id="wlsm_paystack_secret_key" value="<?php echo esc_attr( $school_paystack_secret_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Secret Secret', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>
				</div>



				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_sslcommerz_fields" aria-expanded="true" aria-controls="wlsm_sslcommerz_fields">
					<?php esc_html_e( 'SSLCommerz Payment Gateway ( International )', 'school-management' ); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_sslcommerz_fields">

					<div class="wlsm_payment_method wlsm_sslcommerz">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_sslcommerz_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'SSLCommerz Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_sslcommerz_enable" class="wlsm-font-bold">
										<input <?php checked( $school_sslcommerz_enable, true, true ); ?> type="checkbox" name="sslcommerz_enable" id="wlsm_sslcommerz_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
									<?php if ( ! WLSM_Payment::currency_supports_sslcommerz( $currency ) ) { ?>
									<br>
									<small class="text-secondary">
										<?php
										printf(
											/* translators: %s: currency code */
											__( 'SSLCommerz does not support currency %s.', 'school-management' ),
											esc_html( $currency )
										);
										?>
									</small>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_sslcommerz">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_sslcommerz_store_id" class="wlsm-font-bold"><?php esc_html_e( 'SSLCommerz Store ID', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="sslcommerz_store_id" type="text" class="form-control" id="wlsm_sslcommerz_store_id" value="<?php echo esc_attr( $school_sslcommerz_store_id ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'SSLCommerz Store ID', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_sslcommerz">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_sslcommerz_store_passwd" class="wlsm-font-bold"><?php esc_html_e( 'SSLCommerz Store Passwd', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="sslcommerz_store_passwd" type="text" class="form-control" id="wlsm_sslcommerz_store_passwd" value="<?php echo esc_attr( $school_sslcommerz_store_passwd ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'SSLCommerz Store Passwd', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_sslcommerz">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_sslcommerz_mode" class="wlsm-font-bold"><?php esc_html_e( 'Payment Mode', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<select name="sslcommerz_mode" class="form-control" id="wlsm_sslcommerz_mode">
										<option <?php selected( $school_sslcommerz_mode, 'sandbox', true ); ?> value="sandbox"><?php esc_html_e( 'Sandbox', 'school-management' ); ?></option>
										<option <?php selected( $school_sslcommerz_mode, 'live', true ); ?> value="live"><?php esc_html_e( 'Live', 'school-management' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_sslcommerz">
						<div class="row">
							<div class="col-md-12">
								<label class="wlsm-font-bold"><?php esc_html_e( 'SSLCommerz Notify URL', 'school-management' ); ?>: </label><br>
								<span class="text-primary"><?php echo esc_url( $school_sslcommerz_notify_url ); ?></span><br>
								<small class="font-weight-bold">
									( <?php esc_html_e( 'To save transactions, you need to enable SSLCommerz IPN (Instant Payment Notification) in your SSLCommerz Account and use this notify URL', 'school-management' ); ?>
									)
								</small>
								<small>
									<ol>
										<li><?php esc_html_e( 'Log into your SSLCommerz account.', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Go to "My Account" then "Account Settings".', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Look for an option labelled "IPN Settings". Click on the update button for that option.', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Click "Choose IPN Settings".', 'school-management' ); ?></li>
										<li><?php esc_html_e( 'Enter the "Website Domain" and URL given above in "IPN Listener Url" and hit "Save URL".', 'school-management' ); ?></li>
									</ol>
								</small>
							</div>
						</div>
					</div>
				</div>

				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_bank_transfer_fields" aria-expanded="true" aria-controls="wlsm_bank_transfer_fields">
					<?php esc_html_e( 'Bank Transfer Payment Method', 'school-management' ); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_bank_transfer_fields">

					<div class="wlsm_payment_method wlsm_bank_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_bank_transfer_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'Bank Transfer Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_bank_transfer_enable" class="wlsm-font-bold">
										<input <?php checked( $school_bank_transfer_enable, true, true ); ?> type="checkbox" name="bank_transfer_enable" id="wlsm_bank_transfer_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_bank_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_bank_transfer_branch" class="wlsm-font-bold"><?php esc_html_e( 'Branch Code', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="bank_transfer_branch" type="text" id="wlsm_bank_transfer_branch" value="<?php echo esc_attr( $school_bank_transfer_branch ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Branch Code', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_bank_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_bank_transfer_account" class="wlsm-font-bold"><?php esc_html_e( 'Account No.', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="bank_transfer_account" type="text" id="wlsm_bank_transfer_account" value="<?php echo esc_attr( $school_bank_transfer_account ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Account No.', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_bank_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_bank_transfer_name" class="wlsm-font-bold"><?php esc_html_e( 'Name', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="bank_transfer_name" type="text" id="wlsm_bank_transfer_name" value="<?php echo esc_attr( $school_bank_transfer_name ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Name', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_bank_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_bank_transfer_message" class="wlsm-font-bold"><?php esc_html_e( 'Instructions', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<textarea name="bank_transfer_message" id="wlsm_bank_transfer_message" class="form-control" placeholder="<?php esc_attr_e( 'Instructions', 'school-management' ); ?>" rows="5"><?php echo esc_html( $school_bank_transfer_message ); ?></textarea>
								</div>
								<p class="description"><?php esc_html_e( 'Instructions to be shown to the students for bank transfer.', 'school-management' ); ?></p>
							</div>
						</div>
					</div>

				</div>

				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_upi_transfer_fields" aria-expanded="true" aria-controls="wlsm_upi_transfer_fields">
					<?php esc_html_e( 'UPI Transfer Payment Method', 'school-management' ); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_upi_transfer_fields">

					<div class="wlsm_payment_method wlsm_upi_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_upi_transfer_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'Upi Transfer Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_upi_transfer_enable" class="wlsm-font-bold">
										<input <?php checked( $school_upi_transfer_enable, true, true ); ?> type="checkbox" name="upi_transfer_enable" id="wlsm_upi_transfer_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_upi_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_upi_transfer_qr" class="wlsm-font-bold"><?php esc_html_e( 'Qr Code', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<img src="<?php echo esc_html(wp_get_attachment_url($school_upi_transfer_qr)); ?>" alt="wlsm_upi_transfer_qr" width="500px" height="500px">
									<input name="upi_transfer_qr" type="file" id="wlsm_upi_transfer_qr" value="<?php echo esc_attr( $school_upi_transfer_qr ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'qr Code', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_upi_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_upi_transfer_id" class="wlsm-font-bold"><?php esc_html_e( 'UPI ID.', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="upi_transfer_id" type="text" id="wlsm_upi_transfer_id" value="<?php echo esc_attr( $school_upi_transfer_id ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'UPI ID.', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_upi_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_upi_transfer_name" class="wlsm-font-bold"><?php esc_html_e( 'Name', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="upi_transfer_name" type="text" id="wlsm_upi_transfer_name" value="<?php echo esc_attr( $school_upi_transfer_name ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Name', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_upi_transfer">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_upi_transfer_message" class="wlsm-font-bold"><?php esc_html_e( 'Instructions', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<textarea name="upi_transfer_message" id="wlsm_upi_transfer_message" class="form-control" placeholder="<?php esc_attr_e( 'Instructions', 'school-management' ); ?>" rows="5"><?php echo esc_html( $school_upi_transfer_message ); ?></textarea>
								</div>
								<p class="description"><?php esc_html_e( 'Instructions to be shown to the students for upi transfer.', 'school-management' ); ?></p>
							</div>
						</div>
					</div>

				</div>

				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_amberpay_fields" aria-expanded="true" aria-controls="wlsm_amberpay_fields">
					<?php esc_html_e( 'Amberpay Payment Gateway ', 'school-management' ); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_amberpay_fields">

					<div class="wlsm_payment_method wlsm_amberpay">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_amberpay_enable" class="wlsm-font-bold">
									<?php esc_html_e( 'Amberpay Payment', 'school-management' ); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_amberpay_enable" class="wlsm-font-bold">
										<input <?php checked( $school_amberpay_enable, true, true ); ?> type="checkbox" name="amberpay_enable" id="wlsm_amberpay_enable" value="1">
										<?php esc_html_e( 'Enable', 'school-management' ); ?>
									</label>
									<?php if ( ! WLSM_Payment::currency_supports_amberpay( $currency ) ) { ?>
									<br>
									<small class="text-secondary">
										<?php
										printf(
											/* translators: %s: currency code */
											__( 'Amberpay does not support currency %s.', 'school-management' ),
											esc_html( $currency )
										);
										?>
									</small>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_amberpay">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_amberpay_client_id" class="wlsm-font-bold"><?php esc_html_e( 'Amberpay Client Id', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="amberpay_client_id" type="text" id="wlsm_amberpay_client_id" value="<?php echo esc_attr( $school_amberpay_client_id ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Amberpay Client Id', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_amberpay">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_amberpay_signature_url" class="wlsm-font-bold"><?php esc_html_e( 'Amberpay Signature url', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="amberpay_signature_url" type="text" id="wlsm_amberpay_signature_url" value="<?php echo esc_attr( $school_amberpay_signature_url ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Amberpay Signature url', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_amberpay">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_amberpay_payment_url" class="wlsm-font-bold"><?php esc_html_e( 'Amberpay Payment url', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="amberpay_payment_url" type="text" id="wlsm_amberpay_payment_url" value="<?php echo esc_attr( $school_amberpay_payment_url ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Amberpay Payment url', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_amberpay">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_amberpay_api_key" class="wlsm-font-bold"><?php esc_html_e( 'Amberpay api key', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<input name="amberpay_api_key" type="text" id="wlsm_amberpay_api_key" value="<?php echo esc_attr( $school_amberpay_api_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Amberpay api key', 'school-management' ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_amberpay">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_amberpay_mode" class="wlsm-font-bold"><?php esc_html_e( 'Payment Mode', 'school-management' ); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<select name="amberpay_mode" class="form-control" id="wlsm_amberpay_mode">
										<option <?php selected( $school_amberpay_mode, 'sandbox', true ); ?> value="sandbox"><?php esc_html_e( 'Sandbox', 'school-management' ); ?></option>
										<option <?php selected( $school_amberpay_mode, 'live', true ); ?> value="live"><?php esc_html_e( 'Live', 'school-management' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>

				</div>

								<?php
				// Get offline payment method settings
				$settings_cash = WLSM_M_Setting::get_settings_cash($school_id);
				$school_cash_enable = $settings_cash['enable'];
				$school_cash_instructions = $settings_cash['instructions'];

				$settings_check = WLSM_M_Setting::get_settings_check($school_id);
				$school_check_enable = $settings_check['enable'];
				$school_check_instructions = $settings_check['instructions'];

				$settings_demand_draft = WLSM_M_Setting::get_settings_demand_draft($school_id);
				$school_demand_draft_enable = $settings_demand_draft['enable'];
				$school_demand_draft_instructions = $settings_demand_draft['instructions'];

				$settings_card = WLSM_M_Setting::get_settings_card($school_id);
				$school_card_enable = $settings_card['enable'];
				$school_card_instructions = $settings_card['instructions'];
				?>

				<!-- Cash Payment Method -->
				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_cash_fields" aria-expanded="true" aria-controls="wlsm_cash_fields">
					<?php esc_html_e('Cash Payment Method', 'school-management'); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_cash_fields">
					<div class="wlsm_payment_method wlsm_cash">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_cash_enable" class="wlsm-font-bold">
									<?php esc_html_e('Cash Payment', 'school-management'); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_cash_enable" class="wlsm-font-bold">
										<input <?php checked($school_cash_enable, true, true); ?> type="checkbox" name="cash_enable" id="wlsm_cash_enable" value="1">
										<?php esc_html_e('Enable', 'school-management'); ?>
									</label>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_cash">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_cash_instructions" class="wlsm-font-bold"><?php esc_html_e('Instructions', 'school-management'); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<textarea name="cash_instructions" id="wlsm_cash_instructions" class="form-control" placeholder="<?php esc_attr_e('Instructions for cash payment', 'school-management'); ?>" rows="5"><?php echo esc_html($school_cash_instructions); ?></textarea>
								</div>
								<p class="description"><?php esc_html_e('Instructions to be shown to students for cash payments.', 'school-management'); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Check Payment Method -->
				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_check_fields" aria-expanded="true" aria-controls="wlsm_check_fields">
					<?php esc_html_e('Check/Cheque Payment Method', 'school-management'); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_check_fields">
					<div class="wlsm_payment_method wlsm_check">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_check_enable" class="wlsm-font-bold">
									<?php esc_html_e('Check Payment', 'school-management'); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_check_enable" class="wlsm-font-bold">
										<input <?php checked($school_check_enable, true, true); ?> type="checkbox" name="check_enable" id="wlsm_check_enable" value="1">
										<?php esc_html_e('Enable', 'school-management'); ?>
									</label>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_check">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_check_instructions" class="wlsm-font-bold"><?php esc_html_e('Instructions', 'school-management'); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<textarea name="check_instructions" id="wlsm_check_instructions" class="form-control" placeholder="<?php esc_attr_e('Instructions for check payment', 'school-management'); ?>" rows="5"><?php echo esc_html($school_check_instructions); ?></textarea>
								</div>
								<p class="description"><?php esc_html_e('Instructions to be shown to students for check payments.', 'school-management'); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Demand Draft Payment Method -->
				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_demand_draft_fields" aria-expanded="true" aria-controls="wlsm_demand_draft_fields">
					<?php esc_html_e('Demand Draft Payment Method', 'school-management'); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_demand_draft_fields">
					<div class="wlsm_payment_method wlsm_demand_draft">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_demand_draft_enable" class="wlsm-font-bold">
									<?php esc_html_e('Demand Draft Payment', 'school-management'); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_demand_draft_enable" class="wlsm-font-bold">
										<input <?php checked($school_demand_draft_enable, true, true); ?> type="checkbox" name="demand_draft_enable" id="wlsm_demand_draft_enable" value="1">
										<?php esc_html_e('Enable', 'school-management'); ?>
									</label>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_demand_draft">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_demand_draft_instructions" class="wlsm-font-bold"><?php esc_html_e('Instructions', 'school-management'); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<textarea name="demand_draft_instructions" id="wlsm_demand_draft_instructions" class="form-control" placeholder="<?php esc_attr_e('Instructions for demand draft payment', 'school-management'); ?>" rows="5"><?php echo esc_html($school_demand_draft_instructions); ?></textarea>
								</div>
								<p class="description"><?php esc_html_e('Instructions to be shown to students for demand draft payments.', 'school-management'); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Card Payment Method (Direct, not online gateway) -->
				<button type="button" class="mt-2 btn btn-block btn-primary" data-toggle="collapse" data-target="#wlsm_card_fields" aria-expanded="true" aria-controls="wlsm_card_fields">
					<?php esc_html_e('Card Payment Method (In-person)', 'school-management'); ?>
				</button>

				<div class="collapse border border-top-0 border-primary p-3" id="wlsm_card_fields">
					<div class="wlsm_payment_method wlsm_card">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_card_enable" class="wlsm-font-bold">
									<?php esc_html_e('Card Payment', 'school-management'); ?>:
								</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<label for="wlsm_card_enable" class="wlsm-font-bold">
										<input <?php checked($school_card_enable, true, true); ?> type="checkbox" name="card_enable" id="wlsm_card_enable" value="1">
										<?php esc_html_e('Enable', 'school-management'); ?>
									</label>
								</div>
							</div>
						</div>
					</div>

					<div class="wlsm_payment_method wlsm_card">
						<div class="row">
							<div class="col-md-3">
								<label for="wlsm_card_instructions" class="wlsm-font-bold"><?php esc_html_e('Instructions', 'school-management'); ?>:</label>
							</div>
							<div class="col-md-9">
								<div class="form-group">
									<textarea name="card_instructions" id="wlsm_card_instructions" class="form-control" placeholder="<?php esc_attr_e('Instructions for card payment', 'school-management'); ?>" rows="5"><?php echo esc_html($school_card_instructions); ?></textarea>
								</div>
								<p class="description"><?php esc_html_e('Instructions to be shown to students for in-person card payments.', 'school-management'); ?></p>
							</div>
						</div>
					</div>
				</div>

				<div class="row mt-2">
					<div class="col-md-12 text-center">
						<button type="submit" class="btn btn-primary" id="wlsm-save-school-payment-method-settings-btn">
							<i class="fas fa-save"></i>&nbsp;
							<?php esc_html_e( 'Save', 'school-management' ); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>

</div>
