<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Session.php';

$inline_view = isset( $inline_view ) ? (bool) $inline_view : false;

$class_label    = WLSM_M_Class::get_label_text( $student->class_label );
$session_dates  = WLSM_M_Session::get_session_dates( $student->session_id );

$start_date = $session_dates ? new DateTime( $session_dates->start_date ) : null;
$end_date   = $session_dates ? new DateTime( $session_dates->end_date ) : null;

$interval = ( $start_date && $end_date ) ? $start_date->diff( $end_date ) : null;

$total_months        = $interval ? ( ( $interval->y * 12 ) + $interval->m + ( $interval->d > 0 ? 1 : 0 ) ) : 0;
$total_months        = max( $total_months, 1 );
$total_quarters      = ceil( $total_months / 3 );
$total_quadrimesters = ceil( $total_months / 4 );
$total_half_years    = ceil( $total_months / 6 );
$total_years         = ceil( $total_months / 12 );

$invoices              = is_array( $invoices ) ? $invoices : array();
$grand_total           = 0;
$total_invoice_payable = 0;
$total_invoice_paid    = 0;
$total_invoice_due     = 0;
$invoice_counts        = array(
	'paid'            => 0,
	'partially_paid'  => 0,
	'unpaid'          => 0,
	'other'           => 0,
);

foreach ( $invoices as $invoice_summary ) {
	$total_invoice_payable += floatval( $invoice_summary->payable );
	$total_invoice_paid    += floatval( $invoice_summary->paid );
	$total_invoice_due     += max( floatval( $invoice_summary->due ), 0 );

	$status_key = isset( $invoice_summary->status ) ? $invoice_summary->status : 'other';
	if ( isset( $invoice_counts[ $status_key ] ) ) {
		$invoice_counts[ $status_key ]++;
	} else {
		$invoice_counts['other']++;
	}
}

$total_invoice_payable = max( $total_invoice_payable, 0 );
$total_invoice_paid    = max( $total_invoice_paid, 0 );
$total_invoice_due     = max( $total_invoice_due, 0 );
$wrapper_classes = $inline_view ? 'wlsm-container wlsm wlsm-form-section wlsm-fee-details-modal' : 'wlsm-form-section wlsm-fee-details-modal';
?>
<div class="<?php echo esc_attr($wrapper_classes); ?>">
	<div class="wlsm-print-fee-structure-container">
		<?php if ($inline_view) { ?>
		<div class="d-flex justify-content-between align-items-center mb-3">
			<span class="wlsm-section-heading wlsm-font-bold mb-0"><?php esc_html_e('Student Fee Details', 'school-management'); ?></span>
			<button type="button" class="btn btn-sm btn-outline-secondary wlsm-hide-invoice-fee-details">
				<i class="fas fa-times"></i> <?php esc_html_e('Close', 'school-management'); ?>
			</button>
		</div>
		<?php } ?>
		<ul>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e('Student Name', 'school-management'); ?>:</span>
				<span><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e('Enrollment Number', 'school-management'); ?>:</span>
				<span><?php echo esc_html($student->enrollment_number); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e('Class', 'school-management'); ?>:</span>
				<span><?php echo esc_html($class_label); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e('Session', 'school-management'); ?>:</span>
				<span><?php echo esc_html(WLSM_M_Session::get_label_text($student->session_label)); ?></span>
			</li>
		</ul>

		<div class="table-responsive w-100">
			<table class="table table-bordered wlsm-view-fee-structure">
				<thead>
					<tr>
						<th class="text-nowrap"><?php esc_html_e('Fee Type', 'school-management'); ?></th>
						<th class="text-nowrap"><?php esc_html_e('Period', 'school-management'); ?></th>
						<th class="text-nowrap"><?php esc_html_e('Amount', 'school-management'); ?></th>
						<th class="text-nowrap"><?php esc_html_e('Occurrences in Session', 'school-management'); ?></th>
						<th class="text-nowrap"><?php esc_html_e('Session Total', 'school-management'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( count( $fees ) ) { ?>
						<?php foreach ( $fees as $fee ) { ?>
							<?php
							switch ( $fee->period ) {
								case 'monthly':
									$occurrences = $total_months;
									break;
								case 'quarterly':
									$occurrences = $total_quarters;
									break;
								case 'quadrimester':
									$occurrences = $total_quadrimesters;
									break;
								case 'half-yearly':
									$occurrences = $total_half_years;
									break;
								case 'annually':
									$occurrences = $total_years;
									break;
								case 'one-time':
								default:
									$occurrences = 1;
									break;
							}

							$occurrences    = max( $occurrences, 1 );
							$session_total  = $fee->amount * $occurrences;
							$grand_total   += $session_total;
							?>
							<tr>
								<td><?php echo esc_html( WLSM_M_Staff_Accountant::get_label_text( $fee->label ) ); ?></td>
								<td><?php echo esc_html( WLSM_M_Staff_Accountant::get_fee_period_text( $fee->period ) ); ?></td>
								<td><?php echo esc_html( WLSM_Config::get_money_text( $fee->amount, $school_id ) ); ?></td>
								<td><?php echo esc_html( $occurrences ); ?></td>
								<td><?php echo esc_html( WLSM_Config::get_money_text( $session_total, $school_id ) ); ?></td>
							</tr>
						<?php } ?>
						<tr class="wlsm-font-bold">
							<td colspan="4" class="text-right"><?php esc_html_e( 'Grand Total', 'school-management' ); ?></td>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $grand_total, $school_id ) ); ?></td>
						</tr>
						<?php
							$payable_amount     = $grand_total;
							$student_concession = WLSM_M_Staff_General::fetch_student_concession( $student->ID, $student->session_id, $school_id );

							if ( $student_concession && 'approved' === $student_concession->status ) {
								$concession_amount = 0;
								$concession_label  = '';

								if ( 'percentage' === $student_concession->concession_type ) {
									$concession_amount = ( $grand_total * $student_concession->percentage_value ) / 100;
									$concession_label  = esc_html( $student_concession->concession_name ) . ' (' . $student_concession->percentage_value . '%)';
								} elseif ( 'fixed_amount' === $student_concession->concession_type ) {
									$concession_amount = min( $student_concession->fixed_amount, $grand_total );
									$concession_label  = esc_html( $student_concession->concession_name ) . ' (' . esc_html__( 'Fixed', 'school-management' ) . ')';
								}

								if ( $concession_amount > 0 ) {
									$payable_amount = max( $grand_total - $concession_amount, 0 );
						?>
						<tr class="wlsm-font-bold">
							<td colspan="4" class="text-right"><?php echo esc_html( $concession_label . ':' ); ?></td>
							<td>- <?php echo esc_html( WLSM_Config::get_money_text( $concession_amount, $school_id ) ); ?></td>
						</tr>
						<tr class="wlsm-font-bold">
							<td colspan="4" class="text-right"><?php esc_html_e( 'Payable Amount', 'school-management' ); ?></td>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $payable_amount, $school_id ) ); ?></td>
						</tr>
						<?php
								}
							}
						?>
					<?php } else { ?>
						<tr>
							<td colspan="5" class="text-center wlsm-font-bold"><?php esc_html_e( 'No fee structure assigned.', 'school-management' ); ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>

		<?php
		if ( ! isset( $payable_amount ) ) {
			$payable_amount = $grand_total;
		}
		$session_payable_total = max( isset( $payable_amount ) ? $payable_amount : $grand_total, 0 );
		$display_invoice_total = min( $total_invoice_payable, $session_payable_total );
		$remaining_balance     = max( $session_payable_total - $total_invoice_paid, 0 );
		$not_invoiced_balance  = max( $session_payable_total - $total_invoice_payable, 0 );
		$display_due_amount    = min( $total_invoice_due, $remaining_balance );
		?>

		<div class="mt-4">
			<h5 class="wlsm-font-bold"><?php esc_html_e( 'Invoice Summary', 'school-management' ); ?></h5>
			<div class="table-responsive w-100">
				<table class="table table-bordered wlsm-fee-summary-table">
					<tbody>
						<tr>
							<th class="text-nowrap"><?php esc_html_e( 'Session Fee Total', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $grand_total, $school_id ) ); ?></td>
						</tr>
						<tr>
							<th class="text-nowrap"><?php esc_html_e( 'Net Payable (After Concessions)', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $session_payable_total, $school_id ) ); ?></td>
						</tr>
						<tr>
							<th class="text-nowrap"><?php esc_html_e( 'Invoiced to Date', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $display_invoice_total, $school_id ) ); ?></td>
						</tr>
						<tr>
							<th class="text-nowrap"><?php esc_html_e( 'Paid Amount', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $total_invoice_paid, $school_id ) ); ?></td>
						</tr>
						<tr>
							<th class="text-nowrap"><?php esc_html_e( 'Outstanding (Invoiced Due)', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $display_due_amount, $school_id ) ); ?></td>
						</tr>
						<tr>
							<th class="text-nowrap"><?php esc_html_e( 'Remaining Session Balance', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $remaining_balance, $school_id ) ); ?></td>
						</tr>
						<tr>
							<th class="text-nowrap"><?php esc_html_e( 'Upcoming (Not Yet Invoiced)', 'school-management' ); ?></th>
							<td><?php echo esc_html( WLSM_Config::get_money_text( $not_invoiced_balance, $school_id ) ); ?></td>
						</tr>
						<tr>
							<th class="text-nowrap"><?php esc_html_e( 'Invoices Count', 'school-management' ); ?></th>
							<td>
								<span class="mr-3">
									<?php esc_html_e( 'Paid:', 'school-management' ); ?>
									<span class="wlsm-font-bold"><?php echo esc_html( $invoice_counts['paid'] ); ?></span>
								</span>
								<span class="mr-3">
									<?php esc_html_e( 'Partially Paid:', 'school-management' ); ?>
									<span class="wlsm-font-bold"><?php echo esc_html( $invoice_counts['partially_paid'] ); ?></span>
								</span>
								<span class="mr-3">
									<?php esc_html_e( 'Unpaid:', 'school-management' ); ?>
									<span class="wlsm-font-bold"><?php echo esc_html( $invoice_counts['unpaid'] ); ?></span>
								</span>
								<?php if ( $invoice_counts['other'] ) { ?>
								<span>
									<?php esc_html_e( 'Other:', 'school-management' ); ?>
									<span class="wlsm-font-bold"><?php echo esc_html( $invoice_counts['other'] ); ?></span>
								</span>
								<?php } ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<?php
		require WLSM_PLUGIN_DIR_PATH . 'includes/partials/student_invoices.php';
		require WLSM_PLUGIN_DIR_PATH . 'includes/partials/student_payments.php';
		?>
	</div>
</div>
