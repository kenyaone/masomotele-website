<?php
/**
 * MTTI MIS Student Dashboard
 *
 * Coursera-style self-service learning portal
 * Shows progress, quizzes, certificates, badges
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Student_Dashboard {

    /**
     * Display student dashboard for a course
     */
    public static function display_dashboard($course_id) {
        $student_id = get_current_user_id();

        if (!$student_id) {
            return '<p style="color: red;">Please log in to view your dashboard.</p>';
        }

        global $wpdb;

        // Get course info
        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_course WHERE course_id = %d",
            $course_id
        ));

        if (!$course) {
            return '<p style="color: red;">Course not found.</p>';
        }

        // Get student progress
        $progress = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_student_progress WHERE student_id = %d AND course_id = %d",
            $student_id, $course_id
        ));

        // Get completion data
        $completion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_course_completion WHERE student_id = %d AND course_id = %d",
            $student_id, $course_id
        ));

        // Get quizzes
        $quizzes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_quizzes WHERE course_id = %d ORDER BY quiz_id",
            $course_id
        ));

        // Get student's best scores
        $quiz_scores = array();
        foreach ($quizzes as $quiz) {
            $best = $wpdb->get_row($wpdb->prepare(
                "SELECT MAX(percent) as best_score, COUNT(*) as attempts FROM wpcu_mtti_quiz_attempts
                 WHERE quiz_id = %d AND student_id = %d",
                $quiz->quiz_id, $student_id
            ));
            $quiz_scores[$quiz->quiz_id] = array(
                'score' => $best ? floatval($best->best_score) : null,
                'attempts' => $best ? intval($best->attempts) : 0
            );
        }

        // Get certificates
        $certificates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_certificates WHERE student_id = %d AND course_id = %d ORDER BY issued_at DESC",
            $student_id, $course_id
        ));

        // Get badges
        $badges = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_student_badges WHERE student_id = %d AND course_id = %d ORDER BY earned_at DESC",
            $student_id, $course_id
        ));

        ob_start();
        ?>
        <div style="max-width: 1200px; margin: 0 auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">

            <!-- Header -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; border-radius: 12px; margin-bottom: 30px;">
                <h1 style="margin: 0 0 10px 0; font-size: 32px;">📚 <?php echo esc_html($course->title); ?></h1>
                <p style="margin: 0; opacity: 0.9;">Self-paced learning course with auto-graded quizzes</p>
            </div>

            <!-- Progress Overview -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">

                <!-- Overall Progress -->
                <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Overall Progress</h3>
                    <div style="font-size: 36px; font-weight: bold; color: #667eea; margin-bottom: 10px;">
                        <?php echo ($progress ? intval($progress->progress_percentage) : 0); ?>%
                    </div>
                    <div style="background: #f0f0f0; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; width: <?php echo ($progress ? intval($progress->progress_percentage) : 0); ?>%; transition: width 0.3s ease;"></div>
                    </div>
                </div>

                <!-- Current Grade -->
                <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Current Grade</h3>
                    <div style="font-size: 36px; font-weight: bold; color: <?php echo self::get_color_for_score($completion ? floatval($completion->final_score) : 0); ?>; margin-bottom: 10px;">
                        <?php echo ($completion && $completion->final_score !== null ? round($completion->final_score, 1) : '--'); ?>%
                    </div>
                    <?php if ($completion && $completion->passed): ?>
                        <span style="color: #27ae60; font-weight: bold;">✅ Passed</span>
                    <?php elseif ($completion): ?>
                        <span style="color: #e74c3c; font-weight: bold;">⏳ In Progress</span>
                    <?php else: ?>
                        <span style="color: #95a5a6; font-weight: bold;">Not started</span>
                    <?php endif; ?>
                </div>

                <!-- Status -->
                <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Certificate Status</h3>
                    <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">
                        <?php if (!empty($certificates)): ?>
                            ✅ Earned
                        <?php elseif ($completion && $completion->final_score >= 70): ?>
                            ✅ Ready!
                        <?php else: ?>
                            ⏳ <?php echo (70 - ($completion ? $completion->final_score : 0)); ?>% to go
                        <?php endif; ?>
                    </div>
                    <span style="color: #7f8c8d; font-size: 12px;">70%+ required to pass</span>
                </div>

            </div>

            <!-- Quizzes Section -->
            <div style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 30px;">
                <h2 style="margin: 0 0 20px 0; color: #333;">📝 Quizzes</h2>

                <div style="display: grid; gap: 15px;">
                    <?php
                    $index = 1;
                    foreach ($quizzes as $quiz):
                        $score = isset($quiz_scores[$quiz->quiz_id]) ? $quiz_scores[$quiz->quiz_id]['score'] : null;
                        $attempts = isset($quiz_scores[$quiz->quiz_id]) ? $quiz_scores[$quiz->quiz_id]['attempts'] : 0;
                        $passed = ($score !== null && $score >= $quiz->pass_mark);
                        $max_attempts = $quiz->max_attempts ?: 3;
                        $can_retake = ($attempts < $max_attempts);
                    ?>
                        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid <?php echo $passed ? '#27ae60' : ($score !== null ? '#f39c12' : '#95a5a6'); ?>; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="margin: 0 0 8px 0; font-size: 16px;"><?php echo esc_html($quiz->title); ?></h3>
                                <p style="margin: 0; color: #7f8c8d; font-size: 14px;">
                                    <?php if ($score !== null): ?>
                                        Your Score: <strong><?php echo round($score, 1); ?>%</strong> (<?php echo $attempts; ?> attempt<?php echo $attempts != 1 ? 's' : ''; ?>)
                                        <?php if ($passed): ?>
                                            ✅ Passed
                                        <?php else: ?>
                                            ❌ Below passing (<?php echo $quiz->pass_mark; ?>%)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Not attempted yet
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <a href="<?php echo esc_url(add_query_arg('quiz_id', $quiz->quiz_id, get_permalink())); ?>" style="padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">
                                    <?php echo ($score === null) ? 'Start Quiz' : ($can_retake ? 'Retake Quiz' : 'View Results'); ?>
                                </a>
                                <?php if ($can_retake && $score !== null): ?>
                                    <p style="margin: 8px 0 0 0; font-size: 12px; color: #7f8c8d;"><?php echo ($max_attempts - $attempts); ?> attempt<?php echo (($max_attempts - $attempts) != 1) ? 's' : ''; ?> left</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php $index++; endforeach; ?>
                </div>
            </div>

            <!-- Certificates Section -->
            <?php if (!empty($certificates)): ?>
            <div style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 30px; border-top: 4px solid #f39c12;">
                <h2 style="margin: 0 0 20px 0; color: #333;">🏆 Certificates</h2>

                <div style="display: grid; gap: 15px;">
                    <?php foreach ($certificates as $cert): ?>
                        <div style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 20px; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <h3 style="margin: 0 0 8px 0; font-size: 18px;">✅ Certificate Earned!</h3>
                                    <p style="margin: 5px 0; font-size: 14px;">
                                        <strong>Certificate #:</strong> <?php echo esc_html($cert->certificate_number); ?>
                                    </p>
                                    <p style="margin: 5px 0; font-size: 14px;">
                                        <strong>Final Score:</strong> <?php echo esc_html($cert->final_score); ?>% (Grade <?php echo esc_html($cert->grade); ?>)
                                    </p>
                                    <p style="margin: 5px 0; font-size: 14px;">
                                        <strong>Issued:</strong> <?php echo esc_html(date('F d, Y', strtotime($cert->issued_at))); ?>
                                    </p>
                                </div>
                                <div>
                                    <a href="<?php echo esc_url(add_query_arg('verify_cert', $cert->verification_code, home_url('/verify-certificate/'))); ?>" style="padding: 10px 20px; background: white; color: #e67e22; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold; margin-bottom: 10px; display: block; text-align: center;">
                                        📄 View Certificate
                                    </a>
                                    <a href="<?php echo esc_url(add_query_arg('verify_cert', $cert->verification_code, home_url('/verify-certificate/'))); ?>" style="padding: 10px 20px; background: rgba(255,255,255,0.3); color: white; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold; display: block; text-align: center; margin-top: 8px;">
                                        ✓ Verify Online
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Badges Section -->
            <?php if (!empty($badges)): ?>
            <div style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 30px; border-top: 4px solid #9b59b6;">
                <h2 style="margin: 0 0 20px 0; color: #333;">🎖️ Achievements</h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                    <?php foreach ($badges as $badge): ?>
                        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; border: 2px solid #9b59b6;">
                            <div style="font-size: 48px; margin-bottom: 10px;"><?php echo esc_html($badge->icon_emoji); ?></div>
                            <h4 style="margin: 0 0 8px 0; color: #333;"><?php echo esc_html($badge->badge_name); ?></h4>
                            <p style="margin: 0; color: #7f8c8d; font-size: 12px;">Earned <?php echo esc_html(date('M d', strtotime($badge->earned_at))); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Help Section -->
            <div style="background: #ecf0f1; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db;">
                <h3 style="margin: 0 0 10px 0; color: #333;">ℹ️ How This Works</h3>
                <ul style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                    <li>Complete all lessons and quizzes at your own pace</li>
                    <li>You can retake each quiz up to 3 times — your best score counts</li>
                    <li>Your final grade is the average of all quiz scores</li>
                    <li>Achieve 70%+ to pass and earn your certificate</li>
                    <li>Share your certificate online using the verification code</li>
                    <li>Earn badges by hitting milestones (perfect score, quick learner, etc.)</li>
                </ul>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get color for score
     */
    private static function get_color_for_score($score) {
        if ($score >= 90) return '#27ae60'; // Green
        if ($score >= 80) return '#3498db'; // Blue
        if ($score >= 70) return '#f39c12'; // Orange
        if ($score >= 60) return '#e67e22'; // Dark orange
        return '#e74c3c'; // Red
    }

    /**
     * Update student progress
     */
    public static function update_progress($course_id, $student_id) {
        global $wpdb;

        $quizzes = $wpdb->get_results($wpdb->prepare(
            "SELECT quiz_id FROM wpcu_mtti_quizzes WHERE course_id = %d",
            $course_id
        ));

        $quizzes_completed = 0;
        $quizzes_passed = 0;
        $total_score = 0;

        foreach ($quizzes as $quiz) {
            $attempt = $wpdb->get_row($wpdb->prepare(
                "SELECT COUNT(*) as count, MAX(percent) as best FROM wpcu_mtti_quiz_attempts
                 WHERE quiz_id = %d AND student_id = %d",
                $quiz->quiz_id, $student_id
            ));

            if ($attempt->count > 0) {
                $quizzes_completed++;
                $total_score += floatval($attempt->best);

                if ($attempt->best >= 70) {
                    $quizzes_passed++;
                }
            }
        }

        $average_score = ($quizzes_completed > 0) ? ($total_score / $quizzes_completed) : 0;
        $progress_percentage = ($quizzes_completed / count($quizzes)) * 100;

        // Update or insert progress
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_student_progress WHERE student_id = %d AND course_id = %d",
            $student_id, $course_id
        ));

        if ($existing) {
            $wpdb->update('wpcu_mtti_student_progress',
                array(
                    'quizzes_completed' => $quizzes_completed,
                    'quizzes_passed' => $quizzes_passed,
                    'average_score' => round($average_score, 2),
                    'progress_percentage' => round($progress_percentage),
                    'last_activity' => current_time('mysql')
                ),
                array('student_id' => $student_id, 'course_id' => $course_id)
            );
        } else {
            $wpdb->insert('wpcu_mtti_student_progress',
                array(
                    'course_id' => $course_id,
                    'student_id' => $student_id,
                    'total_quizzes' => count($quizzes),
                    'quizzes_completed' => $quizzes_completed,
                    'quizzes_passed' => $quizzes_passed,
                    'average_score' => round($average_score, 2),
                    'progress_percentage' => round($progress_percentage),
                    'started_at' => current_time('mysql'),
                    'last_activity' => current_time('mysql')
                )
            );
        }

        return array(
            'average_score' => round($average_score, 2),
            'passed' => ($average_score >= 70)
        );
    }
}
