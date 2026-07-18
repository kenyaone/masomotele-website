<?php
/**
 * MTTI MIS Rubric-Based Grading Interface
 *
 * Comprehensive grading tool for rigorous assessment
 * Supports rubric-based, point-based grading with audit trails
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Admin_Rubric_Grader {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Display the rubric grading interface
     */
    public function display_grading_interface() {
        global $wpdb;

        // Permission check
        if (!current_user_can('manage_mtti') && !current_user_can('manage_options')) {
            wp_die('You do not have permission to grade assignments.');
        }

        $submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;
        $assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

        if (!$submission_id || !$assignment_id) {
            echo '<div class="notice notice-error"><p>Invalid submission or assignment ID.</p></div>';
            return;
        }

        // Get submission details
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_assignment_submissions WHERE submission_id = %d",
            $submission_id
        ));

        if (!$submission) {
            echo '<div class="notice notice-error"><p>Submission not found.</p></div>';
            return;
            return;
        }

        // Get student info
        $student = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_users WHERE ID = %d",
            $submission->student_id
        ));

        // Get assignment
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_assignments WHERE assignment_id = %d",
            $assignment_id
        ));

        // Get rubric for this assignment
        $rubric = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_grading_rubrics WHERE assignment_id = %d",
            $assignment_id
        ));

        if (!$rubric) {
            echo '<div class="notice notice-error"><p>No rubric found for this assignment.</p></div>';
            return;
        }

        // Get rubric criteria
        $criteria = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_rubric_criteria WHERE rubric_id = %d ORDER BY order_number",
            $rubric->rubric_id
        ));

        // Get performance levels for each criterion
        $levels_by_criteria = array();
        foreach ($criteria as $criterion) {
            $levels = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM wpcu_mtti_rubric_levels WHERE criteria_id = %d ORDER BY level_order",
                $criterion->criteria_id
            ));
            $levels_by_criteria[$criterion->criteria_id] = $levels;
        }

        // Get existing grading if any
        $existing_grade = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_grading_log WHERE submission_id = %d AND is_submitted = TRUE ORDER BY action_timestamp DESC LIMIT 1",
            $submission_id
        ));

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grade_submission'])) {
            $this->process_grading($submission_id, $assignment_id, $rubric->rubric_id, $criteria);
            echo '<div class="notice notice-success"><p>✅ Grade submitted successfully!</p></div>';
        }

        // Display grading interface
        $this->render_grading_form($submission, $student, $assignment, $rubric, $criteria, $levels_by_criteria, $existing_grade);
    }

    /**
     * Render the grading form
     */
    private function render_grading_form($submission, $student, $assignment, $rubric, $criteria, $levels_by_criteria, $existing_grade) {
        ?>
        <div class="wrap">
            <h1>📝 Rubric Grading Interface</h1>

            <!-- Student & Assignment Info -->
            <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="margin-top: 0;">Assignment: <?php echo esc_html($assignment->title); ?></h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <strong>Student:</strong><br>
                        <?php echo esc_html($student->display_name); ?> (ID: <?php echo $student->ID; ?>)
                    </div>
                    <div>
                        <strong>Submission Date:</strong><br>
                        <?php echo esc_html($submission->submitted_at); ?>
                    </div>
                    <div>
                        <strong>Current Grade:</strong><br>
                        <?php echo ($submission->score !== null) ? $submission->score . ' / ' . $rubric->max_score : 'Not graded'; ?>
                    </div>
                </div>
            </div>

            <!-- Rubric Grading Form -->
            <form method="POST" style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
                <h3>Rubric Scoring (<?php echo $rubric->max_score; ?> points total)</h3>

                <?php wp_nonce_field('mtti_grade_submission_' . $submission->submission_id); ?>
                <input type="hidden" name="grade_submission" value="1">
                <input type="hidden" name="submission_id" value="<?php echo $submission->submission_id; ?>">
                <input type="hidden" name="assignment_id" value="<?php echo $assignment->assignment_id; ?>">

                <!-- Criteria Scoring Grid -->
                <div style="margin-bottom: 30px;">
                    <?php
                    $total_points = 0;
                    foreach ($criteria as $index => $criterion):
                        $levels = $levels_by_criteria[$criterion->criteria_id];
                        $field_name = 'criterion_' . $criterion->criteria_id;
                        $selected_level = isset($_POST[$field_name]) ? intval($_POST[$field_name]) : null;
                        ?>
                        <div style="background: #fafafa; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #0073aa;">
                            <h4 style="margin: 0 0 10px 0;"><?php echo esc_html($criterion->criteria_name); ?> (<?php echo $criterion->max_points; ?> pts)</h4>
                            <p style="color: #666; margin: 0 0 10px 0;"><?php echo esc_html($criterion->criteria_description); ?></p>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                                <?php foreach ($levels as $level): ?>
                                    <label style="padding: 10px; border: 2px solid #ddd; border-radius: 4px; cursor: pointer; background: white; transition: all 0.3s;"
                                           onmouseover="this.style.borderColor='<?php echo esc_attr($level->color_code); ?>'; this.style.backgroundColor='<?php echo esc_attr($level->color_code); ?>20';"
                                           onmouseout="this.style.borderColor='#ddd'; this.style.backgroundColor='white';">
                                        <input type="radio"
                                               name="<?php echo esc_attr($field_name); ?>"
                                               value="<?php echo $level->level_id; ?>"
                                               data-points="<?php echo $level->points; ?>"
                                               <?php checked($selected_level, $level->level_id); ?>
                                               onchange="updateTotalPoints();">
                                        <strong><?php echo esc_html($level->performance_level); ?></strong><br>
                                        <?php echo esc_html($level->points); ?> pts
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total Points Display -->
                <div style="background: #e8f5e9; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 2px solid #4caf50;">
                    <h3 style="margin: 0;">Total Points: <span id="total-points" style="color: #4caf50; font-weight: bold;">0</span> / <?php echo $rubric->max_score; ?></h3>
                    <p id="grade-letter" style="margin: 10px 0 0 0; font-size: 18px;"></p>
                </div>

                <!-- Feedback Comments -->
                <div style="margin-bottom: 20px;">
                    <label for="grader_feedback"><strong>Feedback & Comments:</strong></label>
                    <textarea name="grader_feedback" id="grader_feedback" style="width: 100%; height: 150px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
<?php echo isset($_POST['grader_feedback']) ? esc_textarea($_POST['grader_feedback']) : ''; ?></textarea>
                </div>

                <!-- Submit Buttons -->
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="button button-primary button-large">📝 Submit Grade</button>
                    <button type="button" class="button button-secondary button-large" onclick="window.history.back();">← Cancel</button>
                </div>
            </form>
        </div>

        <script>
        function updateTotalPoints() {
            let total = 0;
            const radios = document.querySelectorAll('input[type="radio"]:checked');

            radios.forEach(radio => {
                total += parseFloat(radio.getAttribute('data-points'));
            });

            document.getElementById('total-points').textContent = total.toFixed(0);

            // Calculate grade letter
            const percentage = (total / <?php echo $rubric->max_score; ?>) * 100;
            let grade = 'F';
            if (percentage >= 90) grade = 'A';
            else if (percentage >= 80) grade = 'B';
            else if (percentage >= 70) grade = 'C';
            else if (percentage >= 60) grade = 'D';

            document.getElementById('grade-letter').textContent = 'Grade: ' + grade + ' (' + percentage.toFixed(1) + '%)';
        }

        // Initialize on load
        window.addEventListener('load', updateTotalPoints);
        </script>
        <?php
    }

    /**
     * Process grading submission
     */
    private function process_grading($submission_id, $assignment_id, $rubric_id, $criteria) {
        global $wpdb;

        $total_points = 0;
        $grading_data = array();

        // Process each criterion
        foreach ($criteria as $criterion) {
            $field_name = 'criterion_' . $criterion->criteria_id;
            if (isset($_POST[$field_name])) {
                $level_id = intval($_POST[$field_name]);

                // Get the level details
                $level = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM wpcu_mtti_rubric_levels WHERE level_id = %d",
                    $level_id
                ));

                if ($level) {
                    $total_points += $level->points;

                    // Log this grading action
                    $wpdb->insert('wpcu_mtti_grading_log', array(
                        'submission_id' => $submission_id,
                        'grader_id' => get_current_user_id(),
                        'action_type' => 'rubric_scored',
                        'rubric_criteria_id' => $criterion->criteria_id,
                        'rubric_level_id' => $level_id,
                        'points_awarded' => $level->points,
                        'notes' => sanitize_textarea_field($_POST['grader_feedback']),
                        'is_draft' => false,
                        'is_submitted' => true,
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'user_agent' => $_SERVER['HTTP_USER_AGENT']
                    ));
                }
            }
        }

        // Update submission with total score
        $percentage = ($total_points / 100) * 100;

        // Calculate letter grade
        $grade = 'F';
        if ($percentage >= 90) $grade = 'A';
        elseif ($percentage >= 80) $grade = 'B';
        elseif ($percentage >= 70) $grade = 'C';
        elseif ($percentage >= 60) $grade = 'D';

        $wpdb->update('wpcu_mtti_assignment_submissions',
            array(
                'score' => $total_points,
                'marks_obtained' => $total_points,
                'grade' => $grade,
                'graded_at' => current_time('mysql'),
                'graded_by' => get_current_user_id(),
                'status' => 'Graded'
            ),
            array('submission_id' => $submission_id)
        );

        // Add audit trail entry
        $wpdb->insert('wpcu_mtti_submission_audit', array(
            'submission_id' => $submission_id,
            'grading_completed' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));
    }
}
