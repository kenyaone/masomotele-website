<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Setting.php';

if (isset($from_front)) {
	$print_button_classes = 'button btn-sm btn-success';
} else {
	$print_button_classes = 'btn btn-sm btn-success';
}
?>

<!-- Print income. -->
<div class="wlsm-container d-flex mb-2">
	<div class="col-md-12 wlsm-text-center">
		<br>
		<button type="button" class="<?php echo esc_attr($print_button_classes); ?>" id="wlsm-print-income-btn" data-styles='["<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/css/bootstrap.min.css'); ?>","<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/css/wlsm-school-header.css'); ?>","<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/css/print/wlsm-income.css'); ?>"]' data-title="
			<?php
			printf(
				/* translators: 1: income title, 2: invoice number */
				esc_attr__('Donation - %1$s (%2$s)', 'school-management'),
				esc_attr(WLSM_M_Staff_Accountant::get_invoice_title_text($income->label)),
				esc_attr($income->invoice_number)
			);
		?>"><?php esc_html_e('Print Donation', 'school-management'); ?>
		</button>
	</div>
</div>

<!-- Print income section. -->
<div class="wlsm-container wlsm" id="wlsm-print-income">
	<div class="wlsm-print-income-container">
		<?php require WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/partials/school_header.php'; ?>

		<div class="row">
			<div class="col-md-12">
				<div class="wlsm-h5 wlsm-income-heading text-center">
					<?php
					printf(
						wp_kses(
							/* translators: %s: invoice title */
							__('<span class="wlsm-font-bold">Donation Title:</span> %s', 'school-management'),
							array(
								'span' => array('class' => array()),
							)
						),
						esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($income->label))
					);
					?>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-6">
				<span class="font-bold ml-4"><strong><?php esc_html_e('Donation Date:', 'school-management'); ?></strong></span>
				<span class="text-inverse"><?php echo esc_html(WLSM_M_Staff_Class::get_date_text($income->income_date)); ?></span>
			</div>
			<div class="col-md-6 text-right pr-4">
				<span class="font-bold ml-4"><strong><?php esc_html_e('Invoice No:', 'school-management'); ?></strong></span>
				<span class="text-inverse"><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($income->invoice_number)); ?></span>
			</div>
		</div>

		<div class="row">
			<div class="col-md-6">
				<span class="font-bold ml-4"><strong><?php esc_html_e('Doner Name:', 'school-management'); ?></strong></span>
				<span class="text-inverse"><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($income->doner_name)); ?></span>
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
						if (!empty($income)) {
					?>
						<tr>
							<td class="wlsm-text-center">1.</td>
							<td class="wlsm-text-center"><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($income->label)); ?></td>
							<td class="wlsm-text-center"><?php echo esc_html(WLSM_Config::get_money_text($income->amount, $school_id)); ?></td>
						</tr>
						<tr>
							<td class="wlsm-text-center" colspan="2"><?php esc_html_e( 'Total', 'school-management' ); ?></td>
							<td class="wlsm-text-center"><?php echo esc_html(WLSM_Config::get_money_text($income->amount, $school_id)); ?></td>
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
					<!-- <img src="<?php // echo esc_url( wp_get_attachment_url( $school_signature ) ); ?>" class="income-image"><br> -->
				<?php // } ?>
				<span class="wlsm-font-bold"><strong><?php esc_html_e('Authorised By', 'school-management'); ?></strong></span>
			</div>
			<div class="col-6 text-right pr-5 mt-4">
				<?php // if ( ! empty( $receiver_signature ) ) { ?>
					<!-- <img src="<?php // echo esc_url( wp_get_attachment_url( $receiver_signature ) ); ?>" class="income-image"><br> -->
				<?php // } ?>
				<span class="wlsm-font-bold"><strong><?php esc_html_e('Receiver\'s Signature', 'school-management'); ?></strong></span>
			</div>
		</div>
	</div>
</div>
