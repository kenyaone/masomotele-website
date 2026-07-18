<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Dashboard - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$role = $auth->getRole();

// Stats
$totalClasses = (int)$db->fetchColumn("SELECT COUNT(*) FROM lms_classes WHERE status='active'");
$myEnrolments = (int)$db->fetchColumn("SELECT COUNT(*) FROM lms_enrolments WHERE user_id = ?", [$userId]);
$totalUsers = ($role === 'admin') ? (int)$db->fetchColumn("SELECT COUNT(*) FROM lms_users") : 0;

// My classes
$myClasses = $db->fetchAll("SELECT c.*, (SELECT COUNT(*) FROM lms_lessons WHERE class_id=c.id) as lesson_count,
    (SELECT COUNT(*) FROM lms_completions WHERE class_id=c.id AND user_id=?) as completed
    FROM lms_classes c JOIN lms_enrolments e ON c.id=e.class_id WHERE e.user_id=? AND c.status='active' ORDER BY e.enrolled_at DESC LIMIT 6", [$userId, $userId]);

// My badges
$myBadges = $db->fetchAll("SELECT b.* FROM lms_badges b JOIN lms_user_badges ub ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.earned_at DESC LIMIT 4", [$userId]);

// Recent forum posts
$recentPosts = $db->fetchAll("SELECT fp.*, u.name as author, ft.title as topic_title FROM lms_forum_posts fp JOIN lms_users u ON fp.user_id=u.id JOIN lms_forum_topics ft ON fp.topic_id=ft.id ORDER BY fp.created_at DESC LIMIT 5");

// Recent announcements
$announcements = $db->fetchAll("SELECT a.*, u.name as teacher FROM lms_announcements a JOIN lms_users u ON a.teacher_id=u.id ORDER BY a.created_at DESC LIMIT 3");

// Upcoming live sessions (for enrolled classes if student, all if admin/teacher)
if (in_array($role, ['admin','teacher'])) {
    $upcomingLive = $db->fetchAll(
        "SELECT ls.*, c.title as class_title FROM lms_live_sessions ls JOIN lms_classes c ON ls.class_id=c.id WHERE ls.status IN ('scheduled','live') ORDER BY ls.scheduled_at ASC LIMIT 5"
    );
} else {
    $upcomingLive = $db->fetchAll(
        "SELECT ls.*, c.title as class_title FROM lms_live_sessions ls JOIN lms_classes c ON ls.class_id=c.id JOIN lms_enrolments e ON e.class_id=ls.class_id AND e.user_id=? WHERE ls.status IN ('scheduled','live') ORDER BY ls.scheduled_at ASC LIMIT 5",
        [$userId]
    );
}

require_once __DIR__ . '/templates/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-house me-2 text-primary"></i>Welcome, <?= htmlspecialchars($_SESSION['lms_user_name'] ?? '') ?>!</h4>
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
                        <p class="text-muted">You haven't enrolled in any lms_classes yet. <a href="<?= SITE_URL ?>/classes.php">Browse classes</a></p>
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
            <!-- Live Classes -->
            <div class="card mb-4 border-success">
                <div class="card-header bg-success bg-opacity-10 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-success"><i class="bi bi-camera-video-fill me-2"></i>Live Classes</h6>
                    <a href="<?= SITE_URL ?>/live-sessions.php" class="btn btn-sm btn-success">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($upcomingLive)): ?>
                    <p class="text-muted p-3 mb-0 small">No upcoming live sessions.</p>
                    <?php else: ?>
                    <?php foreach ($upcomingLive as $ls):
                        $isLive = ($ls['status'] === 'live');
                        $lsTs   = strtotime($ls['scheduled_at']);
                    ?>
                    <a href="<?= SITE_URL ?>/live-class.php?id=<?= $ls['id'] ?>" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark" style="transition:.1s" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background=''">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:36px;height:36px;background:<?= $isLive ? '#dcfce7' : '#dbeafe' ?>">
                            <i class="bi bi-camera-video-fill" style="color:<?= $isLive ? '#16a34a' : '#2563eb' ?>;font-size:.85rem"></i>
                        </div>
                        <div style="min-width:0">
                            <div class="fw-semibold text-truncate" style="font-size:.82rem"><?= htmlspecialchars($ls['title']) ?></div>
                            <div class="text-muted" style="font-size:.72rem">
                                <?php if ($isLive): ?>
                                <span class="text-success fw-bold">&#11044; Live now</span>
                                <?php else: ?>
                                <?= date('d M, g:i A', $lsTs) ?>
                                <?php endif; ?>
                                &mdash; <?= htmlspecialchars($ls['class_title']) ?>
                            </div>
                        </div>
                        <?php if ($isLive): ?>
                        <span class="badge bg-success ms-auto flex-shrink-0" style="font-size:.65rem">Join</span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
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
