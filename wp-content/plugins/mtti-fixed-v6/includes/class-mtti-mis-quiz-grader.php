<?php
/**
 * MTTI MIS Quiz Auto-Grader
 *
 * Handles auto-grading of quizzes with fuzzy matching for fill-in-the-blank
 * Supports: Multiple Choice, True/False, Fill-in-the-Blank
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Quiz_Grader {

    /**
     * Grade a quiz submission
     */
    public static function grade_quiz_submission($quiz_id, $student_id, $answers) {
        global $wpdb;

        // Get quiz questions
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_quiz_questions WHERE quiz_id = %d ORDER BY question_id",
            $quiz_id
        ));

        $total_score = 0;
        $total_questions = count($questions);
        $results = array();

        foreach ($questions as $question) {
            $student_answer = isset($answers[$question->question_id]) ? $answers[$question->question_id] : '';
            $correct_answer = $question->correct_answer;
            $is_correct = false;

            // Grade based on question type
            switch ($question->question_type) {
                case 'mcq':
                    // Multiple choice: exact match
                    $is_correct = ($student_answer === $correct_answer);
                    break;

                case 'true_false':
                    // True/False: exact match (true/false string)
                    $is_correct = (strtolower($student_answer) === strtolower($correct_answer));
                    break;

                case 'fill_blank':
                    // Fill-in-the-blank: FUZZY matching
                    $is_correct = self::check_fill_blank_answer($student_answer, $correct_answer);
                    break;

                default:
                    $is_correct = ($student_answer === $correct_answer);
            }

            if ($is_correct) {
                $total_score++;
            }

            $results[$question->question_id] = array(
                'question' => $question->question_text,
                'student_answer' => $student_answer,
                'correct_answer' => $correct_answer,
                'is_correct' => $is_correct,
                'question_type' => $question->question_type,
                'explanation' => $question->explanation
            );
        }

        // Calculate percentage
        $percentage = ($total_questions > 0) ? ($total_score / $total_questions) * 100 : 0;

        // Determine pass/fail
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_quizzes WHERE quiz_id = %d",
            $quiz_id
        ));
        $pass_mark = $quiz ? $quiz->pass_mark : 70;
        $passed = ($percentage >= $pass_mark);

        return array(
            'score' => $total_score,
            'total' => $total_questions,
            'percentage' => round($percentage, 2),
            'passed' => $passed,
            'pass_mark' => $pass_mark,
            'results' => $results
        );
    }

    /**
     * Check fill-in-the-blank answer with fuzzy matching
     *
     * Handles variations like:
     * - "reach is unique people, impressions is total views"
     * - "Reach = unique users, Impressions = total views"
     * - "reach = unique, impressions = all"
     * - etc.
     */
    private static function check_fill_blank_answer($student_answer, $correct_answer) {
        // Normalize both answers
        $student = self::normalize_answer($student_answer);
        $correct = self::normalize_answer($correct_answer);

        // Exact match after normalization
        if ($student === $correct) {
            return true;
        }

        // Check for key concept match
        // Expected: reach = unique people/users, impressions = total views/impressions
        $student_keywords = self::extract_keywords($student);
        $correct_keywords = self::extract_keywords($correct);

        // Must have reach/unique concept
        if (in_array('reach', $student_keywords) && in_array('unique', $student_keywords)) {
            // Must have impressions/total concept
            if ((in_array('impressions', $student_keywords) || in_array('impression', $student_keywords)) &&
                (in_array('total', $student_keywords) || in_array('all', $student_keywords))) {
                return true;
            }
        }

        // Levenshtein distance for small typos (max 3 character differences)
        if (strlen($correct) > 0) {
            $distance = levenshtein($student, $correct);
            $threshold = max(3, strlen($correct) * 0.2); // 20% tolerance

            if ($distance <= $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize answer text for comparison
     */
    private static function normalize_answer($text) {
        // Convert to lowercase
        $text = strtolower(trim($text));

        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove punctuation but keep structure
        $text = preg_replace('/[.,!?;:\'"()-]/', '', $text);

        // Remove common filler words
        $fillers = array('is', 'the', 'a', 'an', 'and', 'or', 'vs', 'versus', 'vs.');
        foreach ($fillers as $filler) {
            $text = preg_replace('/\b' . $filler . '\b/', '', $text);
        }

        // Clean up spaces again
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Extract key concepts from answer
     */
    private static function extract_keywords($text) {
        // Important keywords for this question
        $keywords = array(
            'reach', 'unique', 'people', 'users', 'person', 'user',
            'impressions', 'impression', 'total', 'views', 'view', 'all'
        );

        $found = array();
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $found[] = $keyword;
            }
        }

        return $found;
    }

    /**
     * Save quiz attempt to database
     */
    public static function save_quiz_attempt($quiz_id, $student_id, $grading_result) {
        global $wpdb;

        $wpdb->insert('wpcu_mtti_quiz_attempts', array(
            'quiz_id' => $quiz_id,
            'student_id' => $student_id,
            'score' => $grading_result['score'],
            'total' => $grading_result['total'],
            'percent' => $grading_result['percentage'],
            'passed' => $grading_result['passed'] ? 1 : 0,
            'attempt_number' => self::get_attempt_number($quiz_id, $student_id),
            'attempted_at' => current_time('mysql'),
            'answers' => json_encode($grading_result['results'])
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get attempt number for this student on this quiz
     */
    private static function get_attempt_number($quiz_id, $student_id) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM wpcu_mtti_quiz_attempts WHERE quiz_id = %d AND student_id = %d",
            $quiz_id, $student_id
        ));

        return ($count + 1);
    }

    /**
     * Can student retake this quiz?
     */
    public static function can_retake($quiz_id, $student_id) {
        global $wpdb;

        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_quizzes WHERE quiz_id = %d",
            $quiz_id
        ));

        if (!$quiz || !$quiz->max_attempts) {
            return true; // No limit
        }

        $attempt_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM wpcu_mtti_quiz_attempts WHERE quiz_id = %d AND student_id = %d",
            $quiz_id, $student_id
        ));

        return ($attempt_count < $quiz->max_attempts);
    }

    /**
     * Get best attempt score
     */
    public static function get_best_score($quiz_id, $student_id) {
        global $wpdb;

        $best = $wpdb->get_row($wpdb->prepare(
            "SELECT MAX(percent) as best_score FROM wpcu_mtti_quiz_attempts
             WHERE quiz_id = %d AND student_id = %d",
            $quiz_id, $student_id
        ));

        return $best ? $best->best_score : 0;
    }
}
