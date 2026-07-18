<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'My Profile - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$db = Database::getInstance();
$user = $auth->getCurrentUser();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = ['name' => trim($_POST['name'] ?? $user['name'])];
    if (!empty($_FILES['photo']['tmp_name'])) {
        $up = new FileUploader();
        $r = $up->upload($_FILES['photo'], 'images');
        if ($r['success']) $data['photo'] = $r['filepath'];
    }
    if (!empty($_POST['new_password']) && strlen($_POST['new_password']) >= 6) {
        $data['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    }
    $db->update('lms_users', $data, 'id = ?', [$user['id']]);
    $_SESSION['user_name'] = $data['name'];
    $msg = 'Profile updated!';
    $user = $auth->getCurrentUser();
}
require_once __DIR__ . '/templates/header.php';
?>
<div class="container" style="max-width:600px">
    <h4 class="mb-3"><i class="bi bi-person me-2 text-primary"></i>My Profile</h4>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="text-center mb-3">
                    <?php if ($user['photo']): ?><img src="<?= SITE_URL ?>/<?= $user['photo'] ?>" class="rounded-circle" width="80" height="80"><?php else: ?><div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width:80px;height:80px;font-size:2rem"><i class="bi bi-person"></i></div><?php endif; ?>
                </div>
                <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
                <div class="mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" disabled></div>
                <div class="mb-3"><label class="form-label">Profile Photo</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
                <div class="mb-3"><label class="form-label">New Password (leave blank to keep)</label><input type="password" name="new_password" class="form-control" minlength="6"></div>
                <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Save</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
