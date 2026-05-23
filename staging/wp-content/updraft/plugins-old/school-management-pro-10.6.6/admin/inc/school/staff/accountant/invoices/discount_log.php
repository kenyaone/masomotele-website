<?php
defined( 'ABSPATH' ) || die();

?>

<!-- Discount Log -->
<div class="wlsm-form-section wlsm-discount-log">
    <div class="row">
        <div class="col-md-12">
            <div class="wlsm-form-sub-heading wlsm-font-bold">
                <?php esc_html_e('Discount Log', 'school-management'); ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
           			<table class="table table-hover table-bordered" id="wlsm-discount-log-table" data-invoice="<?php echo esc_attr($invoice->ID); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('discount-log-' . $invoice->ID)); ?>">
				<thead>
					<tr class="text-white bg-primary">
						<th><?php esc_html_e('Old Amount', 'school-management'); ?></th>
						<th><?php esc_html_e('New Amount', 'school-management'); ?></th>
						<th><?php esc_html_e('Change Note', 'school-management'); ?></th>
						<th><?php esc_html_e('Changed By', 'school-management'); ?></th>
						<th class="text-nowrap"><?php esc_html_e('Date', 'school-management'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$discount_changes = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT dc.*, u.user_nicename as staff_name FROM " . WLSM_INVOICE_DISCOUNT_CHANGES . " dc
							JOIN " . WLSM_STAFF . " s ON s.ID = dc.staff_id
							JOIN " . $wpdb->users . " u ON u.ID = s.user_id
							WHERE dc.invoice_id = %d ORDER BY dc.change_date DESC",
							$invoice->ID
						)
					);

					if ($discount_changes) {
						foreach ($discount_changes as $change) {
							?>
							<tr>
								<td><?php echo esc_html(WLSM_Config::get_money_text($change->old_amount, $school_id)); ?></td>
								<td><?php echo esc_html(WLSM_Config::get_money_text($change->new_amount, $school_id)); ?></td>
								<td><?php echo esc_html($change->change_note); ?></td>
								<td><?php echo esc_html($change->staff_name); ?></td>
								<td><?php echo esc_html(WLSM_Config::get_date_text($change->change_date)); ?></td>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<td colspan="5" class="text-center"><?php esc_html_e('No data available', 'school-management'); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
        </div>
    </div>
</div>
