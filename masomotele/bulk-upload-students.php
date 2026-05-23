<?php
/**
 * M.T.T.I LMS - Bulk Student Upload
 * Upload students via CSV/Excel file
 * Place in: /var/www/html/mtti-lms/bulk-upload-students.php
 */

session_start();
require_once __DIR__ . '/config/app.php';

// DB connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// Check admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    // Allow access for now if no session system yet
}

$results = null;
$errors = [];
$success_count = 0;
$skip_count = 0;
$error_count = 0;

// Process upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $default_password = trim($_POST['default_password'] ?? 'Mtti2025!');
    $default_role = $_POST['default_role'] ?? 'student';
    $auto_enroll_class = intval($_POST['auto_enroll_class'] ?? 0);
    
    // Validate file
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'txt'])) {
        $errors[] = "Only CSV/TXT files are accepted. Got: .$ext";
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = "File too large (max 5MB).";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error code: " . $file['error'];
    }
    
    if (empty($errors)) {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $errors[] = "Cannot read uploaded file.";
        } else {
            // Read header row
            $header = fgetcsv($handle);
            if (!$header) {
                $errors[] = "Empty file or invalid CSV format.";
            } else {
                // Normalize headers
                $header = array_map(function($h) {
                    return strtolower(trim(str_replace(["\xEF\xBB\xBF", '"'], '', $h)));
                }, $header);
                
                // Map columns
                $col_map = [];
                $required = ['name'];
                $known_cols = ['name', 'email', 'phone', 'password', 'role'];
                
                foreach ($header as $i => $col) {
                    // Flexible matching
                    $col_clean = preg_replace('/[^a-z]/', '', $col);
                    if (in_array($col, $known_cols)) {
                        $col_map[$col] = $i;
                    } elseif (strpos($col_clean, 'name') !== false && !isset($col_map['name'])) {
                        $col_map['name'] = $i;
                    } elseif (strpos($col_clean, 'email') !== false || strpos($col_clean, 'mail') !== false) {
                        $col_map['email'] = $i;
                    } elseif (strpos($col_clean, 'phone') !== false || strpos($col_clean, 'mobile') !== false || strpos($col_clean, 'tel') !== false) {
                        $col_map['phone'] = $i;
                    } elseif (strpos($col_clean, 'password') !== false || strpos($col_clean, 'pass') !== false) {
                        $col_map['password'] = $i;
                    } elseif (strpos($col_clean, 'role') !== false) {
                        $col_map['role'] = $i;
                    }
                }
                
                // Check required columns
                foreach ($required as $req) {
                    if (!isset($col_map[$req])) {
                        $errors[] = "Required column '$req' not found. Found columns: " . implode(', ', $header);
                    }
                }
            }
            
            if (empty($errors)) {
                $results = [];
                $row_num = 1;
                $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, status, created_at) VALUES (:name, :email, :phone, :password, :role, 'active', NOW())");
                $enroll_stmt = $pdo->prepare("INSERT IGNORE INTO enrolments (user_id, class_id, enrolled_at, pathway) VALUES (:uid, :cid, NOW(), :pathway)");
                
                while (($row = fgetcsv($handle)) !== false) {
                    $row_num++;
                    
                    // Skip empty rows
                    if (empty(array_filter($row))) continue;
                    
                    $name = trim($row[$col_map['name']] ?? '');
                    $email = isset($col_map['email']) ? trim($row[$col_map['email']] ?? '') : '';
                    $phone = isset($col_map['phone']) ? trim($row[$col_map['phone']] ?? '') : '';
                    $pwd = isset($col_map['password']) ? trim($row[$col_map['password']] ?? '') : '';
                    $role = isset($col_map['role']) ? strtolower(trim($row[$col_map['role']] ?? '')) : $default_role;
                    
                    // Validate
                    if (empty($name)) {
                        $results[] = ['row' => $row_num, 'name' => '(empty)', 'status' => 'error', 'msg' => 'Name is required'];
                        $error_count++;
                        continue;
                    }
                    
                    // Auto-generate email if missing
                    if (empty($email)) {
                        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '.', $name));
                        $slug = preg_replace('/\.+/', '.', trim($slug, '.'));
                        $email = $slug . '.' . rand(100, 999) . '@mtti.local';
                    }
                    
                    // Validate role
                    if (!in_array($role, ['student', 'teacher', 'admin'])) {
                        $role = $default_role;
                    }
                    
                    // Hash password
                    $hash = !empty($pwd) ? password_hash($pwd, PASSWORD_DEFAULT) : $password_hash;
                    
                    // Check duplicate email
                    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $check->execute([$email]);
                    if ($check->fetch()) {
                        $results[] = ['row' => $row_num, 'name' => $name, 'status' => 'skipped', 'msg' => "Email '$email' already exists"];
                        $skip_count++;
                        continue;
                    }
                    
                    try {
                        $stmt->execute([
                            ':name' => $name,
                            ':email' => $email,
                            ':phone' => $phone,
                            ':password' => $hash,
                            ':role' => $role
                        ]);
                        $new_user_id = $pdo->lastInsertId();
                        
                        // Auto-enroll if class selected
                        if ($auto_enroll_class > 0) {
                            $enroll_stmt->execute([':uid' => $new_user_id, ':cid' => $auto_enroll_class]);
                        }
                        
                        $results[] = ['row' => $row_num, 'name' => $name, 'status' => 'success', 'msg' => "Created (ID: $new_user_id)"];
                        $success_count++;
                    } catch (PDOException $e) {
                        $results[] = ['row' => $row_num, 'name' => $name, 'status' => 'error', 'msg' => $e->getMessage()];
                        $error_count++;
                    }
                }
            }
            fclose($handle);
        }
    }
}

// Get classes for auto-enroll dropdown
$classes = $pdo->query("SELECT id, title FROM classes WHERE status='active' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Students - M.T.T.I LMS</title>
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
        .drop-zone:hover, .drop-zone.dragover {
            border-color: var(--mtti-primary); background: #e0f0e5;
        }
        .drop-zone i { font-size: 3rem; color: var(--mtti-primary); }
        .btn-mtti { background: var(--mtti-primary); color: white; border: none; padding: 0.7rem 2rem; border-radius: 0.5rem; font-weight: 600; }
        .btn-mtti:hover { background: var(--mtti-dark); color: white; }
        .btn-mtti-outline { border: 2px solid var(--mtti-primary); color: var(--mtti-primary); background: white; padding: 0.7rem 2rem; border-radius: 0.5rem; font-weight: 600; }
        .btn-mtti-outline:hover { background: var(--mtti-primary); color: white; }
        .result-badge { font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 1rem; font-weight: 600; }
        .result-success { background: #d4edda; color: #155724; }
        .result-error { background: #f8d7da; color: #721c24; }
        .result-skipped { background: #fff3cd; color: #856404; }
        .stats-bar { display: flex; gap: 1rem; flex-wrap: wrap; margin: 1rem 0; }
        .stat-pill {
            padding: 0.5rem 1.2rem; border-radius: 2rem; font-weight: 700; font-size: 0.9rem;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .template-box {
            background: #fffbf0; border: 1px solid var(--mtti-accent); border-radius: 0.75rem;
            padding: 1rem 1.5rem; margin-top: 1rem;
        }
        .file-preview { display: none; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 0.75rem; margin-top: 1rem; }
        .file-preview.show { display: flex; }
    </style>
</head>
<body>

<div class="brand-header">
    <div class="container">
        <a href="index.php" class="text-white text-decoration-none"><i class="bi bi-arrow-left me-2"></i>Back to LMS</a>
        <h1 class="mt-2"><i class="bi bi-people-fill me-2"></i>Bulk Upload Students</h1>
        <small>M.T.T.I — Start Learning, Start Earning</small>
    </div>
</div>

<div class="container" style="max-width: 800px;">
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger mt-3 rounded-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php foreach ($errors as $err): ?>
            <div><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="upload-card">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            
            <div class="drop-zone" id="dropZone" onclick="document.getElementById('csv_file').click()">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <h5 class="mt-2 mb-1">Drop CSV file here or click to browse</h5>
                <p class="text-muted mb-0">Accepted: .csv, .txt — Max 5MB</p>
            </div>
            <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" class="d-none" required>
            
            <div class="file-preview" id="filePreview">
                <i class="bi bi-file-earmark-check text-success" style="font-size:2rem;"></i>
                <div>
                    <strong id="fileName"></strong><br>
                    <small class="text-muted" id="fileSize"></small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="clearFile()"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="template-box">
                <h6><i class="bi bi-download me-1"></i>CSV Template</h6>
                <p class="mb-1 small text-muted">Your CSV should have these columns (only <strong>name</strong> is required):</p>
                <code class="d-block p-2 bg-white rounded">name,email,phone,password,role</code>
                <p class="mb-0 mt-1 small text-muted">If email is missing, one is auto-generated. If password is missing, the default below is used.</p>
                <a href="#" onclick="downloadTemplate(); return false;" class="btn btn-sm btn-mtti-outline mt-2">
                    <i class="bi bi-download me-1"></i>Download Template CSV
                </a>
            </div>

            <div class="row mt-3 g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Default Password</label>
                    <input type="text" name="default_password" class="form-control" value="Mtti2025!" placeholder="For students without a password column">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Default Role</label>
                    <select name="default_role" class="form-select">
                        <option value="student" selected>Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Auto-Enroll in Class</label>
                    <select name="auto_enroll_class" class="form-select">
                        <option value="0">— None —</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= $cls['id'] ?>"><?= htmlspecialchars($cls['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-mtti btn-lg" id="submitBtn">
                    <i class="bi bi-cloud-upload me-2"></i>Upload & Import Students
                </button>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if ($results !== null): ?>
    <div class="upload-card">
        <h5><i class="bi bi-clipboard-data me-2"></i>Import Results</h5>
        
        <div class="stats-bar">
            <div class="stat-pill result-success"><i class="bi bi-check-circle-fill"></i> <?= $success_count ?> Added</div>
            <div class="stat-pill result-skipped"><i class="bi bi-skip-forward-fill"></i> <?= $skip_count ?> Skipped</div>
            <div class="stat-pill result-error"><i class="bi bi-x-circle-fill"></i> <?= $error_count ?> Errors</div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Row</th><th>Name</th><th>Status</th><th>Details</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr>
                        <td><?= $r['row'] ?></td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
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
// Drag & drop
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('csv_file');

['dragenter','dragover'].forEach(e => {
    dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.add('dragover'); });
});
['dragleave','drop'].forEach(e => {
    dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.remove('dragover'); });
});
dropZone.addEventListener('drop', e => {
    fileInput.files = e.dataTransfer.files;
    showPreview();
});
fileInput.addEventListener('change', showPreview);

function showPreview() {
    if (fileInput.files.length) {
        const f = fileInput.files[0];
        document.getElementById('fileName').textContent = f.name;
        document.getElementById('fileSize').textContent = (f.size / 1024).toFixed(1) + ' KB';
        document.getElementById('filePreview').classList.add('show');
        dropZone.style.display = 'none';
    }
}

function clearFile() {
    fileInput.value = '';
    document.getElementById('filePreview').classList.remove('show');
    dropZone.style.display = 'block';
}

function downloadTemplate() {
    const csv = "name,email,phone,password,role\nJohn Kipchoge,john@example.com,0712345678,Pass123!,student\nMary Akinyi,mary@example.com,0723456789,,student\nJames Mwangi,,0734567890,,student";
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'mtti-students-template.csv';
    a.click();
}
</script>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
