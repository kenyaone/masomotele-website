<?php
/**
 * M.T.T.I LMS - Re-link lesson files to lessons
 * Run once from browser or CLI: php relink-files.php
 * Place in: /var/www/html/mtti-lms/relink-files.php
 */
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();

$videoDir = __DIR__ . '/assets/uploads/videos/';
$fileDir  = __DIR__ . '/assets/uploads/files/';

$linked = 0;
$skipped = 0;
$unmatched = [];

// Get all lessons
$lessons = $db->fetchAll("SELECT id, title, subject_id FROM lessons ORDER BY id");

// Build a lookup: normalized title => lesson_id
$lessonMap = [];
foreach ($lessons as $l) {
    $key = normalizeTitle($l['title']);
    $lessonMap[$key] = $l['id'];
}

function normalizeTitle($title) {
    $t = strtolower($title);
    $t = preg_replace('/[^a-z0-9]+/', ' ', $t);
    $t = trim($t);
    return $t;
}

function fileToTitle($filename) {
    // Remove timestamp prefix like 1772629299_
    $name = preg_replace('/^\d+_\d*_?/', '', $filename);
    // Remove extension
    $name = preg_replace('/\.(mp4|webm|pdf|doc|docx|jpg|png|gif)(\.\w+)?$/i', '', $name);
    // Replace underscores/hyphens with spaces
    $name = str_replace(['_', '-'], ' ', $name);
    // Remove extra spaces
    $name = trim(preg_replace('/\s+/', ' ', $name));
    return $name;
}

function findBestLesson($filename, $lessonMap) {
    $raw   = fileToTitle($filename);
    $norm  = normalizeTitle($raw);
    $words = explode(' ', $norm);

    // Exact match
    if (isset($lessonMap[$norm])) return $lessonMap[$norm];

    // Partial match — find lesson with most overlapping words
    $best = null;
    $bestScore = 0;
    foreach ($lessonMap as $key => $lid) {
        $lessonWords = explode(' ', $key);
        $common = count(array_intersect($words, $lessonWords));
        $score  = $common / max(count($words), count($lessonWords));
        if ($score > $bestScore && $score >= 0.4) {
            $bestScore = $score;
            $best = $lid;
        }
    }
    return $best;
}

echo "<pre style='font-family:monospace;font-size:13px;padding:20px'>";
echo "M.T.T.I LMS — Re-linking Lesson Files\n";
echo str_repeat("=", 60) . "\n\n";

// Process videos
echo "📹 VIDEOS\n" . str_repeat("-", 40) . "\n";
foreach (scandir($videoDir) as $file) {
    if ($file === '.' || $file === '..') continue;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4','webm','ogg','avi','mkv'])) continue;

    // Skip if already linked
    $exists = $db->fetchOne("SELECT id FROM lesson_files WHERE filename=?", [$file]);
    if ($exists) { echo "  ⏭  SKIP (already linked): $file\n"; $skipped++; continue; }

    $lessonId = findBestLesson($file, $lessonMap);
    if ($lessonId) {
        $db->insert('lesson_files', [
            'lesson_id'     => $lessonId,
            'original_name' => fileToTitle($file) . '.' . $ext,
            'filename'      => $file,
            'filepath'      => 'assets/uploads/videos/' . $file,
            'filetype'      => 'video',
            'filesize'      => filesize($videoDir . $file),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $lessonTitle = '';
        foreach ($lessons as $l) { if ($l['id'] == $lessonId) { $lessonTitle = $l['title']; break; } }
        echo "  ✅ $file\n     → Lesson $lessonId: $lessonTitle\n";
        $linked++;
    } else {
        echo "  ❌ UNMATCHED: $file\n";
        $unmatched[] = $file;
    }
}

// Process PDFs/docs
echo "\n📄 FILES (PDF/DOC)\n" . str_repeat("-", 40) . "\n";
foreach (scandir($fileDir) as $file) {
    if ($file === '.' || $file === '..') continue;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf','doc','docx','ppt','pptx'])) continue;

    // Skip past papers (numbered 46-108 range or KCSE in name)
    if (preg_match('/KCSE|KNEC|PAPER|ANSWERS|REVISION/i', $file)) {
        echo "  ⏭  SKIP (past paper): $file\n";
        $skipped++;
        continue;
    }

    $exists = $db->fetchOne("SELECT id FROM lesson_files WHERE filename=?", [$file]);
    if ($exists) { echo "  ⏭  SKIP (already linked): $file\n"; $skipped++; continue; }

    $lessonId = findBestLesson($file, $lessonMap);
    if ($lessonId) {
        $db->insert('lesson_files', [
            'lesson_id'     => $lessonId,
            'original_name' => fileToTitle($file) . '.' . $ext,
            'filename'      => $file,
            'filepath'      => 'assets/uploads/files/' . $file,
            'filetype'      => $ext,
            'filesize'      => filesize($fileDir . $file),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $lessonTitle = '';
        foreach ($lessons as $l) { if ($l['id'] == $lessonId) { $lessonTitle = $l['title']; break; } }
        echo "  ✅ $file\n     → Lesson $lessonId: $lessonTitle\n";
        $linked++;
    } else {
        echo "  ❌ UNMATCHED: $file\n";
        $unmatched[] = $file;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Linked:   $linked files\n";
echo "⏭  Skipped:  $skipped files\n";
echo "❌ Unmatched: " . count($unmatched) . " files\n";

if (!empty($unmatched)) {
    echo "\nUnmatched files (need manual linking):\n";
    foreach ($unmatched as $u) echo "  - $u\n";
}

echo "\nDone! Refresh the LMS to see your content.\n";
echo "</pre>";
?>
