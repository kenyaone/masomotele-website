<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Examination.php';

$page_url = WLSM_M_Staff_Examination::get_academic_multi_group_report_page_url();
?>

<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<?php esc_html_e( 'Academic Multi Group Reports', 'school-management' ); ?>
			</span>
			<span class="float-md-right">
				<?php if ( WLSM_M_Role::can('add_exam_results') ) : ?>
					<a href="<?php echo esc_url( $page_url . '&action=save' ); ?>" class="btn btn-sm btn-outline-light">
						<i class="fas fa-plus-square"></i>&nbsp;
						<?php echo esc_html__( 'Add New Academic Multi Group Report', 'school-management' ); ?>
					</a>
				<?php endif; ?>
			</span>
		</div>
		<div class="wlsm-table-block">
			<table class="table table-hover table-bordered" id="wlsm-academic-multi-group-report-table">
				<thead>
						<tr class="text-white bg-primary">
							<th scope="col"><?php esc_html_e( 'Report Title', 'school-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Class', 'school-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Session', 'school-management' ); ?></th>
							<th scope="col" class="text-nowrap"><?php esc_html_e( 'Groups', 'school-management' ); ?></th>
							<th scope="col" class="text-nowrap"><?php esc_html_e( 'Action', 'school-management' ); ?></th>
						</tr>
				</thead>
			</table>
		</div>
	</div>
</div>
