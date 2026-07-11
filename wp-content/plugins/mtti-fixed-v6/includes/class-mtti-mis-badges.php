<?php
/**
 * MTTI MIS Badge System
 *
 * Gamification with automatic badge awards
 * Quick Learner, Quiz Master, Perfect Score, etc.
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Badges {

    const BADGES = array(
        'quick_learner' => array(
            'name' => 'Quick Learner',
            'emoji' => '⚡',
            'description' => 'Complete course in less than 1 week',
            'requirement' => 'time_based'
        ),
        'quiz_master' => array(
            'name' => 'Quiz Master',
            'emoji' => '🧠',
            'description' => 'Score 90%+ on all quizzes',
            'requirement' => 'score_based'
        ),
        'perfect_score' => array(
            'name' => 'Perfect Score',
            'emoji' => '💯',
            'description' => 'Get 100% on any quiz',
            'requirement' => 'perfect'
        ),
        'consistent' => array(
            'name' => 'Consistent Performer',
            'emoji' => '📈',
            'description' => 'Score 80%+ on all attempts',
            'requirement' => 'consistency'
        ),
        'persistent' => array(
            'name' => 'Persistent',
            'emoji' => '🎯',
            'description' => 'Retake a quiz 3 times and pass',
            'requirement' => 'persistence'
        ),
        'certificate_earned' => array(
            'name' => 'Certificate Earned',
            'emoji' => '🏆',
            'description' => 'Achieve 70%+ and earn certificate',
            'requirement' => 'completion'
        ),
        'early_bird' => array(
            'name' => 'Early Bird',
            'emoji' => '🌅',
            'description' => 'Pass first quiz within 24 hours of enrollment',
            'requirement' => 'early_completion'
        ),
        'comeback_king' => array(
            'name' => 'Comeback King',
            'emoji' => '🔥',
            'description' => 'Fail a quiz then score 90%+ on retake',
            'requirement' => 'comeback'
        )
    );

    /**
     * Check and award badges after quiz attempt
     */
    public static function check_badges_after_quiz($quiz_id, $student_id, $course_id) {
        global $wpdb;

        // Get quiz info
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_quizzes WHERE quiz_id = %d",
            $quiz_id
        ));

        // Get this attempt
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_quiz_attempts WHERE quiz_id = %d AND student_id = %d ORDER BY attempt_id DESC LIMIT 1",
            $quiz_id, $student_id
        ));

        if (!$attempt) {
            return;
        }

        $score = floatval($attempt->percent);

        // Check Perfect Score (100%)
        if ($score === 100.0) {
            self::award_badge('perfect_score', $student_id, $course_id);
        }

        // Check all quiz attempts for this student
        $all_attempts = $wpdb->get_results($wpdb->prepare(
            "SELECT percent FROM wpcu_mtti_quiz_attempts WHERE student_id = %d AND quiz_id IN (SELECT quiz_id FROM wpcu_mtti_quizzes WHERE course_id = %d)",
            $student_id, $course_id
        ));

        if (!empty($all_attempts)) {
            $scores = array_map(function($a) { return floatval($a->percent); }, $all_attempts);

            // Check Quiz Master (all quizzes 90%+)
            if (count($scores) >= 3 && min($scores) >= 90) {
                self::award_badge('quiz_master', $student_id, $course_id);
            }

            // Check Consistent (all 80%+)
            if (count($scores) >= 3 && min($scores) >= 80) {
                self::award_badge('consistent', $student_id, $course_id);
            }

            // Check Persistent (retake 3+ times and pass)
            $attempt_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM wpcu_mtti_quiz_attempts WHERE student_id = %d AND quiz_id = %d",
                $student_id, $quiz_id
            ));

            if ($attempt_count >= 3 && $score >= 70) {
                self::award_badge('persistent', $student_id, $course_id);
            }

            // Check Comeback King (failed then 90%+)
            if ($attempt_count >= 2) {
                $previous = $wpdb->get_row($wpdb->prepare(
                    "SELECT percent FROM wpcu_mtti_quiz_attempts WHERE student_id = %d AND quiz_id = %d ORDER BY attempt_id DESC LIMIT 1 OFFSET 1",
                    $student_id, $quiz_id
                ));

                if ($previous && floatval($previous->percent) < 70 && $score >= 90) {
                    self::award_badge('comeback_king', $student_id, $course_id);
                }
            }
        }

        // Check time-based badges
        self::check_time_badges($student_id, $course_id);

        // Check completion badges
        self::check_completion_badges($student_id, $course_id);
    }

    /**
     * Check time-based badges
     */
    private static function check_time_badges($student_id, $course_id) {
        global $wpdb;

        // Quick Learner (complete within 1 week)
        $started = $wpdb->get_row($wpdb->prepare(
            "SELECT started_at FROM wpcu_mtti_student_progress WHERE student_id = %d AND course_id = %d",
            $student_id, $course_id
        ));

        if ($started) {
            $start_time = strtotime($started->started_at);
            $now = current_time('mysql');
            $now_time = strtotime($now);
            $days_elapsed = ($now_time - $start_time) / (24 * 60 * 60);

            // Check if all quizzes passed
            $all_passed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM wpcu_mtti_quizzes q WHERE q.course_id = %d
                 AND EXISTS (SELECT 1 FROM wpcu_mtti_quiz_attempts a WHERE a.quiz_id = q.quiz_id AND a.student_id = %d AND a.percent >= %f)",
                $course_id, $student_id, 70
            ));

            $total_quizzes = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM wpcu_mtti_quizzes WHERE course_id = %d",
                $course_id
            ));

            if ($all_passed == $total_quizzes && $days_elapsed < 7) {
                self::award_badge('quick_learner', $student_id, $course_id);
            }
        }

        // Early Bird (pass first quiz within 24 hours)
        $first_quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT q.quiz_id, q.created_at FROM wpcu_mtti_quizzes q WHERE q.course_id = %d ORDER BY q.created_at LIMIT 1",
            $course_id
        ));

        if ($first_quiz) {
            $first_pass = $wpdb->get_row($wpdb->prepare(
                "SELECT attempted_at FROM wpcu_mtti_quiz_attempts WHERE quiz_id = %d AND student_id = %d AND percent >= 70 ORDER BY attempted_at LIMIT 1",
                $first_quiz->quiz_id, $student_id
            ));

            if ($first_pass) {
                $pass_time = strtotime($first_pass->attempted_at);
                $enrollment_time = strtotime($started->started_at);
                $hours_elapsed = ($pass_time - $enrollment_time) / (60 * 60);

                if ($hours_elapsed < 24) {
                    self::award_badge('early_bird', $student_id, $course_id);
                }
            }
        }
    }

    /**
     * Check completion badges
     */
    private static function check_completion_badges($student_id, $course_id) {
        global $wpdb;

        // Certificate Earned (70%+)
        $avg_score = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(percent) FROM wpcu_mtti_quiz_attempts a
             JOIN wpcu_mtti_quizzes q ON a.quiz_id = q.quiz_id
             WHERE a.student_id = %d AND q.course_id = %d",
            $student_id, $course_id
        ));

        if ($avg_score >= 70) {
            self::award_badge('certificate_earned', $student_id, $course_id);
        }
    }

    /**
     * Award badge to student
     */
    private static function award_badge($badge_key, $student_id, $course_id) {
        global $wpdb;

        if (!isset(self::BADGES[$badge_key])) {
            return false;
        }

        $badge = self::BADGES[$badge_key];

        // Check if already awarded
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_student_badges WHERE student_id = %d AND course_id = %d AND badge_type = %s",
            $student_id, $course_id, $badge_key
        ));

        if ($existing) {
            return false; // Already awarded
        }

        // Award badge
        $wpdb->insert('wpcu_mtti_student_badges', array(
            'student_id' => $student_id,
            'course_id' => $course_id,
            'badge_type' => $badge_key,
            'badge_name' => $badge['name'],
            'badge_description' => $badge['description'],
            'icon_emoji' => $badge['emoji'],
            'earned_at' => current_time('mysql')
        ));

        return true;
    }

    /**
     * Get all available badges
     */
    public static function get_all_badges() {
        $badges = array();
        foreach (self::BADGES as $key => $badge) {
            $badges[] = array_merge(array('key' => $key), $badge);
        }
        return $badges;
    }

    /**
     * Get badges for student
     */
    public static function get_student_badges($student_id, $course_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_student_badges WHERE student_id = %d AND course_id = %d ORDER BY earned_at DESC",
            $student_id, $course_id
        ));
    }

    /**
     * Get badges earned in course (for leaderboard)
     */
    public static function get_course_badges($course_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_student_badges WHERE course_id = %d ORDER BY earned_at DESC",
            $course_id
        ));
    }
}
