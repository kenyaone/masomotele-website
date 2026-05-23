<?php
defined('ABSPATH') || die();

global $wp;
global $wpdb;

if ((isset($_POST["TransactionId"]))) {
	$payment_amount = isset($_POST['Amount']) ? sanitize_text_field($_POST['Amount']) : '';

	$data = array(
		'payment_status'   => $_POST['PaymentStatus'],
		'payment_currency' => $_POST['CurrencyCode'],
		'txn_id'           => $_POST['TransactionId'],
		'receiver_email'   => $_POST['receiver_email'],
		// 'payer_email'      => $_POST['payer_email'],
		'invoice_id'       => $_POST['CustomMessage'],
		// 'invoice_ids'      => $_POST['custom_ids'],
		'payment_amount'   => $payment_amount
	);

	if ($data['payment_status'] == 'Success') {
		$payment_status   = $data['payment_status'];
		$payment_currency = $data['payment_currency'];
		$transaction_id   = $data['txn_id'];
		$receiver_email   = $data['receiver_email'];
		$payer_email      = $data['payer_email'];
		$invoice_id       = $data['invoice_id'];
		$invoice_ids      = '';
		$payment_amount   = $data['payment_amount'];


		// Check if transaction_id already exists in the WLSM_PAYMENTS table
		$existing_transaction = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . WLSM_PAYMENTS . " WHERE transaction_id = %s",
			$transaction_id
		));



		if ($existing_transaction > 0) {
			die;
		}

		// Checks if pending invoice exists.
		$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);



		if (!$invoice) {
			wp_send_json_error(esc_html__('Invoice not found or already paid.', 'school-management'));
		}

		$partial_payment = $invoice->partial_payment;

		$due = $invoice->payable - $invoice->paid;

		$school_id  = $invoice->school_id;
		$session_id = $invoice->session_id;

		$description = sprintf(
			/* translators: 1: invoice title, 2: invoice number */
			__('Invoice: %1$s (%2$s)', 'school-management'),
			esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice->invoice_title)),
			esc_html($invoice->invoice_number)
		);

		global $wpdb;

		try {
			$wpdb->query('BEGIN;');

			$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);


			// Payment data.
			$payment_data = array(
				'receipt_number'    => $receipt_number,
				'amount'            => $payment_amount,
				'transaction_id'    => $transaction_id,
				'payment_method'    => 'amberpay',
				'invoice_label'     => $invoice->invoice_title,
				'invoice_payable'   => $invoice->payable,
				'student_record_id' => $invoice->student_id,
				'invoice_id'        => $invoice_id,
				'school_id'         => $school_id,
			);

			$payment_data['created_at'] = current_time('Y-m-d H:i:s');



			$success = $wpdb->insert(WLSM_PAYMENTS, $payment_data);
			// 	var_dump($success); die;

			$new_payment_id = $wpdb->insert_id;

			// 			$buffer = ob_get_clean();
			// 			if (!empty($buffer)) {
			// 				throw new Exception($buffer);
			// 			}

			// 			if (false === $success) {
			// 				throw new Exception($wpdb->last_error);
			// 			}

			$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

			$wpdb->query('COMMIT;');

			die;

			// 			wp_send_json_success(array('message' => esc_html__('Payment made successfully.', 'school-management')));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($unexpected_error_message);
		}
	}
}


$current_page_url = home_url(add_query_arg(array(), $wp->request));
if (! is_user_logged_in()) {
	$login_form_args = array(
		'redirect'       => $current_page_url,
		'form_id'        => 'wlsm-login-form',
		'id_username'    => 'wlsm-login-username',
		'id_password'    => 'wlsm-login-password',
		'id_remember'    => 'wlsm-login-remember',
		'id_submit'      => 'wlsm-login-submit',
		'value_username' => '',
	);
	wp_login_form($login_form_args);
?>
	<a target="_blank" href="<?php echo esc_url(wp_lostpassword_url($current_page_url)); ?>">
		<?php esc_html_e('Lost your password?', 'school-management'); ?>
	</a>
<?php
} else {
	global $wpdb;
	$students = '';
	$user_id            = get_current_user_id();
	$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);
	if ($unique_student_ids) {
		$students           = WLSM_M_Parent::fetch_students( $unique_student_ids );
		$current_student_id = get_user_meta($user_id, 'wlsm_current_student_id', true);
		$student_id         = $current_student_id ? $current_student_id : $students[0]->ID;
		$student = WLSM_M::get_student_by_id($student_id);
		$user_id = $student->user_id;

	} else {
		$student = WLSM_M::get_student($user_id);
	}

	$records 		  = $students;
	// $user_id			= $students[2]->user_id;

	// Checks if user is student.

	$logout_redirect_url = $current_page_url;
	if ($student) {
		$school_id                          = $student->school_id;
		$settings_general                   = WLSM_M_Setting::get_settings_general($school_id);
		$school_student_logout_redirect_url = $settings_general['student_logout_redirect_url'];
		if (! empty($school_student_logout_redirect_url)) {
			$logout_redirect_url = $school_student_logout_redirect_url;
		}
	}
	$session_id = $student ? $student->session_id : 0;
	$logout_url = wp_logout_url($logout_redirect_url);
	$current_user = wp_get_current_user();

?>
	<div class="wlsm-logged-in-info">
		<span class="wlsm-logged-in-text"><?php echo esc_html(ucwords($current_user->user_login)) ?>
			<a class="wlsm-logout-link" href="<?php echo esc_url($logout_url); ?>">
				<?php esc_html_e('Logout', 'school-management'); ?>
			</a>
			<br>
			<br>
			<?php
			$current_session = WLSM_Config::current_session();
			?>
	</div>
<?php

	if ($student) {
		$school_id  = $student->school_id;
		$session_id = $student->session_id;

		$class_school_id = $student->class_school_id;
		require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/student/route.php';
	} else {
		require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Parent.php';

		$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);
		$students = WLSM_M_Parent::fetch_students( $unique_student_ids );

		if (count($unique_student_ids)) {
			require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/parent/route.php';
		} else {
			require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/no_record.php';
		}
	}
}
