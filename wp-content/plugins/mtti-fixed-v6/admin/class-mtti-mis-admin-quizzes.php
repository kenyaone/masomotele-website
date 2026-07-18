<?php
/**
 * Quizzes Admin Class
 * Manages creation, editing, and grading of structured quizzes with question banks
 */
class MTTI_MIS_Admin_Quizzes {

    private $plugin_name;
    private $version;
    private $db;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = MTTI_MIS_Database::get_instance();
    }

    public function display() {
        // Handle form submissions
        if (isset($_POST['mtti_quiz_submit'])) {
            check_admin_referer('mtti_quiz_action', 'mtti_quiz_nonce');
            $this->handle_form_submission();
            return;
        }

        if (isset($_POST['mtti_question_submit'])) {
            check_admin_referer('mtti_question_action', 'mtti_question_nonce');
            $this->handle_question_submission();
            return;
        }

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $quiz_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        switch ($action) {
            case 'add':
                $this->display_add_form();
                break;
            case 'edit':
                $this->display_edit_form($quiz_id);
                break;
            case 'questions':
                $this->display_questions_manager($quiz_id);
                break;
            case 'attempts':
                $this->display_attempts($quiz_id);
                break;
            case 'delete':
                $this->handle_delete($quiz_id);
                break;
            default:
                $this->display_list();
        }
    }

    private function display_list() {
        $quizzes = $this->db->get_quizzes(array('limit' => 100));
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Quizzes</h1>
            <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes&action=add'); ?>" class="page-title-action">Create Quiz</a>
            <hr class="wp-header-end">

            <?php if (isset($_GET['message'])) : ?>
            <div class="notice <?php echo ($_GET['message'] === 'deleted') ? 'notice-warning' : 'notice-success'; ?> is-dismissible">
                <p>
                    <?php
                    switch ($_GET['message']) {
                        case 'created': echo 'Quiz created successfully!'; break;
                        case 'updated': echo 'Quiz updated successfully!'; break;
                        case 'deleted': echo 'Quiz deleted successfully!'; break;
                    }
                    ?>
                </p>
            </div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Unit</th>
                        <th>Pass Mark</th>
                        <th>Max Attempts</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Questions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quizzes)) : ?>
                    <tr><td colspan="9" style="text-align: center; padding: 20px;">No quizzes found. <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes&action=add'); ?>">Create one</a></td></tr>
                    <?php else : ?>
                        <?php foreach ($quizzes as $quiz) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($quiz->title); ?></strong></td>
                            <td><?php echo esc_html($quiz->course_code . ' - ' . $quiz->course_name); ?></td>
                            <td><?php echo $quiz->unit_name ? esc_html($quiz->unit_name) : '—'; ?></td>
                            <td><?php echo esc_html($quiz->pass_mark . '%'); ?></td>
                            <td><?php echo $quiz->max_attempts > 0 ? esc_html($quiz->max_attempts) : '∞'; ?></td>
                            <td><?php echo $quiz->is_final ? '<span class="badge">Final</span>' : '<span class="badge" style="background: #ccc;">Practice</span>'; ?></td>
                            <td><?php echo esc_html($quiz->status); ?></td>
                            <td><?php echo intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}mtti_quiz_questions WHERE quiz_id = %d", $quiz->quiz_id))); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes&action=edit&id=' . $quiz->quiz_id); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes&action=questions&id=' . $quiz->quiz_id); ?>" class="button button-small">Questions</a>
                                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes&action=attempts&id=' . $quiz->quiz_id); ?>" class="button button-small">Attempts</a>
                                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes&action=delete&id=' . $quiz->quiz_id); ?>" class="button button-small" onclick="return confirm('Delete this quiz? This cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function display_add_form() {
        $courses = $this->db->get_courses();
        ?>
        <div class="wrap">
            <h1>Create Quiz</h1>

            <form method="post" class="mtti-form" style="max-width: 600px;">
                <?php wp_nonce_field('mtti_quiz_action', 'mtti_quiz_nonce'); ?>
                <input type="hidden" name="mtti_quiz_submit" value="1">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="quiz_title">Title *</label></th>
                        <td><input type="text" name="quiz_title" id="quiz_title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_course">Course *</label></th>
                        <td>
                            <select name="quiz_course" id="quiz_course" class="regular-text" required>
                                <option value="">Select a course...</option>
                                <?php foreach ($courses as $course) : ?>
                                <option value="<?php echo esc_attr($course->course_id); ?>"><?php echo esc_html($course->course_code . ' - ' . $course->course_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_description">Description</label></th>
                        <td><textarea name="quiz_description" id="quiz_description" class="large-text" rows="4"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_pass_mark">Pass Mark (%) *</label></th>
                        <td><input type="number" name="quiz_pass_mark" id="quiz_pass_mark" class="small-text" value="70" min="0" max="100" step="0.01" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_max_attempts">Max Attempts (0 = unlimited)</label></th>
                        <td><input type="number" name="quiz_max_attempts" id="quiz_max_attempts" class="small-text" value="0" min="0" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_time_limit">Time Limit (minutes, 0 = no limit)</label></th>
                        <td><input type="number" name="quiz_time_limit" id="quiz_time_limit" class="small-text" value="0" min="0" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_shuffle">Shuffle Questions</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="quiz_shuffle" id="quiz_shuffle" value="1" checked>
                                Randomize question order for each student
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_is_final">Final Quiz</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="quiz_is_final" id="quiz_is_final" value="1">
                                Mark as final assessment (counts toward unit completion)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_status">Status</label></th>
                        <td>
                            <select name="quiz_status" id="quiz_status" class="regular-text">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Draft">Draft</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Create Quiz', 'primary', 'submit'); ?>
            </form>
        </div>
        <?php
    }

    private function display_edit_form($quiz_id) {
        $quiz = $this->db->get_quiz($quiz_id);
        if (!$quiz) {
            wp_die('Quiz not found');
        }

        $courses = $this->db->get_courses();
        ?>
        <div class="wrap">
            <h1>Edit Quiz: <?php echo esc_html($quiz->title); ?></h1>

            <form method="post" class="mtti-form" style="max-width: 600px;">
                <?php wp_nonce_field('mtti_quiz_action', 'mtti_quiz_nonce'); ?>
                <input type="hidden" name="mtti_quiz_submit" value="1">
                <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="quiz_title">Title *</label></th>
                        <td><input type="text" name="quiz_title" id="quiz_title" class="regular-text" value="<?php echo esc_attr($quiz->title); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_course">Course *</label></th>
                        <td>
                            <select name="quiz_course" id="quiz_course" class="regular-text" required>
                                <?php foreach ($courses as $course) : ?>
                                <option value="<?php echo esc_attr($course->course_id); ?>" <?php selected($quiz->course_id, $course->course_id); ?>>
                                    <?php echo esc_html($course->course_code . ' - ' . $course->course_name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_description">Description</label></th>
                        <td><textarea name="quiz_description" id="quiz_description" class="large-text" rows="4"><?php echo esc_textarea($quiz->description); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_pass_mark">Pass Mark (%) *</label></th>
                        <td><input type="number" name="quiz_pass_mark" id="quiz_pass_mark" class="small-text" value="<?php echo esc_attr($quiz->pass_mark); ?>" min="0" max="100" step="0.01" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_max_attempts">Max Attempts (0 = unlimited)</label></th>
                        <td><input type="number" name="quiz_max_attempts" id="quiz_max_attempts" class="small-text" value="<?php echo esc_attr($quiz->max_attempts); ?>" min="0" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_time_limit">Time Limit (minutes, 0 = no limit)</label></th>
                        <td><input type="number" name="quiz_time_limit" id="quiz_time_limit" class="small-text" value="<?php echo esc_attr($quiz->time_limit_minutes); ?>" min="0" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_shuffle">Shuffle Questions</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="quiz_shuffle" id="quiz_shuffle" value="1" <?php checked($quiz->shuffle_questions, 1); ?>>
                                Randomize question order for each student
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_is_final">Final Quiz</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="quiz_is_final" id="quiz_is_final" value="1" <?php checked($quiz->is_final, 1); ?>>
                                Mark as final assessment (counts toward unit completion)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quiz_status">Status</label></th>
                        <td>
                            <select name="quiz_status" id="quiz_status" class="regular-text">
                                <option value="Active" <?php selected($quiz->status, 'Active'); ?>>Active</option>
                                <option value="Inactive" <?php selected($quiz->status, 'Inactive'); ?>>Inactive</option>
                                <option value="Draft" <?php selected($quiz->status, 'Draft'); ?>>Draft</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Update Quiz', 'primary', 'submit'); ?>
                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes'); ?>" class="button">Cancel</a>
            </form>
        </div>
        <?php
    }

    private function display_questions_manager($quiz_id) {
        global $wpdb;
        $quiz = $this->db->get_quiz($quiz_id);
        if (!$quiz) {
            wp_die('Quiz not found');
        }

        $questions = $this->db->get_quiz_questions($quiz_id);
        ?>
        <div class="wrap">
            <h1>Manage Questions: <?php echo esc_html($quiz->title); ?></h1>
            <p class="description">Add, edit, and manage questions for this quiz. Correct answers are stored securely and never shown to students before submission.</p>

            <hr class="wp-header-end">

            <h2>Add Question</h2>
            <form method="post" class="mtti-form" style="max-width: 800px;">
                <?php wp_nonce_field('mtti_question_action', 'mtti_question_nonce'); ?>
                <input type="hidden" name="mtti_question_submit" value="1">
                <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="question_type">Question Type *</label></th>
                        <td>
                            <select name="question_type" id="question_type" class="regular-text" required onchange="this.form.submit();">
                                <option value="">Select a type...</option>
                                <option value="mcq">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="fill_blank">Fill in the Blank</option>
                            </select>
                        </td>
                    </tr>

                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_type'])) :
                        $type = sanitize_text_field($_POST['question_type']);
                    ?>
                        <tr>
                            <th scope="row"><label for="question_text">Question Text *</label></th>
                            <td><textarea name="question_text" id="question_text" class="large-text" rows="3" required></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="question_points">Points *</label></th>
                            <td><input type="number" name="question_points" id="question_points" class="small-text" value="1" min="0.01" step="0.01" required></td>
                        </tr>

                        <?php if ($type === 'mcq') : ?>
                        <tr>
                            <th scope="row"><label>Options (one per line) *</label></th>
                            <td><textarea name="question_options" class="large-text" rows="5" placeholder="Option A&#10;Option B&#10;Option C&#10;Option D" required></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="correct_index">Correct Answer (option number, 1-indexed) *</label></th>
                            <td><input type="number" name="correct_index" id="correct_index" class="small-text" value="1" min="1" required></td>
                        </tr>
                        <?php elseif ($type === 'true_false') : ?>
                        <tr>
                            <th scope="row"><label for="correct_answer">Correct Answer *</label></th>
                            <td>
                                <select name="correct_answer" id="correct_answer" class="regular-text" required>
                                    <option value="">Select...</option>
                                    <option value="true">True</option>
                                    <option value="false">False</option>
                                </select>
                            </td>
                        </tr>
                        <?php elseif ($type === 'fill_blank') : ?>
                        <tr>
                            <th scope="row"><label for="correct_answer">Correct Answer (case-insensitive) *</label></th>
                            <td><input type="text" name="correct_answer" id="correct_answer" class="regular-text" required></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <th scope="row"><label for="explanation">Explanation (shown after submission)</label></th>
                            <td><textarea name="explanation" id="explanation" class="large-text" rows="3"></textarea></td>
                        </tr>
                    <?php endif; ?>
                </table>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_type'])) : ?>
                    <?php submit_button('Add Question', 'primary', 'submit'); ?>
                <?php endif; ?>
            </form>

            <hr>
            <h2>Questions (<?php echo count($questions); ?>)</h2>

            <?php if (empty($questions)) : ?>
                <p style="padding: 20px; background: #f5f5f5; text-align: center;">No questions added yet. Add one above.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Type</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q) : ?>
                        <tr>
                            <td><?php echo esc_html($q->order_number); ?></td>
                            <td><?php echo esc_html(substr($q->question_text, 0, 60)) . (strlen($q->question_text) > 60 ? '...' : ''); ?></td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $q->question_type))); ?></td>
                            <td><?php echo esc_html($q->points); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes&action=questions&id=' . $quiz_id . '&edit=' . $q->question_id); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes&action=questions&id=' . $quiz_id . '&delete_q=' . $q->question_id); ?>" class="button button-small" onclick="return confirm('Delete this question?');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p style="margin-top: 20px;">
                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes&action=edit&id=' . $quiz_id); ?>" class="button">Edit Quiz Details</a>
                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes'); ?>" class="button">Back to Quizzes</a>
            </p>
        </div>
        <?php
    }

    private function display_attempts($quiz_id) {
        global $wpdb;
        $quiz = $this->db->get_quiz($quiz_id);
        if (!$quiz) {
            wp_die('Quiz not found');
        }

        $attempts = $wpdb->get_results($wpdb->prepare("
            SELECT qa.*, u.display_name, s.admission_number
            FROM {$wpdb->prefix}mtti_quiz_attempts qa
            LEFT JOIN {$wpdb->users} u ON qa.student_id = u.ID
            LEFT JOIN {$wpdb->prefix}mtti_students s ON qa.student_id = s.user_id
            WHERE qa.quiz_id = %d
            ORDER BY qa.attempted_at DESC
        ", $quiz_id));
        ?>
        <div class="wrap">
            <h1>Quiz Attempts: <?php echo esc_html($quiz->title); ?></h1>
            <p class="description"><?php echo count($attempts); ?> student(s) have attempted this quiz.</p>

            <hr class="wp-header-end">

            <?php if (empty($attempts)) : ?>
                <p style="padding: 20px; background: #f5f5f5; text-align: center;">No attempts yet.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Admission #</th>
                            <th>Score</th>
                            <th>Pass Mark</th>
                            <th>Result</th>
                            <th>Attempt</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attempts as $attempt) : ?>
                        <tr>
                            <td><?php echo esc_html($attempt->display_name); ?></td>
                            <td><?php echo esc_html($attempt->admission_number); ?></td>
                            <td><strong><?php echo esc_html($attempt->score . '/' . $attempt->total); ?></strong></td>
                            <td><?php echo esc_html($quiz->pass_mark . '%'); ?></td>
                            <td>
                                <?php if ($attempt->passed) : ?>
                                    <span style="background: #0d9e3a; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">PASSED</span>
                                <?php else : ?>
                                    <span style="background: #d92e25; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">FAILED</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($attempt->attempt_number); ?></td>
                            <td><?php echo esc_html(date('M d, Y g:i A', strtotime($attempt->attempted_at))); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p style="margin-top: 20px;">
                <a href="<?php echo admin_url('admin.php?page=mtti-mis-quizzes'); ?>" class="button">Back to Quizzes</a>
            </p>
        </div>
        <?php
    }

    private function handle_form_submission() {
        global $wpdb;

        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        $title = sanitize_text_field($_POST['quiz_title']);
        $course_id = intval($_POST['quiz_course']);
        $description = sanitize_textarea_field($_POST['quiz_description']);
        $pass_mark = floatval($_POST['quiz_pass_mark']);
        $max_attempts = intval($_POST['quiz_max_attempts']);
        $time_limit = intval($_POST['quiz_time_limit']) ?: null;
        $shuffle = isset($_POST['quiz_shuffle']) ? 1 : 0;
        $is_final = isset($_POST['quiz_is_final']) ? 1 : 0;
        $status = sanitize_text_field($_POST['quiz_status']);

        $user = wp_get_current_user();
        $staff_id = $wpdb->get_var($wpdb->prepare("SELECT staff_id FROM {$wpdb->prefix}mtti_staff WHERE user_id = %d", $user->ID));

        $data = array(
            'course_id'     => $course_id,
            'staff_id'      => $staff_id ?: 0,
            'title'         => $title,
            'description'   => $description,
            'pass_mark'     => $pass_mark,
            'max_attempts'  => $max_attempts,
            'time_limit_minutes' => $time_limit,
            'shuffle_questions' => $shuffle,
            'is_final'      => $is_final,
            'status'        => $status,
        );

        if ($quiz_id > 0) {
            $this->db->update_quiz($quiz_id, $data);
            wp_redirect(admin_url('admin.php?page=mtti-mis-quizzes&message=updated'));
        } else {
            $this->db->insert_quiz($data);
            wp_redirect(admin_url('admin.php?page=mtti-mis-quizzes&message=created'));
        }
        exit;
    }

    private function handle_question_submission() {
        global $wpdb;

        $quiz_id = intval($_POST['quiz_id']);
        $question_type = sanitize_text_field($_POST['question_type']);
        $question_text = sanitize_textarea_field($_POST['question_text']);
        $points = floatval($_POST['question_points']);
        $explanation = sanitize_textarea_field($_POST['explanation']);

        // Get next order number
        $max_order = intval($wpdb->get_var($wpdb->prepare(
            "SELECT MAX(order_number) FROM {$wpdb->prefix}mtti_quiz_questions WHERE quiz_id = %d",
            $quiz_id
        ))) + 1;

        $correct_answer = '';
        $options = null;

        if ($question_type === 'mcq') {
            $option_lines = array_filter(array_map('trim', explode("\n", $_POST['question_options'])));
            $options = json_encode($option_lines);
            $correct_index = intval($_POST['correct_index']) - 1;
            $correct_answer = json_encode($correct_index);
        } elseif ($question_type === 'true_false') {
            $correct_answer = sanitize_text_field($_POST['correct_answer']);
            $options = json_encode(['True', 'False']);
        } elseif ($question_type === 'fill_blank') {
            $correct_answer = sanitize_text_field($_POST['correct_answer']);
        }

        $data = array(
            'quiz_id'        => $quiz_id,
            'question_text'  => $question_text,
            'question_type'  => $question_type,
            'options'        => $options,
            'correct_answer' => $correct_answer,
            'points'         => $points,
            'order_number'   => $max_order,
            'explanation'    => $explanation,
            'status'         => 'Active',
        );

        $this->db->insert_quiz_question($data);
        wp_redirect(admin_url('admin.php?page=mtti-mis-quizzes&action=questions&id=' . $quiz_id));
        exit;
    }

    private function handle_delete($quiz_id) {
        global $wpdb;

        if (!current_user_can('manage_assessments')) {
            wp_die('You do not have permission to delete quizzes');
        }

        $wpdb->delete($wpdb->prefix . 'mtti_quizzes', array('quiz_id' => $quiz_id));
        $wpdb->delete($wpdb->prefix . 'mtti_quiz_questions', array('quiz_id' => $quiz_id));
        $wpdb->delete($wpdb->prefix . 'mtti_quiz_attempts', array('quiz_id' => $quiz_id));

        wp_redirect(admin_url('admin.php?page=mtti-mis-quizzes&message=deleted'));
        exit;
    }
}
