<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Classes - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$search = trim($_GET['q'] ?? '');

// Handle enrol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enrol_class_id'])) {
    $classId = (int)$_POST['enrol_class_id'];
    $exists = $db->fetchOne("SELECT id FROM enrolments WHERE user_id=? AND class_id=?", [$userId, $classId]);
    if (!$exists) {
        $db->insert('enrolments', ['user_id' => $userId, 'class_id' => $classId, 'status' => 'active']);
        // Check for first enrolment badge
        $enrolCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM enrolments WHERE user_id=?", [$userId]);
        if ($enrolCount === 1) {
            $badge = $db->fetchOne("SELECT id FROM badges WHERE trigger_type='first_enrolment'");
            if ($badge) { $db->insert('user_badges', ['user_id' => $userId, 'badge_id' => $badge['id'], 'earned_at' => date('Y-m-d H:i:s')]); }
        }
    }
    header('Location: ' . SITE_URL . '/class.php?id=' . $classId); exit;
}

$sql = "SELECT c.*, u.name as instructor, (SELECT COUNT(*) FROM lessons WHERE class_id=c.id) as lessons,
    (SELECT COUNT(*) FROM enrolments WHERE class_id=c.id) as students
    FROM classes c LEFT JOIN users u ON c.instructor_id=u.id WHERE c.status='active'";
$params = [];
if ($search) { $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)"; $params = ["%$search%", "%$search%"]; }
$sql .= " ORDER BY c.created_at DESC";
$classes = $db->fetchAll($sql, $params);
$enrolled = $db->fetchAll("SELECT class_id FROM enrolments WHERE user_id=?", [$userId]);
$enrolledIds = array_column($enrolled, 'class_id');
require_once __DIR__ . '/templates/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-book me-2 text-primary"></i>Browse Classes</h4>
        <form class="d-flex gap-2" method="GET"><input type="text" name="q" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>"><button class="btn btn-primary"><i class="bi bi-search"></i></button></form>
    </div>
    <div class="row g-3">
        <?php foreach ($classes as $c): $isEnrolled = in_array($c['id'], $enrolledIds); ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5><?= htmlspecialchars($c['title']) ?></h5>
                    <p class="text-muted small"><?= htmlspecialchars(substr($c['description'] ?? '', 0, 120)) ?></p>
                    <div class="d-flex gap-3 text-muted small mb-3">
                        <span><i class="bi bi-journal me-1"></i><?= $c['lessons'] ?> lessons</span>
                        <span><i class="bi bi-people me-1"></i><?= $c['students'] ?> students</span>
                    </div>
                    <?php if ($isEnrolled): ?>
                        <a href="<?= SITE_URL ?>/class.php?id=<?= $c['id'] ?>" class="btn btn-success w-100"><i class="bi bi-play-circle me-1"></i>Continue</a>
                    <?php else: ?>
                        <form method="POST"><input type="hidden" name="enrol_class_id" value="<?= $c['id'] ?>"><button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Enrol</button></form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($classes)): ?><div class="col-12"><p class="text-muted">No classes available yet.</p></div><?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
