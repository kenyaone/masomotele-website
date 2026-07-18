<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Live Classes - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$role   = $auth->getRole();

$now = date('Y-m-d H:i:s');

// Upcoming sessions for classes I'm enrolled in (or all for teacher/admin)
if (in_array($role, ['admin', 'teacher'])) {
    $sessions = $db->fetchAll(
        "SELECT ls.*, c.title as class_title, u.name as host_name
         FROM lms_live_sessions ls
         JOIN lms_classes c ON ls.class_id = c.id
         JOIN lms_users u ON ls.created_by = u.id
         WHERE ls.status IN ('scheduled','live')
         ORDER BY ls.scheduled_at ASC"
    );
} else {
    $sessions = $db->fetchAll(
        "SELECT ls.*, c.title as class_title, u.name as host_name
         FROM lms_live_sessions ls
         JOIN lms_classes c ON ls.class_id = c.id
         JOIN lms_users u ON ls.created_by = u.id
         JOIN lms_enrolments e ON e.class_id = ls.class_id AND e.user_id = ?
         WHERE ls.status IN ('scheduled','live')
         ORDER BY ls.scheduled_at ASC",
        [$userId]
    );
}

// Past sessions
$pastSessions = $db->fetchAll(
    "SELECT ls.*, c.title as class_title, u.name as host_name
     FROM lms_live_sessions ls
     JOIN lms_classes c ON ls.class_id = c.id
     JOIN lms_users u ON ls.created_by = u.id
     WHERE ls.status = 'ended'
     ORDER BY ls.scheduled_at DESC LIMIT 20"
);

require_once __DIR__ . '/templates/header.php';
?>
<style>
.sess-card{background:#fff;border-radius:12px;padding:18px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.07);border-left:4px solid #ccc;transition:box-shadow .15s}
.sess-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.12)}
.sess-card.live{border-left-color:#16a34a}
.sess-card.scheduled{border-left-color:#2563eb}
.sess-card.ended{border-left-color:#94a3b8;opacity:.8}
.live-dot{width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block;margin-right:6px;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
.countdown{font-size:.78rem;color:#64748b;font-weight:600}
.badge-live{background:#dcfce7;color:#16a34a;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-sched{background:#dbeafe;color:#2563eb;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-ended{background:#f1f5f9;color:#64748b;padding:3px 10px;border-radius:20px;font-size:.72rem}
.hdr-bar{background:linear-gradient(135deg,#0a3d1f,#1a6b3c);color:#fff;border-radius:12px;padding:20px 24px;margin-bottom:24px}
</style>

<div class="hdr-bar d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-camera-video me-2"></i>Live Classes</h4>
        <p class="mb-0" style="opacity:.8;font-size:.88rem">Join live Jitsi video sessions with your instructor</p>
    </div>
    <?php if (in_array($role, ['admin','teacher'])): ?>
    <a href="schedule-session.php" class="btn btn-warning fw-bold">
        <i class="bi bi-plus-circle me-1"></i>Schedule Session
    </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-lg-8">
        <h5 class="mb-3 fw-bold"><i class="bi bi-calendar-event me-2 text-primary"></i>Upcoming &amp; Live</h5>

        <?php if (empty($sessions)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-camera-video-off" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:10px"></i>
            <p>No upcoming live sessions<?= $role === 'student' ? ' for your enrolled classes' : '' ?>.</p>
            <?php if ($role === 'student'): ?>
            <a href="classes.php" class="btn btn-primary btn-sm">Browse Classes</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php foreach ($sessions as $s):
            $isLive = ($s['status'] === 'live');
            $startTs = strtotime($s['scheduled_at']);
            $endTs   = $startTs + ($s['duration_minutes'] * 60);
            $isActive = (time() >= $startTs - 300 && time() <= $endTs); // joinable 5min early
        ?>
        <div class="sess-card <?= $s['status'] ?>">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                <div style="min-width:0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <?php if ($isLive): ?>
                        <span class="badge-live"><span class="live-dot"></span>LIVE NOW</span>
                        <?php else: ?>
                        <span class="badge-sched"><i class="bi bi-clock me-1"></i>Scheduled</span>
                        <?php endif; ?>
                        <span style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($s['class_title']) ?></span>
                    </div>
                    <h5 class="mb-1 fw-bold" style="font-size:1rem"><?= htmlspecialchars($s['title']) ?></h5>
                    <?php if ($s['description']): ?>
                    <p class="mb-2 text-muted" style="font-size:.83rem"><?= htmlspecialchars($s['description']) ?></p>
                    <?php endif; ?>
                    <div class="d-flex flex-wrap gap-3 mt-1" style="font-size:.78rem;color:#64748b">
                        <span><i class="bi bi-calendar3 me-1"></i><?= date('D, d M Y', $startTs) ?></span>
                        <span><i class="bi bi-clock me-1"></i><?= date('g:i A', $startTs) ?> EAT</span>
                        <span><i class="bi bi-hourglass-split me-1"></i><?= $s['duration_minutes'] ?> min</span>
                        <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($s['host_name']) ?></span>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2 align-items-end">
                    <?php if ($isActive || $isLive): ?>
                    <a href="live-class.php?id=<?= $s['id'] ?>" class="btn btn-success fw-bold">
                        <i class="bi bi-camera-video-fill me-1"></i>Join Now
                    </a>
                    <?php elseif (time() < $startTs): ?>
                    <button class="btn btn-outline-primary btn-sm" disabled>
                        <i class="bi bi-hourglass me-1"></i>Not started
                    </button>
                    <span class="countdown" id="cd-<?= $s['id'] ?>" data-ts="<?= $startTs ?>"></span>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin','teacher'])): ?>
                    <div class="d-flex gap-1">
                        <a href="schedule-session.php?edit=<?= $s['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="schedule-session.php" onsubmit="return confirm('Cancel this session?')">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle"></i></button>
                        </form>
                        <?php if (!$isLive): ?>
                        <form method="POST" action="schedule-session.php">
                            <input type="hidden" name="action" value="go_live">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button class="btn btn-success btn-sm fw-bold"><i class="bi bi-broadcast me-1"></i>Go Live</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($pastSessions)): ?>
        <h5 class="mb-3 mt-4 fw-bold text-muted"><i class="bi bi-clock-history me-2"></i>Past Sessions</h5>
        <?php foreach ($pastSessions as $s): ?>
        <div class="sess-card ended">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <span class="badge-ended me-2">Ended</span>
                    <span class="fw-semibold"><?= htmlspecialchars($s['title']) ?></span>
                    <span class="text-muted ms-2" style="font-size:.78rem">&mdash; <?= htmlspecialchars($s['class_title']) ?></span>
                    <div class="text-muted mt-1" style="font-size:.75rem">
                        <i class="bi bi-calendar3 me-1"></i><?= date('d M Y, g:i A', strtotime($s['scheduled_at'])) ?>
                        &nbsp;|&nbsp;<i class="bi bi-person me-1"></i><?= htmlspecialchars($s['host_name']) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card border-info mb-4">
            <div class="card-body text-center py-4">
                <i class="bi bi-camera-video-fill text-info" style="font-size:2.5rem"></i>
                <h6 class="mt-2 fw-bold">How Live Classes Work</h6>
                <ul class="text-start text-muted" style="font-size:.83rem">
                    <li>Sessions use Jitsi Meet — free, no app needed</li>
                    <li>Works in Chrome, Firefox, Edge &amp; Safari</li>
                    <li>Enable your camera &amp; microphone when prompted</li>
                    <li>You can join 5 minutes before the session starts</li>
                    <li>Sessions are NOT recorded automatically</li>
                </ul>
            </div>
        </div>
        <div class="card border-warning">
            <div class="card-body" style="font-size:.83rem">
                <h6 class="fw-bold"><i class="bi bi-wifi me-2 text-warning"></i>Low Bandwidth Tips</h6>
                <ul class="text-muted mb-0">
                    <li>Turn off your video if connection is slow</li>
                    <li>Use headphones to reduce echo</li>
                    <li>Close other browser tabs</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function formatCountdown(secs) {
    if (secs <= 0) return 'Starting soon...';
    const h = Math.floor(secs/3600), m = Math.floor((secs%3600)/60), s = secs%60;
    if (h > 0) return `Starts in ${h}h ${m}m`;
    if (m > 0) return `Starts in ${m}m ${s}s`;
    return `Starts in ${s}s`;
}
document.querySelectorAll('[data-ts]').forEach(el => {
    const ts = parseInt(el.dataset.ts) * 1000;
    const update = () => {
        const diff = Math.floor((ts - Date.now()) / 1000);
        el.textContent = formatCountdown(diff);
        if (diff <= 0) { el.closest('.sess-card').querySelector('button[disabled]')?.remove(); location.reload(); }
    };
    update(); setInterval(update, 1000);
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
