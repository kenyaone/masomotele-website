<?php
/**
 * MTTI MIS Leaderboard
 *
 * Real-time student rankings
 * Anonymous display with score-based ranking
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Leaderboard {

    /**
     * Display leaderboard for course
     */
    public static function display_leaderboard($course_id, $limit = 20) {
        global $wpdb;

        $student_id = get_current_user_id();

        // Get top students by score
        $leaderboard = $wpdb->get_results($wpdb->prepare(
            "SELECT
                cc.student_id,
                cc.final_score,
                cc.grade,
                cc.completed_at,
                COUNT(DISTINCT sb.badge_id) as badge_count,
                COUNT(DISTINCT cert.certificate_id) as certificate_count
            FROM wpcu_mtti_course_completion cc
            LEFT JOIN wpcu_mtti_student_badges sb ON cc.student_id = sb.student_id AND sb.course_id = %d
            LEFT JOIN wpcu_mtti_certificates cert ON cc.student_id = cert.student_id AND cert.course_id = %d
            WHERE cc.course_id = %d AND cc.passed = 1
            GROUP BY cc.student_id
            ORDER BY cc.final_score DESC, cc.completed_at ASC
            LIMIT %d",
            $course_id, $course_id, $course_id, $limit
        ));

        // Get current student's rank
        $current_rank = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM wpcu_mtti_course_completion
             WHERE course_id = %d AND student_id != %d AND passed = 1
             AND final_score > (SELECT final_score FROM wpcu_mtti_course_completion WHERE student_id = %d AND course_id = %d)",
            $course_id, $student_id, $student_id, $course_id
        ));

        // Get current student's score
        $current_score = $wpdb->get_row($wpdb->prepare(
            "SELECT final_score, grade FROM wpcu_mtti_course_completion WHERE student_id = %d AND course_id = %d",
            $student_id, $course_id
        ));

        ob_start();
        ?>
        <div style="max-width: 900px; margin: 0 auto; padding: 20px;">

            <!-- Leaderboard Header -->
            <div style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                <h1 style="margin: 0 0 10px 0; font-size: 32px;">🏆 Top Performers</h1>
                <p style="margin: 0; opacity: 0.9;">See how you rank in <?php echo esc_html(get_the_title($course_id)); ?></p>
            </div>

            <!-- Your Rank Card -->
            <?php if ($current_score): ?>
            <div style="background: white; padding: 20px; border-radius: 8px; border: 2px solid #3498db; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="margin: 0; color: #3498db; font-size: 24px;">Your Rank: #<?php echo $current_rank ?: '?'; ?></h2>
                        <p style="margin: 8px 0 0 0; color: #7f8c8d;">
                            Your Score: <strong><?php echo esc_html($current_score->final_score); ?>%</strong> (Grade <?php echo esc_html($current_score->grade); ?>)
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 48px;">📊</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Leaderboard Table -->
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 30px;">

                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #e0e0e0;">
                            <th style="padding: 15px; text-align: left; font-weight: bold; color: #333; width: 50px;">Rank</th>
                            <th style="padding: 15px; text-align: left; font-weight: bold; color: #333;">Student</th>
                            <th style="padding: 15px; text-align: center; font-weight: bold; color: #333; width: 100px;">Score</th>
                            <th style="padding: 15px; text-align: center; font-weight: bold; color: #333; width: 80px;">Grade</th>
                            <th style="padding: 15px; text-align: center; font-weight: bold; color: #333; width: 80px;">Badges</th>
                            <th style="padding: 15px; text-align: center; font-weight: bold; color: #333; width: 100px;">Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        foreach ($leaderboard as $entry):
                            $is_current = ($entry->student_id == $student_id);
                            $medal = '';
                            if ($rank === 1) $medal = '🥇';
                            elseif ($rank === 2) $medal = '🥈';
                            elseif ($rank === 3) $medal = '🥉';
                        ?>
                            <tr style="border-bottom: 1px solid #e0e0e0; background: <?php echo $is_current ? '#ecf0f1' : 'white'; ?>;">
                                <td style="padding: 15px; font-weight: bold; color: <?php echo $is_current ? '#3498db' : '#333'; ?>;">
                                    <?php echo $medal; ?> #<?php echo $rank; ?>
                                </td>
                                <td style="padding: 15px; color: #333;">
                                    <?php if ($is_current): ?>
                                        <strong>You</strong>
                                    <?php else: ?>
                                        Student <?php echo str_pad($entry->student_id, 4, '0', STR_PAD_LEFT); ?>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: center; color: <?php echo self::get_color_for_score($entry->final_score); ?>; font-weight: bold;">
                                    <?php echo esc_html(round($entry->final_score, 1)); ?>%
                                </td>
                                <td style="padding: 15px; text-align: center; background: <?php echo self::get_grade_background($entry->grade); ?>; color: white; font-weight: bold;">
                                    <?php echo esc_html($entry->grade); ?>
                                </td>
                                <td style="padding: 15px; text-align: center; color: #f39c12; font-weight: bold;">
                                    <?php if ($entry->badge_count > 0): ?>
                                        🎖️ <?php echo intval($entry->badge_count); ?>
                                    <?php else: ?>
                                        <span style="color: #bdc3c7;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: center; color: #7f8c8d; font-size: 12px;">
                                    <?php echo esc_html(date('M d', strtotime($entry->completed_at))); ?>
                                </td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>

            </div>

            <!-- Insight Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">

                <!-- Highest Score -->
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #27ae60;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Highest Score</h3>
                    <div style="font-size: 28px; font-weight: bold; color: #27ae60;">
                        <?php
                        $highest = $leaderboard[0] ?? null;
                        echo $highest ? round($highest->final_score, 1) . '%' : '--';
                        ?>
                    </div>
                </div>

                <!-- Average Score -->
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Course Average</h3>
                    <div style="font-size: 28px; font-weight: bold; color: #3498db;">
                        <?php
                        $avg = $wpdb->get_var($wpdb->prepare(
                            "SELECT AVG(final_score) FROM wpcu_mtti_course_completion WHERE course_id = %d AND passed = 1",
                            $course_id
                        ));
                        echo $avg ? round($avg, 1) . '%' : '--';
                        ?>
                    </div>
                </div>

                <!-- Total Completions -->
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f39c12;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Students Passed</h3>
                    <div style="font-size: 28px; font-weight: bold; color: #f39c12;">
                        <?php
                        $total = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM wpcu_mtti_course_completion WHERE course_id = %d AND passed = 1",
                            $course_id
                        ));
                        echo intval($total);
                        ?>
                    </div>
                </div>

            </div>

            <!-- Help Text -->
            <div style="background: #ecf0f1; padding: 15px; border-radius: 8px; margin-top: 30px; text-align: center; color: #555; font-size: 13px;">
                <p style="margin: 0;">ℹ️ Leaderboard shows anonymized rankings. Student names are hidden for privacy. Your rank updates automatically after each quiz.</p>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get color for score
     */
    private static function get_color_for_score($score) {
        if ($score >= 95) return '#27ae60'; // Dark green
        if ($score >= 90) return '#2ecc71'; // Light green
        if ($score >= 80) return '#3498db'; // Blue
        if ($score >= 70) return '#f39c12'; // Orange
        return '#e74c3c'; // Red
    }

    /**
     * Get background color for grade
     */
    private static function get_grade_background($grade) {
        switch ($grade) {
            case 'A': return '#27ae60';
            case 'B': return '#3498db';
            case 'C': return '#f39c12';
            case 'D': return '#e67e22';
            default: return '#e74c3c';
        }
    }

    /**
     * Get leaderboard JSON for API
     */
    public static function get_leaderboard_json($course_id, $limit = 50) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                ROW_NUMBER() OVER (ORDER BY cc.final_score DESC) as rank,
                cc.final_score,
                cc.grade,
                COUNT(DISTINCT sb.badge_id) as badge_count
            FROM wpcu_mtti_course_completion cc
            LEFT JOIN wpcu_mtti_student_badges sb ON cc.student_id = sb.student_id AND sb.course_id = %d
            WHERE cc.course_id = %d AND cc.passed = 1
            ORDER BY cc.final_score DESC
            LIMIT %d",
            $course_id, $course_id, $limit
        ));

        return json_encode($results);
    }
}
