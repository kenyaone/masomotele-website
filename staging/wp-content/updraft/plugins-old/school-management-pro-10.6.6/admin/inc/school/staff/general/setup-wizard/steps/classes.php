<?php
defined( 'ABSPATH' ) || die();

// Get available classes
$classes = WLSM_M_School::get_keyword_classes();

// Get currently assigned classes for this school
$assigned_classes = WLSM_M_Staff_Class::fetch_classes( $school_id );
$assigned_class_ids = wp_list_pluck( $assigned_classes, 'ID' );

// Setup nonce for assign classes action
$nonce_action = 'assign-classes-' . $school_id;
?>

<div class="wlsm-classes-step">
    <form id="wlsm-step-classes-form" class="wlsm-setup-step-form" data-step="classes" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">
        
        <!-- Hidden fields for the existing assign-classes action -->
        <?php $nonce = wp_create_nonce( $nonce_action ); ?>
        <input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">
        <input type="hidden" name="action" value="wlsm-assign-classes">
        <input type="hidden" name="school_id" value="<?php echo esc_attr( $school_id ); ?>">
        
        <div class="mb-4">
            <h5 class="text-primary mb-3">
                <i class="fas fa-chalkboard mr-2"></i>
                <?php esc_html_e( 'Select Classes for Your School', 'school-management' ); ?>
            </h5>
            <p class="text-muted">
                <?php esc_html_e( 'Choose which classes your school offers. You can modify this later from the settings.', 'school-management' ); ?>
            </p>
        </div>

    <?php if ( $classes ) : ?>
    <div class="row">
        <?php foreach ( $classes as $class ) : ?>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="class-item p-3 border rounded bg-white h-100">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" 
                           class="custom-control-input" 
                           id="class_<?php echo esc_attr( $class->ID ); ?>" 
                           name="classes[]" 
                           value="<?php echo esc_attr( $class->ID ); ?>"
                           <?php checked( in_array( $class->ID, $assigned_class_ids ) ); ?>>
                    <label class="custom-control-label" for="class_<?php echo esc_attr( $class->ID ); ?>">
                        <h6 class="mb-1"><?php echo esc_html( WLSM_M_Class::get_label_text( $class->label ) ); ?></h6>
                        <?php if ( !empty( $class->code ) ) : ?>
                        <small class="text-muted"><?php esc_html_e( 'Code:', 'school-management' ); ?> <?php echo esc_html( $class->code ); ?></small>
                        <?php endif; ?>
                    </label>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Selection Options -->
    <div class="mt-4 p-3 bg-light rounded">
        <h6 class="mb-3"><?php esc_html_e( 'Quick Selection:', 'school-management' ); ?></h6>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary btn-sm" id="select-all-classes">
                <i class="fas fa-check-double mr-1"></i>
                <?php esc_html_e( 'Select All', 'school-management' ); ?>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-all-classes">
                <i class="fas fa-times mr-1"></i>
                <?php esc_html_e( 'Clear All', 'school-management' ); ?>
            </button>
        </div>
    </div>

    <!-- Validation Alert -->
    <div class="alert alert-warning mt-3" id="classes-validation-alert" style="display: none;">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?php esc_html_e( 'Please select at least one class to continue.', 'school-management' ); ?>
    </div>

    <?php else : ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle mr-2"></i>
        <?php esc_html_e( 'No classes are available. Please contact your administrator to add classes first.', 'school-management' ); ?>
    </div>
    <?php endif; ?>
</form>
</div>
