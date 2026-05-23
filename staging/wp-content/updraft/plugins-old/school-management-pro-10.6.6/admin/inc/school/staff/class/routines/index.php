<?php
defined( 'ABSPATH' ) || die();

$page_url = WLSM_M_Staff_Class::get_timetable_page_url();
$current_user = WLSM_M_Role::can('add_timetable');
?>

<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-calendar-alt"></i>
				<?php esc_html_e( 'Class Timetables', 'school-management' ); ?>
			</span>
			<span class="float-md-right">
				<?php if ( $current_user ) : ?>
					<a href="<?php echo esc_url( $page_url . '&action=save' ); ?>" class="btn btn-sm btn-outline-light">
						<i class="fas fa-plus-square"></i>&nbsp;
						<?php echo esc_html__( 'Add New Routine', 'school-management' ); ?>
					</a>
				<?php endif; ?>
			</span>
		</div>
		<div class="wlsm-table-block">
			<table class="table table-hover table-bordered" id="wlsm-timetable-table">
				<thead>
					<tr class="text-white bg-primary">
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Class', 'school-management' ); ?></th>
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Section', 'school-management' ); ?></th>
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Action', 'school-management' ); ?></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>
