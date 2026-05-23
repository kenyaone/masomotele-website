<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Dashboard - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$role = $auth->getRole();

// Stats
$totalClasses = (int)$db->fetchColumn("SELECT COUNT(*) FROM classes WHERE status='active'");
$myEnrolments = (int)$db->fetchColumn("SELECT COUNT(*) FROM enrolments WHERE user_id = ?", [$userId]);
$totalUsers = ($role === 'admin') ? (int)$db->fetchColumn("SELECT COUNT(*) FROM users") : 0;

// My classes
$myClasses = $db->fetchAll("SELECT c.*, (SELECT COUNT(*) FROM lessons WHERE class_id=c.id) as lesson_count,
    (SELECT COUNT(*) FROM completions WHERE class_id=c.id AND user_id=?) as completed
    FROM classes c JOIN enrolments e ON c.id=e.class_id WHERE e.user_id=? AND c.status='active' ORDER BY e.enrolled_at DESC LIMIT 6", [$userId, $userId]);

// My badges
$myBadges = $db->fetchAll("SELECT b.* FROM badges b JOIN user_badges ub ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.earned_at DESC LIMIT 4", [$userId]);

// Recent forum posts
$recentPosts = $db->fetchAll("SELECT fp.*, u.name as author, ft.title as topic_title FROM forum_posts fp JOIN users u ON fp.user_id=u.id JOIN forum_topics ft ON fp.topic_id=ft.id ORDER BY fp.created_at DESC LIMIT 5");

// Recent announcements
$announcements = $db->fetchAll("SELECT a.*, u.name as teacher FROM announcements a JOIN users u ON a.teacher_id=u.id ORDER BY a.created_at DESC LIMIT 3");

require_once __DIR__ . '/templates/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-house me-2 text-primary"></i>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h4>
        <span class="badge bg-primary fs-6"><?= ucfirst($role) ?></span>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card text-white p-3" style="background:linear-gradient(135deg,#1a5632,#2d7a4c)"><h6><i class="bi bi-book me-1"></i>Classes</h6><h2><?= $totalClasses ?></h2></div></div>
        <div class="col-md-3"><div class="card text-white p-3" style="background:linear-gradient(135deg,#16a34a,#22c55e)"><h6><i class="bi bi-person-check me-1"></i>Enrolled</h6><h2><?= $myEnrolments ?></h2></div></div>
        <div class="col-md-3"><div class="card text-white p-3" style="background:linear-gradient(135deg,#e8a423,#f5c842)"><h6><i class="bi bi-award me-1"></i>Badges</h6><h2><?= count($myBadges) ?></h2></div></div>
        <?php if ($role === 'admin'): ?>
        <div class="col-md-3"><div class="card text-white p-3" style="background:linear-gradient(135deg,#b45309,#ea580c)"><h6><i class="bi bi-people me-1"></i>Users</h6><h2><?= $totalUsers ?></h2></div></div>
        <?php else: ?>
        <div class="col-md-3"><div class="card text-white p-3" style="background:linear-gradient(135deg,#b45309,#ea580c)"><h6><i class="bi bi-trophy me-1"></i>Completed</h6><h2><?= array_sum(array_column($myClasses, 'completed')) ?></h2></div></div>
        <?php endif; ?>
    </div>
<?php if ($role === 'admin'): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning bg-opacity-10"><h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Admin Tools</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><a href="<?= SITE_URL ?>/bulk-upload-students.php" class="btn btn-outline-success w-100 py-3"><i class="bi bi-people-fill d-block" style="font-size:1.5rem;"></i>Bulk Upload Students</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/bulk-upload-lessons.php" class="btn btn-outline-primary w-100 py-3"><i class="bi bi-journals d-block" style="font-size:1.5rem;"></i>Bulk Upload Lessons</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/admin/smart-upload.php" class="btn btn-outline-warning w-100 py-3"><i class="bi bi-lightning-charge-fill d-block" style="font-size:1.5rem;"></i>Smart Upload</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/admin/curriculum-seeder.php" class="btn btn-outline-info w-100 py-3"><i class="bi bi-database-fill-gear d-block" style="font-size:1.5rem;"></i>Curriculum Seeder</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/admin/past-papers.php" class="btn btn-outline-danger w-100 py-3"><i class="bi bi-journal-check d-block" style="font-size:1.5rem;"></i>KCSE Past Papers</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/smart-upload-papers.php" class="btn btn-outline-warning w-100 py-3"><i class="bi bi-lightning-charge-fill d-block" style="font-size:1.5rem;"></i>Smart Upload Papers</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/admin/interactives.php" class="btn btn-outline-info w-100 py-3"><i class="bi bi-play-circle-fill d-block" style="font-size:1.5rem;"></i>Interactives</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/admin/teacher-dashboard.php" class="btn btn-outline-primary w-100 py-3"><i class="bi bi-graph-up d-block" style="font-size:1.5rem;"></i>Teacher Dashboard</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-outline-secondary w-100 py-3"><i class="bi bi-person-gear d-block" style="font-size:1.5rem;"></i>Manage Users</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/admin/bulk-upload.php" class="btn btn-outline-success w-100 py-3"><i class="bi bi-cloud-upload-fill d-block" style="font-size:1.5rem;"></i>Bulk Upload</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/admin/timetable.php" class="btn btn-outline-dark w-100 py-3"><i class="bi bi-calendar-week-fill d-block" style="font-size:1.5rem;"></i>Timetable</a></div>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/admin/audit-log.php" class="btn btn-outline-danger w-100 py-3"><i class="bi bi-shield-check d-block" style="font-size:1.5rem;"></i>Audit Log</a></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="row g-4">
        <!-- My Classes -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between"><h5 class="mb-0"><i class="bi bi-book me-2"></i>My Classes</h5><a href="<?= SITE_URL ?>/classes.php" class="btn btn-sm btn-primary">Browse All</a></div>
                <div class="card-body">
                    <?php if (empty($myClasses)): ?>
                        <p class="text-muted">You haven't enrolled in any classes yet. <a href="<?= SITE_URL ?>/classes.php">Browse classes</a></p>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($myClasses as $c): $pct = $c['lesson_count'] > 0 ? round(($c['completed']/$c['lesson_count'])*100) : 0; ?>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6><a href="<?= SITE_URL ?>/class.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></a></h6>
                                    <div class="progress mb-2" style="height:8px"><div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div></div>
                                    <small class="text-muted"><?= $c['completed'] ?>/<?= $c['lesson_count'] ?> lessons (<?= $pct ?>%)</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Forum Posts -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between"><h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Recent Discussions</h5><a href="<?= SITE_URL ?>/forum.php" class="btn btn-sm btn-outline-primary">View All</a></div>
                <div class="card-body">
                    <?php if (empty($recentPosts)): ?>
                        <p class="text-muted">No discussions yet. <a href="<?= SITE_URL ?>/forum.php">Start one!</a></p>
                    <?php else: ?>
                        <?php foreach ($recentPosts as $post): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <strong><?= htmlspecialchars($post['author']) ?></strong> in <a href="<?= SITE_URL ?>/forum.php?topic=<?= $post['topic_id'] ?>"><?= htmlspecialchars($post['topic_title']) ?></a>
                            <p class="mb-0 text-muted small"><?= htmlspecialchars(substr($post['content'], 0, 120)) ?>... <span class="text-muted"><?= date('M j, g:ia', strtotime($post['created_at'])) ?></span></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            </div>
            <!-- Interactive Library -->
            <div class="card mb-4 border-info">
                <div class="card-body text-center py-4">
                    <i class="bi bi-play-circle-fill text-info" style="font-size:2.5rem"></i>
                    <h5 class="mt-2 fw-bold">Interactive Library</h5>
                    <p class="text-muted small mb-2">Learn with interactive lessons, quizzes &amp; simulations</p>
                    <a href="<?= SITE_URL ?>/interactives.php" class="btn btn-info text-white w-100"><i class="bi bi-play-fill me-1"></i>Browse Interactives</a>
                </div>
            </div>
            <!-- KCSE Past Papers -->
                <div class="col-md-3"><a href="<?= SITE_URL ?>/smart-upload-papers.php" class="btn btn-outline-warning w-100 py-3"><i class="bi bi-lightning-charge-fill d-block" style="font-size:1.5rem;"></i>Smart Upload Papers</a></div>
            <div class="card mb-4 border-warning">
                <div class="card-body text-center py-4">
                    <i class="bi bi-journal-check text-warning" style="font-size:2.5rem"></i>
                    <h5 class="mt-2 fw-bold">KCSE Past Papers</h5>
                <div class="col-md-3"><a href="<?= SITE_URL ?>/smart-upload-papers.php" class="btn btn-outline-warning w-100 py-3"><i class="bi bi-lightning-charge-fill d-block" style="font-size:1.5rem;"></i>Smart Upload Papers</a></div>
                    <p class="text-muted small mb-2">Browse & download past exam papers for revision</p>
                    <a href="<?= SITE_URL ?>/past-papers.php" class="btn btn-warning w-100"><i class="bi bi-arrow-right me-1"></i>Open Past Papers</a>
                </div>
            </div>
            <!-- My Badges -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-award me-2"></i>My Badges</h5></div>
                <div class="card-body">
                    <?php if (empty($myBadges)): ?>
                        <p class="text-muted">No badges yet. Complete lessons and interactive activities to earn badges!</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($myBadges as $badge): ?>
                            <div class="text-center" style="width:70px" title="<?= htmlspecialchars($badge['description']) ?>">
                                <div class="rounded-circle bg-<?= $badge['color'] ?? 'primary' ?> text-white d-flex align-items-center justify-content-center mx-auto" style="width:50px;height:50px;font-size:1.5rem"><i class="bi bi-<?= $badge['icon'] ?? 'star' ?>"></i></div>
                                <small class="d-block mt-1"><?= htmlspecialchars($badge['name']) ?></small>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <a href="<?= SITE_URL ?>/badges.php" class="btn btn-sm btn-outline-primary mt-2 w-100">View All Badges</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Announcements -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-megaphone me-2"></i>Announcements</h5></div>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <p class="text-muted">No announcements.</p>
                    <?php else: ?>
                        <?php foreach ($announcements as $a): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <strong><?= htmlspecialchars($a['title']) ?></strong>
                            <p class="mb-0 small text-muted"><?= htmlspecialchars(substr($a['content'], 0, 100)) ?>...</p>
                            <small class="text-muted">By <?= htmlspecialchars($a['teacher']) ?> - <?= date('M j', strtotime($a['created_at'])) ?></small>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
