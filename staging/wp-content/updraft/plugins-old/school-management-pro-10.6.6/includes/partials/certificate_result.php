<?php
// --- Always-available defaults (prevents "Undefined variable" in parent files) ---
$exam_id             = 0;
$exam_title          = '';
$exam_center         = '';
$start_date          = '';
$end_date            = '';
$show_rank           = false;
$show_remark         = false;
$show_eremark        = false;
$psychomotor_enable  = false;
$teacher_signature   = '';
$enable_max_marks    = false;
$enable_obtained     = false;

$psychomotor         = array();
$grade_criteria      = array('enable_overall_grade' => false, 'marks_grades' => array());
$enable_overall_grade = false;
$marks_grades        = array();
$show_marks_grades   = 0;

$exam_papers         = array();
$exam_results        = array();

$student_rank        = '';   // Keep type flexible if your calc returns string/number
$total_maximum_marks = 0.0;
$total_obtained_marks= 0.0;
$total_percentage    = 0.0;
$student_percentage  = '';
$p_scale             = '';
$__cert_error        = '';   // Optional: carry a non-fatal error message

// --- Fetch base records safely ---
$admit_card = WLSM_M_Staff_Examination::fetch_admit_card($school_id ?? 0, $admit_card_id ?? 0);
if ($admit_card && !empty($admit_card->exam_id)) {
    $exam_id = (int) $admit_card->exam_id;
    $exam    = WLSM_M_Staff_Examination::fetch_exam($school_id ?? 0, $exam_id);

    if ($exam) {
        // Safe reads from $exam
        $exam_id            = (int) ($exam->ID ?? 0);
        $exam_title         = (string) ($exam->exam_title ?? '');
        $exam_center        = (string) ($exam->exam_center ?? '');
        $start_date         = (string) ($exam->start_date ?? '');
        $end_date           = (string) ($exam->end_date ?? '');
        $show_rank          = !empty($exam->show_rank);
        $show_remark        = !empty($exam->show_remark);
        $show_eremark       = !empty($exam->show_eremark);
        $psychomotor_enable = !empty($exam->psychomotor_analysis);
        $teacher_signature  = (string) ($exam->teacher_signature ?? '');

        $enable_max_marks   = !empty($exam->enable_total_marks);
        $enable_obtained    = !empty($exam->results_obtained_marks);

        // Psychomotor & grade criteria
        $psychomotor = WLSM_Config::sanitize_psychomotor($exam->psychomotor ?? array());

        $grade_criteria = WLSM_Config::sanitize_grade_criteria($exam->grade_criteria ?? array());
        $enable_overall_grade = !empty($grade_criteria['enable_overall_grade']);
        $marks_grades         = !empty($grade_criteria['marks_grades']) && is_array($grade_criteria['marks_grades'])
                                ? $grade_criteria['marks_grades'] : array();
        $show_marks_grades    = count($marks_grades);

        // Papers & results
        $exam_papers  = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card($school_id ?? 0, $admit_card_id ?? 0);
        $exam_papers  = is_array($exam_papers) ? $exam_papers : array();

        $exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card($school_id ?? 0, $admit_card_id ?? 0);
        $exam_results = is_array($exam_results) ? $exam_results : array();

        // Rank (guard)
        $student_rank = WLSM_M_Staff_Examination::calculate_exam_ranks($school_id ?? 0, $exam_id, array(), $admit_card_id ?? 0);

        // Totals
        foreach ($exam_papers as $exam_paper) {
            $paper_id  = isset($exam_paper->ID) ? $exam_paper->ID : 0;
            $max_marks = isset($exam_paper->maximum_marks) ? (float) $exam_paper->maximum_marks : 0.0;

            $exam_result = ($paper_id && isset($exam_results[$paper_id])) ? $exam_results[$paper_id] : null;
            $obtained    = $exam_result->obtained_marks ?? '';

            // Optional fields (kept local to loop; use outside only if you store them)
            // $teacher_remark = $exam_result->teacher_remark ?? '';
            // $school_remark  = $exam_result->school_remark  ?? '';

            if (isset($exam_result->scale)) {
                $p_scale = $exam_result->scale; // last seen
            }

            $total_maximum_marks  += $max_marks;
            $total_obtained_marks += WLSM_Config::sanitize_marks($obtained);

            // If needed per-paper:
            // $percentage = WLSM_Config::sanitize_percentage($max_marks, WLSM_Config::sanitize_marks($obtained));
        }

        // Overall percentage/text
        $total_percentage   = WLSM_Config::sanitize_percentage($total_maximum_marks, $total_obtained_marks);
        $student_percentage = WLSM_Config::get_percentage_text($total_maximum_marks, $total_obtained_marks);

        // Scale may be serialized
        if (is_string($p_scale) && preg_match('/^(a|O|s|i|d|b):\d+:/', $p_scale)) {
            $tmp = @unserialize($p_scale);
            if ($tmp !== false || $p_scale === 'b:0;') {
                $p_scale = $tmp;
            }
        }
    } else {
        $__cert_error = 'Exam not found or inaccessible.';
    }
} else {
    $__cert_error = 'Admit card or exam not found.';
}

// Note: $__cert_error is set but not echoed; the including template can decide how to display it.
