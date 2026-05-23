<?php
/**
 * M.T.T.I LMS - Teacher Portal
 * Clean flow: Class → Subjects → Topics → Lessons (with add/edit at each level)
 * Place in: /var/www/html/mtti-lms/teacher-portal.php
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
if (!in_array($auth->getRole(), ['admin', 'teacher'])) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }
$db = Database::getInstance();
$userId = $auth->getUserId();
$role = $auth->getRole();

$view = $_GET['view'] ?? 'classes';
$classId = intval($_GET['class_id'] ?? 0);
$subjectId = intval($_GET['subject_id'] ?? 0);
$topicId = intval($_GET['topic_id'] ?? 0);
$msg = $_GET['msg'] ?? '';

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redir = $_POST['redirect'] ?? '';

    if ($action === 'add_lesson') {
        $cid = intval($_POST['class_id']);
        $sid = intval($_POST['subject_id']) ?: null;
        $title = trim($_POST['lesson_title']);
        $maxSort = (int)$db->fetchColumn("SELECT COALESCE(MAX(sort_order),0) FROM lessons WHERE class_id=?", [$cid]);

        $lessonId = $db->insert('lessons', [
            'class_id' => $cid, 'subject_id' => $sid, 'title' => $title,
            'content_html' => '', 'content_type' => $_POST['content_type'] ?? null, 'sort_order' => $maxSort + 1,
            'status' => 'published', 'created_at' => date('Y-m-d H:i:s')
        ]);

        // Handle file upload
        if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['error'] === UPLOAD_ERR_OK) {
            $origName = $_FILES['lesson_file']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $dirs = ['pdf'=>'files','doc'=>'files','docx'=>'files','mp4'=>'videos','webm'=>'videos','jpg'=>'images','jpeg'=>'images','png'=>'images','html'=>'html','htm'=>'html'];
            $dir = 'assets/uploads/' . ($dirs[$ext] ?? 'files') . '/';
            @mkdir(__DIR__ . '/' . $dir, 0755, true);
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $destPath = $dir . $safeName;
            if (move_uploaded_file($_FILES['lesson_file']['tmp_name'], __DIR__ . '/' . $destPath)) {
                $db->insert('lesson_files', [
                    'lesson_id' => $lessonId, 'original_name' => $origName,
                    'filename' => $safeName, 'filepath' => $destPath,
                    'filetype' => $ext, 'filesize' => $_FILES['lesson_file']['size'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                // Auto-generate content_html
                $url = SITE_URL . '/' . $destPath;
                $html = '';
                if (in_array($ext, ['mp4','webm'])) $html = "<video controls width='100%'><source src='$url' type='video/$ext'></video>";
                elseif ($ext === 'pdf') $html = "<iframe src='$url' width='100%' height='800px' style='border:none'></iframe>";
                elseif (in_array($ext, ['html','htm'])) $html = "<iframe src='$url' width='100%' height='800px' style='border:none'></iframe>";
                elseif (in_array($ext, ['jpg','jpeg','png','gif'])) $html = "<img src='$url' class='img-fluid'>";
                if ($html) $db->query("UPDATE lessons SET content_html=? WHERE id=?", [$html, $lessonId]);
            }
        }
        $msg = 'Lesson added!';
        header('Location: ' . $redir . '&msg=' . urlencode($msg)); exit;
    }

    if ($action === 'delete_lesson') {
        $lid = intval($_POST['lesson_id']);
        $files = $db->fetchAll("SELECT filepath FROM lesson_files WHERE lesson_id=?", [$lid]);
        foreach ($files as $f) { @unlink(__DIR__ . '/' . $f['filepath']); }
        $db->query("DELETE FROM lesson_files WHERE lesson_id=?", [$lid]);
        $db->query("DELETE FROM lessons WHERE id=?", [$lid]);
        $msg = 'Lesson deleted';
        header('Location: ' . $redir . '&msg=' . urlencode($msg)); exit;
    }

    if ($action === 'add_subject') {
        $cid = intval($_POST['class_id']);
        $pid = intval($_POST['parent_id']) ?: null;
        $title = trim($_POST['title']);
        $maxSort = (int)$db->fetchColumn("SELECT COALESCE(MAX(sort_order),0) FROM subjects WHERE class_id=? AND " . ($pid ? "parent_id=$pid" : "parent_id IS NULL"), [$cid]);
        $db->insert('subjects', [
            'class_id' => $cid, 'parent_id' => $pid, 'title' => $title,
            'level_type' => $pid ? 'topic' : 'subject',
            'sort_order' => $maxSort + 1, 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')
        ]);
        $msg = ($pid ? 'Topic' : 'Subject') . ' added!';
        header('Location: ' . $redir . '&msg=' . urlencode($msg)); exit;
    }

    if ($action === 'delete_subject') {
        $sid = intval($_POST['subject_id']);
        $db->query("UPDATE lessons SET subject_id=NULL WHERE subject_id=?", [$sid]);
        $children = $db->fetchAll("SELECT id FROM subjects WHERE parent_id=?", [$sid]);
        foreach ($children as $ch) { $db->query("UPDATE lessons SET subject_id=NULL WHERE subject_id=?", [$ch['id']]); }
        $db->query("DELETE FROM subjects WHERE parent_id=?", [$sid]);
        $db->query("DELETE FROM subjects WHERE id=?", [$sid]);
        $msg = 'Deleted';
        header('Location: ' . $redir . '&msg=' . urlencode($msg)); exit;
    }

    if ($action === 'quick_quiz') {
        $cid = intval($_POST['class_id']);
        $title = trim($_POST['quiz_title']);
        $qid = $db->insert('quizzes', [
            'class_id' => $cid, 'title' => $title,
            'time_limit' => intval($_POST['time_limit'] ?? 30),
            'pass_mark' => intval($_POST['pass_mark'] ?? 50),
            'status' => 'active', 'created_at' => date('Y-m-d H:i:s')
        ]);
        // Add questions
        $questions = $_POST['questions'] ?? [];
        foreach ($questions as $i => $q) {
            $qt = trim($q['text'] ?? '');
            if (!$qt) continue;
            $opts = array_filter([$q['opt_a'] ?? '', $q['opt_b'] ?? '', $q['opt_c'] ?? '', $q['opt_d'] ?? ''], fn($o) => trim($o));
            $db->insert('questions', [
                'quiz_id' => $qid, 'type' => !empty($opts) ? 'mcq' : 'short_answer',
                'question_text' => $qt,
                'options_json' => !empty($opts) ? json_encode(array_values($opts)) : null,
                'correct_answer' => trim($q['answer'] ?? ''),
                'points' => intval($q['marks'] ?? 1),
                'sort_order' => $i + 1, 'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        $msg = "Quiz '$title' created with " . count(array_filter($questions, fn($q) => trim($q['text'] ?? ''))) . " questions!";
        header('Location: ' . $redir . '&msg=' . urlencode($msg)); exit;
    }
}

$classes = $db->fetchAll("SELECT c.*, (SELECT COUNT(*) FROM enrolments WHERE class_id=c.id) as students, (SELECT COUNT(*) FROM lessons WHERE class_id=c.id AND status='published') as lessons FROM classes c WHERE c.status='active' ORDER BY c.title");
$classInfo = $classId ? $db->fetchOne("SELECT * FROM classes WHERE id=?", [$classId]) : null;

$pageTitle = 'Teacher Portal - ' . SITE_NAME;
require_once __DIR__ . '/templates/header.php';
?>
<style>
:root{--bg:#eef3e8;--pri:#1a5632;--pri2:#2d7a4c;--gold:#e8a423;--gold2:#fef9e7;--gold3:#f5c842;--txt:#1e293b;--mut:#64748b;--brd:#c8dcc0;--grn:#16a34a;--amb:#f59e0b;--red:#dc2626}
body{background:var(--bg)}
.tp-hero{background:linear-gradient(135deg,var(--pri),var(--pri2));color:#fff;padding:16px 20px;border-radius:0 0 14px 14px;margin:-1rem -12px 16px;border-bottom:4px solid var(--gold)}
.tp-hero h4{margin:0;font-weight:800;font-size:1.1rem}.tp-hero .sub{opacity:.8;font-size:.82rem;color:var(--gold2)}
.tp-bc{display:flex;align-items:center;gap:6px;font-size:.82rem;margin-bottom:14px;flex-wrap:wrap}
.tp-bc a{color:var(--pri);text-decoration:none;font-weight:700}.tp-bc a:hover{text-decoration:underline;color:var(--gold)}.tp-bc .sep{color:var(--mut)}
.card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
.s-card{background:#fff;border-radius:14px;border:1px solid var(--brd);border-left:5px solid var(--gold);padding:18px;text-decoration:none;color:var(--txt);transition:all .25s;position:relative;box-shadow:0 2px 8px rgba(26,86,50,.06)}
.s-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(26,86,50,.15);border-color:var(--pri)}
.s-card h6{font-weight:700;font-size:.95rem;margin:0 0 6px;color:var(--pri)}.s-card .meta{font-size:.75rem;color:var(--mut)}
.s-card .icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:10px}
.add-card{border:3px dashed var(--gold);background:var(--gold2);display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;color:var(--pri);min-height:120px;border-left:3px dashed var(--gold)}
.add-card:hover{border-color:var(--pri);background:#e8f5ee}
.add-card i{font-size:1.8rem;margin-bottom:4px}.add-card span{font-size:.85rem;font-weight:700}
.lesson-row{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#fff;border-radius:12px;border:1px solid var(--brd);margin-bottom:8px;transition:all .2s;border-left:4px solid transparent}
.lesson-row:hover{border-left-color:var(--gold);background:var(--gold2);box-shadow:0 2px 8px rgba(0,0,0,.05)}
.lesson-row .num{width:30px;height:30px;border-radius:50%;background:var(--pri);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.78rem;flex-shrink:0}
.lesson-row .lt{flex:1;font-weight:600;font-size:.88rem}.lesson-row .lm{font-size:.72rem;color:var(--mut)}.lesson-row .type-icon{font-size:.85rem;color:var(--gold)}
.btn-sm-act{padding:5px 12px;border-radius:8px;font-size:.75rem;font-weight:700;border:2px solid var(--pri);background:#fff;color:var(--pri);cursor:pointer;transition:all .15s;text-decoration:none}
.btn-sm-act:hover{background:var(--pri);color:#fff}
.btn-sm-del{padding:5px 10px;border-radius:8px;font-size:.75rem;border:2px solid var(--red);background:#fff;color:var(--red);cursor:pointer}.btn-sm-del:hover{background:var(--red);color:#fff}
.quick-form{background:#fff;border-radius:14px;border:1px solid var(--brd);border-left:5px solid var(--pri);padding:18px;margin-bottom:16px;box-shadow:0 2px 8px rgba(26,86,50,.06)}
.quick-form h6{font-weight:800;font-size:.92rem;margin:0 0 14px;display:flex;align-items:center;gap:8px;color:var(--pri)}
.quick-form .form-control,.quick-form .form-select{font-size:.88rem;border-radius:10px;border:2px solid var(--brd)}.quick-form .form-control:focus,.quick-form .form-select:focus{border-color:var(--pri)}
.quick-form .btn{font-size:.88rem;border-radius:10px;font-weight:700}
.tools-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.tool-link{padding:10px 18px;background:linear-gradient(135deg,#fff,var(--gold2));border:2px solid var(--brd);border-radius:12px;text-decoration:none;color:var(--pri);font-weight:700;font-size:.85rem;transition:all .2s;display:inline-flex;align-items:center;gap:8px;box-shadow:0 2px 6px rgba(0,0,0,.04)}
.tool-link:hover{border-color:var(--pri);background:var(--pri);color:#fff;transform:translateY(-2px);box-shadow:0 4px 12px rgba(26,86,50,.2)}
.tool-link.active{background:var(--pri);color:#fff;border-color:var(--pri)}
.cat-label{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--pri);margin:18px 0 8px;padding:4px 10px;background:var(--gold2);border-radius:6px;display:inline-block}
.stat-mini{display:inline-flex;align-items:center;gap:4px;font-size:.72rem;padding:3px 10px;border-radius:10px;background:var(--gold2);color:var(--pri);font-weight:700}
.empty{text-align:center;padding:40px;color:var(--mut)}.empty i{font-size:2.5rem;color:var(--gold)}
.quiz-q-row{background:var(--gold2);border:2px solid var(--brd);border-radius:12px;padding:14px;margin-bottom:10px;border-left:4px solid var(--pri)}
.quiz-q-row .q-num{font-weight:800;color:var(--gold);font-size:.88rem}
</style>

<div class="container-fluid">
<div class="tp-hero">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4><i class="bi bi-easel me-2"></i>Teacher Portal</h4>
            <div class="sub">Manage content: Class → Subject → Topic → Lessons</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= SITE_URL ?>/admin/teacher-dashboard.php" class="tool-link" style="background:#e8a423;color:#000;border-color:#e8a423;font-weight:700"><i class="bi bi-graph-up"></i>Analytics</a>
            <a href="<?= SITE_URL ?>/dashboard.php" class="tool-link" style="background:#e8a423;color:#000;border-color:#e8a423;font-weight:700"><i class="bi bi-house"></i>Dashboard</a>
        </div>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show py-2" style="font-size:.85rem;border-radius:10px"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:.7rem"></button></div><?php endif; ?>

<?php if ($view === 'classes'): ?>
<!-- ═══ CLASSES ═══ -->
<div class="tp-bc"><span style="font-weight:700">My Classes</span></div>
<div class="tools-bar">
    
    
    
    
    
    <a href="<?= SITE_URL ?>/teacher-upload.php" class="tool-link" style="background:#0a5e2a;color:#fff;border-color:#0a5e2a;font-weight:700"><i class="bi bi-upload"></i>Upload Content</a>
    <a href="<?= SITE_URL ?>/admin/assignments.php" class="tool-link"><i class="bi bi-clipboard-check text-danger"></i>Assignments</a>
</div>
<div class="card-grid">
    <?php foreach ($classes as $c): ?>
    <a href="?view=subjects&class_id=<?= $c['id'] ?>" class="s-card">
        <div class="icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-book"></i></div>
        <h6><?= htmlspecialchars($c['title']) ?></h6>
        <div class="meta"><?= $c['students'] ?> students · <?= $c['lessons'] ?> lessons</div>
    </a>
    <?php endforeach; ?>
</div>

<?php elseif ($view === 'subjects' && $classId): ?>
<!-- ═══ SUBJECTS ═══ -->
<?php
$subjects = $db->fetchAll("SELECT s.*, (SELECT COUNT(*) FROM subjects WHERE parent_id=s.id AND status='active') as topic_count FROM subjects s WHERE s.class_id=? AND (s.parent_id IS NULL OR s.parent_id=0) AND s.status='active' ORDER BY s.sort_order", [$classId]);
$colors = ['#2563eb','#dc2626','#16a34a','#7c3aed','#ea580c','#0891b2','#be185d','#4f46e5','#b45309','#0d9488','#6d28d9','#c2410c','#0369a1','#a21caf'];
$icons = ['bi-calculator','bi-book','bi-chat-text','bi-bug','bi-droplet','bi-lightning','bi-globe','bi-geo-alt','bi-heart','bi-flower1','bi-pc-display','bi-house','bi-palette','bi-translate'];
$redir = "?view=subjects&class_id=$classId";
?>
<div class="tp-bc">
    <a href="?view=classes">Classes</a><span class="sep">›</span>
    <span style="font-weight:700"><?= htmlspecialchars($classInfo['title']) ?></span>
</div>
<div class="card-grid">
    <?php foreach ($subjects as $si => $sub):
        $col = $colors[$si % count($colors)];
        $ic = $icons[$si % count($icons)];
        // Count all lessons under this subject + children
        $lCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM lessons WHERE status='published' AND (subject_id=? OR subject_id IN (SELECT id FROM subjects WHERE parent_id=?))", [$sub['id'], $sub['id']]);
    ?>
    <a href="?view=topics&class_id=<?= $classId ?>&subject_id=<?= $sub['id'] ?>" class="s-card">
        <div class="icon" style="background:<?= $col ?>15;color:<?= $col ?>"><i class="bi <?= $ic ?>"></i></div>
        <h6><?= htmlspecialchars($sub['title']) ?></h6>
        <div class="meta"><?= $sub['topic_count'] ?> topics · <?= $lCount ?> lessons</div>
    </a>
    <?php endforeach; ?>
    <div class="s-card add-card" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
        <i class="bi bi-plus-circle"></i><span>Add Subject</span>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="add_subject"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="redirect" value="<?= $redir ?>">
<div class="modal-header"><h6 class="modal-title fw-bold">Add Subject</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="text" name="title" class="form-control" required placeholder="e.g. Biology, French, Music"></div>
<div class="modal-footer"><button class="btn btn-primary">Add Subject</button></div>
</form></div></div></div>

<?php elseif ($view === 'topics' && $classId && $subjectId): ?>
<!-- ═══ TOPICS + LESSONS ═══ -->
<?php
$subjectInfo = $db->fetchOne("SELECT * FROM subjects WHERE id=?", [$subjectId]);
$topics = $db->fetchAll("SELECT s.*, (SELECT COUNT(*) FROM lessons WHERE subject_id=s.id AND status='published') as lesson_count FROM subjects s WHERE s.parent_id=? AND s.status='active' ORDER BY s.sort_order", [$subjectId]);
$directLessons = $db->fetchAll("SELECT l.*, f.filetype, f.original_name FROM lessons l LEFT JOIN lesson_files f ON f.lesson_id=l.id WHERE l.subject_id=? AND l.class_id=? AND l.status='published' ORDER BY l.sort_order", [$subjectId, $classId]);
$quizzes = $db->fetchAll("SELECT * FROM quizzes WHERE class_id=? AND status='active'", [$classId]);
$redir = "?view=topics&class_id=$classId&subject_id=$subjectId";
?>
<div class="tp-bc">
    <a href="?view=classes">Classes</a><span class="sep">›</span>
    <a href="?view=subjects&class_id=<?= $classId ?>"><?= htmlspecialchars($classInfo['title']) ?></a><span class="sep">›</span>
    <span style="font-weight:700"><?= htmlspecialchars($subjectInfo['title']) ?></span>
</div>

<!-- Topics -->
<div class="cat-label">Topics in <?= htmlspecialchars($subjectInfo['title']) ?></div>
<div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
    <?php foreach ($topics as $t): ?>
    <a href="?view=topic_lessons&class_id=<?= $classId ?>&subject_id=<?= $subjectId ?>&topic_id=<?= $t['id'] ?>" class="s-card" style="padding:12px">
        <h6 style="font-size:.85rem"><?= htmlspecialchars($t['title']) ?></h6>
        <div class="meta"><?= $t['lesson_count'] ?> lessons</div>
    </a>
    <?php endforeach; ?>
    <div class="s-card add-card" style="min-height:70px;padding:12px" data-bs-toggle="modal" data-bs-target="#addTopicModal">
        <i class="bi bi-plus-circle"></i><span>Add Topic</span>
    </div>
</div>

<!-- Direct lessons under subject -->
<?php if (!empty($directLessons)): ?>
<div class="cat-label">Lessons (directly under <?= htmlspecialchars($subjectInfo['title']) ?>)</div>
<?php foreach ($directLessons as $i => $l):
    $ft = $l['filetype'] ?? ''; $ti = 'bi-file-text';
    if (in_array($ft,['mp4','webm','video'])) $ti='bi-camera-video'; elseif ($ft==='pdf') $ti='bi-file-pdf'; elseif (in_array($ft,['html','htm'])) $ti='bi-code-slash';
?>
<div class="lesson-row">
    <div class="num"><?= $i+1 ?></div>
    <i class="bi <?= $ti ?> type-icon"></i>
    <span class="lt"><?= htmlspecialchars($l['title']) ?></span>
    <a href="<?= SITE_URL ?>/class.php?id=<?= $classId ?>&lesson=<?= $l['id'] ?>" target="_blank" class="btn-sm-act"><i class="bi bi-eye"></i></a>
    <form method="POST" style="margin:0" onsubmit="return confirm('Delete this lesson?')"><input type="hidden" name="action" value="delete_lesson"><input type="hidden" name="lesson_id" value="<?= $l['id'] ?>"><input type="hidden" name="redirect" value="<?= $redir ?>"><button class="btn-sm-del"><i class="bi bi-trash"></i></button></form>
</div>
<?php endforeach; endif; ?>

<!-- Quick Add Lesson -->
<div class="quick-form" style="margin-top:16px">
    <h6><i class="bi bi-plus-circle text-success"></i>Quick Add Lesson</h6>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_lesson"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="redirect" value="<?= $redir ?>">
        <div class="row g-2">
            <div class="col-md-5"><input type="text" name="lesson_title" class="form-control" required placeholder="Lesson title"></div>
            <div class="col-md-3"><select name="content_type" class="form-select"><option value="notes">Notes</option><option value="interactive">Interactive</option><option value="video">Video</option><option value="homework">Homework</option><option value="past_paper">Past Paper</option><option value="assignment">Assignment</option></select></div><div class="col-md-4"><input type="file" name="lesson_file" class="form-control" accept=".pdf,.mp4,.webm,.html,.htm,.jpg,.png,.doc,.docx,.ppt,.pptx"></div>
            <div class="col-md-2"><button class="btn btn-success w-100"><i class="bi bi-plus me-1"></i>Add</button></div>
        </div>
    </form>
</div>
<!-- Modals -->
<div class="modal fade" id="addTopicModal"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="add_subject"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="parent_id" value="<?= $subjectId ?>"><input type="hidden" name="redirect" value="<?= $redir ?>">
<div class="modal-header"><h6 class="modal-title fw-bold">Add Topic to <?= htmlspecialchars($subjectInfo['title']) ?></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="text" name="title" class="form-control" required placeholder="e.g. Genetics, Quadratic Equations"></div>
<div class="modal-footer"><button class="btn btn-primary">Add Topic</button></div>
</form></div></div></div>

<?php elseif ($view === 'topic_lessons' && $classId && $subjectId && $topicId): ?>
<!-- ═══ LESSONS IN A TOPIC ═══ -->
<?php
$subjectInfo = $db->fetchOne("SELECT * FROM subjects WHERE id=?", [$subjectId]);
$topicInfo = $db->fetchOne("SELECT * FROM subjects WHERE id=?", [$topicId]);
$lessons = $db->fetchAll("SELECT l.*, f.filetype, f.original_name FROM lessons l LEFT JOIN lesson_files f ON f.lesson_id=l.id WHERE l.subject_id=? AND l.class_id=? AND l.status='published' ORDER BY l.sort_order", [$topicId, $classId]);
// Also check sub-topics
$subTopics = $db->fetchAll("SELECT * FROM subjects WHERE parent_id=? AND status='active' ORDER BY sort_order", [$topicId]);
$redir = "?view=topic_lessons&class_id=$classId&subject_id=$subjectId&topic_id=$topicId";
?>
<div class="tp-bc">
    <a href="?view=classes">Classes</a><span class="sep">›</span>
    <a href="?view=subjects&class_id=<?= $classId ?>"><?= htmlspecialchars($classInfo['title']) ?></a><span class="sep">›</span>
    <a href="?view=topics&class_id=<?= $classId ?>&subject_id=<?= $subjectId ?>"><?= htmlspecialchars($subjectInfo['title']) ?></a><span class="sep">›</span>
    <span style="font-weight:700"><?= htmlspecialchars($topicInfo['title']) ?></span>
</div>

<?php if (!empty($subTopics)): ?>
<div class="cat-label">Sub-topics</div>
<div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
    <?php foreach ($subTopics as $st):
        $stCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM lessons WHERE subject_id=? AND status='published'", [$st['id']]);
    ?>
    <a href="?view=topic_lessons&class_id=<?= $classId ?>&subject_id=<?= $subjectId ?>&topic_id=<?= $st['id'] ?>" class="s-card" style="padding:12px">
        <h6 style="font-size:.85rem"><?= htmlspecialchars($st['title']) ?></h6>
        <div class="meta"><?= $stCount ?> lessons</div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="cat-label">Lessons in <?= htmlspecialchars($topicInfo['title']) ?></div>
<?php if (empty($lessons)): ?>
<div class="empty"><i class="bi bi-journal-x"></i><p>No lessons yet. Add one below.</p></div>
<?php else: ?>
<?php foreach ($lessons as $i => $l):
    $ft=$l['filetype']??'';$ti='bi-file-text';
    if(in_array($ft,['mp4','webm','video']))$ti='bi-camera-video';elseif($ft==='pdf')$ti='bi-file-pdf';elseif(in_array($ft,['html','htm']))$ti='bi-code-slash';
?>
<div class="lesson-row">
    <div class="num"><?= $i+1 ?></div>
    <i class="bi <?= $ti ?> type-icon"></i>
    <span class="lt"><?= htmlspecialchars($l['title']) ?></span>
    <?php if($l['original_name']): ?><span class="lm"><?= htmlspecialchars($l['original_name']) ?></span><?php endif; ?>
    <a href="<?= SITE_URL ?>/class.php?id=<?= $classId ?>&lesson=<?= $l['id'] ?>" target="_blank" class="btn-sm-act"><i class="bi bi-eye"></i> View</a>
    <form method="POST" style="margin:0" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_lesson"><input type="hidden" name="lesson_id" value="<?= $l['id'] ?>"><input type="hidden" name="redirect" value="<?= $redir ?>"><button class="btn-sm-del"><i class="bi bi-trash"></i></button></form>
</div>
<?php endforeach; endif; ?>

<!-- Quick Add -->
<div class="quick-form" style="margin-top:16px">
    <h6><i class="bi bi-plus-circle text-success"></i>Add Lesson to <?= htmlspecialchars($topicInfo['title']) ?></h6>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_lesson"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="subject_id" value="<?= $topicId ?>"><input type="hidden" name="redirect" value="<?= $redir ?>">
        <div class="row g-2">
            <div class="col-md-5"><input type="text" name="lesson_title" class="form-control" required placeholder="Lesson title"></div>
            <div class="col-md-3"><select name="content_type" class="form-select"><option value="notes">Notes</option><option value="interactive">Interactive</option><option value="video">Video</option><option value="homework">Homework</option><option value="past_paper">Past Paper</option><option value="assignment">Assignment</option></select></div><div class="col-md-4"><input type="file" name="lesson_file" class="form-control" accept=".pdf,.mp4,.webm,.html,.htm,.jpg,.png,.doc,.docx"></div>
            <div class="col-md-2"><button class="btn btn-success w-100"><i class="bi bi-plus me-1"></i>Add</button></div>
        </div>
    </form>
</div>

<?php endif; ?>
</div>

<script>
let qCount = 1;
function addQuizQ() {
    const c = document.getElementById('quizQuestions');
    const html = `<div class="quiz-q-row"><div class="q-num">Q${qCount+1}</div>
    <input type="text" name="questions[${qCount}][text]" class="form-control mb-1" placeholder="Question text">
    <div class="row g-1"><div class="col-6"><input type="text" name="questions[${qCount}][opt_a]" class="form-control form-control-sm" placeholder="A)"></div>
    <div class="col-6"><input type="text" name="questions[${qCount}][opt_b]" class="form-control form-control-sm" placeholder="B)"></div>
    <div class="col-6"><input type="text" name="questions[${qCount}][opt_c]" class="form-control form-control-sm" placeholder="C)"></div>
    <div class="col-6"><input type="text" name="questions[${qCount}][opt_d]" class="form-control form-control-sm" placeholder="D)"></div></div>
    <div class="row g-1 mt-1"><div class="col-8"><input type="text" name="questions[${qCount}][answer]" class="form-control form-control-sm" placeholder="Correct answer"></div>
    <div class="col-4"><input type="number" name="questions[${qCount}][marks]" class="form-control form-control-sm" value="1"></div></div></div>`;
    c.insertAdjacentHTML('beforeend', html);
    qCount++;
}
</script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
