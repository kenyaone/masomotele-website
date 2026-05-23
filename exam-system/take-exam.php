<?php
/**
 * MTTI Exam Taker
 * Loads HTML exam file, injects student info + result-saving function
 */

define('EXAM_DIR', __DIR__ . '/exams/');
define('RESULTS_DIR', __DIR__ . '/results/');

$exam_file = isset($_GET['exam']) ? preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['exam']) : '';
$admission = isset($_GET['admission']) ? htmlspecialchars(strip_tags($_GET['admission'])) : '';
$student_name = isset($_GET['student']) ? htmlspecialchars(strip_tags(urldecode($_GET['student']))) : '';

// Validate
if (empty($exam_file) || empty($admission)) {
    header('Location: index.php');
    exit;
}

$exam_path = EXAM_DIR . $exam_file . '.html';
if (!file_exists($exam_path)) {
    die('<!DOCTYPE html><html><body style="font-family:Arial;text-align:center;padding:60px;">
        <h2 style="color:#C62828;">❌ Exam not found</h2>
        <p>File: <code>' . htmlspecialchars($exam_file) . '.html</code></p>
        <a href="index.php" style="color:#1B5E20;">← Back to Exam Portal</a>
    </body></html>');
}

// Ensure results dir exists
if (!is_dir(RESULTS_DIR)) mkdir(RESULTS_DIR, 0755, true);

$exam_html = file_get_contents($exam_path);

// Build the injection script
$save_url = 'save-result.php';

$inject = '<script>
// =============================================
// MTTI EXAM SYSTEM — Auto-injected
// =============================================
window.MTTI_STUDENT = {
    admission: ' . json_encode($admission) . ',
    name: ' . json_encode($student_name) . ',
    exam: ' . json_encode($exam_file) . '
};

/**
 * Call this from your exam HTML to save results:
 * 
 * submitExamToMTTI(examName, score, maxScore, questionResults, durationMinutes);
 * 
 * OR with object:
 * submitExamToMTTI({ exam_name, score, max_score, question_results, duration_minutes });
 */
window.submitExamToMTTI = async function(examNameOrObj, score, maxScore, questionResults, durationMinutes) {
    var s = window.MTTI_STUDENT;
    var payload;

    if (typeof examNameOrObj === "object" && examNameOrObj !== null) {
        payload = {
            admission_number: s.admission,
            student_name: s.name,
            exam_file: s.exam,
            exam_name: examNameOrObj.exam_name || examNameOrObj.examName || s.exam,
            score: examNameOrObj.score || 0,
            max_score: examNameOrObj.max_score || examNameOrObj.maxScore || 100,
            question_results: examNameOrObj.question_results || examNameOrObj.questionResults || null,
            duration_minutes: examNameOrObj.duration_minutes || examNameOrObj.durationMinutes || 0
        };
    } else {
        payload = {
            admission_number: s.admission,
            student_name: s.name,
            exam_file: s.exam,
            exam_name: examNameOrObj || s.exam,
            score: score || 0,
            max_score: maxScore || 100,
            question_results: questionResults || null,
            duration_minutes: durationMinutes || 0
        };
    }

    try {
        var resp = await fetch("' . $save_url . '", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });
        var result = await resp.json();
        console.log("[MTTI] Result saved:", result);
        return result;
    } catch (err) {
        console.error("[MTTI] Save error:", err);
        return { success: false, message: err.message };
    }
};

// Auto-fill student fields if they exist in the HTML
document.addEventListener("DOMContentLoaded", function() {
    var s = window.MTTI_STUDENT;
    var nameFields = document.querySelectorAll("#studentName, #student-name, [name=student_name], [name=studentName]");
    var admFields = document.querySelectorAll("#admissionNumber, #admission-number, #studentAdm, [name=admission_number], [name=admissionNumber]");
    nameFields.forEach(function(el) { el.value = s.name; el.readOnly = true; el.style.backgroundColor = "#e8f5e9"; });
    admFields.forEach(function(el) { el.value = s.admission; el.readOnly = true; el.style.backgroundColor = "#e8f5e9"; });
});
</script>';

// Inject before </head> or </body>
if (stripos($exam_html, '</head>') !== false) {
    $exam_html = str_ireplace('</head>', $inject . "\n</head>", $exam_html);
} elseif (stripos($exam_html, '</body>') !== false) {
    $exam_html = str_ireplace('</body>', $inject . "\n</body>", $exam_html);
} else {
    $exam_html .= $inject;
}

echo $exam_html;
