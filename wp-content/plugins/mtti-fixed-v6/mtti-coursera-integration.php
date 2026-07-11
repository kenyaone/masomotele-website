<?php
/**
 * MTTI Coursera-Style Integration
 *
 * Integrates all Coursera-style components into the main plugin
 * Auto-grading, certificates, dashboard, badges, leaderboard
 */

// Load all Coursera-style classes
require_once plugin_dir_path(__FILE__) . 'includes/class-mtti-mis-database-setup.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mtti-mis-quiz-grader.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mtti-mis-certificate.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mtti-mis-student-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mtti-mis-badges.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mtti-mis-leaderboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mtti-mis-certificate-verification.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mtti-mis-enrollment-access.php';

// Initialize on plugin load
add_action('plugins_loaded', function() {
    // Create database tables if needed
    if (get_option('mtti_coursera_tables_created') !== 'yes') {
        MTTI_MIS_Database_Setup::create_coursera_style_tables();
        MTTI_MIS_Enrollment_Access::create_access_tables();
        update_option('mtti_coursera_tables_created', 'yes');
    }
});

// Register dashboard shortcode
add_shortcode('mtti_dashboard', function($atts) {
    $course_id = $atts['course_id'] ?? 5;
    return MTTI_MIS_Student_Dashboard::display_dashboard($course_id);
});

// Register leaderboard shortcode
add_shortcode('mtti_leaderboard', function($atts) {
    $course_id = $atts['course_id'] ?? 5;
    return MTTI_MIS_Leaderboard::display_leaderboard($course_id);
});

// Register certificate verification page
add_action('template_redirect', function() {
    if (isset($_GET['mtti_action']) && $_GET['mtti_action'] === 'verify_certificate') {
        echo MTTI_MIS_Certificate_Verification::display_verification_page();
        exit;
    }
});

// Hook: Auto-grade quiz on submission
add_action('mtti_quiz_submitted', function($quiz_id, $student_id, $answers) {
    $result = MTTI_MIS_Quiz_Grader::grade_quiz_submission($quiz_id, $student_id, $answers);
    
    // Save attempt
    MTTI_MIS_Quiz_Grader::save_quiz_attempt($quiz_id, $student_id, $result);
    
    // Update progress
    global $wpdb;
    $quiz = $wpdb->get_row($wpdb->prepare(
        "SELECT course_id FROM wpcu_mtti_quizzes WHERE quiz_id = %d",
        $quiz_id
    ));
    
    if ($quiz) {
        $course_id = $quiz->course_id;
        
        // Update progress
        MTTI_MIS_Student_Dashboard::update_progress($course_id, $student_id);
        
        // Check badges
        MTTI_MIS_Badges::check_badges_after_quiz($quiz_id, $student_id, $course_id);
        
        // Check certificate
        MTTI_MIS_Certificate::check_and_issue_certificate($course_id, $student_id);
    }
}, 10, 3);

// Enrollment access check
add_filter('mtti_can_access_lesson', function($can_access, $lesson_id, $student_id) {
    global $wpdb;
    
    $lesson = $wpdb->get_row($wpdb->prepare(
        "SELECT course_id FROM wpcu_mtti_lesson WHERE lesson_id = %d",
        $lesson_id
    ));
    
    if (!$lesson) {
        return $can_access;
    }
    
    $access = MTTI_MIS_Enrollment_Access::can_access_lesson($lesson_id, $student_id, $lesson->course_id);
    
    return $access['allowed'];
}, 10, 3);

// Log access attempts
add_action('mtti_lesson_access_attempt', function($lesson_id, $student_id, $course_id, $granted) {
    MTTI_MIS_Enrollment_Access::log_access_attempt($lesson_id, $student_id, $course_id, $granted);
}, 10, 4);

?>
