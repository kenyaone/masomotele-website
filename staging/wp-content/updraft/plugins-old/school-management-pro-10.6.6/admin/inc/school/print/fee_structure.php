<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Setting.php';

if ( isset( $from_front ) ) {
	$print_button_classes = 'button btn-sm btn-success';
} else {
	$print_button_classes = 'btn btn-sm btn-success';
}

$class_label = WLSM_M_Class::get_label_text( $student->class_label );
?>

<!-- Print fee structure. -->
<div class="wlsm-container wlsm d-flex mt-2 mb-2">
	<div class="col-md-12 wlsm-text-center">
		<?php
		printf(
			wp_kses(
				/* translators: %s: class label */
				__( '<span class="wlsm-font-bold">Student Fee Structure</span><br><span class="wlsm-font-bold">Class:</span> %s</span>', 'school-management' ),
				array( 'span' => array( 'class' => array() ), 'br' => array() )
			),
			esc_html( $class_label )
		);
		?>
		<br>
		<button type="button" class="<?php echo esc_attr( $print_button_classes ); ?> mt-2" id="wlsm-print-fee-structure-btn" data-styles='["<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/bootstrap.min.css' ); ?>","<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/wlsm-school-header.css' ); ?>","<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/print/wlsm-fee-structure.css' ); ?>"]' data-title="<?php esc_attr_e( 'Fee Structure', 'school-management' ); ?>"><?php esc_html_e( 'Print Fee Structure', 'school-management' ); ?>
		</button>
	</div>
</div>

<!-- Print fee structure section. -->
<div class="wlsm-container wlsm wlsm-form-section" id="wlsm-print-fee-structure">
	<div class="wlsm-print-fee-structure-container">

		<?php require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/partials/school_header.php'; ?>

		<ul>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Student Name', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $student->student_name ) ); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Enrollment Number', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( $student->enrollment_number ); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Session', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( WLSM_M_Session::get_label_text( $student->session_label ) ); ?></span>
			</li>
		</ul>

		<span class="wlsm-font-bold"><?php esc_html_e( 'Fee Structure', 'school-management' ); ?></span>
		<span class="wlsm-float-md-right float-md-right">
		<?php
		/* translators: %s: class label */
		printf(
			wp_kses(
				__( '<span class="wlsm-font-bold">Class:</span> %s</span>', 'school-management' ),
				array( 'span' => array( 'class' => array() ) )
			),
			esc_html( $class_label )
		);
		?>
		</span>

		<div class="table-responsive w-100">
			<?php
			// Get session dates
			$session_dates = WLSM_M_Session::get_session_dates($student->session_id);
			$start_date = new DateTime($session_dates->start_date);
			$end_date = new DateTime($session_dates->end_date);

			// Calculate months between dates
			$interval = $start_date->diff($end_date);
			$total_months = ($interval->y * 12) + $interval->m;
			if ($interval->d > 0) {
				$total_months++;
			}
			?>
			<table class="table table-bordered wlsm-view-fee-structure">
				<thead>
					<tr>
						<th class="text-nowrap"><?php esc_html_e( 'Fee Type', 'school-management' ); ?></th>
						<th class="text-nowrap"><?php esc_html_e( 'Period', 'school-management' ); ?></th>
						<th class="text-nowrap"><?php esc_html_e( 'Amount', 'school-management' ); ?></th>
						<th class="text-nowrap"><?php esc_html_e( 'Total for Session', 'school-management' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					// Calculate durations for different periods
					$total_months = ($interval->y * 12) + $interval->m + ($interval->d > 0 ? 1 : 0);
					$total_quarters = ceil($total_months / 3);      // 3 months
					$total_quadrimesters = ceil($total_months / 4); // 4 months
					$total_half_years = ceil($total_months / 6);    // 6 months
					$total_years = ceil($total_months / 12);        // 12 months

					$fee_period_totals = array();
					$grand_total = 0;

					foreach ( $fees as $key => $fee ) {
						// Calculate total based on period
						switch($fee->period) {
							case 'monthly':
								$fee_total = $fee->amount * $total_months;
								break;
							case 'quarterly':
								$fee_total = $fee->amount * $total_quarters;
								break;
							case 'quadrimester':
								$fee_total = $fee->amount * $total_quadrimesters;
								break;
							case 'half-yearly':
								$fee_total = $fee->amount * $total_half_years;
								break;
							case 'annually':
								$fee_total = $fee->amount * $total_years;
								break;
							case 'one-time':
							default:
								$fee_total = $fee->amount;
								break;
						}

						// Add to period totals
						if (!isset($fee_period_totals[$fee->period])) {
							$fee_period_totals[$fee->period] = 0;
						}
						$fee_period_totals[$fee->period] += $fee_total;
						$grand_total += $fee_total;
					?>
					<tr>
						<td><?php echo esc_html( WLSM_M_Staff_Accountant::get_label_text( $fee->label ) ); ?></td>
						<td><?php echo esc_html( WLSM_M_Staff_Accountant::get_fee_period_text( $fee->period ) ); ?></td>
						<td><?php echo esc_html( WLSM_Config::get_money_text( $fee->amount, $school_id ) ); ?></td>
						<td><?php echo esc_html( WLSM_Config::get_money_text( $fee_total, $school_id ) ); ?></td>
					</tr>
					<?php
					}
					?>
					<tr class="wlsm-font-bold">
						<td colspan="3" class="text-right"><?php esc_html_e( 'Grand Total', 'school-management' ); ?></td>
						<td><?php echo esc_html( WLSM_Config::get_money_text( $grand_total, $school_id ) ); ?></td>
					</tr>

					<?php
					// Check if student has any concession
					$student_concession = WLSM_M_Staff_General::fetch_student_concession( $student->ID, $student->session_id, $school_id );

					if ( $student_concession && 'approved' === $student_concession->status ) {
						$concession_amount = 0;
						$concession_label  = '';

						// Calculate concession based on type
						if ( 'percentage' === $student_concession->concession_type ) {
							$concession_amount = ( $grand_total * $student_concession->percentage_value ) / 100;
							$concession_label  = esc_html( $student_concession->concession_name ) . ' (' . $student_concession->percentage_value . '%)';
						} elseif ( 'fixed_amount' === $student_concession->concession_type ) {
							$concession_amount = min( $student_concession->fixed_amount, $grand_total ); // Don't exceed total
							$concession_label  = esc_html( $student_concession->concession_name ) . ' (' . esc_html__( 'Fixed', 'school-management' ) . ')';
						}

						$payable_amount = $grand_total - $concession_amount;

						if ( $concession_amount > 0 ) {
					?>
					<tr class="wlsm-font-bold">
						<td colspan="3" class="text-right"><?php echo esc_html( $concession_label . ':' ); ?></td>
						<td>- <?php echo esc_html( WLSM_Config::get_money_text( $concession_amount, $school_id ) ); ?></td>
					</tr>
					<tr class="wlsm-font-bold">
						<td colspan="3" class="text-right"><?php esc_html_e( 'Payable Amount', 'school-management' ); ?>:</td>
						<td><?php echo esc_html( WLSM_Config::get_money_text( $payable_amount, $school_id ) ); ?></td>
					</tr>
					<?php
						}
					}
					?>

				</tbody>
				</table>
			</div>

		</div>
	</div>
