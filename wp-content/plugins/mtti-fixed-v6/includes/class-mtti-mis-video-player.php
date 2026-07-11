<?php
/**
 * MTTI MIS Lesson Content Handler
 *
 * Skeleton for custom video uploads.
 * Ready for admin to add video_url to lessons.
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Video_Player {

    /**
     * Render lesson content
     * Admin provides video_url via database
     */
    public static function render_lesson($lesson_id) {
        global $wpdb;

        $lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_lesson WHERE lesson_id = %d",
            $lesson_id
        ));

        if (!$lesson) {
            return '<p>Lesson not found</p>';
        }

        // Display lesson content
        ob_start();
        ?>
        <div class="lesson-container">
            <h2><?php echo esc_html($lesson->title); ?></h2>

            <?php if ($lesson->video_url): ?>
                <div class="lesson-video">
                    <?php echo wp_kses_post($lesson->video_url); ?>
                </div>
            <?php else: ?>
                <div class="lesson-placeholder" style="padding: 30px; background: #f8f9fa; border-radius: 8px; text-align: center; border: 2px dashed #ccc;">
                    <p style="color: #7f8c8d; font-size: 16px;">📹 Video will be added here</p>
                </div>
            <?php endif; ?>

            <div class="lesson-content">
                <?php echo wp_kses_post($lesson->content); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
