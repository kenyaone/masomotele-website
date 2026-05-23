<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Invoice.php';

if ( isset( $from_front ) ) {
	$print_button_classes = 'button btn-sm btn-success';
} else {
	$print_button_classes = 'btn btn-sm btn-success';
}

$invoice_payable = isset( $payment->invoice_payable ) ? (float) $payment->invoice_payable : 0;
if ( ! $invoice_payable && isset( $payment->invoice_amount ) ) {
	$invoice_payable = (float) $payment->invoice_amount;
}

$payment->invoice_payable = $invoice_payable;
$due 			= $payment->due;
$bank_name 		= $payment->bank_name;
$cheque_number 	= $payment->cheque_number;
$cheque_date 	= $payment->cheque_date;
$authorized_by 	= $payment->authorized_by;
// $invoice_id 	= $payment->invoice_id;

// $total_paid = WLSM_M_Invoice::get_total_paid_amount( $invoice_id, $school_id );
?>

<!-- Print invoice payment. -->
<div class="wlsm-container d-flex mb-2">
	<div class="col-md-12 wlsm-text-center">
		<br>
		<button type="button" class="<?php echo esc_attr( $print_button_classes ); ?>" id="wlsm-print-invoice-payment-btn" data-styles='["<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/bootstrap.min.css' ); ?>","<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/wlsm-school-header.css' ); ?>","<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/print/wlsm-payment.css' ); ?>"]' data-title="<?php
		printf(
			/* translators: %s: receipt number */
			esc_attr__( 'Payment Receipt - %s', 'school-management' ),
			esc_attr( WLSM_M_Invoice::get_receipt_number_text( $payment->receipt_number ) ) );
		?>"><?php esc_html_e( 'Print Payment Receipt', 'school-management' ); ?>
		</button>
	</div>
</div>

<!-- Print invoice payment section. -->
<div class="wlsm-container wlsm" id="wlsm-print-invoice-payment">
	<div class="wlsm-print-invoice-payment-container">

		<?php require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/partials/school_header.php'; ?>

		<div class="row">
			<div class="col-md-12">
				<div class="wlsm-h5 wlsm-invoice-payment-heading wlsm-font-bold text-center">
					<?php esc_html_e( 'Payment Receipt', 'school-management' ); ?>
					<small class="float-md-right">
					<?php
					printf(
						wp_kses(
							/* translators: %s: receipt number */
							__( '<span class="wlsm-font-bold">Receipt No.</span> %s', 'school-management' ),
							array( 'span' => array( 'class' => array() ) )
						),
						esc_html( WLSM_M_Invoice::get_receipt_number_text( $payment->receipt_number ) )
					);
					?>
					</small>
				</div>
			</div>
		</div>

		<div class="row mt-2">
			<div class="col-12">
				<div class="table-responsive w-100">
					<table class="table table-bordered">
						<tr>
							<th><?php esc_html_e( 'Invoice Title', 'school-management' ); ?></th>
							<td>
							<?php
							if ( $payment->invoice_id ) {
								echo esc_html( WLSM_M_Staff_Accountant::get_invoice_title_text( $payment->invoice_title ) );
							} else {
								echo esc_html( WLSM_M_Staff_Accountant::get_invoice_title_text( $payment->invoice_label ) );
							}
							?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Receipt Number', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Invoice::get_receipt_number_text( $payment->receipt_number ) ); ?></td>
						</tr>
<?php
	$invoice_amount_text = WLSM_Config::get_money_text( $payment->invoice_payable, $school_id );
	if ( '-' === $invoice_amount_text ) {
		$invoice_amount_text = WLSM_Config::currency_symbol( $school_id ) . number_format( 0, 2, '.', ',' );
	}

	$due_amount_text = WLSM_Config::get_money_text( $payment->due, $school_id );
	if ( '-' === $due_amount_text ) {
		$due_amount_text = WLSM_Config::currency_symbol( $school_id ) . number_format( 0, 2, '.', ',' );
	}

	// Compute Session Total Remaining Amount similar to Student Dashboard "Session Fee Summary"
	$session_remaining_amount = null;
	try {
		$student_id = isset($payment->student_record_id) ? (int)$payment->student_record_id : 0;
		if ($student_id && isset($school_id)) {
			// Fetch student and fees
			$student_obj = WLSM_M_Staff_General::fetch_student($school_id, isset($session_id) ? $session_id : 0, $student_id);
			if (!$student_obj && isset($payment->session_id)) {
				$student_obj = WLSM_M_Staff_General::fetch_student($school_id, (int)$payment->session_id, $student_id);
			}
			if ($student_obj) {
				$sess_id_local = isset($session_id) && $session_id ? $session_id : (isset($student_obj->session_id) ? (int)$student_obj->session_id : 0);
				$fees = WLSM_M_Staff_Accountant::fetch_student_fees($school_id, $student_id);

				// Calculate months in session
				$start_date = new DateTime($student_obj->start_date);
				$end_date   = new DateTime($student_obj->end_date);
				$interval   = $start_date->diff($end_date);
				$months_in_session = ($interval->y * 12) + $interval->m;
				if ($interval->d > 0) { $months_in_session++; }

				// Totals by period
				$session_totals = array(
					'monthly' => 0,
					'quarterly' => 0,
					'quadrimester' => 0,
					'half-yearly' => 0,
					'annually' => 0,
					'one-time' => 0,
				);
				foreach ($fees as $fee) {
					switch ($fee->period) {
						case 'monthly':       $session_totals['monthly']       += (int)$fee->amount * $months_in_session; break;
						case 'one-time':      $session_totals['one-time']      += (int)$fee->amount; break;
						case 'quarterly':     $session_totals['quarterly']     += (int)$fee->amount * ceil($months_in_session / 3); break;
						case 'quadrimester':  $session_totals['quadrimester']  += (int)$fee->amount * ceil($months_in_session / 4); break;
						case 'half-yearly':   $session_totals['half-yearly']   += (int)$fee->amount * ceil($months_in_session / 6); break;
						case 'annually':      $session_totals['annually']      += (int)$fee->amount * ceil($months_in_session / 12); break;
					}
				}

				$total_payable = array_sum($session_totals);

				// Concession
				$student_concession = WLSM_M_Staff_General::fetch_student_concession($student_id, $sess_id_local, $school_id);
				$concession_amount = 0;
				if ($student_concession && 'approved' === $student_concession->status) {
					if ('percentage' === $student_concession->concession_type) {
						$concession_amount = ($total_payable * $student_concession->percentage_value) / 100;
					} elseif ('fixed_amount' === $student_concession->concession_type) {
						$concession_amount = min($student_concession->fixed_amount, $total_payable);
					}
				}
				$payable_amount = max($total_payable - $concession_amount, 0);

				// Total paid in this session for this student record
				$total_paid_session = 0;
				$student_payments = WLSM_M_Staff_Accountant::get_student_payments($student_id);
				if (!empty($student_payments)) {
					foreach ($student_payments as $sp) { $total_paid_session += (float)$sp->amount; }
				}

				$session_remaining_amount = max($payable_amount - $total_paid_session, 0);
			}
		}
	} catch (Exception $e) {
		$session_remaining_amount = null;
	}
	$session_remaining_amount_text = '-';
	if (null !== $session_remaining_amount) {
		$session_remaining_amount_text = WLSM_Config::get_money_text($session_remaining_amount, $school_id);
		if ('-' === $session_remaining_amount_text) {
			$session_remaining_amount_text = WLSM_Config::currency_symbol($school_id) . number_format( 0, 2, '.', ',' );
		}
	}
?>
<tr>
	<th><?php esc_html_e( 'Invoice Amount', 'school-management' ); ?></th>
	<td><?php echo esc_html( $invoice_amount_text ); ?></td>
</tr>
						<tr>
							<th><?php esc_html_e( 'Amount Received', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $payment->amount, $school_id ) ); ?></td>
						</tr>
						<tr>
	<th><?php esc_html_e( 'Due Amount', 'school-management' ); ?></th>
	<td><?php echo esc_html( $due_amount_text ); ?></td>
						</tr>
						<?php if ($session_remaining_amount !== null) : ?>
						<tr>
							<th><?php esc_html_e( 'Total Remaining Amount in Session', 'school-management' ); ?></th>
							<td><?php echo esc_html( $session_remaining_amount_text ); ?></td>
						</tr>
						<?php endif; ?>
						<!-- <tr>
							<th><?php // esc_html_e( 'Total Amount Paid', 'school-management' ); ?></th>
							<td><?php // echo esc_html( WLSM_Config::get_money_text( $total_paid, $school_id ) ); ?></td>
						</tr> -->
						<tr>
							<th><?php esc_html_e( 'Payment Method', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Invoice::get_payment_method_text( $payment->payment_method ) ); ?></td>
						</tr>
						<?php if( $bank_name ): ?>
							<tr>
								<th><?php esc_html_e( 'Bank Name', 'school-management' ); ?></th>
								<td><?php echo esc_html( $bank_name ); ?></td>
							</tr>
						<?php endif; ?>
						<?php if( $cheque_number ): ?>
						<tr>
							<th><?php esc_html_e( 'Cheque Number', 'school-management' ); ?></th>
							<td><?php echo esc_html( $cheque_number ); ?></td>
						</tr>
						<?php endif; ?>
						<?php if( $cheque_date ): ?>
						<tr>
							<th><?php esc_html_e( 'Cheque Date', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_date_text( $cheque_date ) ); ?></td>
						</tr>
						<?php endif; ?>
						<?php if( $authorized_by ): ?>
						<tr>
							<th><?php esc_html_e( 'Authorized By', 'school-management' ); ?></th>
							<td><?php echo esc_html( $authorized_by); ?></td>
						</tr>
						<?php endif; ?>
						<tr>
							<th><?php esc_html_e( 'Transaction ID', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Invoice::get_transaction_id_text( $payment->transaction_id ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Date', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_date_text( $payment->created_at ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Student Name', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $payment->student_name ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Enrollment Number', 'school-management' ); ?></th>
							<td><?php echo esc_html( $payment->enrollment_number ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Phone', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Staff_Class::get_phone_text( $payment->phone ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Email', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $payment->email ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Class', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Class::get_label_text( $payment->class_label ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Section', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Class::get_label_text( $payment->section_label ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Roll Number', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Staff_Class::get_roll_no_text( $payment->roll_number ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Father\'s Name:', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $payment->father_name ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Father\'s Phone:', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_M_Staff_Class::get_phone_text( $payment->father_phone ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Receiver Name:', 'school-management' ); ?></th>
							<?php
								$user_id = $payment->added_by;
								$user_info = get_userdata($user_id);

								if ($user_info) {
									$username = $user_info->user_login;
								} else {
									$username = '';
								}
							?>
							<td><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $username ) ); ?></td>
						</tr>
					</table>
				</div>

				<div class="row">
					<div class="col-6 pl-5 mt-4">
					</div>
					<div class="col-6 text-right pr-5 mt-4">
						<span class="wlsm-font-bold"><strong><?php esc_html_e('Receiver\'s Signature', 'school-management'); ?></strong></span>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>
