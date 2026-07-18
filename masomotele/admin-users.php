<?php
/**
 * M.T.T.I LMS — Manage Users
 * Fixed: username taken false positive, class assignment, redirect after save
 */
require_once __DIR__ . '/includes/init.php';
$auth = new Auth(); $auth->requireLogin();
$pdo  = Database::getInstance()->getConnection();
$role = $auth->getRole();
$currentId = $auth->getUserId();
if (!in_array($role, ['admin','school_admin','superadmin'])) {
    header('Location: ' . SITE_URL . '/dashboard.php'); exit;
}

$msg = ''; $err = '';
if(isset($_GET['msg'])) $msg = $_GET['msg']==='created'?'✅ User created successfully!':($_GET['msg']==='updated'?'✅ User updated successfully!':'');

// ── ACTIONS ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // ── Save user ──────────────────────────────────────
    if ($postAction === 'save_user') {
        $edit_id   = (int)($_POST['edit_id'] ?? 0);
        $username  = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $userRole  = $_POST['role'] ?? 'student';
        $password  = trim($_POST['password'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $active    = isset($_POST['active']) ? 1 : 0;

        if (!$username || !$full_name || !$userRole) {
            $err = 'Username, Full Name and Role are required.';
        } elseif (!$edit_id && !$password) {
            $err = 'Password is required for new users.';
        } else {
            // Check unique username — exclude self when editing
            $chkSql = $edit_id
                ? "SELECT id FROM lms_users WHERE username=? AND id!=?"
                : "SELECT id FROM lms_users WHERE username=?";
            $chkParams = $edit_id ? [$username, $edit_id] : [$username];
            $chk = $pdo->prepare($chkSql);
            $chk->execute($chkParams);
            if ($chk->fetch()) {
                $err = "Username '$username' is already taken.";
            } else {
                // Generate placeholder email if blank (email is NOT NULL)
                if (!$email) $email = $username . '@mtti.local';
                // Generate placeholder phone if blank (phone is NOT NULL)
                if (!$phone) $phone = '0700' . str_pad($edit_id ?: rand(100000,999999), 6, '0', STR_PAD_LEFT);

                if ($edit_id) {
                    // Update
                    $sql = "UPDATE lms_users SET name=?,username=?,full_name=?,email=?,role=?,phone=?,is_active=?,status=?";
                    $params = [$full_name,$username,$full_name,$email,$userRole,$phone,$active,$active?'active':'inactive'];
                    if ($password) { $sql .= ",password=?"; $params[] = password_hash($password, PASSWORD_DEFAULT); }
                    $sql .= " WHERE id=?"; $params[] = $edit_id;
                    $pdo->prepare($sql)->execute($params);

                    // Update teacher class assignments
                    if ($userRole === 'teacher') {
                        $pdo->prepare("UPDATE lms_classes SET instructor_id=NULL WHERE instructor_id=?")->execute([$edit_id]);
                        foreach (($_POST['teacher_classes'] ?? []) as $cid) {
                            $pdo->prepare("UPDATE lms_classes SET instructor_id=? WHERE id=?")->execute([$edit_id,(int)$cid]);
                        }
                    }
                    header('Location: ' . SITE_URL . '/admin-users.php?msg=updated'); exit;

                } else {
                    // Insert
                    $pdo->prepare("INSERT INTO lms_users (name,username,full_name,email,role,phone,is_active,status,password,created_at)
                        VALUES (?,?,?,?,?,?,?,?,?,NOW())")
                        ->execute([$full_name,$username,$full_name,$email,$userRole,$phone,$active,$active?'active':'inactive',
                                   password_hash($password, PASSWORD_DEFAULT)]);
                    $newId = (int)$pdo->lastInsertId();

                    // Assign teacher to classes
                    if ($userRole === 'teacher') {
                        foreach (($_POST['teacher_classes'] ?? []) as $cid) {
                            $pdo->prepare("UPDATE lms_classes SET instructor_id=? WHERE id=?")->execute([$newId,(int)$cid]);
                        }
                    }
                    header('Location: ' . SITE_URL . '/admin-users.php?msg=created'); exit;
                }
            }
        }
    }

    // ── Reset password ─────────────────────────────────
    if ($postAction === 'reset_password') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $np  = trim($_POST['new_password'] ?? '');
        if ($uid && $np) {
            $pdo->prepare("UPDATE lms_users SET password=? WHERE id=?")->execute([password_hash($np, PASSWORD_DEFAULT), $uid]);
            header('Location: ' . SITE_URL . '/admin-users.php?msg=updated'); exit;
        }
    }

    // ── Toggle active ──────────────────────────────────
    if ($postAction === 'toggle_active') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $pdo->prepare("UPDATE lms_users SET is_active=1-is_active, status=IF(is_active=1,'inactive','active') WHERE id=?")->execute([$uid]);
        header('Location: ' . SITE_URL . '/admin-users.php'); exit;
    }

    // ── Delete user ────────────────────────────────────
    if ($postAction === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid && $uid !== $currentId) {
            $pdo->prepare("DELETE FROM lms_enrolments WHERE user_id=?")->execute([$uid]);
            $pdo->prepare("DELETE FROM lms_users WHERE id=?")->execute([$uid]);
        }
        header('Location: ' . SITE_URL . '/admin-users.php'); exit;
    }
}

// ── Fetch data ─────────────────────────────────────────
$search      = trim($_GET['search'] ?? '');
$filter_role = $_GET['role'] ?? '';
$edit_user   = null;
if (isset($_GET['edit'])) {
    $e = $pdo->prepare("SELECT * FROM lms_users WHERE id=?");
    $e->execute([(int)$_GET['edit']]);
    $edit_user = $e->fetch(PDO::FETCH_ASSOC);
}

$sql    = "SELECT id, COALESCE(full_name,name) as full_name, username, email, phone, role, is_active, status, last_login FROM lms_users WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (username LIKE ? OR full_name LIKE ? OR name LIKE ? OR email LIKE ?)"; $s="%$search%"; $params=[$s,$s,$s,$s]; }
if ($filter_role) { $sql .= " AND role=?"; $params[] = $filter_role; }
$sql .= " ORDER BY role, full_name";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$classes   = $pdo->query("SELECT id, title as name FROM lms_classes WHERE status='active' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
$stats     = $pdo->query("SELECT role, COUNT(*) as cnt, SUM(is_active) as active_cnt FROM lms_users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
$stats_map = array_column($stats, null, 'role');

// Teacher's currently assigned classes
$teacherClasses = [];
if ($edit_user && $edit_user['role']==='teacher') {
    $tc = $pdo->prepare("SELECT id FROM lms_classes WHERE instructor_id=?");
    $tc->execute([$edit_user['id']]);
    $teacherClasses = array_column($tc->fetchAll(PDO::FETCH_ASSOC), 'id');
}

require_once __DIR__ . '/templates/header.php';
?>
<style>
.wrap{max-width:1100px;margin:0 auto;padding:24px 16px 60px}
.page-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px}
.page-hdr h1{font-size:1.4rem;font-weight:800;color:#1a5632;margin:0}
.subtitle{color:#94a3b8;font-size:.85rem;margin-top:2px}
.stats-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.stat-box{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 18px;text-align:center;min-width:100px}
.stat-box .val{font-size:1.4rem;font-weight:900;color:#1a5632}
.stat-box .lbl{font-size:.72rem;color:#94a3b8;font-weight:600;margin-top:2px}
.panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:20px}
.panel h2{font-size:1rem;font-weight:800;color:#1a5632;margin-bottom:16px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg{display:flex;flex-direction:column;gap:4px}
.fg label{font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px}
.fg input,.fg select,.fg textarea{padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.85rem;font-family:inherit;outline:none;transition:border .2s;background:#fff}
.fg input:focus,.fg select:focus{border-color:#1a5632}
.fg select[multiple]{height:130px;padding:4px}
.fg select[multiple] option{padding:6px 8px;border-radius:4px;margin:1px 0}
.fg select[multiple] option:checked{background:#1a5632;color:#fff}
.fg-full{grid-column:1/-1}
.btn{padding:9px 18px;border:none;border-radius:8px;font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-pri{background:#1a5632;color:#fff}.btn-pri:hover{background:#2d7a4c}
.btn-sec{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}.btn-sec:hover{background:#e2e8f0}
.btn-danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.btn-sm{padding:5px 10px;font-size:.75rem}
.alert{padding:10px 14px;border-radius:8px;font-size:.85rem;font-weight:600;margin-bottom:16px}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #86efac}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.search-bar{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
.search-bar input{flex:1;min-width:200px;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.85rem;outline:none}
.search-bar input:focus{border-color:#1a5632}
.search-bar select{padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.85rem;outline:none;background:#fff}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.82rem}
thead th{background:#1a5632;color:#fff;padding:10px 12px;text-align:left;white-space:nowrap;font-weight:700}
tbody td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
tbody tr:hover td{background:#f8fafc}
.role-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:700}
.role-admin{background:#fef3c7;color:#92400e}
.role-teacher{background:#dbeafe;color:#1e40af}
.role-student{background:#d1fae5;color:#065f46}
.role-superadmin{background:#ede9fe;color:#5b21b6}
.role-school_admin{background:#fce7f3;color:#9d174d}
.status-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:5px}
.dot-green{background:#22c55e}.dot-red{background:#ef4444}
.actions-cell{display:flex;gap:5px;flex-wrap:wrap}
.hint{font-size:.7rem;color:#94a3b8;margin-top:3px}
@media(max-width:600px){.form-grid{grid-template-columns:1fr}}
</style>

<div class="wrap">
<div class="page-hdr">
    <div>
        <h1>👥 Manage Users</h1>
        <div class="subtitle">Add and manage teachers, students and administrators</div>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-box"><div class="val"><?= count($users) ?></div><div class="lbl">Showing</div></div>
    <?php foreach (['admin','teacher','student'] as $r): $s=$stats_map[$r]??null; ?>
    <div class="stat-box">
        <div class="val"><?= $s?$s['cnt']:0 ?></div>
        <div class="lbl"><?= ucfirst($r) ?>s<?php if($s&&$s['active_cnt']): ?> <span style="color:#22c55e;font-size:.65rem">(<?= $s['active_cnt'] ?> active)</span><?php endif; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add / Edit Form -->
<div class="panel">
    <h2><?= $edit_user ? '✏️ Edit: '.htmlspecialchars($edit_user['full_name']??$edit_user['name']??'User') : '➕ Add New User' ?></h2>
    <form method="POST" id="userForm">
        <input type="hidden" name="action" value="save_user">
        <input type="hidden" name="edit_id" value="<?= $edit_user?$edit_user['id']:0 ?>">
        <div class="form-grid">
            <div class="fg">
                <label>Full Name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($edit_user['full_name']??$edit_user['name']??'') ?>" placeholder="e.g. John Kamau">
            </div>
            <div class="fg">
                <label>Username * <span class="hint">(used to log in)</span></label>
                <input type="text" name="username" required value="<?= htmlspecialchars($edit_user['username']??'') ?>" placeholder="e.g. jkamau" autocomplete="off">
            </div>
            <div class="fg">
                <label>Role *</label>
                <select name="role" id="roleSelect" onchange="toggleClassFields()" required>
                    <option value="">— Select Role —</option>
                    <?php foreach (['student','teacher','admin'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($edit_user['role']??'')===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($edit_user['phone']??'') ?>" placeholder="07XX XXX XXX">
                <span class="hint">Leave blank to auto-generate</span>
            </div>
            <div class="fg">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email']??'') ?>" placeholder="Optional">
                <span class="hint">Leave blank to auto-generate</span>
            </div>
            <div class="fg">
                <label><?= $edit_user?'New Password (leave blank to keep)':'Password *' ?></label>
                <input type="password" name="password" placeholder="<?= $edit_user?'Leave blank to keep current':'Min 6 characters' ?>" autocomplete="new-password">
            </div>

            <!-- Student class assignment -->
            <div class="fg" id="student-class" style="display:none">
                <label>Enrol in Class</label>
                <select name="class_id">
                    <option value="">— Not enrolled —</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="hint">Student will see lessons from this class</span>
            </div>

            <!-- Teacher class assignment -->
            <div class="fg fg-full" id="teacher-class" style="display:none">
                <label>Assign to Classes</label>
                <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;padding:10px;max-height:180px;overflow-y:auto;display:grid;grid-template-columns:1fr 1fr;gap:6px">
                    <?php foreach ($classes as $c): ?>
                    <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;font-weight:500;color:#1a2e1f;cursor:pointer;padding:4px 6px;border-radius:6px;transition:background .15s" onmouseover="this.style.background='#e8f5ed'" onmouseout="this.style.background='transparent'">
                        <input type="checkbox" name="teacher_classes[]" value="<?= $c['id'] ?>" <?= in_array($c['id'],$teacherClasses)?'checked':'' ?> style="width:15px;height:15px;accent-color:#1a5632;flex-shrink:0">
                        <?= htmlspecialchars($c['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <span class="hint">Check all lms_classes this teacher will manage</span>
            </div>

            <div class="fg fg-full" style="display:flex;align-items:center;gap:8px;margin-top:4px">
                <input type="checkbox" name="active" id="activeChk" value="1" <?= (!$edit_user||$edit_user['is_active'])?'checked':'' ?> style="width:16px;height:16px;accent-color:#1a5632">
                <label for="activeChk" style="font-size:.85rem;font-weight:600;color:#1a2e1f;text-transform:none;letter-spacing:0">Active account</label>
            </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:8px">
            <button type="submit" class="btn btn-pri">💾 <?= $edit_user?'Update User':'Create User' ?></button>
            <?php if($edit_user): ?><a href="admin-users.php" class="btn btn-sec">✕ Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<!-- Search & Filter -->
<form method="GET" class="search-bar">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search name, username, email...">
    <select name="role" onchange="this.form.submit()">
        <option value="">All Roles</option>
        <?php foreach (['admin','teacher','student'] as $r): ?>
        <option value="<?= $r ?>" <?= $filter_role===$r?'selected':'' ?>><?= ucfirst($r) ?>s</option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-pri">Search</button>
    <?php if($search||$filter_role): ?><a href="admin-users.php" class="btn btn-sec">Clear</a><?php endif; ?>
</form>

<!-- Users Table -->
<div class="panel" style="padding:0;overflow:hidden">
<div class="table-wrap">
<table>
<thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Email/Phone</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
    <td><strong><?= htmlspecialchars($u['full_name']?:'-') ?></strong></td>
    <td><code style="font-size:.8rem;background:#f1f5f9;padding:2px 6px;border-radius:4px"><?= htmlspecialchars($u['username']??'-') ?></code></td>
    <td><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
    <td><?= htmlspecialchars($u['email']??'') ?><br><small style="color:#94a3b8"><?= htmlspecialchars($u['phone']??'') ?></small></td>
    <td><span class="status-dot <?= $u['is_active']?'dot-green':'dot-red' ?>"></span><?= $u['is_active']?'Active':'Inactive' ?></td>
    <td style="font-size:.75rem;color:#94a3b8"><?= $u['last_login']?date('d M y',strtotime($u['last_login'])):'-' ?></td>
    <td>
        <div class="actions-cell">
            <a href="?edit=<?= $u['id'] ?>" class="btn btn-sec btn-sm">✏️ Edit</a>
            <?php if($u['id']!==$currentId): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Toggle active status?')">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm" style="background:#fff3cd;color:#856404;border:1px solid #ffc107"><?= $u['is_active']?'Suspend':'Activate' ?></button>
            </form>
            <!-- Reset Password -->
            <button class="btn btn-sm btn-sec" onclick="showReset(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']?:$u['username']) ?>')">🔑 Reset PW</button>
            <form method="POST" style="display:inline" onsubmit="return confirm('DELETE this user permanently?')">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm btn-danger">🗑</button>
            </form>
            <?php else: ?>
            <span style="font-size:.75rem;color:#94a3b8">(you)</span>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
<?php if(empty($users)): ?>
<tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8">No users found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:24px;max-width:360px;width:90%">
        <h3 style="margin-bottom:14px;font-size:1rem" id="resetTitle">Reset Password</h3>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="resetUserId">
            <div class="fg" style="margin-bottom:12px">
                <label>New Password</label>
                <input type="password" name="new_password" required placeholder="Min 6 characters" style="padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.85rem;width:100%;outline:none">
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-pri">Save Password</button>
                <button type="button" class="btn btn-sec" onclick="closeReset()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('userForm').addEventListener('keydown', function(e){
    if(e.key==='Enter' && e.target.tagName !== 'TEXTAREA'){
        e.preventDefault();
    }
});

function toggleClassFields(){
    var r = document.getElementById('roleSelect').value;
    document.getElementById('student-class').style.display = r==='student'?'block':'none';
    document.getElementById('teacher-class').style.display = r==='teacher'?'block':'none';
}

// Run on page load
toggleClassFields();

// Also set correct value if editing
<?php if($edit_user): ?>
document.getElementById('roleSelect').value = '<?= $edit_user['role'] ?>';
toggleClassFields();
<?php endif; ?>

function showReset(id, name){
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetTitle').textContent = 'Reset Password: ' + name;
    var m = document.getElementById('resetModal');
    m.style.display = 'flex';
}
function closeReset(){ document.getElementById('resetModal').style.display='none'; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
