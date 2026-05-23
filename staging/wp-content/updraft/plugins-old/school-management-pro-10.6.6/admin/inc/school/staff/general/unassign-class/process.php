<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Role.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_School.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Log.php';

$current_user = WLSM_M_Role::can( 'manage_classes' );

if ( ! $current_user ) {
	die();
}

WLSM_Helper::check_demo();

$school_id = $current_school['id'];
$permissions = $current_school['permissions'];

// Check if form was submitted
if ( ! isset( $_POST['wlsm-unassign-class-nonce'] ) || ! wp_verify_nonce( $_POST['wlsm-unassign-class-nonce'], 'wlsm-unassign-class' ) ) {
	wp_die( esc_html__( 'Security check failed. Please try again.', 'school-management' ) );
}

$class_ids = array();
$confirm_unassign = false;

if ( isset( $_POST['class_ids'] ) && is_array( $_POST['class_ids'] ) ) {
	$class_ids = array_map( 'absint', $_POST['class_ids'] );
	$class_ids = array_filter( $class_ids );
}

if ( isset( $_POST['confirm_unassign'] ) ) {
	$confirm_unassign = true;
}

$success = false;
$errors = array();

if ( empty( $class_ids ) ) {
	$errors[] = esc_html__( 'Please select at least one class to unassign.', 'school-management' );
}

if ( ! $confirm_unassign ) {
	$errors[] = esc_html__( 'Please confirm that you understand this action cannot be undone.', 'school-management' );
}

if ( empty( $errors ) ) {
	try {
		global $wpdb;

		$wpdb->query( 'START TRANSACTION' );

		$unassigned_classes = array();

		foreach ( $class_ids as $class_id ) {
			// Verify class is assigned to this school
			$assigned = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM " . WLSM_CLASS_SCHOOL . " WHERE school_id = %d AND class_id = %d",
				$school_id,
				$class_id
			) );

			if ( $assigned ) {
				// Get class details for logging
				$class = $wpdb->get_row( $wpdb->prepare(
					"SELECT label FROM " . WLSM_CLASSES . " WHERE ID = %d",
					$class_id
				) );

				// Remove class assignment
				$deleted = $wpdb->delete(
					WLSM_CLASS_SCHOOL,
					array(
						'school_id' => $school_id,
						'class_id'  => $class_id,
					),
					array( '%d', '%d' )
				);

				if ( $deleted ) {
					$unassigned_classes[] = $class->label;

					// Log the action
					WLSM_Log::save(
						$school_id,
						'class_unassigned',
						sprintf( esc_html__( 'Class "%s" was unassigned from the school by user ID: %d', 'school-management' ), $class->label, get_current_user_id() ),
						'class_management'
					);
				}
			}
		}

		if ( ! empty( $unassigned_classes ) ) {
			$wpdb->query( 'COMMIT' );
			$success = true;
			$success_message = sprintf(
				esc_html__( 'Successfully unassigned %d class(es): %s', 'school-management' ),
				count( $unassigned_classes ),
				implode( ', ', $unassigned_classes )
			);
		} else {
			$wpdb->query( 'ROLLBACK' );
			$errors[] = esc_html__( 'No classes were unassigned. They may have already been unassigned.', 'school-management' );
		}

	} catch ( Exception $exception ) {
		$wpdb->query( 'ROLLBACK' );
		$errors[] = esc_html__( 'An error occurred while unassigning classes. Please try again.', 'school-management' );
	}
}

$page_url = admin_url( 'admin.php?page=' . WLSM_MENU_STAFF_UNASSIGN_CLASS );
?>

<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-unlink"></i>
				<?php esc_html_e( 'Unassign Classes - Result', 'school-management' ); ?>
			</span>
		</div>

		<div class="wlsm-form-section">
			<?php if ( $success ) : ?>
				<div class="alert alert-success">
					<i class="fas fa-check-circle"></i>
					<?php echo esc_html( $success_message ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $errors ) ) : ?>
				<div class="alert alert-danger">
					<i class="fas fa-exclamation-triangle"></i>
					<ul class="mb-0">
						<?php foreach ( $errors as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="text-center">
				<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-primary">
					<i class="fas fa-arrow-left"></i>&nbsp;
					<?php esc_html_e( 'Back to Unassign Classes', 'school-management' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>
