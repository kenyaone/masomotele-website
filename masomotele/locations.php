<?php
session_start();

// ─── CONFIG ──────────────────────────────────────────────────────────────────
define('MAP_PASSWORD', 'mtti2026');
// ─────────────────────────────────────────────────────────────────────────────

$dsn = 'mysql:host=localhost;dbname=mtti_lms;charset=utf8mb4';
$dbUser = 'tele';
$dbPass = 'kenya123@';

$pdo = null;
$dbError = null;
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    $dbError = 'Database connection failed: ' . $e->getMessage();
}

// ─── AUTH ─────────────────────────────────────────────────────────────────────
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$loginError = '';
if (isset($_POST['password']) && !isset($_SESSION['mtti_map_auth'])) {
    $pw = trim($_POST['password']);
    if ($pw === MAP_PASSWORD) {
        $_SESSION['mtti_map_auth'] = true;
    } else {
        $loginError = 'Incorrect password. Please try again.';
    }
}

if (!isset($_SESSION['mtti_map_auth'])) {
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MTTI — Schools Map</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(135deg,#0f3460 0%,#16213e 50%,#e94560 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.card{background:white;border-radius:16px;padding:40px 36px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.35);}
.logo{text-align:center;margin-bottom:28px;}
.logo img{width:70px;height:70px;object-fit:contain;margin-bottom:12px;border-radius:8px;}
.logo h1{font-size:1.7rem;color:#0f3460;font-weight:900;letter-spacing:1px;}
.logo p{color:#666;font-size:.88rem;margin-top:4px;}
.badge{display:inline-block;background:#0f3460;color:white;padding:3px 12px;border-radius:20px;font-size:.75rem;font-weight:700;margin-top:6px;}
label{display:block;font-weight:600;color:#333;margin-bottom:6px;font-size:.9rem;}
input[type=password]{width:100%;padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;font-size:1rem;outline:none;transition:.2s;}
input[type=password]:focus{border-color:#0f3460;}
.btn{width:100%;padding:13px;background:#0f3460;color:white;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;margin-top:16px;transition:.2s;}
.btn:hover{background:#1a4a8a;}
.error{background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:6px;font-size:.88rem;margin-bottom:16px;}
.hint{text-align:center;color:#999;font-size:.8rem;margin-top:18px;}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1>M.T.T.I</h1>
        <p>Schools Performance Map</p>
        <span class="badge">RESTRICTED ACCESS</span>
    </div>
    <?php if ($loginError): ?>
    <div class="error">⚠ <?= htmlspecialchars($loginError) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label for="pw">Access Password</label>
        <input type="password" id="pw" name="password" placeholder="Enter access password" autofocus required>
        <button type="submit" class="btn">Access Dashboard →</button>
    </form>
    <p class="hint">Contact the administrator for access credentials.</p>
</div>
</body>
</html><?php
    exit;
}

// ─── AUTHENTICATED: LOAD DATA ─────────────────────────────────────────────────
$schools         = [];
$total_schools   = 0;
$total_students  = 0;
$total_lessons   = 0;
$total_certs     = 0;
$global_avg      = 0;
$total_active30  = 0;
$schoolsJson     = '[]';

if ($pdo && !$dbError) {
    try {
        // Main per-class stats query
        $sql = "
            SELECT
                c.id,
                c.title AS name,
                COALESCE(c.location, 'Unknown') AS location,
                COALESCE(c.lat, 0.5149) AS lat,
                COALESCE(c.lng, 35.2690) AS lng,
                c.grade_level,
                c.kicd_category,
                COUNT(DISTINCT e.user_id)  AS enrolled,
                COUNT(DISTINCT CASE WHEN e.status='active' THEN e.user_id END) AS active_students,
                (SELECT COUNT(*) FROM subjects s2 WHERE s2.class_id = c.id) AS total_subjects,
                (SELECT COUNT(*) FROM lessons l2
                    JOIN subjects s3 ON l2.subject_id = s3.id
                    WHERE s3.class_id = c.id) AS total_lessons,
                COUNT(DISTINCT co.lesson_id) AS lessons_completed,
                COUNT(DISTINCT qa.id) AS quiz_attempts,
                ROUND(AVG(CASE WHEN qa.percentage > 0 THEN qa.percentage END), 1) AS avg_score,
                COUNT(DISTINCT cert.id) AS certificates,
                COUNT(DISTINCT CASE WHEN co.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                    THEN co.user_id END) AS active_30d
            FROM classes c
            LEFT JOIN enrolments e    ON e.class_id = c.id
            LEFT JOIN completions co  ON co.class_id = c.id
            LEFT JOIN quizzes qz      ON qz.class_id = c.id
            LEFT JOIN quiz_attempts qa ON qa.quiz_id = qz.id AND qa.percentage > 0
            LEFT JOIN certificates cert ON cert.class_id = c.id
            WHERE c.status = 'active'
            GROUP BY c.id, c.title, c.location, c.lat, c.lng, c.grade_level, c.kicd_category
            ORDER BY enrolled DESC, c.title ASC
        ";
        $stmt = $pdo->query($sql);
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Totals
        $total_schools  = count($schools);
        $total_students = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student' OR role='learner'")->fetchColumn();
        // Fallback: use enrolments count if no explicit student role
        if ($total_students === 0) {
            $total_students = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM enrolments")->fetchColumn();
        }
        $total_certs   = (int)$pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
        $total_lessons = (int)$pdo->query("SELECT COUNT(*) FROM lessons")->fetchColumn();

        // Global avg quiz score (non-zero attempts only)
        $global_avg = (float)$pdo->query("SELECT ROUND(AVG(percentage),1) FROM quiz_attempts WHERE percentage > 0")->fetchColumn();

        // Total active in last 30 days across all schools
        $total_active30 = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM completions WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

        $schoolsJson = json_encode($schools);

    } catch (Exception $e) {
        $dbError = 'Data query failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MTTI — Schools Map</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.20.3/xlsx.full.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(135deg,#0f3460 0%,#16213e 50%,#533483 100%);background-attachment:fixed;min-height:100vh;color:#1a1a1a;}
.container{max-width:1200px;margin:0 auto;padding:20px;}
.header{background:rgba(255,255,255,.97);border-radius:12px;padding:22px 25px;margin-bottom:20px;box-shadow:0 4px 12px rgba(0,0,0,.12);}
.header-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:12px;}
.header-top h1{font-size:1.8rem;color:#0f3460;display:flex;align-items:center;gap:10px;}
.header-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.role-badge{background:#0f3460;color:white;padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:700;}
.btn-action{color:white;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;font-size:.88rem;transition:.2s;}
.btn-excel{background:#1d6f42;}
.btn-excel:hover{background:#166134;}
.btn-print{background:#0f3460;}
.btn-print:hover{background:#1a4a8a;}
.logout-btn{background:transparent;color:#dc2626;border:2px solid #dc2626;padding:9px 18px;border-radius:8px;font-weight:700;cursor:pointer;font-size:.85rem;}
.logout-btn:hover{background:#dc2626;color:white;}
.alert{background:#fff3cd;border:1px solid #ffc107;color:#856404;padding:12px;border-radius:8px;margin-bottom:16px;}
.alert.error{background:#f8d7da;border-color:#f5c6cb;color:#721c24;}

/* Stats bar */
.stats-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;}
.stat-item{background:linear-gradient(135deg,#0f3460,#1a4a8a);color:white;padding:15px;border-radius:8px;text-align:center;}
.stat-item .number{font-size:1.7rem;font-weight:900;}
.stat-item .label{font-size:.82rem;opacity:.9;margin-top:4px;}

/* Map */
.map-section{background:white;border-radius:12px;overflow:hidden;margin-bottom:20px;box-shadow:0 4px 12px rgba(0,0,0,.1);}
#map{height:500px;width:100%;}

/* School cards */
.schools-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:18px;margin-top:20px;}
.school-card{background:white;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.1);border-left:4px solid #0f3460;transition:.2s;position:relative;}
.school-card.no-students{border-left-color:#f59e0b;opacity:.9;}
.school-card:hover{transform:translateY(-3px);box-shadow:0 4px 16px rgba(0,0,0,.15);}
.school-card h3{color:#0f3460;font-size:1rem;margin-bottom:5px;padding-right:32px;}
.school-card .location-tag{color:#4a7aab;font-weight:600;margin-bottom:10px;font-size:.85rem;}
.metric{display:flex;justify-content:space-between;padding:5px 0;border-top:1px solid #f0f0f0;}
.metric-label{color:#666;font-size:.83rem;}
.metric-value{font-weight:700;color:#0f3460;font-size:.83rem;}
.badge-empty{display:inline-block;background:#fef3c7;color:#92400e;border:1px solid #f59e0b;padding:2px 8px;border-radius:10px;font-size:.72rem;font-weight:700;margin-bottom:8px;}
.enroll-num{position:absolute;top:14px;right:14px;background:#0f3460;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;}

/* Sections heading */
.section-heading{color:white;font-size:1.1rem;font-weight:800;margin:20px 0 12px;display:flex;align-items:center;gap:8px;}

/* Footer */
.footer{background:rgba(255,255,255,.92);border-radius:12px;padding:16px;text-align:center;color:#666;font-size:.83rem;margin-top:20px;}
.footer a{color:#0f3460;font-weight:600;}

@media(max-width:768px){.header-top h1{font-size:1.3rem;}#map{height:320px;}.schools-grid{grid-template-columns:1fr;}}
@media print{body{background:white;}.btn-action,.logout-btn{display:none;}#map{height:400px;}}
</style>
</head>
<body>
<div class="container">

    <div class="header">
        <div class="header-top">
            <h1>🗺 MTTI Schools Map</h1>
            <div class="header-actions">
                <span class="role-badge">⭐ Admin View</span>
                <button class="btn-action btn-excel" onclick="exportExcel()">📊 Export Excel</button>
                <button class="btn-action btn-print" onclick="window.print()">🖨 Print / PDF</button>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>

        <?php if ($dbError): ?>
        <div class="alert error">⚠ <?= htmlspecialchars($dbError) ?></div>
        <?php endif; ?>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="number"><?= $total_schools ?: '—' ?></div>
                <div class="label">Active Schools</div>
            </div>
            <div class="stat-item">
                <div class="number"><?= $total_students ? number_format($total_students) : '—' ?></div>
                <div class="label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="number"><?= $total_lessons ? number_format($total_lessons) : '—' ?></div>
                <div class="label">Total Lessons</div>
            </div>
            <div class="stat-item">
                <div class="number"><?= $total_certs ? number_format($total_certs) : '—' ?></div>
                <div class="label">Certificates</div>
            </div>
            <div class="stat-item" style="background:linear-gradient(135deg,#0369a1,#0ea5e9);">
                <div class="number"><?= $global_avg ? number_format($global_avg, 1) . '%' : '—' ?></div>
                <div class="label">Avg Quiz Score</div>
            </div>
            <div class="stat-item" style="background:linear-gradient(135deg,#059669,#10b981);">
                <div class="number"><?= $total_active30 ?: '—' ?></div>
                <div class="label">Active Last 30 Days</div>
            </div>
        </div>
    </div>

    <div class="map-section"><div id="map"></div></div>

    <?php if (count($schools) > 0): ?>
    <div class="section-heading">🏫 All Schools (<?= count($schools) ?>)</div>
    <div class="schools-grid" id="schoolsGrid">
    <?php foreach ($schools as $s):
        $enrolled  = (int)($s['enrolled'] ?? 0);
        $active30  = (int)($s['active_30d'] ?? 0);
        $avgScore  = $s['avg_score'] ? number_format((float)$s['avg_score'], 1) . '%' : '—';
        $certs     = (int)($s['certificates'] ?? 0);
        $subjects  = (int)($s['total_subjects'] ?? 0);
        $lessons   = (int)($s['total_lessons'] ?? 0);
        $completed = (int)($s['lessons_completed'] ?? 0);
        $compRate  = ($lessons > 0) ? round(100 * $completed / max($lessons, 1), 1) : null;
    ?>
    <div class="school-card <?= $enrolled === 0 ? 'no-students' : '' ?>" data-id="<?= $s['id'] ?>">
        <?php if ($enrolled > 0): ?>
        <div class="enroll-num"><?= $enrolled ?></div>
        <?php else: ?>
        <span class="badge-empty">⏳ No enrolments</span>
        <?php endif; ?>
        <h3><?= htmlspecialchars($s['name']) ?></h3>
        <p class="location-tag">📌 <?= htmlspecialchars($s['location']) ?></p>
        <?php
        $glMap = ['pre_primary'=>'Pre-Primary','lower_primary'=>'Lower Primary','upper_primary'=>'Upper Primary',
                  'junior_secondary'=>'Junior Secondary','secondary'=>'Secondary','senior_secondary'=>'Senior Secondary'];
        $glLabel = $glMap[$s['grade_level']] ?? $s['grade_level'];
        if ($glLabel): ?><p style="font-size:.78rem;color:#888;margin-bottom:8px;"><?= htmlspecialchars($glLabel) ?></p><?php endif; ?>
        <div class="metric"><span class="metric-label">Enrolled Students</span><span class="metric-value"><?= $enrolled ?: '—' ?></span></div>
        <div class="metric"><span class="metric-label">Active (last 30 days)</span><span class="metric-value"><?= $active30 ?: '—' ?></span></div>
        <div class="metric"><span class="metric-label">Subjects</span><span class="metric-value"><?= $subjects ?: '—' ?></span></div>
        <div class="metric"><span class="metric-label">Total Lessons</span><span class="metric-value"><?= $lessons ?: '—' ?></span></div>
        <div class="metric"><span class="metric-label">Lesson Completions</span><span class="metric-value"><?= $completed ?: '—' ?></span></div>
        <?php if ($compRate !== null): ?>
        <div class="metric"><span class="metric-label">Completion Rate</span><span class="metric-value"><?= $compRate ?>%</span></div>
        <?php endif; ?>
        <div class="metric"><span class="metric-label">Quiz Avg Score</span><span class="metric-value"><?= $avgScore ?></span></div>
        <div class="metric"><span class="metric-label">Certificates Issued</span><span class="metric-value"><?= $certs ?: '—' ?></span></div>
    </div>
    <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div style="background:rgba(255,255,255,.95);border-radius:12px;padding:40px;text-align:center;margin-top:20px;color:#666;">
        <p style="font-size:1.2rem;">No active schools found.</p>
        <p style="margin-top:8px;font-size:.9rem;">Add schools in the admin panel and set them to <strong>active</strong>.</p>
    </div>
    <?php endif; ?>

    <div class="footer">
        M.T.T.I LMS — Schools Performance Map &nbsp;|&nbsp;
        Generated <?= date('d M Y, H:i') ?> EAT &nbsp;|&nbsp;
        <a href="/mtti-lms/">Back to LMS</a>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ─── DATA ─────────────────────────────────────────────────────────────────────
const schoolsData = <?= $schoolsJson ?>;

// ─── MAP ──────────────────────────────────────────────────────────────────────
const map = L.map('map', { center: [0.5, 35.8], zoom: 6 });
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 18
}).addTo(map);

// Jitter overlapping pins (golden-angle spiral)
function jitterGroups(schools) {
    const groups = {};
    schools.forEach(s => {
        const key = `${parseFloat(s.lat).toFixed(4)}_${parseFloat(s.lng).toFixed(4)}`;
        if (!groups[key]) groups[key] = [];
        groups[key].push(s);
    });
    const result = [];
    Object.values(groups).forEach(group => {
        if (group.length === 1) { result.push({ ...group[0], jlat: +group[0].lat, jlng: +group[0].lng }); return; }
        const golden = 2.39996; // radians
        group.forEach((s, i) => {
            const r = 0.025 * Math.sqrt(i + 1);
            const angle = i * golden;
            result.push({ ...s, jlat: +s.lat + r * Math.sin(angle), jlng: +s.lng + r * Math.cos(angle) });
        });
    });
    return result;
}

const jittered = jitterGroups(schoolsData);
const bounds = [];

jittered.forEach(s => {
    const enrolled = parseInt(s.enrolled) || 0;
    const color = enrolled > 0 ? '#0f3460' : '#f59e0b';
    const size  = enrolled > 20 ? 18 : enrolled > 5 ? 14 : 11;
    const marker = L.circleMarker([s.jlat, s.jlng], {
        radius: size, fillColor: color, color: 'white',
        weight: 2, opacity: 1, fillOpacity: 0.88
    }).addTo(map);

    const avg    = s.avg_score ? `${parseFloat(s.avg_score).toFixed(1)}%` : '—';
    const comp   = parseInt(s.lessons_completed) || 0;
    const tot    = parseInt(s.total_lessons) || 0;
    const compR  = tot > 0 ? `${Math.round(100*comp/tot)}%` : '—';
    const certs  = parseInt(s.certificates) || 0;
    const active = parseInt(s.active_30d) || 0;

    marker.bindPopup(`
        <strong style="color:#0f3460;font-size:.95rem;">${s.name}</strong><br>
        <em style="color:#888;font-size:.8rem;">📌 ${s.location}</em>
        <hr style="margin:6px 0;border-color:#eee;">
        <table style="width:100%;font-size:.82rem;border-collapse:collapse;">
            <tr><td style="color:#555;padding:2px 4px;">👥 Enrolled</td><td style="font-weight:700;padding:2px 4px;">${enrolled || '—'}</td></tr>
            <tr><td style="color:#555;padding:2px 4px;">🟢 Active 30d</td><td style="font-weight:700;padding:2px 4px;">${active || '—'}</td></tr>
            <tr><td style="color:#555;padding:2px 4px;">📚 Lessons</td><td style="font-weight:700;padding:2px 4px;">${tot || '—'}</td></tr>
            <tr><td style="color:#555;padding:2px 4px;">✅ Completion</td><td style="font-weight:700;padding:2px 4px;">${compR}</td></tr>
            <tr><td style="color:#555;padding:2px 4px;">📝 Quiz Avg</td><td style="font-weight:700;padding:2px 4px;">${avg}</td></tr>
            <tr><td style="color:#555;padding:2px 4px;">🎓 Certs</td><td style="font-weight:700;padding:2px 4px;">${certs || '—'}</td></tr>
        </table>
    `, { maxWidth: 220 });

    if (enrolled > 0) marker.on('click', () => {
        const card = document.querySelector(`.school-card[data-id="${s.id}"]`);
        if (card) { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); card.style.boxShadow = '0 0 0 3px #0f3460'; setTimeout(() => card.style.boxShadow = '', 2000); }
    });

    if (+s.lat !== 0 && +s.lng !== 0) bounds.push([s.jlat, s.jlng]);
});

if (bounds.length > 0) {
    if (bounds.length === 1) { map.setView(bounds[0], 10); }
    else { map.fitBounds(bounds, { padding: [40, 40] }); }
} else {
    map.setView([0.5149, 35.2690], 10);
}

// ─── EXCEL EXPORT ─────────────────────────────────────────────────────────────
function exportExcel() {
    const wb = XLSX.utils.book_new();
    const date = new Date().toLocaleDateString('en-KE', { day:'2-digit', month:'short', year:'numeric' }).replace(/ /g,'-');

    const headers = ['School Name','Location','Grade Level','Enrolled Students','Active (30 days)',
        'Subjects','Total Lessons','Lessons Completed','Completion Rate (%)','Quiz Avg Score (%)','Certificates'];
    const rows = schoolsData.map(s => {
        const tot = parseInt(s.total_lessons) || 0;
        const comp = parseInt(s.lessons_completed) || 0;
        const rate = tot > 0 ? Math.round(100 * comp / tot) : '';
        return [
            s.name, s.location, s.grade_level || '',
            parseInt(s.enrolled) || 0,
            parseInt(s.active_30d) || 0,
            parseInt(s.total_subjects) || 0,
            tot, comp, rate,
            s.avg_score ? parseFloat(s.avg_score) : '',
            parseInt(s.certificates) || 0
        ];
    });

    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    ws['!cols'] = [28,28,16,12,14,10,12,14,14,14,12].map(w => ({ wch: w }));
    XLSX.utils.book_append_sheet(wb, ws, 'Schools');

    // Summary sheet
    const totalEnrolled = schoolsData.reduce((a,s) => a + (parseInt(s.enrolled)||0), 0);
    const totalLessons  = schoolsData.reduce((a,s) => a + (parseInt(s.total_lessons)||0), 0);
    const totalComp     = schoolsData.reduce((a,s) => a + (parseInt(s.lessons_completed)||0), 0);
    const avgScores     = schoolsData.filter(s => s.avg_score).map(s => parseFloat(s.avg_score));
    const overallAvg    = avgScores.length ? (avgScores.reduce((a,b)=>a+b,0)/avgScores.length).toFixed(1) : 0;
    const summaryRows = [
        ['MTTI Schools Summary', date],
        [],
        ['Total Active Schools', schoolsData.length],
        ['Total Enrolled Students', totalEnrolled],
        ['Total Lessons', totalLessons],
        ['Total Lessons Completed', totalComp],
        ['Overall Completion Rate', totalLessons > 0 ? Math.round(100*totalComp/totalLessons)+'%' : '—'],
        ['Overall Avg Quiz Score', overallAvg ? overallAvg+'%' : '—'],
    ];
    const ws2 = XLSX.utils.aoa_to_sheet(summaryRows);
    ws2['!cols'] = [{ wch: 28 }, { wch: 20 }];
    XLSX.utils.book_append_sheet(wb, ws2, 'Summary');

    XLSX.writeFile(wb, `MTTI-Schools-Map-${date}.xlsx`);
}
</script>
</body>
</html>
