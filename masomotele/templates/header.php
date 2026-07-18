<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? SITE_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background:#f4f5f7; font-family:'Segoe UI',system-ui,sans-serif; }
        .navbar-brand img { height:36px; }
        .navbar { background:linear-gradient(135deg,#0a3d1f,#1a6b3c) !important; }
        .navbar .nav-link, .navbar-brand { color:#fff !important; }
        .navbar .nav-link:hover { color:#ffd700 !important; }
        .live-badge { animation:pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.6} }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-0 px-3">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= SITE_URL ?>/dashboard.php">
        <img src="<?= SITE_URL ?>/logo.jpeg" alt="MTTI" onerror="this.style.display='none'">
        <span class="fw-bold" style="font-size:.95rem">MTTI LMS</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/dashboard.php"><i class="bi bi-house me-1"></i>Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/classes.php"><i class="bi bi-book me-1"></i>Classes</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/live-sessions.php"><i class="bi bi-camera-video me-1"></i>Live Classes</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/forum.php"><i class="bi bi-chat-dots me-1"></i>Forum</a></li>
        </ul>
        <ul class="navbar-nav">
            <?php if (!empty($_SESSION['lms_user_name'])): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['lms_user_name']) ?>
                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem"><?= ucfirst($_SESSION['lms_user_role'] ?? '') ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= SITE_URL ?>/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <?php if (in_array($_SESSION['lms_user_role'] ?? '', ['admin','teacher'])): ?>
                    <li><a class="dropdown-item" href="<?= SITE_URL ?>/teacher-portal.php"><i class="bi bi-gear me-2"></i>Teacher Portal</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<div class="container-fluid py-3 px-3 px-md-4">
