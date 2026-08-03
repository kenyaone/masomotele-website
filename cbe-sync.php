<?php
// CBE box → cloud sync receiver.
// Boxes POST an aggregated per-device summary; we upsert into cbe_devices.
// Companion map: cbe-locations.php.

declare(strict_types=1);
header('Content-Type: application/json');

const CBE_SYNC_SECRET = 'cbe_sync_k3nya_2026';

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (($_POST['secret'] ?? '') !== CBE_SYNC_SECRET) fail('bad secret', 403);

$payload = json_decode($_POST['payload'] ?? '', true);
if (!is_array($payload)) fail('bad payload');

$deviceId = trim((string)($payload['deviceId'] ?? ''));
if ($deviceId === '') fail('missing device_id');

// DB credentials live outside public_html so they never enter git.
// Create /home/uvyzhdzt/mtti_db_config.php with:
//   <?php return ['host'=>'localhost','user'=>'uvyzhdzt_mtti','pass'=>'...','db'=>'uvyzhdzt_mtti'];
$cfgPath = '/home/uvyzhdzt/mtti_db_config.php';
if (!is_file($cfgPath)) fail('db config missing', 500);
$cfg = require $cfgPath;
$m = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db']);
if ($m->connect_errno) fail('db: '.$m->connect_error, 500);
$m->set_charset('utf8mb4');

// One-time bootstrap; noop on subsequent calls.
$m->query("CREATE TABLE IF NOT EXISTS cbe_devices (
    device_id               VARCHAR(64)  NOT NULL PRIMARY KEY,
    school_name             VARCHAR(191) DEFAULT '',
    county                  VARCHAR(64)  DEFAULT '',
    region                  VARCHAR(64)  DEFAULT '',
    lat                     DOUBLE       DEFAULT NULL,
    lng                     DOUBLE       DEFAULT NULL,
    learner_count           INT          DEFAULT 0,
    lesson_completions      INT          DEFAULT 0,
    quiz_attempts           INT          DEFAULT 0,
    avg_score               DOUBLE       DEFAULT NULL,
    cert_count              INT          DEFAULT 0,
    active_last_30          INT          DEFAULT 0,
    app_version             VARCHAR(32)  DEFAULT '',
    avg_sync_interval_secs  INT          DEFAULT NULL,
    learner_count_prev      INT          DEFAULT NULL,
    first_sync_at           DATETIME     NULL,
    last_sync_at            DATETIME     NULL,
    INDEX idx_county (county),
    INDEX idx_last_sync (last_sync_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$m->begin_transaction();
try {
    $chk = $m->prepare('SELECT last_sync_at, avg_sync_interval_secs, learner_count FROM cbe_devices WHERE device_id=?');
    $chk->bind_param('s', $deviceId); $chk->execute();
    $existing = $chk->get_result()->fetch_assoc(); $chk->close();

    $avgInterval = null;
    if ($existing && $existing['last_sync_at']) {
        $gap = max(0, time() - strtotime($existing['last_sync_at']));
        $old = $existing['avg_sync_interval_secs'];
        $avgInterval = (int)($old ? (0.7 * $old + 0.3 * $gap) : $gap);
    }
    $prevLearners = $existing ? (int)$existing['learner_count'] : null;

    $name        = (string)($payload['school_name']         ?? '');
    $county      = (string)($payload['county']              ?? '');
    $region      = (string)($payload['region']              ?? '');
    $lat         = isset($payload['lat']) && is_numeric($payload['lat']) ? (float)$payload['lat'] : null;
    $lng         = isset($payload['lng']) && is_numeric($payload['lng']) ? (float)$payload['lng'] : null;
    $learners    = (int)($payload['learner_count']          ?? 0);
    $lessons     = (int)($payload['lesson_completions']     ?? 0);
    $quizzes     = (int)($payload['quiz_attempts']          ?? 0);
    $avgScore    = isset($payload['avg_score']) && is_numeric($payload['avg_score']) ? (float)$payload['avg_score'] : null;
    $certs       = (int)($payload['cert_count']             ?? 0);
    $active30    = (int)($payload['active_last_30']         ?? 0);
    $appVersion  = (string)($payload['app_version']         ?? '');

    // Upsert — school_name/county/region locked on first sync.
    $stmt = $m->prepare('INSERT INTO cbe_devices
        (device_id, school_name, county, region, lat, lng,
         learner_count, lesson_completions, quiz_attempts, avg_score,
         cert_count, active_last_30, app_version,
         avg_sync_interval_secs, learner_count_prev,
         first_sync_at, last_sync_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
        ON DUPLICATE KEY UPDATE
            learner_count_prev      = learner_count,
            learner_count           = VALUES(learner_count),
            lesson_completions      = VALUES(lesson_completions),
            quiz_attempts           = VALUES(quiz_attempts),
            avg_score               = VALUES(avg_score),
            cert_count              = VALUES(cert_count),
            active_last_30          = VALUES(active_last_30),
            app_version             = VALUES(app_version),
            avg_sync_interval_secs  = VALUES(avg_sync_interval_secs),
            lat                     = IFNULL(VALUES(lat), lat),
            lng                     = IFNULL(VALUES(lng), lng),
            last_sync_at            = NOW()
            /* school_name, county, region locked on first sync */'
    );
    $stmt->bind_param('ssssddiiididisii',
        $deviceId, $name, $county, $region, $lat, $lng,
        $learners, $lessons, $quizzes, $avgScore,
        $certs, $active30, $appVersion,
        $avgInterval, $prevLearners);
    $stmt->execute(); $stmt->close();

    $m->commit();
    echo json_encode(['ok' => true, 'device' => $deviceId]);
} catch (Throwable $e) {
    $m->rollback();
    fail('write failed: '.$e->getMessage(), 500);
}
