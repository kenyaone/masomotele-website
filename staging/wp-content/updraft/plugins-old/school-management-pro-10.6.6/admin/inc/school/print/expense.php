<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Setting.php';

if (isset($from_front)) {
	$print_button_classes = 'button btn-sm btn-success';
} else {
	$print_button_classes = 'btn btn-sm btn-success';
}
?>

<!-- Print expense. -->
<div class="wlsm-container d-flex mb-2">
	<div class="col-md-12 wlsm-text-center">
		<br>
		<button type="button" class="<?php echo esc_attr($print_button_classes); ?>" id="wlsm-print-expense-btn" data-styles='["<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/css/bootstrap.min.css'); ?>","<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/css/wlsm-school-header.css'); ?>","<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/css/print/wlsm-expense.css'); ?>"]' data-title="
			<?php
			printf(
				/* translators: 1: expense title, 2: invoice number */
				esc_attr__('Expense - %1$s (%2$s)', 'school-management'),
				esc_attr(WLSM_M_Staff_Accountant::get_invoice_title_text($expense->label)),
				esc_attr($expense->invoice_number)
			);
		?>"><?php esc_html_e('Print Expense', 'school-management'); ?>
		</button>
	</div>
</div>

<!-- Print expense section. -->
<div class="wlsm-container wlsm" id="wlsm-print-expense">
	<div class="wlsm-print-expense-container">
		<?php require WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/partials/school_header.php'; ?>

		<div class="row">
			<div class="col-md-12">
				<div class="wlsm-h5 wlsm-expense-heading text-center">
					<?php
					printf(
						wp_kses(
							/* translators: %s: invoice title */
							__('<span class="wlsm-font-bold">Expense Title:</span> %s', 'school-management'),
							array(
								'span' => array('class' => array()),
							)
						),
						esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($expense->label))
					);
					?>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-6">
				<span class="font-bold ml-4"><strong><?php esc_html_e('Expense Date:', 'school-management'); ?></strong></span>
				<span class="text-inverse"><?php echo esc_html(WLSM_M_Staff_Class::get_date_text($expense->expense_date)); ?></span>
			</div>
			<div class="col-md-6 text-right pr-4">
				<span class="font-bold ml-4"><strong><?php esc_html_e('Invoice No:', 'school-management'); ?></strong></span>
				<span class="text-inverse"><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($expense->invoice_number)); ?></span>
			</div>
		</div>

		<div class="row">
			<div class="col-md-6">
				<span class="font-bold ml-4"><strong><?php esc_html_e('Expense No:', 'school-management'); ?></strong></span>
				<span class="text-inverse"><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($expense->ID)); ?></span>
			</div>
			<div class="col-md-6 text-right pr-4">
				<span class="font-bold ml-4"><strong><?php esc_html_e('Supplier Name:', 'school-management'); ?></strong></span>
				<span class="text-inverse"><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($expense->supplier_name)); ?></span>
			</div>
		</div>
		<hr class="wlsm-mb-2">

		<div class="col-md-12">
			<table class="table table-bordered wlsm-table-striped wlsm-table-hover">
				<thead>
					<tr>
						<th class="wlsm-text-center"><?php esc_html_e('Sr. No', 'school-management'); ?></th>
						<th class="wlsm-text-center"><?php esc_html_e('Title', 'school-management'); ?></th>
						<th class="wlsm-text-center"><?php esc_html_e('Amount', 'school-management'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						if (!empty($expense)) {
					?>
						<tr>
							<td class="wlsm-text-center">1.</td>
							<td class="wlsm-text-center"><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($expense->label)); ?></td>
							<td class="wlsm-text-center"><?php echo esc_html(WLSM_Config::get_money_text($expense->amount, $school_id)); ?></td>
						</tr>
						<tr>
							<td class="wlsm-text-center" colspan="2"><?php esc_html_e( 'Total', 'school-management' ); ?></td>
							<td class="wlsm-text-center"><?php echo esc_html(WLSM_Config::get_money_text($expense->amount, $school_id)); ?></td>
						</tr>
					<?php
						}
					?>
				</tbody>
			</table>
		</div>

		<div class="row">
			<div class="col-6 pl-5 mt-4">
				<?php // if ( ! empty( $school_signature ) ) { ?>
					<!-- <img src="<?php // echo esc_url( wp_get_attachment_url( $school_signature ) ); ?>" class="expense-image"><br> -->
				<?php // } ?>
				<span class="wlsm-font-bold"><strong><?php esc_html_e('Authorised By', 'school-management'); ?></strong></span>
			</div>
			<div class="col-6 text-right pr-5 mt-4">
				<?php // if ( ! empty( $receiver_signature ) ) { ?>
					<!-- <img src="<?php // echo esc_url( wp_get_attachment_url( $receiver_signature ) ); ?>" class="expense-image"><br> -->
				<?php // } ?>
				<span class="wlsm-font-bold"><strong><?php esc_html_e('Receiver\'s Signature', 'school-management'); ?></strong></span>
			</div>
		</div>
	</div>
</div>
