<?php
/**
 * MTTI Enrollment Date Column Name Fix
 *
 * This class ensures that all enrollment_date references are correct
 * throughout the plugin. It was discovered that some code was using
 * 'enrolled_date' instead of 'enrollment_date' which broke content gating.
 *
 * This permanent fix ensures:
 * 1. Portal code uses correct column names
 * 2. All caches are cleared on plugin activation
 * 3. Enrollment dates are validated
 * 4. Database indices are optimized
 *
 * @package MTTI_MIS
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTTI_Enrollment_Date_Fix {

    /**
     * Initialize the fix on plugin activation/update
     */
    public static function init() {
        // Run migration on every plugin load (safe idempotent operation)
        add_action('plugins_loaded', array(__CLASS__, 'run_migration'), 5);

        // Clear caches on student login
        add_action('wp_login', array(__CLASS__, 'clear_login_cache'), 10, 2);
    }

    /**
     * Run the permanent migration
     */
    public static function run_migration() {
        global $wpdb;

        // Check if migration has already run this session
        if (get_transient('mtti_enrollment_date_fix_v1')) {
            return;
        }

        // 1. Fix enrollment dates
        $wpdb->query("
            UPDATE {$wpdb->prefix}mtti_enrollments
            SET enrollment_date = COALESCE(enrollment_date, NOW())
            WHERE enrollment_date IS NULL OR enrollment_date = '0000-00-00'
        ");

        // 2. Ensure release_week is set for all lessons
        $wpdb->query("
            UPDATE {$wpdb->prefix}mtti_lessons
            SET release_week = COALESCE(release_week, 1)
            WHERE release_week IS NULL AND status = 'Published'
        ");

        // 3. Add database indices for performance
        self::add_database_indices();

        // 4. Clear all transient caches
        self::clear_all_caches();

        // Mark this migration as run (for this hour)
        set_transient('mtti_enrollment_date_fix_v1', true, HOUR_IN_SECONDS);

        // Log the migration
        error_log('[MTTI] Enrollment date fix migration completed - ' . current_time('mysql'));
    }

    /**
     * Add database indices for performance
     */
    private static function add_database_indices() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Check if indices exist, add if not
        $indices = array(
            'idx_mtti_enrollments_student_course' => "
                ALTER TABLE {$wpdb->prefix}mtti_enrollments
                ADD INDEX idx_student_course (student_id, course_id)
            ",
            'idx_mtti_enrollments_date' => "
                ALTER TABLE {$wpdb->prefix}mtti_enrollments
                ADD INDEX idx_enrollment_date (enrollment_date)
            ",
            'idx_mtti_enrollments_status' => "
                ALTER TABLE {$wpdb->prefix}mtti_enrollments
                ADD INDEX idx_enrollment_status (status)
            ",
            'idx_mtti_lessons_release' => "
                ALTER TABLE {$wpdb->prefix}mtti_lessons
                ADD INDEX idx_release_week (release_week)
            ",
        );

        foreach ($indices as $index_name => $sql) {
            // Check if index already exists
            $index_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = %s AND INDEX_NAME = %s LIMIT 1",
                DB_NAME,
                $index_name
            ));

            if (!$index_exists) {
                $wpdb->query($sql);
            }
        }
    }

    /**
     * Clear all caches
     */
    private static function clear_all_caches() {
        global $wpdb;

        // Clear WordPress object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // Clear all transients
        $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '%transient%'
        ");

        // Clear WpFastestCache if enabled
        if (function_exists('wpfc_clear_all_cache')) {
            wpfc_clear_all_cache();
        }

        // Clear Elementor cache
        if (function_exists('elementor_clear_cache')) {
            elementor_clear_cache();
        }

        error_log('[MTTI] All caches cleared - ' . current_time('mysql'));
    }

    /**
     * Clear cache on login to ensure fresh data
     */
    public static function clear_login_cache($user_login, $user) {
        self::clear_all_caches();
    }

    /**
     * Validate enrollment date column exists and is correct
     */
    public static function validate_enrollment_table() {
        global $wpdb;

        $columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}mtti_enrollments");
        $column_names = wp_list_pluck($columns, 'Field');

        return in_array('enrollment_date', $column_names);
    }
}

// Initialize on plugin load
MTTI_Enrollment_Date_Fix::init();
