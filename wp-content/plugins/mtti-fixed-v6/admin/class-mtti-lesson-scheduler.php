<?php
/**
 * MTTI Lesson Scheduler Admin Interface
 * Allows admins to set custom lesson unlock dates across all courses
 */

if (!defined('ABSPATH')) exit;

class MTTI_Lesson_Scheduler {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_mtti_get_course_lessons', array($this, 'ajax_get_course_lessons'));
        add_action('wp_ajax_mtti_save_lesson_schedule', array($this, 'ajax_save_lesson_schedule'));
        add_action('wp_ajax_mtti_apply_template', array($this, 'ajax_apply_template'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'mtti-mis',
            'Lesson Scheduler',
            'Lesson Scheduler',
            'manage_options',
            'mtti-lesson-scheduler',
            array($this, 'render_page')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'mtti-lesson-scheduler') === false) return;

        wp_enqueue_style('mtti-scheduler', MTTI_MIS_PLUGIN_URL . 'assets/css/lesson-scheduler.css', array(), '1.0.0');
        wp_enqueue_script('mtti-scheduler', MTTI_MIS_PLUGIN_URL . 'assets/js/lesson-scheduler.js', array('jquery'), '1.0.0', true);

        wp_localize_script('mtti-scheduler', 'mttiScheduler', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mtti_scheduler_nonce'),
        ));
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;
        $courses = $wpdb->get_results("SELECT course_id, course_code, course_name FROM {$wpdb->prefix}mtti_courses WHERE status = 'Active' ORDER BY course_name");
        ?>
        <div class="wrap mtti-lesson-scheduler-wrap">
            <h1>📚 Lesson Scheduler</h1>
            <p style="margin-bottom: 30px; font-size: 16px; color: #666;">
                Set custom lesson unlock dates for your courses. Learners will access lessons progressively based on their enrollment date.
            </p>

            <div class="mtti-scheduler-container">
                <!-- Left Sidebar -->
                <div class="scheduler-sidebar">
                    <h3>Select Course</h3>
                    <select id="courseSelect" class="mtti-course-selector">
                        <option value="">-- Choose a Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course->course_id; ?>">
                                <?php echo esc_html($course->course_code . ' - ' . $course->course_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="templateOptions" style="display:none; margin-top: 30px;">
                        <h3>Quick Templates</h3>
                        <button class="button button-primary mtti-template-btn" data-template="immediate">
                            ⚡ All at Once
                        </button>
                        <button class="button button-primary mtti-template-btn" data-template="daily">
                            📅 1 per Day
                        </button>
                        <button class="button button-primary mtti-template-btn" data-template="weekly">
                            📆 1 per Week
                        </button>
                        <button class="button button-primary mtti-template-btn" data-template="biweekly">
                            📊 1 per 2 Weeks
                        </button>
                        <button class="button mtti-template-btn" data-template="custom">
                            ⚙️ Custom
                        </button>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="scheduler-content">
                    <div id="lessonsList" style="display:none;">
                        <div class="mtti-toolbar">
                            <h3 id="selectedCourseName"></h3>
                            <span id="lessonCount" class="mtti-lesson-count"></span>
                        </div>

                        <table class="mtti-schedule-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 45%;">Lesson Title</th>
                                    <th style="width: 25%;">Release Week</th>
                                    <th style="width: 25%;">Release Date (Optional)</th>
                                </tr>
                            </thead>
                            <tbody id="lessonsTableBody">
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>

                        <div style="margin-top: 20px; text-align: right;">
                            <button id="saveSchedule" class="button button-primary button-large">
                                💾 Save Schedule
                            </button>
                            <span id="saveStatus" style="margin-left: 15px; display: none;"></span>
                        </div>

                        <div id="previewInfo" style="margin-top: 30px; padding: 15px; background: #f0f6ff; border-left: 4px solid #0073aa; border-radius: 4px;">
                            <strong>📋 Schedule Preview:</strong>
                            <p id="previewText" style="margin: 10px 0 0 0; color: #666;"></p>
                        </div>
                    </div>

                    <div id="noSelection" style="text-align: center; padding: 60px 20px;">
                        <p style="font-size: 18px; color: #999;">
                            👈 Select a course to manage its lesson schedule
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .mtti-scheduler-wrap {
                background: #fff;
                padding: 20px;
            }

            .mtti-scheduler-container {
                display: grid;
                grid-template-columns: 280px 1fr;
                gap: 30px;
                margin-top: 20px;
            }

            .scheduler-sidebar {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 8px;
                height: fit-content;
                position: sticky;
                top: 100px;
            }

            .scheduler-sidebar h3 {
                margin: 0 0 15px 0;
                font-size: 14px;
                font-weight: 600;
                color: #333;
            }

            .mtti-course-selector {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }

            .mtti-template-btn {
                display: block;
                width: 100%;
                margin: 8px 0;
                padding: 10px;
                font-size: 13px;
                text-align: left;
            }

            .mtti-template-btn:hover {
                background: #f0f0f0 !important;
            }

            .scheduler-content {
                background: #fafafa;
                padding: 20px;
                border-radius: 8px;
            }

            .mtti-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #ddd;
            }

            .mtti-toolbar h3 {
                margin: 0;
                font-size: 18px;
                color: #333;
            }

            .mtti-lesson-count {
                background: #0073aa;
                color: white;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }

            .mtti-schedule-table {
                width: 100%;
                border-collapse: collapse;
                background: white;
                border-radius: 6px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .mtti-schedule-table thead {
                background: #f5f5f5;
                border-bottom: 2px solid #ddd;
            }

            .mtti-schedule-table th {
                padding: 15px;
                text-align: left;
                font-weight: 600;
                color: #333;
                font-size: 13px;
            }

            .mtti-schedule-table td {
                padding: 12px 15px;
                border-bottom: 1px solid #eee;
                font-size: 14px;
            }

            .mtti-schedule-table tr:hover {
                background: #f9f9f9;
            }

            .mtti-schedule-table input[type="number"],
            .mtti-schedule-table input[type="date"] {
                padding: 6px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 13px;
                width: 100%;
                box-sizing: border-box;
            }

            .mtti-schedule-table input:focus {
                outline: none;
                border-color: #0073aa;
                box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
            }

            #previewInfo {
                font-size: 13px;
            }

            #saveStatus {
                padding: 8px 12px;
                border-radius: 4px;
                font-weight: 600;
            }

            #saveStatus.success {
                background: #d4edda;
                color: #155724;
            }

            #saveStatus.error {
                background: #f8d7da;
                color: #721c24;
            }
        </style>
        <?php
    }

    public function ajax_get_course_lessons() {
        check_ajax_referer('mtti_scheduler_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $course_id = intval($_POST['course_id']);
        global $wpdb;

        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mtti_courses WHERE course_id = %d",
            $course_id
        ));

        $lessons = $wpdb->get_results($wpdb->prepare(
            "SELECT lesson_id, title, order_number, release_week, release_date
             FROM {$wpdb->prefix}mtti_lessons
             WHERE course_id = %d AND status = 'Active'
             ORDER BY order_number ASC",
            $course_id
        ));

        wp_send_json_success(array(
            'course' => $course,
            'lessons' => $lessons,
        ));
    }

    public function ajax_save_lesson_schedule() {
        check_ajax_referer('mtti_scheduler_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $schedule = isset($_POST['schedule']) ? $_POST['schedule'] : array();
        global $wpdb;

        $updated = 0;
        $errors = array();

        foreach ($schedule as $lesson_id => $data) {
            $lesson_id = intval($lesson_id);
            $release_week = isset($data['release_week']) && $data['release_week'] !== '' ? intval($data['release_week']) : NULL;
            $release_date = isset($data['release_date']) && $data['release_date'] !== '' ? sanitize_text_field($data['release_date']) : NULL;

            $result = $wpdb->update(
                $wpdb->prefix . 'mtti_lessons',
                array(
                    'release_week' => $release_week,
                    'release_date' => $release_date,
                ),
                array('lesson_id' => $lesson_id),
                array('%d', '%s'),
                array('%d')
            );

            if ($result !== false) {
                $updated++;
            } else {
                $errors[] = "Failed to update lesson ID: $lesson_id";
            }
        }

        if (empty($errors)) {
            wp_send_json_success(array(
                'message' => "✅ Successfully updated $updated lessons",
                'updated' => $updated,
            ));
        } else {
            wp_send_json_error(array(
                'message' => implode(', ', $errors),
                'updated' => $updated,
            ));
        }
    }

    public function ajax_apply_template() {
        check_ajax_referer('mtti_scheduler_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $template = sanitize_text_field($_POST['template']);
        $course_id = intval($_POST['course_id']);
        global $wpdb;

        $lessons = $wpdb->get_results($wpdb->prepare(
            "SELECT lesson_id, order_number FROM {$wpdb->prefix}mtti_lessons
             WHERE course_id = %d AND status = 'Active'
             ORDER BY order_number ASC",
            $course_id
        ));

        $schedule = array();

        foreach ($lessons as $index => $lesson) {
            $lesson_num = $index + 1;

            switch ($template) {
                case 'immediate':
                    $schedule[$lesson->lesson_id] = array(
                        'release_week' => 1,
                        'release_date' => '',
                    );
                    break;

                case 'daily':
                    $schedule[$lesson->lesson_id] = array(
                        'release_week' => NULL,
                        'release_date' => date('Y-m-d', strtotime("+$index days")),
                    );
                    break;

                case 'weekly':
                    $week = ceil($lesson_num / 1);
                    $schedule[$lesson->lesson_id] = array(
                        'release_week' => $week,
                        'release_date' => '',
                    );
                    break;

                case 'biweekly':
                    $week = ceil($lesson_num / 2) * 2 - 1;
                    $schedule[$lesson->lesson_id] = array(
                        'release_week' => $week,
                        'release_date' => '',
                    );
                    break;
            }
        }

        wp_send_json_success(array(
            'schedule' => $schedule,
            'lessons' => $lessons,
        ));
    }
}

new MTTI_Lesson_Scheduler();
?>
