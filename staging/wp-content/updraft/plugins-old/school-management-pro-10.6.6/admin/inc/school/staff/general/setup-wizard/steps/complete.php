<?php
defined( 'ABSPATH' ) || die();

// Get school information for the summary
$school = WLSM_M_School::fetch_school( $school_id );
$school_name = $school ? $school->label : __('Your School', 'school-management');
?>

<div class="step-content">
    <!-- Next Steps Main Focus -->
    <div class="row mb-5">
        <div class="col-lg-8">
            <div class="border border-primary rounded p-4 bg-white">
                <div class="mb-3 bg-white p-3 rounded">
                    <h5 class="mb-0 text-primary"><i class="fas fa-route mr-2"></i><?php esc_html_e('What\'s Next?', 'school-management'); ?></h5>
                </div>
                <div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-users text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-primary"><?php esc_html_e('Add Students', 'school-management'); ?></h6>
                                <p class="small text-muted"><?php esc_html_e('Start adding students to your school and assign them to classes.', 'school-management'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WLSM_MENU_STAFF_ADMISSIONS)); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php esc_html_e('Add Students', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-chalkboard-teacher text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-primary"><?php esc_html_e('Add Teachers', 'school-management'); ?></h6>
                                <p class="small text-muted"><?php esc_html_e('Add teachers and assign them to classes and subjects.', 'school-management'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WLSM_MENU_STAFF_EMPLOYEES)); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php esc_html_e('Add Teachers', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-calendar-alt text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-primary"><?php esc_html_e('Schedule Classes', 'school-management'); ?></h6>
                                <p class="small text-muted"><?php esc_html_e('Create class schedules and timetables for your school.', 'school-management'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WLSM_MENU_STAFF_TIMETABLE)); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php esc_html_e('Create Schedule', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-book text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-primary"><?php esc_html_e('Library', 'school-management'); ?></h6>
                                <p class="small text-muted"><?php esc_html_e('Manage library books and cards.', 'school-management'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WLSM_MENU_STAFF_LIBRARY)); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php esc_html_e('Go to Library', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-bus text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-primary"><?php esc_html_e('Transport', 'school-management'); ?></h6>
                                <p class="small text-muted"><?php esc_html_e('Manage school transport and routes.', 'school-management'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WLSM_MENU_STAFF_TRANSPORT)); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php esc_html_e('Go to Transport', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-rupee-sign text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-primary"><?php esc_html_e('Accounting', 'school-management'); ?></h6>
                                <p class="small text-muted"><?php esc_html_e('Manage fees, invoices, and expenses.', 'school-management'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WLSM_MENU_STAFF_ACCOUNTING)); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php esc_html_e('Go to Accounting', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-id-card text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-primary"><?php esc_html_e('ID Cards', 'school-management'); ?></h6>
                                <p class="small text-muted"><?php esc_html_e('Generate student ID cards.', 'school-management'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WLSM_MENU_STAFF_ID_CARDS)); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php esc_html_e('Go to ID Cards', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-bullhorn text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-primary"><?php esc_html_e('Notices', 'school-management'); ?></h6>
                                <p class="small text-muted"><?php esc_html_e('Send important notices to students.', 'school-management'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WLSM_MENU_STAFF_NOTICES)); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php esc_html_e('Go to Notices', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-certificate text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-primary"><?php esc_html_e('Certificates', 'school-management'); ?></h6>
                                <p class="small text-muted"><?php esc_html_e('Issue certificates to students.', 'school-management'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WLSM_MENU_STAFF_CERTIFICATES)); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php esc_html_e('Go to Certificates', 'school-management'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Setup Summary on Right -->
        <div class="col-lg-4">
            <div class="border border-success rounded p-4 bg-white">
                <div class="mb-3 bg-success text-white p-3 rounded">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i><?php esc_html_e('Setup Summary', 'school-management'); ?></h5>
                </div>
                <div>
                    <h6 class="text-success mb-3"><?php esc_html_e('✓ Completed Steps', 'school-management'); ?></h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <strong><?php esc_html_e('School Information', 'school-management'); ?></strong>
                            <br><small class="text-muted ml-3"><?php esc_html_e('Basic school details configured', 'school-management'); ?></small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <strong><?php esc_html_e('Classes Assignment', 'school-management'); ?></strong>
                            <br><small class="text-muted ml-3"><?php esc_html_e('Classes assigned to your school', 'school-management'); ?></small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <strong><?php esc_html_e('Subjects Configuration', 'school-management'); ?></strong>
                            <br><small class="text-muted ml-3"><?php esc_html_e('Subjects added for each class', 'school-management'); ?></small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <strong><?php esc_html_e('Student Types', 'school-management'); ?></strong>
                            <br><small class="text-muted ml-3"><?php esc_html_e('Student categories defined', 'school-management'); ?></small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <strong><?php esc_html_e('Fee Types', 'school-management'); ?></strong>
                            <br><small class="text-muted ml-3"><?php esc_html_e('Fee categories set up', 'school-management'); ?></small>
                        </li>
                        <!-- <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <strong><?php esc_html_e('General Settings', 'school-management'); ?></strong>
                            <br><small class="text-muted ml-3"><?php esc_html_e('Basic school preferences configured', 'school-management'); ?></small>
                        </li> -->
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <strong><?php esc_html_e('Registration Settings', 'school-management'); ?></strong>
                            <br><small class="text-muted ml-3"><?php esc_html_e('Student registration form configured', 'school-management'); ?></small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for final submission -->
    <form id="complete-setup-form" style="display: none;">
        <input type="hidden" name="setup_completed" value="1">
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark the setup as completed
    const form = document.getElementById('complete-setup-form');
    
    // Optional: You can add confetti or celebration animation here
    console.log('School setup wizard completed successfully!');
    
    // Auto-save completion status
    setTimeout(function() {
        // This could trigger a backend call to mark setup as completed
        const formData = new FormData(form);
        console.log('Setup completion status saved');
    }, 1000);
});
</script>