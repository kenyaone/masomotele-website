<?php
/**
 * Lesson Scheduler - Simple Standalone
 */

// Add menu item
add_action('admin_menu', function() {
    add_submenu_page(
        'mtti-mis',
        'Lesson Scheduler',
        '📚 Lesson Scheduler',
        'manage_options',
        'lesson-scheduler-main',
        'render_lesson_scheduler_ui'
    );
});

// Render UI
function render_lesson_scheduler_ui() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    global $wpdb;
    $courses = $wpdb->get_results("SELECT course_id, course_code, course_name FROM {$wpdb->prefix}mtti_courses WHERE status='Active' ORDER BY course_name");
    ?>
    <div class="wrap">
        <h1>📚 Lesson Scheduler</h1>
        <p>Control when lessons unlock for learners.</p>

        <div style="max-width: 1200px;">
            <label><strong>Select Course:</strong></label>
            <select id="pickedCourse" style="padding: 8px; font-size: 14px; min-width: 300px;">
                <option value="">-- Choose --</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo $c->course_id; ?>"><?php echo $c->course_code.' - '.$c->course_name; ?></option>
                <?php endforeach; ?>
            </select>

            <div id="schedulerUI" style="display:none; margin-top: 30px;">
                <h3 id="courseName"></h3>

                <div style="margin: 20px 0;">
                    <button class="button" id="allBtn">All Now</button>
                    <button class="button" id="dayBtn">1/Day</button>
                    <button class="button" id="weekBtn">1/Week</button>
                </div>

                <table class="widefat" id="lessonsTable">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="width: 5%">#</th>
                            <th style="width: 50%">Title</th>
                            <th style="width: 22%">Week</th>
                            <th style="width: 23%">Date</th>
                        </tr>
                    </thead>
                    <tbody id="lessonRows"></tbody>
                </table>

                <button class="button button-primary" id="saveBtn" style="margin-top: 20px;">💾 Save</button>
                <span id="msg" style="margin-left: 15px;"></span>
            </div>
        </div>
    </div>

    <script>
    jQuery(function($) {
        let lessons = [];

        $('#pickedCourse').change(function() {
            if (!this.value) return;
            $.post(ajaxurl, {
                action: 'load_scheduler_lessons',
                course: this.value,
                nonce: '<?php echo wp_create_nonce('scheduler'); ?>'
            }, function(r) {
                if (r.success) {
                    lessons = r.data.lessons;
                    $('#courseName').text(r.data.course);
                    renderTable();
                    $('#schedulerUI').show();
                }
            });
        });

        function renderTable() {
            let html = '';
            lessons.forEach((L, i) => {
                html += `<tr>
                    <td>${i+1}</td>
                    <td><strong>${L.title}</strong></td>
                    <td><input type="number" class="week" data-id="${L.lesson_id}" value="${L.release_week || ''}" min="1" style="width:80px; padding:6px;"></td>
                    <td><input type="date" class="date" data-id="${L.lesson_id}" value="${L.release_date || ''}" style="width:120px; padding:6px;"></td>
                </tr>`;
            });
            $('#lessonRows').html(html);
        }

        $('#allBtn').click(function() {
            $('.week').val('1');
            $('.date').val('');
        });

        $('#dayBtn').click(function() {
            $('.date').val('');
            $('.week').each(function(i) {
                let d = new Date();
                d.setDate(d.getDate() + i);
                $(this).val('').parent().next().find('input').val(d.toISOString().split('T')[0]);
            });
        });

        $('#weekBtn').click(function() {
            $('.date').val('');
            $('.week').each(function(i) {
                $(this).val(Math.ceil((i+1) / 4));
            });
        });

        $('#saveBtn').click(function() {
            let schedule = {};
            $('.week, .date').each(function() {
                let id = $(this).data('id');
                if (!schedule[id]) schedule[id] = {};
                if ($(this).hasClass('week')) {
                    schedule[id].w = $(this).val();
                } else {
                    schedule[id].d = $(this).val();
                }
            });

            $.post(ajaxurl, {
                action: 'save_scheduler_lessons',
                course: $('#pickedCourse').val(),
                schedule: schedule,
                nonce: '<?php echo wp_create_nonce('scheduler'); ?>'
            }, function(r) {
                let m = $('#msg');
                if (r.success) {
                    m.html('✅ '+r.data).css('color','green');
                } else {
                    m.html('❌ Error').css('color','red');
                }
                setTimeout(() => m.html(''), 3000);
            });
        });
    });
    </script>
    <?php
}

// AJAX: Load lessons
add_action('wp_ajax_load_scheduler_lessons', function() {
    check_ajax_referer('scheduler');
    if (!current_user_can('manage_options')) wp_send_json_error();

    $cid = intval($_POST['course']);
    global $wpdb;

    $c = $wpdb->get_row($wpdb->prepare("SELECT course_code, course_name FROM {$wpdb->prefix}mtti_courses WHERE course_id=%d", $cid));
    $L = $wpdb->get_results($wpdb->prepare("SELECT lesson_id, title, release_week, release_date FROM {$wpdb->prefix}mtti_lessons WHERE course_id=%d AND status='Active' ORDER BY order_number", $cid));

    wp_send_json_success(['course' => $c->course_code.' - '.$c->course_name, 'lessons' => $L]);
});

// AJAX: Save lessons
add_action('wp_ajax_save_scheduler_lessons', function() {
    check_ajax_referer('scheduler');
    if (!current_user_can('manage_options')) wp_send_json_error();

    $cid = intval($_POST['course']);
    $sched = $_POST['schedule'] ?? [];
    global $wpdb;

    $cnt = 0;
    foreach ($sched as $lid => $data) {
        $wpdb->update(
            $wpdb->prefix.'mtti_lessons',
            ['release_week' => $data['w'] ?: NULL, 'release_date' => $data['d'] ?: NULL],
            ['lesson_id' => intval($lid)],
            ['%d', '%s'],
            ['%d']
        );
        $cnt++;
    }

    wp_send_json_success("Updated $cnt lessons");
});
