<?php
require_once __DIR__ . "/includes/init.php";
$auth = new Auth();
if ($auth->isLoggedIn()) { header("Location: " . SITE_URL . "/dashboard.php"); exit; }
$db = Database::getInstance();
$error = ""; $success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = trim($_POST["name"] ?? "");
    $email   = trim($_POST["email"] ?? "");
    $phone   = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";
    $county  = trim($_POST["county"] ?? "");
    $school  = trim($_POST["school"] ?? "");
    $pathway = $_POST["pathway"] ?? "general";
    $parentName  = trim($_POST["parent_name"] ?? "");
    $parentPhone = trim($_POST["parent_phone"] ?? "");

    if (!$name || !$phone || !$password) { $error = "Name, phone and password are required."; }
    elseif ($password !== $confirm)        { $error = "Passwords do not match."; }
    elseif (strlen($password) < 6)         { $error = "Password must be at least 6 characters."; }
    else {
        if (!$email) $email = strtolower(preg_replace("/\s+/",".", $name)) . rand(100,999) . "@mtti.learn";
        $result = $auth->register($name, $email, $phone, $password);
        if ($result["success"]) {
            $uid = $result["user_id"];
            $db->query("UPDATE lms_users SET county=?,school=?,pathway=?,parent_name=?,parent_phone=? WHERE id=?",
                [$county,$school,$pathway,$parentName,$parentPhone,$uid]);
            $selectedClass = (int)($_POST['class_id'] ?? 0);
            if ($selectedClass) {
                $db->query("INSERT IGNORE INTO lms_enrolments (user_id,class_id,enrolled_at,pathway) VALUES (?,?,NOW(),?)",
                    [$uid,$selectedClass,$pathway]);
            }
            try { $db->query("INSERT INTO analytics_events (user_id,event_type,meta,created_at) VALUES (?,\"registration\",?,NOW())",
                [$uid, json_encode(["county"=>$county,"school"=>$school,"pathway"=>$pathway])]); } catch(Exception $e) {}
            $success = $name;
        } else { $error = $result["message"]; }
    }
}
$allClasses = $db->fetchAll("SELECT id,title FROM lms_classes WHERE status='active' ORDER BY title");
$totalStudents = $db->fetchColumn("SELECT COUNT(*) FROM lms_users WHERE role=\"student\"") ?? 0;
$totalLessons  = $db->fetchColumn("SELECT COUNT(*) FROM lms_lessons WHERE status=\"published\"") ?? 0;
$sponsors = [];
try { $sponsors = $db->fetchAll("SELECT name,tagline FROM lms_sponsors WHERE status=\"active\" AND show_on_dashboard=1 LIMIT 5") ?? []; } catch(Exception $e) {}
$counties = ["Baringo","Bomet","Bungoma","Busia","Elgeyo-Marakwet","Embu","Garissa","Homa Bay","Isiolo","Kajiado","Kakamega","Kericho","Kiambu","Kilifi","Kirinyaga","Kisii","Kisumu","Kitui","Kwale","Laikipia","Lamu","Machakos","Makueni","Mandera","Marsabit","Meru","Migori","Mombasa","Murang\"a","Nairobi","Nakuru","Nandi","Narok","Nyamira","Nyandarua","Nyeri","Samburu","Siaya","Taita-Taveta","Tana River","Tharaka-Nithi","Trans Nzoia","Turkana","Uasin Gishu","Vihiga","Wajir","West Pokot"];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Join Free — M.T.T.I Learn | Kenya\'s CBC Learning Platform</title>
<meta name="description" content="Free CBC-aligned learning for all Kenyan students. Grade 10-12 interactive lessons, past papers, homework worksheets.">
<link rel="manifest" href="<?= SITE_URL ?>/manifest.json">
<style>
:root{--g:#0a5e2a;--gl:#0d7a38;--acc:#f5a623;--bg:#f4f7f5}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Segoe UI",system-ui,sans-serif;background:var(--bg);min-height:100vh}
.hero{background:linear-gradient(135deg,#062015 0%,var(--g) 60%,var(--gl) 100%);color:#fff;padding:36px 20px 56px;text-align:center}
.brand{font-size:2rem;font-weight:900;letter-spacing:-1px}.brand span{color:var(--acc)}
.hero h1{font-size:1.5rem;font-weight:800;margin:10px 0 8px;line-height:1.3}
.hero p{opacity:.85;font-size:.92rem;max-width:420px;margin:0 auto}
.badges{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:14px}
.badge{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);padding:4px 12px;border-radius:20px;font-size:.76rem;font-weight:600}
.trust{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;max-width:480px;margin:-28px auto 0;padding:0 16px;position:relative;z-index:1}
.trust-item{background:#fff;border-radius:10px;padding:12px 8px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,.1)}
.trust-item .num{font-size:1.4rem;font-weight:800;color:var(--g)}
.trust-item .lbl{font-size:.68rem;color:#666;margin-top:2px}
.card{background:#fff;border-radius:14px;padding:24px;max-width:500px;margin:24px auto;box-shadow:0 4px 20px rgba(0,0,0,.08);position:relative}
.section-title{font-size:1rem;font-weight:700;color:var(--g);margin-bottom:18px;padding-bottom:8px;border-bottom:2px solid var(--acc)}
.fg{margin-bottom:14px}
.fg label{display:block;font-size:.82rem;font-weight:600;color:#333;margin-bottom:4px}
.fg label .req{color:#c62828}
.fc{width:100%;padding:10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:.9rem;font-family:inherit;transition:border .2s}
.fc:focus{outline:none;border-color:var(--g);box-shadow:0 0 0 3px rgba(10,94,42,.07)}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.pathway-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px}
.pw{border:2px solid #e0e0e0;border-radius:10px;padding:12px 8px;cursor:pointer;text-align:center;transition:all .2s}
.pw:hover{border-color:var(--g)}.pw.sel{border-color:var(--g);background:#f0f7f2}
.pw input{display:none}.pw .ic{font-size:1.4rem;display:block;margin-bottom:3px}
.pw .nm{font-size:.78rem;font-weight:700;color:#333}.pw .ds{font-size:.66rem;color:#777;margin-top:2px}
.btn{width:100%;background:var(--g);color:#fff;border:none;padding:13px;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;margin-top:8px;transition:background .2s}
.btn:hover{background:var(--gl)}.btn:disabled{opacity:.5;cursor:default}
.link{text-align:center;font-size:.85rem;color:#555;margin-top:14px}
.link a{color:var(--g);font-weight:700;text-decoration:none}
.alert-e{background:#fce4ec;color:#c62828;border:1px solid #f48fb1;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.86rem}
.success-box{text-align:center;padding:36px 20px}
.success-box .big{font-size:3rem;margin-bottom:12px}
.success-box h2{color:var(--g);margin-bottom:8px}
.success-box p{color:#555;font-size:.9rem;margin-bottom:20px}
.success-box a{display:block;background:var(--g);color:#fff;padding:13px;border-radius:10px;text-decoration:none;font-weight:700;font-size:1rem}
.sponsor-bar{text-align:center;padding:14px 20px 24px;max-width:500px;margin:0 auto}
.sponsor-bar p{font-size:.72rem;color:#999;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em}
.sponsor-logos{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}
.sponsor-logos span{font-size:.78rem;font-weight:700;color:#777;padding:4px 12px;border:1px solid #e0e0e0;border-radius:8px;background:#fff}
details summary{cursor:pointer;font-size:.82rem;font-weight:600;color:var(--g);padding:6px 0}
@media(max-width:480px){.row2,.pathway-grid{grid-template-columns:1fr}.trust{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<div class="hero">
  <div class="brand">M.T.T.I<span>.</span>Learn</div>
  <h1>Free CBC Learning for Every Kenyan Student</h1>
  <p>Interactive lessons, past papers & homework worksheets — all free, always.</p>
  <div class="badges">
    <span class="badge">✅ 100% Free</span>
    <span class="badge">📚 CBC Grade 10–12</span>
    <span class="badge">📱 Works Offline</span>
    <span class="badge">🏆 Certificates</span>
  </div>
</div>

<div class="trust">
  <div class="trust-item"><div class="num"><?= number_format($totalStudents) ?>+</div><div class="lbl">Students</div></div>
  <div class="trust-item"><div class="num"><?= number_format($totalLessons) ?>+</div><div class="lbl">Lessons</div></div>
  <div class="trust-item"><div class="num">Free</div><div class="lbl">Forever</div></div>
</div>

<div class="card">
<?php if ($success): ?>
  <div class="success-box">
    <div class="big">🎉</div>
    <h2>Welcome, <?= htmlspecialchars($success) ?>!</h2>
    <p>Your account is ready. All lessons are unlocked — start learning now.</p>
    <a href="<?= SITE_URL ?>/login.php">Go to Dashboard →</a>
    <p style="margin-top:12px;font-size:.75rem;color:#999">Share: <?= SITE_URL ?>/register.php</p>
  </div>
<?php else: ?>
  <?php if ($error): ?><div class="alert-e">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <div class="section-title">📚 Create Your Free Account</div>
  <form method="POST" id="rf">
    <div class="row2">
      <div class="fg"><label>Full Name <span class="req">*</span></label>
        <input type="text" name="name" class="fc" placeholder="Jane Wanjiku" value="<?= htmlspecialchars($_POST["name"] ?? "") ?>" required></div>
      <div class="fg"><label>Phone <span class="req">*</span></label>
        <input type="tel" name="phone" class="fc" placeholder="07XX XXX XXX" value="<?= htmlspecialchars($_POST["phone"] ?? "") ?>" required></div>
    </div>
    <div class="fg"><label>Email <small style="color:#999;font-weight:400">(optional)</small></label>
      <input type="email" name="email" class="fc" placeholder="jane@email.com" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"></div>
    <div class="row2">
      <div class="fg"><label>County <span class="req">*</span></label>
        <select name="county" class="fc" required>
          <option value="">Select county</option>
          <?php foreach ($counties as $c): $s = ($_POST["county"] ?? "") === $c ? " selected" : ""; echo "<option value=\"$c\"$s>$c</option>"; endforeach; ?>
        </select></div>
      <div class="fg"><label>School</label>
        <input type="text" name="school" class="fc" placeholder="Your school name" value="<?= htmlspecialchars($_POST["school"] ?? "") ?>"></div>
    </div>
    <div class="fg"><label>Class / Grade <span class="req">*</span></label>
      <select name="class_id" class="fc" required>
        <option value="">Select your class</option>
        <?php foreach ($allClasses as $cl): $s = ($_POST["class_id"] ?? "") == $cl["id"] ? " selected" : ""; echo "<option value=\"$cl[id]\"$s>$cl[title]</option>"; endforeach; ?>
      </select></div>
    <div class="fg"><label>Learning Pathway <span class="req">*</span></label>
      <div class="pathway-grid">
        <?php $cur = $_POST["pathway"] ?? "general";
        $paths = [["stem","🔬","STEM","Science & Maths"],["arts_sports","🎨","Arts & Sports","Creative & PE"],
                  ["social_sciences","🌍","Social Sciences","History & Business"],["general","📚","General","All subjects"]];
        foreach ($paths as [$v,$ic,$nm,$ds]): $sel = $cur === $v ? " sel" : ""; ?>
        <label class="pw<?= $sel ?>">
          <input type="radio" name="pathway" value="<?= $v ?>" <?= $cur===$v?"checked":"" ?>>
          <span class="ic"><?= $ic ?></span><span class="nm"><?= $nm ?></span><span class="ds"><?= $ds ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="row2">
      <div class="fg"><label>Password <span class="req">*</span></label>
        <input type="password" name="password" class="fc" placeholder="Min 6 characters" required></div>
      <div class="fg"><label>Confirm Password <span class="req">*</span></label>
        <input type="password" name="confirm_password" class="fc" placeholder="Repeat" required></div>
    </div>
    <details><summary>+ Add Parent/Guardian contact</summary>
      <div class="row2" style="margin-top:10px">
        <div class="fg"><label>Parent Name</label><input type="text" name="parent_name" class="fc" placeholder="Parent name"></div>
        <div class="fg"><label>Parent Phone</label><input type="tel" name="parent_phone" class="fc" placeholder="07XX XXX XXX"></div>
      </div>
    </details>
    <button type="submit" class="btn" id="sb">🚀 Create Free Account</button>
  </form>
  <div class="link">Already have an account? <a href="<?= SITE_URL ?>/login.php">Log in</a></div>
<?php endif; ?>
</div>

<?php if (!empty($sponsors)): ?>
<div class="sponsor-bar">
  <p>Learning supported by</p>
  <div class="sponsor-logos">
    <?php foreach ($sponsors as $s): ?>
    <span><?= htmlspecialchars($s["name"]) ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll(".pw").forEach(p => {
  p.addEventListener("click", () => {
    document.querySelectorAll(".pw").forEach(x => x.classList.remove("sel"));
    p.classList.add("sel"); p.querySelector("input").checked = true;
  });
});
document.getElementById("rf")?.addEventListener("submit", function() {
  const b = document.getElementById("sb"); b.disabled = true; b.textContent = "Creating account...";
});
</script>
</body></html>
