<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Quiz - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId = $auth->getUserId();
$quizId = (int)($_GET['id'] ?? 0);
$quiz = $db->fetchOne("SELECT * FROM quizzes WHERE id=?", [$quizId]);
if (!$quiz) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }

// Check attempts
$attempts = (int)$db->fetchColumn("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND user_id=?", [$quizId, $userId]);
$maxReached = $quiz['max_attempts'] > 0 && $attempts >= $quiz['max_attempts'];

// Handle submission
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$maxReached) {
    $questions = $db->fetchAll("SELECT * FROM questions WHERE quiz_id=? ORDER BY sort_order, id", [$quizId]);
    $score = 0; $total = 0; $answers = [];
    foreach ($questions as $q) {
        $total += (float)$q['points'];
        $userAns = $_POST['q_' . $q['id']] ?? '';
        $correct = false;
        if ($q['type'] === 'mcq' || $q['type'] === 'true_false') { $correct = (strtolower(trim($userAns)) === strtolower(trim($q['correct_answer']))); }
        elseif ($q['type'] === 'short_answer' || $q['type'] === 'fill_blank') { $correct = (strtolower(trim($userAns)) === strtolower(trim($q['correct_answer']))); }
        if ($correct) $score += (float)$q['points'];
        $answers[$q['id']] = ['answer' => $userAns, 'correct' => $correct, 'points' => $correct ? $q['points'] : 0];
    }
    $pct = $total > 0 ? round(($score / $total) * 100, 2) : 0;
    $passed = $pct >= (float)$quiz['pass_mark'];
    $db->insert('quiz_attempts', ['quiz_id'=>$quizId,'user_id'=>$userId,'answers_json'=>json_encode($answers),'score'=>$score,'total_points'=>$total,'percentage'=>$pct,'passed'=>$passed?1:0,'started_at'=>date('Y-m-d H:i:s'),'submitted_at'=>date('Y-m-d H:i:s')]);
    $db->insert('grades', ['user_id'=>$userId,'class_id'=>$quiz['class_id'],'quiz_id'=>$quizId,'grade'=>$pct,'grade_type'=>'auto','created_at'=>date('Y-m-d H:i:s')]);

    // Badge check: first quiz passed
    if ($passed) {
        $passCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM quiz_attempts WHERE user_id=? AND passed=1", [$userId]);
        if ($passCount === 1) {
            $badge = $db->fetchOne("SELECT id FROM lms_badges WHERE trigger_type='first_quiz_pass'");
            if ($badge) {
                $has = $db->fetchOne("SELECT id FROM lms_user_badges WHERE user_id=? AND badge_id=?", [$userId, $badge['id']]);
                if (!$has) $db->insert('lms_user_badges', ['user_id'=>$userId,'badge_id'=>$badge['id'],'earned_at'=>date('Y-m-d H:i:s')]);
            }
        }
    }
    $result = ['score'=>$score,'total'=>$total,'pct'=>$pct,'passed'=>$passed,'answers'=>$answers];
}

$questions = $db->fetchAll("SELECT * FROM questions WHERE quiz_id=? ORDER BY " . ($quiz['shuffle_questions'] ? "RAND()" : "sort_order, id"), [$quizId]);
require_once __DIR__ . '/templates/header.php';
?>
<div class="container" style="max-width:800px">
    <h4 class="mb-3"><i class="bi bi-question-circle me-2 text-primary"></i><?= htmlspecialchars($quiz['title']) ?></h4>
    <?php if ($quiz['description']): ?><p class="text-muted"><?= htmlspecialchars($quiz['description']) ?></p><?php endif; ?>

    <?php if ($result): ?>
    <!-- Results -->
    <div class="card mb-4">
        <div class="card-body text-center">
            <h2 class="<?= $result['passed'] ? 'text-success' : 'text-danger' ?>"><?= $result['pct'] ?>%</h2>
            <p><?= $result['score'] ?>/<?= $result['total'] ?> points | <?= $result['passed'] ? '<span class="badge bg-success">PASSED</span>' : '<span class="badge bg-danger">FAILED</span>' ?></p>
            <p class="text-muted">Pass mark: <?= $quiz['pass_mark'] ?>%</p>
        </div>
    </div>
    <?php if ($quiz['show_answers']): foreach ($questions as $i => $q): $a = $result['answers'][$q['id']] ?? null; ?>
    <div class="card mb-2">
        <div class="card-body">
            <div class="d-flex"><span class="badge bg-<?= ($a && $a['correct']) ? 'success' : 'danger' ?> me-2"><?= $i+1 ?></span><strong><?= htmlspecialchars($q['question_text']) ?></strong></div>
            <?php if ($a): ?><p class="mt-1 mb-0 small">Your answer: <strong><?= htmlspecialchars($a['answer']) ?></strong> | Correct: <strong><?= htmlspecialchars($q['correct_answer']) ?></strong></p><?php endif; ?>
            <?php if ($q['explanation']): ?><p class="small text-muted mt-1 mb-0"><i class="bi bi-lightbulb me-1"></i><?= htmlspecialchars($q['explanation']) ?></p><?php endif; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
    <a href="<?= SITE_URL ?>/class.php?id=<?= $quiz['class_id'] ?>" class="btn btn-primary mt-3">Back to Class</a>

    <?php elseif ($maxReached): ?>
    <div class="alert alert-warning">You have used all <?= $quiz['max_attempts'] ?> attempts for this quiz.</div>
    <a href="<?= SITE_URL ?>/class.php?id=<?= $quiz['class_id'] ?>" class="btn btn-primary">Back to Class</a>

    <?php else: ?>
    <!-- Quiz Form -->
    <div class="alert alert-info small">
        <i class="bi bi-info-circle me-1"></i>Time: <?= $quiz['time_limit'] ?> min | Attempts: <?= $attempts ?>/<?= $quiz['max_attempts'] ?: '∞' ?> | Pass: <?= $quiz['pass_mark'] ?>%
    </div>
    <form method="POST" id="quizForm">
        <?php foreach ($questions as $i => $q): $opts = json_decode($q['options_json'], true) ?: []; ?>
        <div class="card mb-3">
            <div class="card-body">
                <h6><span class="badge bg-primary me-2"><?= $i+1 ?></span><?= htmlspecialchars($q['question_text']) ?> <small class="text-muted">(<?= $q['points'] ?> pts - <?= ucfirst(str_replace('_',' ',$q['type'])) ?>)</small></h6>
                <?php if ($q['type'] === 'mcq'): foreach ($opts as $o): ?>
                    <div class="form-check"><input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="<?= htmlspecialchars($o) ?>" required><label class="form-check-label"><?= htmlspecialchars($o) ?></label></div>
                <?php endforeach; elseif ($q['type'] === 'true_false'): ?>
                    <div class="form-check"><input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="True" required><label class="form-check-label">True</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="False" required><label class="form-check-label">False</label></div>
                <?php elseif ($q['type'] === 'short_answer' || $q['type'] === 'fill_blank'): ?>
                    <input type="text" name="q_<?= $q['id'] ?>" class="form-control" placeholder="Your answer..." required>
                <?php elseif ($q['type'] === 'essay'): ?>
                    <textarea name="q_<?= $q['id'] ?>" class="form-control" rows="4" placeholder="Write your answer..."></textarea>
                <?php elseif ($q['type'] === 'drag_drop'): foreach ($opts as $o): ?>
                    <div class="form-check"><input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="<?= htmlspecialchars($o) ?>"><label class="form-check-label"><?= htmlspecialchars($o) ?></label></div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-success btn-lg w-100"><i class="bi bi-send me-2"></i>Submit Quiz</button>
    </form>

    <?php if ($quiz['time_limit'] > 0): ?>
    <script>
    let timeLeft = <?= $quiz['time_limit'] * 60 ?>;
    const timer = setInterval(() => {
        timeLeft--;
        const m = Math.floor(timeLeft/60), s = timeLeft%60;
        document.title = m+':'+(s<10?'0':'')+s+' - Quiz';
        if (timeLeft <= 0) { clearInterval(timer); document.getElementById('quizForm').submit(); }
    }, 1000);
    </script>
    <?php endif; endif; ?>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
