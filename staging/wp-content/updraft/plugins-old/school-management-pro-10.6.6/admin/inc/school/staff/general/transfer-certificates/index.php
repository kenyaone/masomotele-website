<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';

$page_url = WLSM_M_Staff_General::get_transfer_certificate_page_url();
$permissions = $current_school['permissions'];
?>

<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-file-export"></i>
				<?php esc_html_e( 'Transfer Certificates', 'school-management' ); ?>
			</span>
			<span class="float-md-right">
			<?php if ( WLSM_M_Role::check_permission( array( 'issue_certificates' ), $permissions ) ) : ?>
					<a href="<?php echo esc_url( $page_url . '&action=save' ); ?>" class="btn btn-sm btn-outline-light">
						<i class="fas fa-plus-square"></i>&nbsp;
						<?php echo esc_html__( 'Issue New Transfer Certificate', 'school-management' ); ?>
					</a>
				<?php endif; ?>
			</span>
		</div>
		<div class="wlsm-table-block wlsm-form-section">
			<table class="table table-hover table-bordered" id="wlsm-transfer-certificates-table">
				<thead>
					<tr class="text-white bg-primary">
						<th scope="col"><?php esc_html_e( 'Certificate Number', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Student', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Admission Number', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Class', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Section', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Certificate', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Issued Date', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Student Status', 'school-management' ); ?></th>
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Action', 'school-management' ); ?></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>