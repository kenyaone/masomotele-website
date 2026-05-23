<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Role.php';

global $wpdb;

$page_url = WLSM_M_Staff_General::get_roles_page_url();

$school_id = $current_school['id'];

$permission_list = WLSM_M_Role::get_permissions();

$role = NULL;

$nonce_action = 'add-role';

$name        = '';
$permissions = array();

if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
	$id   = absint( $_GET['id'] );
	$role = WLSM_M_Staff_General::fetch_role( $school_id, $id );

	if ( $role ) {
		$nonce_action = 'edit-role-' . $role->ID;

		$name = $role->name;

		if ( $role->permissions ) {
			$permissions = $role->permissions;
			if ( is_serialized( $permissions ) ) {
				$permissions = unserialize( $permissions );
			}
		}
	}
}
?>
<div class="row">
	<div class="col-md-12">
		<div class="mt-3 text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading-box">
				<span class="wlsm-section-heading">
					<?php
					if ( $role ) {
						printf(
							wp_kses(
								/* translators: %s: role name */
								__( 'Edit Role: %s', 'school-management' ),
								array(
									'span' => array( 'class' => array() )
								)
							),
							esc_html( $name )
						);
					} else {
						esc_html_e( 'Add New Role', 'school-management' );
					}
					?>
				</span>
			</span>
			<span class="float-md-right">
				<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-user-tag"></i>&nbsp;
					<?php esc_html_e( 'View All', 'school-management' ); ?>
				</a>
			</span>
		</div>
		<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" id="wlsm-save-role-form">

			<?php $nonce = wp_create_nonce( $nonce_action ); ?>
			<input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">

			<input type="hidden" name="action" value="wlsm-save-role">

			<?php if ( $role ) { ?>
			<input type="hidden" name="role_id" value="<?php echo esc_attr( $role->ID ); ?>">
			<?php } ?>

			<!-- Role -->
			<div class="wlsm-form-section">
				<div class="row">
					<div class="col-md-12">
						<div class="wlsm-form-sub-heading wlsm-font-bold">
							<?php esc_html_e( 'Role', 'school-management' ); ?>
						</div>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<input type="text" name="name" class="form-control" id="wlsm_name" placeholder="<?php esc_attr_e( 'Enter role name', 'school-management' ); ?>" value="<?php echo esc_attr( $name ); ?>">
					</div>
				</div>
			</div>

			<!-- Permissions -->
			<div class="wlsm-form-section">
				<div class="row">
					<div class="col-md-12">
						<div class="wlsm-form-sub-heading wlsm-font-bold">
							<?php esc_html_e( 'Permissions', 'school-management' ); ?>
						</div>
					</div>
				</div>

				<div class="form-row mb-3">
					<div class="col-md-8">
						<input type="text" class="form-control" id="wlsm_permission_search" placeholder="<?php esc_attr_e( 'Search permissions...', 'school-management' ); ?>">
					</div>
					<div class="col-md-4 text-right">
						<button type="button" class="btn btn-sm btn-outline-primary" id="wlsm_expand_all_permissions"><?php esc_html_e( 'Expand All', 'school-management' ); ?></button>
						<button type="button" class="btn btn-sm btn-outline-secondary" id="wlsm_collapse_all_permissions"><?php esc_html_e( 'Collapse All', 'school-management' ); ?></button>
					</div>
				</div>

				<div class="form-row" id="wlsm_permissions_container">
					<?php
					$permission_groups = WLSM_M_Role::get_grouped_permissions();
					foreach ( $permission_groups as $group_name => $permissions_in_group ) {
						$group_key = sanitize_title( $group_name );
						$total_permissions = count( $permissions_in_group );
						?>
						<div class="col-md-6 col-lg-4 mb-2 wlsm-permission-group">
							<div class="border rounded">
								<div class="p-2 bg-light d-flex justify-content-between align-items-center wlsm-permission-group-header" style="cursor: pointer;" data-toggle="collapse" data-target="#collapse_<?php echo esc_attr( $group_key ); ?>">
									<div class="d-flex align-items-center">
										<span class="wlsm-font-bold text-dark mr-2">
											<?php echo esc_html( $group_name ); ?>
										</span>
										<span class="badge badge-primary wlsm-permission-count" data-total="<?php echo esc_attr( $total_permissions ); ?>">0/<?php echo esc_html( $total_permissions ); ?></span>
									</div>
									<div class="d-flex align-items-center">
										<div class="form-check form-check-inline mr-3" onclick="event.stopPropagation();">
											<input class="form-check-input wlsm-select-all-permission" type="checkbox" id="wlsm_select_all_<?php echo esc_attr( $group_key ); ?>" data-group="<?php echo esc_attr( $group_key ); ?>">
											<label class="form-check-label wlsm-font-bold text-secondary" for="wlsm_select_all_<?php echo esc_attr( $group_key ); ?>" style="font-size: 0.85rem;">
												<?php esc_html_e( 'Select All', 'school-management' ); ?>
											</label>
										</div>
										<i class="fas fa-chevron-down wlsm-group-toggle-icon"></i>
									</div>
								</div>
								<div id="collapse_<?php echo esc_attr( $group_key ); ?>" class="collapse">
									<div class="p-2">
										<div class="row">
											<?php
											foreach ( $permissions_in_group as $key => $value ) {
												?>
												<div class="col-12 mb-1 wlsm-permission-item">
													<div class="form-check pl-0">
														<input <?php checked( in_array( $key, $permissions ), true, true ); ?> class="form-check-input mt-1 wlsm-group-permission wlsm-group-<?php echo esc_attr( $group_key ); ?>" type="checkbox" name="permission[]" id="wlsm_role_permission_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>" data-group="<?php echo esc_attr( $group_key ); ?>" style="position: relative; margin-left: 0;">
														<label class="form-check-label wlsm-font-bold ml-2" for="wlsm_role_permission_<?php echo esc_attr( $key ); ?>" style="font-size: 0.9rem; line-height: 1.5;">
															<?php echo esc_html( $value ); ?>
														</label>
													</div>
												</div>
												<?php
											}
											?>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
					}
					?>
			</div>

			<div class="row mt-2">
				<div class="col-md-12 text-center">
					<button type="submit" class="btn btn-primary" id="wlsm-save-role-btn">
						<?php
						if ( $role ) {
							?>
							<i class="fas fa-save"></i>&nbsp;
							<?php
							esc_html_e( 'Update Role', 'school-management' );
						} else {
							?>
							<i class="fas fa-plus-square"></i>&nbsp;
							<?php
							esc_html_e( 'Add New Role', 'school-management' );
						}
						?>
					</button>
				</div>
			</div>

		</form>
	</div>
</div>
