<?php
/**
 * M.T.T.I LMS — Corporate/Sponsor Dashboard
 * Separate view for corporate partners to see impact metrics
 * Place in: /var/www/html/mtti-lms/corporate.php
 * Access: /corporate.php?token=SPONSOR_TOKEN
 */
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();

// Auth: either admin or valid sponsor token
$sponsorId = null;
$sponsor = null;
$isAdmin = false;

try {
    $auth = new Auth();
    if ($auth->isLoggedIn() && in_array($auth->getRole(), ['admin','school_admin'])) {
        $isAdmin = true;
        $sponsorId = intval($_GET['sponsor_id'] ?? 0);
        if ($sponsorId) $sponsor = $db->fetchOne("SELECT * FROM sponsors WHERE id=?", [$sponsorId]);
    }
} catch(Exception $e) {}

if (!$isAdmin) {
    $token = trim($_GET['token'] ?? '');
    if (!$token) { http_response_code(403); die('<h2>Access denied. Please use your sponsor link.</h2>'); }
    // For now use a simple token check via settings
    $sponsor = $db->fetchOne("SELECT * FROM sponsors WHERE slug=? AND status='active'", [$token]);
    if (!$sponsor) { http_response_code(403); die('<h2>Invalid or expired sponsor link.</h2>'); }
    $sponsorId = $sponsor['id'];
}

// Stats
$totalStudents   = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role='student' AND status='active'") ?? 0;
$activeThisWeek  = $db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM analytics_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)") ?? 0;
$activeThisMonth = $db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM analytics_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?? 0;
$totalLessons    = $db->fetchColumn("SELECT COUNT(*) FROM lessons WHERE status='published'") ?? 0;
$totalCompletions= $db->fetchColumn("SELECT COUNT(*) FROM completions") ?? 0;

// Students by county
$byCounty = $db->fetchAll("SELECT county, COUNT(*) as cnt FROM users WHERE role='student' AND county IS NOT NULL AND county != '' GROUP BY county ORDER BY cnt DESC LIMIT 10") ?? [];

// Students by pathway
$byPathway = $db->fetchAll("SELECT pathway, COUNT(*) as cnt FROM users WHERE role='student' GROUP BY pathway") ?? [];

// Popular subjects
$popularSubjects = $db->fetchAll("SELECT s.title, COUNT(c.id) as completions FROM subjects s LEFT JOIN completions c ON c.subject_id=s.id WHERE s.level_type='subject' GROUP BY s.id ORDER BY completions DESC LIMIT 8") ?? [];

// Registrations over time (last 30 days)
$regOverTime = $db->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as cnt FROM users WHERE role='student' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day") ?? [];

// Sponsor-specific stats
$sponsorClicks = 0; $sponsorImpressions = 0;
if ($sponsor) {
    $sponsorClicks      = $sponsor['clicks'] ?? 0;
    $sponsorImpressions = $sponsor['impressions'] ?? 0;
}

// Career postings
$careerPostings = [];
if ($sponsor) {
    $careerPostings = $db->fetchAll("SELECT * FROM career_postings WHERE sponsor_id=? ORDER BY created_at DESC LIMIT 10", [$sponsor['id']]) ?? [];
}

$pageTitle = ($sponsor ? htmlspecialchars($sponsor['name']) . ' — ' : '') . 'Corporate Dashboard | M.T.T.I';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
:root{--pri:#1e3a5f;--acc:#f59e0b;--g:#16a34a;--r:#dc2626;--bg:#f0f4f8}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:#1e293b}
.topbar{background:linear-gradient(135deg,#0f2644,var(--pri));color:#fff;padding:16px 24px;display:flex;align-items:center;justify-content:space-between}
.topbar h1{font-size:1.1rem;font-weight:800}
.topbar .sub{font-size:.78rem;opacity:.7;margin-top:2px}
.logo{font-weight:900;font-size:1.3rem;letter-spacing:-1px}
.logo span{color:var(--acc)}
.container{max-width:1200px;margin:0 auto;padding:24px 16px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.stat-card .val{font-size:2rem;font-weight:800;color:var(--pri)}
.stat-card .lbl{font-size:.8rem;color:#64748b;margin-top:4px}
.stat-card .delta{font-size:.75rem;color:var(--g);margin-top:6px;font-weight:600}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
.grid3{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:24px}
.card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.card h3{font-size:.95rem;font-weight:700;color:var(--pri);margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #f1f5f9}
.bar-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.bar-row .name{font-size:.82rem;min-width:120px;color:#475569}
.bar-row .bar{flex:1;height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden}
.bar-row .fill{height:100%;background:linear-gradient(90deg,var(--pri),var(--acc));border-radius:4px;transition:width .8s}
.bar-row .cnt{font-size:.78rem;color:#94a3b8;min-width:30px;text-align:right}
.impact-box{background:linear-gradient(135deg,var(--pri),#2d5f9f);color:#fff;border-radius:12px;padding:24px;text-align:center}
.impact-box h2{font-size:2.5rem;font-weight:900;color:var(--acc)}
.impact-box p{opacity:.85;font-size:.88rem;margin-top:8px}
.career-card{border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:8px}
.career-card h4{font-size:.88rem;font-weight:700;color:var(--pri)}
.career-card .meta{font-size:.75rem;color:#94a3b8;margin-top:4px}
.btn{padding:8px 16px;border-radius:8px;font-size:.82rem;font-weight:600;border:none;cursor:pointer;text-decoration:none;display:inline-block}
.btn-primary{background:var(--pri);color:#fff}.btn-primary:hover{background:#163256}
.btn-success{background:var(--g);color:#fff}
.pathway-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9}
.pathway-item:last-child{border:none}
.pathway-badge{padding:3px 10px;border-radius:10px;font-size:.7rem;font-weight:700}
.badge-stem{background:#dbeafe;color:#1d4ed8}
.badge-arts{background:#fce7f3;color:#9d174d}
.badge-social{background:#dcfce7;color:#166534}
.badge-general{background:#f1f5f9;color:#475569}
.sponsor-highlight{background:linear-gradient(135deg,#fffbeb,#fef3c7);border:2px solid var(--acc);border-radius:12px;padding:20px;margin-bottom:24px}
.sponsor-highlight h3{color:#92400e}
@media(max-width:768px){.grid2,.grid3{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="topbar">
    <div>
        <div class="logo">M.T.T.I<span>.</span>Learn</div>
        <div class="sub"><?= $sponsor ? 'Partner Dashboard — ' . htmlspecialchars($sponsor['name']) : 'Corporate Dashboard' ?></div>
    </div>
    <div style="text-align:right">
        <div style="font-size:.78rem;opacity:.7">Powered by</div>
        <div style="font-weight:700">Masomotele Technical Training Institute</div>
    </div>
</div>

<div class="container">

    <?php if ($sponsor): ?>
    <div class="sponsor-highlight">
        <h3>👋 Welcome, <?= htmlspecialchars($sponsor['name']) ?></h3>
        <p style="font-size:.88rem;color:#78350f;margin-top:6px">Here's the impact your partnership is creating for Kenyan students. Last updated: <?= date('d M Y H:i') ?></p>
        <div style="display:flex;gap:20px;margin-top:14px;flex-wrap:wrap">
            <div><div style="font-size:1.5rem;font-weight:800;color:#92400e"><?= number_format($sponsorImpressions) ?></div><div style="font-size:.75rem;color:#78350f">Times your brand was seen</div></div>
            <div><div style="font-size:1.5rem;font-weight:800;color:#92400e"><?= number_format($sponsorClicks) ?></div><div style="font-size:.75rem;color:#78350f">Students clicked your link</div></div>
            <div><div style="font-size:1.5rem;font-weight:800;color:#92400e"><?= count($careerPostings) ?></div><div style="font-size:.75rem;color:#78350f">Active career postings</div></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="val"><?= number_format($totalStudents) ?></div>
            <div class="lbl">Registered Students</div>
            <div class="delta">↑ Growing daily</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= number_format($activeThisWeek) ?></div>
            <div class="lbl">Active This Week</div>
            <div class="delta">Last 7 days</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= number_format($activeThisMonth) ?></div>
            <div class="lbl">Active This Month</div>
            <div class="delta">Last 30 days</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= number_format($totalLessons) ?></div>
            <div class="lbl">Lessons Available</div>
            <div class="delta">All CBC-aligned</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= number_format($totalCompletions) ?></div>
            <div class="lbl">Lessons Completed</div>
            <div class="delta">Total completions</div>
        </div>
        <div class="stat-card" style="background:linear-gradient(135deg,var(--pri),#2d6a9f);color:#fff">
            <div class="val" style="color:var(--acc)"><?= count($byCounty) ?>+</div>
            <div class="lbl" style="color:rgba(255,255,255,.8)">Counties Represented</div>
            <div class="delta" style="color:#90cdf4">Nationwide reach</div>
        </div>
    </div>

    <div class="grid3">
        <!-- Registration trend -->
        <div class="card">
            <h3>📈 Student Registrations — Last 30 Days</h3>
            <canvas id="regChart" height="120"></canvas>
        </div>
        <!-- Pathway breakdown -->
        <div class="card">
            <h3>🎯 Learning Pathways</h3>
            <?php
            $pathwayLabels = ['stem'=>'STEM 🔬','arts_sports'=>'Arts & Sports 🎨','social_sciences'=>'Social Sciences 🌍','general'=>'General 📚'];
            $pathwayClasses = ['stem'=>'badge-stem','arts_sports'=>'badge-arts','social_sciences'=>'badge-social','general'=>'badge-general'];
            $totalP = array_sum(array_column($byPathway, 'cnt'));
            foreach ($byPathway as $p):
                $pct = $totalP > 0 ? round($p['cnt']/$totalP*100) : 0;
                $label = $pathwayLabels[$p['pathway']] ?? $p['pathway'];
                $cls = $pathwayClasses[$p['pathway']] ?? 'badge-general';
            ?>
            <div class="pathway-item">
                <span class="pathway-badge <?= $cls ?>"><?= $label ?></span>
                <div style="flex:1;height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden">
                    <div style="height:100%;width:<?= $pct ?>%;background:var(--pri);border-radius:3px"></div>
                </div>
                <span style="font-size:.78rem;color:#64748b;min-width:50px;text-align:right"><?= $p['cnt'] ?> (<?= $pct ?>%)</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid2">
        <!-- Top counties -->
        <div class="card">
            <h3>🗺️ Top Counties</h3>
            <?php
            $maxCnt = !empty($byCounty) ? max(array_column($byCounty, 'cnt')) : 1;
            foreach ($byCounty as $row):
                $w = round($row['cnt']/$maxCnt*100);
            ?>
            <div class="bar-row">
                <span class="name"><?= htmlspecialchars($row['county']) ?></span>
                <div class="bar"><div class="fill" style="width:<?= $w ?>%"></div></div>
                <span class="cnt"><?= $row['cnt'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Popular subjects -->
        <div class="card">
            <h3>📚 Popular Subjects</h3>
            <?php
            $maxS = !empty($popularSubjects) ? max(array_column($popularSubjects, 'completions')) : 1;
            $maxS = max($maxS, 1);
            foreach ($popularSubjects as $row):
                $w = round($row['completions']/$maxS*100);
            ?>
            <div class="bar-row">
                <span class="name"><?= htmlspecialchars($row['title']) ?></span>
                <div class="bar"><div class="fill" style="width:<?= max($w,5) ?>%"></div></div>
                <span class="cnt"><?= $row['completions'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Impact statement -->
    <div class="impact-box" style="margin-bottom:24px">
        <h2><?= number_format($totalStudents) ?></h2>
        <p>Kenyan students are accessing quality education through M.T.T.I Learn — completely free.<br>
        Your partnership puts your brand in front of the next generation of professionals, customers and decision-makers.</p>
        <?php if (!$sponsor): ?>
        <a href="mailto:info@masomoteletraining.co.ke?subject=Corporate Partnership Enquiry" class="btn btn-success" style="margin-top:16px">📧 Become a Partner</a>
        <?php endif; ?>
    </div>

    <!-- Career Postings -->
    <?php if ($sponsor): ?>
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <h3 style="margin:0;padding:0;border:none">💼 Your Career Postings</h3>
            <a href="<?= SITE_URL ?>/career-post.php?sponsor=<?= $sponsor['slug'] ?>" class="btn btn-primary">+ New Posting</a>
        </div>
        <?php if (empty($careerPostings)): ?>
        <p style="color:#94a3b8;font-size:.88rem;text-align:center;padding:20px">No career postings yet. Post internships, jobs or scholarships to reach students directly.</p>
        <?php else: ?>
        <?php foreach ($careerPostings as $p): ?>
        <div class="career-card">
            <h4><?= htmlspecialchars($p['title']) ?></h4>
            <div class="meta">
                <?= ucfirst($p['type']) ?> · <?= htmlspecialchars($p['location'] ?? '') ?>
                <?php if ($p['deadline']): ?> · Deadline: <?= date('d M Y', strtotime($p['deadline'])) ?><?php endif; ?>
                · <strong><?= $p['applications'] ?> applications</strong> · <?= $p['views'] ?> views
                · <span style="color:<?= $p['status']==='active' ? '#16a34a' : '#dc2626' ?>"><?= ucfirst($p['status']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Admin: all sponsors -->
    <?php if ($isAdmin && !$sponsorId): ?>
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <h3 style="margin:0;padding:0;border:none">🤝 All Corporate Partners</h3>
            <a href="<?= SITE_URL ?>/admin/sponsors.php" class="btn btn-primary">Manage Sponsors</a>
        </div>
        <?php
        $allSponsors = $db->fetchAll("SELECT * FROM sponsors ORDER BY FIELD(package,'platinum','gold','silver','bronze'), name") ?? [];
        foreach ($allSponsors as $s):
        ?>
        <div class="career-card">
            <h4><?= htmlspecialchars($s['name']) ?> <span class="pathway-badge badge-stem" style="font-size:.65rem"><?= strtoupper($s['package']) ?></span></h4>
            <div class="meta">
                <?= ucfirst($s['category']) ?> · <?= $s['impressions'] ?> impressions · <?= $s['clicks'] ?> clicks
                · <a href="?sponsor_id=<?= $s['id'] ?>" style="color:var(--pri)">View dashboard</a>
                · <a href="<?= SITE_URL ?>/corporate.php?token=<?= $s['slug'] ?>" style="color:#888">Partner link</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script>
const regData = <?= json_encode($regOverTime) ?>;
const labels = regData.map(r => r.day);
const data = regData.map(r => r.cnt);
new Chart(document.getElementById('regChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'New students',
            data: data,
            borderColor: '#1e3a5f',
            backgroundColor: 'rgba(30,58,95,.08)',
            tension: 0.4,
            fill: true,
            pointRadius: 3,
            pointBackgroundColor: '#f59e0b'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
            x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 10 } } }
        }
    }
});
</script>
</body>
</html>
