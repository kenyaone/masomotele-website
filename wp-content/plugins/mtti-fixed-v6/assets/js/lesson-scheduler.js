jQuery(function($) {
    let currentCourse = null;
    let currentLessons = [];

    // Course selection
    $('#courseSelect').on('change', function() {
        currentCourse = $(this).val();

        if (!currentCourse) {
            $('#lessonsList').hide();
            $('#noSelection').show();
            $('#templateOptions').hide();
            return;
        }

        loadCourseLessons();
    });

    function loadCourseLessons() {
        $.ajax({
            url: mttiScheduler.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mtti_get_course_lessons',
                course_id: currentCourse,
                nonce: mttiScheduler.nonce,
            },
            success: function(response) {
                if (response.success) {
                    const { course, lessons } = response.data;
                    currentLessons = lessons;

                    $('#selectedCourseName').text(course.course_code + ' - ' + course.course_name);
                    $('#lessonCount').text(lessons.length + ' lessons');

                    renderLessonsTable(lessons);
                    updatePreview();

                    $('#noSelection').hide();
                    $('#lessonsList').show();
                    $('#templateOptions').show();
                }
            },
            error: function(error) {
                alert('Error loading lessons: ' + error.statusText);
            }
        });
    }

    function renderLessonsTable(lessons) {
        const tbody = $('#lessonsTableBody');
        tbody.empty();

        lessons.forEach((lesson, index) => {
            const row = $(`
                <tr data-lesson-id="${lesson.lesson_id}">
                    <td>${index + 1}</td>
                    <td>
                        <strong>${escapeHtml(lesson.title)}</strong>
                    </td>
                    <td>
                        <input type="number" class="release-week" min="1" max="52"
                               placeholder="Week #"
                               value="${lesson.release_week || ''}"
                               data-lesson-id="${lesson.lesson_id}">
                    </td>
                    <td>
                        <input type="date" class="release-date"
                               value="${lesson.release_date || ''}"
                               data-lesson-id="${lesson.lesson_id}">
                    </td>
                </tr>
            `);

            tbody.append(row);
        });

        // Add change listeners for preview updates
        $('#lessonsTableBody').on('change', '.release-week, .release-date', function() {
            updatePreview();
        });
    }

    function updatePreview() {
        const schedule = getScheduleFromForm();
        let preview = 'First lesson available immediately.<br>';

        let hasSchedule = false;
        for (let key in schedule) {
            if (schedule[key].release_week || schedule[key].release_date) {
                hasSchedule = true;
                break;
            }
        }

        if (!hasSchedule) {
            preview = '✅ All lessons available immediately upon enrollment.';
        } else {
            preview = '';
            currentLessons.forEach((lesson, index) => {
                const schedData = schedule[lesson.lesson_id];
                if (schedData.release_week) {
                    const days = (schedData.release_week - 1) * 7;
                    preview += `Week ${schedData.release_week} (${days} days): ${escapeHtml(lesson.title)}<br>`;
                } else if (schedData.release_date) {
                    preview += `${schedData.release_date}: ${escapeHtml(lesson.title)}<br>`;
                } else {
                    preview += `Immediately: ${escapeHtml(lesson.title)}<br>`;
                }
            });
        }

        $('#previewText').html(preview);
    }

    function getScheduleFromForm() {
        const schedule = {};
        $('#lessonsTableBody tr').each(function() {
            const lessonId = $(this).data('lesson-id');
            const releaseWeek = $(this).find('.release-week').val();
            const releaseDate = $(this).find('.release-date').val();

            schedule[lessonId] = {
                release_week: releaseWeek || '',
                release_date: releaseDate || '',
            };
        });
        return schedule;
    }

    // Template buttons
    $('.mtti-template-btn').on('click', function() {
        const template = $(this).data('template');

        if (template === 'custom') {
            alert('Custom mode: Edit the fields below to set your own schedule.');
            return;
        }

        if (!currentCourse) {
            alert('Please select a course first.');
            return;
        }

        applyTemplate(template);
    });

    function applyTemplate(template) {
        $.ajax({
            url: mttiScheduler.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mtti_apply_template',
                template: template,
                course_id: currentCourse,
                nonce: mttiScheduler.nonce,
            },
            success: function(response) {
                if (response.success) {
                    const { schedule, lessons } = response.data;

                    // Update form fields with template values
                    $('#lessonsTableBody tr').each(function() {
                        const lessonId = $(this).data('lesson-id');
                        if (schedule[lessonId]) {
                            $(this).find('.release-week').val(schedule[lessonId].release_week || '');
                            $(this).find('.release-date').val(schedule[lessonId].release_date || '');
                        }
                    });

                    updatePreview();
                    showNotification('Template applied! Review and save when ready.', 'info');
                }
            },
            error: function(error) {
                alert('Error applying template: ' + error.statusText);
            }
        });
    }

    // Save schedule
    $('#saveSchedule').on('click', function() {
        const schedule = getScheduleFromForm();

        $.ajax({
            url: mttiScheduler.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mtti_save_lesson_schedule',
                schedule: schedule,
                nonce: mttiScheduler.nonce,
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    loadCourseLessons(); // Refresh to show updated values
                } else {
                    showNotification(response.data.message || 'Error saving schedule', 'error');
                }
            },
            error: function(error) {
                showNotification('Error: ' + error.statusText, 'error');
            }
        });
    });

    function showNotification(message, type) {
        const $status = $('#saveStatus');
        $status.removeClass('success error').addClass(type).text(message).show();
        setTimeout(() => {
            $status.fadeOut();
        }, 5000);
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});
