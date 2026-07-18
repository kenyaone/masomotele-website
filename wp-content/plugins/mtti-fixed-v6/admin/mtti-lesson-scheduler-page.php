<?php
/**
 * MTTI Lesson Scheduler - Standalone Admin Page
 * Access: MTTI MIS → Lesson Scheduler
 */

if (!defined('ABSPATH')) exit;

// Register the admin page
add_action('admin_menu', function() {
    add_submenu_page(
        'mtti-mis',
        'Lesson Scheduler',
        '📚 Lesson Scheduler',
        'manage_options',
        'mtti-scheduler',
        'mtti_render_scheduler_page'
    );
});

// Render the page
function mtti_render_scheduler_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    global $wpdb;
    $courses = $wpdb->get_results("
        SELECT course_id, course_code, course_name
        FROM {$wpdb->prefix}mtti_courses
        WHERE status = 'Active'
        ORDER BY course_name
    ");
    ?>
    <div class="wrap" style="max-width: 1400px;">
        <h1 style="margin-bottom: 10px;">📚 Lesson Scheduler</h1>
        <p style="color: #666; font-size: 16px; margin-bottom: 30px;">
            Control when lessons unlock for learners. Set custom schedules per course.
        </p>

        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 30px;">

            <!-- SIDEBAR -->
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; height: fit-content; border: 1px solid #e0e0e0;">

                <h3 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 700; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                    📖 Select Course
                </h3>

                <select id="scheduleCoursePicker" style="width: 100%; padding: 10px 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; background: white;">
                    <option value="">-- Choose a Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo esc_attr($course->course_id); ?>">
                            <?php echo esc_html($course->course_code . ' - ' . $course->course_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div id="schedulerTemplates" style="display: none; margin-top: 30px;">
                    <h3 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 700; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                        ⚡ Quick Apply
                    </h3>

                    <button type="button" class="scheduler-template-btn" data-template="all" style="width: 100%; padding: 10px 12px; margin: 8px 0; background: #fff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-size: 13px; text-align: left; font-weight: 500; transition: all 0.2s;">
                        ⚡ All Available Now
                    </button>

                    <button type="button" class="scheduler-template-btn" data-template="daily" style="width: 100%; padding: 10px 12px; margin: 8px 0; background: #fff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-size: 13px; text-align: left; font-weight: 500; transition: all 0.2s;">
                        📅 1 Lesson/Day
                    </button>

                    <button type="button" class="scheduler-template-btn" data-template="weekly" style="width: 100%; padding: 10px 12px; margin: 8px 0; background: #fff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-size: 13px; text-align: left; font-weight: 500; transition: all 0.2s;">
                        📆 1 Lesson/Week
                    </button>

                    <button type="button" class="scheduler-template-btn" data-template="biweekly" style="width: 100%; padding: 10px 12px; margin: 8px 0; background: #fff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-size: 13px; text-align: left; font-weight: 500; transition: all 0.2s;">
                        📊 1 Lesson/2 Weeks
                    </button>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0;">

                <div id="schedulerContent" style="display: none;">

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #eee;">
                        <h2 id="schedulerCourseTitle" style="margin: 0; font-size: 18px; color: #333; font-weight: 600;"></h2>
                        <span id="schedulerLessonCount" style="background: #0073aa; color: white; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;"></span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                                    <th style="padding: 12px 15px; text-align: left; font-size: 12px; font-weight: 600; color: #333; width: 5%;">#</th>
                                    <th style="padding: 12px 15px; text-align: left; font-size: 12px; font-weight: 600; color: #333; width: 50%;">Lesson Title</th>
                                    <th style="padding: 12px 15px; text-align: left; font-size: 12px; font-weight: 600; color: #333; width: 22.5%;">Release Week</th>
                                    <th style="padding: 12px 15px; text-align: left; font-size: 12px; font-weight: 600; color: #333; width: 22.5%;">Release Date</th>
                                </tr>
                            </thead>
                            <tbody id="schedulerLessonsTable"></tbody>
                        </table>
                    </div>

                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <button type="button" id="schedulerSaveBtn" class="button button-primary button-large" style="padding: 10px 30px; font-size: 15px; font-weight: 600;">
                                💾 Save Schedule
                            </button>
                            <span id="schedulerSaveStatus" style="margin-left: 15px; font-weight: 600; display: none;"></span>
                        </div>
                    </div>

                </div>

                <div id="schedulerEmpty" style="text-align: center; padding: 80px 20px; color: #999;">
                    <p style="font-size: 18px; margin: 0;">👈 Select a course to get started</p>
                </div>

            </div>

        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let currentCourse = null;
        let currentLessons = [];

        // Course picker
        $('#scheduleCoursePicker').on('change', function() {
            currentCourse = $(this).val();
            if (!currentCourse) {
                $('#schedulerContent').hide();
                $('#schedulerEmpty').show();
                $('#schedulerTemplates').hide();
                return;
            }
            loadLessons();
        });

        // Load lessons
        function loadLessons() {
            $.post(ajaxurl, {
                action: 'mtti_get_scheduler_lessons',
                course_id: currentCourse,
                nonce: '<?php echo wp_create_nonce('mtti_scheduler'); ?>'
            }, function(response) {
                if (response.success) {
                    currentLessons = response.data.lessons;
                    const course = response.data.course;

                    $('#schedulerCourseTitle').text(course.course_code + ' - ' + course.course_name);
                    $('#schedulerLessonCount').text(currentLessons.length + ' lessons');

                    renderLessonsTable(currentLessons);

                    $('#schedulerEmpty').hide();
                    $('#schedulerContent').show();
                    $('#schedulerTemplates').show();
                }
            }, 'json');
        }

        // Render table
        function renderLessonsTable(lessons) {
            const tbody = $('#schedulerLessonsTable');
            tbody.empty();

            lessons.forEach((lesson, idx) => {
                const row = $(`
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px 15px; font-size: 13px;">${idx + 1}</td>
                        <td style="padding: 12px 15px; font-size: 13px;"><strong>${escapeHtml(lesson.title)}</strong></td>
                        <td style="padding: 12px 15px;">
                            <input type="number" class="lesson-week" data-lesson="${lesson.lesson_id}"
                                   min="1" max="52" placeholder="Week"
                                   value="${lesson.release_week || ''}"
                                   style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                        </td>
                        <td style="padding: 12px 15px;">
                            <input type="date" class="lesson-date" data-lesson="${lesson.lesson_id}"
                                   value="${lesson.release_date || ''}"
                                   style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                        </td>
                    </tr>
                `);
                tbody.append(row);
            });
        }

        // Template buttons
        $('.scheduler-template-btn').on('click', function() {
            const template = $(this).data('template');

            if (template === 'all') {
                $('.lesson-week').val('1');
                $('.lesson-date').val('');
            } else if (template === 'daily') {
                $('.lesson-week').val('');
                $('.lesson-date').each(function(idx) {
                    const date = new Date();
                    date.setDate(date.getDate() + idx);
                    $(this).val(formatDate(date));
                });
            } else if (template === 'weekly') {
                $('.lesson-week').each(function(idx) {
                    $(this).val(Math.ceil((idx + 1) / 4));
                });
                $('.lesson-date').val('');
            } else if (template === 'biweekly') {
                $('.lesson-week').each(function(idx) {
                    $(this).val(Math.ceil((idx + 1) / 2) * 2 - 1);
                });
                $('.lesson-date').val('');
            }
        });

        // Save
        $('#schedulerSaveBtn').on('click', function() {
            const schedule = {};

            $('.lesson-week, .lesson-date').each(function() {
                const lessonId = $(this).data('lesson');
                if (!schedule[lessonId]) {
                    schedule[lessonId] = {};
                }
                if ($(this).hasClass('lesson-week')) {
                    schedule[lessonId].week = $(this).val();
                } else {
                    schedule[lessonId].date = $(this).val();
                }
            });

            $.post(ajaxurl, {
                action: 'mtti_save_scheduler_lessons',
                course_id: currentCourse,
                schedule: schedule,
                nonce: '<?php echo wp_create_nonce('mtti_scheduler'); ?>'
            }, function(response) {
                const status = $('#schedulerSaveStatus');
                if (response.success) {
                    status.removeClass('error').addClass('success').text('✅ ' + response.data.message).show();
                    setTimeout(() => status.fadeOut(), 4000);
                } else {
                    status.removeClass('success').addClass('error').text('❌ ' + response.data.message).show();
                }
            }, 'json');
        });

        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    });
    </script>

    <style>
        #schedulerSaveStatus.success {
            background: #d4edda;
            color: #155724;
            padding: 10px 16px;
            border-radius: 4px;
        }
        #schedulerSaveStatus.error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 16px;
            border-radius: 4px;
        }
        .scheduler-template-btn:hover {
            background: #f0f6ff !important;
            border-color: #0073aa !important;
        }
    </style>
    <?php
}

// AJAX: Get lessons for course
add_action('wp_ajax_mtti_get_scheduler_lessons', function() {
    check_ajax_referer('mtti_scheduler');

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
        "SELECT lesson_id, title, release_week, release_date FROM {$wpdb->prefix}mtti_lessons
         WHERE course_id = %d AND status = 'Active'
         ORDER BY order_number ASC",
        $course_id
    ));

    wp_send_json_success(['course' => $course, 'lessons' => $lessons]);
});

// AJAX: Save schedule
add_action('wp_ajax_mtti_save_scheduler_lessons', function() {
    check_ajax_referer('mtti_scheduler');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $course_id = intval($_POST['course_id']);
    $schedule = isset($_POST['schedule']) ? $_POST['schedule'] : [];
    global $wpdb;

    $updated = 0;
    foreach ($schedule as $lesson_id => $data) {
        $lesson_id = intval($lesson_id);
        $week = (!empty($data['week']) && $data['week'] !== '') ? intval($data['week']) : NULL;
        $date = (!empty($data['date']) && $data['date'] !== '') ? sanitize_text_field($data['date']) : NULL;

        $wpdb->update(
            $wpdb->prefix . 'mtti_lessons',
            ['release_week' => $week, 'release_date' => $date],
            ['lesson_id' => $lesson_id],
            ['%d', '%s'],
            ['%d']
        );
        $updated++;
    }

    wp_send_json_success(['message' => "Updated $updated lessons"]);
});
