<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Certificates - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();

// Generate certificate if requesting
if (isset($_GET['generate'])) {
    $classId = (int)$_GET['generate'];
    $class = $db->fetchOne("SELECT * FROM classes WHERE id=?", [$classId]);
    if ($class) {
        // Calculate average grade for this class
        $avg = $db->fetchColumn("SELECT AVG(percentage) FROM quiz_attempts WHERE quiz_id IN (SELECT id FROM quizzes WHERE class_id=?) AND user_id=? AND passed=1", [$classId, $userId]);
        if ($avg >= 70) {
            $exists = $db->fetchOne("SELECT id FROM certificates WHERE user_id=? AND class_id=?", [$userId, $classId]);
            if (!$exists) {
                $certNum = 'MTTI-' . date('Y') . '-' . str_pad($classId, 3, '0', STR_PAD_LEFT) . '-' . str_pad($userId, 4, '0', STR_PAD_LEFT);
                $db->insert('certificates', ['user_id'=>$userId, 'class_id'=>$classId, 'certificate_number'=>$certNum, 'grade'=>round($avg,2), 'issued_at'=>date('Y-m-d H:i:s')]);
                // Badge check
                $certCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM certificates WHERE user_id=?", [$userId]);
                if ($certCount === 1) {
                    $badge = $db->fetchOne("SELECT id FROM badges WHERE trigger_type='course_complete'");
                    if ($badge) {
                        $has = $db->fetchOne("SELECT id FROM user_badges WHERE user_id=? AND badge_id=?", [$userId, $badge['id']]);
                        if (!$has) $db->insert('user_badges', ['user_id'=>$userId,'badge_id'=>$badge['id'],'earned_at'=>date('Y-m-d H:i:s')]);
                    }
                }
            }
        }
    }
    header('Location: ' . SITE_URL . '/certificates.php'); exit;
}

// View/print certificate
if (isset($_GET['view'])) {
    $cert = $db->fetchOne("SELECT c.*, u.name as student_name, cl.title as class_title FROM certificates c JOIN users u ON c.user_id=u.id JOIN classes cl ON c.class_id=cl.id WHERE c.id=? AND c.user_id=?", [(int)$_GET['view'], $userId]);
    if ($cert):
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Certificate - <?=htmlspecialchars($cert['student_name'])?></title>
<style>
    @media print { body{margin:0;} .no-print{display:none!important;} }
    body{font-family:Georgia,serif;text-align:center;background:#f5f5f5;}
    .cert{width:900px;margin:20px auto;padding:60px;background:white;border:8px double #1a5276;position:relative;box-shadow:0 4px 20px rgba(0,0,0,0.1);}
    .cert::before{content:'';position:absolute;top:15px;left:15px;right:15px;bottom:15px;border:2px solid #2980b9;pointer-events:none;}
    .cert h1{font-size:3em;color:#1a5276;margin:0;letter-spacing:3px;}
    .cert h2{font-size:1.4em;color:#555;font-weight:normal;margin:10px 0 30px;}
    .cert .name{font-size:2.2em;color:#1a5276;border-bottom:2px solid #2980b9;display:inline-block;padding:10px 40px;margin:20px 0;}
    .cert .course{font-size:1.3em;color:#333;margin:15px 0;}
    .cert .grade{font-size:1.1em;color:#27ae60;margin:10px 0;}
    .cert .details{display:flex;justify-content:space-between;margin-top:50px;padding:0 40px;}
    .cert .details div{text-align:center;}
    .cert .details .line{border-top:1px solid #333;padding-top:5px;min-width:200px;}
    .logo{font-size:1.5em;color:#1a5276;margin-bottom:10px;}
</style></head><body>
<div class="no-print" style="padding:15px;text-align:center;background:#1a5276;">
    <button onclick="window.print()" style="padding:10px 30px;font-size:16px;background:#27ae60;color:white;border:none;border-radius:5px;cursor:pointer;">🖨️ Print Certificate</button>
    <a href="<?=SITE_URL?>/certificates.php" style="padding:10px 30px;font-size:16px;background:#555;color:white;border:none;border-radius:5px;text-decoration:none;margin-left:10px;">← Back</a>
</div>
<div class="cert">
    <div class="logo">🎓 M.T.T.I</div>
    <h1>CERTIFICATE</h1>
    <h2>of Completion</h2>
    <p style="font-size:1.1em;color:#666;">This is to certify that</p>
    <div class="name"><?=htmlspecialchars($cert['student_name'])?></div>
    <p class="course">has successfully completed the course</p>
    <p style="font-size:1.5em;color:#1a5276;font-weight:bold;"><?=htmlspecialchars($cert['class_title'])?></p>
    <p class="grade">with a grade of <strong><?=$cert['grade']?>%</strong></p>
    <p style="color:#888;font-size:0.9em;">Certificate No: <?=$cert['certificate_number']?></p>
    <div class="details">
        <div><div class="line">Date: <?=date('F j, Y', strtotime($cert['issued_at']))?></div></div>
        <div><div class="line">Masomotele Technical Training Institute</div></div>
        <div><div class="line">Director</div></div>
    </div>
</div>
</body></html>
<?php exit; endif;
}

// My certificates
$certs = $db->fetchAll("SELECT c.*, cl.title as class_title FROM certificates c JOIN classes cl ON c.class_id=cl.id WHERE c.user_id=? ORDER BY c.issued_at DESC", [$userId]);

// Eligible classes (passed with 70%+ but no cert yet)
$eligible = $db->fetchAll("SELECT cl.id, cl.title, ROUND(AVG(qa.percentage),1) as avg_grade FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id=q.id JOIN classes cl ON q.class_id=cl.id WHERE qa.user_id=? AND qa.passed=1 GROUP BY cl.id HAVING avg_grade >= 70 AND cl.id NOT IN (SELECT class_id FROM certificates WHERE user_id=?)", [$userId, $userId]);

require_once __DIR__ . '/templates/header.php';
?>
<div class="container">
    <h4 class="mb-4"><i class="bi bi-patch-check me-2 text-primary"></i>My Certificates</h4>

    <?php if (!empty($eligible)): ?>
    <div class="alert alert-success"><i class="bi bi-star me-2"></i>You have earned certificates! Click to generate:</div>
    <div class="row g-3 mb-4">
        <?php foreach ($eligible as $e): ?>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h6><?=htmlspecialchars($e['title'])?></h6>
                    <span class="badge bg-success fs-6"><?=$e['avg_grade']?>%</span>
                    <a href="?generate=<?=$e['id']?>" class="btn btn-success w-100 mt-2"><i class="bi bi-award me-1"></i>Generate Certificate</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($certs)): ?>
    <div class="row g-3">
        <?php foreach ($certs as $c): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div style="font-size:3rem;color:#1a5276;">🎓</div>
                    <h6><?=htmlspecialchars($c['class_title'])?></h6>
                    <span class="badge bg-success"><?=$c['grade']?>%</span>
                    <p class="small text-muted mt-1"><?=$c['certificate_number']?><br>Issued: <?=date('M j, Y', strtotime($c['issued_at']))?></p>
                    <a href="?view=<?=$c['id']?>" class="btn btn-primary btn-sm"><i class="bi bi-eye me-1"></i>View & Print</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card"><div class="card-body text-muted">No certificates yet. Complete courses with 70%+ to earn certificates.</div></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
