<?php
/**
 * M.T.T.I LMS - Smart Upload: KCSE Past Papers
 * Drop files → auto-detect subject, year, paper#, type → upload
 * Place in: /var/www/html/mtti-lms/smart-upload-papers.php
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
if (!in_array($auth->getRole(), ['admin', 'teacher'])) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }
$db = Database::getInstance();
$userId = $auth->getUserId();

// Get KCSE subjects for matching
$kcseSubjects = $db->fetchAll("SELECT * FROM kcse_subjects ORDER BY sort_order");
$subjectNames = array_column($kcseSubjects, 'name');

$results = null; $successCount = 0; $errorCount = 0;

// Process upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'smart_upload_papers') {
    $assignments = json_decode($_POST['assignments'] ?? '[]', true);
    $uploaded = $_FILES['files'] ?? null;
    $results = [];

    if ($uploaded && is_array($uploaded['name'])) {
        $dir = 'assets/uploads/past-papers/';
        @mkdir(__DIR__ . '/' . $dir, 0755, true);

        for ($i = 0; $i < count($uploaded['name']); $i++) {
            if ($uploaded['error'][$i] !== UPLOAD_ERR_OK || empty($uploaded['name'][$i])) continue;

            $origName = $uploaded['name'][$i];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'html', 'htm'])) {
                $results[] = ['name' => $origName, 'status' => 'error', 'msg' => "Only PDF/HTML allowed"];
                $errorCount++; continue;
            }

            // Get assignment from JS
            $assign = null;
            foreach ($assignments as $a) {
                if ($a['filename'] === $origName) { $assign = $a; break; }
            }

            $subject = $assign['subject'] ?? '';
            $year = intval($assign['year'] ?? 0);
            $paperNum = intval($assign['paper_number'] ?? 1);
            $paperType = $assign['paper_type'] ?? 'question';

            if (!$subject || !$year) {
                $results[] = ['name' => $origName, 'status' => 'error', 'msg' => "Missing subject or year"];
                $errorCount++; continue;
            }

            $typeLabel = $paperType === 'marking_scheme' ? 'Marking Scheme' : 'Paper ' . $paperNum;
            $title = "$subject KCSE $year - $typeLabel";

            $safeName = strtolower(str_replace(' ', '-', $subject)) . '-' . $year . '-p' . $paperNum . '-' . ($paperType === 'marking_scheme' ? 'ms' : 'qp') . '-' . time() . '.' . $ext;
            $destPath = $dir . $safeName;

            if (move_uploaded_file($uploaded['tmp_name'][$i], __DIR__ . '/' . $destPath)) {
                try {
                    // Check duplicate
                    $exists = $db->fetchOne("SELECT id FROM past_papers WHERE subject=? AND year=? AND paper_number=? AND paper_type=?", [$subject, $year, $paperNum, $paperType]);
                    if ($exists) {
                        $db->query("UPDATE past_papers SET file_path=?, file_size=?, format=?, title=?, updated_at=NOW() WHERE id=?", [$destPath, $uploaded['size'][$i], $ext === 'pdf' ? 'pdf' : 'html', $title, $exists['id']]);
                        $results[] = ['name' => $origName, 'status' => 'success', 'msg' => "$title (updated existing)"];
                    } else {
                        $db->insert('past_papers', [
                            'subject' => $subject, 'year' => $year, 'paper_number' => $paperNum,
                            'title' => $title, 'paper_type' => $paperType,
                            'format' => $ext === 'pdf' ? 'pdf' : 'html',
                            'file_path' => $destPath, 'file_size' => $uploaded['size'][$i],
                            'uploaded_by' => $userId, 'status' => 'active',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                        $results[] = ['name' => $origName, 'status' => 'success', 'msg' => $title];
                    }
                    $successCount++;
                } catch (Exception $e) {
                    $results[] = ['name' => $origName, 'status' => 'error', 'msg' => $e->getMessage()];
                    $errorCount++;
                }
            } else {
                $results[] = ['name' => $origName, 'status' => 'error', 'msg' => 'Move failed'];
                $errorCount++;
            }
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>
<style>
:root{--pp:#1e3a5f;--pp2:#2d6a9f;--acc:#f59e0b;--grn:#16a34a;--red:#dc2626}
body{background:#f5f6f8;font-family:'DM Sans',sans-serif}
.pp-hdr{background:linear-gradient(135deg,var(--pp),var(--pp2));color:#fff;padding:1.5rem 2rem;border-radius:0 0 1rem 1rem;margin:-1rem -1rem 1.5rem}
.pp-hdr h4{margin:0}.pp-hdr small{color:var(--acc)}
.drop-zone{border:3px dashed #b8c4d3;border-radius:1rem;padding:3rem 2rem;text-align:center;cursor:pointer;transition:all .3s;background:#f8fafc}
.drop-zone:hover,.drop-zone.dragover{border-color:var(--pp);background:#e8f0f8;transform:scale(1.003)}
.drop-zone .icon{font-size:3.5rem;color:var(--pp)}
.file-row{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;margin-bottom:6px;background:#fff;border:1px solid #f1f5f9;transition:all .2s;animation:slideIn .3s ease-out}
@keyframes slideIn{from{opacity:0;transform:translateX(-15px)}to{opacity:1;transform:translateX(0)}}
.file-row.match-high{border-left:4px solid var(--grn);background:#f0fdf4}
.file-row.match-medium{border-left:4px solid var(--acc);background:#fffbeb}
.file-row.match-none{border-left:4px solid #cbd5e1}
.file-icon{width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;background:#fce4ec;color:#c62828}
.file-name{font-weight:600;font-size:.88rem}.file-meta{font-size:.72rem;color:#94a3b8}
.conf-badge{font-size:.65rem;padding:2px 8px;border-radius:10px;font-weight:700}
.conf-high{background:#dcfce7;color:#16a34a}.conf-med{background:#fef3c7;color:#92400e}.conf-none{background:#f1f5f9;color:#64748b}
.sel-sm{font-size:.82rem;padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0}
.result-row{padding:8px 14px;border-radius:8px;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.result-ok{background:#dcfce7}.result-err{background:#fee2e2}
.btn-upload{background:var(--pp);color:#fff;border:none;padding:.7rem 2rem;border-radius:8px;font-weight:700;font-size:1rem}
.btn-upload:hover{background:#16304d;color:#fff}.btn-upload:disabled{opacity:.4}
#fileList{max-height:55vh;overflow-y:auto}
</style>

<div class="container-fluid">
    <div class="pp-hdr">
        <a href="<?= SITE_URL ?>/admin/past-papers.php" class="text-white text-decoration-none" style="font-size:.8rem"><i class="bi bi-arrow-left me-1"></i>Past Papers Admin</a>
        <h4 class="mt-2"><i class="bi bi-lightning-charge-fill me-2"></i>Smart Upload — KCSE Past Papers</h4>
        <small>Drop files → auto-detect subject, year & paper type → upload</small>
    </div>

    <?php if ($results !== null): ?>
    <!-- Results -->
    <div class="row g-3 mb-4">
        <div class="col-md-6"><div class="text-center p-3 rounded" style="background:#dcfce7"><h2 style="font-weight:800;color:#16a34a;margin:0"><?= $successCount ?></h2><div>Papers Uploaded</div></div></div>
        <div class="col-md-6"><div class="text-center p-3 rounded" style="background:#fee2e2"><h2 style="font-weight:800;color:#dc2626;margin:0"><?= $errorCount ?></h2><div>Errors</div></div></div>
    </div>
    <div class="card p-3 mb-4">
        <?php foreach ($results as $r): ?>
        <div class="result-row <?= $r['status'] === 'success' ? 'result-ok' : 'result-err' ?>">
            <i class="bi bi-<?= $r['status'] === 'success' ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?>"></i>
            <strong><?= htmlspecialchars($r['name']) ?></strong>
            <span class="text-muted"><?= htmlspecialchars($r['msg']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/smart-upload-papers.php" class="btn-upload"><i class="bi bi-arrow-repeat me-1"></i>Upload More</a>
        <a href="<?= SITE_URL ?>/admin/past-papers.php" class="btn btn-outline-secondary"><i class="bi bi-list me-1"></i>View All Papers</a>
    </div>
    <?php else: ?>

    <!-- Upload Interface -->
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card p-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>How it works</h6>
                <div class="small text-muted">
                    <p class="mb-2"><strong>1.</strong> Drop PDF or HTML files on the right</p>
                    <p class="mb-2"><strong>2.</strong> System auto-detects from filename:</p>
                    <ul class="mb-2" style="padding-left:16px">
                        <li><strong>Subject</strong> — Chemistry, Biology, Math...</li>
                        <li><strong>Year</strong> — any 4-digit year (1995–<?= date('Y') ?>)</li>
                        <li><strong>Paper #</strong> — "Paper 1", "P2", "Paper 3"</li>
                        <li><strong>Type</strong> — "MS", "Marking Scheme" → M/S</li>
                    </ul>
                    <p class="mb-2"><strong>3.</strong> Review & adjust any wrong matches</p>
                    <p class="mb-0"><strong>4.</strong> Click Upload — all papers saved!</p>
                </div>
                <hr>
                <h6 class="fw-bold mb-2" style="font-size:.8rem">Naming tips for best results</h6>
                <div class="small text-muted">
                    <code style="font-size:.72rem;display:block;background:#f1f5f9;padding:6px 8px;border-radius:6px;margin-bottom:4px">Chemistry 2023 Paper 1.pdf</code>
                    <code style="font-size:.72rem;display:block;background:#f1f5f9;padding:6px 8px;border-radius:6px;margin-bottom:4px">Biology-2022-P2-MS.pdf</code>
                    <code style="font-size:.72rem;display:block;background:#f1f5f9;padding:6px 8px;border-radius:6px;margin-bottom:4px">Mathematics KCSE 2021 Marking Scheme.pdf</code>
                    <code style="font-size:.72rem;display:block;background:#f1f5f9;padding:6px 8px;border-radius:6px">Physics P1 2020.pdf</code>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <form method="POST" enctype="multipart/form-data" id="smartForm">
                <input type="hidden" name="action" value="smart_upload_papers">
                <input type="hidden" name="assignments" id="formAssignments" value="[]">

                <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                    <div class="icon"><i class="bi bi-cloud-arrow-up"></i></div>
                    <h5 class="mt-2">Drop past papers here or click to browse</h5>
                    <p class="text-muted mb-0">PDF or HTML files — multiple files OK</p>
                </div>
                <input type="file" name="files[]" id="fileInput" multiple class="d-none" accept=".pdf,.html,.htm">

                <div id="fileList" class="mt-3"></div>

                <div id="uploadBar" class="mt-3 d-flex justify-content-between align-items-center" style="display:none!important">
                    <div>
                        <span id="countBadge" class="badge bg-primary fs-6 me-2">0 files</span>
                        <span id="matchSummary" class="small text-muted"></span>
                    </div>
                    <button type="submit" class="btn-upload" id="uploadBtn" disabled>
                        <i class="bi bi-lightning-charge-fill me-1"></i>Upload All Papers
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const SUBJECTS = <?= json_encode($subjectNames) ?>;
// Build alias map for matching
const SUBJECT_ALIASES = {
    'Mathematics': ['math','maths','mathematics'],
    'English': ['english','eng'],
    'Kiswahili': ['kiswahili','kisw','swahili'],
    'Biology': ['biology','bio'],
    'Chemistry': ['chemistry','chem'],
    'Physics': ['physics','phy','phys'],
    'History': ['history','hist'],
    'Geography': ['geography','geo','geog'],
    'CRE': ['cre','christian','c.r.e'],
    'IRE': ['ire','islamic','i.r.e'],
    'Agriculture': ['agriculture','agri','agric'],
    'Business Studies': ['business','bst','bus'],
    'Computer Studies': ['computer','comp','ict','cs'],
    'Home Science': ['home science','homescience','hsc'],
    'Art & Design': ['art','art and design','a&d'],
    'Music': ['music'],
    'French': ['french','fre'],
    'German': ['german','ger']
};

let files = [];
let classifications = [];

// Drag & drop
const dz = document.getElementById('dropZone');
const fi = document.getElementById('fileInput');
['dragenter','dragover'].forEach(e => dz.addEventListener(e, ev => { ev.preventDefault(); dz.classList.add('dragover'); }));
['dragleave','drop'].forEach(e => dz.addEventListener(e, ev => { ev.preventDefault(); dz.classList.remove('dragover'); }));
dz.addEventListener('drop', e => addFiles(e.dataTransfer.files));
fi.addEventListener('change', () => addFiles(fi.files));

function addFiles(newFiles) {
    for (const f of newFiles) {
        if (!files.find(x => x.name === f.name && x.size === f.size)) files.push(f);
    }
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    fi.files = dt.files;
    classifyAll();
    render();
}

function removeFile(idx) {
    files.splice(idx, 1);
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    fi.files = dt.files;
    classifyAll();
    render();
}

function classify(filename) {
    const clean = filename.replace(/\.[^.]+$/, '').replace(/[_\-\.]/g, ' ').toLowerCase();
    let subject = '', year = 0, paperNum = 1, paperType = 'question';
    let confidence = 'none';

    // Detect year (4 digits between 1990-2030)
    const yearMatch = clean.match(/\b(19[9]\d|20[0-3]\d)\b/);
    if (yearMatch) year = parseInt(yearMatch[1]);

    // Detect subject
    let bestSubj = '', bestScore = 0;
    for (const [subj, aliases] of Object.entries(SUBJECT_ALIASES)) {
        for (const alias of aliases) {
            if (clean.includes(alias)) {
                const score = alias.length;
                if (score > bestScore) { bestScore = score; bestSubj = subj; }
            }
        }
    }
    subject = bestSubj;

    // Detect paper number
    const pMatch = clean.match(/paper\s*(\d)/i) || clean.match(/\bp(\d)\b/i);
    if (pMatch) paperNum = parseInt(pMatch[1]);

    // Detect marking scheme
    if (/marking|scheme|\bms\b|m\.s\.|m\/s/.test(clean)) {
        paperType = 'marking_scheme';
    }

    // Confidence
    if (subject && year) confidence = 'high';
    else if (subject || year) confidence = 'medium';

    return { subject, year, paperNum, paperType, confidence };
}

function classifyAll() {
    classifications = files.map(f => classify(f.name));
}

function render() {
    const list = document.getElementById('fileList');
    const bar = document.getElementById('uploadBar');
    const badge = document.getElementById('countBadge');
    const summary = document.getElementById('matchSummary');
    const btn = document.getElementById('uploadBtn');

    if (!files.length) { list.innerHTML = ''; bar.style.cssText = 'display:none!important'; return; }

    bar.style.cssText = '';
    badge.textContent = files.length + ' file' + (files.length > 1 ? 's' : '');
    btn.disabled = false;

    let hc = 0, mc = 0, nc = 0, html = '';
    files.forEach((f, i) => {
        const c = classifications[i];
        if (c.confidence === 'high') hc++; else if (c.confidence === 'medium') mc++; else nc++;
        const sz = (f.size / (1024*1024)).toFixed(1);
        const confLabel = c.confidence === 'high' ? 'Auto' : c.confidence === 'medium' ? 'Partial' : 'Manual';
        const confClass = c.confidence === 'high' ? 'conf-high' : c.confidence === 'medium' ? 'conf-med' : 'conf-none';

        html += `<div class="file-row match-${c.confidence}" style="animation-delay:${i*0.04}s">`;
        html += `<div class="file-icon">📄</div>`;
        html += `<div style="flex:1;min-width:0">`;
        html += `<div class="file-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(f.name)}</div>`;
        html += `<div class="file-meta">${sz} MB <span class="conf-badge ${confClass}">${confLabel}</span></div>`;
        html += `</div>`;
        // Subject
        html += `<select class="sel-sm" data-idx="${i}" data-field="subject" onchange="upd(${i},'subject',this.value)" style="width:130px">`;
        html += `<option value="">— Subject —</option>`;
        SUBJECTS.forEach(s => { html += `<option value="${s}" ${c.subject===s?'selected':''}>${s}</option>`; });
        html += `</select>`;
        // Year
        html += `<select class="sel-sm" data-idx="${i}" data-field="year" onchange="upd(${i},'year',this.value)" style="width:80px">`;
        html += `<option value="">Year</option>`;
        for (let y = new Date().getFullYear(); y >= 1995; y--) { html += `<option value="${y}" ${c.year===y?'selected':''}>${y}</option>`; }
        html += `</select>`;
        // Paper #
        html += `<select class="sel-sm" data-idx="${i}" data-field="paperNum" onchange="upd(${i},'paperNum',this.value)" style="width:65px">`;
        [1,2,3].forEach(n => { html += `<option value="${n}" ${c.paperNum===n?'selected':''}>P${n}</option>`; });
        html += `</select>`;
        // Type
        html += `<select class="sel-sm" data-idx="${i}" data-field="paperType" onchange="upd(${i},'paperType',this.value)" style="width:75px">`;
        html += `<option value="question" ${c.paperType==='question'?'selected':''}>Q/P</option>`;
        html += `<option value="marking_scheme" ${c.paperType==='marking_scheme'?'selected':''}>M/S</option>`;
        html += `<option value="both" ${c.paperType==='both'?'selected':''}>Both</option>`;
        html += `</select>`;
        html += `<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${i})" title="Remove"><i class="bi bi-x-lg"></i></button>`;
        html += `</div>`;
    });

    list.innerHTML = html;
    summary.textContent = `${hc} auto, ${mc} partial, ${nc} manual`;
    updateForm();
}

function upd(idx, field, val) {
    if (field === 'subject') classifications[idx].subject = val;
    else if (field === 'year') classifications[idx].year = parseInt(val) || 0;
    else if (field === 'paperNum') classifications[idx].paperNum = parseInt(val) || 1;
    else if (field === 'paperType') classifications[idx].paperType = val;
    // Update confidence
    const c = classifications[idx];
    c.confidence = (c.subject && c.year) ? 'high' : (c.subject || c.year) ? 'medium' : 'none';
    updateForm();
    // Update row style
    const rows = document.querySelectorAll('.file-row');
    if (rows[idx]) rows[idx].className = 'file-row match-' + c.confidence;
}

function updateForm() {
    const a = files.map((f, i) => ({
        filename: f.name,
        subject: classifications[i].subject,
        year: classifications[i].year,
        paper_number: classifications[i].paperNum,
        paper_type: classifications[i].paperType
    }));
    document.getElementById('formAssignments').value = JSON.stringify(a);
}

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

document.getElementById('smartForm').addEventListener('submit', function(e) {
    updateForm();
    // Check all have subject + year
    const missing = classifications.filter(c => !c.subject || !c.year);
    if (missing.length) {
        if (!confirm(missing.length + ' file(s) missing subject or year. They will fail. Continue?')) { e.preventDefault(); return; }
    }
    const btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
});
</script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
