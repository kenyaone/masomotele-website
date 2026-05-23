<?php
/**
 * M.T.T.I LMS - Lesson Browser (Student-facing)
 * Tree view: Class → Subject → Topic → Lessons with thumbnails/previews
 * Place in: /var/www/html/mtti-lms/lessons.php
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$role = $auth->getRole();

// Get classes (enrolled for students, all for admin/teacher)
if ($role === 'student') {
    $classes = $db->fetchAll("SELECT c.id, c.title, c.curriculum_type, c.grade_level FROM classes c INNER JOIN enrolments e ON c.id = e.class_id WHERE e.user_id = ? AND c.status = 'active' ORDER BY c.title", [$userId]);
} else {
    $classes = $db->fetchAll("SELECT id, title, curriculum_type, grade_level FROM classes WHERE status='active' ORDER BY title");
}

$selectedClass = intval($_GET['class_id'] ?? ($classes[0]['id'] ?? 0));
$selectedSubject = intval($_GET['subject_id'] ?? 0);

// Get subjects tree for selected class
$subjects = [];
$lessons = [];
if ($selectedClass) {
    $tops = $db->fetchAll("SELECT id, title, level_type FROM subjects WHERE class_id=? AND (parent_id IS NULL OR parent_id=0) AND status='active' ORDER BY sort_order", [$selectedClass]);
    foreach ($tops as &$t) {
        $t['children'] = $db->fetchAll("SELECT id, title, level_type FROM subjects WHERE parent_id=? AND status='active' ORDER BY sort_order", [$t['id']]);
        // Count lessons per subject
        $ids = [$t['id']];
        foreach ($t['children'] as $c) $ids[] = $c['id'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $t['lesson_count'] = (int)$db->fetchColumn("SELECT COUNT(*) FROM lessons WHERE class_id=? AND subject_id IN ($placeholders) AND status='published'", array_merge([$selectedClass], $ids));
    }
    $subjects = $tops;

    // Get lessons for selected subject (or all if none selected)
    if ($selectedSubject) {
        // Get children of selected subject too
        $childIds = $db->fetchAll("SELECT id FROM subjects WHERE parent_id=?", [$selectedSubject]);
        $ids = [$selectedSubject];
        foreach ($childIds as $c) $ids[] = $c['id'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $lessons = $db->fetchAll(
            "SELECT l.*, s.title as subject_name,
                    (SELECT filepath FROM lesson_files WHERE lesson_id=l.id LIMIT 1) as file_path,
                    (SELECT filetype FROM lesson_files WHERE lesson_id=l.id LIMIT 1) as file_type,
                    (SELECT original_name FROM lesson_files WHERE lesson_id=l.id LIMIT 1) as file_name,
                    (SELECT filesize FROM lesson_files WHERE lesson_id=l.id LIMIT 1) as file_size
             FROM lessons l LEFT JOIN subjects s ON l.subject_id=s.id
             WHERE l.class_id=? AND l.subject_id IN ($placeholders) AND l.status='published'
             ORDER BY l.sort_order",
            array_merge([$selectedClass], $ids)
        );
    } else {
        $lessons = $db->fetchAll(
            "SELECT l.*, s.title as subject_name,
                    (SELECT filepath FROM lesson_files WHERE lesson_id=l.id LIMIT 1) as file_path,
                    (SELECT filetype FROM lesson_files WHERE lesson_id=l.id LIMIT 1) as file_type,
                    (SELECT original_name FROM lesson_files WHERE lesson_id=l.id LIMIT 1) as file_name,
                    (SELECT filesize FROM lesson_files WHERE lesson_id=l.id LIMIT 1) as file_size
             FROM lessons l LEFT JOIN subjects s ON l.subject_id=s.id
             WHERE l.class_id=? AND l.status='published'
             ORDER BY s.sort_order, l.sort_order",
            [$selectedClass]
        );
    }

    // Check completions
    $completedIds = [];
    if ($role === 'student') {
        $comps = $db->fetchAll("SELECT lesson_id FROM completions WHERE student_id=? AND class_id=?", [$userId, $selectedClass]);
        foreach ($comps as $c) $completedIds[] = $c['lesson_id'];
    }
}

require_once __DIR__ . '/templates/header.php';
?>
<style>
.lesson-browser { min-height: 80vh; }
.sidebar-tree { background: #f8f9fa; border-radius: 1rem; padding: 1rem; }
.tree-class { font-weight: 700; font-size: 0.95rem; padding: 6px 10px; border-radius: 0.5rem; margin-bottom: 4px; cursor: pointer; transition: all 0.2s; }
.tree-class:hover { background: #e9ecef; }
.tree-class.active { background: #1a5632; color: #fff; }
.tree-subject { font-size: 0.85rem; padding: 5px 10px 5px 20px; border-radius: 0.4rem; cursor: pointer; transition: all 0.2s; display: flex; justify-content: space-between; align-items: center; }
.tree-subject:hover { background: #e3f2fd; }
.tree-subject.active { background: #0d6efd; color: #fff; }
.tree-subject .badge { font-size: 0.65rem; }
.tree-topic { font-size: 0.78rem; padding: 3px 10px 3px 36px; color: #666; cursor: pointer; border-radius: 0.3rem; }
.tree-topic:hover { background: #f0f7ff; color: #333; }
.tree-topic.active { background: #cfe2ff; color: #0d6efd; font-weight: 600; }
.lesson-card { border: 1px solid #e9ecef; border-radius: 0.8rem; overflow: hidden; transition: all 0.2s; cursor: pointer; height: 100%; }
.lesson-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); transform: translateY(-2px); }
.lesson-card .thumb { width: 100%; height: 140px; object-fit: cover; background: #e9ecef; display: flex; align-items: center; justify-content: center; }
.lesson-card .thumb i { font-size: 2.5rem; color: #adb5bd; }
.lesson-card .thumb.video { background: linear-gradient(135deg, #1a237e, #283593); }
.lesson-card .thumb.video i { color: #fff; }
.lesson-card .thumb.pdf { background: linear-gradient(135deg, #b71c1c, #c62828); }
.lesson-card .thumb.pdf i { color: #fff; }
.lesson-card .thumb.html { background: linear-gradient(135deg, #e65100, #ef6c00); }
.lesson-card .thumb.html i { color: #fff; }
.lesson-card .thumb.image { background: linear-gradient(135deg, #1b5e20, #2e7d32); }
.lesson-card .thumb.image i { color: #fff; }
.lesson-card .body { padding: 10px 12px; }
.lesson-card .body h6 { font-size: 0.85rem; margin: 0 0 4px; line-height: 1.3; }
.lesson-card .body .meta { font-size: 0.7rem; color: #888; }
.lesson-card.completed { border-color: #28a745; }
.lesson-card.completed::before { content: '✓'; position: absolute; top: 8px; right: 8px; background: #28a745; color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; z-index: 1; }
.no-lessons { text-align: center; padding: 3rem; color: #999; }
.breadcrumb-nav { font-size: 0.85rem; margin-bottom: 1rem; }
.all-link { font-size: 0.8rem; cursor: pointer; color: #0d6efd; }
.all-link:hover { text-decoration: underline; }
</style>

<div class="container-fluid lesson-browser">
    <div class="row g-3">
        <!-- Sidebar Tree -->
        <div class="col-lg-3">
            <div class="sidebar-tree">
                <h6 class="fw-bold mb-3"><i class="bi bi-book me-2"></i>My Classes</h6>
                <?php foreach ($classes as $cls): ?>
                <a href="?class_id=<?= $cls['id'] ?>" class="tree-class d-block text-decoration-none <?= $cls['id'] == $selectedClass ? 'active' : 'text-dark' ?>">
                    <i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($cls['title']) ?>
                </a>
                <?php if ($cls['id'] == $selectedClass && !empty($subjects)): ?>
                    <div class="ms-1 mb-2">
                    <div class="all-link mb-1 ps-2" onclick="location.href='?class_id=<?= $selectedClass ?>'">
                        <i class="bi bi-grid me-1"></i>All Lessons
                    </div>
                    <?php foreach ($subjects as $sub): ?>
                        <a href="?class_id=<?= $selectedClass ?>&subject_id=<?= $sub['id'] ?>"
                           class="tree-subject d-block text-decoration-none <?= $sub['id'] == $selectedSubject ? 'active' : 'text-dark' ?>">
                            <span><i class="bi bi-folder2 me-1"></i><?= htmlspecialchars($sub['title']) ?></span>
                            <?php if ($sub['lesson_count'] > 0): ?>
                            <span class="badge bg-<?= $sub['id'] == $selectedSubject ? 'light text-primary' : 'secondary' ?>"><?= $sub['lesson_count'] ?></span>
                            <?php endif; ?>
                        </a>
                        <?php if ($sub['id'] == $selectedSubject && !empty($sub['children'])): ?>
                            <?php foreach ($sub['children'] as $child): ?>
                            <a href="?class_id=<?= $selectedClass ?>&subject_id=<?= $child['id'] ?>"
                               class="tree-topic d-block text-decoration-none <?= $child['id'] == $selectedSubject ? 'active' : '' ?>">
                                ↳ <?= htmlspecialchars($child['title']) ?>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <?php
            $currentClassName = '';
            foreach ($classes as $c) { if ($c['id'] == $selectedClass) { $currentClassName = $c['title']; break; } }
            $currentSubjectName = '';
            if ($selectedSubject) {
                $srow = $db->fetchOne("SELECT title FROM subjects WHERE id=?", [$selectedSubject]);
                $currentSubjectName = $srow['title'] ?? '';
            }
            ?>
            <div class="breadcrumb-nav">
                <a href="?class_id=<?= $selectedClass ?>" class="text-decoration-none"><?= htmlspecialchars($currentClassName) ?></a>
                <?php if ($currentSubjectName): ?>
                    <i class="bi bi-chevron-right mx-1 text-muted"></i>
                    <span class="fw-bold"><?= htmlspecialchars($currentSubjectName) ?></span>
                <?php endif; ?>
                <span class="text-muted ms-2">(<?= count($lessons) ?> lesson<?= count($lessons) !== 1 ? 's' : '' ?>)</span>
            </div>

            <?php if (empty($lessons)): ?>
                <div class="no-lessons">
                    <i class="bi bi-journal-x" style="font-size:3rem;"></i>
                    <h5 class="mt-3">No lessons yet</h5>
                    <p class="text-muted">
                        <?= $selectedSubject ? 'No lessons in this topic yet.' : 'No lessons have been uploaded to this class.' ?>
                        <?php if (in_array($role, ['admin', 'teacher'])): ?>
                            <br><a href="<?= SITE_URL ?>/smart-upload.php" class="btn btn-primary btn-sm mt-2"><i class="bi bi-lightning-charge me-1"></i>Upload Lessons</a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                <?php foreach ($lessons as $lesson):
                    $ft = $lesson['file_type'] ?? '';
                    $isVideo = in_array($ft, ['mp4','webm','ogg','avi','mkv']);
                    $isPdf = $ft === 'pdf';
                    $isImage = in_array($ft, ['jpg','jpeg','png','gif','webp']);
                    $isHtml = in_array($ft, ['html','htm']);
                    $thumbClass = $isVideo ? 'video' : ($isPdf ? 'pdf' : ($isImage ? 'image' : ($isHtml ? 'html' : '')));
                    $icon = $isVideo ? 'bi-play-circle-fill' : ($isPdf ? 'bi-file-earmark-pdf-fill' : ($isImage ? 'bi-image' : ($isHtml ? 'bi-code-slash' : 'bi-file-earmark')));
                    $isCompleted = in_array($lesson['id'], $completedIds);
                    $fsize = $lesson['file_size'] ? round($lesson['file_size'] / (1024*1024), 1) . ' MB' : '';

                    // Check for thumbnail
                    $thumbSrc = '';
                    if ($isVideo && $lesson['file_path']) {
                        $thumbFile = 'assets/uploads/thumbnails/' . pathinfo(basename($lesson['file_path']), PATHINFO_FILENAME) . '.jpg';
                        if (file_exists(__DIR__ . '/' . $thumbFile)) {
                            $thumbSrc = SITE_URL . '/' . $thumbFile;
                        }
                    } elseif ($isImage && $lesson['file_path']) {
                        $thumbSrc = SITE_URL . '/' . $lesson['file_path'];
                    }
                ?>
                <div class="col-sm-6 col-md-4 col-xl-3">
                    <a href="<?= SITE_URL ?>/lesson.php?id=<?= $lesson['id'] ?>" class="text-decoration-none">
                    <div class="lesson-card position-relative <?= $isCompleted ? 'completed' : '' ?>">
                        <?php if ($thumbSrc): ?>
                            <img src="<?= $thumbSrc ?>" class="thumb" alt="" style="object-fit:cover;">
                        <?php else: ?>
                            <div class="thumb <?= $thumbClass ?>"><i class="bi <?= $icon ?>"></i></div>
                        <?php endif; ?>
                        <div class="body">
                            <h6 class="text-dark"><?= htmlspecialchars($lesson['title']) ?></h6>
                            <div class="meta">
                                <?php if ($lesson['subject_name']): ?>
                                    <i class="bi bi-folder2 me-1"></i><?= htmlspecialchars($lesson['subject_name']) ?>
                                <?php endif; ?>
                                <?php if ($fsize): ?>
                                    <span class="ms-1"><?= $fsize ?></span>
                                <?php endif; ?>
                                <?php if ($isVideo): ?>
                                    <i class="bi bi-camera-video ms-1"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    </a>
                </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
