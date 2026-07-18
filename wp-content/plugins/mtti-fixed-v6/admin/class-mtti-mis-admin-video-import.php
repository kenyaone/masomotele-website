<?php
/**
 * Bulk Video Import — set the YouTube/Vimeo URL for every "Video" lesson
 * in a course from one screen, instead of opening each lesson's edit form
 * one at a time. Targets content_type='video' rows specifically (the
 * per-topic video placeholders in courses built with the Objectives ->
 * Video -> Theory -> Practical -> Quiz structure, but works for any
 * course's video lessons).
 *
 * @package MTTI_MIS
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Admin_Video_Import {

    public function display() {
        global $wpdb;

        if (!current_user_can('manage_courses') && !current_user_can('manage_options')) {
            wp_die('You do not have permission to access this page.');
        }

        $summary = null;
        if (isset($_POST['mtti_video_import_submit'])) {
            check_admin_referer('mtti_video_import_action', 'mtti_video_import_nonce');
            $summary = $this->process_submission();
        }

        $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
        $courses   = $wpdb->get_results("SELECT course_id, course_code, course_name FROM {$wpdb->prefix}mtti_courses WHERE status = 'Active' ORDER BY course_name");
        if (!$course_id && $courses) {
            // Default to SMD-01 if present, else the first active course.
            foreach ($courses as $c) {
                if ($c->course_code === 'SMD-01') { $course_id = (int) $c->course_id; break; }
            }
            if (!$course_id) $course_id = (int) $courses[0]->course_id;
        }

        $videos = $course_id ? $wpdb->get_results($wpdb->prepare(
            "SELECT l.lesson_id, l.title, l.content_url, cu.unit_name, cu.order_number AS unit_order
             FROM {$wpdb->prefix}mtti_lessons l
             LEFT JOIN {$wpdb->prefix}mtti_course_units cu ON l.unit_id = cu.unit_id
             WHERE l.course_id = %d AND l.content_type = 'video' AND l.status = 'Published' AND l.deleted_at IS NULL
             ORDER BY l.order_number ASC",
            $course_id
        )) : array();

        $this->render($courses, $course_id, $videos, $summary);
    }

    /**
     * Validates and saves every submitted URL. A URL only needs to be a
     * well-formed URL to be accepted — embed_video_player() (learner-portal)
     * already falls back to a plain <video> tag for anything that isn't
     * recognisably YouTube/Vimeo, so this isn't a hard gate, just a nudge.
     */
    private function process_submission() {
        global $wpdb;

        $updated = 0;
        $skipped_empty = 0;
        $skipped_unchanged = 0;
        $warnings = array();

        $urls = $_POST['video_url'] ?? array();
        if (!is_array($urls)) return null;

        foreach ($urls as $lesson_id => $raw_url) {
            $lesson_id = intval($lesson_id);
            $raw_url = trim((string) $raw_url);

            if ($raw_url === '') { $skipped_empty++; continue; }

            $url = esc_url_raw($raw_url);
            if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                $warnings[] = "Lesson #{$lesson_id}: \"{$raw_url}\" doesn't look like a valid URL — skipped.";
                continue;
            }

            // get_row(), not get_var() -- a video lesson with content_url
            // still NULL (the normal "Coming Soon" starting state, the main
            // case this tool exists for) must not be mistaken for "no such
            // row", which get_var() alone can't tell apart.
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT content_url FROM {$wpdb->prefix}mtti_lessons WHERE lesson_id = %d AND content_type = 'video'",
                $lesson_id
            ));
            if ($row === null) { continue; } // not a video lesson, or doesn't exist — ignore silently
            if ($row->content_url === $url) { $skipped_unchanged++; continue; }

            $is_youtube_or_vimeo = (bool) preg_match('/(youtube\.com|youtu\.be|vimeo\.com)/i', $url);
            if (!$is_youtube_or_vimeo) {
                $warnings[] = "Lesson #{$lesson_id}: \"{$url}\" isn't a recognised YouTube/Vimeo link — saved anyway, will attempt direct video playback.";
            }

            $wpdb->update(
                $wpdb->prefix . 'mtti_lessons',
                array('content_url' => $url, 'updated_at' => current_time('mysql')),
                array('lesson_id' => $lesson_id, 'content_type' => 'video')
            );
            $updated++;
        }

        return compact('updated', 'skipped_empty', 'skipped_unchanged', 'warnings');
    }

    private function render($courses, $course_id, $videos, $summary) {
        ?>
        <div class="wrap">
            <h1>🎬 Bulk Video Import</h1>
            <p class="description">Paste a YouTube (or Vimeo) link for each video lesson below, then save them all at once — no need to open each lesson's edit screen individually.</p>

            <?php if ($summary) : ?>
                <div class="notice notice-success is-dismissible" style="margin-top:16px;">
                    <p>
                        <strong>✅ <?php echo intval($summary['updated']); ?> video<?php echo $summary['updated'] === 1 ? '' : 's'; ?> updated.</strong>
                        <?php if ($summary['skipped_unchanged']) echo intval($summary['skipped_unchanged']) . ' unchanged. '; ?>
                        <?php if ($summary['skipped_empty']) echo intval($summary['skipped_empty']) . ' left blank. '; ?>
                    </p>
                </div>
                <?php if (!empty($summary['warnings'])) : ?>
                    <div class="notice notice-warning is-dismissible">
                        <p><strong>⚠️ Warnings:</strong></p>
                        <ul style="margin-left:20px;list-style:disc;">
                            <?php foreach ($summary['warnings'] as $w) : ?>
                                <li><?php echo esc_html($w); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="get" style="margin:20px 0;">
                <input type="hidden" name="page" value="mtti-mis-video-import">
                <label for="course_id" style="font-weight:600;margin-right:8px;">Course:</label>
                <select name="course_id" id="course_id" onchange="this.form.submit()">
                    <?php foreach ($courses as $c) : ?>
                        <option value="<?php echo intval($c->course_id); ?>" <?php selected($course_id, $c->course_id); ?>>
                            <?php echo esc_html($c->course_code . ' — ' . $c->course_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if (empty($videos)) : ?>
                <div class="notice notice-info"><p>This course has no video-type lessons.</p></div>
            <?php else : ?>
                <form method="post">
                    <?php wp_nonce_field('mtti_video_import_action', 'mtti_video_import_nonce'); ?>
                    <input type="hidden" name="course_id" value="<?php echo intval($course_id); ?>">

                    <table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th style="width:22%;">Unit</th>
                                <th style="width:33%;">Lesson</th>
                                <th style="width:10%;">Status</th>
                                <th>YouTube / Vimeo URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($videos as $v) : $has_url = !empty($v->content_url); ?>
                                <tr>
                                    <td><?php echo esc_html($v->unit_name ?: '—'); ?></td>
                                    <td><?php echo esc_html($v->title); ?></td>
                                    <td>
                                        <?php if ($has_url) : ?>
                                            <span style="color:#2e7d32;font-weight:600;">✅ Set</span>
                                        <?php else : ?>
                                            <span style="color:#e65100;font-weight:600;">⏳ Coming Soon</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="url" name="video_url[<?php echo intval($v->lesson_id); ?>]"
                                               value="<?php echo esc_attr($v->content_url); ?>"
                                               placeholder="https://www.youtube.com/watch?v=…"
                                               class="large-text">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p style="margin-top:16px;">
                        <button type="submit" name="mtti_video_import_submit" class="button button-primary button-large">💾 Save All Videos</button>
                        <span class="description" style="margin-left:10px;">Only rows you fill in or change are updated — blank/unchanged rows are left alone.</span>
                    </p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}
