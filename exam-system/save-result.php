<?php
/**
 * MTTI Exam — Save Result
 * Receives JSON POST from exam HTML, saves to /results/ as JSON file
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

define('RESULTS_DIR', __DIR__ . '/results/');
if (!is_dir(RESULTS_DIR)) mkdir(RESULTS_DIR, 0755, true);

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Required fields
$admission = trim($body['admission_number'] ?? '');
$exam_name = trim($body['exam_name'] ?? $body['exam_file'] ?? '');
$score = floatval($body['score'] ?? 0);
$max_score = floatval($body['max_score'] ?? 100);

if (empty($admission) || empty($exam_name)) {
    echo json_encode(['success' => false, 'message' => 'Missing admission_number or exam_name']);
    exit;
}

// Calculate grade
$percentage = $max_score > 0 ? round(($score / $max_score) * 100, 1) : 0;
if ($percentage >= 80) $grade = 'A';
elseif ($percentage >= 70) $grade = 'B';
elseif ($percentage >= 60) $grade = 'C';
elseif ($percentage >= 50) $grade = 'D';
else $grade = 'F';

// Build result record
$result = [
    'id' => uniqid('res_'),
    'admission_number' => $admission,
    'student_name' => trim($body['student_name'] ?? ''),
    'exam_file' => trim($body['exam_file'] ?? ''),
    'exam_name' => $exam_name,
    'score' => $score,
    'max_score' => $max_score,
    'percentage' => $percentage,
    'grade' => $grade,
    'passed' => $percentage >= 50,
    'duration_minutes' => intval($body['duration_minutes'] ?? 0),
    'question_results' => $body['question_results'] ?? null,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
    'submitted_at' => date('Y-m-d H:i:s'),
    'timestamp' => time(),
];

// Save to individual file: results/ADM_EXAM_TIMESTAMP.json
$safe_adm = preg_replace('/[^a-zA-Z0-9]/', '-', $admission);
$safe_exam = preg_replace('/[^a-zA-Z0-9]/', '-', $body['exam_file'] ?? $exam_name);
$filename = $safe_adm . '_' . $safe_exam . '_' . time() . '.json';

$saved = file_put_contents(RESULTS_DIR . $filename, json_encode($result, JSON_PRETTY_PRINT));

// Also append to master log
$log_file = RESULTS_DIR . 'all-results.json';
$all = [];
if (file_exists($log_file)) {
    $all = json_decode(file_get_contents($log_file), true) ?: [];
}
$all[] = $result;
file_put_contents($log_file, json_encode($all, JSON_PRETTY_PRINT));

if ($saved) {
    echo json_encode([
        'success' => true,
        'message' => 'Result saved',
        'id' => $result['id'],
        'percentage' => $percentage,
        'grade' => $grade,
        'passed' => $percentage >= 50,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to write file']);
}
