<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();
$auth = new Auth();

// API endpoints - no login required (for courier app)
if (isset($_GET["api"])) {
    // handled below
} else {
    $pageTitle = "DataPost - " . SITE_NAME;
    $auth->requireLogin();
}
$role = $auth->getRole();


// API: Ping (for network detection)
if (isset($_GET['api']) && $_GET['api'] === 'ping') {
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true,'time'=>date('Y-m-d H:i:s'),'server'=>gethostname(),'site'=>SITE_NAME]);
    exit;
}

// API: Per-student activity breakdown
if (isset($_GET['api']) && $_GET['api'] === 'per_student') {
    header('Content-Type: application/json');
    $rows = $db->fetchAll("
        SELECT u.name, u.username, MAX(c.title) as class_name,
               COUNT(a.id) as events,
               SUM(a.time_spent) as total_seconds,
               MAX(a.created_at) as last_seen,
               COUNT(DISTINCT a.lesson_id) as lessons_opened
        FROM analytics a
        JOIN users u ON u.id=a.user_id
        LEFT JOIN classes c ON c.id=a.class_id
        WHERE a.synced=0
        GROUP BY a.user_id, u.name, u.username
        ORDER BY last_seen DESC
    ");
    echo json_encode(['success'=>true,'students'=>$rows]);
    exit;
}

// API: Download CSV
if (isset($_GET['api']) && $_GET['api'] === 'download_csv') {
    $auth->requireLogin();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="mtti-analytics-'.date('Ymd-His').'.csv"');
    $rows = $db->fetchAll("SELECT a.*,u.name as user_name FROM analytics a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC");
    echo "ID,Time,User,Event,Page,ClassID,LessonID,TimeSpent,Device,Synced\n";
    foreach ($rows as $r) {
        echo $r['id'].',"'.$r['created_at'].'","'.addslashes($r['user_name']??'').'","'.$r['event_type'].'","'.addslashes($r['page']).'",'.($r['class_id']??'').','
            .($r['lesson_id']??'').','.$r['time_spent'].',"'.substr(addslashes($r['device_info']??''),0,50).'",'.$r['synced']."\n";
    }
    exit;
}

// API: Relay analytics to Google Sheets via server-side curl
if (isset($_GET['api']) && $_GET['api'] === 'relay_to_sheets') {
    header('Content-Type: application/json');
    $sheetsUrl = $db->fetchColumn("SELECT setting_value FROM settings WHERE setting_key='courier_sheets_webhook'") ?? '';
    if (!$sheetsUrl) { echo json_encode(['success'=>false,'message'=>'No Sheets URL configured']); exit; }

    $pkg = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($pkg)) {
        // Build package if not provided
        $pkg = ['summary'=>['generated_at'=>date('Y-m-d H:i:s'),'server_name'=>SITE_NAME]];
    }

    $ch = curl_init($sheetsUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($pkg),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) { echo json_encode(['success'=>false,'message'=>$err]); exit; }
    echo json_encode(['success'=>true,'http_code'=>$code,'response'=>substr($res,0,200)]);
    exit;
}

// API: Send email report via SMTP (curl)
if (isset($_GET['api']) && $_GET['api'] === 'send_email') {
    header('Content-Type: application/json');

    // Load SMTP settings
    $smtpRows = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','report_email','courier_email')");
    $smtp = [];
    foreach ($smtpRows as $r) $smtp[$r['setting_key']] = $r['setting_value'];

    $dest = $smtp['report_email'] ?: ($smtp['courier_email'] ?? '');
    $host = $smtp['smtp_host'] ?: 'smtp.gmail.com';
    $port = (int)($smtp['smtp_port'] ?: 587);
    $user = $smtp['smtp_user'] ?? '';
    $pass = $smtp['smtp_pass'] ?? '';
    $from = $smtp['smtp_from'] ?: $user;

    if (!$dest) { echo json_encode(['success'=>false,'message'=>'No recipient email configured. Set Report Email in Sync Settings.']); exit; }
    if (!$user || !$pass) { echo json_encode(['success'=>false,'message'=>'SMTP credentials not set. Configure SMTP settings below.']); exit; }

    $students = (int)$db->fetchColumn("SELECT COUNT(*) FROM users WHERE role='student'");
    $today    = (int)$db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM analytics WHERE DATE(created_at)=CURDATE()");
    $week     = (int)$db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM analytics WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)");
    $unsynced = (int)$db->fetchColumn("SELECT COUNT(*) FROM analytics WHERE synced=0");
    $totalHrs = round((float)$db->fetchColumn("SELECT COALESCE(SUM(time_spent),0) FROM analytics") / 3600, 1);

    $topLessons = $db->fetchAll("SELECT l.title, c.title as cls, COUNT(a.id) as views FROM analytics a JOIN lessons l ON a.lesson_id=l.id JOIN classes c ON l.class_id=c.id WHERE a.lesson_id IS NOT NULL GROUP BY a.lesson_id ORDER BY views DESC LIMIT 5");
    $topQuizzes = $db->fetchAll("SELECT q.title, ROUND(AVG(qa.percentage),1) as avg, COUNT(qa.id) as att FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id=q.id GROUP BY qa.quiz_id ORDER BY att DESC LIMIT 5");

    $subject = 'M.T.T.I LMS Report — '.date('d M Y');
    $crlf = "\r\n";
    $body  = "M.T.T.I LMS — Usage & Analytics Report{$crlf}";
    $body .= "Generated: ".date('Y-m-d H:i:s')."{$crlf}";
    $body .= "Server: ".SITE_URL."{$crlf}{$crlf}";
    $body .= "SUMMARY{$crlf}-------{$crlf}";
    $body .= "Total Students:   {$students}{$crlf}";
    $body .= "Active Today:     {$today}{$crlf}";
    $body .= "Active This Week: {$week}{$crlf}";
    $body .= "Study Hours:      {$totalHrs}h{$crlf}";
    $body .= "Unsynced Events:  {$unsynced}{$crlf}{$crlf}";

    if (!empty($topLessons)) {
        $body .= "TOP LESSONS{$crlf}-----------{$crlf}";
        foreach ($topLessons as $l) $body .= "  {$l['views']} views — {$l['title']} ({$l['cls']}){$crlf}";
        $body .= "{$crlf}";
    }
    if (!empty($topQuizzes)) {
        $body .= "QUIZ PERFORMANCE{$crlf}----------------{$crlf}";
        foreach ($topQuizzes as $q) $body .= "  {$q['title']}: avg {$q['avg']}% ({$q['att']} attempts){$crlf}";
        $body .= "{$crlf}";
    }
    $body .= "CSV download: ".SITE_URL."/datapost.php?download=1{$crlf}";

    // Write message to temp file and send via curl SMTP
    $msgFile = tempnam(sys_get_temp_dir(), 'mtti_mail_');
    $date = date('r');
    file_put_contents($msgFile,
        "Date: {$date}{$crlf}To: {$dest}{$crlf}From: M.T.T.I LMS <{$from}>{$crlf}Subject: {$subject}{$crlf}MIME-Version: 1.0{$crlf}Content-Type: text/plain; charset=UTF-8{$crlf}{$crlf}{$body}"
    );
    $url = ($port === 465) ? "smtps://{$host}:{$port}" : "smtp://{$host}:{$port}";
    $cmd = sprintf('curl -s --url %s --ssl-reqd --mail-from %s --mail-rcpt %s --upload-file %s --user %s 2>&1',
        escapeshellarg($url),
        escapeshellarg($from),
        escapeshellarg($dest),
        escapeshellarg($msgFile),
        escapeshellarg("{$user}:{$pass}")
    );
    $out = []; $code = 0;
    exec($cmd, $out, $code);
    @unlink($msgFile);

    if ($code === 0) {
        echo json_encode(['success'=>true, 'message'=>"Report sent to {$dest}"]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'SMTP error: '.implode(' | ', $out)]);
    }
    exit;
}

// API: Log a courier sync event
if (isset($_GET['api']) && $_GET['api'] === 'log_sync' && $_SERVER['REQUEST_METHOD']==='POST') {
    header('Content-Type: application/json');
    $d = json_decode(file_get_contents('php://input'), true) ?? [];
    $db->query(
        "INSERT INTO courier_sync_log (device_info,action,entries_count,destination,status,details,created_at) VALUES (?,?,?,?,?,?,NOW())",
        [
            substr($d['device']??$_SERVER['HTTP_USER_AGENT']??'unknown',0,200),
            $d['action']??'post',
            (int)($d['entries']??0),
            $d['destination']??'unknown',
            $d['status']??'success',
            substr($d['details']??'',0,500),
        ]
    );
    echo json_encode(['success'=>true]);
    exit;
}

// API: Generate sync package
if (isset($_GET['api']) && $_GET['api'] === 'sync_package') {
    header('Content-Type: application/json');
    $unsynced = $db->fetchAll("SELECT * FROM analytics WHERE synced=0 ORDER BY created_at");
    $totalUsers = (int)$db->fetchColumn("SELECT COUNT(*) FROM users WHERE role='student'");
    $totalEnrolments = (int)$db->fetchColumn("SELECT COUNT(*) FROM enrolments");
    $activeToday = (int)$db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM analytics WHERE DATE(created_at)=CURDATE()");
    $activeWeek = (int)$db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM analytics WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $lessonViews = $db->fetchAll("SELECT l.title as lesson, c.title as class_name, COUNT(a.id) as views, SUM(a.time_spent) as total_time FROM analytics a JOIN lessons l ON a.lesson_id=l.id JOIN classes c ON l.class_id=c.id WHERE a.synced=0 AND a.lesson_id IS NOT NULL GROUP BY a.lesson_id ORDER BY views DESC");
    $quizScores = $db->fetchAll("SELECT q.title as quiz, c.title as class_name, COUNT(qa.id) as attempts, ROUND(AVG(qa.percentage),1) as avg_score, SUM(qa.passed) as passed FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id=q.id JOIN classes c ON q.class_id=c.id GROUP BY qa.quiz_id ORDER BY attempts DESC");
    $classEnrol = $db->fetchAll("SELECT c.title, c.location, c.project_name, COUNT(e.id) as students FROM classes c LEFT JOIN enrolments e ON c.id=e.class_id WHERE c.status='active' GROUP BY c.id ORDER BY students DESC");
    $csv = "ID,Timestamp,User,Event,Page,ClassID,LessonID,TimeSpent,Device,Lat,Lng\n";
    foreach ($unsynced as $r) {
        $uname = $db->fetchColumn("SELECT name FROM users WHERE id=?", [$r['user_id']]);
        $csv .= $r['id'].',"'.$r['created_at'].'","'.($uname ?: 'Unknown').'","'.$r['event_type'].'","'.$r['page'].'",'.($r['class_id'] ?: '').','
            .($r['lesson_id'] ?: '').','.$r['time_spent'].',"'.substr($r['device_info'] ?? '',0,50).'",'
            .($r['latitude'] ?: '').','
            .($r['longitude'] ?: '')."\n";
    }
    echo json_encode([
        'success' => true,
        'summary' => [
            'server_name' => SITE_NAME,
            'school_location' => $db->fetchColumn("SELECT location FROM classes WHERE location IS NOT NULL AND location != '' LIMIT 1") ?? 'Not set',
            'project_name' => $db->fetchColumn("SELECT project_name FROM classes WHERE project_name IS NOT NULL AND project_name != '' LIMIT 1") ?? 'Not set', 'server_url' => SITE_URL, 'generated_at' => date('Y-m-d H:i:s'),
            'total_students' => $totalUsers, 'total_enrolments' => $totalEnrolments,
            'active_today' => $activeToday, 'active_this_week' => $activeWeek,
            'unsynced_events' => count($unsynced),
            'lesson_views' => $lessonViews, 'quiz_scores' => $quizScores, 'class_enrolments' => $classEnrol
        ],
        'raw_csv' => $csv,
        'entry_ids' => array_column($unsynced, 'id')
    ]);
    exit;
}

// API: Mark as synced
if (isset($_GET['api']) && $_GET['api'] === 'mark_synced' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $ids = $data['entry_ids'] ?? [];
    $dest = $data['destination'] ?? 'unknown';
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->query("UPDATE analytics SET synced=1, synced_at=NOW() WHERE id IN ($placeholders)", array_map('intval', $ids));
    }
    $db->insert('courier_sync_log', [
        'device_info' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        'action' => 'post', 'entries_count' => count($ids),
        'destination' => $dest, 'status' => 'success', 'created_at' => date('Y-m-d H:i:s')
    ]);
    echo json_encode(['success' => true, 'marked' => count($ids)]);
    exit;
}

// API: Save settings
if (isset($_GET['api']) && $_GET['api'] === 'save_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $auth->requireLogin();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $allowed = ['report_email','smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','courier_sheets_webhook','courier_custom_server'];
    foreach ($allowed as $key) {
        if (array_key_exists($key, $data)) {
            $db->query("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)",
                [$key, trim($data[$key] ?? '')]);
        }
    }
    echo json_encode(['success'=>true]);
    exit;
}

// API: Sync school stats to masomoteletraining.co.ke/mtti-sync.php
if (isset($_GET['api']) && $_GET['api'] === 'sync_to_map' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $auth->requireLogin();
    $mapUrl = 'https://masomoteletraining.co.ke/mtti-sync.php';
    $syncKey = DATAPOST_KEY;

    $sql = "SELECT c.id, c.title AS name, COALESCE(c.location,'Unknown') AS location,
        COALESCE(c.lat, 0.5149) AS lat, COALESCE(c.lng, 35.2690) AS lng, c.grade_level,
        COUNT(DISTINCT e.user_id) AS enrolled,
        (SELECT COUNT(*) FROM subjects s2 WHERE s2.class_id=c.id) AS total_subjects,
        (SELECT COUNT(*) FROM lessons l2 JOIN subjects s3 ON l2.subject_id=s3.id WHERE s3.class_id=c.id) AS total_lessons,
        COUNT(DISTINCT co.lesson_id) AS lessons_completed,
        COUNT(DISTINCT qa.id) AS quiz_attempts,
        ROUND(AVG(CASE WHEN qa.percentage>0 THEN qa.percentage END),1) AS avg_score,
        COUNT(DISTINCT cert.id) AS certificates,
        COUNT(DISTINCT CASE WHEN co.completed_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) THEN co.user_id END) AS active_30d
        FROM classes c
        LEFT JOIN enrolments e ON e.class_id=c.id
        LEFT JOIN completions co ON co.class_id=c.id
        LEFT JOIN quizzes qz ON qz.class_id=c.id
        LEFT JOIN quiz_attempts qa ON qa.quiz_id=qz.id AND qa.percentage>0
        LEFT JOIN certificates cert ON cert.class_id=c.id
        WHERE c.status='active'
        GROUP BY c.id, c.title, c.location, c.lat, c.lng, c.grade_level
        ORDER BY enrolled DESC, c.title ASC";
    $schools = $db->fetchAll($sql);
    $payload = json_encode(['schools' => $schools, 'source' => gethostname() . '-mtti']);

    $ch = curl_init($mapUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Sync-Key: ' . $syncKey],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $parsed = json_decode($res, true);
    if ($parsed && !empty($parsed['ok'])) {
        echo json_encode(['success' => true, 'saved' => $parsed['saved'], 'ts' => $parsed['ts']]);
    } else {
        echo json_encode(['success' => false, 'error' => $err ?: ($res ?: 'Unknown error')]);
    }
    exit;
}

// Download CSV
if (isset($_GET['download'])) {
    $rows = $db->fetchAll("SELECT a.*, u.name as user_name FROM analytics a LEFT JOIN users u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 10000");
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="mtti-datapost-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Timestamp','User','Event','Page','ClassID','LessonID','TimeSpent','Device','Lat','Lng','Synced']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['id'],$r['created_at'],$r['user_name'],$r['event_type'],$r['page'],$r['class_id'],$r['lesson_id'],$r['time_spent'],$r['device_info'],$r['latitude'],$r['longitude'],$r['synced'] ? 'Yes' : 'No']);
    }
    fclose($out);
    exit;
}

// Page stats
$totalEvents = (int)$db->fetchColumn("SELECT COUNT(*) FROM analytics");
$unsyncedCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM analytics WHERE synced=0");
$syncedCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM analytics WHERE synced=1");
$activeToday = (int)$db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM analytics WHERE DATE(created_at)=CURDATE()");
$totalTimeSecs = (int)$db->fetchColumn("SELECT COALESCE(SUM(time_spent),0) FROM analytics");
$totalTimeHrs = round($totalTimeSecs / 3600, 1);

$topLessons = $db->fetchAll("SELECT l.title, c.title as class_name, COUNT(a.id) as views, ROUND(SUM(a.time_spent)/60) as mins FROM analytics a JOIN lessons l ON a.lesson_id=l.id JOIN classes c ON l.class_id=c.id WHERE a.lesson_id IS NOT NULL GROUP BY a.lesson_id ORDER BY views DESC LIMIT 10");
$quizPerf = $db->fetchAll("SELECT q.title, ROUND(AVG(qa.percentage),1) as avg_score, COUNT(qa.id) as attempts, SUM(qa.passed) as passed FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id=q.id GROUP BY qa.quiz_id ORDER BY attempts DESC LIMIT 10");
$classProg = $db->fetchAll("SELECT c.title, COUNT(DISTINCT e.user_id) as students, (SELECT COUNT(*) FROM lessons WHERE class_id=c.id) as total_lessons FROM classes c LEFT JOIN enrolments e ON c.id=e.class_id WHERE c.status='active' GROUP BY c.id ORDER BY students DESC");
$recentEvents = $db->fetchAll("SELECT a.*, u.name as user_name FROM analytics a LEFT JOIN users u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 20");
$hourly = $db->fetchAll("SELECT HOUR(created_at) as hr, COUNT(*) as hits FROM analytics GROUP BY hr ORDER BY hr");
$devices = $db->fetchAll("SELECT CASE WHEN device_info LIKE '%Mobile%' OR device_info LIKE '%Android%' OR device_info LIKE '%iPhone%' THEN 'Mobile' WHEN device_info LIKE '%Tablet%' OR device_info LIKE '%iPad%' THEN 'Tablet' ELSE 'Desktop' END as dtype, COUNT(*) as cnt FROM analytics WHERE device_info IS NOT NULL GROUP BY dtype ORDER BY cnt DESC");

// Settings
$settings = [];
$settingRows = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'courier_%' OR setting_key LIKE 'smtp_%' OR setting_key='report_email'");
foreach ($settingRows as $r) $settings[$r['setting_key']] = $r['setting_value'];

$syncLog = $db->fetchAll("SELECT * FROM courier_sync_log ORDER BY created_at DESC LIMIT 10");

require_once __DIR__ . '/templates/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="bi bi-broadcast me-2 text-primary"></i>DataPost</h4>
            <small class="text-muted">Auto-captured usage data | Sync to cloud when online</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?download=1" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>CSV</a>
            <button class="btn btn-primary btn-sm" onclick="syncToCloud()" id="mainSyncBtn"><i class="bi bi-cloud-upload me-1"></i>Sync (<?=$unsyncedCount?>)</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="syncToMap()" id="mapSyncBtn" title="Push school stats to masomoteletraining.co.ke/locations.php"><i class="bi bi-geo-alt me-1"></i>Sync to Map</button>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2"><div class="card bg-primary text-white p-3 text-center"><h3><?=$totalEvents?></h3><small>Total Events</small></div></div>
        <div class="col-md-2"><div class="card bg-warning text-dark p-3 text-center"><h3 id="unsyncNum"><?=$unsyncedCount?></h3><small>Pending Sync</small></div></div>
        <div class="col-md-2"><div class="card bg-success text-white p-3 text-center"><h3><?=$syncedCount?></h3><small>Synced</small></div></div>
        <div class="col-md-2"><div class="card bg-info text-white p-3 text-center"><h3><?=$activeToday?></h3><small>Active Today</small></div></div>
        <div class="col-md-2"><div class="card bg-secondary text-white p-3 text-center"><h3><?=$totalTimeHrs?>h</h3><small>Study Time</small></div></div>
        <div class="col-md-2"><div class="card p-3 text-center"><span id="netBadge" class="badge bg-secondary">Checking...</span><br><small class="text-muted">Network</small></div></div>
    </div>

    <div class="row g-4">
        <!-- Lesson Views -->
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><h6 class="mb-0"><i class="bi bi-eye me-2"></i>Lesson Views & Time</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>Lesson</th><th>Class</th><th>Views</th><th>Time</th></tr></thead>
            <tbody>
            <?php if (!empty($topLessons)): ?>
                <?php foreach($topLessons as $l): ?>
                <tr><td><?=htmlspecialchars(substr($l['title'],0,30))?></td><td><small class="text-muted"><?=htmlspecialchars(substr($l['class_name'],0,20))?></small></td>
                <td><span class="badge bg-primary"><?=$l['views']?></span></td><td><?=$l['mins']?> min</td></tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="text-muted">No lesson data yet. Data appears as students view lessons.</td></tr>
            <?php endif; ?>
            </tbody></table></div></div>
        </div>

        <!-- Quiz Scores -->
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><h6 class="mb-0"><i class="bi bi-trophy me-2"></i>Quiz Performance</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>Quiz</th><th>Avg Score</th><th>Attempts</th><th>Passed</th></tr></thead>
            <tbody>
            <?php if (!empty($quizPerf)): ?>
                <?php foreach($quizPerf as $q): ?>
                <?php
                    $scoreClass = 'danger';
                    if ($q['avg_score'] >= 70) $scoreClass = 'success';
                    elseif ($q['avg_score'] >= 50) $scoreClass = 'warning';
                ?>
                <tr><td><?=htmlspecialchars(substr($q['title'],0,30))?></td>
                <td><span class="badge bg-<?=$scoreClass?>"><?=$q['avg_score']?>%</span></td>
                <td><?=$q['attempts']?></td><td><?=$q['passed']?></td></tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="text-muted">No quiz data yet.</td></tr>
            <?php endif; ?>
            </tbody></table></div></div>
        </div>

        <!-- Class Enrolment -->
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><h6 class="mb-0"><i class="bi bi-people me-2"></i>Enrolments & Progress</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>Class</th><th>Students</th><th>Lessons</th></tr></thead>
            <tbody>
            <?php if (!empty($classProg)): ?>
                <?php foreach($classProg as $c): ?>
                <tr><td><?=htmlspecialchars($c['title'])?></td><td><span class="badge bg-primary"><?=$c['students']?></span></td><td><?=$c['total_lessons']?></td></tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-muted">No classes yet.</td></tr>
            <?php endif; ?>
            </tbody></table></div></div>
        </div>

        <!-- Devices -->
        <div class="col-lg-3">
            <div class="card"><div class="card-header"><h6 class="mb-0"><i class="bi bi-phone me-2"></i>Devices</h6></div>
            <div class="card-body">
            <?php if (!empty($devices)): ?>
                <?php
                    $totalDev = 0;
                    foreach ($devices as $d) $totalDev += (int)$d['cnt'];
                    if ($totalDev < 1) $totalDev = 1;
                ?>
                <?php foreach($devices as $d):
                    $pct = round(($d['cnt'] / $totalDev) * 100);
                    $icon = ($d['dtype'] === 'Mobile') ? 'phone' : 'laptop';
                ?>
                <div class="d-flex justify-content-between small mb-1">
                    <span><i class="bi bi-<?=$icon?> me-1"></i><?=$d['dtype']?></span>
                    <span><?=$d['cnt']?> (<?=$pct?>%)</span>
                </div>
                <div class="progress mb-2" style="height:6px"><div class="progress-bar" style="width:<?=$pct?>%"></div></div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted small">No device data yet.</p>
            <?php endif; ?>
            </div></div>
        </div>

        <!-- Peak Hours -->
        <div class="col-lg-3">
            <div class="card"><div class="card-header"><h6 class="mb-0"><i class="bi bi-clock me-2"></i>Peak Hours</h6></div>
            <div class="card-body">
            <?php if (!empty($hourly)): ?>
                <?php
                    $maxH = 0;
                    foreach ($hourly as $h) { if ((int)$h['hits'] > $maxH) $maxH = (int)$h['hits']; }
                    if ($maxH < 1) $maxH = 1;
                ?>
                <?php foreach($hourly as $h):
                    $pct = round(($h['hits'] / $maxH) * 100);
                ?>
                <div class="d-flex justify-content-between small mb-1">
                    <span><?=str_pad($h['hr'], 2, '0', STR_PAD_LEFT)?>:00</span>
                    <span><?=$h['hits']?></span>
                </div>
                <div class="progress mb-2" style="height:6px"><div class="progress-bar bg-info" style="width:<?=$pct?>%"></div></div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted small">No hourly data yet.</p>
            <?php endif; ?>
            </div></div>
        </div>

        <!-- Recent Events -->
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><h6 class="mb-0"><i class="bi bi-activity me-2"></i>Live Event Feed</h6></div>
            <div class="table-responsive" style="max-height:300px;overflow-y:auto"><table class="table table-sm mb-0">
            <thead><tr><th>Time</th><th>User</th><th>Event</th><th>Page</th><th>Time</th></tr></thead>
            <tbody>
            <?php if (!empty($recentEvents)): ?>
                <?php foreach($recentEvents as $e): ?>
                <tr>
                    <td><small><?=date('M j g:ia', strtotime($e['created_at']))?></small></td>
                    <td><small><?=htmlspecialchars($e['user_name'] ?? '?')?></small></td>
                    <td><span class="badge bg-secondary"><?=$e['event_type']?></span></td>
                    <td><small><?=htmlspecialchars(basename($e['page'] ?? ''))?></small></td>
                    <td><?=$e['time_spent']?>s</td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted">No events yet. Data appears as users browse the LMS.</td></tr>
            <?php endif; ?>
            </tbody></table></div></div>
        </div>

        <?php if ($role === 'admin'): ?>
        <!-- Sync Settings -->
        <div class="col-lg-6">
            <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-cloud-upload me-2"></i>Sync &amp; Email Settings</h6>
                <button class="btn btn-sm btn-outline-primary" onclick="sendEmailReport()" id="emailBtn"><i class="bi bi-envelope me-1"></i>Send Email Report</button>
            </div>
            <div class="card-body">
                <form id="settingsForm">
                <p class="text-muted small mb-3">Configure where synced data is sent. SMTP is used for email reports — use a Gmail App Password.</p>

                <div class="mb-2"><label class="form-label small fw-semibold"><i class="bi bi-envelope me-1"></i>Report Recipient Email</label>
                    <input type="email" name="report_email" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['report_email'] ?? '')?>" placeholder="who@receives.com"></div>

                <hr class="my-2"><p class="small fw-semibold text-muted mb-2">SMTP Settings</p>
                <div class="row g-2 mb-2">
                    <div class="col-8"><label class="form-label small">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com')?>" placeholder="smtp.gmail.com"></div>
                    <div class="col-4"><label class="form-label small">Port</label>
                        <input type="number" name="smtp_port" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['smtp_port'] ?? '587')?>" placeholder="587"></div>
                </div>
                <div class="mb-2"><label class="form-label small">SMTP Username (your email)</label>
                    <input type="email" name="smtp_user" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['smtp_user'] ?? '')?>" placeholder="youremail@gmail.com"></div>
                <div class="mb-2"><label class="form-label small">SMTP Password / App Password</label>
                    <input type="password" name="smtp_pass" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['smtp_pass'] ?? '')?>" placeholder="Gmail: use App Password, not account password">
                    <small class="text-muted">Gmail: enable 2FA → <a href="https://myaccount.google.com/apppasswords" target="_blank">generate App Password</a></small></div>
                <div class="mb-3"><label class="form-label small">From Name/Email</label>
                    <input type="email" name="smtp_from" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['smtp_from'] ?? '')?>" placeholder="Same as SMTP username usually"></div>

                <hr class="my-2"><p class="small fw-semibold text-muted mb-2">Cloud Destinations</p>
                <div class="mb-2"><label class="form-label small fw-semibold"><i class="bi bi-table me-1"></i>Google Sheets Webhook</label>
                    <input type="url" name="courier_sheets_webhook" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['courier_sheets_webhook'] ?? '')?>" placeholder="https://script.google.com/..."></div>
                <div class="mb-3"><label class="form-label small fw-semibold"><i class="bi bi-server me-1"></i>Custom Server URL</label>
                    <input type="url" name="courier_custom_server" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['courier_custom_server'] ?? '')?>" placeholder="https://your-server.com/receive.php"></div>

                <button type="button" class="btn btn-primary btn-sm" onclick="saveSettings()"><i class="bi bi-save me-1"></i>Save Settings</button>
                <span id="saveMsg" class="ms-2 small text-success" style="display:none">Saved!</span>
                </form>
            </div></div>
        </div>

        <!-- Sync Log -->
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Sync History</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>Time</th><th>Action</th><th>Entries</th><th>To</th></tr></thead>
            <tbody>
            <?php if (!empty($syncLog)): ?>
                <?php foreach($syncLog as $s): ?>
                <tr>
                    <td><small><?=date('M j g:ia', strtotime($s['created_at']))?></small></td>
                    <td><span class="badge bg-success"><?=$s['action']?></span></td>
                    <td><?=$s['entries_count']?></td>
                    <td><small><?=htmlspecialchars($s['destination'] ?? '-')?></small></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="text-muted">No syncs yet.</td></tr>
            <?php endif; ?>
            </tbody></table></div></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
var SU = '<?=SITE_URL?>';

function checkNet() {
    var el = document.getElementById('netBadge');
    if (navigator.onLine) {
        el.innerHTML = '<i class="bi bi-wifi"></i> Online';
        el.className = 'badge bg-success';
    } else {
        el.innerHTML = '<i class="bi bi-wifi-off"></i> Offline';
        el.className = 'badge bg-danger';
    }
}
setInterval(checkNet, 5000);
checkNet();

function syncToCloud() {
    if (!navigator.onLine) {
        alert('No internet. Connect to mobile data or WiFi first.');
        return;
    }

    var btn = document.getElementById('mainSyncBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Syncing...';

    fetch(SU + '/datapost.php?api=sync_package')
        .then(function(r) { return r.json(); })
        .then(function(pkg) {
            if (!pkg.success || pkg.entry_ids.length === 0) {
                alert('Nothing to sync!');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Sync (0)';
                return;
            }

            var sent = false;
            var dest = '';
            var sheetsUrl = '<?=htmlspecialchars($settings['courier_sheets_webhook'] ?? '')?>';
            var customUrl = '<?=htmlspecialchars($settings['courier_custom_server'] ?? '')?>';
            var email = '<?=htmlspecialchars($settings['courier_email'] ?? '')?>';

            // Try Google Sheets
            if (sheetsUrl) {
                try {
                    var summary = pkg.summary;
                    fetch(sheetsUrl, {
                        method: 'POST',
                        mode: 'no-cors',
                        body: JSON.stringify({
                            type: 'summary', server: summary.server_name, date: summary.generated_at,
                            students: summary.total_students, enrolments: summary.total_enrolments,
                            active_today: summary.active_today, events: summary.unsynced_events
                        })
                    });
                    sent = true;
                    dest = 'google_sheets';
                } catch(e) {}
            }

            // Try custom server
            if (customUrl && !sent) {
                try {
                    fetch(customUrl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(pkg)
                    }).then(function(r) { if (r.ok) { sent = true; dest = 'custom_server'; } });
                } catch(e) {}
            }

            // Email
            // Email is now handled server-side via SMTP — skip mailto here

            // Mark synced
            if (sent) {
                fetch(SU + '/datapost.php?api=mark_synced', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({entry_ids: pkg.entry_ids, destination: dest})
                }).then(function() {
                    alert('Synced ' + pkg.entry_ids.length + ' events to ' + dest + '!');
                    location.reload();
                });
            } else {
                alert('No sync destination configured. Set email, Sheets, or server URL below.');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Sync';
        })
        .catch(function(e) {
            alert('Sync failed: ' + e.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Sync';
        });
}

// Auto-sync when online
window.addEventListener('online', function() {
    checkNet();
    var unsync = parseInt(document.getElementById('unsyncNum').textContent || '0');
    if (unsync > 0) {
        setTimeout(syncToCloud, 5000);
    }
});

function syncToMap() {
    var btn = document.getElementById('mapSyncBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Syncing...';
    fetch(SU + '/datapost.php?api=sync_to_map', {method:'POST'})
        .then(function(r){ return r.json(); })
        .then(function(d){
            btn.disabled = false;
            if (d.success) {
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Synced (' + d.saved + ')';
                btn.className = 'btn btn-sm btn-success';
                setTimeout(function(){ btn.innerHTML = '<i class="bi bi-geo-alt me-1"></i>Sync to Map'; btn.className = 'btn btn-sm btn-outline-secondary'; }, 4000);
            } else {
                btn.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Failed';
                btn.className = 'btn btn-sm btn-danger';
                setTimeout(function(){ btn.innerHTML = '<i class="bi bi-geo-alt me-1"></i>Sync to Map'; btn.className = 'btn btn-sm btn-outline-secondary'; }, 4000);
                console.error('Map sync error:', d.error);
            }
        })
        .catch(function(e){
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-geo-alt me-1"></i>Sync to Map';
            btn.className = 'btn btn-sm btn-outline-secondary';
        });
}

function sendEmailReport() {
    var btn = document.getElementById('emailBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';
    fetch(SU + '/datapost.php?api=send_email', {method:'POST'})
        .then(function(r){ return r.json(); })
        .then(function(d){
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-envelope me-1"></i>Send Email Report';
            if (d.success) {
                alert('✅ ' + d.message);
            } else {
                alert('❌ ' + d.message);
            }
        })
        .catch(function(e){
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-envelope me-1"></i>Send Email Report';
            alert('Request failed: ' + e.message);
        });
}

function saveSettings() {
    var form = document.getElementById('settingsForm');
    var data = {};
    form.querySelectorAll('input[name], select[name], textarea[name]').forEach(function(el){
        data[el.name] = el.value;
    });
    fetch(SU + '/datapost.php?api=save_settings', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(function(r){ return r.json(); }).then(function(d){
        if (d.success) {
            var msg = document.getElementById('saveMsg');
            msg.style.display = 'inline';
            setTimeout(function(){ msg.style.display = 'none'; }, 3000);
        }
    });
}
</script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
