<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'My Grades - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$grades = $db->fetchAll("SELECT g.*, c.title as class_title, q.title as quiz_title FROM grades g JOIN classes c ON g.class_id=c.id LEFT JOIN quizzes q ON g.quiz_id=q.id WHERE g.user_id=? ORDER BY g.created_at DESC", [$auth->getUserId()]);
require_once __DIR__ . '/templates/header.php';
?>
<div class="container">
    <h4 class="mb-3"><i class="bi bi-trophy me-2 text-primary"></i>My Grades</h4>
    <div class="card"><div class="card-body">
        <table class="table table-hover">
            <thead><tr><th>Class</th><th>Quiz</th><th>Grade</th><th>Type</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($grades as $g): ?>
            <tr><td><?= htmlspecialchars($g['class_title']) ?></td><td><?= htmlspecialchars($g['quiz_title'] ?? 'Manual') ?></td>
                <td><span class="badge bg-<?= $g['grade']>=50?'success':'danger' ?>"><?= $g['grade'] ?>%</span></td>
                <td><?= ucfirst($g['grade_type']) ?></td><td><?= date('M j, Y', strtotime($g['created_at'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($grades)): ?><tr><td colspan="5" class="text-muted">No grades yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div></div>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
