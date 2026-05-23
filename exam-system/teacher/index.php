<?php
/**
 * MTTI Exam Admin — Results Dashboard
 * Simple password protection. Teachers view results here.
 */

session_start();

// ====== CONFIG ======
define('RESULTS_DIR', dirname(__DIR__) . '/results/');
define('USERS_FILE', __DIR__ . '/users.json');
define('DEFAULT_PASSWORD', '12345');

// Initialize users file with default admin if not exists
if (!file_exists(USERS_FILE)) {
    @file_put_contents(USERS_FILE, json_encode([
        'admin' => [
            'password' => password_hash(DEFAULT_PASSWORD, PASSWORD_DEFAULT),
            'name' => 'Administrator',
            'must_change' => true,
            'created' => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT));
}

function get_users() {
    if (!file_exists(USERS_FILE)) {
        // Fallback if file can't be created
        return [
            'admin' => [
                'password' => password_hash('12345', PASSWORD_DEFAULT),
                'name' => 'Administrator',
                'must_change' => false,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
    }
    return json_decode(file_get_contents(USERS_FILE), true) ?: [];
}
function save_users($users) {
    @file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

// ====== AUTH ======
if (isset($_POST['logout'])) { session_destroy(); header('Location: index.php'); exit; }

// Handle login
if (isset($_POST['username']) && isset($_POST['login_pass'])) {
    $users = get_users();
    $username = strtolower(trim($_POST['username']));
    if (isset($users[$username]) && password_verify($_POST['login_pass'], $users[$username]['password'])) {
        $_SESSION['mtti_exam_admin'] = true;
        $_SESSION['mtti_username'] = $username;
        $_SESSION['mtti_name'] = $users[$username]['name'];
        $_SESSION['mtti_must_change'] = $users[$username]['must_change'] ?? false;
    } else {
        $login_error = 'Invalid username or password';
    }
}

// Handle password change
if (isset($_POST['new_password']) && !empty($_SESSION['mtti_username'])) {
    $new_pass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($new_pass) < 4) {
        $pass_error = 'Password must be at least 4 characters';
    } elseif ($new_pass !== $confirm) {
        $pass_error = 'Passwords do not match';
    } else {
        $users = get_users();
        $users[$_SESSION['mtti_username']]['password'] = password_hash($new_pass, PASSWORD_DEFAULT);
        $users[$_SESSION['mtti_username']]['must_change'] = false;
        save_users($users);
        $_SESSION['mtti_must_change'] = false;
        $pass_success = 'Password changed successfully!';
    }
}

// Handle add new teacher (only admin can)
if (isset($_POST['add_teacher']) && $_SESSION['mtti_username'] === 'admin') {
    $new_user = strtolower(trim($_POST['new_username']));
    $new_name = trim($_POST['new_name']);
    if ($new_user && $new_name) {
        $users = get_users();
        if (isset($users[$new_user])) {
            $teacher_error = 'Username already exists';
        } else {
            $users[$new_user] = [
                'password' => password_hash(DEFAULT_PASSWORD, PASSWORD_DEFAULT),
                'name' => $new_name,
                'must_change' => true,
                'created' => date('Y-m-d H:i:s')
            ];
            save_users($users);
            $teacher_success = "Teacher '$new_name' added! Default password: " . DEFAULT_PASSWORD;
        }
    }
}

// Handle reset password (admin only)
if (isset($_GET['reset_user']) && $_SESSION['mtti_username'] === 'admin') {
    $users = get_users();
    $reset_user = $_GET['reset_user'];
    if (isset($users[$reset_user])) {
        $users[$reset_user]['password'] = password_hash(DEFAULT_PASSWORD, PASSWORD_DEFAULT);
        $users[$reset_user]['must_change'] = true;
        save_users($users);
        $teacher_success = "Password reset for '{$users[$reset_user]['name']}'. New password: " . DEFAULT_PASSWORD;
    }
}

// Handle delete teacher (admin only)
if (isset($_GET['delete_user']) && $_SESSION['mtti_username'] === 'admin' && $_GET['delete_user'] !== 'admin') {
    $users = get_users();
    unset($users[$_GET['delete_user']]);
    save_users($users);
    $teacher_success = 'Teacher removed.';
}

// ====== SHOW LOGIN PAGE ======
if (empty($_SESSION['mtti_exam_admin'])) {
    ?>
    <!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MTTI Exam Admin</title>
    <style>body{font-family:'Segoe UI',Arial;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}.login{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);width:360px;text-align:center}.login h2{color:#1B5E20;margin-bottom:6px}.login .sub{color:#666;font-size:.85rem;margin-bottom:20px}.login input{width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;margin-bottom:12px;font-size:1rem}.login input:focus{outline:none;border-color:#1B5E20}.login button{width:100%;padding:12px;background:#1B5E20;color:#fff;border:none;border-radius:8px;font-size:1rem;cursor:pointer;font-weight:600}.login button:hover{background:#2E7D32}.err{color:#C62828;font-size:.85rem;margin-bottom:12px;background:#FFEBEE;padding:8px;border-radius:6px}.hint{color:#999;font-size:.8rem;margin-top:16px}</style></head>
    <body><div class="login">
        <h2>🔒 Exam Admin</h2>
        <p class="sub">MTTI Examination System</p>
        <?php if (!empty($login_error)) echo '<p class="err">'.$login_error.'</p>'; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="login_pass" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p class="hint">Default login: admin / 12345</p>
    </div></body></html>
    <?php
    exit;
}

// ====== FORCE PASSWORD CHANGE ======
if (!empty($_SESSION['mtti_must_change'])) {
    ?>
    <!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Change Password</title>
    <style>body{font-family:'Segoe UI',Arial;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}.box{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);width:400px;text-align:center}.box h2{color:#1B5E20;margin-bottom:6px}.box .warn{background:#FFF3E0;color:#E65100;padding:12px;border-radius:8px;margin-bottom:20px;font-size:.9rem}.box input{width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;margin-bottom:12px;font-size:1rem}.box input:focus{outline:none;border-color:#1B5E20}.box button{width:100%;padding:12px;background:#1B5E20;color:#fff;border:none;border-radius:8px;font-size:1rem;cursor:pointer;font-weight:600}.box button:hover{background:#2E7D32}.err{color:#C62828;font-size:.85rem;margin-bottom:12px;background:#FFEBEE;padding:8px;border-radius:6px}</style></head>
    <body><div class="box">
        <h2>🔑 Change Password</h2>
        <div class="warn">⚠️ You must change your default password before continuing.</div>
        <?php if (!empty($pass_error)) echo '<p class="err">'.$pass_error.'</p>'; ?>
        <form method="POST">
            <input type="password" name="new_password" placeholder="New password (min 4 characters)" required minlength="4">
            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            <button type="submit">✅ Change Password</button>
        </form>
    </div></body></html>
    <?php
    exit;
}

// ====== LOAD RESULTS ======
$all_results = [];
$log_file = RESULTS_DIR . 'all-results.json';
if (file_exists($log_file)) {
    $all_results = json_decode(file_get_contents($log_file), true) ?: [];
}

// Sort newest first
usort($all_results, function($a, $b) { return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0); });

// Filters
$search = trim($_GET['search'] ?? '');
$exam_filter = trim($_GET['exam'] ?? '');
$pass_filter = $_GET['passed'] ?? '';

$filtered = $all_results;
if ($search) {
    $filtered = array_filter($filtered, function($r) use ($search) {
        return stripos($r['admission_number'], $search) !== false || stripos($r['student_name'], $search) !== false;
    });
}
if ($exam_filter) {
    $filtered = array_filter($filtered, function($r) use ($exam_filter) {
        return $r['exam_file'] === $exam_filter || $r['exam_name'] === $exam_filter;
    });
}
if ($pass_filter !== '') {
    $pf = $pass_filter === '1';
    $filtered = array_filter($filtered, function($r) use ($pf) { return $r['passed'] === $pf; });
}
$filtered = array_values($filtered);

// Get unique exam names for filter dropdown
$exam_names = array_unique(array_column($all_results, 'exam_file'));
sort($exam_names);

// Stats
$total = count($filtered);
$passed = count(array_filter($filtered, function($r) { return $r['passed']; }));
$failed = $total - $passed;
$avg = $total > 0 ? round(array_sum(array_column($filtered, 'percentage')) / $total, 1) : 0;

// ====== HANDLE CSV EXPORT ======
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="mtti-exam-results-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Admission #', 'Student Name', 'Exam', 'Score', 'Max Score', 'Percentage', 'Grade', 'Passed', 'Duration (min)', 'Date']);
    foreach ($filtered as $r) {
        fputcsv($out, [$r['admission_number'], $r['student_name'], $r['exam_name'], $r['score'], $r['max_score'], $r['percentage'].'%', $r['grade'], $r['passed']?'Yes':'No', $r['duration_minutes'], $r['submitted_at']]);
    }
    fclose($out);
    exit;
}

// ====== HANDLE DELETE ======
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $all_results = array_filter($all_results, function($r) use ($del_id) { return $r['id'] !== $del_id; });
    file_put_contents($log_file, json_encode(array_values($all_results), JSON_PRETTY_PRINT));
    header('Location: index.php?deleted=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTTI Exam Admin</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#f5f5f5; }
        .topbar { background:linear-gradient(135deg,#1B5E20,#2E7D32); color:#fff; padding:16px 24px; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:1.2rem; }
        .topbar form button { background:rgba(255,255,255,.2); color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; }
        .wrap { max-width:1200px; margin:24px auto; padding:0 20px; }
        
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:24px; }
        .stat { background:#fff; border-radius:10px; padding:16px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,.06); }
        .stat .num { font-size:1.8rem; font-weight:800; }
        .stat .lbl { font-size:.8rem; color:#666; margin-top:4px; }
        .stat.green .num { color:#2E7D32; }
        .stat.red .num { color:#C62828; }
        .stat.blue .num { color:#1565C0; }
        .stat.purple .num { color:#6A1B9A; }
        
        .filters { background:#fff; border-radius:10px; padding:16px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,.06); display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
        .filters label { font-size:.85rem; font-weight:600; display:block; margin-bottom:4px; }
        .filters input, .filters select { padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:.9rem; }
        .filters .btn { padding:8px 16px; background:#1B5E20; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:.9rem; }
        .filters .btn:hover { background:#2E7D32; }
        .filters .btn-outline { background:#fff; color:#333; border:1px solid #ddd; }
        
        table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06); }
        th { background:#1B5E20; color:#fff; padding:10px 12px; text-align:left; font-size:.85rem; }
        td { padding:10px 12px; border-bottom:1px solid #eee; font-size:.85rem; }
        tr:hover { background:#f9f9f9; }
        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:.75rem; font-weight:600; }
        .badge.pass { background:#E8F5E9; color:#2E7D32; }
        .badge.fail { background:#FFEBEE; color:#C62828; }
        .grade { font-size:1.1rem; font-weight:800; }
        a.del { color:#C62828; text-decoration:none; font-size:.8rem; }
        a.del:hover { text-decoration:underline; }
        
        .empty { text-align:center; padding:40px; color:#999; }
        @media(max-width:768px) { .filters{flex-direction:column;} td,th{padding:6px 8px;font-size:.8rem;} }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>📊 MTTI Exam Results</h1>
        <div style="display:flex;gap:10px;align-items:center;">
            <span style="opacity:.8;">👤 <?php echo htmlspecialchars($_SESSION['mtti_name']); ?></span>
            <a href="../" style="color:#fff;text-decoration:none;">← Exam Portal</a>
            <a href="?change_pass=1" style="color:#fff;text-decoration:none;">🔑 Password</a>
            <form method="POST" style="display:inline;"><button type="submit" name="logout" value="1">Logout</button></form>
        </div>
    </div>

    <div class="wrap">
        <?php if (isset($_GET['deleted'])): ?><div style="background:#E8F5E9;color:#2E7D32;padding:12px;border-radius:8px;margin-bottom:16px;">✅ Result deleted.</div><?php endif; ?>

        <!-- STATS -->
        <div class="stats">
            <div class="stat blue"><div class="num"><?php echo $total; ?></div><div class="lbl">Total Results</div></div>
            <div class="stat green"><div class="num"><?php echo $passed; ?></div><div class="lbl">Passed</div></div>
            <div class="stat red"><div class="num"><?php echo $failed; ?></div><div class="lbl">Failed</div></div>
            <div class="stat purple"><div class="num"><?php echo $avg; ?>%</div><div class="lbl">Average</div></div>
        </div>

        <!-- FILTERS -->
        <div class="filters">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;width:100%;">
                <div><label>Search</label><input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Admission # or name"></div>
                <div><label>Exam</label>
                    <select name="exam"><option value="">All exams</option>
                    <?php foreach ($exam_names as $en): ?><option value="<?php echo htmlspecialchars($en); ?>" <?php echo $exam_filter===$en?'selected':''; ?>><?php echo htmlspecialchars(ucwords(str_replace(['-','_'],' ',$en))); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div><label>Status</label>
                    <select name="passed"><option value="">All</option><option value="1" <?php echo $pass_filter==='1'?'selected':''; ?>>Passed</option><option value="0" <?php echo $pass_filter==='0'?'selected':''; ?>>Failed</option></select>
                </div>
                <div><button type="submit" class="btn">🔍 Filter</button></div>
                <div><a href="index.php" class="btn btn-outline" style="text-decoration:none;display:inline-block;">Clear</a></div>
                <div><a href="?<?php echo http_build_query(array_merge($_GET, ['export'=>'csv'])); ?>" class="btn btn-outline" style="text-decoration:none;display:inline-block;background:#1565C0;color:#fff;">📥 Export CSV</a></div>
            </form>
        </div>

        <!-- RESULTS TABLE -->
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Admission #</th>
                    <th>Exam</th>
                    <th>Score</th>
                    <th>%</th>
                    <th>Grade</th>
                    <th>Status</th>
                    <th>Duration</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filtered)): ?>
                    <tr><td colspan="11" class="empty">No results found.</td></tr>
                <?php else: ?>
                    <?php foreach ($filtered as $i => $r): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($r['student_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($r['admission_number']); ?></td>
                        <td><?php echo htmlspecialchars($r['exam_name']); ?></td>
                        <td><?php echo $r['score'] . '/' . $r['max_score']; ?></td>
                        <td><?php echo $r['percentage']; ?>%</td>
                        <td><span class="grade"><?php echo $r['grade']; ?></span></td>
                        <td><span class="badge <?php echo $r['passed'] ? 'pass' : 'fail'; ?>"><?php echo $r['passed'] ? '✅ Pass' : '❌ Fail'; ?></span></td>
                        <td><?php echo $r['duration_minutes'] ? $r['duration_minutes'] . 'm' : '—'; ?></td>
                        <td><?php echo date('d M Y H:i', strtotime($r['submitted_at'])); ?></td>
                        <td>
                            <a href="view-result.php?id=<?php echo urlencode($r['id']); ?>">👁️</a>
                            <a href="?delete_id=<?php echo urlencode($r['id']); ?>&<?php echo http_build_query($_GET); ?>" class="del" onclick="return confirm('Delete this result?');">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php // PASSWORD CHANGE (voluntary)
        if (isset($_GET['change_pass'])): ?>
        <div style="background:#fff;border-radius:10px;padding:24px;margin-top:24px;box-shadow:0 2px 8px rgba(0,0,0,.06);max-width:400px;">
            <h3 style="color:#1B5E20;margin-bottom:16px;">🔑 Change Password</h3>
            <?php if (!empty($pass_error)) echo '<div style="background:#FFEBEE;color:#C62828;padding:8px;border-radius:6px;margin-bottom:12px;">'.$pass_error.'</div>'; ?>
            <?php if (!empty($pass_success)) echo '<div style="background:#E8F5E9;color:#2E7D32;padding:8px;border-radius:6px;margin-bottom:12px;">'.$pass_success.'</div>'; ?>
            <form method="POST">
                <input type="password" name="new_password" placeholder="New password" required minlength="4" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:10px;">
                <input type="password" name="confirm_password" placeholder="Confirm password" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:10px;">
                <button type="submit" class="btn" style="width:100%;border:none;cursor:pointer;">Change Password</button>
            </form>
        </div>
        <?php endif; ?>

        <?php // TEACHER MANAGEMENT (admin only)
        if ($_SESSION['mtti_username'] === 'admin'): 
            $users = get_users();
        ?>
        <div style="background:#fff;border-radius:10px;padding:24px;margin-top:24px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
            <h3 style="color:#1B5E20;margin-bottom:16px;">👥 Manage Teachers</h3>
            <?php if (!empty($teacher_error)) echo '<div style="background:#FFEBEE;color:#C62828;padding:8px;border-radius:6px;margin-bottom:12px;">'.$teacher_error.'</div>'; ?>
            <?php if (!empty($teacher_success)) echo '<div style="background:#E8F5E9;color:#2E7D32;padding:8px;border-radius:6px;margin-bottom:12px;">'.$teacher_success.'</div>'; ?>
            
            <table>
                <thead><tr><th>Username</th><th>Name</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $uname => $udata): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($uname); ?></strong></td>
                        <td><?php echo htmlspecialchars($udata['name']); ?></td>
                        <td><?php echo ($udata['must_change'] ?? false) ? '<span class="badge fail">Default password</span>' : '<span class="badge pass">Active</span>'; ?></td>
                        <td><?php echo $udata['created'] ?? '—'; ?></td>
                        <td>
                            <?php if ($uname !== 'admin'): ?>
                                <a href="?reset_user=<?php echo urlencode($uname); ?>" onclick="return confirm('Reset password to 12345?');">🔄 Reset</a> |
                                <a href="?delete_user=<?php echo urlencode($uname); ?>" onclick="return confirm('Remove this teacher?');" class="del">🗑️ Remove</a>
                            <?php else: ?>
                                <span style="color:#999;">System admin</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h4 style="margin-top:20px;color:#333;">Add New Teacher</h4>
            <form method="POST" style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap;">
                <input type="text" name="new_username" placeholder="Username (e.g. jsmith)" required style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
                <input type="text" name="new_name" placeholder="Full name" required style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
                <button type="submit" name="add_teacher" value="1" class="btn" style="border:none;cursor:pointer;padding:8px 16px;">➕ Add Teacher</button>
            </form>
            <p style="color:#999;font-size:.8rem;margin-top:8px;">New teachers get default password: <strong>12345</strong> (forced to change on first login)</p>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
