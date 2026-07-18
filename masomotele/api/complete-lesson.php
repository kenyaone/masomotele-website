<?php
require_once __DIR__ . '/../includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$userId  = $auth->getUserId();
$classId  = (int)($_POST['class_id'] ?? 0);
$lessonId = (int)($_POST['lesson_id'] ?? 0);
if (!$classId || !$lessonId) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }
$enrolled = $db->fetchOne("SELECT id FROM lms_enrolments WHERE user_id=? AND class_id=?", [$userId, $classId]);
if (!$enrolled) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }
$lesson = $db->fetchOne("SELECT id, subject_id FROM lms_lessons WHERE id=? AND class_id=? AND status='published'", [$lessonId, $classId]);
if (!$lesson) { header('Location: ' . SITE_URL . '/class.php?id=' . $classId); exit; }
$exists = $db->fetchOne("SELECT id FROM lms_completions WHERE user_id=? AND lesson_id=?", [$userId, $lessonId]);
if (!$exists) {
    $db->insert('lms_completions', ['user_id' => $userId, 'class_id' => $classId, 'lesson_id' => $lessonId, 'subject_id' => $lesson['subject_id'], 'completed_at' => date('Y-m-d H:i:s')]);
    $count = (int)$db->fetchColumn("SELECT COUNT(*) FROM lms_completions WHERE user_id=?", [$userId]);
    $thresholds = [5 => 'lessons_completed', 20 => 'lessons_completed'];
    foreach ($thresholds as $n => $type) {
        if ($count === $n) {
            $badge = $db->fetchOne("SELECT id FROM lms_badges WHERE trigger_type=? AND criteria_value=?", [$type, $n]);
            if ($badge) { try { $db->insert('lms_user_badges', ['user_id' => $userId, 'badge_id' => $badge['id'], 'earned_at' => date('Y-m-d H:i:s')]); } catch(Exception $e){} }
        }
    }
    $totalLessons = (int)$db->fetchColumn("SELECT COUNT(*) FROM lms_lessons WHERE class_id=? AND status='published'", [$classId]);
    $doneCount    = (int)$db->fetchColumn("SELECT COUNT(*) FROM lms_completions WHERE user_id=? AND class_id=?", [$userId, $classId]);
    if ($totalLessons > 0 && $doneCount >= $totalLessons) {
        $badge = $db->fetchOne("SELECT id FROM lms_badges WHERE trigger_type='class_completed'");
        if ($badge) { try { $db->insert('lms_user_badges', ['user_id' => $userId, 'badge_id' => $badge['id'], 'earned_at' => date('Y-m-d H:i:s')]); } catch(Exception $e){} }
    }
}
header('Location: ' . SITE_URL . '/class.php?id=' . $classId . '&lesson=' . $lessonId);
exit;
