<?php
/**
 * MTTI Exam System — Standalone
 * Place this folder anywhere: public_html/exam-system/
 * 
 * Students: Enter admission number → Select exam → Take exam → Results saved as JSON
 * Teachers: View /admin/ to see all results, export, search
 */

// Config
define('EXAM_DIR', __DIR__ . '/exams/');
define('RESULTS_DIR', __DIR__ . '/results/');
define('SITE_NAME', 'Masomotele Technical Training Institute');
define('SITE_MOTTO', 'Start Learning, Start Earning');

// Ensure results directory exists and is protected
if (!is_dir(RESULTS_DIR)) mkdir(RESULTS_DIR, 0755, true);
if (!file_exists(RESULTS_DIR . '.htaccess')) {
    file_put_contents(RESULTS_DIR . '.htaccess', "Deny from all\n");
}

// Get available exams
$exams = [];
if (is_dir(EXAM_DIR)) {
    foreach (glob(EXAM_DIR . '*.html') as $file) {
        $name = basename($file, '.html');
        $exams[$name] = ucwords(str_replace(['-', '_'], ' ', $name));
    }
}
ksort($exams);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTTI Exam System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; min-height: 100vh; }
        
        .header {
            background: linear-gradient(135deg, #1B5E20, #2E7D32);
            color: white; padding: 20px; text-align: center;
        }
        .header h1 { font-size: 1.4rem; letter-spacing: 1px; }
        .header p { font-size: 0.85rem; opacity: 0.8; margin-top: 4px; }
        
        .container {
            max-width: 500px; margin: 40px auto; padding: 0 20px;
        }
        
        .card {
            background: white; border-radius: 12px; padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .card h2 { color: #1B5E20; margin-bottom: 24px; font-size: 1.3rem; text-align: center; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.9rem; }
        .form-group input, .form-group select {
            width: 100%; padding: 12px 16px; border: 2px solid #ddd; border-radius: 8px;
            font-size: 1rem; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none; border-color: #1B5E20;
        }
        
        .btn {
            width: 100%; padding: 14px; background: #1B5E20; color: white;
            border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
        }
        .btn:hover { background: #2E7D32; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        
        .error { color: #C62828; font-size: 0.85rem; margin-top: 6px; display: none; }
        
        .exam-count { text-align: center; color: #666; font-size: 0.85rem; margin-top: 16px; }
        
        .footer { text-align: center; padding: 20px; color: #999; font-size: 0.8rem; margin-top: 40px; }
        .footer a { color: #1B5E20; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo SITE_NAME; ?></h1>
        <p>"<?php echo SITE_MOTTO; ?>"</p>
    </div>

    <div class="container">
        <div class="card">
            <h2>📝 Exam Portal</h2>
            
            <form id="examForm" onsubmit="return startExam(event);">
                <div class="form-group">
                    <label>Admission Number *</label>
                    <input type="text" id="admission" placeholder="e.g. COA/2025/0001" required autocomplete="off">
                    <div class="error" id="admError">Please enter your admission number</div>
                </div>

                <div class="form-group">
                    <label>Your Name *</label>
                    <input type="text" id="studentName" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label>Select Exam *</label>
                    <select id="examSelect" required>
                        <option value="">— Choose an exam —</option>
                        <?php foreach ($exams as $file => $name): ?>
                            <option value="<?php echo htmlspecialchars($file); ?>"><?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn" id="startBtn">🚀 Start Exam</button>
            </form>
        </div>

        <p class="exam-count"><?php echo count($exams); ?> exam(s) available</p>
    </div>

    <div class="footer">
        <p>MTTI Eldoret | Sagaas Center, 4th Floor | <a href="teacher/">Teacher Login</a></p>
    </div>

    <script>
    function startExam(e) {
        e.preventDefault();
        
        var adm = document.getElementById('admission').value.trim();
        var name = document.getElementById('studentName').value.trim();
        var exam = document.getElementById('examSelect').value;

        if (!adm) { document.getElementById('admError').style.display = 'block'; return false; }
        document.getElementById('admError').style.display = 'none';

        if (!name || !exam) return false;

        // Build URL to take-exam.php
        var url = 'take-exam.php?exam=' + encodeURIComponent(exam) 
            + '&admission=' + encodeURIComponent(adm) 
            + '&student=' + encodeURIComponent(name);

        window.location.href = url;
        return false;
    }
    </script>
</body>
</html>
