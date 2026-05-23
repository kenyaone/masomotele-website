<?php
/**
 * View single exam result detail
 */
session_start();
if (empty($_SESSION['mtti_exam_admin'])) { header('Location: index.php'); exit; }
if (!empty($_SESSION['mtti_must_change'])) { header('Location: index.php'); exit; }

define('RESULTS_DIR', dirname(__DIR__) . '/results/');
$result_id = $_GET['id'] ?? '';

// Find result
$log_file = RESULTS_DIR . 'all-results.json';
$result = null;
if (file_exists($log_file)) {
    $all = json_decode(file_get_contents($log_file), true) ?: [];
    foreach ($all as $r) {
        if ($r['id'] === $result_id) { $result = $r; break; }
    }
}

if (!$result) {
    die('<!DOCTYPE html><html><body style="font-family:Arial;text-align:center;padding:60px;">
        <h2>Result not found</h2><a href="index.php">← Back</a></body></html>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result — <?php echo htmlspecialchars($result['student_name']); ?></title>
    <style>
        body { font-family:'Segoe UI',Arial; background:#f5f5f5; margin:0; }
        .topbar { background:linear-gradient(135deg,#1B5E20,#2E7D32); color:#fff; padding:16px 24px; }
        .topbar h1 { font-size:1.2rem; }
        .wrap { max-width:800px; margin:24px auto; padding:0 20px; }
        .card { background:#fff; border-radius:12px; padding:32px; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:20px; }
        .card h2 { color:#1B5E20; margin-bottom:20px; }
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .info-grid div { padding:8px 0; }
        .info-grid strong { color:#333; }
        .grade-box { text-align:center; padding:24px; background:#E8F5E9; border-radius:12px; margin:20px 0; }
        .grade-box .g { font-size:4rem; font-weight:900; color:#1B5E20; }
        .grade-box .p { font-size:1.5rem; font-weight:bold; color:#333; }
        .grade-box.fail { background:#FFEBEE; }
        .grade-box.fail .g { color:#C62828; }
        pre { background:#f5f5f5; padding:16px; border-radius:8px; overflow-x:auto; font-size:.85rem; max-height:400px; overflow-y:auto; }
        .btn { display:inline-block; padding:10px 20px; background:#1B5E20; color:#fff; text-decoration:none; border-radius:8px; margin-top:12px; }
        .btn:hover { background:#2E7D32; }
        @media print { .topbar,.btn{display:none!important} .wrap{margin:0;max-width:100%} }
    </style>
</head>
<body>
    <div class="topbar"><h1>📝 Result Detail</h1></div>
    <div class="wrap">
        <a href="index.php" class="btn" style="margin-bottom:16px;">← Back to Results</a>

        <div class="card">
            <h2><?php echo htmlspecialchars($result['student_name']); ?></h2>
            <div class="info-grid">
                <div><strong>Admission #:</strong> <?php echo htmlspecialchars($result['admission_number']); ?></div>
                <div><strong>Exam:</strong> <?php echo htmlspecialchars($result['exam_name']); ?></div>
                <div><strong>Score:</strong> <?php echo $result['score'] . ' / ' . $result['max_score']; ?></div>
                <div><strong>Duration:</strong> <?php echo $result['duration_minutes'] ? $result['duration_minutes'] . ' minutes' : '—'; ?></div>
                <div><strong>Date:</strong> <?php echo date('d F Y, H:i:s', strtotime($result['submitted_at'])); ?></div>
                <div><strong>IP:</strong> <?php echo htmlspecialchars($result['ip_address'] ?? '—'); ?></div>
            </div>

            <div class="grade-box <?php echo $result['passed'] ? '' : 'fail'; ?>">
                <div class="g"><?php echo $result['grade']; ?></div>
                <div class="p"><?php echo $result['percentage']; ?>%</div>
                <div style="margin-top:8px;font-size:1.1rem;"><?php echo $result['passed'] ? '✅ PASSED' : '❌ FAILED'; ?></div>
            </div>

            <?php if (!empty($result['question_results'])): ?>
                <h3 style="margin-top:24px;">Individual Answers</h3>
                <pre><?php 
                    $qr = $result['question_results'];
                    if (is_string($qr)) {
                        $decoded = json_decode($qr, true);
                        echo htmlspecialchars($decoded ? json_encode($decoded, JSON_PRETTY_PRINT) : $qr);
                    } else {
                        echo htmlspecialchars(json_encode($qr, JSON_PRETTY_PRINT));
                    }
                ?></pre>
            <?php endif; ?>
        </div>

        <button onclick="window.print();" class="btn">🖨️ Print</button>
    </div>
</body>
</html>
