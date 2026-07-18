<?php
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$role   = $auth->getRole();

$sessionId = (int)($_GET['id'] ?? 0);
$session = $db->fetchOne(
    "SELECT ls.*, c.title as class_title, u.name as host_name
     FROM lms_live_sessions ls
     JOIN lms_classes c ON ls.class_id = c.id
     JOIN lms_users u ON ls.created_by = u.id
     WHERE ls.id=?",
    [$sessionId]
);
if (!$session) { header('Location: ' . SITE_URL . '/live-sessions.php'); exit; }

// Students must be enrolled
if ($role === 'student') {
    $enrolled = $db->fetchOne("SELECT id FROM lms_enrolments WHERE user_id=? AND class_id=?", [$userId, $session['class_id']]);
    if (!$enrolled) { header('Location: ' . SITE_URL . '/live-sessions.php'); exit; }
}

if ($session['status'] === 'cancelled') { header('Location: ' . SITE_URL . '/live-sessions.php'); exit; }

// Mark session as live if teacher/admin joins and it was scheduled
if (in_array($role, ['admin','teacher']) && $session['status'] === 'scheduled') {
    $db->query("UPDATE lms_live_sessions SET status='live' WHERE id=?", [$sessionId]);
    $session['status'] = 'live';
}

$pageTitle = htmlspecialchars($session['title']) . ' — Live Class';
$startTs   = strtotime($session['scheduled_at']);
$endTs     = $startTs + ($session['duration_minutes'] * 60);
$displayName = htmlspecialchars($_SESSION['lms_user_name'] ?? 'Student');
$roomName    = $session['room_name'];

// Build a stable Jitsi room URL
$jitsiDomain = 'meet.jit.si';
$jitsiRoom   = rawurlencode($roomName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#fff;height:100vh;display:flex;flex-direction:column}
        .bar{display:flex;align-items:center;justify-content:space-between;padding:8px 16px;background:#1e293b;flex-shrink:0;flex-wrap:wrap;gap:8px}
        .bar-left{display:flex;align-items:center;gap:12px}
        .bar h5{margin:0;font-size:.9rem;font-weight:700}
        .bar .sub{font-size:.72rem;color:#94a3b8}
        .badge-live{background:#16a34a;color:#fff;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;animation:pulse 2s infinite}
        .badge-sched{background:#2563eb;color:#fff;padding:3px 10px;border-radius:20px;font-size:.7rem}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}
        .jitsi-frame{flex:1;width:100%;border:none;display:block}
        .waiting{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;text-align:center;padding:40px 20px}
        .waiting .icon{font-size:3rem;margin-bottom:12px;opacity:.4}
        .waiting h4{font-size:1.2rem;margin-bottom:8px}
        .waiting p{color:#94a3b8;font-size:.88rem}
        .btn-back{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:7px;background:rgba(255,255,255,.1);color:#fff;text-decoration:none;font-size:.8rem;border:none;cursor:pointer;transition:.15s}
        .btn-back:hover{background:rgba(255,255,255,.2);color:#fff}
        .info-bar{display:flex;gap:16px;align-items:center;flex-wrap:wrap;font-size:.75rem;color:#94a3b8}
    </style>
</head>
<body>
<div class="bar">
    <div class="bar-left">
        <a href="live-sessions.php" class="btn-back"><i>&#8592;</i> Back</a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5><?= htmlspecialchars($session['title']) ?></h5>
                <?php if ($session['status'] === 'live'): ?>
                <span class="badge-live">&#11044; LIVE</span>
                <?php else: ?>
                <span class="badge-sched">Scheduled</span>
                <?php endif; ?>
            </div>
            <div class="sub"><?= htmlspecialchars($session['class_title']) ?> &mdash; Host: <?= htmlspecialchars($session['host_name']) ?></div>
        </div>
    </div>
    <div class="info-bar">
        <span>&#128197; <?= date('d M Y', $startTs) ?></span>
        <span>&#128336; <?= date('g:i A', $startTs) ?> EAT</span>
        <span>&#9679; <?= $session['duration_minutes'] ?> min</span>
        <?php if (in_array($role, ['admin','teacher'])): ?>
        <form method="POST" action="schedule-session.php" style="margin:0">
            <input type="hidden" name="action" value="end_session">
            <input type="hidden" name="id" value="<?= $session['id'] ?>">
            <button type="submit" onclick="return confirm('End this live session?')" style="background:#ef4444;color:#fff;border:none;border-radius:6px;padding:5px 12px;cursor:pointer;font-size:.75rem;font-weight:700">End Session</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php
$now = time();
// Always joinable if status=live (teacher clicked Go Live), or within 5 min of scheduled time
$isJoinable = ($session['status'] === 'live') || ($now >= $startTs - 300);
if ($isJoinable):
?>
<iframe
    class="jitsi-frame"
    src="https://<?= $jitsiDomain ?>/<?= $jitsiRoom ?>#userInfo.displayName=<?= rawurlencode($displayName) ?>&config.startWithVideoMuted=<?= $role === 'student' ? 'true' : 'false' ?>&config.startWithAudioMuted=false&config.prejoinPageEnabled=true&config.disableDeepLinking=true&interfaceConfig.SHOW_JITSI_WATERMARK=false&interfaceConfig.DEFAULT_REMOTE_DISPLAY_NAME=Student"
    allow="camera; microphone; display-capture; fullscreen; autoplay"
    allowfullscreen
></iframe>
<?php else: ?>
<div class="waiting">
    <div class="icon">&#128247;</div>
    <h4><?= htmlspecialchars($session['title']) ?></h4>
    <p>This session starts on <strong><?= date('l, d F Y at g:i A', $startTs) ?> EAT</strong></p>
    <p style="margin-top:8px">You can join 5 minutes before start time.</p>
    <p id="countdown" style="margin-top:16px;font-size:1.1rem;font-weight:700;color:#60a5fa"></p>
    <a href="live-sessions.php" class="btn-back" style="margin-top:20px">&#8592; Back to Live Classes</a>
</div>
<script>
const ts = <?= $startTs * 1000 ?>;
function tick(){
    const diff = Math.floor((ts - 300000 - Date.now())/1000);
    if(diff<=0){location.reload();return;}
    const h=Math.floor(diff/3600),m=Math.floor((diff%3600)/60),s=diff%60;
    document.getElementById('countdown').textContent = (h?h+'h ':'')+m+'m '+s+'s until you can join';
}
tick();setInterval(tick,1000);
</script>
<?php endif; ?>
</body>
</html>
