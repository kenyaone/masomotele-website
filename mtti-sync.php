<?php
declare(strict_types=1);
header('Content-Type: application/json');

const SYNC_SECRET = 'mtti_sync_k3nya_2026';

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (($_POST['secret'] ?? '') !== SYNC_SECRET) fail('bad secret', 403);

$payload = json_decode($_POST['payload'] ?? '', true);
if (!is_array($payload)) fail('bad payload');

$deviceId = trim((string)($payload['deviceId'] ?? ''));
if ($deviceId === '') fail('missing device_id');

$m = new mysqli('localhost','uvyzhdzt_mtti','mtti_sync_2026!','uvyzhdzt_mtti');
if ($m->connect_errno) fail('db: '.$m->connect_error, 500);
$m->set_charset('utf8mb4');

$m->begin_transaction();
try {
    // Pre-fetch existing record for sync interval + trend tracking
    $chk = $m->prepare('SELECT last_sync_at, avg_sync_interval_secs, student_count FROM devices WHERE device_id=?');
    $chk->bind_param('s', $deviceId); $chk->execute();
    $existing = $chk->get_result()->fetch_assoc(); $chk->close();

    $avgInterval = null;
    if ($existing && $existing['last_sync_at']) {
        $gap = max(0, time() - strtotime($existing['last_sync_at']));
        $old = $existing['avg_sync_interval_secs'];
        $avgInterval = (int)($old ? (0.7 * $old + 0.3 * $gap) : $gap);
    }
    $prevStudents = $existing ? (int)$existing['student_count'] : null;

    // Extract payload fields
    $name         = (string)($payload['name']               ?? '');
    $county       = is_numeric($payload['county'] ?? '') ? '' : (string)($payload['county'] ?? '');
    $region       = (string)($payload['region']             ?? '');
    $lat          = isset($payload['lat']) && is_numeric($payload['lat']) ? (float)$payload['lat'] : null;
    $lng          = isset($payload['lng']) && is_numeric($payload['lng']) ? (float)$payload['lng'] : null;
    $students     = (int)($payload['student_count']         ?? 0);
    $lessons      = (int)($payload['lesson_completions']    ?? 0);
    $quizzes      = (int)($payload['quiz_attempts']         ?? 0);
    $avgScore     = isset($payload['avg_score']) && is_numeric($payload['avg_score']) ? (float)$payload['avg_score'] : null;
    $certs        = (int)($payload['cert_count']            ?? 0);
    $active30     = (int)($payload['active_last_30']        ?? 0);

    // Upsert — name/county/region locked on first sync, never overwritten
    $stmt = $m->prepare('INSERT INTO devices
        (device_id, name, county, region, lat, lng,
         student_count, lesson_completions, quiz_attempts, avg_score,
         cert_count, active_last_30, avg_sync_interval_secs, student_count_prev,
         first_sync_at, last_sync_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
        ON DUPLICATE KEY UPDATE
            student_count_prev      = student_count,
            student_count           = VALUES(student_count),
            lesson_completions      = VALUES(lesson_completions),
            quiz_attempts           = VALUES(quiz_attempts),
            avg_score               = VALUES(avg_score),
            cert_count              = VALUES(cert_count),
            active_last_30          = VALUES(active_last_30),
            avg_sync_interval_secs  = VALUES(avg_sync_interval_secs),
            lat                     = IFNULL(VALUES(lat), lat),
            lng                     = IFNULL(VALUES(lng), lng),
            last_sync_at            = NOW()
            /* name, county, region locked on first sync */'
    );
    $stmt->bind_param('ssssddiiididii',
        $deviceId, $name, $county, $region, $lat, $lng,
        $students, $lessons, $quizzes, $avgScore,
        $certs, $active30, $avgInterval, $prevStudents);
    $stmt->execute(); $stmt->close();

    $m->commit();
    echo json_encode(['ok' => true, 'device' => $deviceId]);
} catch (Throwable $e) {
    $m->rollback();
    fail('write failed: '.$e->getMessage(), 500);
}
