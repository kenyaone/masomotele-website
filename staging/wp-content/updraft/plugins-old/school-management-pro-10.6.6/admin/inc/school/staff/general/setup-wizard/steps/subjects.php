<?php
defined( 'ABSPATH' ) || die();

// Get assigned classes for this school
$assigned_classes = WLSM_M_Staff_Class::fetch_classes( $school_id );

// Get available subject types
$subject_types = WLSM_M_Staff_Class::fetch_subject_type();
if ( empty( $subject_types ) ) {
    $subject_types = array( 'General', 'Core', 'Elective', 'Optional' );
}

// Setup nonce for save subject action
$nonce_action = 'add-subject';
?>

<div class="wlsm-subjects-step">
    <!-- Step Header -->
    <div class="step-header mb-4">
        <h5 class="step-title text-primary mb-3">
            <i class="fas fa-book mr-2"></i>
            <?php esc_html_e( 'Add Subjects for Your Classes', 'school-management' ); ?>
        </h5>
        <p class="step-description text-muted">
            <?php esc_html_e( 'Add subjects for each class. You can use quick-add buttons for common subjects or add custom ones. All subjects can be modified later from the subjects management section.', 'school-management' ); ?>
        </p>
    </div>

    <?php if ( $assigned_classes ) : ?>
    
    <!-- Subject Management Form -->
    <form id="wlsm-step-subjects-form" class="wlsm-setup-step-form" data-step="subjects" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">
        
        <!-- Hidden fields -->
        <?php $nonce = wp_create_nonce( $nonce_action ); ?>
        <input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">
        <input type="hidden" name="action" value="wlsm-save-subject">
        
        <!-- Global Quick Add Section -->
        <div class="global-quick-add mb-4 p-3 bg-light border rounded">
            <div class="mb-3">
                <h6 class="mb-2">
                    <i class="fas fa-magic mr-2 text-info"></i>
                    <?php esc_html_e( 'Quick Add Common Subjects', 'school-management' ); ?>
                </h6>
                <p class="text-muted small mb-0">
                    <?php esc_html_e( 'Click to add common subjects to all classes at once:', 'school-management' ); ?>
                </p>
            </div>
            <div class="quick-add-buttons">
                <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 quick-add-all" data-subject="Mathematics">
                    <i class="fas fa-calculator mr-1"></i> Mathematics
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 quick-add-all" data-subject="English">
                    <i class="fas fa-language mr-1"></i> English
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 quick-add-all" data-subject="Science">
                    <i class="fas fa-flask mr-1"></i> Science
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 quick-add-all" data-subject="Social Studies">
                    <i class="fas fa-globe mr-1"></i> Social Studies
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 quick-add-all" data-subject="Physical Education">
                    <i class="fas fa-running mr-1"></i> Physical Education
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 quick-add-all" data-subject="Art">
                    <i class="fas fa-palette mr-1"></i> Art
                </button>
            </div>
        </div>
        
        <!-- Subjects by Class -->
        <div class="subjects-by-class">
            <?php foreach ( $assigned_classes as $index => $class ) : ?>
            <div class="class-subjects-section mb-4 p-3 border rounded" data-class-id="<?php echo esc_attr( $class->ID ); ?>">
                
                <!-- Class Header -->
                <div class="class-header mb-3 pb-2 border-bottom">
                    <h6 class="mb-1 d-flex align-items-center justify-content-between">
                        <span>
                            <i class="fas fa-chalkboard mr-2 text-primary"></i>
                            <?php echo esc_html( WLSM_M_Class::get_label_text( $class->label ) ); ?>
                        </span>
                        <span class="badge badge-secondary subject-count">0 subjects</span>
                    </h6>
                </div>
                
                <!-- Class-specific Quick Add -->
                <div class="class-quick-add mb-3 p-2 bg-light rounded">
                    <small class="text-muted d-block mb-2"><?php esc_html_e( 'Quick add for this class:', 'school-management' ); ?></small>
                    <div>
                        <button type="button" class="btn btn-outline-info btn-sm mr-1 mb-1 quick-add-single" 
                                data-class-id="<?php echo esc_attr( $class->ID ); ?>" 
                                data-subject="Mathematics">Math</button>
                        <button type="button" class="btn btn-outline-info btn-sm mr-1 mb-1 quick-add-single" 
                                data-class-id="<?php echo esc_attr( $class->ID ); ?>" 
                                data-subject="English">English</button>
                        <button type="button" class="btn btn-outline-info btn-sm mr-1 mb-1 quick-add-single" 
                                data-class-id="<?php echo esc_attr( $class->ID ); ?>" 
                                data-subject="Science">Science</button>
                        <button type="button" class="btn btn-outline-info btn-sm mr-1 mb-1 quick-add-single" 
                                data-class-id="<?php echo esc_attr( $class->ID ); ?>" 
                                data-subject="History">History</button>
                        <button type="button" class="btn btn-outline-info btn-sm mr-1 mb-1 quick-add-single" 
                                data-class-id="<?php echo esc_attr( $class->ID ); ?>" 
                                data-subject="Geography">Geography</button>
                    </div>
                </div>
                
                <!-- Subject Items Container -->
                <div class="subjects-container" data-class-id="<?php echo esc_attr( $class->ID ); ?>">
                    <!-- Initial empty subject item -->
                    <div class="subject-item mb-2 p-2 border rounded">
                        <div class="row">
                            <div class="col-sm-6">
                                <input type="text" 
                                       class="form-control form-control-sm subject-name" 
                                       placeholder="<?php esc_attr_e( 'Subject name', 'school-management' ); ?>"
                                       data-class-id="<?php echo esc_attr( $class->ID ); ?>">
                            </div>
                            <div class="col-sm-3">
                                <input type="text" 
                                       class="form-control form-control-sm subject-code" 
                                       placeholder="<?php esc_attr_e( 'Code (optional)', 'school-management' ); ?>">
                            </div>
                            <div class="col-sm-2">
                                <select class="form-control form-control-sm subject-type">
                                    <?php foreach ( $subject_types as $type ) : ?>
                                        <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $type, 'General' ); ?>>
                                            <?php echo esc_html( $type ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-subject" title="<?php esc_attr_e( 'Remove subject', 'school-management' ); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="class-actions mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary add-subject-btn" 
                            data-class-id="<?php echo esc_attr( $class->ID ); ?>">
                        <i class="fas fa-plus mr-1"></i> <?php esc_html_e( 'Add Another Subject', 'school-management' ); ?>
                    </button>
                    
                    <button type="button" class="btn btn-sm btn-outline-warning clear-all-subjects ml-2" 
                            data-class-id="<?php echo esc_attr( $class->ID ); ?>">
                        <i class="fas fa-trash mr-1"></i> <?php esc_html_e( 'Clear All', 'school-management' ); ?>
                    </button>
                </div>
                
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Validation Alerts -->
        <div class="alerts-container">
            <div class="alert alert-warning" id="subjects-validation-alert" style="display: none;">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span id="validation-message"><?php esc_html_e( 'Please add at least one subject for each class.', 'school-management' ); ?></span>
            </div>
            
            <div class="alert alert-info" id="subjects-info-alert" style="display: none;">
                <i class="fas fa-info-circle mr-2"></i>
                <span id="info-message"></span>
            </div>
        </div>
        
    </form>

    <?php else : ?>
    
    <!-- No Classes Warning -->
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?php esc_html_e( 'No classes found. Please assign classes first before adding subjects.', 'school-management' ); ?>
        <div class="mt-2">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wlsm-setup-wizard&step=classes' ) ); ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> <?php esc_html_e( 'Go Back to Classes', 'school-management' ); ?>
            </a>
        </div>
    </div>
    
    <?php endif; ?>
</div>
