<?php
defined( 'ABSPATH' ) || die();

$page_url = WLSM_M_Staff_Accountant::get_concession_types_page_url();
?>

<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-file-invoice"></i>
				<?php esc_html_e( 'Concession Types', 'school-management' ); ?>
			</span>
			<span class="float-md-right">
			<?php if( WLSM_M_Role::can('add_concession_types')): ?>
				<a href="<?php echo esc_url( $page_url . '&action=save' ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-plus-square"></i>&nbsp;
					<?php echo esc_html__( 'Add New Concession Type', 'school-management' ); ?>
				</a>
			<?php endif; ?>
			</span>
		</div>
		<div class="wlsm-table-block">
			<table class="table table-hover table-bordered" id="wlsm-concession-types-table">
				<thead>
					<tr class="text-white bg-primary">
						<th scope="col"><?php esc_html_e( 'Concession Name', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Concession Type', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Value', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Class', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'school-management' ); ?></th>
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Action', 'school-management' ); ?></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>
