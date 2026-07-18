<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Schedule Live Session - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
if (!in_array($auth->getRole(), ['admin','teacher'])) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }
$db = Database::getInstance();
$userId = $auth->getUserId();
$role   = $auth->getRole();
$msg = ''; $error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'cancel') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $db->query("UPDATE lms_live_sessions SET status='cancelled' WHERE id=? AND (created_by=? OR ?='admin')", [$id, $userId, $role]);
        header('Location: ' . SITE_URL . '/live-sessions.php'); exit;
    }

    if ($action === 'go_live') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $db->query("UPDATE lms_live_sessions SET status='live' WHERE id=?", [$id]);
        header('Location: ' . SITE_URL . '/live-class.php?id=' . $id); exit;
    }

    if ($action === 'end_session') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $db->query("UPDATE lms_live_sessions SET status='ended' WHERE id=?", [$id]);
        header('Location: ' . SITE_URL . '/live-sessions.php'); exit;
    }

    if ($action === 'save' || $action === 'update') {
        $classId  = (int)($_POST['class_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $dateStr  = trim($_POST['scheduled_date'] ?? '');
        $timeStr  = trim($_POST['scheduled_time'] ?? '');
        $duration = (int)($_POST['duration_minutes'] ?? 60);
        $editId   = (int)($_POST['edit_id'] ?? 0);

        if (!$classId || !$title || !$dateStr || !$timeStr) {
            $error = 'Please fill in all required fields.';
        } else {
            $scheduledAt = date('Y-m-d H:i:s', strtotime("$dateStr $timeStr"));
            // Generate a deterministic room name: class + title slug + date
            $slug      = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
            $roomName  = 'mtti-' . $classId . '-' . $slug . '-' . date('Ymd', strtotime($dateStr));
            $roomName  = substr($roomName, 0, 90);

            if ($editId) {
                $db->query(
                    "UPDATE lms_live_sessions SET class_id=?, title=?, description=?, scheduled_at=?, duration_minutes=?, room_name=? WHERE id=? AND (created_by=? OR ?='admin')",
                    [$classId, $title, $desc, $scheduledAt, $duration, $roomName, $editId, $userId, $role]
                );
                $msg = 'Session updated.';
            } else {
                $newId = $db->insert('lms_live_sessions', [
                    'class_id'         => $classId,
                    'title'            => $title,
                    'description'      => $desc,
                    'scheduled_at'     => $scheduledAt,
                    'duration_minutes' => $duration,
                    'room_name'        => $roomName,
                    'status'           => 'scheduled',
                    'created_by'       => $userId,
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
                $msg = 'Session scheduled! Students will see it on the Live Classes page.';
            }
        }
    }
}

// Load for edit
$editSession = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId) {
    $editSession = $db->fetchOne("SELECT * FROM lms_live_sessions WHERE id=?", [$editId]);
}

// Load classes for dropdown
$classes = $db->fetchAll("SELECT id, title FROM lms_classes WHERE status='active' ORDER BY title");

require_once __DIR__ . '/templates/header.php';
?>
<div style="max-width:680px;margin:0 auto">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="live-sessions.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-camera-video me-2 text-primary"></i>
            <?= $editSession ? 'Edit Session' : 'Schedule Live Session' ?>
        </h4>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($msg) ?>
        <a href="live-sessions.php" class="btn btn-sm btn-success ms-auto">View All Sessions</a>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editSession ? 'update' : 'save' ?>">
                <?php if ($editSession): ?><input type="hidden" name="edit_id" value="<?= $editSession['id'] ?>"><?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                    <select name="class_id" class="form-select" required>
                        <option value="">-- Select a class --</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($editSession && $editSession['class_id'] == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['title']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Session Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Grade 10 Chemistry — Organic Compounds"
                           value="<?= htmlspecialchars($editSession['title'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description <span class="text-muted">(optional)</span></label>
                    <textarea name="description" class="form-control" rows="2" placeholder="What will be covered in this session?"><?= htmlspecialchars($editSession['description'] ?? '') ?></textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="scheduled_date" class="form-control" required
                               min="<?= date('Y-m-d') ?>"
                               value="<?= $editSession ? date('Y-m-d', strtotime($editSession['scheduled_at'])) : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Time (EAT) <span class="text-danger">*</span></label>
                        <input type="time" name="scheduled_time" class="form-control" required
                               value="<?= $editSession ? date('H:i', strtotime($editSession['scheduled_at'])) : '08:00' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Duration</label>
                        <select name="duration_minutes" class="form-select">
                            <?php foreach ([30, 45, 60, 90, 120] as $d): ?>
                            <option value="<?= $d ?>" <?= ($editSession['duration_minutes'] ?? 60) == $d ? 'selected' : '' ?>>
                                <?= $d >= 60 ? ($d/60).'h'.($d%60?(' '.($d%60).'m'):'') : $d.'m' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 mb-4" style="font-size:.83rem">
                    <i class="bi bi-info-circle me-1"></i>
                    A Jitsi Meet room will be automatically created for this session. No account needed — students join directly from the LMS.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="bi bi-calendar-check me-1"></i><?= $editSession ? 'Update Session' : 'Schedule Session' ?>
                    </button>
                    <a href="live-sessions.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-3 border-0 bg-light">
        <div class="card-body py-3" style="font-size:.82rem;color:#64748b">
            <strong>How it works:</strong> When you click "Go Live" from the sessions list, students enrolled in that class can join. The Jitsi room is open as long as the session is active. Click "End Session" when done.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
