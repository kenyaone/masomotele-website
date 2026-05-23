<?php
defined( 'ABSPATH' ) || die();
require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/general/setup-wizard/route.php';
// Note: Variables are already defined in route.php before including this file
?>

<div class="wlsm container-fluid">
    <div class="row mt-2">
        <div class="col-12">
            <!-- Header -->
            <div class="wlsm-setup-wizard-header bg-primary text-white py-4 mb-4 rounded">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-12 text-center">
                            <h1 class="h3 mb-0 text-white">
                                <i class="fas fa-magic mr-2"></i>
                                <?php esc_html_e( 'School Setup Wizard', 'school-management' ); ?>
                            </h1>
                            <p class="mb-0 opacity-75">
                                <?php esc_html_e( 'Configure your school in just a few simple steps', 'school-management' ); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="wlsm-setup-progress mb-4">
                <div class="progress" style="height: 14px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                         role="progressbar"
                         style="width: <?php echo esc_attr( $progress_percentage ); ?>%"
                         aria-valuenow="<?php echo esc_attr( $progress_percentage ); ?>"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-muted">
                        <?php printf( esc_html__( 'Step %d of %d', 'school-management' ), $current_step_index + 1, $total_steps ); ?>
                    </small>
                    <small class="text-muted">
                        <?php printf( esc_html__( '%d%% Complete', 'school-management' ), intval( $progress_percentage ) ); ?>
                    </small>
                </div>
            </div>

            <div class="row">
                <!-- Steps Sidebar -->
                <div class="col-md-4 col-lg-3">
                    <div class="wlsm-setup-sidebar bg-white p-4 rounded shadow-sm">
                        <div class="sidebar-header mb-4">
                            <h6 class="mb-0 text-primary font-weight-bold">
                                <i class="fas fa-list-ol mr-2"></i>
                                <?php esc_html_e( 'Setup Steps', 'school-management' ); ?>
                            </h6>
                        </div>
                        <div class="steps-list">
                            <?php foreach ( $setup_steps as $step_key => $step_data ) :
                                $is_current = ( $step_key === $current_step );
                                $is_completed_by_navigation = ( array_search( $step_key, array_keys( $setup_steps ) ) < $current_step_index );
                                $is_completed_by_data = isset( $completion_status[ $step_key ] ) && $completion_status[ $step_key ];
                                $step_number = array_search( $step_key, array_keys( $setup_steps ) ) + 1;
                                $step_url = add_query_arg( 'step', $step_key );

                                // Determine step status priority: current > completed by data > completed by navigation > incomplete
                                $step_status_class = '';
                                $step_icon = '';
                                $step_text_class = '';

                                if ( $is_current ) {
                                    $step_status_class = 'bg-primary text-white';
                                    $step_icon = '<span class="badge badge-light">' . esc_html( $step_number ) . '</span>';
                                    $step_text_class = 'text-white';
                                } elseif ( $is_completed_by_data ) {
                                    $step_status_class = 'bg-success text-white';
                                    $step_icon = '<i class="fas fa-check-circle text-white"></i>';
                                    $step_text_class = 'text-white';
                                } elseif ( $is_completed_by_navigation ) {
                                    $step_status_class = 'bg-light';
                                    $step_icon = '<i class="fas fa-check text-secondary"></i>';
                                    $step_text_class = 'text-dark';
                                } else {
                                    $step_status_class = 'bg-white border';
                                    $step_icon = '<span class="badge badge-secondary">' . esc_html( $step_number ) . '</span>';
                                    $step_text_class = 'text-secondary';
                                }
                            ?>
                            <a href="<?php echo esc_url( $step_url ); ?>" class="step-item-link text-decoration-none" title="<?php printf( esc_attr__( 'Navigate to %s', 'school-management' ), $step_data['title'] ); ?>">
                                <div class="step-item py-3 px-3 mb-2 rounded <?php echo esc_attr( $step_status_class ); ?> step-clickable"
                                     data-step="<?php echo esc_attr( $step_key ); ?>"
                                     style="transition: all 0.3s ease; cursor: pointer;">
                                    <div class="d-flex align-items-center">
                                        <div class="step-number mr-3">
                                            <?php echo $step_icon; ?>
                                        </div>
                                        <div class="step-info flex-grow-1">
                                            <div class="step-title font-weight-bold <?php echo esc_attr( $step_text_class ); ?>">
                                                <i class="<?php echo esc_attr( $step_data['icon'] ); ?> mr-2"></i>
                                                <?php echo esc_html( $step_data['title'] ); ?>
                                                <?php if ( $step_data['required'] ) : ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                                <?php if ( $is_completed_by_data && ! $is_current ) : ?>
                                                    <i class="fas fa-check-double ml-1" style="font-size: 0.8em;" title="<?php esc_attr_e( 'Step completed with data', 'school-management' ); ?>"></i>
                                                <?php elseif ( ! $is_current ) : ?>
                                                    <i class="fas fa-external-link-alt ml-1" style="font-size: 0.7em; opacity: 0.6;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <small class="step-description <?php echo $is_current ? 'text-white-50' : ( $is_completed_by_data ? 'text-white-50' : 'text-muted' ); ?>">
                                                <?php echo esc_html( $step_data['description'] ); ?>
                                                <?php if ( $is_completed_by_data && ! $is_current ) : ?>
                                                    <span class="ml-1 badge badge-sm badge-light">✓ <?php esc_html_e( 'Completed', 'school-management' ); ?></span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-8 col-lg-9">
                    <div class="wlsm-setup-content">
                        <div class="content-wrapper bg-white rounded shadow-sm">
                            <div class="content-header p-4 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="step-icon mr-3">
                                        <i class="<?php echo esc_attr( $setup_steps[ $current_step ]['icon'] ); ?> fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-1"><?php echo esc_html( $setup_steps[ $current_step ]['title'] ); ?></h4>
                                        <p class="mb-0 text-muted"><?php echo esc_html( $setup_steps[ $current_step ]['description'] ); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="content-body p-4">
                                <!-- Demo Mode Warning -->
                                <?php if ( defined( 'WLSM_DEMO_MODE' ) && WLSM_DEMO_MODE ) : ?>
                                <div class="alert alert-warning mb-4" role="alert">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong><?php esc_html_e( 'Demo Mode Active', 'school-management' ); ?></strong>
                                    <br>
                                    <?php esc_html_e( 'Setup wizard actions are disabled in demo mode to preserve the demo environment.', 'school-management' ); ?>
                                </div>
                                <?php endif; ?>
                                <!-- Dynamic Content Area -->
                                <div id="wlsm-setup-step-content">
                                    <?php
                                    // Include step-specific content
                                    $step_file = WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/general/setup-wizard/steps/' . $current_step . '.php';
                                    if ( file_exists( $step_file ) ) {
                                        include $step_file;
                                    } else {
                                        echo '<div class="alert alert-warning">Step content not found.</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="content-footer p-4 bg-light border-top">
                                <!-- Navigation Buttons -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ( $current_step_index > 0 ) :
                                            $prev_step = array_keys( $setup_steps )[ $current_step_index - 1 ];
                                        ?>
                                        <a href="<?php echo esc_url( add_query_arg( 'step', $prev_step ) ); ?>"
                                           class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left mr-2"></i>
                                            <?php esc_html_e( 'Previous', 'school-management' ); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ( $current_step_index < ( $total_steps - 1 ) ) :
                                            $next_step = array_keys( $setup_steps )[ $current_step_index + 1 ];
                                            $is_demo_mode = defined( 'WLSM_DEMO_MODE' ) && WLSM_DEMO_MODE;
                                        ?>
                                        <button type="button"
                                                class="btn btn-primary wlsm-setup-next-btn"
                                                data-step="<?php echo esc_attr( $current_step ); ?>"
                                                data-next-step="<?php echo esc_attr( $next_step ); ?>"
                                                <?php echo $is_demo_mode ? 'disabled' : ''; ?>
                                                <?php if ( $is_demo_mode ) : ?>
                                                title="<?php esc_attr_e( 'This action is disabled in demo mode', 'school-management' ); ?>"
                                                <?php endif; ?> >
                                            <?php esc_html_e( 'Next', 'school-management' ); ?>
                                            <i class="fas fa-arrow-right ml-2"></i>
                                        </button>
                                        <?php else :
                                            $is_demo_mode = defined( 'WLSM_DEMO_MODE' ) && WLSM_DEMO_MODE;
                                        ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="wlsm-setup-loading-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="sr-only"><?php esc_html_e( 'Loading...', 'school-management' ); ?></span>
                </div>
                <h5><?php esc_html_e( 'Processing...', 'school-management' ); ?></h5>
                <p class="text-muted mb-0"><?php esc_html_e( 'Please wait while we save your settings.', 'school-management' ); ?></p>
            </div>
        </div>
    </div>
</div>
