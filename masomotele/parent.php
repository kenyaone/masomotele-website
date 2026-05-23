<?php
/**
 * Parent Portal — read-only view of child progress
 * Access: /parent.php?token=PARENT_TOKEN
 * Place in: /var/www/html/mtti-lms/parent.php
 */
require_once __DIR__ . "/includes/init.php";
$db = Database::getInstance();
$token = trim($_GET["token"] ?? "");
if (!$token) { http_response_code(403); die(renderError("No access token. Please use the link sent to you by your child.")); }
$access = $db->fetchOne("SELECT * FROM parent_access WHERE access_token=?", [$token]);
if (!$access) { http_response_code(403); die(renderError("Invalid or expired link. Ask your child to share their progress link again.")); }
$student = $db->fetchOne("SELECT id,name,email,phone,county,school,pathway,created_at FROM users WHERE id=?", [$access["student_id"]]);
if (!$student) { die(renderError("Student account not found.")); }
// Update last accessed
$db->query("UPDATE parent_access SET last_accessed=NOW() WHERE id=?", [$access["id"]]);
$sid = $student["id"];
// Completions by subject
$completions = $db->fetchAll("SELECT s.title as subject, COUNT(c.id) as done,
    (SELECT COUNT(*) FROM lessons l WHERE l.subject_id=s.id AND l.status=\"published\") as total
    FROM subjects s LEFT JOIN completions c ON c.subject_id=s.id AND c.user_id=?
    WHERE s.level_type=\"subject\" GROUP BY s.id ORDER BY done DESC LIMIT 10", [$sid]) ?? [];
$totalLessons = $db->fetchColumn("SELECT COUNT(*) FROM lessons WHERE status=\"published\"") ?? 0;
$totalDone = $db->fetchColumn("SELECT COUNT(*) FROM completions WHERE user_id=?", [$sid]) ?? 0;
$certificates = $db->fetchAll("SELECT cert.*, cl.title as class_title FROM certificates cert JOIN classes cl ON cl.id=cert.class_id WHERE cert.user_id=? ORDER BY cert.issued_at DESC LIMIT 5", [$sid]) ?? [];
$recentActivity = $db->fetchAll("SELECT l.title, c.completed_at FROM completions c JOIN lessons l ON l.id=c.lesson_id WHERE c.user_id=? ORDER BY c.completed_at DESC LIMIT 10", [$sid]) ?? [];
function renderError($msg) { return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Error</title></head><body style=\"font-family:sans-serif;padding:40px;text-align:center\"><h2>⚠️ $msg</h2></body></html>"; }
$pct = $totalLessons > 0 ? round($totalDone/$totalLessons*100) : 0;
$pathway_labels = ["stem"=>"STEM 🔬","arts_sports"=>"Arts & Sports 🎨","social_sciences"=>"Social Sciences 🌍","general"=>"General 📚"];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Progress Report — <?= htmlspecialchars($student["name"]) ?> | M.T.T.I Learn</title>
<style>
:root{--g:#0a5e2a;--acc:#f5a623}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Segoe UI",system-ui,sans-serif;background:#f4f7f5;color:#1a1a1a}
.topbar{background:linear-gradient(135deg,#0a3d20,var(--g));color:#fff;padding:16px 20px;text-align:center}
.brand{font-weight:900;font-size:1.3rem;letter-spacing:-1px}.brand span{color:var(--acc)}
.topbar p{font-size:.8rem;opacity:.75;margin-top:4px}
.container{max-width:640px;margin:0 auto;padding:20px 16px}
.student-card{background:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);display:flex;align-items:center;gap:16px}
.avatar{width:60px;height:60px;border-radius:50%;background:var(--g);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:800;flex-shrink:0}
.student-name{font-size:1.1rem;font-weight:700;color:#1a1a1a}
.student-meta{font-size:.8rem;color:#666;margin-top:3px}
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px}
.stat{background:#fff;border-radius:10px;padding:14px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.06)}
.stat .val{font-size:1.5rem;font-weight:800;color:var(--g)}
.stat .lbl{font-size:.72rem;color:#666;margin-top:3px}
.card{background:#fff;border-radius:12px;padding:18px;margin-bottom:16px;box-shadow:0 2px 6px rgba(0,0,0,.06)}
.card h3{font-size:.92rem;font-weight:700;color:var(--g);margin-bottom:14px;border-bottom:2px solid var(--acc);padding-bottom:8px}
.prog-row{margin-bottom:10px}
.prog-row .label{display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:4px}
.prog-bar{height:8px;background:#e8f5e9;border-radius:4px;overflow:hidden}
.prog-fill{height:100%;background:linear-gradient(90deg,var(--g),#34a853);border-radius:4px;transition:width .8s}
.activity-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:.82rem}
.activity-item:last-child{border:none}
.cert-item{display:flex;align-items:center;gap:10px;padding:10px;background:#f0f7f2;border-radius:8px;margin-bottom:6px}
.cert-item .icon{font-size:1.5rem}
.cert-item .title{font-size:.85rem;font-weight:600;color:var(--g)}
.cert-item .date{font-size:.72rem;color:#888}
.footer{text-align:center;padding:20px;font-size:.75rem;color:#999}
.overall-prog{background:linear-gradient(135deg,var(--g),#0d7a38);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;text-align:center}
.overall-prog .big{font-size:2.5rem;font-weight:900;color:var(--acc)}
.overall-prog .circle{width:80px;height:80px;border-radius:50%;border:6px solid var(--acc);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.4rem;font-weight:900;color:#fff}
</style>
</head>
<body>
<div class="topbar">
  <div class="brand">M.T.T.I<span>.</span>Learn</div>
  <p>Student Progress Report</p>
</div>
<div class="container">
  <div class="student-card">
    <div class="avatar"><?= strtoupper(substr($student["name"],0,1)) ?></div>
    <div>
      <div class="student-name"><?= htmlspecialchars($student["name"]) ?></div>
      <div class="student-meta">
        <?php if ($student["school"]): ?>📍 <?= htmlspecialchars($student["school"]) ?><?php endif; ?>
        <?php if ($student["county"]): ?> · <?= htmlspecialchars($student["county"]) ?> County<?php endif; ?><br>
        Pathway: <?= $pathway_labels[$student["pathway"]] ?? "General" ?> · Joined: <?= date("d M Y", strtotime($student["created_at"])) ?>
      </div>
    </div>
  </div>
  <div class="overall-prog">
    <div class="circle"><?= $pct ?>%</div>
    <p style="font-size:.85rem;opacity:.9">Overall course completion</p>
    <p style="font-size:.75rem;opacity:.7;margin-top:4px"><?= $totalDone ?> of <?= $totalLessons ?> lessons completed</p>
  </div>
  <div class="stats-row">
    <div class="stat"><div class="val"><?= $totalDone ?></div><div class="lbl">Lessons Done</div></div>
    <div class="stat"><div class="val"><?= count($certificates) ?></div><div class="lbl">Certificates</div></div>
    <div class="stat"><div class="val"><?= $pct ?>%</div><div class="lbl">Progress</div></div>
  </div>
  <?php if (!empty($completions)): ?>
  <div class="card">
    <h3>📚 Progress by Subject</h3>
    <?php foreach ($completions as $row):
      $subPct = $row["total"] > 0 ? round($row["done"]/$row["total"]*100) : 0;
    ?>
    <div class="prog-row">
      <div class="label">
        <span><?= htmlspecialchars($row["subject"]) ?></span>
        <span style="color:<?= $subPct >= 60 ? "var(--g)" : "#f59e0b" ?>"><?= $row["done"] ?>/<?= $row["total"] ?> (<?= $subPct ?>%)</span>
      </div>
      <div class="prog-bar"><div class="prog-fill" style="width:<?= $subPct ?>%"></div></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($certificates)): ?>
  <div class="card">
    <h3>🏆 Certificates Earned</h3>
    <?php foreach ($certificates as $c): ?>
    <div class="cert-item">
      <span class="icon">🎓</span>
      <div>
        <div class="title"><?= htmlspecialchars($c["class_title"]) ?></div>
        <div class="date">Issued <?= date("d M Y", strtotime($c["issued_at"])) ?> · Grade: <?= $c["grade"] ?>%</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($recentActivity)): ?>
  <div class="card">
    <h3>⚡ Recent Activity</h3>
    <?php foreach ($recentActivity as $a): ?>
    <div class="activity-item">
      <span>✓</span>
      <span style="flex:1"><?= htmlspecialchars($a["title"]) ?></span>
      <span style="color:#999;font-size:.72rem"><?= date("d M", strtotime($a["completed_at"])) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="footer">
    M.T.T.I Learning Management System · <?= date("d M Y H:i") ?><br>
    Report for: <?= htmlspecialchars($student["name"]) ?>
  </div>
</div>
</body></html>
