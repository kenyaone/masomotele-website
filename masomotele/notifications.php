<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Notifications - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
if (isset($_GET['mark_read'])) { $db->update('notifications', ['is_read'=>1], 'user_id=?', [$userId]); header('Location: ' . SITE_URL . '/notifications.php'); exit; }
$notifications = $db->fetchAll("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50", [$userId]);
require_once __DIR__ . '/templates/header.php';
?>
<div class="container" style="max-width:700px">
    <div class="d-flex justify-content-between mb-3"><h4><i class="bi bi-bell me-2 text-primary"></i>Notifications</h4><a href="?mark_read=1" class="btn btn-sm btn-outline-primary">Mark all read</a></div>
    <?php foreach ($notifications as $n): ?>
    <div class="card mb-2 <?= $n['is_read'] ? '' : 'border-primary' ?>">
        <div class="card-body py-2">
            <strong><?= htmlspecialchars($n['title']) ?></strong>
            <p class="mb-0 small"><?= htmlspecialchars($n['message']) ?></p>
            <small class="text-muted"><?= date('M j, g:ia', strtotime($n['created_at'])) ?></small>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($notifications)): ?><p class="text-muted">No notifications.</p><?php endif; ?>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
