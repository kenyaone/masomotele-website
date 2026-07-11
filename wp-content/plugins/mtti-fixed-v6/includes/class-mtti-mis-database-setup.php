<?php
/**
 * MTTI MIS Database Setup
 *
 * Creates Coursera-style tables for certificates, progress, badges
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Database_Setup {

    public static function create_coursera_style_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // 1. CERTIFICATES TABLE
        $sql_certificates = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mtti_certificates (
            certificate_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            course_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            certificate_number VARCHAR(50) UNIQUE NOT NULL,
            verification_code VARCHAR(20) UNIQUE NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            course_title VARCHAR(255) NOT NULL,
            final_score DECIMAL(5,2) NOT NULL,
            grade VARCHAR(1) NOT NULL,
            issued_at DATETIME NOT NULL,
            valid TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_student (student_id),
            KEY idx_course (course_id),
            KEY idx_cert_number (certificate_number),
            KEY idx_verification_code (verification_code)
        ) $charset_collate;";

        // 2. STUDENT PROGRESS TABLE
        $sql_progress = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mtti_student_progress (
            progress_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            course_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            total_lessons INT DEFAULT 0,
            lessons_completed INT DEFAULT 0,
            total_quizzes INT DEFAULT 0,
            quizzes_completed INT DEFAULT 0,
            quizzes_passed INT DEFAULT 0,
            average_score DECIMAL(5,2) DEFAULT 0,
            progress_percentage INT DEFAULT 0,
            started_at DATETIME NOT NULL,
            last_activity DATETIME,
            completed_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_student (student_id),
            KEY idx_course (course_id),
            UNIQUE KEY unique_student_course (student_id, course_id)
        ) $charset_collate;";

        // 3. STUDENT BADGES TABLE
        $sql_badges = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mtti_student_badges (
            badge_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            badge_type VARCHAR(50) NOT NULL,
            badge_name VARCHAR(100) NOT NULL,
            badge_description TEXT,
            icon_emoji VARCHAR(10),
            earned_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_student (student_id),
            KEY idx_course (course_id),
            KEY idx_badge_type (badge_type),
            UNIQUE KEY unique_student_badge (student_id, course_id, badge_type)
        ) $charset_collate;";

        // 4. COURSE COMPLETION TABLE
        $sql_completion = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mtti_course_completion (
            completion_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            course_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            quiz1_score DECIMAL(5,2) DEFAULT NULL,
            quiz2_score DECIMAL(5,2) DEFAULT NULL,
            quiz3_score DECIMAL(5,2) DEFAULT NULL,
            final_score DECIMAL(5,2) DEFAULT NULL,
            grade VARCHAR(1),
            passed TINYINT(1) DEFAULT 0,
            certificate_id BIGINT UNSIGNED,
            completed_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_student (student_id),
            KEY idx_course (course_id),
            UNIQUE KEY unique_student_course (student_id, course_id)
        ) $charset_collate;";

        // Execute SQL
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_certificates);
        dbDelta($sql_progress);
        dbDelta($sql_badges);
        dbDelta($sql_completion);
    }

    /**
     * Drop all Coursera-style tables (CAREFUL!)
     */
    public static function drop_coursera_tables() {
        global $wpdb;

        $tables = array(
            'mtti_certificates',
            'mtti_student_progress',
            'mtti_student_badges',
            'mtti_course_completion'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
    }

    /**
     * Get database status
     */
    public static function check_tables() {
        global $wpdb;

        $tables = array(
            'certificates' => 'wpcu_mtti_certificates',
            'progress' => 'wpcu_mtti_student_progress',
            'badges' => 'wpcu_mtti_student_badges',
            'completion' => 'wpcu_mtti_course_completion'
        );

        $status = array();
        foreach ($tables as $name => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            $status[$name] = ($exists ? '✅ EXISTS' : '❌ MISSING');
        }

        return $status;
    }
}
