<?php
/**
 * M.T.T.I LMS — Admin Analytics Dashboard
 * Place in: /var/www/html/mtti-lms/analytics-dashboard.php
 */
require_once __DIR__ . "/includes/init.php";
$auth = new Auth(); $auth->requireLogin();
if (!in_array($auth->getRole(), ["admin","school_admin"])) { header("Location: " . SITE_URL . "/dashboard.php"); exit; }
$db = Database::getInstance();

// Core stats
$stats = [
    "total_students"   => $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role=\"student\"") ?? 0,
    "active_today"     => $db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM completions WHERE DATE(completed_at)=CURDATE()") ?? 0,
    "active_week"      => $db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM completions WHERE completed_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)") ?? 0,
    "active_month"     => $db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM completions WHERE completed_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)") ?? 0,
    "total_lessons"    => $db->fetchColumn("SELECT COUNT(*) FROM lessons WHERE status=\"published\"") ?? 0,
    "total_homework"   => $db->fetchColumn("SELECT COUNT(*) FROM lessons WHERE content_type=\"homework\"") ?? 0,
    "total_completions"=> $db->fetchColumn("SELECT COUNT(*) FROM completions") ?? 0,
    "total_papers"     => $db->fetchColumn("SELECT COUNT(*) FROM past_papers WHERE status=\"active\"") ?? 0,
    "new_this_week"    => $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role=\"student\" AND created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)") ?? 0,
];

$byCounty      = $db->fetchAll("SELECT county, COUNT(*) as cnt FROM users WHERE role=\"student\" AND county IS NOT NULL AND county != \"\" GROUP BY county ORDER BY cnt DESC LIMIT 10") ?? [];
$byPathway     = $db->fetchAll("SELECT pathway, COUNT(*) as cnt FROM users WHERE role=\"student\" GROUP BY pathway ORDER BY cnt DESC") ?? [];
$bySubject     = $db->fetchAll("SELECT s.title, COUNT(c.id) as cnt FROM subjects s LEFT JOIN completions c ON c.subject_id=s.id WHERE s.level_type=\"subject\" GROUP BY s.id ORDER BY cnt DESC LIMIT 8") ?? [];
$topStudents   = $db->fetchAll("SELECT u.name, u.school, u.county, COUNT(c.id) as cnt FROM users u LEFT JOIN completions c ON c.user_id=u.id WHERE u.role=\"student\" GROUP BY u.id ORDER BY cnt DESC LIMIT 10") ?? [];
$regTrend      = $db->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as cnt FROM users WHERE role=\"student\" AND created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day") ?? [];
$completeTrend = $db->fetchAll("SELECT DATE(completed_at) as day, COUNT(*) as cnt FROM completions WHERE completed_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(completed_at) ORDER BY day") ?? [];
$sponsorStats  = $db->fetchAll("SELECT name, package, impressions, clicks, status FROM sponsors ORDER BY FIELD(package,\"platinum\",\"gold\",\"silver\",\"bronze\")") ?? [];

require_once __DIR__ . "/templates/header.php";
?>
<style>
.an-hdr{background:linear-gradient(135deg,#0f2644,#1e3a5f);color:#fff;padding:20px;border-radius:0 0 14px 14px;margin:-16px -16px 20px}
.an-hdr h2{font-size:1.2rem;font-weight:800}
.sg{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:20px}
.sc{background:#fff;border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.sc .v{font-size:1.8rem;font-weight:800;color:#1e3a5f}
.sc .l{font-size:.75rem;color:#64748b;margin-top:3px}
.sc .d{font-size:.72rem;color:#16a34a;margin-top:5px;font-weight:600}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
.g3{display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:20px}
.card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 6px rgba(0,0,0,.06)}
.card h3{font-size:.88rem;font-weight:700;color:#1e3a5f;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #f1f5f9}
.br{display:flex;align-items:center;gap:8px;margin-bottom:7px}
.br .n{font-size:.8rem;min-width:110px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.br .b{flex:1;height:7px;background:#f1f5f9;border-radius:4px;overflow:hidden}
.br .f{height:100%;background:linear-gradient(90deg,#1e3a5f,#f59e0b);border-radius:4px}
.br .c{font-size:.72rem;color:#94a3b8;min-width:28px;text-align:right}
.top-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f8f9fa;font-size:.82rem}
.top-row:last-child{border:none}
.top-rank{width:22px;height:22px;border-radius:50%;background:#1e3a5f;color:#fff;font-size:.68rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.top-rank.gold{background:#f59e0b}.top-rank.silver{background:#94a3b8}.top-rank.bronze{background:#c2844b}
@media(max-width:768px){.g2,.g3{grid-template-columns:1fr}}
</style>
<div class="an-hdr">
  <a href="<?= SITE_URL ?>/dashboard.php" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.8rem">← Dashboard</a>
  <h2 style="margin-top:6px">📊 Analytics Dashboard</h2>
  <p style="font-size:.8rem;opacity:.7">Live platform metrics · Last updated: <?= date("d M Y H:i") ?></p>
</div>

<div class="sg">
  <div class="sc"><div class="v"><?= number_format($stats["total_students"]) ?></div><div class="l">Total Students</div><div class="d">+<?= $stats["new_this_week"] ?> this week</div></div>
  <div class="sc"><div class="v"><?= $stats["active_today"] ?></div><div class="l">Active Today</div></div>
  <div class="sc"><div class="v"><?= $stats["active_week"] ?></div><div class="l">Active This Week</div></div>
  <div class="sc"><div class="v"><?= $stats["active_month"] ?></div><div class="l">Active This Month</div></div>
  <div class="sc"><div class="v"><?= number_format($stats["total_completions"]) ?></div><div class="l">Total Completions</div></div>
  <div class="sc"><div class="v"><?= $stats["total_lessons"] ?></div><div class="l">Published Lessons</div></div>
  <div class="sc"><div class="v"><?= $stats["total_homework"] ?></div><div class="l">Homework PDFs</div></div>
  <div class="sc"><div class="v"><?= $stats["total_papers"] ?></div><div class="l">Past Papers</div></div>
</div>

<div class="g3">
  <div class="card">
    <h3>📈 Registrations (Last 30 Days)</h3>
    <canvas id="regChart" height="100"></canvas>
  </div>
  <div class="card">
    <h3>🎯 Pathways</h3>
    <?php $pl=["stem"=>"STEM 🔬","arts_sports"=>"Arts 🎨","social_sciences"=>"Social 🌍","general"=>"General 📚"];
    $tp=max(1,array_sum(array_column($byPathway,"cnt")));
    foreach ($byPathway as $r): $p=round($r["cnt"]/$tp*100); ?>
    <div class="br">
      <span class="n"><?= $pl[$r["pathway"]] ?? $r["pathway"] ?></span>
      <div class="b"><div class="f" style="width:<?= $p ?>%"></div></div>
      <span class="c"><?= $r["cnt"] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="g2">
  <div class="card">
    <h3>🗺️ Top Counties</h3>
    <?php $mx=max(1,...array_column($byCounty,"cnt")?:[1]);
    foreach ($byCounty as $r): ?>
    <div class="br">
      <span class="n"><?= htmlspecialchars($r["county"]) ?></span>
      <div class="b"><div class="f" style="width:<?= round($r["cnt"]/$mx*100) ?>%"></div></div>
      <span class="c"><?= $r["cnt"] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <h3>📚 Top Subjects</h3>
    <?php $ms=max(1,...array_column($bySubject,"cnt")?:[1]);
    foreach ($bySubject as $r): ?>
    <div class="br">
      <span class="n"><?= htmlspecialchars($r["title"]) ?></span>
      <div class="b"><div class="f" style="width:<?= max(3,round($r["cnt"]/$ms*100)) ?>%"></div></div>
      <span class="c"><?= $r["cnt"] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="g2">
  <div class="card">
    <h3>🏆 Top Students</h3>
    <?php foreach ($topStudents as $i => $s):
      $rc = $i===0?"gold":($i===1?"silver":($i===2?"bronze":""));
    ?>
    <div class="top-row">
      <div class="top-rank <?= $rc ?>"><?= $i+1 ?></div>
      <div style="flex:1">
        <div style="font-weight:600"><?= htmlspecialchars($s["name"]) ?></div>
        <div style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars($s["school"] ?? "") ?><?= $s["county"] ? " · ".$s["county"] : "" ?></div>
      </div>
      <div style="font-size:.82rem;font-weight:700;color:#1e3a5f"><?= $s["cnt"] ?> ✓</div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($sponsorStats)): ?>
  <div class="card">
    <h3>🤝 Sponsor Performance</h3>
    <?php foreach ($sponsorStats as $s): ?>
    <div class="top-row">
      <div style="flex:1">
        <div style="font-weight:600;font-size:.82rem"><?= htmlspecialchars($s["name"]) ?></div>
        <div style="font-size:.72rem;color:#94a3b8"><?= strtoupper($s["package"]) ?> · <?= $s["status"] ?></div>
      </div>
      <div style="text-align:right;font-size:.75rem;color:#475569">
        <?= number_format($s["impressions"]) ?> views<br>
        <?= $s["clicks"] ?> clicks
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="card">
    <h3>🤝 Corporate Partners</h3>
    <p style="color:#94a3b8;font-size:.85rem;text-align:center;padding:20px">No sponsors yet.<br><a href="<?= SITE_URL ?>/admin/sponsors.php" style="color:#1e3a5f">Add your first sponsor →</a></p>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const rd = <?= json_encode($regTrend) ?>;
new Chart(document.getElementById("regChart"), {
  type: "bar",
  data: {
    labels: rd.map(r => r.day),
    datasets: [{
      label: "New students",
      data: rd.map(r => r.cnt),
      backgroundColor: "rgba(30,58,95,.7)",
      borderRadius: 4
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: "#f8f9fa" } },
      x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 9 } } }
    }
  }
});
</script>
<?php require_once __DIR__ . "/templates/footer.php"; ?>
