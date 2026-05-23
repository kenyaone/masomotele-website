<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Role.php';
$page_url = WLSM_M_Staff_General::get_inquiries_page_url();

$gdpr_enable = get_option( 'wlsm_gdpr_enable' );

$permissions = $current_school['permissions'];
?>
<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-envelope"></i>
				<?php esc_html_e( 'Inquiries', 'school-management' ); ?>
			</span>
			<span class="float-md-right">
				<?php if ( WLSM_M_Role::check_permission( array( 'add_inquiries' ), $permissions ) ) : ?>
				<a href="<?php echo esc_url( $page_url . '&action=save' ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-plus-square"></i>&nbsp;
					<?php esc_html_e( 'Add New Inquiry', 'school-management' ); ?>
				</a>
				<?php endif; ?>
			</span>
		</div>
		<div class="wlsm-table-block">
			<table class="table table-hover table-bordered" id="wlsm-inquiries-table">
				<thead>
					<tr class="text-white bg-primary">
						<th scope="col"><?php esc_html_e( 'Class', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Name', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Phone', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Email', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Message', 'school-management' ); ?></th>
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Date', 'school-management' ); ?></th>
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Follow Up Date', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'school-management' ); ?></th>
						<?php if ( $gdpr_enable ) { ?>
						<th scope="col"><?php esc_html_e( 'GDPR Agreed', 'school-management' ); ?></th>
						<?php } ?>
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Action', 'school-management' ); ?></th>
					</tr>
				</thead>
			</table>
			<?php require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/partials/export.php'; ?>
		</div>
	</div>
</div>
