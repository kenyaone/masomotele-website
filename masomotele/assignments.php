<?php
/**
 * M.T.T.I LMS - Student Assignments
 * View assignments, submit work, see grades
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$msg = $_GET['msg'] ?? '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
    $aid = intval($_POST['assignment_id']);
    $assignment = $db->fetchOne("SELECT * FROM assignments WHERE id=? AND status='active'", [$aid]);
    if (!$assignment) { header('Location: assignments.php?msg=Invalid'); exit; }

    $existing = $db->fetchOne("SELECT id,status FROM assignment_submissions WHERE assignment_id=? AND student_id=?", [$aid, $userId]);
    if ($existing && $existing['status'] !== 'resubmit') { header('Location: assignments.php?msg=Already+submitted'); exit; }

    $text = trim($_POST['submission_text'] ?? '');
    $filePath = null; $fileName = null; $fileSize = 0;
    $isLate = $assignment['due_date'] && strtotime($assignment['due_date']) < time() ? 1 : 0;

    // File upload
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/assets/uploads/assignments/';
        @mkdir($dir, 0755, true);
        $origName = $_FILES['submission_file']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = explode(',', $assignment['allowed_formats'] ?? 'pdf,doc,docx,jpg,png,html');
        if (in_array($ext, $allowed)) {
            $safeName = $userId . '_' . $aid . '_' . time() . '.' . $ext;
            $destPath = 'assets/uploads/assignments/' . $safeName;
            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $dir . $safeName)) {
                $filePath = $destPath; $fileName = $origName; $fileSize = $_FILES['submission_file']['size'];
            }
        }
    }

    if ($existing) {
        $db->query("UPDATE assignment_submissions SET submission_text=?, file_path=COALESCE(?,file_path), file_name=COALESCE(?,file_name), file_size=?, submitted_at=NOW(), is_late=?, status='submitted', grade=NULL, feedback=NULL WHERE id=?",
            [$text, $filePath, $fileName, $fileSize ?: 0, $isLate, $existing['id']]);
    } else {
        $db->insert('assignment_submissions', [
            'assignment_id' => $aid, 'student_id' => $userId,
            'submission_text' => $text, 'file_path' => $filePath,
            'file_name' => $fileName, 'file_size' => $fileSize,
            'is_late' => $isLate, 'submitted_at' => date('Y-m-d H:i:s'), 'status' => 'submitted'
        ]);
    }
    header('Location: assignments.php?msg=Submitted+successfully'); exit;
}

// Get assignments for enrolled classes — use user_id (not student_id)
$assignments = $db->fetchAll("
    SELECT a.*, c.title as class_title, s.title as subject_title,
        sub.id as sub_id, sub.status as sub_status, sub.grade, sub.feedback,
        sub.submitted_at as sub_date, sub.is_late, sub.submission_text as sub_text,
        sub.file_name as sub_file_name, sub.file_path as sub_file_path
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN enrolments e ON e.class_id = a.class_id AND e.user_id = ?
    LEFT JOIN subjects s ON a.subject_id = s.id
    LEFT JOIN assignment_submissions sub ON sub.assignment_id = a.id AND sub.student_id = ?
    WHERE a.status IN ('active','closed')
    ORDER BY FIELD(a.status,'active','closed'), a.due_date ASC",
    [$userId, $userId]);

$pageTitle = 'My Assignments - ' . SITE_NAME;
require_once __DIR__ . '/templates/header.php';
?>
<style>
:root{--pri:#1a5632;--pri2:#2d7a4c;--gold:#e8a423;--gold2:#fef9e7;--txt:#1e293b;--mut:#64748b;--brd:#c8dcc0;--bg:#eef3e8}
body{background:var(--bg)}
.sa-hero{background:linear-gradient(135deg,var(--pri),var(--pri2));color:#fff;padding:18px 20px;border-radius:0 0 16px 16px;margin:-1rem -12px 18px;border-bottom:4px solid var(--gold)}
.sa-hero h4{margin:0;font-weight:800;font-size:1.2rem}
.sa-hero .sub{opacity:.8;font-size:.82rem;color:var(--gold2);margin-top:3px}
.type-pill{display:inline-block;padding:2px 10px;border-radius:8px;font-size:.68rem;font-weight:700;margin-bottom:6px}
.tp-essay{background:#ede9fe;color:#7c3aed}
.tp-structured{background:#dbeafe;color:#1d4ed8}
.tp-file{background:#dcfce7;color:#16a34a}
.tp-mc{background:#fef3c7;color:#92400e}
.as-card{background:#fff;border-radius:16px;border:1px solid var(--brd);border-left:5px solid var(--gold);padding:18px;margin-bottom:16px;transition:box-shadow .2s}
.as-card:hover{box-shadow:0 4px 20px rgba(26,86,50,.08)}
.as-card.submitted{border-left-color:#3b82f6}
.as-card.graded{border-left-color:#16a34a}
.as-card.late{border-left-color:#dc2626}
.as-card.closed{opacity:.65}
.as-card h6{font-weight:700;color:var(--pri);margin:0 0 2px;font-size:.98rem}
.as-meta{font-size:.74rem;color:var(--mut);display:flex;gap:10px;flex-wrap:wrap;margin-top:6px}
.as-meta span{display:flex;align-items:center;gap:3px}
.due-tag{padding:3px 10px;border-radius:8px;font-size:.7rem;font-weight:700;white-space:nowrap}
.dt-ok{background:#dcfce7;color:#16a34a}
.dt-soon{background:#fef3c7;color:#92400e}
.dt-past{background:#fee2e2;color:#dc2626}
.status-pill{padding:4px 14px;border-radius:10px;font-size:.78rem;font-weight:700;display:inline-block}
.sp-pending{background:var(--gold2);color:#92400e}
.sp-submitted{background:#dbeafe;color:#1d4ed8}
.sp-graded{background:#dcfce7;color:#16a34a}
.sp-resubmit{background:#fee2e2;color:#dc2626}
.grade-display{font-size:1.6rem;font-weight:800;color:#16a34a;margin:8px 0 4px}
.feedback-box{background:var(--gold2);border-radius:10px;padding:12px 14px;font-size:.85rem;margin-top:8px;border-left:4px solid var(--gold)}
.sub-form{background:#f8fafc;border-radius:12px;border:2px solid var(--pri);padding:16px;margin-top:14px}
.sub-form h6{font-weight:700;color:var(--pri);font-size:.9rem;margin:0 0 12px;display:flex;align-items:center;gap:6px}
.structured-q{background:#fff;border-radius:8px;border:1px solid var(--brd);padding:12px;margin-bottom:10px}
.structured-q .qnum{background:var(--pri);color:#fff;border-radius:6px;padding:1px 8px;font-size:.72rem;font-weight:700;margin-right:6px}
.btn-submit{background:var(--pri);color:#fff;border:none;border-radius:10px;padding:10px 28px;font-weight:700;font-size:.9rem;transition:all .2s}
.btn-submit:hover{background:#155d33;color:#fff;transform:translateY(-1px)}
.sub-preview{background:#e8f5e9;border-radius:10px;padding:12px;font-size:.83rem;margin-top:8px;border-left:3px solid var(--pri)}
</style>

<div class="container-fluid px-3">
<div class="sa-hero">
    <a href="<?= SITE_URL ?>/student-portal.php" class="text-white text-decoration-none" style="font-size:.8rem">
        <i class="bi bi-arrow-left me-1"></i>My Learning
    </a>
    <h4 class="mt-2"><i class="bi bi-clipboard-check me-2" style="color:var(--gold)"></i>My Assignments</h4>
    <div class="sub">View, submit and track your assignment submissions</div>
</div>

<?php if ($msg): ?>
<div class="alert alert-success alert-dismissible fade show py-2 mb-3" style="font-size:.85rem;border-radius:10px">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($assignments)): ?>
<div class="text-center py-5" style="color:var(--mut)">
    <i class="bi bi-clipboard-x" style="font-size:3.5rem;color:var(--gold)"></i>
    <h5 class="mt-3">No assignments yet</h5>
    <p style="font-size:.88rem">Your teacher hasn't posted any assignments yet. Check back soon!</p>
</div>
<?php endif; ?>

<?php foreach ($assignments as $a):
    $aType = $a['assignment_type'] ?? 'file_upload';
    $dueDate = $a['due_date'] ? strtotime($a['due_date']) : null;
    $diff = $dueDate ? $dueDate - time() : null;
    $dtClass = 'dt-ok'; $dtText = 'No deadline';
    if ($dueDate) {
        if ($diff < 0) { $dtClass = 'dt-past'; $dtText = 'Overdue · ' . date('M j', $dueDate); }
        elseif ($diff < 86400 * 2) { $dtClass = 'dt-soon'; $dtText = 'Due ' . date('M j, g:ia', $dueDate); }
        else { $dtClass = 'dt-ok'; $dtText = 'Due ' . date('M j', $dueDate); }
    }
    $cardClass = '';
    if ($a['sub_status'] === 'graded') $cardClass = 'graded';
    elseif ($a['sub_status'] === 'submitted') $cardClass = 'submitted';
    elseif ($a['is_late']) $cardClass = 'late';
    if ($a['status'] === 'closed') $cardClass .= ' closed';

    $typePill = match($aType) {
        'essay' => '<span class="type-pill tp-essay">✍️ Essay</span>',
        'structured' => '<span class="type-pill tp-structured">📋 Structured</span>',
        'multiple_choice' => '<span class="type-pill tp-mc">🔘 Quiz</span>',
        default => '<span class="type-pill tp-file">📎 File Upload</span>'
    };
?>
<div class="as-card <?= $cardClass ?>">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div>
            <?= $typePill ?>
            <h6><?= htmlspecialchars($a['title']) ?></h6>
            <div style="font-size:.8rem;color:var(--mut)">
                <?= htmlspecialchars($a['class_title']) ?>
                <?= $a['subject_title'] ? ' · ' . htmlspecialchars($a['subject_title']) : '' ?>
            </div>
        </div>
        <span class="due-tag <?= $dtClass ?>"><?= $dtText ?></span>
    </div>

    <?php if ($a['description']): ?>
    <div style="font-size:.85rem;margin-top:8px;color:var(--txt)"><?= nl2br(htmlspecialchars($a['description'])) ?></div>
    <?php endif; ?>

    <?php if ($a['instructions']): ?>
    <div style="font-size:.82rem;color:var(--mut);margin-top:6px;background:#f8fafc;padding:8px 12px;border-radius:8px;border-left:3px solid var(--gold)">
        <strong>📌 Instructions:</strong> <?= nl2br(htmlspecialchars($a['instructions'])) ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($a['teacher_file_name'])): ?>
    <div style="margin-top:8px">
        <a href="<?= SITE_URL ?>/<?= $a['teacher_file_path'] ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;text-decoration:none;color:#1d4ed8;font-weight:600;font-size:.83rem">
            <i class="bi bi-file-earmark-arrow-down"></i>
            Download: <?= htmlspecialchars($a['teacher_file_name']) ?>
        </a>
    </div>
    <?php endif; ?>

    <div class="as-meta">
        <span><i class="bi bi-star-fill" style="color:var(--gold)"></i> <?= $a['total_marks'] ?> marks</span>
        <?php if ($a['allow_late']): ?><span><i class="bi bi-clock"></i> Late submissions allowed</span><?php endif; ?>
        <?php if ($a['due_date']): ?><span><i class="bi bi-calendar"></i> <?= date('D, M j Y g:ia', strtotime($a['due_date'])) ?></span><?php endif; ?>
    </div>

    <?php if ($a['sub_status'] === 'graded'): ?>
        <!-- GRADED -->
        <div class="grade-display">
            <?= $a['grade'] ?><span style="font-size:1rem;color:var(--mut)"> / <?= $a['total_marks'] ?></span>
            <span style="font-size:.85rem;font-weight:600;color:var(--mut);margin-left:8px">(<?= round(($a['grade'] / max($a['total_marks'],1))*100) ?>%)</span>
        </div>
        <span class="status-pill sp-graded"><i class="bi bi-check-circle-fill me-1"></i>Graded</span>
        <?php if ($a['feedback']): ?>
        <div class="feedback-box"><strong>💬 Teacher's Feedback:</strong><br><?= nl2br(htmlspecialchars($a['feedback'])) ?></div>
        <?php endif; ?>
        <?php if ($a['sub_file_name']): ?>
        <div class="mt-2"><a href="<?= SITE_URL ?>/<?= $a['sub_file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-success" style="border-radius:8px"><i class="bi bi-file-earmark me-1"></i>View My Submission</a></div>
        <?php endif; ?>

    <?php elseif ($a['sub_status'] === 'submitted'): ?>
        <!-- SUBMITTED -->
        <div class="mt-2">
            <span class="status-pill sp-submitted"><i class="bi bi-hourglass-split me-1"></i>Submitted — Awaiting Grade</span>
            <div style="font-size:.78rem;color:var(--mut);margin-top:4px">Submitted <?= date('M j, Y g:ia', strtotime($a['sub_date'])) ?></div>
        </div>
        <?php if ($a['sub_text']): ?>
        <div class="sub-preview"><strong>Your answer:</strong><br><?= nl2br(htmlspecialchars(substr($a['sub_text'], 0, 300))) ?><?= strlen($a['sub_text']) > 300 ? '...' : '' ?></div>
        <?php endif; ?>
        <?php if ($a['sub_file_name']): ?>
        <div class="mt-2"><a href="<?= SITE_URL ?>/<?= $a['sub_file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius:8px"><i class="bi bi-file-earmark me-1"></i><?= htmlspecialchars($a['sub_file_name']) ?></a></div>
        <?php endif; ?>

    <?php elseif ($a['sub_status'] === 'resubmit'): ?>
        <!-- RESUBMIT -->
        <div class="mt-2"><span class="status-pill sp-resubmit"><i class="bi bi-arrow-repeat me-1"></i>Resubmission Requested</span></div>
        <?php if ($a['feedback']): ?>
        <div class="feedback-box"><strong>Teacher's note:</strong><br><?= nl2br(htmlspecialchars($a['feedback'])) ?></div>
        <?php endif; ?>
        <?php if ($a['status'] === 'active'): ?>
        <?php include_once __DIR__ . '/includes/assignment_submit_form.php'; // reuse form ?>
        <?php // fallthrough to submission form below ?>
        <?php endif; ?>

    <?php endif; ?>

    <?php
    $canSubmit = ($a['status'] === 'active') &&
                 (!$a['sub_status'] || $a['sub_status'] === 'resubmit') &&
                 ($a['allow_late'] || !$dueDate || $dueDate > time() || $diff === null);
    if ($canSubmit):
        $isResubmit = $a['sub_status'] === 'resubmit';
    ?>
    <div class="sub-form">
        <h6>
            <i class="bi bi-<?= $isResubmit ? 'arrow-repeat' : 'send' ?>"></i>
            <?= $isResubmit ? 'Resubmit Your Work' : 'Submit Your Work' ?>
        </h6>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit">
            <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">

            <?php if ($aType === 'essay'): ?>
                <!-- ESSAY: rich text area -->
                <label class="form-label fw-semibold" style="font-size:.85rem">✍️ Your Essay</label>
                <textarea name="submission_text" class="form-control mb-2" rows="8" required
                    placeholder="Write your essay here. Be thorough and well-structured..."></textarea>
                <small class="text-muted d-block mb-2">You may also attach a file (optional)</small>
                <input type="file" name="submission_file" class="form-control mb-3" accept=".pdf,.doc,.docx">

            <?php elseif ($aType === 'structured'): ?>
                <!-- STRUCTURED: guided questions from rubric -->
                <?php
                $questions = [];
                if (!empty($a['rubric'])) {
                    $lines = array_filter(explode("\n", $a['rubric']));
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line) $questions[] = $line;
                    }
                }
                ?>
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $qi => $q): ?>
                    <div class="structured-q">
                        <label style="font-size:.85rem;font-weight:600">
                            <span class="qnum">Q<?= $qi+1 ?></span><?= htmlspecialchars($q) ?>
                        </label>
                        <textarea name="structured_answers[]" class="form-control mt-2" rows="3"
                            placeholder="Your answer to question <?= $qi+1 ?>..." required></textarea>
                    </div>
                    <?php endforeach; ?>
                    <input type="hidden" name="structured_questions" value="<?= htmlspecialchars(json_encode($questions)) ?>">
                <?php else: ?>
                    <label class="form-label fw-semibold" style="font-size:.85rem">📋 Your Structured Response</label>
                    <textarea name="submission_text" class="form-control mb-2" rows="6" required
                        placeholder="Answer each part of the assignment clearly. Label each section (Part A, Part B, etc.)..."></textarea>
                <?php endif; ?>
                <small class="text-muted d-block mb-2">You may also attach a file (optional)</small>
                <input type="file" name="submission_file" class="form-control mb-3" accept=".pdf,.doc,.docx,.jpg,.png">

            <?php else: ?>
                <!-- FILE UPLOAD (default) -->
                <label class="form-label fw-semibold" style="font-size:.85rem">📝 Notes / Comments <small class="text-muted fw-normal">(optional)</small></label>
                <textarea name="submission_text" class="form-control mb-2" rows="3"
                    placeholder="Add any notes or comments for your teacher..."></textarea>
                <label class="form-label fw-semibold" style="font-size:.85rem">📎 Upload Your Work *</label>
                <input type="file" name="submission_file" class="form-control mb-1" required
                    accept=".pdf,.doc,.docx,.jpg,.png,.zip,.html">
                <small class="text-muted d-block mb-3">Accepted: PDF, Word, Images, ZIP, HTML</small>
            <?php endif; ?>

            <button type="submit" class="btn-submit">
                <i class="bi bi-send me-2"></i><?= $isResubmit ? 'Resubmit' : 'Submit Assignment' ?>
            </button>
            <?php if ($isLate ?? false): ?>
            <small class="text-danger ms-2"><i class="bi bi-exclamation-triangle"></i> This will be marked as late</small>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($a['status'] === 'closed' && !$a['sub_status']): ?>
    <div class="mt-2"><span class="status-pill" style="background:#f1f5f9;color:#64748b"><i class="bi bi-lock me-1"></i>Closed — Not Submitted</span></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
