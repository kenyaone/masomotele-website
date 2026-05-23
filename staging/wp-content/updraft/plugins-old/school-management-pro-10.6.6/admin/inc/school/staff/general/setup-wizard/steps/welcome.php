<?php
defined( 'ABSPATH' ) || die();
?>

<div class="wlsm-welcome-step">
    <div class="text-center mb-4">
        <div class="welcome-icon mb-3">
            <i class="fas fa-graduation-cap fa-4x text-primary"></i>
        </div>
        <h2 class="text-primary mb-3"><?php esc_html_e( 'Welcome to School Management Setup!', 'school-management' ); ?></h2>
        <p class="lead text-muted">
            <?php esc_html_e( 'This wizard will help you configure your school in just a few simple steps.', 'school-management' ); ?>
        </p>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Setup Overview Section -->
            <div class="setup-section border border-primary rounded p-4 mb-4">
                <div class="section-header bg-primary text-white p-3 rounded-top mb-3" style="margin: -1rem -1rem 1rem -1rem;">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks mr-2"></i>
                        <?php esc_html_e( 'What We\'ll Set Up', 'school-management' ); ?>
                    </h5>
                </div>
                <div class="section-body">
                    <div class="row">
                        <?php
                        $features = array(
                            array(
                                'icon' => 'fas fa-chalkboard',
                                'title' => __( 'Classes & Sections', 'school-management' ),
                                'description' => __( 'Configure your school classes and sections', 'school-management' )
                            ),
                            array(
                                'icon' => 'fas fa-book',
                                'title' => __( 'Subjects', 'school-management' ),
                                'description' => __( 'Add subjects for each class', 'school-management' )
                            ),
                            array(
                                'icon' => 'fas fa-users',
                                'title' => __( 'Student Types', 'school-management' ),
                                'description' => __( 'Define different student categories', 'school-management' )
                            ),
                            array(
                                'icon' => 'fas fa-money-bill-wave',
                                'title' => __( 'Fee Structure', 'school-management' ),
                                'description' => __( 'Set up fee types and amounts', 'school-management' )
                            ),
                            // array(
                            //     'icon' => 'fas fa-cog',
                            //     'title' => __( 'General Settings', 'school-management' ),
                            //     'description' => __( 'Configure basic school preferences', 'school-management' )
                            // ),
                            array(
                                'icon' => 'fas fa-user-plus',
                                'title' => __( 'Registration Settings', 'school-management' ),
                                'description' => __( 'Customize student registration forms', 'school-management' )
                            )
                        );

                        foreach ( $features as $index => $feature ) :
                            if ( $index % 2 === 0 && $index > 0 ) echo '</div><div class="row">';
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon mr-3">
                                    <i class="<?php echo esc_attr( $feature['icon'] ); ?> fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1"><?php echo esc_html( $feature['title'] ); ?></h6>
                                    <small class="text-muted"><?php echo esc_html( $feature['description'] ); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Ready to Start -->
            <div class="text-center mt-4">
                <div class="alert alert-success">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?php esc_html_e( 'This process will take approximately 5-10 minutes to complete.', 'school-management' ); ?>
                </div>
            </div>
        </div>
    </div>
</div>
