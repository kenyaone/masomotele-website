<?php
/**
 * MTTI Schools Map — Sync Receiver
 * Accepts JSON push from local MTTI LMS, stores in SQLite
 * Self-initialises DB on first run.
 */
define('SYNC_KEY', 'MTTI_SAGAAS_DP_K3Y_2026');
define('DB_FILE', __DIR__ . '/../data/mtti_map.db');

header('Content-Type: application/json');

// Allow CORS from LAN origins
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Sync-Key');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Auth
$key = $_SERVER['HTTP_X_SYNC_KEY'] ?? ($_POST['sync_key'] ?? '');
if ($key !== SYNC_KEY) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }

// Ensure data dir exists
$dataDir = dirname(DB_FILE);
if (!is_dir($dataDir)) @mkdir($dataDir, 0750, true);

// Open/init DB
try {
    $db = new SQLite3(DB_FILE);
    $db->busyTimeout(5000);
    $db->exec("PRAGMA journal_mode=WAL;");
    $db->exec("CREATE TABLE IF NOT EXISTS schools (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        name         TEXT NOT NULL UNIQUE,
        location     TEXT,
        lat          REAL DEFAULT 0,
        lng          REAL DEFAULT 0,
        grade_level  TEXT,
        enrolled     INTEGER DEFAULT 0,
        active_30d   INTEGER DEFAULT 0,
        total_subjects INTEGER DEFAULT 0,
        total_lessons INTEGER DEFAULT 0,
        lessons_completed INTEGER DEFAULT 0,
        quiz_attempts INTEGER DEFAULT 0,
        avg_score    REAL,
        certificates INTEGER DEFAULT 0,
        synced_at    DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS sync_meta (
        id INTEGER PRIMARY KEY,
        last_sync_at DATETIME,
        source TEXT,
        school_count INTEGER
    )");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB init failed: ' . $e->getMessage()]);
    exit;
}

// Parse payload
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!$payload || !isset($payload['schools'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload — expected {schools:[...]}']);
    exit;
}

$schools = $payload['schools'];
$source  = $payload['source'] ?? 'unknown';
$saved   = 0;

foreach ($schools as $s) {
    $name     = $db->escapeString(trim($s['name'] ?? ''));
    $location = $db->escapeString($s['location'] ?? '');
    $lat      = (float)($s['lat'] ?? 0);
    $lng      = (float)($s['lng'] ?? 0);
    $gl       = $db->escapeString($s['grade_level'] ?? '');
    $enrolled = (int)($s['enrolled'] ?? 0);
    $a30      = (int)($s['active_30d'] ?? 0);
    $tsub     = (int)($s['total_subjects'] ?? 0);
    $tles     = (int)($s['total_lessons'] ?? 0);
    $comp     = (int)($s['lessons_completed'] ?? 0);
    $qa       = (int)($s['quiz_attempts'] ?? 0);
    $avg      = $s['avg_score'] !== null ? (float)$s['avg_score'] : 'NULL';
    $certs    = (int)($s['certificates'] ?? 0);

    if (!$name) continue;

    $avgVal = ($avg === 'NULL') ? 'NULL' : $avg;
    $db->exec("INSERT INTO schools
        (name,location,lat,lng,grade_level,enrolled,active_30d,total_subjects,total_lessons,
         lessons_completed,quiz_attempts,avg_score,certificates,synced_at)
        VALUES ('$name','$location',$lat,$lng,'$gl',$enrolled,$a30,$tsub,$tles,
                $comp,$qa,$avgVal,$certs,datetime('now'))
        ON CONFLICT(name) DO UPDATE SET
            location=excluded.location, lat=excluded.lat, lng=excluded.lng,
            grade_level=excluded.grade_level, enrolled=excluded.enrolled,
            active_30d=excluded.active_30d, total_subjects=excluded.total_subjects,
            total_lessons=excluded.total_lessons, lessons_completed=excluded.lessons_completed,
            quiz_attempts=excluded.quiz_attempts, avg_score=excluded.avg_score,
            certificates=excluded.certificates, synced_at=datetime('now')");
    $saved++;
}

// Update meta
$db->exec("DELETE FROM sync_meta; INSERT INTO sync_meta (last_sync_at, source, school_count)
           VALUES (datetime('now'), '" . $db->escapeString($source) . "', $saved)");

echo json_encode(['ok' => true, 'saved' => $saved, 'ts' => date('c')]);
