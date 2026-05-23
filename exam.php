<?php
/**
 * MTTI Exam Launcher — exam.php
 * Place in public_html/ (WordPress root)
 * 
 * URL format: /exam.php?file=EXAM_NAME&student=NAME&admission=ADM&course=COURSE&schedule_id=ID
 * 
 * This file:
 * 1. Validates parameters
 * 2. Loads the HTML exam file from /exams/ folder
 * 3. Injects student info + API submission function
 * 4. Results are stored in MTTI Exam MIS plugin tables (NOT MTTI MIS)
 */

// Get parameters
$exam_file = isset($_GET['file']) ? preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['file']) : '';
$student_name = isset($_GET['student']) ? htmlspecialchars(urldecode($_GET['student'])) : '';
$admission_number = isset($_GET['admission']) ? htmlspecialchars(urldecode($_GET['admission'])) : '';
$course = isset($_GET['course']) ? htmlspecialchars(urldecode($_GET['course'])) : '';
$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;
$token = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '';

// Validate exam file
if (empty($exam_file)) {
    die('<html><body style="font-family:Arial;text-align:center;padding:60px;">
        <h2 style="color:#C62828;">❌ No exam file specified</h2>
        <p>Please access exams through the MTTI Exam MIS system.</p>
        <a href="/" style="color:#1B5E20;">← Return to Home</a>
    </body></html>');
}

// Find exam file — check /exams/ folder first, then root
$exam_path = '';
$possible_paths = [
    __DIR__ . '/exams/' . $exam_file . '.html',
    __DIR__ . '/' . $exam_file . '.html',
];

foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $exam_path = $path;
        break;
    }
}

if (empty($exam_path)) {
    die('<html><body style="font-family:Arial;text-align:center;padding:60px;">
        <h2 style="color:#C62828;">❌ Exam file not found</h2>
        <p>File: <code>' . htmlspecialchars($exam_file) . '.html</code></p>
        <p>Please ensure the exam file exists in the <code>/exams/</code> folder.</p>
        <a href="/" style="color:#1B5E20;">← Return to Home</a>
    </body></html>');
}

// Detect site URL for API endpoint
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'masomoteletraining.co.ke';
$site_url = $protocol . '://' . $host;

// Load the HTML exam file
$exam_html = file_get_contents($exam_path);

// Create the injection script
$inject_script = '
<script>
// =============================================
// MTTI EXAM CONFIG — Injected by exam.php
// Results submit to MTTI Exam MIS plugin
// =============================================
window.MTTI_EXAM_CONFIG = {
    studentName: ' . json_encode($student_name) . ',
    admissionNumber: ' . json_encode($admission_number) . ',
    course: ' . json_encode($course) . ',
    scheduleId: ' . json_encode($schedule_id) . ',
    token: ' . json_encode($token) . ',
    examFile: ' . json_encode($exam_file) . ',
    apiEndpoint: ' . json_encode($site_url . '/wp-json/mtti-exam/v1/submit') . ',
    siteUrl: ' . json_encode($site_url) . '
};

/**
 * Submit exam results to MTTI Exam MIS
 * Call this at the end of your submitExam() or showResults() function:
 *
 * submitExamToMTTI(examName, score, maxScore, questionResults, durationMinutes);
 *
 * Or with an object:
 * submitExamToMTTI({ exam_name, score, max_score, question_results, duration_minutes });
 */
window.submitExamToMTTI = async function(examNameOrObj, score, maxScore, questionResults, durationMinutes) {
    const config = window.MTTI_EXAM_CONFIG;
    
    let payload;
    if (typeof examNameOrObj === "object" && examNameOrObj !== null) {
        // Object format: submitExamToMTTI({ exam_name, score, max_score, ... })
        payload = {
            student_name: config.studentName,
            admission_number: config.admissionNumber,
            course: config.course,
            schedule_id: config.scheduleId,
            exam_name: examNameOrObj.exam_name || examNameOrObj.examName || config.examFile,
            score: examNameOrObj.score || 0,
            max_score: examNameOrObj.max_score || examNameOrObj.maxScore || 100,
            question_results: examNameOrObj.question_results || examNameOrObj.questionResults || [],
            duration_minutes: examNameOrObj.duration_minutes || examNameOrObj.durationMinutes || 0
        };
    } else {
        // Positional format: submitExamToMTTI(name, score, max, results, duration)
        payload = {
            student_name: config.studentName,
            admission_number: config.admissionNumber,
            course: config.course,
            schedule_id: config.scheduleId,
            exam_name: examNameOrObj || config.examFile,
            score: score || 0,
            max_score: maxScore || 100,
            question_results: questionResults || [],
            duration_minutes: durationMinutes || 0
        };
    }

    console.log("[MTTI Exam MIS] Submitting results:", payload);

    try {
        const response = await fetch(config.apiEndpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            console.log("[MTTI Exam MIS] ✅ Result saved:", result);
            return { success: true, data: result };
        } else {
            console.error("[MTTI Exam MIS] ❌ Failed:", result.message);
            return { success: false, message: result.message };
        }
    } catch (error) {
        console.error("[MTTI Exam MIS] ❌ Network error:", error);
        return { success: false, message: error.message };
    }
};

// Auto-fill student name if the exam has input fields for it
document.addEventListener("DOMContentLoaded", function() {
    const config = window.MTTI_EXAM_CONFIG;
    
    // Common field selectors used in MTTI exam HTML files
    const nameFields = document.querySelectorAll("#studentName, #student-name, [name=student_name], [name=studentName]");
    const admFields = document.querySelectorAll("#admissionNumber, #admission-number, #studentAdm, [name=admission_number], [name=admissionNumber]");
    
    nameFields.forEach(function(el) { 
        el.value = config.studentName; 
        el.readOnly = true;
        el.style.backgroundColor = "#f0f8ff";
    });
    admFields.forEach(function(el) { 
        el.value = config.admissionNumber; 
        el.readOnly = true;
        el.style.backgroundColor = "#f0f8ff";
    });
    
    console.log("[MTTI Exam MIS] Student loaded:", config.studentName, "(" + config.admissionNumber + ")");
});
</script>';

// Inject before </head> or before </body>
if (stripos($exam_html, '</head>') !== false) {
    $exam_html = str_ireplace('</head>', $inject_script . "\n</head>", $exam_html);
} elseif (stripos($exam_html, '</body>') !== false) {
    $exam_html = str_ireplace('</body>', $inject_script . "\n</body>", $exam_html);
} else {
    $exam_html .= $inject_script;
}

// Output
echo $exam_html;
