<?php
defined( 'ABSPATH' ) || die();

// Get existing student types
$student_types = WLSM_Helper::student_type( $school_id );

// Setup nonce for save student type action
$nonce_action = 'add-student-type';
?>

<div class="wlsm-student-types-step">
    <!-- Step Header -->
    <div class="step-header mb-4">
        <h5 class="step-title text-primary mb-3">
            <i class="fas fa-users mr-2"></i>
            <?php esc_html_e( 'Define Student Types', 'school-management' ); ?>
        </h5>
        <p class="step-description text-muted">
            <?php esc_html_e( 'Create different student categories to help organize your student body. Student types help you categorize students based on enrollment status, residence, fee structure, or other criteria.', 'school-management' ); ?>
        </p>
    </div>

    <!-- Student Type Management Form -->
    <form id="wlsm-step-student-types-form" class="wlsm-setup-step-form" data-step="student_types" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">
        
        <!-- Hidden fields -->
        <?php $nonce = wp_create_nonce( $nonce_action ); ?>
        <input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">
        <input type="hidden" name="action" value="wlsm-save-student-type">

    <div class="row">
        <div class="col-md-8">
            <!-- Quick Add Common Types -->
            <div class="global-quick-add mb-4 p-3 bg-light border rounded">
                <div class="mb-3">
                    <h6 class="mb-2">
                        <i class="fas fa-magic mr-2 text-info"></i>
                        <?php esc_html_e( 'Quick Add Common Student Types', 'school-management' ); ?>
                    </h6>
                    <p class="text-muted small mb-0">
                        <?php esc_html_e( 'Click to quickly add common student type categories:', 'school-management' ); ?>
                    </p>
                </div>
                <div class="quick-add-buttons">
                    <?php 
                    $common_types = array(
                        array( 'label' => __( 'Regular', 'school-management' ), 'icon' => 'fas fa-user' ),
                        array( 'label' => __( 'Day Scholar', 'school-management' ), 'icon' => 'fas fa-sun' ),
                        array( 'label' => __( 'Hostel', 'school-management' ), 'icon' => 'fas fa-bed' ),
                        array( 'label' => __( 'Transport', 'school-management' ), 'icon' => 'fas fa-bus' ),
                        array( 'label' => __( 'Scholarship', 'school-management' ), 'icon' => 'fas fa-graduation-cap' ),
                        array( 'label' => __( 'Sports Quota', 'school-management' ), 'icon' => 'fas fa-trophy' ),
                        array( 'label' => __( 'Management Quota', 'school-management' ), 'icon' => 'fas fa-briefcase' ),
                        array( 'label' => __( 'NRI', 'school-management' ), 'icon' => 'fas fa-globe' ),
                    );
                    foreach ( $common_types as $type ) :
                    ?>
                    <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 quick-add-student-type" data-type="<?php echo esc_attr( $type['label'] ); ?>">
                        <i class="<?php echo esc_attr( $type['icon'] ); ?> mr-1"></i> <?php echo esc_html( $type['label'] ); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Student Types Container -->
            <div class="student-types-management mb-4 p-3 border rounded">
                <div class="section-header mb-3 pb-2 border-bottom">
                    <h6 class="mb-1 d-flex align-items-center justify-content-between">
                        <span>
                            <i class="fas fa-plus mr-2 text-success"></i>
                            <?php esc_html_e( 'Add Student Types', 'school-management' ); ?>
                        </span>
                        <span class="badge badge-secondary student-type-count">0 types</span>
                    </h6>
                </div>
                
                <div id="student-types-container">
                    <!-- Initial empty student type item -->
                    <div class="student-type-item mb-2 p-2 border rounded">
                        <div class="row">
                            <div class="col-sm-10">
                                <input type="text" 
                                       name="student_types[]" 
                                       class="form-control form-control-sm student-type-name" 
                                       placeholder="<?php esc_attr_e( 'Enter student type name', 'school-management' ); ?>">
                            </div>
                            <div class="col-sm-2">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-student-type" title="<?php esc_attr_e( 'Remove type', 'school-management' ); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="type-actions mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary add-student-type-btn">
                        <i class="fas fa-plus mr-1"></i> <?php esc_html_e( 'Add Another Type', 'school-management' ); ?>
                    </button>
                    
                    <button type="button" class="btn btn-sm btn-outline-warning clear-all-types ml-2">
                        <i class="fas fa-trash mr-1"></i> <?php esc_html_e( 'Clear All', 'school-management' ); ?>
                    </button>
                </div>
            </div>
            
            <!-- Validation Alerts -->
            <div class="alerts-container">
                <div class="alert alert-warning" id="student-types-validation-alert" style="display: none;">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span id="validation-message"><?php esc_html_e( 'Please add at least one student type to continue.', 'school-management' ); ?></span>
                </div>
                
                <div class="alert alert-info" id="student-types-info-alert" style="display: none;">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span id="info-message"></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <?php if ( $student_types ) : ?>
            <!-- Existing Student Types -->
            <div class="existing-types mb-4 p-3 bg-success text-white border rounded">
                <h6 class="mb-3">
                    <i class="fas fa-check mr-2"></i>
                    <?php esc_html_e( 'Existing Student Types', 'school-management' ); ?>
                </h6>
                <div class="existing-types-list">
                    <?php foreach ( $student_types as $type ) : ?>
                    <div class="existing-type-item mb-2 p-2 bg-white text-dark rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><?php echo esc_html( $type->label ); ?></span>
                            <span class="badge badge-success">
                                <i class="fas fa-check"></i>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Help Section -->
            <div class="help-section p-3 bg-light border rounded">
                <h6 class="mb-3">
                    <i class="fas fa-info-circle mr-2 text-info"></i>
                    <?php esc_html_e( 'What are Student Types?', 'school-management' ); ?>
                </h6>
                <div class="help-content">
                    <p class="small text-muted mb-2">
                        <?php esc_html_e( 'Student types help you categorize students based on their enrollment status, residence, fee structure, or any other criteria that\'s important for your school management.', 'school-management' ); ?>
                    </p>
                    <div class="examples">
                        <strong class="small text-dark"><?php esc_html_e( 'Examples:', 'school-management' ); ?></strong>
                        <ul class="small text-muted mb-0 mt-1">
                            <li><?php esc_html_e( 'Day Scholar vs Hostel', 'school-management' ); ?></li>
                            <li><?php esc_html_e( 'Regular vs Scholarship', 'school-management' ); ?></li>
                            <li><?php esc_html_e( 'Local vs Out of State', 'school-management' ); ?></li>
                            <li><?php esc_html_e( 'Transport vs Non-Transport', 'school-management' ); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    </form>
</div>
