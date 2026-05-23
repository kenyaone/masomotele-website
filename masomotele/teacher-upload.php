<?php
/**
 * M.T.T.I LMS — Teacher Bulk Upload Portal
 * Teachers can only upload to their assigned class
 * Supports: HTML interactives, MP4 videos, PDFs, docs
 */
require_once __DIR__.'/config/app.php';
require_once __DIR__.'/includes/Database.php';
require_once __DIR__.'/includes/init.php';

// Auth check
session_start();
if(!isset($_SESSION['user_id'])) { header('Location: '.SITE_URL.'/login.php'); exit; }
$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'student';
if(!in_array($userRole,['admin','school_admin','teacher'])) {
    http_response_code(403); die('Access denied');
}

$db = Database::getInstance();

// Get assigned classes
if($userRole === 'admin' || $userRole === 'school_admin') {
    $classes = $db->fetchAll("SELECT id,title,curriculum_type FROM classes WHERE status='active' ORDER BY title");
} else {
    $classes = $db->fetchAll("SELECT id,title,curriculum_type FROM classes WHERE instructor_id=? AND status='active' ORDER BY title", [$userId]);
}

if(empty($classes)) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#c62828">⚠ No classes assigned to you yet. Contact admin.</div>');
}

// ── AJAX: get subjects for a class ────────────────────
if(isset($_GET['api']) && $_GET['api'] === 'subjects') {
    header('Content-Type: application/json');
    $cid = (int)($_GET['class_id'] ?? 0);
    // Verify teacher owns this class
    if($userRole === 'teacher') {
        $ok = $db->fetchColumn("SELECT COUNT(*) FROM classes WHERE id=? AND instructor_id=?", [$cid,$userId]);
        if(!$ok) { echo json_encode(['error'=>'Unauthorized']); exit; }
    }
    $subjects = $db->fetchAll("SELECT id,title,level_type,parent_id FROM subjects WHERE class_id=? AND status='active' ORDER BY sort_order,title", [$cid]);
    echo json_encode($subjects); exit;
}

// ── AJAX: upload files ────────────────────────────────
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['api']) && $_POST['api']==='upload') {
    header('Content-Type: application/json');
    $classId   = (int)($_POST['class_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $topicId   = (int)($_POST['topic_id'] ?? 0) ?: null;
    $results   = [];

    // Verify teacher owns class
    if($userRole === 'teacher') {
        $ok = $db->fetchColumn("SELECT COUNT(*) FROM classes WHERE id=? AND instructor_id=?", [$classId,$userId]);
        if(!$ok) { echo json_encode(['error'=>'Unauthorized']); exit; }
    }

    $files = $_FILES['files'] ?? [];
    if(empty($files['name'][0])) { echo json_encode(['error'=>'No files received']); exit; }

    $uploadCount = count($files['name']);
    for($i=0; $i<$uploadCount; $i++) {
        $origName = $files['name'][$i];
        $tmpPath  = $files['tmp_name'][$i];
        $size     = $files['size'][$i];
        $err      = $files['error'][$i];
        $contentType = $_POST['content_type_'.$i] ?? 'notes';
        $lessonTitle = $_POST['title_'.$i] ?? pathinfo($origName, PATHINFO_FILENAME);
        $lessonTitle = trim($lessonTitle) ?: pathinfo($origName, PATHINFO_FILENAME);

        if($err !== UPLOAD_ERR_OK) {
            $results[] = ['name'=>$origName,'ok'=>false,'msg'=>'Upload error '.$err]; continue;
        }

        // Detect file type
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $typeMap = [
            'html'=>'interactive','htm'=>'interactive',
            'mp4'=>'video','webm'=>'video',
            'pdf'=>'notes','doc'=>'notes','docx'=>'notes',
            'ppt'=>'notes','pptx'=>'notes','txt'=>'notes',
        ];
        $detectedType = $typeMap[$ext] ?? 'notes';
        // Use user-specified type if provided and valid
        $validTypes = ['interactive','video','notes','homework','assignment'];
        if(!in_array($contentType, $validTypes)) $contentType = $detectedType;

        // Destination path
        $destDir = match($contentType) {
            'interactive' => __DIR__.'/assets/uploads/html/',
            'video'       => __DIR__.'/assets/uploads/videos/',
            default       => __DIR__.'/assets/uploads/files/',
        };
        if(!is_dir($destDir)) mkdir($destDir, 0755, true);

        // Sanitize filename
        $safeName = preg_replace('/[^a-zA-Z0-9._\-]/', '-', $origName);
        $safeName = preg_replace('/-+/', '-', $safeName);
        if(file_exists($destDir.$safeName)) {
            $safeName = pathinfo($safeName,PATHINFO_FILENAME).'-'.time().'.'.$ext;
        }
        $destPath = $destDir.$safeName;

        if(!move_uploaded_file($tmpPath, $destPath)) {
            $results[] = ['name'=>$origName,'ok'=>false,'msg'=>'Failed to save file']; continue;
        }

        // Relative file path for DB
        $relPath = match($contentType) {
            'interactive' => 'assets/uploads/html/'.$safeName,
            'video'       => 'assets/uploads/videos/'.$safeName,
            default       => 'assets/uploads/files/'.$safeName,
        };

        // Insert lesson
        $lessonId = $db->insert('lessons', [
            'class_id'     => $classId,
            'subject_id'   => $subjectId,
            'title'        => $lessonTitle,
            'content_type' => $contentType,
            'status'       => 'published',
            'sort_order'   => 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        // Insert lesson file
        $db->insert('lesson_files', [
            'lesson_id'     => $lessonId,
            'topic_id'      => $topicId,
            'original_name' => $origName,
            'filename'      => $safeName,
            'filepath'      => $relPath,
            'filetype'      => $ext,
            'filesize'      => $size,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $results[] = ['name'=>$origName,'ok'=>true,'msg'=>'Uploaded as: '.$safeName,'lesson_id'=>$lessonId];
    }

    echo json_encode(['success'=>true,'results'=>$results]); exit;
}

// ── Read school config ────────────────────────────────
$schoolCfg = ['school_short'=>'LMS','school_name'=>'School LMS','powered_by'=>true];
$cfgFile = __DIR__.'/school-config.json';
if(file_exists($cfgFile)) $schoolCfg = array_merge($schoolCfg, json_decode(file_get_contents($cfgFile),true)??[]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Upload Content — <?= htmlspecialchars($schoolCfg['school_short']) ?> LMS</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap');
:root{
  --pri:#0a5e2a;--pri-d:#064a1f;--pri-l:#0d7a38;--pri-pale:#e8f5ed;
  --acc:#f5a623;--acc-d:#e0951a;
  --bg:#f4f7f5;--card:#fff;--border:#d4e6d9;--text:#1a2e1f;--gray:#6b7a6e;
  --ok:#2e7d32;--no:#c62828;--warn:#e65100;--blue:#1565c0;
  --radius:12px;--shadow:0 2px 12px rgba(10,94,42,.08);
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

/* Topbar */
.topbar{background:linear-gradient(135deg,var(--pri-d),var(--pri));padding:14px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 12px rgba(0,0,0,.2);position:sticky;top:0;z-index:100}
.tb-left{display:flex;flex-direction:column}
.tb-brand{color:#fff;font-weight:800;font-size:1rem;letter-spacing:.3px}
.tb-brand span{color:var(--acc)}
.tb-school{color:rgba(255,255,255,.65);font-size:.65rem;font-weight:500;margin-top:1px}
.tb-right{display:flex;align-items:center;gap:10px}
.tb-user{color:rgba(255,255,255,.8);font-size:.75rem}
.tb-logout{color:var(--acc);font-size:.72rem;text-decoration:none;font-weight:700}

/* Layout */
.wrap{max-width:760px;margin:0 auto;padding:20px 16px 60px}

/* Section header */
.sec-hdr{margin-bottom:16px}
.sec-title{font-size:1.15rem;font-weight:800;color:var(--pri)}
.sec-sub{font-size:.78rem;color:var(--gray);margin-top:3px}

/* Card */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px;margin-bottom:16px;box-shadow:var(--shadow)}
.card-title{font-size:.82rem;font-weight:800;color:var(--pri);margin-bottom:14px;display:flex;align-items:center;gap:6px}

/* Form fields */
.field{margin-bottom:14px}
.field label{display:block;font-size:.72rem;font-weight:700;color:var(--gray);margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px}
.field select,.field input[type=text]{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:8px;font-family:'DM Sans',sans-serif;font-size:.85rem;color:var(--text);outline:none;background:#fff;transition:border .2s}
.field select:focus,.field input[type=text]:focus{border-color:var(--pri)}

/* Drop zone */
.dropzone{border:2.5px dashed var(--border);border-radius:var(--radius);padding:32px 20px;text-align:center;cursor:pointer;transition:all .2s;background:var(--bg);position:relative}
.dropzone.drag{border-color:var(--pri);background:var(--pri-pale)}
.dropzone.has-files{border-color:var(--ok);border-style:solid}
.dz-ico{font-size:2.4rem;margin-bottom:8px}
.dz-txt{font-size:.88rem;font-weight:700;color:var(--text)}
.dz-sub{font-size:.7rem;color:var(--gray);margin-top:4px}
.dz-types{display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-top:10px}
.dz-chip{padding:3px 9px;border-radius:20px;font-size:.62rem;font-weight:700;background:var(--pri-pale);color:var(--pri)}

/* File list */
.file-list{margin-top:14px}
.file-row{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:10px 12px;margin-bottom:8px;animation:slideIn .2s ease}
@keyframes slideIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.fr-top{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.fr-ico{font-size:1.3rem;flex-shrink:0}
.fr-name{flex:1;font-size:.78rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fr-size{font-size:.65rem;color:var(--gray);flex-shrink:0}
.fr-remove{background:none;border:none;color:var(--no);cursor:pointer;font-size:1rem;flex-shrink:0;padding:2px 4px}
.fr-fields{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.fr-field label{font-size:.65rem;font-weight:700;color:var(--gray);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:.4px}
.fr-field select,.fr-field input{width:100%;padding:7px 10px;border:1.5px solid var(--border);border-radius:6px;font-family:'DM Sans',sans-serif;font-size:.78rem;color:var(--text);background:#fff;outline:none}
.fr-field select:focus,.fr-field input:focus{border-color:var(--pri)}

/* Progress */
.prog-wrap{background:#e8f0eb;border-radius:20px;height:6px;margin:10px 0;overflow:hidden;display:none}
.prog-wrap.show{display:block}
.prog-bar{height:100%;background:linear-gradient(90deg,var(--pri),var(--pri-l));border-radius:20px;transition:width .4s;width:0%}

/* Upload button */
.btn-upload{width:100%;padding:14px;background:linear-gradient(135deg,var(--pri),var(--pri-l));color:#fff;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-weight:800;font-size:.95rem;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-upload:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(10,94,42,.3)}
.btn-upload:disabled{opacity:.5;cursor:not-allowed;transform:none;box-shadow:none}

/* Results */
.result-item{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;margin-bottom:6px;font-size:.78rem;font-weight:600}
.result-ok{background:#e8f5e9;color:var(--ok)}
.result-err{background:#ffebee;color:var(--no)}

/* Badge */
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:.62rem;font-weight:700}
.badge-html{background:#e8f5e9;color:var(--ok)}
.badge-video{background:#e3f2fd;color:var(--blue)}
.badge-pdf{background:#fff3e0;color:var(--warn)}
.badge-other{background:#f3e5f5;color:#6a1b9a}

/* Powered by */
.powered{text-align:center;margin-top:30px;font-size:.65rem;color:var(--gray)}
.powered a{color:var(--pri);text-decoration:none;font-weight:700}

/* Responsive */
@media(max-width:500px){
  .fr-fields{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div class="topbar">
  <div class="tb-left">
    <div class="tb-brand"><?= htmlspecialchars($schoolCfg['school_short']) ?> <span>LMS</span></div>
    <div class="tb-school">Content Upload Portal</div>
  </div>
  <div class="tb-right">
    <span class="tb-user">👤 <?= htmlspecialchars($_SESSION['name'] ?? 'Teacher') ?></span>
    <a class="tb-logout" href="<?= SITE_URL ?>/login.php">Sign Out</a>
  </div>
</div>

<div class="wrap">
  <div class="sec-hdr">
    <div class="sec-title">📤 Upload Content</div>
    <div class="sec-sub">Select your class and subject, then drop multiple files at once</div>
  </div>

  <!-- Step 1: Class & Subject -->
  <div class="card">
    <div class="card-title">📚 Step 1 — Select Class & Subject</div>
    <div class="field">
      <label>Class</label>
      <select id="selClass" onchange="loadSubjects()">
        <option value="">-- Select your class --</option>
        <?php foreach($classes as $c): ?>
        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?> <small>(<?= strtoupper($c['curriculum_type']) ?>)</small></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field" id="subjectWrap" style="display:none">
      <label>Subject</label>
      <select id="selSubject" onchange="loadTopics()">
        <option value="">-- Select subject --</option>
      </select>
    </div>
    <div class="field" id="topicWrap" style="display:none">
      <label>Strand / Topic <span style="font-weight:400;text-transform:none">(optional)</span></label>
      <select id="selTopic">
        <option value="">-- General (no specific topic) --</option>
      </select>
    </div>
  </div>

  <!-- Step 2: Files -->
  <div class="card" id="uploadCard" style="display:none">
    <div class="card-title">📁 Step 2 — Select Files</div>
    <div class="dropzone" id="dropzone" onclick="document.getElementById('fileInput').click()">
      <div class="dz-ico">📂</div>
      <div class="dz-txt">Tap to select files or drag & drop</div>
      <div class="dz-sub">Select multiple files at once</div>
      <div class="dz-types">
        <span class="dz-chip">📄 HTML Interactives</span>
        <span class="dz-chip">🎬 MP4 Videos</span>
        <span class="dz-chip">📑 PDF Documents</span>
        <span class="dz-chip">📝 Word/PPT</span>
      </div>
    </div>
    <input type="file" id="fileInput" multiple accept=".html,.htm,.mp4,.pdf,.doc,.docx,.ppt,.pptx,.txt" style="display:none" onchange="filesSelected(this.files)">
    <div class="file-list" id="fileList"></div>
  </div>

  <!-- Step 3: Upload -->
  <div id="uploadAction" style="display:none">
    <div class="prog-wrap" id="progWrap"><div class="prog-bar" id="progBar"></div></div>
    <button class="btn-upload" id="btnUpload" onclick="doUpload()">
      <span>🚀</span><span id="btnTxt">Upload All Files</span>
    </button>
    <div id="results" style="margin-top:14px"></div>
  </div>

  <?php if($schoolCfg['powered_by']??true): ?>
  <div class="powered">Powered by <a href="https://masomoteletraining.co.ke" target="_blank">M.T.T.I</a> · masomoteletraining.co.ke</div>
  <?php endif; ?>
</div>

<script>
let selectedFiles = [];
const BASE = '<?= SITE_URL ?>';

// ── Load subjects ──────────────────────────────────────
async function loadSubjects() {
  const cid = document.getElementById('selClass').value;
  const sw = document.getElementById('subjectWrap');
  const tw = document.getElementById('topicWrap');
  const uc = document.getElementById('uploadCard');
  const ua = document.getElementById('uploadAction');

  sw.style.display = 'none'; tw.style.display = 'none';
  uc.style.display = 'none'; ua.style.display = 'none';
  selectedFiles = []; document.getElementById('fileList').innerHTML = '';

  if(!cid) return;

  const r = await fetch(`${BASE}/teacher-upload.php?api=subjects&class_id=${cid}`);
  const subjects = await r.json();

  // Build subject dropdown — only top-level subjects
  const topLevel = subjects.filter(s => s.level_type === 'subject' || !s.parent_id);
  const sel = document.getElementById('selSubject');
  sel.innerHTML = '<option value="">-- Select subject --</option>';
  topLevel.forEach(s => {
    sel.innerHTML += `<option value="${s.id}">${s.title}</option>`;
  });

  // Store all for topic filtering
  window._allSubjects = subjects;
  sw.style.display = 'block';
}

// ── Load topics ────────────────────────────────────────
function loadTopics() {
  const sid = parseInt(document.getElementById('selSubject').value);
  const tw = document.getElementById('topicWrap');
  const uc = document.getElementById('uploadCard');

  if(!sid) { tw.style.display='none'; uc.style.display='none'; return; }

  // Find children of selected subject
  const children = (window._allSubjects||[]).filter(s => s.parent_id == sid);
  const sel = document.getElementById('selTopic');
  sel.innerHTML = '<option value="">-- General (no specific topic) --</option>';
  children.forEach(s => {
    sel.innerHTML += `<option value="${s.id}">${s.title}</option>`;
  });
  tw.style.display = children.length ? 'block' : 'none';
  uc.style.display = 'block';
}

// ── File selection ─────────────────────────────────────
function filesSelected(files) {
  Array.from(files).forEach(f => {
    if(!selectedFiles.find(x => x.name === f.name && x.size === f.size)) {
      selectedFiles.push(f);
    }
  });
  renderFileList();
}

function renderFileList() {
  const list = document.getElementById('fileList');
  const ua = document.getElementById('uploadAction');
  const btn = document.getElementById('btnTxt');

  if(!selectedFiles.length) {
    list.innerHTML = ''; ua.style.display = 'none'; return;
  }

  ua.style.display = 'block';
  btn.textContent = `Upload ${selectedFiles.length} File${selectedFiles.length>1?'s':''}`;

  list.innerHTML = selectedFiles.map((f,i) => {
    const ext = f.name.split('.').pop().toLowerCase();
    const ico = ext==='mp4'||ext==='webm'?'🎬':ext==='html'||ext==='htm'?'📄':ext==='pdf'?'📑':'📝';
    const defType = ext==='mp4'||ext==='webm'?'video':ext==='html'||ext==='htm'?'interactive':'notes';
    const badge = ext==='html'||ext==='htm'?'badge-html':ext==='mp4'?'badge-video':ext==='pdf'?'badge-pdf':'badge-other';
    const size = f.size > 1048576 ? (f.size/1048576).toFixed(1)+' MB' : (f.size/1024).toFixed(0)+' KB';
    const title = f.name.replace(/\.[^.]+$/,'').replace(/[-_]/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
    return `
    <div class="file-row" id="fr${i}">
      <div class="fr-top">
        <span class="fr-ico">${ico}</span>
        <span class="fr-name">${f.name}</span>
        <span class="badge ${badge}">${ext.toUpperCase()}</span>
        <span class="fr-size">${size}</span>
        <button class="fr-remove" onclick="removeFile(${i})" title="Remove">✕</button>
      </div>
      <div class="fr-fields">
        <div class="fr-field">
          <label>Lesson Title</label>
          <input type="text" id="title_${i}" value="${title}">
        </div>
        <div class="fr-field">
          <label>Content Type</label>
          <select id="ctype_${i}">
            <option value="interactive" ${defType==='interactive'?'selected':''}>📄 Interactive</option>
            <option value="video" ${defType==='video'?'selected':''}>🎬 Video</option>
            <option value="notes" ${defType==='notes'?'selected':''}>📑 Notes/PDF</option>
            <option value="homework">📝 Homework</option>
            <option value="assignment">✅ Assignment</option>
          </select>
        </div>
      </div>
    </div>`;
  }).join('');

  // Drag and drop styling
  const dz = document.getElementById('dropzone');
  dz.classList.add('has-files');
  dz.querySelector('.dz-txt').textContent = `${selectedFiles.length} file(s) selected — tap to add more`;
}

function removeFile(i) {
  selectedFiles.splice(i, 1);
  renderFileList();
}

// Drag and drop
const dz = document.getElementById('dropzone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag'); });
dz.addEventListener('dragleave', () => dz.classList.remove('drag'));
dz.addEventListener('drop', e => {
  e.preventDefault(); dz.classList.remove('drag');
  filesSelected(e.dataTransfer.files);
});

// ── Upload ─────────────────────────────────────────────
async function doUpload() {
  const classId   = document.getElementById('selClass').value;
  const subjectId = document.getElementById('selSubject').value;
  const topicId   = document.getElementById('selTopic').value;

  if(!classId || !subjectId) { alert('Select a class and subject first'); return; }
  if(!selectedFiles.length) { alert('No files selected'); return; }

  const btn = document.getElementById('btnUpload');
  btn.disabled = true;
  document.getElementById('progWrap').classList.add('show');
  document.getElementById('results').innerHTML = '';

  const fd = new FormData();
  fd.append('api','upload');
  fd.append('class_id', classId);
  fd.append('subject_id', subjectId);
  fd.append('topic_id', topicId);

  selectedFiles.forEach((f,i) => {
    fd.append('files[]', f);
    fd.append('title_'+i, document.getElementById('title_'+i)?.value || f.name);
    fd.append('content_type_'+i, document.getElementById('ctype_'+i)?.value || 'notes');
  });

  const xhr = new XMLHttpRequest();
  xhr.open('POST', `${BASE}/teacher-upload.php`);

  xhr.upload.onprogress = e => {
    if(e.lengthComputable) {
      document.getElementById('progBar').style.width = Math.round(e.loaded/e.total*100)+'%';
    }
  };

  xhr.onload = () => {
    btn.disabled = false;
    try {
      const r = JSON.parse(xhr.responseText);
      const results = document.getElementById('results');
      if(r.error) { results.innerHTML=`<div class="result-item result-err">❌ ${r.error}</div>`; return; }

      let html = '', okCount = 0, errCount = 0;
      (r.results||[]).forEach(res => {
        if(res.ok) {
          okCount++;
          html += `<div class="result-item result-ok">✅ ${res.name} — uploaded successfully</div>`;
        } else {
          errCount++;
          html += `<div class="result-item result-err">❌ ${res.name} — ${res.msg}</div>`;
        }
      });

      results.innerHTML = `
        <div class="result-item ${okCount>0?'result-ok':'result-err'}" style="font-size:.85rem;font-weight:800">
          ${okCount>0?'🎉':'⚠'} ${okCount} uploaded${errCount>0?`, ${errCount} failed`:''}
        </div>${html}`;

      if(okCount > 0) {
        selectedFiles = [];
        renderFileList();
        document.getElementById('progBar').style.width = '100%';
        setTimeout(()=>{ document.getElementById('progWrap').classList.remove('show'); document.getElementById('progBar').style.width='0%'; }, 1500);
      }
    } catch(e) {
      document.getElementById('results').innerHTML = '<div class="result-item result-err">❌ Server error — check PHP logs</div>';
    }
  };

  xhr.onerror = () => {
    btn.disabled = false;
    document.getElementById('results').innerHTML = '<div class="result-item result-err">❌ Network error</div>';
  };

  xhr.send(fd);
}
</script>
</body>
</html>
