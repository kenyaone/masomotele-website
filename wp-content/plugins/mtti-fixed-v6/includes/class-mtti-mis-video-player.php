<?php
/**
 * MTTI MIS Video Player
 *
 * Generic video player supporting multiple sources:
 * - Self-hosted MP4
 * - Vimeo
 * - Wistia
 * - Any platform via embed code
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Video_Player {

    /**
     * Render video player for lesson
     *
     * Supports:
     * - Self-hosted: /path/to/video.mp4
     * - Vimeo: vimeo:VIDEO_ID or https://vimeo.com/12345
     * - Wistia: wistia:VIDEO_ID or https://home.wistia.com/medias/ABC123
     * - Custom embed: <iframe>...</iframe>
     */
    public static function render_video_player($video_source) {
        if (!$video_source) {
            return '<p style="color: #e74c3c; padding: 20px; background: #fdeae6; border-radius: 6px;">⚠️ No video configured for this lesson</p>';
        }

        // Detect video source type
        if (self::is_custom_embed($video_source)) {
            return self::render_custom_embed($video_source);
        } elseif (self::is_vimeo($video_source)) {
            return self::render_vimeo($video_source);
        } elseif (self::is_wistia($video_source)) {
            return self::render_wistia($video_source);
        } elseif (self::is_local_file($video_source)) {
            return self::render_html5_video($video_source);
        } else {
            return '<p style="color: #e74c3c;">❌ Video source not recognized. Please use one of: MP4 URL, Vimeo ID, Wistia ID, or embed code.</p>';
        }
    }

    /**
     * Check if video is custom HTML embed code
     */
    private static function is_custom_embed($video_source) {
        return strpos($video_source, '<iframe') !== false;
    }

    /**
     * Check if video is Vimeo
     */
    private static function is_vimeo($video_source) {
        return strpos($video_source, 'vimeo') !== false ||
               preg_match('/vimeo:\d+/', $video_source);
    }

    /**
     * Check if video is Wistia
     */
    private static function is_wistia($video_source) {
        return strpos($video_source, 'wistia') !== false ||
               preg_match('/wistia:\w+/', $video_source);
    }

    /**
     * Check if video is local file (MP4, etc)
     */
    private static function is_local_file($video_source) {
        $extensions = array('mp4', 'webm', 'ogv', 'mov', 'avi');
        foreach ($extensions as $ext) {
            if (preg_match('/\.' . $ext . '$/i', $video_source)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render custom HTML embed (for any platform)
     */
    private static function render_custom_embed($embed_code) {
        return '<div class="video-container" style="position: relative; width: 100%; max-width: 100%; background: #000; border-radius: 8px; overflow: hidden;">
                    ' . wp_kses_post($embed_code) . '
                </div>';
    }

    /**
     * Render Vimeo player
     */
    private static function render_vimeo($video_source) {
        // Extract video ID
        if (preg_match('/vimeo:(\d+)/', $video_source, $matches)) {
            $video_id = $matches[1];
        } elseif (preg_match('/vimeo\.com\/(\d+)/', $video_source, $matches)) {
            $video_id = $matches[1];
        } else {
            return '<p style="color: #e74c3c;">❌ Invalid Vimeo URL. Use: vimeo:12345 or https://vimeo.com/12345</p>';
        }

        return '<div style="position: relative; width: 100%; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
                    <iframe
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                        src="https://player.vimeo.com/video/' . intval($video_id) . '"
                        frameborder="0"
                        allow="autoplay; fullscreen; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>';
    }

    /**
     * Render Wistia player
     */
    private static function render_wistia($video_source) {
        // Extract video ID
        if (preg_match('/wistia:(\w+)/', $video_source, $matches)) {
            $video_id = $matches[1];
        } elseif (preg_match('/medias\/(\w+)/', $video_source, $matches)) {
            $video_id = $matches[1];
        } else {
            return '<p style="color: #e74c3c;">❌ Invalid Wistia URL. Use: wistia:ABC123 or https://home.wistia.com/medias/ABC123</p>';
        }

        return '<div style="position: relative; width: 100%; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
                    <iframe
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                        src="https://home.wistia.com/medias/' . esc_attr($video_id) . '?embedType=async"
                        allowfullscreen
                        frameborder="0">
                    </iframe>
                </div>';
    }

    /**
     * Render HTML5 video player (self-hosted)
     */
    private static function render_html5_video($video_url) {
        return '<video
                    width="100%"
                    height="auto"
                    controls
                    style="border-radius: 8px; background: #000; max-width: 100%;">
                    <source src="' . esc_url($video_url) . '" type="video/mp4">
                    Your browser does not support the video tag.
                </video>';
    }

    /**
     * Get video duration in readable format
     */
    public static function get_video_duration_display($lesson_duration_minutes) {
        if ($lesson_duration_minutes >= 60) {
            $hours = floor($lesson_duration_minutes / 60);
            $mins = $lesson_duration_minutes % 60;
            return $hours . 'h ' . $mins . 'm';
        }
        return $lesson_duration_minutes . ' min';
    }

    /**
     * Validate video URL/source before saving
     */
    public static function validate_video_source($video_source) {
        if (!$video_source) {
            return array('valid' => false, 'error' => 'Video source is required');
        }

        // Check for valid formats
        $valid_formats = array(
            'is_custom_embed' => self::is_custom_embed($video_source),
            'is_vimeo' => self::is_vimeo($video_source),
            'is_wistia' => self::is_wistia($video_source),
            'is_local_file' => self::is_local_file($video_source),
        );

        if (!in_array(true, $valid_formats)) {
            return array(
                'valid' => false,
                'error' => 'Invalid video source. Supported formats: MP4 URL, Vimeo (vimeo:12345), Wistia (wistia:ABC123), or embed code'
            );
        }

        return array('valid' => true, 'error' => null);
    }
}
