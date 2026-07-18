<?php
/**
 * ASSESSMENTS - Unified Quiz + Assignment system
 * Replaces separate quiz.php and assignments.php
 * 
 * Deploy: sudo cp assessments.php /var/www/html/mtti-lms/assessments.php
 */

require_once __DIR__ . '/includes/init.php';



$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $auth->getUserId();
$role = $auth->getRole();
$user = ['id' => $userId, 'role' => $role, 'class_id' => 0];
if ($role === 'student') { $u = $db->fetchOne("SELECT class_id FROM lms_enrolments WHERE user_id=? AND status='active' LIMIT 1",[$userId]); $user['class_id'] = $u['class_id'] ?? 0; }
require_once __DIR__ . '/templates/header.php';


// ─────────────────────────────────────────
// HANDLE ACTIONS
// ─────────────────────────────────────────

$msg = '';
$err = '';

// ── Delete assessment
if (isset($_GET['delete']) && in_array($role, ['admin','teacher'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM assessments WHERE id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM assessment_questions WHERE assessment_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM assessment_submissions WHERE assessment_id=?")->execute([$id]);
    $msg = "Assessment deleted.";
}

// ── Create/Edit assessment (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ── SAVE assessment
    if ($_POST['action'] === 'save_assessment' && in_array($role,['admin','teacher'])) {
        $title       = trim($_POST['title'] ?? '');
        $type        = $_POST['type'] ?? 'assignment';   // quiz | assignment
        $class_id    = intval($_POST['class_id'] ?? 0);
        $subject_id  = intval($_POST['subject_id'] ?? 0);
        $lesson_id   = intval($_POST['lesson_id'] ?? 0) ?: null;
        $instructions= trim($_POST['instructions'] ?? '');
        $due_date    = $_POST['due_date'] ?? null;
        $duration    = intval($_POST['duration'] ?? 0) ?: null;
        $check_notes = isset($_POST['check_notes']) ? 1 : 0;
        $edit_id     = intval($_POST['edit_id'] ?? 0);

        // Handle file attachment for assignment type
        $attachment = null;
        if ($type === 'assignment' && isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
            $ext  = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','doc','docx','jpg','jpeg','png','zip'];
            if (in_array($ext, $allowed)) {
                $dir = 'uploads/assessments/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = uniqid('assess_') . '.' . $ext;
                move_uploaded_file($_FILES['attachment']['tmp_name'], $dir . $fname);
                $attachment = $dir . $fname;
            } else {
                $err = "File type not allowed. Use: PDF, DOC, DOCX, JPG, PNG, ZIP";
            }
        }

        if (!$err && $title && $class_id && $subject_id) {
            if ($edit_id) {
                $sql = "UPDATE assessments SET title=?,type=?,class_id=?,subject_id=?,lesson_id=?,
                        instructions=?,due_date=?,duration_mins=?,check_notes=?";
                $params = [$title,$type,$class_id,$subject_id,$lesson_id,$instructions,$due_date,$duration,$check_notes];
                if ($attachment) { $sql .= ",attachment=?"; $params[] = $attachment; }
                $sql .= " WHERE id=?"; $params[] = $edit_id;
                $pdo->prepare($sql)->execute($params);
                $msg = "Assessment updated!";
                $assessment_id = $edit_id;
            } else {
                $pdo->prepare("INSERT INTO assessments 
                    (title,type,class_id,subject_id,lesson_id,instructions,due_date,duration_mins,check_notes,attachment,created_by,created_at)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())")
                    ->execute([$title,$type,$class_id,$subject_id,$lesson_id,$instructions,$due_date,$duration,$check_notes,$attachment,$user['id']]);
                $assessment_id = $pdo->lastInsertId();
                $msg = "Assessment created! Now add questions below.";
            }
        } elseif (!$err) {
            $err = "Title, Class and Subject are required.";
        }
    }

    // ── SAVE question
    if ($_POST['action'] === 'save_question' && in_array($role,['admin','teacher'])) {
        $a_id    = intval($_POST['assessment_id'] ?? 0);
        $q_text  = trim($_POST['question_text'] ?? '');
        $q_type  = $_POST['question_type'] ?? 'mcq'; // mcq | truefalse | short | essay
        $marks   = intval($_POST['marks'] ?? 1);
        $options = json_encode([
            'a' => trim($_POST['opt_a'] ?? ''),
            'b' => trim($_POST['opt_b'] ?? ''),
            'c' => trim($_POST['opt_c'] ?? ''),
            'd' => trim($_POST['opt_d'] ?? ''),
        ]);
        $correct = trim($_POST['correct_answer'] ?? '');
        $explanation = trim($_POST['explanation'] ?? '');

        if ($a_id && $q_text) {
            $pdo->prepare("INSERT INTO assessment_questions 
                (assessment_id,question_text,question_type,options_json,correct_answer,explanation,marks)
                VALUES (?,?,?,?,?,?,?)")
                ->execute([$a_id,$q_text,$q_type,$options,$correct,$explanation,$marks]);
            $msg = "Question added!";
            $assessment_id = $a_id;
        }
    }

    // ── SUBMIT (student answers)
    if ($_POST['action'] === 'submit_answers' && $role === 'student') {
        $a_id    = intval($_POST['assessment_id'] ?? 0);
        $answers = $_POST['answers'] ?? [];

        // Check if already submitted
        $existing = $pdo->prepare("SELECT id FROM assessment_submissions WHERE assessment_id=? AND student_id=?");
        $existing->execute([$a_id, $user['id']]);
        if ($existing->fetch()) {
            $err = "You have already submitted this assessment.";
        } else {
            // Get assessment info
            $assess = $pdo->prepare("SELECT * FROM assessments WHERE id=?");
            $assess->execute([$a_id]);
            $assess = $assess->fetch(PDO::FETCH_ASSOC);

            // Get questions
            $qs = $pdo->prepare("SELECT * FROM assessment_questions WHERE assessment_id=?");
            $qs->execute([$a_id]);
            $questions = $qs->fetchAll(PDO::FETCH_ASSOC);

            $auto_score = 0;
            $total_marks = 0;
            $answers_detail = [];

            foreach ($questions as $q) {
                $student_ans = trim($answers[$q['id']] ?? '');
                $total_marks += $q['marks'];
                $is_correct = null;

                if (in_array($q['question_type'], ['mcq','truefalse'])) {
                    $is_correct = (strtolower($student_ans) === strtolower($q['correct_answer']));
                    if ($is_correct) $auto_score += $q['marks'];
                }
                // essay/short — needs teacher grading or AI check
                $answers_detail[] = [
                    'q_id'        => $q['id'],
                    'answer'      => $student_ans,
                    'is_correct'  => $is_correct,
                    'marks_earned'=> $is_correct ? $q['marks'] : ($is_correct === null ? null : 0),
                ];
            }

            // Handle file upload for assignment submission
            $file_path = null;
            if ($assess['type'] === 'assignment' && isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === 0) {
                $ext  = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));
                $dir  = 'uploads/submissions/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'sub_' . $user['id'] . '_' . $a_id . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['submission_file']['tmp_name'], $dir . $fname);
                $file_path = $dir . $fname;
            }

            $pdo->prepare("INSERT INTO assessment_submissions
                (assessment_id,student_id,answers_json,auto_score,total_marks,submission_file,submitted_at)
                VALUES (?,?,?,?,?,?,NOW())")
                ->execute([$a_id, $user['id'], json_encode($answers_detail), $auto_score, $total_marks, $file_path]);

            $msg = ($assess['type'] === 'quiz')
                ? "Submitted! Your score: $auto_score / $total_marks"
                : "Assignment submitted successfully!";

            // AI check against notes if enabled
            if ($assess['check_notes'] && $assess['lesson_id'] && !empty($questions)) {
                $lesson = $pdo->prepare("SELECT content_html AS content,title FROM lms_lessons WHERE id=?");
                $lesson->execute([$assess['lesson_id']]);
                $lesson = $lesson->fetch(PDO::FETCH_ASSOC);
                if ($lesson) {
                    // Queue AI check (done via AJAX endpoint below)
                    $_SESSION['pending_ai_check'] = [
                        'submission_id' => $pdo->lastInsertId(),
                        'lesson_content' => $lesson['content'],
                        'lesson_title'   => $lesson['title'],
                    ];
                }
            }
        }
    }

    // ── GRADE submission (teacher)
    if ($_POST['action'] === 'grade_submission' && in_array($role,['admin','teacher'])) {
        $sub_id = intval($_POST['submission_id'] ?? 0);
        $score  = floatval($_POST['score'] ?? 0);
        $feedback = trim($_POST['feedback'] ?? '');
        $pdo->prepare("UPDATE assessment_submissions SET teacher_score=?,feedback=?,graded_at=NOW() WHERE id=?")
            ->execute([$score, $feedback, $sub_id]);
        $msg = "Graded successfully!";
    }
}

// ─────────────────────────────────────────
// FETCH DATA FOR VIEW
// ─────────────────────────────────────────

// Classes
$classes = $pdo->query("SELECT id,title AS name FROM lms_classes ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Subjects
$subjects = $pdo->query("SELECT id,title AS name,class_id FROM lms_subjects WHERE level_type='subject' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
$subjects_by_class = [];
foreach ($subjects as $s) { $subjects_by_class[$s['class_id']][] = $s; }

// Lessons
$lessons = $pdo->query("SELECT id,title,subject_id FROM lms_lessons WHERE status='published' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
$lessons_by_subject = [];
foreach ($lessons as $l) { $lessons_by_subject[$l['subject_id']][] = $l; }

// Assessments list
// Fetch assessments based on role
$base_select = "SELECT a.*, s.title AS subject_name, c.title AS class_name FROM assessments a
    LEFT JOIN subjects s ON s.id=a.subject_id
    LEFT JOIN lms_classes c ON c.id=a.class_id";

if ($role === 'student') {
    $assessments = $db->fetchAll($base_select . "
        JOIN enrolments e ON e.class_id=a.class_id AND e.user_id=? AND e.status='active'
        ORDER BY a.created_at DESC", [$userId]);
    // Add submitted flag
    foreach ($assessments as &$a) {
        $a['submitted'] = (int)$db->fetchColumn("SELECT COUNT(*) FROM assessment_submissions WHERE assessment_id=? AND student_id=?", [$a['id'], $userId]);
    }
} elseif ($role === 'teacher') {
    $assessments = $db->fetchAll($base_select . " WHERE a.created_by=? ORDER BY a.created_at DESC", [$userId]);
    foreach ($assessments as &$a) {
        $a['sub_count'] = (int)$db->fetchColumn("SELECT COUNT(*) FROM assessment_submissions WHERE assessment_id=?", [$a['id']]);
    }
} else {
    // admin / school_admin — see all
    $assessments = $db->fetchAll($base_select . " ORDER BY a.created_at DESC");
    foreach ($assessments as &$a) {
        $a['sub_count'] = (int)$db->fetchColumn("SELECT COUNT(*) FROM assessment_submissions WHERE assessment_id=?", [$a['id']]);
    }
}

// If viewing a specific assessment
$view_id = intval($_GET['view'] ?? 0);
$view_assess = null;
$view_questions = [];
$view_submissions = [];
if ($view_id) {
    $s = $pdo->prepare("SELECT a.*, s.title AS subject_name, c.title AS class_name, l.title AS lesson_title
        FROM assessments a
        JOIN subjects s ON s.id=a.subject_id
        JOIN lms_classes c ON c.id=a.class_id
        LEFT JOIN lessons l ON l.id=a.lesson_id
        WHERE a.id=?");
    $s->execute([$view_id]);
    $view_assess = $s->fetch(PDO::FETCH_ASSOC);

    $q = $pdo->prepare("SELECT * FROM assessment_questions WHERE assessment_id=? ORDER BY id");
    $q->execute([$view_id]);
    $view_questions = $q->fetchAll(PDO::FETCH_ASSOC);

    if ($role !== 'student') {
        $sub = $pdo->prepare("SELECT sub.*, u.name AS username, u.name AS full_name FROM assessment_submissions sub
            JOIN users u ON u.id=sub.student_id WHERE sub.assessment_id=? ORDER BY sub.submitted_at DESC");
        $sub->execute([$view_id]);
        $view_submissions = $sub->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sub = $pdo->prepare("SELECT * FROM assessment_submissions WHERE assessment_id=? AND student_id=?");
        $sub->execute([$view_id, $user['id']]);
        $my_submission = $sub->fetch(PDO::FETCH_ASSOC);
    }
}

$subjects_json   = json_encode($subjects_by_class);
$lessons_json    = json_encode($lessons_by_subject);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assignments – LMS</title>
<style>
:root {
    --primary: #1a6b3a;
    --accent:  #f0a500;
    --bg:      #f5f7fa;
    --card:    #ffffff;
    --text:    #1e293b;
    --muted:   #64748b;
    --border:  #e2e8f0;
    --danger:  #dc2626;
    
    --assign-color: #7c3aed;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); }
.wrap { max-width: 1200px; margin: 0 auto; padding: 20px; }
h1 { font-size: 1.6rem; color: var(--primary); margin-bottom: 4px; }
.subtitle { color: var(--muted); font-size: 0.9rem; margin-bottom: 24px; }

/* Tabs */
.tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
.tab-btn { padding: 8px 20px; border-radius: 20px; border: 2px solid var(--border);
    background: var(--card); cursor: pointer; font-weight: 600; font-size: 0.85rem;
    transition: all .2s; }
.tab-btn.active { border-color: var(--primary); background: var(--primary); color: #fff; }


.tab-btn[data-type="assignment"] { border-color: var(--assign-color); }
.tab-btn[data-type="assignment"].active { background: var(--assign-color); color: #fff; }

/* Cards grid */
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.card { background: var(--card); border-radius: 12px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.08);
    border-left: 4px solid var(--border); transition: transform .15s; }
.card:hover { transform: translateY(-2px); }

.card.assignment { border-left-color: var(--assign-color); }
.card-type { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }

.card.assignment .card-type { color: var(--assign-color); }
.card h3 { font-size: 1rem; margin-bottom: 6px; }
.card .meta { color: var(--muted); font-size: 0.82rem; margin-bottom: 12px; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;
    font-weight: 600; margin-right: 4px; }
.badge-green { background: #dcfce7; color: #166534; }
.badge-blue  { background: #dbeafe; color: #1d4ed8; }
.badge-purple{ background: #ede9fe; color: #6d28d9; }
.badge-red   { background: #fee2e2; color: #b91c1c; }
.card-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn { padding: 6px 14px; border-radius: 6px; border: none; cursor: pointer;
    font-size: 0.82rem; font-weight: 600; text-decoration: none; display: inline-block; }
.btn-primary { background: var(--primary); color: #fff; }

.btn-purple  { background: var(--assign-color); color: #fff; }
.btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
.btn-danger  { background: var(--danger); color: #fff; }
.btn-sm      { padding: 4px 10px; font-size: 0.78rem; }

/* Create Form */
.create-form { background: var(--card); border-radius: 12px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 24px; }
.create-form h2 { font-size: 1.1rem; color: var(--primary); margin-bottom: 20px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-grid.three { grid-template-columns: 1fr 1fr 1fr; }
.form-full { grid-column: 1 / -1; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-group label { font-size: 0.82rem; font-weight: 600; color: var(--muted); }
.form-group input, .form-group select, .form-group textarea {
    padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem; }
.form-group textarea { resize: vertical; min-height: 80px; }
.toggle-type { display: flex; gap: 0; margin-bottom: 20px; border-radius: 8px; overflow: hidden; border: 2px solid var(--border); width: fit-content; }
.toggle-type label { padding: 8px 24px; cursor: pointer; font-weight: 600; font-size: 0.85rem; }
.toggle-type input[type=radio] { display: none; }

.toggle-type input[type=radio]:checked + label.for-assign  { background: var(--assign-color); color: #fff; }

/* Messages */
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; }
.alert-success { background: #dcfce7; color: #166534; }
.alert-error   { background: #fee2e2; color: #991b1b; }

/* Detail view */
.detail-header { background: var(--card); border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.detail-header h2 { font-size: 1.3rem; margin-bottom: 8px; }
.questions-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
.question-card { background: var(--card); border-radius: 10px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,.07); }
.question-card .q-num { font-size: 0.75rem; color: var(--muted); font-weight: 700; text-transform: uppercase; }
.question-card .q-text { font-size: 0.95rem; margin: 6px 0 12px; font-weight: 600; }
.options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.option { padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); cursor: pointer;
    font-size: 0.88rem; transition: all .15s; }
.option.selected { border-color: var(--quiz-color); background: #eff6ff; }
.option.correct  { border-color: #16a34a; background: #f0fdf4; }
.option.wrong    { border-color: var(--danger); background: #fef2f2; }
.sub-file-area { border: 2px dashed var(--border); border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; }
.sub-file-area:hover { border-color: var(--primary); }

/* Submissions table */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
th { background: var(--bg); text-align: left; padding: 10px 12px; font-weight: 700; }
td { padding: 9px 12px; border-top: 1px solid var(--border); }
tr:hover td { background: #fafafa; }

/* AI feedback box */
.ai-feedback { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 16px; margin-top: 12px; }
.ai-feedback h4 { color: #92400e; font-size: 0.85rem; margin-bottom: 8px; }
.ai-feedback p { font-size: 0.88rem; line-height: 1.6; white-space: pre-wrap; }

.hidden { display: none; }
@media(max-width:600px) { .form-grid, .form-grid.three { grid-template-columns: 1fr; } .options-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="wrap">

<h1>📋 Assignments</h1>
<p class="subtitle">Create and manage assignments for your classes</p>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<?php if ($view_assess): ?>
<!-- ═══════════════════════ DETAIL / TAKE VIEW ═══════════════════════ -->
<a href="assessments.php" class="btn btn-outline btn-sm" style="margin-bottom:16px;">← Back to list</a>

<div class="detail-header">
    <div class="card-type" style="color:<?= $view_assess['type']==='quiz' ? 'var(--quiz-color)' : 'var(--assign-color)' ?>">
        <?= $view_assess['type'] === 'quiz' ? '🎯 Quiz' : '📝 Assignment' ?>
    </div>
    <h2><?= htmlspecialchars($view_assess['title']) ?></h2>
    <div class="meta">
        <?= htmlspecialchars($view_assess['class_name']) ?> &nbsp;·&nbsp;
        <?= htmlspecialchars($view_assess['subject_name']) ?>
        <?php if ($view_assess['lesson_title']): ?> &nbsp;·&nbsp; Lesson: <?= htmlspecialchars($view_assess['lesson_title']) ?> <?php endif; ?>
        <?php if ($view_assess['due_date']): ?> &nbsp;·&nbsp; Due: <?= date('D d M Y', strtotime($view_assess['due_date'])) ?> <?php endif; ?>
        <?php if ($view_assess['duration_mins']): ?> &nbsp;·&nbsp; ⏱ <?= $view_assess['duration_mins'] ?> min <?php endif; ?>
    </div>
    <?php if ($view_assess['instructions']): ?>
        <p style="font-size:0.9rem;margin-top:8px;padding:12px;background:#f8fafc;border-radius:8px;">
            <?= nl2br(htmlspecialchars($view_assess['instructions'])) ?>
        </p>
    <?php endif; ?>
    <?php if ($view_assess['attachment']): ?>
        <p style="margin-top:10px;"><a href="<?= htmlspecialchars($view_assess['attachment']) ?>" target="_blank" class="btn btn-outline btn-sm">📎 Download Attachment</a></p>
    <?php endif; ?>
    <?php if ($view_assess['check_notes'] && $view_assess['lesson_title']): ?>
    <?php endif; ?>
</div>

<?php if ($role === 'student'): ?>
    <!-- STUDENT: take/view -->
    <?php if (!empty($my_submission)): ?>
        <div class="alert alert-success">
            ✅ Submitted on <?= date('d M Y g:ia', strtotime($my_submission['submitted_at'])) ?>
            <?php if ($view_assess['type']==='quiz'): ?>
             &nbsp;|&nbsp; Score: <strong><?= $my_submission['teacher_score'] ?? $my_submission['auto_score'] ?> / <?= $my_submission['total_marks'] ?></strong>
            <?php endif; ?>
        </div>
        <?php if ($my_submission['feedback']): ?>
        <?php endif; ?>
        <?php if ($my_submission['ai_feedback']): ?>
        <?php endif; ?>
    <?php else: ?>
        <form method="POST" enctype="multipart/form-data" id="submit-form">
        <input type="hidden" name="action" value="submit_answers">
        <input type="hidden" name="assessment_id" value="<?= $view_id ?>">

        <?php if (!empty($view_questions)): ?>
        <div class="questions-list">
        <?php foreach ($view_questions as $i => $q): ?>
        <div class="question-card">
            <div class="q-num">Question <?= $i+1 ?> &nbsp;·&nbsp; <?= $q['marks'] ?> mark(s)</div>
            <div class="q-text"><?= htmlspecialchars($q['question_text']) ?></div>

            <?php if ($q['question_type'] === 'mcq'): ?>
                <?php $opts = json_decode($q['options_json'], true); ?>
                <div class="options-grid" id="opts-<?= $q['id'] ?>">
                <?php foreach ($opts as $key => $text): if (!$text) continue; ?>
                    <div class="option" onclick="selectOpt(this,'<?= $q['id'] ?>','<?= $key ?>')">
                        <strong><?= strtoupper($key) ?>.</strong> <?= htmlspecialchars($text) ?>
                        <input type="hidden" name="answers[<?= $q['id'] ?>]" id="ans-<?= $q['id'] ?>">
                    </div>
                <?php endforeach; ?>
                </div>

            <?php elseif ($q['question_type'] === 'truefalse'): ?>
                <div class="options-grid">
                <div class="option" onclick="selectOpt(this,'<?= $q['id'] ?>','true')">✅ True <input type="hidden" name="answers[<?= $q['id'] ?>]" id="ans-<?= $q['id'] ?>"></div>
                <div class="option" onclick="selectOpt(this,'<?= $q['id'] ?>','false')">❌ False</div>
                </div>

            <?php elseif ($q['question_type'] === 'short'): ?>
                <input type="text" name="answers[<?= $q['id'] ?>]" placeholder="Your answer..." style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--border);">

            <?php else: ?>
                <textarea name="answers[<?= $q['id'] ?>]" rows="4" placeholder="Write your answer..." style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--border);"></textarea>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($view_assess['type'] === 'assignment'): ?>
        <div style="margin-bottom:20px;">
            <label style="font-weight:600;display:block;margin-bottom:8px;">📎 Upload your submission (optional)</label>
            <label class="sub-file-area" for="submission_file">
                <div>📁 Click to choose file or drag here</div>
                <div style="font-size:0.8rem;color:var(--muted);margin-top:4px;">PDF, DOC, DOCX, JPG, PNG, ZIP</div>
                <input type="file" id="submission_file" name="submission_file" style="display:none" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" onchange="this.previousElementSibling.previousElementSibling.textContent='📎 '+this.files[0].name">
            </label>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Submit Assessment</button>
        </form>
    <?php endif; ?>

<?php else: ?>
    <!-- TEACHER/ADMIN: view questions + submissions -->

    <?php if (in_array($role,['admin','teacher'])): ?>
    <!-- Add Question Form -->
    <div class="create-form" style="margin-bottom:20px;">
        <h2>➕ Add Question</h2>
        <form method="POST">
        <input type="hidden" name="action" value="save_question">
        <input type="hidden" name="assessment_id" value="<?= $view_id ?>">
        <div class="form-grid">
            <div class="form-group form-full">
                <label>Question Text *</label>
                <textarea name="question_text" required rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Question Type</label>
                <select name="question_type" id="qtype" onchange="toggleOptions(this.value)">
                    <option value="mcq">Multiple Choice (MCQ)</option>
                    <option value="truefalse">True / False</option>
                    <option value="short">Short Answer</option>
                    <option value="essay">Essay</option>
                </select>
            </div>
            <div class="form-group">
                <label>Marks</label>
                <input type="number" name="marks" value="1" min="1">
            </div>
        </div>
        <div id="mcq-options" style="margin-top:12px;">
            <div class="form-grid">
                <div class="form-group"><label>Option A</label><input type="text" name="opt_a"></div>
                <div class="form-group"><label>Option B</label><input type="text" name="opt_b"></div>
                <div class="form-group"><label>Option C</label><input type="text" name="opt_c"></div>
                <div class="form-group"><label>Option D</label><input type="text" name="opt_d"></div>
                <div class="form-group"><label>Correct Answer (a/b/c/d or true/false)</label><input type="text" name="correct_answer" placeholder="e.g. a"></div>
                <div class="form-group"><label>Explanation (optional)</label><input type="text" name="explanation"></div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:12px;">Add Question</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Questions display -->
    <?php if (!empty($view_questions)): ?>
    <div class="questions-list">
    <?php foreach ($view_questions as $i => $q): ?>
    <div class="question-card">
        <div class="q-num">Q<?= $i+1 ?> · <?= ucfirst($q['question_type']) ?> · <?= $q['marks'] ?> mark(s)</div>
        <div class="q-text"><?= htmlspecialchars($q['question_text']) ?></div>
        <?php if ($q['question_type']==='mcq'): $opts=json_decode($q['options_json'],true); ?>
        <div class="options-grid">
        <?php foreach ($opts as $k=>$v): if(!$v) continue; ?>
            <div class="option <?= ($k===$q['correct_answer'])?'correct':'' ?>"><?= strtoupper($k) ?>. <?= htmlspecialchars($v) ?></div>
        <?php endforeach; ?>
        </div>
        <?php elseif ($q['question_type']==='truefalse'): ?>
        <p>Correct: <strong><?= $q['correct_answer'] ?></strong></p>
        <?php endif; ?>
        <?php if ($q['explanation']): ?><p style="font-size:0.82rem;color:var(--muted);margin-top:8px;">💡 <?= htmlspecialchars($q['explanation']) ?></p><?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Submissions -->
    <?php if (!empty($view_submissions)): ?>
    <h3 style="margin-bottom:12px;">📬 Submissions (<?= count($view_submissions) ?>)</h3>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Student</th><th>Submitted</th><th>Auto Score</th><th>Teacher Score</th><th>File</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($view_submissions as $sub): ?>
        <tr>
            <td><?= htmlspecialchars($sub['full_name'] ?? $sub['username']) ?></td>
            <td><?= date('d M Y g:ia', strtotime($sub['submitted_at'])) ?></td>
            <td><?= $sub['auto_score'] ?>/<?= $sub['total_marks'] ?></td>
            <td><?= $sub['teacher_score'] ?? '<em style="color:var(--muted)">pending</em>' ?></td>
            <td><?php if ($sub['submission_file']): ?><a href="<?= htmlspecialchars($sub['submission_file']) ?>" target="_blank" class="btn btn-outline btn-sm">📎 View</a><?php else: ?>–<?php endif; ?></td>
            <td>
                <form method="POST" style="display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="action" value="grade_submission">
                <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                <input type="number" name="score" value="<?= $sub['teacher_score']??'' ?>" style="width:60px;padding:4px;border-radius:4px;border:1px solid var(--border);" placeholder="Score">
                <input type="text" name="feedback" value="<?= htmlspecialchars($sub['feedback']??'') ?>" style="width:160px;padding:4px;border-radius:4px;border:1px solid var(--border);" placeholder="Feedback">
                <button class="btn btn-primary btn-sm">Save</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>

<?php endif; ?>

<?php else: ?>
<!-- ═══════════════════════ MAIN LIST VIEW ═══════════════════════ -->

<?php if (in_array($role, ['admin','teacher'])): ?>
<!-- Create Assignment Form -->
<div class="create-form">
    <h2>➕ Create Assignment</h2>
    <form method="POST" enctype="multipart/form-data" id="create-form">
    <input type="hidden" name="action" value="save_assessment">
    <input type="hidden" name="edit_id" value="0">

    <!-- Type Toggle -->
    <label style="font-size:0.82rem;font-weight:600;color:var(--muted);margin-bottom:6px;display:block;">Type</label>
    <div class="toggle-type" style="display:none">
        <input type="radio" name="type" id="type-quiz" value="quiz">
        <label for="type-quiz" class="for-quiz">🎯 Quiz</label>
        <input type="radio" name="type" id="type-assign" value="assignment" checked>
        <label for="type-assign" class="for-assign">📝 Assignment</label>
    </div>

    <div class="form-grid">
        <div class="form-group form-full">
            <label>Title *</label>
            <input type="text" name="title" required placeholder="e.g. Chapter 3 Quiz / Term 1 Assignment">
        </div>
        <div class="form-group">
            <label>Class *</label>
            <select name="class_id" id="sel-class" onchange="loadSubjects(this.value)" required>
                <option value="">— Select Class —</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Subject *</label>
            <select name="subject_id" id="sel-subject" onchange="loadLessons(this.value)" required>
                <option value="">— Select Class first —</option>
            </select>
        </div>
        <div class="form-group">
            <label>Lesson (optional — enables AI note check)</label>
            <select name="lesson_id" id="sel-lesson">
                <option value="">— Select Subject first —</option>
            </select>
        </div>
        <div class="form-group">
            <label>Due Date (optional)</label>
            <input type="date" name="due_date">
        </div>
        <div class="form-group">
            <label>Duration (minutes, optional)</label>
            <input type="number" name="duration" placeholder="e.g. 30">
        </div>
        <div class="form-group form-full">
            <label>Instructions / Description</label>
            <textarea name="instructions" placeholder="What should students do?"></textarea>
        </div>
        <!-- Assignment only: file upload -->
        <div class="form-group form-full" id="attach-row" style="display:none;">
            <label>📎 Attachment (optional – for assignment)</label>
            <input type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
            <small style="color:var(--muted);">Share a PDF, worksheet, or resource with students</small>
        </div>
        <!-- AI notes check -->
        </div>

    <button type="submit" class="btn btn-primary" style="margin-top:16px;">Create Assignment</button>
    </form>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="tabs">
    <button class="tab-btn active" data-filter="all" onclick="filterCards('all',this)">All (<?= count($assessments) ?>)</button>
    <button class="tab-btn" data-type="assignment" data-filter="assignment" onclick="filterCards('assignment',this)">📝 Assignments</button>
</div>

<!-- Assessment Cards -->
<div class="grid" id="cards-grid">
<?php foreach ($assessments as $a): ?>
<div class="card <?= $a['type'] ?>" data-type="<?= $a['type'] ?>">
    <div class="card-type"><?= $a['type']==='quiz' ? '🎯 Quiz' : '📝 Assignment' ?></div>
    <h3><?= htmlspecialchars($a['title']) ?></h3>
    <div class="meta">
        <?= htmlspecialchars($a['class_name']) ?> · <?= htmlspecialchars($a['subject_name']) ?>
        <?php if ($a['due_date']): ?><br>Due: <?= date('d M Y', strtotime($a['due_date'])) ?><?php endif; ?>
    </div>
    <?php if ($role==='student'): ?>
        <?php if ($a['submitted']): ?>
            <span class="badge badge-green">✅ Submitted</span>
        <?php else: ?>
            <span class="badge badge-red">⬜ Pending</span>
        <?php endif; ?>
    <?php else: ?>
        <span class="badge badge-blue"><?= $a['sub_count'] ?> submission(s)</span>
    <?php endif; ?>
    <div class="card-actions" style="margin-top:12px;">
        <a href="assessments.php?view=<?= $a['id'] ?>" class="btn <?= $a['type']==='quiz'?'btn-blue':'btn-purple' ?> btn-sm">
            <?= $role==='student' ? ($a['submitted']?'View':'Take') : 'Open' ?>
        </a>
        <?php if (in_array($role,['admin','teacher'])): ?>
        <a href="assessments.php?delete=<?= $a['id'] ?>" class="btn btn-danger btn-sm"
            onclick="return confirm('Delete this assessment?')">🗑</a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($assessments)): ?>
<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--muted);">
    No assessments yet. <?= in_array($role,['admin','teacher']) ? 'Create one above!' : 'Check back later.' ?>
</div>
<?php endif; ?>
</div>

<?php endif; // end main list view ?>

</div><!-- .wrap -->

<script>
const SUBJECTS = <?= $subjects_json ?>;
const LESSONS  = <?= $lessons_json ?>;

function loadSubjects(classId) {
    const sel = document.getElementById('sel-subject');
    sel.innerHTML = '<option value="">— Select Subject —</option>';
    document.getElementById('sel-lesson').innerHTML = '<option value="">— Select Subject first —</option>';
    const subs = SUBJECTS[classId] || [];
    subs.forEach(s => {
        sel.innerHTML += `<option value="${s.id}">${s.name}</option>`;
    });
}

function loadLessons(subjectId) {
    const sel = document.getElementById('sel-lesson');
    sel.innerHTML = '<option value="">— None —</option>';
    const lsns = LESSONS[subjectId] || [];
    lsns.forEach(l => {
        sel.innerHTML += `<option value="${l.id}">${l.title}</option>`;
    });
}

// Show/hide attachment row based on type
document.querySelectorAll('input[name="type"]').forEach(r => {
    r.addEventListener('change', function() {
        document.getElementById('attach-row').style.display =
            this.value === 'assignment' ? 'block' : 'none';
    });
});

function filterCards(type, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#cards-grid .card').forEach(c => {
        c.style.display = (type === 'all' || c.dataset.type === type) ? '' : 'none';
    });
}

function selectOpt(el, qId, value) {
    document.querySelectorAll('#opts-' + qId + ' .option, [onclick*="' + qId + '"]').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    const inp = document.getElementById('ans-' + qId);
    if (inp) inp.value = value;
    // for truefalse, set sibling hidden input
    const allInputs = document.querySelectorAll(`input[name="answers[${qId}]"]`);
    allInputs.forEach(i => i.value = value);
}

function toggleOptions(type) {
    const d = document.getElementById('mcq-options');
    d.style.display = (type === 'mcq' || type === 'truefalse') ? 'block' : 'none';
}

// AI feedback polling
<?php if (!empty($my_submission) && !$my_submission['ai_feedback'] && $view_assess && $view_assess['check_notes'] && $view_assess['lesson_id']): ?>
setTimeout(function() {
    ;
}, 2000);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
