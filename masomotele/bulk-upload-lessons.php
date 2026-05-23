<?php
/**
 * M.T.T.I LMS - Bulk Upload Lessons with Content Files
 * Upload multiple lessons + attachments (PDFs, videos, HTML) to a class
 * Place in: /var/www/html/mtti-lms/bulk-upload-lessons.php
 */

session_start();
require_once __DIR__ . '/config/app.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

$results = null;
$errors = [];
$success_count = 0;
$error_count = 0;

// Get active classes
$classes = $pdo->query("SELECT id, title FROM classes WHERE status='active' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Upload dirs
$upload_dirs = [
    'pdf' => 'assets/uploads/files/',
    'doc' => 'assets/uploads/files/',
    'docx' => 'assets/uploads/files/',
    'ppt' => 'assets/uploads/files/',
    'pptx' => 'assets/uploads/files/',
    'xls' => 'assets/uploads/files/',
    'xlsx' => 'assets/uploads/files/',
    'txt' => 'assets/uploads/files/',
    'zip' => 'assets/uploads/files/',
    'mp4' => 'assets/uploads/videos/',
    'webm' => 'assets/uploads/videos/',
    'ogg' => 'assets/uploads/videos/',
    'avi' => 'assets/uploads/videos/',
    'mkv' => 'assets/uploads/videos/',
    'jpg' => 'assets/uploads/images/',
    'jpeg' => 'assets/uploads/images/',
    'png' => 'assets/uploads/images/',
    'gif' => 'assets/uploads/images/',
    'webp' => 'assets/uploads/images/',
    'html' => 'assets/uploads/html/',
    'htm' => 'assets/uploads/html/',
];

// Process upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = intval($_POST['class_id'] ?? 0);
    $mode = $_POST['upload_mode'] ?? 'files_as_lessons';
    
    if ($class_id <= 0) {
        $errors[] = "Please select a class.";
    }
    
    // Check class exists
    if (empty($errors)) {
        $cls = $pdo->prepare("SELECT id, title FROM classes WHERE id = ?");
        $cls->execute([$class_id]);
        $class_info = $cls->fetch(PDO::FETCH_ASSOC);
        if (!$class_info) {
            $errors[] = "Class not found.";
        }
    }
    
    if (empty($errors) && $mode === 'csv_with_files') {
        // MODE 1: CSV manifest + files
        $csv_file = $_FILES['csv_manifest'] ?? null;
        if (!$csv_file || $csv_file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Please upload a CSV manifest file.";
        } else {
            $handle = fopen($csv_file['tmp_name'], 'r');
            $header = fgetcsv($handle);
            $header = array_map(function($h) { return strtolower(trim(str_replace("\xEF\xBB\xBF", '', $h))); }, $header);
            
            $col_map = [];
            foreach ($header as $i => $col) {
                $c = preg_replace('/[^a-z_]/', '', $col);
                if (strpos($c, 'title') !== false || strpos($c, 'lesson') !== false) $col_map['title'] = $i;
                elseif (strpos($c, 'content') !== false || strpos($c, 'description') !== false || strpos($c, 'html') !== false) $col_map['content'] = $i;
                elseif (strpos($c, 'file') !== false || strpos($c, 'attachment') !== false) $col_map['file'] = $i;
                elseif (strpos($c, 'order') !== false || strpos($c, 'sort') !== false) $col_map['sort'] = $i;
            }
            
            if (!isset($col_map['title'])) {
                $errors[] = "CSV must have a 'title' column. Found: " . implode(', ', $header);
            }
        }
        
        if (empty($errors)) {
            $results = [];
            $row_num = 1;
            $uploaded_files = $_FILES['lesson_files'] ?? null;
            
            // Index uploaded files by original name
            $file_index = [];
            if ($uploaded_files && is_array($uploaded_files['name'])) {
                for ($i = 0; $i < count($uploaded_files['name']); $i++) {
                    if ($uploaded_files['error'][$i] === UPLOAD_ERR_OK) {
                        $file_index[strtolower($uploaded_files['name'][$i])] = [
                            'tmp_name' => $uploaded_files['tmp_name'][$i],
                            'name' => $uploaded_files['name'][$i],
                            'size' => $uploaded_files['size'][$i],
                        ];
                    }
                }
            }
            
            $lesson_stmt = $pdo->prepare("INSERT INTO lessons (class_id, title, content_html, sort_order, status, created_at) VALUES (:cid, :title, :content, :sort, 'published', NOW())");
            $file_stmt = $pdo->prepare("INSERT INTO lesson_files (lesson_id, original_name, filename, filepath, filetype, filesize, created_at) VALUES (:lid, :orig, :fname, :fpath, :ftype, :fsize, NOW())");
            
            while (($row = fgetcsv($handle)) !== false) {
                $row_num++;
                if (empty(array_filter($row))) continue;
                
                $title = trim($row[$col_map['title']] ?? '');
                $content = isset($col_map['content']) ? trim($row[$col_map['content']] ?? '') : '';
                $filename_ref = isset($col_map['file']) ? strtolower(trim($row[$col_map['file']] ?? '')) : '';
                $sort = isset($col_map['sort']) ? intval($row[$col_map['sort']] ?? $row_num) : $row_num;
                
                if (empty($title)) {
                    $results[] = ['row' => $row_num, 'title' => '(empty)', 'status' => 'error', 'msg' => 'Title is required'];
                    $error_count++;
                    continue;
                }
                
                try {
                    $lesson_stmt->execute([':cid' => $class_id, ':title' => $title, ':content' => $content, ':sort' => $sort]);
                    $lesson_id = $pdo->lastInsertId();
                    $file_msg = '';
                    
                    // Attach file if referenced
                    if (!empty($filename_ref) && isset($file_index[$filename_ref])) {
                        $f = $file_index[$filename_ref];
                        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                        $dest_dir = $upload_dirs[$ext] ?? 'assets/uploads/files/';
                        $safe_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $f['name']);
                        $dest_path = $dest_dir . $safe_name;
                        
                        @mkdir(dirname(__DIR__ . '/' . $dest_path), 0755, true);
                        if (move_uploaded_file($f['tmp_name'], __DIR__ . '/' . $dest_path)) {
                            $file_stmt->execute([
                                ':lid' => $lesson_id,
                                ':orig' => $f['name'],
                                ':fname' => $safe_name,
                                ':fpath' => $dest_path,
                                ':ftype' => $ext,
                                ':fsize' => $f['size']
                            ]);
                            $file_msg = " + file: {$f['name']}";
                        }
                    }
                    
                    $results[] = ['row' => $row_num, 'title' => $title, 'status' => 'success', 'msg' => "Lesson #{$lesson_id} created{$file_msg}"];
                    $success_count++;
                } catch (PDOException $e) {
                    $results[] = ['row' => $row_num, 'title' => $title, 'status' => 'error', 'msg' => $e->getMessage()];
                    $error_count++;
                }
            }
            fclose($handle);
        }
        
    } elseif (empty($errors) && $mode === 'files_as_lessons') {
        // MODE 2: Each file becomes a lesson
        $uploaded = $_FILES['lesson_files'] ?? null;
        if (!$uploaded || !is_array($uploaded['name']) || empty(array_filter($uploaded['name']))) {
            $errors[] = "Please select at least one file to upload.";
        }
        
        if (empty($errors)) {
            $results = [];
            $lesson_stmt = $pdo->prepare("INSERT INTO lessons (class_id, title, content_html, sort_order, status, created_at) VALUES (:cid, :title, :content, :sort, 'published', NOW())");
            $file_stmt = $pdo->prepare("INSERT INTO lesson_files (lesson_id, original_name, filename, filepath, filetype, filesize, created_at) VALUES (:lid, :orig, :fname, :fpath, :ftype, :fsize, NOW())");
            
            // Get current max sort order
            $max_sort = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM lessons WHERE class_id = ?");
            $max_sort->execute([$class_id]);
            $sort = intval($max_sort->fetchColumn());
            
            for ($i = 0; $i < count($uploaded['name']); $i++) {
                if ($uploaded['error'][$i] !== UPLOAD_ERR_OK || empty($uploaded['name'][$i])) continue;
                
                $orig_name = $uploaded['name'][$i];
                $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                $size = $uploaded['size'][$i];
                $tmp = $uploaded['tmp_name'][$i];
                
                // Check allowed extensions
                if (!isset($upload_dirs[$ext])) {
                    $results[] = ['row' => $i + 1, 'title' => $orig_name, 'status' => 'error', 'msg' => "File type .$ext not allowed"];
                    $error_count++;
                    continue;
                }
                
                // Lesson title from filename
                $title = pathinfo($orig_name, PATHINFO_FILENAME);
                $title = str_replace(['_', '-'], ' ', $title);
                $title = ucwords(preg_replace('/\s+/', ' ', $title));
                
                $sort++;
                $dest_dir = $upload_dirs[$ext];
                $safe_name = time() . '_' . $sort . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig_name);
                $dest_path = $dest_dir . $safe_name;
                
                // Generate content HTML based on file type
                $content_html = '';
                if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                    $content_html = '<div class="lesson-video"><video controls width="100%" preload="metadata"><source src="' . htmlspecialchars(SITE_URL . '/' . $dest_path) . '" type="video/' . $ext . '"></video></div>';
                } elseif ($ext === 'pdf') {
                    $content_html = '<div class="lesson-pdf"><iframe src="' . htmlspecialchars(SITE_URL . '/' . $dest_path) . '" width="100%" height="800px" style="border:none;"></iframe><p><a href="' . htmlspecialchars(SITE_URL . '/' . $dest_path) . '" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-download"></i> Download PDF</a></p></div>';
                } elseif (in_array($ext, ['html', 'htm'])) {
                    $content_html = '<div class="lesson-html"><iframe src="' . htmlspecialchars(SITE_URL . '/' . $dest_path) . '" width="100%" height="800px" style="border:none;"></iframe></div>';
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $content_html = '<div class="lesson-image"><img src="' . htmlspecialchars(SITE_URL . '/' . $dest_path) . '" class="img-fluid" alt="' . htmlspecialchars($title) . '"></div>';
                } else {
                    $content_html = '<p><a href="' . htmlspecialchars(SITE_URL . '/' . $dest_path) . '" target="_blank" class="btn btn-primary"><i class="bi bi-download"></i> Download: ' . htmlspecialchars($orig_name) . '</a></p>';
                }
                
                @mkdir(__DIR__ . '/' . $dest_dir, 0755, true);
                
                if (move_uploaded_file($tmp, __DIR__ . '/' . $dest_path)) {
                    try {
                        $lesson_stmt->execute([':cid' => $class_id, ':title' => $title, ':content' => $content_html, ':sort' => $sort]);
                        $lesson_id = $pdo->lastInsertId();
                        
                        $file_stmt->execute([
                            ':lid' => $lesson_id,
                            ':orig' => $orig_name,
                            ':fname' => $safe_name,
                            ':fpath' => $dest_path,
                            ':ftype' => $ext,
                            ':fsize' => $size
                        ]);
                        
                        $results[] = ['row' => $i + 1, 'title' => $title, 'status' => 'success', 'msg' => "Lesson #{$lesson_id} + {$ext} file attached"];
                        $success_count++;
                    } catch (PDOException $e) {
                        $results[] = ['row' => $i + 1, 'title' => $orig_name, 'status' => 'error', 'msg' => $e->getMessage()];
                        $error_count++;
                    }
                } else {
                    $results[] = ['row' => $i + 1, 'title' => $orig_name, 'status' => 'error', 'msg' => 'Failed to move uploaded file'];
                    $error_count++;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Lessons - M.T.T.I LMS</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <style>
        :root {
            --mtti-primary: #1a5632;
            --mtti-accent: #e8a423;
            --mtti-dark: #0d2b19;
            --mtti-light: #f0f7f2;
        }
        body { background: var(--mtti-light); font-family: 'Segoe UI', sans-serif; }
        .brand-header {
            background: linear-gradient(135deg, var(--mtti-dark), var(--mtti-primary));
            color: white; padding: 1.5rem 2rem; border-radius: 0 0 1rem 1rem;
        }
        .brand-header h1 { font-size: 1.5rem; margin: 0; }
        .brand-header small { color: var(--mtti-accent); }
        .upload-card {
            background: white; border-radius: 1rem; box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 2rem; margin-top: 1.5rem;
        }
        .drop-zone {
            border: 2.5px dashed #c3d9cb; border-radius: 1rem; padding: 3rem 2rem;
            text-align: center; cursor: pointer; transition: all 0.3s;
            background: var(--mtti-light);
        }
        .drop-zone:hover, .drop-zone.dragover { border-color: var(--mtti-primary); background: #e0f0e5; }
        .drop-zone i { font-size: 3rem; color: var(--mtti-primary); }
        .btn-mtti { background: var(--mtti-primary); color: white; border: none; padding: 0.7rem 2rem; border-radius: 0.5rem; font-weight: 600; }
        .btn-mtti:hover { background: var(--mtti-dark); color: white; }
        .mode-card {
            border: 2px solid #dee2e6; border-radius: 1rem; padding: 1.5rem;
            cursor: pointer; transition: all 0.3s; text-align: center;
        }
        .mode-card:hover, .mode-card.active { border-color: var(--mtti-primary); background: var(--mtti-light); }
        .mode-card.active { box-shadow: 0 0 0 3px rgba(26,86,50,0.2); }
        .mode-card i { font-size: 2.5rem; color: var(--mtti-primary); }
        .result-badge { font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 1rem; font-weight: 600; }
        .result-success { background: #d4edda; color: #155724; }
        .result-error { background: #f8d7da; color: #721c24; }
        .stats-bar { display: flex; gap: 1rem; flex-wrap: wrap; margin: 1rem 0; }
        .stat-pill { padding: 0.5rem 1.2rem; border-radius: 2rem; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; }
        .file-list { max-height: 200px; overflow-y: auto; }
        .file-list-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.8rem; background: #f8f9fa; border-radius: 0.5rem; margin-bottom: 0.3rem; font-size: 0.85rem; }
        .template-box { background: #fffbf0; border: 1px solid var(--mtti-accent); border-radius: 0.75rem; padding: 1rem 1.5rem; margin-top: 1rem; }
    </style>
</head>
<body>

<div class="brand-header">
    <div class="container">
        <a href="index.php" class="text-white text-decoration-none"><i class="bi bi-arrow-left me-2"></i>Back to LMS</a>
        <h1 class="mt-2"><i class="bi bi-journals me-2"></i>Bulk Upload Lessons</h1>
        <small>M.T.T.I — Start Learning, Start Earning</small>
    </div>
</div>

<div class="container" style="max-width: 850px;">

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger mt-3 rounded-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php foreach ($errors as $err): ?><div><?= htmlspecialchars($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="upload-card">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            
            <!-- Select Class -->
            <div class="mb-4">
                <label class="form-label fw-bold"><i class="bi bi-bookmark-fill me-1"></i>Select Class</label>
                <select name="class_id" class="form-select form-select-lg" required>
                    <option value="">— Choose a class —</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?= $cls['id'] ?>"><?= htmlspecialchars($cls['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Upload Mode -->
            <label class="form-label fw-bold mb-2"><i class="bi bi-gear-fill me-1"></i>Upload Mode</label>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="mode-card active" onclick="setMode('files_as_lessons')" id="mode1">
                        <i class="bi bi-files"></i>
                        <h6 class="mt-2 mb-1">Files → Lessons</h6>
                        <small class="text-muted">Each file becomes a lesson. Title from filename.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mode-card" onclick="setMode('csv_with_files')" id="mode2">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                        <h6 class="mt-2 mb-1">CSV Manifest + Files</h6>
                        <small class="text-muted">CSV defines lessons, reference attached files.</small>
                    </div>
                </div>
            </div>
            <input type="hidden" name="upload_mode" id="uploadMode" value="files_as_lessons">

            <!-- CSV Manifest (Mode 2) -->
            <div id="csvSection" style="display:none;">
                <div class="template-box mb-3">
                    <h6><i class="bi bi-info-circle me-1"></i>CSV Manifest Format</h6>
                    <code class="d-block p-2 bg-white rounded">title,content,file,sort_order</code>
                    <p class="small text-muted mt-1 mb-0">
                        <strong>title</strong>: Lesson name (required) &bull; 
                        <strong>content</strong>: HTML description &bull; 
                        <strong>file</strong>: Filename matching an uploaded file &bull; 
                        <strong>sort_order</strong>: Display order
                    </p>
                    <a href="#" onclick="downloadLessonTemplate(); return false;" class="btn btn-sm btn-outline-secondary mt-2">
                        <i class="bi bi-download me-1"></i>Download Template
                    </a>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">CSV Manifest File</label>
                    <input type="file" name="csv_manifest" class="form-control" accept=".csv,.txt">
                </div>
            </div>

            <!-- File Drop Zone -->
            <div class="drop-zone" id="dropZone" onclick="document.getElementById('lesson_files').click()">
                <i class="bi bi-cloud-arrow-up"></i>
                <h5 class="mt-2 mb-1">Drop lesson files here or click to browse</h5>
                <p class="text-muted mb-0">PDFs, Videos (MP4), HTML, Images, Documents — Multiple files OK</p>
            </div>
            <input type="file" name="lesson_files[]" id="lesson_files" multiple class="d-none" 
                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.mp4,.webm,.ogg,.avi,.mkv,.jpg,.jpeg,.png,.gif,.webp,.html,.htm">
            
            <div class="file-list mt-2" id="fileList"></div>

            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-mtti btn-lg">
                    <i class="bi bi-cloud-upload me-2"></i>Upload & Create Lessons
                </button>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if ($results !== null): ?>
    <div class="upload-card">
        <h5><i class="bi bi-clipboard-data me-2"></i>Import Results — <?= htmlspecialchars($class_info['title'] ?? '') ?></h5>
        <div class="stats-bar">
            <div class="stat-pill result-success"><i class="bi bi-check-circle-fill"></i> <?= $success_count ?> Lessons Created</div>
            <div class="stat-pill result-error"><i class="bi bi-x-circle-fill"></i> <?= $error_count ?> Errors</div>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr><th>#</th><th>Lesson Title</th><th>Status</th><th>Details</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr>
                        <td><?= $r['row'] ?></td>
                        <td><?= htmlspecialchars($r['title']) ?></td>
                        <td><span class="result-badge result-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                        <td class="small text-muted"><?= htmlspecialchars($r['msg']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function setMode(mode) {
    document.getElementById('uploadMode').value = mode;
    document.getElementById('mode1').classList.toggle('active', mode === 'files_as_lessons');
    document.getElementById('mode2').classList.toggle('active', mode === 'csv_with_files');
    document.getElementById('csvSection').style.display = mode === 'csv_with_files' ? 'block' : 'none';
}

// Drag & drop
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('lesson_files');
const fileList = document.getElementById('fileList');

['dragenter','dragover'].forEach(e => {
    dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.add('dragover'); });
});
['dragleave','drop'].forEach(e => {
    dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.remove('dragover'); });
});
dropZone.addEventListener('drop', e => {
    fileInput.files = e.dataTransfer.files;
    showFiles();
});
fileInput.addEventListener('change', showFiles);

function showFiles() {
    fileList.innerHTML = '';
    const icons = { pdf:'bi-file-pdf text-danger', mp4:'bi-camera-video text-primary', html:'bi-code-slash text-info', jpg:'bi-image text-success', jpeg:'bi-image text-success', png:'bi-image text-success', docx:'bi-file-word text-primary', pptx:'bi-file-ppt text-warning' };
    for (const f of fileInput.files) {
        const ext = f.name.split('.').pop().toLowerCase();
        const icon = icons[ext] || 'bi-file-earmark text-secondary';
        const size = (f.size / (1024 * 1024)).toFixed(1);
        fileList.innerHTML += `<div class="file-list-item"><i class="bi ${icon}"></i><span>${f.name}</span><span class="text-muted ms-auto">${size} MB</span></div>`;
    }
}

function downloadLessonTemplate() {
    const csv = 'title,content,file,sort_order\n"Introduction to Web Dev","<p>Welcome to the course!</p>",intro.pdf,1\n"HTML Basics","<p>Learn HTML structure</p>",html-basics.mp4,2\n"CSS Fundamentals","<p>Styling your pages</p>",,3';
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'mtti-lessons-template.csv';
    a.click();
}
</script>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
