<?php
defined( 'ABSPATH' ) || die();

// Accounting Payment vs Expense Chart.
$accounting_payment_expense = array(
	'id'            => 'wlsm-chart-accounting-payment-expense',
	'action'        => 'wlsm-fetch-accounting-payment-expense',
	'nonce'         => esc_attr( wp_create_nonce('accounting-payment-expense') ),
	'title'         => esc_html__( 'Payments vs Expenses', 'school-management' ),
	'title_1'       => esc_html__( 'Month', 'school-management' ),
	'title_2'       => esc_html__( 'Amount', 'school-management' ),
	'payment_label' => esc_html__( 'Paid Invoices (Income)', 'school-management' ),
	'expense_label' => esc_html__( 'Expenses', 'school-management' ),
);

// Get chart type from settings (fallback to 'bar' if not set)
$chart_type = isset($settings_chart_types['accounting_payment_expense']) ? $settings_chart_types['accounting_payment_expense'] : 'bar';

$currency_symbol = html_entity_decode( WLSM_Config::currency_symbol($school_id) );
?>

<div class="wlsm-accounting-chart-container">
	<div class="wlsm-chart-controls">
		<h5 class="mb-3">
			<i class="fas fa-chart-bar text-primary"></i>
			<?php echo esc_html($accounting_payment_expense['title']); ?>
		</h5>
	</div>

	<div class="wlsm-chart-body">
		<!-- Chart Canvas -->
		<canvas class="wlsm-chart" id="<?php echo esc_attr( $accounting_payment_expense['id'] ); ?>" height="300"></canvas>
	</div>
</div>

<?php
$js = <<<EOT
(function($) {
	'use strict';
	$(document).ready(function() {
		var accountingChart;

		// Accounting Payment vs Expense.
		function wlsmAccountingPaymentExpense(postData) {
			$.post('$ajax_url', postData, function(data) {
				var data = JSON.parse(data);
				var paymentData = data.payments;
				var expenseData = data.expenses;

				if(paymentData.length > 0 || expenseData.length > 0) {
					var paymentLabels = [];
					var paymentDatasets = [];

					var expenseLabels = [];
					var expenseDatasets = [];

					for (var i = 0; i < paymentData.length; i++) {
						paymentLabels.push(paymentData[i].x);
						paymentDatasets.push(paymentData[i].y);
					}

					for (var i = 0; i < expenseData.length; i++) {
						expenseLabels.push(expenseData[i].x);
						expenseDatasets.push(expenseData[i].y);
					}

					var accountingCtx = $('#{$accounting_payment_expense['id']}');

					// Destroy existing chart if it exists
					if (accountingChart) {
						accountingChart.destroy();
					}

					accountingChart = new Chart(accountingCtx, {
						type: '$chart_type',
						data: {
							labels: paymentLabels.length > 0 ? paymentLabels : expenseLabels,
							datasets: [{
								label: '{$accounting_payment_expense['payment_label']}',
								backgroundColor: 'rgba(40, 167, 69, 0.8)',
								borderColor: 'rgba(40, 167, 69, 1)',
								borderWidth: 1,
								data: paymentDatasets
							},
							{
								label: '{$accounting_payment_expense['expense_label']}',
								backgroundColor: 'rgba(220, 53, 69, 0.8)',
								borderColor: 'rgba(220, 53, 69, 1)',
								borderWidth: 1,
								data: expenseDatasets
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							legend: {
								position: 'top',
							},
							title: {
								display: true,
								text: '{$accounting_payment_expense['title']}'
							},
							scales: {
								yAxes: [{
									ticks: {
										beginAtZero: true,
										callback: function(value, index, values) {
											return '{$currency_symbol}' + value.toLocaleString();
										}
									}
								}]
							},
							hover: {
								onHover: function(event, elements) {
									event.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
								}
							}
						},
					});

				} else {
					if (accountingChart) {
						accountingChart.destroy();
						accountingChart = null;
					}
					$('#{$accounting_payment_expense['id']}').parent().html('<div class="alert alert-info text-center"><i class="fas fa-info-circle"></i> No data available for the selected date range.</div>');
				}
			}).fail(function() {
				if (accountingChart) {
					accountingChart.destroy();
					accountingChart = null;
				}
				$('#{$accounting_payment_expense['id']}').parent().html('<div class="alert alert-danger text-center"><i class="fas fa-exclamation-triangle"></i> Error loading chart data.</div>');
			});
		}

		// Initial chart load with current month
		function loadAccountingChart() {
			var startDate = $('#wlsm_global_accounting_start_date').val();
			var endDate = $('#wlsm_global_accounting_end_date').val();

			wlsmAccountingPaymentExpense({
				'action': '{$accounting_payment_expense['action']}',
				'nonce': '{$accounting_payment_expense['nonce']}',
				'start_date': startDate,
				'end_date': endDate
			});
		}

		// Load chart on page ready
		loadAccountingChart();

		// Listen for global filter updates
		$(document).on('accounting_dates_updated', function() {
			loadAccountingChart();
		});
	});
})(jQuery);
EOT;
wp_add_inline_script( 'wlsm-admin', $js );
