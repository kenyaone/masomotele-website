<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Badges - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();

$allBadges = $db->fetchAll("SELECT b.*, (SELECT COUNT(*) FROM user_badges WHERE badge_id=b.id) as total_earned FROM badges b ORDER BY b.id");
$myBadgeIds = array_column($db->fetchAll("SELECT badge_id FROM user_badges WHERE user_id=?", [$userId]), 'badge_id');

require_once __DIR__ . '/templates/header.php';
?>
<div class="container">
    <h4 class="mb-4"><i class="bi bi-award me-2 text-primary"></i>Badges & Achievements</h4>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card bg-primary text-white p-3 text-center"><h2><?= count($myBadgeIds) ?></h2><small>Badges Earned</small></div></div>
        <div class="col-md-4"><div class="card bg-secondary text-white p-3 text-center"><h2><?= count($allBadges) - count($myBadgeIds) ?></h2><small>Badges Remaining</small></div></div>
        <div class="col-md-4"><div class="card bg-success text-white p-3 text-center"><h2><?= count($allBadges) > 0 ? round((count($myBadgeIds)/count($allBadges))*100) : 0 ?>%</h2><small>Completion</small></div></div>
    </div>

    <h5 class="mb-3">All Badges</h5>
    <div class="row g-3">
        <?php foreach ($allBadges as $b): $earned = in_array($b['id'], $myBadgeIds); ?>
        <div class="col-md-3 col-6">
            <div class="card h-100 text-center <?= $earned ? '' : 'opacity-50' ?>">
                <div class="card-body">
                    <div class="rounded-circle bg-<?= $b['color'] ?? 'primary' ?> text-white d-flex align-items-center justify-content-center mx-auto mb-2" style="width:70px;height:70px;font-size:2rem">
                        <i class="bi bi-<?= $b['icon'] ?? 'star' ?>"></i>
                    </div>
                    <h6><?= htmlspecialchars($b['name']) ?></h6>
                    <p class="small text-muted mb-1"><?= htmlspecialchars($b['description']) ?></p>
                    <?php if ($earned): ?>
                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Earned!</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><i class="bi bi-lock me-1"></i>Locked</span>
                    <?php endif; ?>
                    <div class="small text-muted mt-1"><?= $b['total_earned'] ?> users earned this</div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($allBadges)): ?><div class="col-12"><p class="text-muted">No badges configured yet.</p></div><?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
