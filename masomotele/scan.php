<?php
// ═══════════════════════════════════════════════════════════
// MASOMOTELE — FILE SCANNER
// Scans interactives/, videos/, papers/ folders and
// auto-maps files to subjects, rebuilds lessons.json
// 
// USAGE: Upload to masomotele/ folder, run once, then DELETE
// URL:   masomoteletraining.co.ke/masomotele/scan.php
// ═══════════════════════════════════════════════════════════

// ── SECURITY: simple token check ─────────────────────────
define('SCAN_TOKEN', 'mtti_scan_2026');
if (($_GET['token'] ?? '') !== SCAN_TOKEN) {
    http_response_code(403);
    die('<h2>Access denied.</h2><p>Add ?token=mtti_scan_2026 to the URL.</p>');
}

define('BASE_DIR',     __DIR__);
define('LESSONS_JSON', BASE_DIR . '/lessons.json');

// ── CLEAN TITLE from filename ─────────────────────────────
function cleanTitle($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    // Replace multiple dashes/underscores with single space
    $name = preg_replace('/[-_]{2,}/', ' — ', $name); // double dash → em dash
    $name = preg_replace('/[-_]/', ' ', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));
    return ucwords(strtolower($name));
}

// ── KEYWORD → SUBJECT MAPPING ────────────────────────────
// Maps filename keywords to subject IDs
// Order matters — more specific patterns first
$KEYWORD_MAP = [

  // ── MATHEMATICS ──
  'f4-math' => ['matrices','matrix','sequences','series','binomial','probability',
    'vectors','trigonometry','statistics','loci','linear.program','longitude',
    'latitude','differentiation','integration','approximation','formulae',
    'variation','logarithm','surds','quadratic','commercial.arithm',
    'compound.proportion','graphical','circles','chords','tangents',
    'linear programming','loci','longitudes and latitudes'],
  'f3-math' => [],  // shared with f4-math by keyword, resolved by context

  // ── PHYSICS ──
  'f4-phys' => ['lenses','lens','circular.motion','floating','sinking',
    'electromagnetic.spectrum','electromagnetic.induction','mains.electricity',
    'cathode.ray','x.ray','photoelectric','radioactivity','electronics',
    'cathode-ray','electromag'],
  'f3-phys' => ['waves','sound','light','electrostatics','heating.effect'],

  // ── CHEMISTRY ──
  'f4-chem' => ['organic.chemistry','radioactiv','electrochemistry','electrolysis',
    'chlorine','acids','bases','salts','reaction.rate','equilibrium','metals',
    'energy.changes','nitrogen','sulphur','haber','contact.process'],
  'f3-chem' => [],

  // ── BIOLOGY ──
  'f4-bio'  => ['genetics','heredity','variation','evolution','classification',
    'growth','hormones','nervous','drugs','immunity','excretion','coordination'],
  'f3-bio'  => ['transport','reproduction','ecology'],

  // ── BUSINESS STUDIES ──
  'f4-bs'   => ['business','entrepreneurship','marketing','finance','accounting',
    'ledger','trial.balance','profit','loss','trade','commerce','insurance'],
  'f3-bs'   => [],

  // ── HISTORY ──
  'f4-hist' => ['history','africa','colonial','independence','governance','politics',
    'revolution','war','nationalism'],
  'f3-hist' => [],

  // ── GEOGRAPHY ──
  'f4-geo'  => ['geography','map','climate','population','resources','environment',
    'drainage','relief','vegetation','soils','land.use'],
  'f3-geo'  => [],

  // ── CRE / IRE ──
  'f4-cre'  => ['cre','ire','christian','islam','religion','bible','faith',
    'moral','ethics','church'],
  'f3-cre'  => [],

  // ── AGRICULTURE ──
  'f4-agri' => ['agriculture','farming','crops','livestock','soil','irrigation',
    'pest','disease','farm'],
  'f3-agri' => [],

  // ── HOME SCIENCE ──
  'f4-hs'   => ['home.science','nutrition','food','clothing','textiles',
    'family','budgeting','meal'],
  'f3-hs'   => [],

  // ── ENGLISH ──
  'f4-eng'  => ['english','comprehension','essay','composition','grammar',
    'literature','novel','poetry','play','oral','listening'],
  'f3-eng'  => [],

  // ── KISWAHILI ──
  'f4-kisw' => ['kiswahili','swahili','fasihi','sarufi','insha','hadithi',
    'kisw','lugha'],
  'f3-kisw' => [],

  // ── GRADE 10 subjects ──
  'g10-math'=> [],
  'g10-phys'=> [],
  'g10-chem'=> [],
  'g10-bio' => [],
  'g10-cs'  => ['computer.studies','ict','internet','hardware','software',
    'programming','spreadsheet','database','networking'],
  'g10-bs'  => [],
  'g10-elec'=> ['electrical','circuit','ohm','capacitor','battery','magnetism',
    'semiconductor','transistor','diode','dc.circuit','ac.circuit','installation'],
  'g10-pm'  => ['power.mechanic','engine','hydraulic','pneumatic','transmission'],

  // ── INTEGRATED SCIENCE (Grade 7-9) ──
  'g9-sci'  => ['integrated.science','science'],
  'g8-sci'  => [],
  'g7-sci'  => [],
];

// ── SUBJECT LABELS (for display) ─────────────────────────
$SUBJ_LABELS = [
  'f4-math'=>'Form 4 Mathematics', 'f4-phys'=>'Form 4 Physics',
  'f4-chem'=>'Form 4 Chemistry',   'f4-bio'=>'Form 4 Biology',
  'f4-eng'=>'Form 4 English',      'f4-kisw'=>'Form 4 Kiswahili',
  'f4-hist'=>'Form 4 History',     'f4-geo'=>'Form 4 Geography',
  'f4-bs'=>'Form 4 Business',      'f4-cre'=>'Form 4 CRE/IRE',
  'f4-agri'=>'Form 4 Agriculture', 'f4-hs'=>'Form 4 Home Science',
  'f3-math'=>'Form 3 Mathematics', 'f3-phys'=>'Form 3 Physics',
  'f3-chem'=>'Form 3 Chemistry',   'f3-bio'=>'Form 3 Biology',
  'f3-eng'=>'Form 3 English',      'f3-kisw'=>'Form 3 Kiswahili',
  'f3-hist'=>'Form 3 History',     'f3-geo'=>'Form 3 Geography',
  'f3-bs'=>'Form 3 Business',      'f3-cre'=>'Form 3 CRE/IRE',
  'f3-agri'=>'Form 3 Agriculture', 'f3-hs'=>'Form 3 Home Science',
  'g10-math'=>'Grade 10 Mathematics','g10-phys'=>'Grade 10 Physics',
  'g10-chem'=>'Grade 10 Chemistry', 'g10-bio'=>'Grade 10 Biology',
  'g10-cs'=>'Grade 10 Comp. Studies','g10-bs'=>'Grade 10 Business',
  'g10-elec'=>'Grade 10 Electrical', 'g10-pm'=>'Grade 10 Power Mechanics',
  'g9-sci'=>'Grade 9 Int. Science',
];

// ── MATCH filename to subject ID ──────────────────────────
function matchSubject($filename, $KEYWORD_MAP) {
    $lower = strtolower($filename);

    // First try: explicit subject prefix in filename
    // e.g. f4-math-matrices.html, g10-phys-lenses.html
    if (preg_match('/^(f[34]|g\d+)[-_](math|phys|chem|bio|eng|kisw|hist|geo|bs|cre|agri|hs|cs|elec|pm|sci)/i', $lower, $m)) {
        $cls  = strtolower($m[1]);
        $subj = strtolower($m[2]);
        $sid  = $cls . '-' . $subj;
        if (isset($KEYWORD_MAP[$sid])) return $sid;
    }

    // Second try: keyword matching
    foreach ($KEYWORD_MAP as $sid => $keywords) {
        foreach ($keywords as $kw) {
            if (!$kw) continue;
            // Convert keyword pattern to regex
            $pattern = str_replace(['.', ' '], ['[^a-z0-9]*', '[^a-z0-9]*'], $kw);
            if (preg_match('/' . $pattern . '/i', $lower)) {
                return $sid;
            }
        }
    }

    // Third try: known topic → subject mapping
    $topicMap = [
        // Physics topics
        'lenses' => 'f4-phys', 'cathode' => 'f4-phys', 'photoelectric' => 'f4-phys',
        'electromagnetic' => 'f4-phys', 'floating' => 'f4-phys', 'electronics' => 'f4-phys',
        'radioactivity' => 'f4-phys', 'circular' => 'f4-phys',
        // Chemistry topics
        'acids' => 'f4-chem', 'bases' => 'f4-chem', 'salts' => 'f4-chem',
        'electrochemistry' => 'f4-chem', 'electrolysis' => 'f4-chem',
        'organic' => 'f4-chem', 'chlorine' => 'f4-chem',
        'energy changes' => 'f4-chem', 'reaction' => 'f4-chem',
        // Maths topics
        'matrices' => 'f4-math', 'differentiation' => 'f4-math',
        'integration' => 'f4-math', 'approximation' => 'f4-math',
        'linear programming' => 'f4-math', 'loci' => 'f4-math',
        'longitudes' => 'f4-math', 'latitudes' => 'f4-math',
        'binomial' => 'f4-math', 'probability' => 'f4-math',
        'sequences' => 'f4-math', 'series' => 'f4-math',
        'trigonometry' => 'f4-math', 'statistics' => 'f4-math',
        'vectors' => 'f4-math', 'energy' => 'f4-phys',
        // Biology topics
        'genetics' => 'f4-bio', 'heredity' => 'f4-bio', 'variation' => 'f4-bio',
        'evolution' => 'f4-bio',
        // Electrical
        'circuit' => 'g10-elec', 'ohm' => 'g10-elec', 'capacitor' => 'g10-elec',
    ];

    foreach ($topicMap as $topic => $sid) {
        if (stripos($lower, str_replace(' ', '', $topic)) !== false ||
            stripos($lower, $topic) !== false) {
            return $sid;
        }
    }

    return null; // unmatched
}

// ── SCAN FILES ────────────────────────────────────────────
$dirs = [
    'inter' => BASE_DIR . '/interactives/',
    'vid'   => BASE_DIR . '/videos/',
    'pdf'   => BASE_DIR . '/papers/',
];
$exts = [
    'inter' => ['html','htm'],
    'vid'   => ['mp4','webm','mov','mkv'],
    'pdf'   => ['pdf'],
];

$result    = []; // lessons.json data
$mapped    = []; // successfully mapped files
$unmapped  = []; // files that couldn't be matched
$skipped   = []; // non-media files

foreach ($dirs as $type => $dir) {
    if (!is_dir($dir)) continue;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $exts[$type])) { $skipped[] = "$type/$file"; continue; }

        $sid = matchSubject($file, $KEYWORD_MAP);
        $title = cleanTitle($file);

        if ($sid) {
            if (!isset($result[$sid])) $result[$sid] = ['inter'=>[],'vid'=>[],'pdf'=>[]];
            $entry = ['title'=>$title, 'file'=>$file];
            if ($type === 'vid') $entry['duration'] = '';
            if ($type === 'pdf') $entry['year'] = '';
            $result[$sid][$type][] = $entry;
            $mapped[] = ['type'=>$type, 'file'=>$file, 'subject'=>$sid, 'title'=>$title];
        } else {
            $unmapped[] = ['type'=>$type, 'file'=>$file];
        }
    }
}

// ── LOAD EXISTING lessons.json ────────────────────────────
$existing = [];
if (file_exists(LESSONS_JSON)) {
    $d = json_decode(file_get_contents(LESSONS_JSON), true);
    if (is_array($d)) $existing = $d;
}

// ── WRITE if confirmed ────────────────────────────────────
$written = false;
$writeMsg = '';
if (isset($_GET['write']) && $_GET['write'] === 'yes') {
    // Merge with existing — new scan wins for matched files
    $merged = $existing;
    foreach ($result as $sid => $content) {
        if (!isset($merged[$sid])) $merged[$sid] = ['inter'=>[],'vid'=>[],'pdf'=>[]];
        // Add files not already registered
        foreach (['inter','vid','pdf'] as $t) {
            $existingFiles = array_column($merged[$sid][$t]??[], 'file');
            foreach ($content[$t] as $entry) {
                if (!in_array($entry['file'], $existingFiles)) {
                    $merged[$sid][$t][] = $entry;
                }
            }
        }
    }
    file_put_contents(LESSONS_JSON, json_encode($merged, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    $written = true;
    $writeMsg = 'lessons.json updated successfully!';
}

// ── STATS ─────────────────────────────────────────────────
$totalFiles   = count($mapped) + count($unmapped);
$mappedCount  = count($mapped);
$unmappedCount= count($unmapped);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masomotele File Scanner</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f0f4f1;color:#1a2e1f;padding:20px;}
h1{color:#0a5e2a;font-size:1.4rem;margin-bottom:4px;}
.sub{color:#6b7a6e;font-size:.82rem;margin-bottom:24px;}
.stats{display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;}
.stat{background:white;border-radius:12px;padding:16px 20px;border:1.5px solid #d4e6d9;text-align:center;min-width:120px;}
.stat strong{display:block;font-size:1.8rem;font-weight:900;color:#0a5e2a;}
.stat span{font-size:.72rem;color:#6b7a6e;}
.card{background:white;border-radius:12px;padding:18px;margin-bottom:16px;border:1.5px solid #d4e6d9;}
.card h2{font-size:.95rem;font-weight:800;margin-bottom:12px;padding-left:10px;border-left:3px solid #f5a623;}
.alert-ok{background:#e8f5ed;color:#0a5e2a;border:1px solid #c8e6c9;padding:12px 16px;border-radius:10px;font-weight:700;margin-bottom:20px;}
.alert-warn{background:#fff8e1;color:#b45309;border:1px solid #fde68a;padding:12px 16px;border-radius:10px;margin-bottom:20px;}
table{width:100%;border-collapse:collapse;font-size:.8rem;}
th{background:#f4f7f5;padding:7px 10px;text-align:left;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#6b7a6e;border-bottom:2px solid #d4e6d9;}
td{padding:7px 10px;border-bottom:1px solid #eef3ef;}
tr:hover td{background:#fafcfa;}
.badge{display:inline-block;padding:2px 7px;border-radius:5px;font-size:.62rem;font-weight:800;}
.bi{background:#e8f5ed;color:#0a5e2a;} .bv{background:#fff8e1;color:#e0951a;} .bp{background:#fce4ec;color:#c62828;}
.bu{background:#fef3c7;color:#92400e;}
.btn{display:inline-block;padding:11px 24px;background:#0a5e2a;color:white;border-radius:8px;font-weight:700;font-size:.88rem;text-decoration:none;margin-right:10px;}
.btn-orange{background:#f5a623;color:#064a1f;}
.btn-red{background:#c62828;color:white;}
.actions{margin:20px 0;}
.warn-box{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;margin-bottom:16px;font-size:.82rem;color:#dc2626;}
</style>
</head>
<body>

<h1>📂 Masomotele File Scanner</h1>
<p class="sub">Scans your videos/, interactives/ and papers/ folders and maps files to subjects</p>

<?php if ($written): ?>
<div class="alert-ok">✅ <?= $writeMsg ?> — <a href="?token=<?=SCAN_TOKEN?>" style="color:#0a5e2a;font-weight:700">View scan results</a> | <a href="admin/" style="color:#0a5e2a;font-weight:700">Go to Admin Panel →</a></div>
<?php endif; ?>

<div class="stats">
  <div class="stat"><strong><?= $totalFiles ?></strong><span>Total Files</span></div>
  <div class="stat"><strong style="color:#2e7d32"><?= $mappedCount ?></strong><span>Mapped ✅</span></div>
  <div class="stat"><strong style="color:#c62828"><?= $unmappedCount ?></strong><span>Unmatched ⚠️</span></div>
  <div class="stat"><strong><?= count($result) ?></strong><span>Subjects Found</span></div>
</div>

<?php if ($unmappedCount > 0): ?>
<div class="warn-box">
  ⚠️ <strong><?= $unmappedCount ?> files could not be matched</strong> to a subject automatically.
  You can add these manually through the Admin Panel → Upload tab.
</div>
<?php endif; ?>

<?php if (!$written): ?>
<div class="actions">
  <div class="alert-warn" style="margin-bottom:14px">
    ⚠️ <strong>Review the matches below before saving.</strong>
    Click "Save to lessons.json" only when you are satisfied with the mapping.
  </div>
  <a href="?token=<?=SCAN_TOKEN?>&write=yes" class="btn btn-orange">💾 Save to lessons.json</a>
  <a href="admin/" class="btn">Go to Admin Panel</a>
</div>
<?php endif; ?>

<!-- MAPPED FILES -->
<div class="card">
  <h2>✅ Mapped Files (<?= $mappedCount ?>)</h2>
  <?php if ($mappedCount === 0): ?>
    <p style="color:#6b7a6e;font-size:.85rem">No files found in the folders.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>File</th><th>Type</th><th>Mapped To</th><th>Title Generated</th></tr></thead>
    <tbody>
    <?php foreach ($mapped as $m): ?>
      <tr>
        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.72rem;color:#6b7a6e"
            title="<?= htmlspecialchars($m['file']) ?>"><?= htmlspecialchars($m['file']) ?></td>
        <td>
          <?php if($m['type']==='inter') echo '<span class="badge bi">Lesson</span>';
                elseif($m['type']==='vid') echo '<span class="badge bv">Video</span>';
                else echo '<span class="badge bp">PDF</span>'; ?>
        </td>
        <td style="font-size:.75rem;font-weight:700;color:#0a5e2a">
          <?= htmlspecialchars($SUBJ_LABELS[$m['subject']] ?? $m['subject']) ?>
        </td>
        <td style="font-size:.78rem"><?= htmlspecialchars($m['title']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- UNMAPPED FILES -->
<?php if ($unmappedCount > 0): ?>
<div class="card">
  <h2>⚠️ Unmatched Files (<?= $unmappedCount ?>) — Need Manual Assignment</h2>
  <p style="font-size:.78rem;color:#6b7a6e;margin-bottom:10px">
    These files exist on the server but couldn't be matched to a subject.
    Upload them manually via Admin → Upload tab to register them.
  </p>
  <table>
    <thead><tr><th>File</th><th>Type</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($unmapped as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['file']) ?></td>
        <td><span class="badge bu">Unmatched</span></td>
        <td style="font-size:.72rem;color:#6b7a6e">Add via Admin → Upload</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- SUBJECT SUMMARY -->
<?php if (!empty($result)): ?>
<div class="card">
  <h2>📊 Summary by Subject</h2>
  <table>
    <thead><tr><th>Subject</th><th>Lessons</th><th>Videos</th><th>Papers</th></tr></thead>
    <tbody>
    <?php foreach ($result as $sid => $c): ?>
      <tr>
        <td style="font-weight:700"><?= htmlspecialchars($SUBJ_LABELS[$sid] ?? $sid) ?></td>
        <td><span class="badge bi"><?= count($c['inter']) ?></span></td>
        <td><span class="badge bv"><?= count($c['vid']) ?></span></td>
        <td><span class="badge bp"><?= count($c['pdf']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="warn-box" style="margin-top:20px">
  🔒 <strong>Security reminder:</strong> Delete this file (scan.php) from your server after use.
  Anyone with the URL can rewrite your lessons.json.
</div>

</body>
</html>
