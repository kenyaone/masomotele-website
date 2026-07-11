<?php
/**
 * MTTI MIS Enrollment-Based Access Control
 *
 * Locks/unlocks course materials based on enrollment date
 * Students only see content they're eligible for
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Enrollment_Access {

    /**
     * Check if student can access lesson
     *
     * Access rules:
     * - Student must be enrolled in course
     * - Lesson must be within their enrollment date range
     * - Lesson must not be future-locked (scheduled for later date)
     */
    public static function can_access_lesson($lesson_id, $student_id, $course_id) {
        global $wpdb;

        // Check 1: Is student enrolled in this course?
        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_enrollments WHERE student_id = %d AND course_id = %d AND enrollment_status = 'active'",
            $student_id, $course_id
        ));

        if (!$enrollment) {
            return array('allowed' => false, 'reason' => 'Not enrolled in this course');
        }

        // Check 2: Has enrollment started?
        $enrollment_date = strtotime($enrollment->enrollment_date);
        $now = current_time('timestamp');

        if ($now < $enrollment_date) {
            $days_until = ceil(($enrollment_date - $now) / (24 * 60 * 60));
            return array(
                'allowed' => false,
                'reason' => "Course access starts in {$days_until} day(s)",
                'unlock_date' => $enrollment->enrollment_date
            );
        }

        // Check 3: Get lesson info (if it has future lock)
        $lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_lesson WHERE lesson_id = %d AND course_id = %d",
            $lesson_id, $course_id
        ));

        if (!$lesson) {
            return array('allowed' => false, 'reason' => 'Lesson not found');
        }

        // Check 4: Is lesson locked for future date?
        if ($lesson->is_locked && $lesson->unlock_date) {
            $unlock_timestamp = strtotime($lesson->unlock_date);

            if ($now < $unlock_timestamp) {
                $days_until = ceil(($unlock_timestamp - $now) / (24 * 60 * 60));
                return array(
                    'allowed' => false,
                    'reason' => "This lesson unlocks in {$days_until} day(s)",
                    'unlock_date' => $lesson->unlock_date
                );
            }
        }

        // Check 5: Check lesson progression (previous lessons must be completed)
        if ($lesson->require_previous_complete) {
            $previous_complete = self::is_previous_lesson_complete($lesson_id, $student_id);
            if (!$previous_complete) {
                return array('allowed' => false, 'reason' => 'Complete previous lessons first');
            }
        }

        // ✅ ALL CHECKS PASSED - ACCESS ALLOWED
        return array('allowed' => true, 'reason' => 'Access granted');
    }

    /**
     * Check if student completed previous lesson
     */
    private static function is_previous_lesson_complete($lesson_id, $student_id) {
        global $wpdb;

        // Get current lesson order
        $current = $wpdb->get_row($wpdb->prepare(
            "SELECT lesson_order FROM wpcu_mtti_lesson WHERE lesson_id = %d",
            $lesson_id
        ));

        if (!$current || $current->lesson_order <= 1) {
            return true; // First lesson, no prereq
        }

        // Get previous lesson
        $previous = $wpdb->get_row($wpdb->prepare(
            "SELECT lesson_id FROM wpcu_mtti_lesson WHERE lesson_order = %d ORDER BY lesson_order DESC LIMIT 1",
            $current->lesson_order - 1
        ));

        if (!$previous) {
            return true;
        }

        // Check if previous lesson is marked complete
        $completion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_lesson_progress WHERE lesson_id = %d AND student_id = %d AND is_complete = 1",
            $previous->lesson_id, $student_id
        ));

        return ($completion !== null);
    }

    /**
     * Can student take quiz?
     */
    public static function can_access_quiz($quiz_id, $student_id, $course_id) {
        global $wpdb;

        // Check 1: Enrolled?
        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_enrollments WHERE student_id = %d AND course_id = %d AND enrollment_status = 'active'",
            $student_id, $course_id
        ));

        if (!$enrollment) {
            return array('allowed' => false, 'reason' => 'Not enrolled');
        }

        // Check 2: Enrollment started?
        $enrollment_date = strtotime($enrollment->enrollment_date);
        $now = current_time('timestamp');

        if ($now < $enrollment_date) {
            $days = ceil(($enrollment_date - $now) / (24 * 60 * 60));
            return array(
                'allowed' => false,
                'reason' => "Course access starts in {$days} day(s)",
                'unlock_date' => $enrollment->enrollment_date
            );
        }

        // Check 3: Quiz has unlock date?
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_quizzes WHERE quiz_id = %d AND course_id = %d",
            $quiz_id, $course_id
        ));

        if (!$quiz) {
            return array('allowed' => false, 'reason' => 'Quiz not found');
        }

        if ($quiz->unlock_date && $now < strtotime($quiz->unlock_date)) {
            $days = ceil((strtotime($quiz->unlock_date) - $now) / (24 * 60 * 60));
            return array(
                'allowed' => false,
                'reason' => "This quiz unlocks in {$days} day(s)",
                'unlock_date' => $quiz->unlock_date
            );
        }

        return array('allowed' => true, 'reason' => 'Access granted');
    }

    /**
     * Get all accessible lessons for student
     */
    public static function get_accessible_lessons($course_id, $student_id) {
        global $wpdb;

        $lessons = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_lesson WHERE course_id = %d ORDER BY lesson_order",
            $course_id
        ));

        $accessible = array();
        $locked_until_date = null;

        foreach ($lessons as $lesson) {
            $access = self::can_access_lesson($lesson->lesson_id, $student_id, $course_id);

            if ($access['allowed']) {
                $accessible[] = $lesson;
            } else {
                // Track when things unlock
                if (isset($access['unlock_date']) && !$locked_until_date) {
                    $locked_until_date = $access['unlock_date'];
                }
            }
        }

        return array(
            'accessible' => $accessible,
            'locked_until' => $locked_until_date
        );
    }

    /**
     * Get access status for display on dashboard
     */
    public static function get_access_status_display($student_id, $course_id) {
        global $wpdb;

        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_enrollments WHERE student_id = %d AND course_id = %d",
            $student_id, $course_id
        ));

        if (!$enrollment) {
            return array(
                'status' => 'not_enrolled',
                'message' => '❌ Not enrolled in this course',
                'icon' => '🚫'
            );
        }

        $enrollment_date = strtotime($enrollment->enrollment_date);
        $now = current_time('timestamp');

        if ($now < $enrollment_date) {
            $days = ceil(($enrollment_date - $now) / (24 * 60 * 60));
            return array(
                'status' => 'pending',
                'message' => "⏳ Course access starts in {$days} day(s) ({$enrollment->enrollment_date})",
                'icon' => '🔒',
                'unlock_date' => $enrollment->enrollment_date
            );
        }

        // Get accessible content
        $accessible = self::get_accessible_lessons($course_id, $student_id);
        $lesson_count = count($accessible['accessible']);

        return array(
            'status' => 'active',
            'message' => "✅ Access granted ({$lesson_count} lessons available)",
            'icon' => '🔓',
            'accessible_count' => $lesson_count
        );
    }

    /**
     * Create database table for lesson access tracking
     */
    public static function create_access_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Lesson access log
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mtti_lesson_access_log (
            access_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            lesson_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            access_granted TINYINT(1) NOT NULL,
            denial_reason VARCHAR(255),
            accessed_at DATETIME NOT NULL,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_student (student_id),
            KEY idx_lesson (lesson_id),
            KEY idx_course (course_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log access attempt (for security audit)
     */
    public static function log_access_attempt($lesson_id, $student_id, $course_id, $granted, $reason = null) {
        global $wpdb;

        $wpdb->insert('wpcu_mtti_lesson_access_log', array(
            'lesson_id' => $lesson_id,
            'student_id' => $student_id,
            'course_id' => $course_id,
            'access_granted' => ($granted ? 1 : 0),
            'denial_reason' => $reason,
            'accessed_at' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));
    }

    /**
     * Check if it's within enrollment window
     * Used for displaying course availability
     */
    public static function is_within_enrollment_window($student_id, $course_id) {
        global $wpdb;

        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_enrollments WHERE student_id = %d AND course_id = %d",
            $student_id, $course_id
        ));

        if (!$enrollment) {
            return false;
        }

        $enrollment_date = strtotime($enrollment->enrollment_date);
        $now = current_time('timestamp');

        // Student can access if enrolled and enrollment date has passed
        return ($now >= $enrollment_date);
    }

    /**
     * Get time until access (in seconds)
     */
    public static function get_time_until_access($student_id, $course_id) {
        global $wpdb;

        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_enrollments WHERE student_id = %d AND course_id = %d",
            $student_id, $course_id
        ));

        if (!$enrollment) {
            return null;
        }

        $enrollment_date = strtotime($enrollment->enrollment_date);
        $now = current_time('timestamp');

        if ($now >= $enrollment_date) {
            return 0; // Already accessible
        }

        return ($enrollment_date - $now);
    }

    /**
     * Format access status for display
     */
    public static function format_access_message($time_until) {
        if ($time_until === null) {
            return '🚫 Not Enrolled';
        }

        if ($time_until === 0) {
            return '✅ Access Now Active';
        }

        $hours = floor($time_until / 3600);
        $days = floor($hours / 24);

        if ($days > 0) {
            return "🔒 Access in {$days} day(s)";
        } elseif ($hours > 0) {
            return "🔒 Access in {$hours} hour(s)";
        } else {
            $minutes = floor($time_until / 60);
            return "🔒 Access in {$minutes} minute(s)";
        }
    }
}
