<?php
/**
 * M.T.T.I LMS — Smart Bulk Upload
 * Auto-matches files to topics/strands using filename keywords
 * Supports: HTML interactives, PDFs, videos, docs
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$pdo  = Database::getInstance()->getConnection();
$role = $auth->getRole();
$userId = $auth->getUserId();

if (!in_array($role, ['admin','school_admin','teacher'])) {
    header('Location: ' . SITE_URL . '/dashboard.php'); exit;
}

// ── Get classes based on role ──────────────────────────
if (in_array($role, ['admin','school_admin'])) {
    $classes = $pdo->query("SELECT id,title,curriculum_type FROM classes WHERE status='active' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT id,title,curriculum_type FROM classes WHERE instructor_id=? AND status='active' ORDER BY title");
    $stmt->execute([$userId]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── AJAX: Get subjects for class ───────────────────────
if (isset($_GET['api']) && $_GET['api'] === 'subjects') {
    header('Content-Type: application/json');
    $cid = (int)($_GET['class_id'] ?? 0);
    $subjects = $pdo->prepare("SELECT id,title FROM subjects WHERE class_id=? AND level_type='subject' AND status='active' ORDER BY sort_order,title");
    $subjects->execute([$cid]);
    echo json_encode($subjects->fetchAll(PDO::FETCH_ASSOC)); exit;
}

// ── AJAX: Match files to topics ────────────────────────
if (isset($_GET['api']) && $_GET['api'] === 'match') {
    header('Content-Type: application/json');
    $cid  = (int)($_GET['class_id'] ?? 0);
    $sid  = (int)($_GET['subject_id'] ?? 0);
    $name = trim($_GET['filename'] ?? '');

    // Get nodes based on curriculum type
    $classRow = $pdo->prepare("SELECT curriculum_type FROM classes WHERE id=?");
    $classRow->execute([$cid]);
    $isCBC = ($classRow->fetchColumn() === 'cbc');
    if ($isCBC) {
        $tree = $pdo->prepare("SELECT s.id, s.title, s.level_type, s.parent_id, p.title as parent_title FROM subjects s LEFT JOIN subjects p ON s.parent_id=p.id WHERE s.class_id=? AND s.status='active' AND (s.parent_id=? OR s.id IN (SELECT id FROM subjects WHERE parent_id IN (SELECT id FROM subjects WHERE parent_id=? AND class_id=?))) ORDER BY s.level_type DESC, s.sort_order, s.title");
        $tree->execute([$cid,$sid,$sid,$cid]);
        $nodes = $tree->fetchAll(PDO::FETCH_ASSOC);
        $direct = $pdo->prepare("SELECT id,title,level_type,parent_id FROM subjects WHERE parent_id=? AND class_id=? AND status='active' ORDER BY sort_order,title");
        $direct->execute([$sid,$cid]);
        $allNodes = array_merge($direct->fetchAll(PDO::FETCH_ASSOC), $nodes);
    } else {
        $lq = $pdo->prepare("SELECT id, title, 'lesson' as level_type, NULL as parent_id, NULL as parent_title FROM lessons WHERE subject_id=? AND class_id=? AND status='published' ORDER BY sort_order, title");
        $lq->execute([$sid, $cid]);
        $allNodes = $lq->fetchAll(PDO::FETCH_ASSOC);
    }
    // Extract keywords from filename
    $clean = preg_replace('/\.(html?|mp4|pdf|docx?|pptx?|txt)$/i','',$name);
    $clean = preg_replace('/^\d+_\d+_/','',$clean); // remove timestamp prefix
    $clean = preg_replace('/^(f\d+|g\d+|grade\d+|form\d+)[-_][a-z]+[-_][\d.]+-/i','',$clean); // remove class-subj-num prefix
    $clean = preg_replace('/[-_full]+$/','',$clean);
    $clean = preg_replace('/[-_]/',' ',$clean);
    $keywords = array_filter(explode(' ', strtolower($clean)), fn($k) => strlen($k) > 2);

    // Score each node
    $best = null; $bestScore = 0;
    foreach ($allNodes as $node) {
        $nodeWords = strtolower($node['title']);
        $score = 0;
        foreach ($keywords as $kw) {
            if (str_contains($nodeWords, $kw)) $score += strlen($kw); // longer match = higher score
        }
        // Also check parent title
        if ($node['parent_title'] ?? null) {
            $parentWords = strtolower($node['parent_title']);
            foreach ($keywords as $kw) {
                if (str_contains($parentWords, $kw)) $score += 1;
            }
        }
        if ($score > $bestScore) { $bestScore = $score; $best = $node; }
    }

    echo json_encode([
        'match'    => $best,
        'score'    => $bestScore,
        'keywords' => array_values($keywords),
        'nodes'    => $allNodes,
        'confident'=> $bestScore >= 2,
    ]); exit;
}

// ── AJAX: Upload file ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api']) && $_POST['api'] === 'upload') {
    header('Content-Type: application/json');
    $classId   = (int)($_POST['class_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $topicId   = (int)($_POST['topic_id'] ?? 0) ?: null;
    $title     = trim($_POST['title'] ?? '');
    $ctype     = $_POST['content_type'] ?? 'interactive';
    $results   = [];

    if (!$classId || !$subjectId) { echo json_encode(['error'=>'Class and subject required']); exit; }

    $files = $_FILES['files'] ?? [];
    if (empty($files['name'][0])) { echo json_encode(['error'=>'No files']); exit; }

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $orig  = $files['name'][$i];
        $tmp   = $files['tmp_name'][$i];
        $size  = $files['size'][$i];
        $err   = $files['error'][$i];
        $ftitle= trim($_POST['title_'.$i] ?? '') ?: pathinfo($orig, PATHINFO_FILENAME);
        $ftype = $_POST['ctype_'.$i] ?? $ctype;
        $ftopic= (int)($_POST['topic_'.$i] ?? $topicId) ?: null;

        if ($err !== UPLOAD_ERR_OK) { $results[] = ['name'=>$orig,'ok'=>false,'msg'=>'Upload error '.$err]; continue; }

        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $typeMap = ['html'=>'interactive','htm'=>'interactive','mp4'=>'video','webm'=>'video','pdf'=>'notes','doc'=>'notes','docx'=>'notes','ppt'=>'notes','pptx'=>'notes'];
        if ($ftype === 'auto') $ftype = $typeMap[$ext] ?? 'notes';

        $destDir = match($ftype) {
            'interactive' => __DIR__.'/assets/uploads/html/',
            'video'       => __DIR__.'/assets/uploads/videos/',
            default       => __DIR__.'/assets/uploads/files/',
        };
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        $safe = preg_replace('/[^a-zA-Z0-9._\-]/','-',$orig);
        $safe = preg_replace('/-+/','-',$safe);
        if (file_exists($destDir.$safe)) $safe = pathinfo($safe,PATHINFO_FILENAME).'-'.time().'.'.$ext;

        if (!move_uploaded_file($tmp, $destDir.$safe)) { $results[] = ['name'=>$orig,'ok'=>false,'msg'=>'Save failed']; continue; }

        $relPath = match($ftype) {
            'interactive' => 'assets/uploads/html/'.$safe,
            'video'       => 'assets/uploads/videos/'.$safe,
            default       => 'assets/uploads/files/'.$safe,
        };

        // Use existing lesson or insert new
        try {
        $existing = $pdo->prepare("SELECT id FROM lessons WHERE subject_id=? AND class_id=? AND title=? LIMIT 1");
        $existing->execute([$subjectId,$classId,$ftitle]);
        $lid = (int)($existing->fetchColumn() ?: 0);
        if (!$lid) {
            $ins = $pdo->prepare("INSERT INTO lessons (class_id,subject_id,title,content_type,status,sort_order,created_at) VALUES (?,?,?,?,?,?,NOW())");
            $ins->execute([$classId,$subjectId,$ftitle,$ftype,'published',0]);
            $lid = (int)$pdo->lastInsertId();
        }
        } catch(Exception $e) { $results[] = ['name'=>$orig,'ok'=>false,'msg'=>'DB error: '.$e->getMessage()]; continue; }

        // Insert lesson file
        $ins2 = $pdo->prepare("INSERT INTO lesson_files (lesson_id,topic_id,original_name,filename,filepath,filetype,filesize,created_at) VALUES (?,?,?,?,?,?,?,NOW())");
        $ins2->execute([$lid,$ftopic,$orig,$safe,$relPath,$ext,$size]);

        $results[] = ['name'=>$orig,'ok'=>true,'msg'=>'Uploaded: '.$ftitle,'lesson_id'=>$lid,'topic_id'=>$ftopic];
    }
    echo json_encode(['success'=>true,'results'=>$results]); exit;
}

require_once __DIR__ . '/templates/header.php';
?>
<style>
.su-wrap{max-width:860px;margin:0 auto;padding:24px 16px 60px}
.su-hero{background:linear-gradient(135deg,#064a1f,#0a5e2a);border-radius:14px;padding:20px 24px;color:#fff;margin-bottom:20px;display:flex;align-items:center;gap:14px}
.su-hero-ico{font-size:2.2rem}
.su-hero h2{font-size:1.1rem;font-weight:800;margin:0 0 3px}
.su-hero p{font-size:.78rem;opacity:.75;margin:0}
.card{background:#fff;border:1px solid #d4e6d9;border-radius:12px;padding:18px;margin-bottom:16px;box-shadow:0 2px 8px rgba(10,94,42,.05)}
.card-title{font-size:.82rem;font-weight:800;color:#0a5e2a;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid #e8f5ed}
.fg{display:flex;flex-direction:column;gap:4px;margin-bottom:12px}
.fg label{font-size:.7rem;font-weight:700;color:#6b7a6e;text-transform:uppercase;letter-spacing:.4px}
.fg select,.fg input{padding:9px 12px;border:1.5px solid #d4e6d9;border-radius:8px;font-size:.85rem;font-family:inherit;outline:none;background:#fff;color:#1a2e1f;transition:border .2s}
.fg select:focus,.fg input:focus{border-color:#0a5e2a}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
/* Drop zone */
.dropzone{border:2.5px dashed #d4e6d9;border-radius:12px;padding:30px 20px;text-align:center;cursor:pointer;transition:all .2s;background:#f4f7f5}
.dropzone.drag{border-color:#0a5e2a;background:#e8f5ed}
.dropzone.has-files{border-color:#2e7d32;border-style:solid;background:#f1f8f1}
.dz-ico{font-size:2rem;margin-bottom:8px}
.dz-txt{font-size:.88rem;font-weight:700;color:#1a2e1f}
.dz-sub{font-size:.7rem;color:#6b7a6e;margin-top:4px}
/* File rows */
.file-rows{margin-top:14px}
.frow{background:#f8fdf8;border:1px solid #d4e6d9;border-radius:10px;padding:12px;margin-bottom:8px;animation:fi .2s ease}
@keyframes fi{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
.frow-top{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.frow-ico{font-size:1.2rem}
.frow-name{flex:1;font-size:.75rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.frow-size{font-size:.65rem;color:#6b7a6e;flex-shrink:0}
.frow-rm{background:none;border:none;color:#c62828;cursor:pointer;font-size:.9rem;padding:2px 5px}
/* Match badge */
.match-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:700;margin-bottom:8px}
.mb-auto{background:#e8f5e9;color:#2e7d32}
.mb-manual{background:#fff3e0;color:#e65100}
.mb-none{background:#ffebee;color:#c62828}
/* File fields */
.frow-fields{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.ff label{font-size:.65rem;font-weight:700;color:#6b7a6e;display:block;margin-bottom:3px;text-transform:uppercase}
.ff select,.ff input{width:100%;padding:7px 10px;border:1.5px solid #d4e6d9;border-radius:6px;font-size:.78rem;font-family:inherit;background:#fff;outline:none}
.ff select:focus,.ff input:focus{border-color:#0a5e2a}
/* Progress */
.prog-wrap{background:#e8f0eb;border-radius:20px;height:6px;margin:10px 0;display:none;overflow:hidden}
.prog-wrap.show{display:block}
.prog-bar{height:100%;background:#0a5e2a;border-radius:20px;transition:width .4s;width:0%}
/* Upload btn */
.btn-upload{width:100%;padding:14px;background:#0a5e2a;color:#fff;border:none;border-radius:10px;font-family:inherit;font-weight:800;font-size:.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s}
.btn-upload:hover{background:#064a1f;transform:translateY(-1px)}
.btn-upload:disabled{opacity:.5;cursor:not-allowed;transform:none}
/* Results */
.res-item{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;margin-bottom:5px;font-size:.78rem;font-weight:600}
.res-ok{background:#e8f5e9;color:#2e7d32}
.res-err{background:#ffebee;color:#c62828}
@media(max-width:560px){.grid2{grid-template-columns:1fr}.frow-fields{grid-template-columns:1fr}}
</style>

<div class="su-wrap">
  <div class="su-hero">
    <div class="su-hero-ico">📤</div>
    <div>
      <h2>Smart Bulk Upload</h2>
      <p>Drop multiple files — system auto-matches to topics & strands from filename</p>
    </div>
  </div>

  <!-- Step 1: Class & Subject -->
  <div class="card">
    <div class="card-title">📚 Step 1 — Select Class & Subject</div>
    <div class="grid2">
      <div class="fg">
        <label>Class</label>
        <select id="selClass" onchange="onClassChange()">
          <option value="">— Select class —</option>
          <?php foreach($classes as $c): ?>
          <option value="<?= $c['id'] ?>" data-type="<?= $c['curriculum_type'] ?>"><?= htmlspecialchars($c['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg">
        <label>Subject</label>
        <select id="selSubject" onchange="onSubjectChange()" disabled>
          <option value="">— Select class first —</option>
        </select>
      </div>
    </div>
    <div id="classInfo" style="font-size:.72rem;color:#6b7a6e;display:none">
      📋 <span id="classInfoTxt"></span>
    </div>
  </div>

  <!-- Step 2: Drop Files -->
  <div class="card" id="uploadCard" style="display:none">
    <div class="card-title">📁 Step 2 — Drop Files</div>
    <div class="dropzone" id="dropzone" onclick="document.getElementById('fileInput').click()">
      <div class="dz-ico">📂</div>
      <div class="dz-txt">Tap to select or drag & drop files</div>
      <div class="dz-sub">HTML interactives · MP4 videos · PDFs · Word/PPT</div>
      <div class="dz-sub" style="margin-top:6px;font-size:.65rem;color:#0a5e2a;font-weight:700">System will auto-match files to topics from filename</div>
    </div>
    <input type="file" id="fileInput" multiple accept=".html,.htm,.mp4,.pdf,.doc,.docx,.ppt,.pptx,.txt" style="display:none" onchange="onFilesSelected(this.files)">
    <div class="file-rows" id="fileRows"></div>
  </div>

  <!-- Step 3: Upload -->
  <div id="uploadAction" style="display:none">
    <div class="prog-wrap" id="progWrap"><div class="prog-bar" id="progBar"></div></div>
    <button class="btn-upload" id="btnUpload" onclick="doUpload()">
      <span>🚀</span><span id="btnTxt">Upload All Files</span>
    </button>
    <div id="results" style="margin-top:12px"></div>
  </div>
</div>

<script>
const BASE = '<?= SITE_URL ?>';
let FILES = [], NODES = [], CLASS_TYPE = '';

// ── Class change ───────────────────────────────────────
async function onClassChange(){
  const sel = document.getElementById('selClass');
  const cid = sel.value;
  CLASS_TYPE = sel.options[sel.selectedIndex]?.dataset?.type || '';
  const subjSel = document.getElementById('selSubject');
  subjSel.innerHTML = '<option value="">Loading...</option>';
  subjSel.disabled = true;
  FILES = []; renderFiles();
  document.getElementById('uploadCard').style.display = 'none';
  document.getElementById('uploadAction').style.display = 'none';

  if (!cid) { subjSel.innerHTML = '<option value="">— Select class first —</option>'; return; }

  const r = await fetch(`${BASE}/smart-upload.php?api=subjects&class_id=${cid}`);
  const subjects = await r.json();
  subjSel.innerHTML = '<option value="">— Select subject —</option>';
  subjects.forEach(s => subjSel.innerHTML += `<option value="${s.id}">${s.title}</option>`);
  subjSel.disabled = false;

  const isCBC = ['cbc','cbe'].includes(CLASS_TYPE);
  document.getElementById('classInfoTxt').textContent = isCBC
    ? 'CBC curriculum — files will be matched to Strands & Sub-Strands'
    : '8-4-4 curriculum — files will be matched to Topics';
  document.getElementById('classInfo').style.display = 'block';
}

// ── Subject change ─────────────────────────────────────
function onSubjectChange(){
  const sid = document.getElementById('selSubject').value;
  FILES = []; renderFiles();
  document.getElementById('uploadCard').style.display = sid ? 'block' : 'none';
  document.getElementById('uploadAction').style.display = 'none';
  NODES = [];
}

// ── File selection ─────────────────────────────────────
function onFilesSelected(flist){
  Array.from(flist).forEach(f => {
    if (!FILES.find(x => x.name === f.name && x.size === f.size)) FILES.push(f);
  });
  matchAndRender();
}

async function matchAndRender(){
  const cid = document.getElementById('selClass').value;
  const sid = document.getElementById('selSubject').value;
  if (!cid || !sid || !FILES.length) return;

  // Match all files
  for (const f of FILES) {
    if (f._matched !== undefined) continue; // already matched
    const r = await fetch(`${BASE}/smart-upload.php?api=match&class_id=${cid}&subject_id=${sid}&filename=${encodeURIComponent(f.name)}`);
    const d = await r.json();
    f._match = d.match;
    f._score = d.score;
    f._confident = d.confident;
    f._nodes = d.nodes;
    f._matched = true;
    if (!NODES.length && d.nodes) NODES = d.nodes;
  }
  renderFiles();
}

function renderFiles(){
  const rows = document.getElementById('fileRows');
  const ua = document.getElementById('uploadAction');
  const btn = document.getElementById('btnTxt');

  if (!FILES.length) { rows.innerHTML = ''; ua.style.display='none'; return; }

  ua.style.display = 'block';
  btn.textContent = `Upload ${FILES.length} File${FILES.length>1?'s':''}`;

  const ext2ico = e => ({'html':'📄','htm':'📄','mp4':'🎬','webm':'🎬','pdf':'📑','doc':'📝','docx':'📝','ppt':'📊','pptx':'📊'}[e]||'📁');
  const ext2type = e => ({'html':'interactive','htm':'interactive','mp4':'video','webm':'video'}[e]||'notes');

  rows.innerHTML = FILES.map((f,i) => {
    const ext = f.name.split('.').pop().toLowerCase();
    const ico = ext2ico(ext);
    const defType = ext2type(ext);
    const size = f.size > 1048576 ? (f.size/1048576).toFixed(1)+' MB' : (f.size/1024).toFixed(0)+' KB';
    const rawTitle = f.name.replace(/\.[^.]+$/,'').replace(/^\d+_\d+_/,'').replace(/[-_]/g,' ').replace(/\bfull\b/gi,'').trim();
    const title = rawTitle.replace(/\b(f\d+|g\d+)\b/gi,'').replace(/\b(chem|phys|bio|math|cs|bs|elec|pm)\b/gi,'').trim().replace(/\s+/g,' ');
    const titleCased = title.split(' ').map(w=>w.charAt(0).toUpperCase()+w.slice(1)).join(' ');

    // Match badge
    let badge = '';
    if (f._matched === undefined) {
      badge = '<span class="match-badge mb-none">⏳ Matching...</span>';
    } else if (f._confident && f._match) {
      badge = `<span class="match-badge mb-auto">✅ Auto: ${f._match.title}</span>`;
    } else if (f._match) {
      badge = `<span class="match-badge mb-manual">⚠ Suggest: ${f._match.title}</span>`;
    } else {
      badge = '<span class="match-badge mb-none">❓ No match — assign manually</span>';
    }

    // Topic/strand options
    const topicOpts = NODES.map(n =>
      `<option value="${n.id}" ${f._match?.id==n.id?'selected':''}>[${n.level_type}] ${n.title}${n.parent_title?' → '+n.parent_title:''}</option>`
    ).join('');

    return `
    <div class="frow" id="fr${i}">
      <div class="frow-top">
        <span class="frow-ico">${ico}</span>
        <span class="frow-name">${f.name}</span>
        <span class="frow-size">${size}</span>
        <button class="frow-rm" onclick="removeFile(${i})">✕</button>
      </div>
      ${badge}
      <div class="frow-fields">
        <div class="ff">
          <label>Lesson Title</label>
          <input type="text" id="t_${i}" value="${titleCased}">
        </div>
        <div class="ff">
          <label>Content Type</label>
          <select id="c_${i}">
            <option value="interactive" ${defType==='interactive'?'selected':''}>📄 Interactive</option>
            <option value="video" ${defType==='video'?'selected':''}>🎬 Video</option>
            <option value="notes" ${defType==='notes'?'selected':''}>📑 Notes/PDF</option>
            <option value="assignment">✅ Assignment</option>
          </select>
        </div>
        <div class="ff">
          <label>Topic / Strand</label>
          <select id="p_${i}" onchange="onTopicChange(${i})">
            <option value="">— General (no topic) —</option>
            ${topicOpts}
          </select>
        </div>
      </div>
    </div>`;
  }).join('');

  updateUploadBtn();
  // Update dropzone text
  const dz = document.getElementById('dropzone');
  dz.classList.add('has-files');
  dz.querySelector('.dz-txt').textContent = `${FILES.length} file(s) — tap to add more`;
}

function removeFile(i){ FILES.splice(i,1); renderFiles(); }

function onTopicChange(i){
  const sel = document.getElementById("p_"+i);
  if (!sel) return;
  const val = sel.value;
  const txt = sel.options[sel.selectedIndex]?.text || "";
  if (val) {
    FILES[i]._manually_confirmed = true;
    FILES[i]._confident = true;
    const badge = document.querySelector("#fr"+i+" .match-badge");
    if(badge){ badge.className="match-badge mb-auto"; badge.textContent="✅ Confirmed: "+txt.replace(/^\[.*?\]\s*/, ""); }
  } else {
    FILES[i]._manually_confirmed = false;
    if (FILES[i]._score < 3) FILES[i]._confident = false;
    const badge = document.querySelector("#fr"+i+" .match-badge");
    if(badge){ badge.className="match-badge mb-none"; badge.textContent="❓ No topic — will be skipped"; }
  }
  updateUploadBtn();
}

function updateUploadBtn(){
  const ready = FILES.filter(f=>f._confident||f._manually_confirmed).length;
  const total = FILES.length;
  const blocked = total - ready;
  document.getElementById("btnTxt").textContent = blocked > 0
    ? "Upload "+ready+" matched files ("+blocked+" skipped)"
    : "Upload All "+total+" File"+(total>1?"s":"");
  document.getElementById("btnUpload").disabled = ready === 0;
}

// Drag drop
const dz = document.getElementById('dropzone');
dz.addEventListener('dragover', e=>{e.preventDefault();dz.classList.add('drag');});
dz.addEventListener('dragleave',()=>dz.classList.remove('drag'));
dz.addEventListener('drop',e=>{e.preventDefault();dz.classList.remove('drag');onFilesSelected(e.dataTransfer.files);});

// ── Upload ─────────────────────────────────────────────
async function doUpload(){
  const cid = document.getElementById('selClass').value;
  const sid = document.getElementById('selSubject').value;
  if (!cid||!sid){alert('Select class and subject');return;}
  if (!FILES.length){alert('No files');return;}

  const btn = document.getElementById('btnUpload');
  btn.disabled=true;
  document.getElementById('progWrap').classList.add('show');
  document.getElementById('results').innerHTML='';

  // Only upload confident or manually confirmed matches
  const readyIdx = FILES.map((f,i)=>(f._confident||f._manually_confirmed)?i:-1).filter(i=>i>=0);
  const blockedCount = FILES.length - readyIdx.length;

  if (!readyIdx.length) {
    btn.disabled=false;
    document.getElementById('results').innerHTML='<div class="res-item res-err">❌ No files ready — assign topics to blocked files first</div>';
    document.getElementById('progWrap').classList.remove('show');
    return;
  }

  if (blockedCount > 0) {
    document.getElementById('results').innerHTML=`<div class="res-item" style="background:#fff3e0;color:#e65100;font-weight:700">⚠ ${blockedCount} file(s) skipped — no topic assigned</div>`;
  }

  const fd = new FormData();
  fd.append('api','upload');
  fd.append('class_id',cid);
  fd.append('subject_id',sid);

  readyIdx.forEach((origIdx, newIdx)=>{
    const f=FILES[origIdx];
    fd.append('files[]',f);
    fd.append('title_'+newIdx, document.getElementById('t_'+origIdx)?.value||f.name);
    fd.append('ctype_'+newIdx, document.getElementById('c_'+origIdx)?.value||'notes');
    fd.append('topic_'+newIdx, document.getElementById('p_'+origIdx)?.value||'');
  });

  const xhr = new XMLHttpRequest();
  xhr.open('POST',`${BASE}/smart-upload.php`);
  xhr.upload.onprogress = e=>{
    if(e.lengthComputable) document.getElementById('progBar').style.width=Math.round(e.loaded/e.total*100)+'%';
  };
  xhr.onload=()=>{
    btn.disabled=false;
    try{
      const r=JSON.parse(xhr.responseText);
      const res=document.getElementById('results');
      let html='',ok=0,err=0;
      (r.results||[]).forEach(x=>{
        if(x.ok){ok++;html+=`<div class="res-item res-ok">✅ ${x.name} — ${x.msg}</div>`;}
        else{err++;html+=`<div class="res-item res-err">❌ ${x.name} — ${x.msg}</div>`;}
      });
      res.innerHTML=`<div class="res-item ${ok?'res-ok':'res-err'}" style="font-weight:800">${ok?'🎉':'⚠'} ${ok} uploaded${err?`, ${err} failed`:''}</div>`+html;
      if(ok){FILES=[];renderFiles();document.getElementById('progBar').style.width='100%';setTimeout(()=>{document.getElementById('progWrap').classList.remove('show');document.getElementById('progBar').style.width='0%';},1500);}
    }catch(e){document.getElementById('results').innerHTML='<div class="res-item res-err">❌ Server error</div>';}
  };
  xhr.onerror=()=>{btn.disabled=false;document.getElementById('results').innerHTML='<div class="res-item res-err">❌ Network error</div>';};
  xhr.send(fd);
}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
