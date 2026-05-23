<?php
require_once __DIR__ . '/includes/init.php';
$auth = new Auth();
if ($auth->isLoggedIn()) {
    if ($auth->getRole() === "student") { header("Location: " . SITE_URL . "/portal/index.html"); exit; }
        if ($r === "teacher" || $r === "admin") { header("Location: " . SITE_URL . "/admin/classes.php"); exit; }
        header("Location: " . SITE_URL . "/dashboard.php"); exit;
}
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $result = $auth->login(trim($_POST["email"] ?? ""), $_POST["password"] ?? "");
    if ($result["success"]) {
        $r = $_SESSION["user_role"] ?? ""; if ($r === "student") { header("Location: " . SITE_URL . "/portal/index.html"); exit; }
        if ($r === "teacher" || $r === "admin") { header("Location: " . SITE_URL . "/admin/classes.php"); exit; }
        header("Location: " . SITE_URL . "/dashboard.php"); exit;
    }
    $error = $result["message"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - M.T.T.I Learning Management System</title>
    <link href="<?= SITE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700;800&display=swap');
        *{font-family:'DM Sans',sans-serif}
        body{background:linear-gradient(135deg,#0a1628 0%,#1a365d 50%,#1e4d2b 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .login-wrap{max-width:900px;width:100%;display:flex;border-radius:20px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,.4)}

        /* ── MTTI Branding Panel ── */
        .brand-panel{
            width:380px;
            background:linear-gradient(180deg,#0a3d1f 0%,#0a5e2a 50%,#0a3d1f 100%);
            color:#fff;
            padding:40px 28px;
            display:flex;flex-direction:column;
            align-items:center;justify-content:center;
            text-align:center;position:relative;overflow:hidden
        }
        /* subtle background pattern */
        .brand-panel::before{
            content:'';position:absolute;inset:0;
            background-image:radial-gradient(circle at 20% 20%, rgba(245,166,35,.07) 0%, transparent 50%),
                             radial-gradient(circle at 80% 80%, rgba(255,255,255,.04) 0%, transparent 50%);
        }
        .brand-logo{
            width:80px;height:80px;
            background:rgba(255,255,255,.1);
            border:2px solid rgba(245,166,35,.4);
            border-radius:20px;
            display:flex;align-items:center;justify-content:center;
            font-size:2.2rem;
            margin-bottom:20px;
            position:relative;z-index:1;
            box-shadow:0 8px 24px rgba(0,0,0,.2)
        }
        .brand-name{
            font-size:1.4rem;font-weight:800;
            color:#fff;letter-spacing:.5px;
            position:relative;z-index:1;margin-bottom:4px
        }
        .brand-name span{color:#f5a623}
        .brand-tagline{
            font-size:.8rem;color:rgba(255,255,255,.7);
            font-style:italic;margin-bottom:28px;
            position:relative;z-index:1
        }
        .brand-divider{
            width:50px;height:2px;
            background:linear-gradient(90deg,transparent,#f5a623,transparent);
            margin:0 auto 28px;position:relative;z-index:1
        }
        /* Course pills */
        .course-list{
            display:flex;flex-direction:column;gap:10px;
            width:100%;position:relative;z-index:1
        }
        .course-pill{
            background:rgba(255,255,255,.08);
            border:1px solid rgba(255,255,255,.12);
            border-radius:10px;
            padding:10px 14px;
            display:flex;align-items:center;gap:10px;
            font-size:.8rem;text-align:left;
            transition:background .2s
        }
        .course-pill:hover{background:rgba(255,255,255,.13)}
        .course-pill .cp-icon{
            width:30px;height:30px;border-radius:8px;
            background:rgba(245,166,35,.2);
            display:flex;align-items:center;justify-content:center;
            font-size:.95rem;flex-shrink:0
        }
        .course-pill .cp-text strong{
            display:block;color:#fff;font-size:.82rem;font-weight:700
        }
        .course-pill .cp-text span{
            color:rgba(255,255,255,.55);font-size:.72rem
        }
        .brand-cta{
            margin-top:24px;position:relative;z-index:1;
            font-size:.75rem;color:rgba(255,255,255,.5);
            line-height:1.6
        }
        .brand-cta a{
            color:#f5a623;font-weight:700;text-decoration:none
        }
        .brand-cta a:hover{text-decoration:underline}
        .brand-location{
            margin-top:16px;position:relative;z-index:1;
            font-size:.72rem;color:rgba(255,255,255,.4);
            display:flex;align-items:center;gap:5px;justify-content:center
        }

        /* ── Login Panel ── */
        .login-panel{flex:1;background:#fff;padding:40px 36px;display:flex;flex-direction:column;justify-content:center}
        .login-logo{font-size:2.5rem;color:#0a5e2a}
        .login-panel h4{font-weight:800;color:#1a365d;margin:8px 0 2px}
        .login-panel .subtitle{color:#94a3b8;font-size:.85rem;margin-bottom:24px}
        .form-control{border-radius:10px;padding:10px 14px;border:2px solid #e2e8f0;font-size:.92rem}
        .form-control:focus{border-color:#0a5e2a;box-shadow:0 0 0 3px rgba(10,94,42,.1)}
        .input-group-text{border-radius:10px 0 0 10px;border:2px solid #e2e8f0;border-right:none;background:#f8fafc;color:#64748b}
        .btn-login{background:linear-gradient(135deg,#0a5e2a,#0d7a37);border:none;border-radius:10px;padding:11px;font-weight:700;font-size:.95rem;transition:all .2s}
        .btn-login:hover{background:linear-gradient(135deg,#084d22,#0a5e2a);transform:translateY(-1px);box-shadow:0 4px 12px rgba(10,94,42,.3)}
        .login-footer{font-size:.78rem;color:#94a3b8;margin-top:20px;text-align:center}
        .login-footer a{color:#0a5e2a;font-weight:700}
        .powered-by{font-size:.68rem;color:#cbd5e1;text-align:center;margin-top:16px}

        @media(max-width:768px){
            .login-wrap{flex-direction:column;max-width:420px}
            .brand-panel{width:100%;padding:28px 20px}
            .course-list{flex-direction:row;flex-wrap:wrap}
            .course-pill{flex:1;min-width:120px}
            .login-panel{padding:28px 24px}
        }
    </style>
</head>
<body>
<div class="login-wrap">

    <!-- Brand Panel -->
    <div class="brand-panel">
        <div class="brand-logo">🎓</div>
        <div class="brand-name" id="bp-name">LMS</div>
        <div class="brand-tagline" id="bp-motto"></div>
        <div class="brand-divider"></div>
        <div style="flex:1"></div>
        <div class="brand-cta" id="bp-cta"></div>
        <div class="brand-location" id="bp-location"></div>
        <div class="powered-by">Powered by <a href="https://masomoteletraining.co.ke" target="_blank" style="color:#f5a623;text-decoration:none;font-weight:700">M.T.T.I</a> · masomoteletraining.co.ke</div>
    </div>
    <!-- Login Panel -->
    <div class="login-panel">
        <div class="text-center mb-3">
            <div class="login-logo"><i class="bi bi-mortarboard-fill"></i></div>
            <h4>M.T.T.I LMS</h4>
            <div class="subtitle">Masomotele Technical Training Institute</div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2" style="font-size:.85rem;border-radius:10px">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.85rem">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="email" class="form-control" placeholder="Enter username" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.85rem">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="passwordField" class="form-control" placeholder="Enter password" required>
                    <span class="input-group-text" style="border-radius:0 10px 10px 0;border:2px solid #e2e8f0;border-left:none;cursor:pointer" onclick="togglePass()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>
            <button type="submit" class="btn btn-login text-white w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="login-footer">
            Don't have an account? <a href="<?= SITE_URL ?>/register.php">Register here</a>
        </div>
        <div class="powered-by">
            Powered by <strong>M.T.T.I LMS</strong> &mdash; Masomotele Technical Training Institute
        </div>
    </div>
</div>

<script>
function togglePass() {
    const f = document.getElementById('passwordField');
    const i = document.getElementById('eyeIcon');
    if (f.type === 'password') {
        f.type = 'text';
        i.className = 'bi bi-eye-slash';
    } else {
        f.type = 'password';
        i.className = 'bi bi-eye';
    }
}
</script>

<script>
fetch('/mtti-lms/school-config.json').then(r=>r.json()).then(c=>{
    var n=document.getElementById('bp-name');
    var m=document.getElementById('bp-motto');
    var cta=document.getElementById('bp-cta');
    var loc=document.getElementById('bp-location');
    if(n) n.textContent=(c.school_short||c.school_name);
    if(m) m.textContent='"'+(c.school_motto||'')+'"';
    if(cta) cta.innerHTML='Not yet enrolled?<br><a href="https://'+c.school_website+'" target="_blank">Visit '+c.school_website+'</a> or call <a href="tel:'+c.school_phone+'">'+c.school_phone+'</a>';
    if(loc) loc.textContent='📍 '+(c.school_address||'');
    var h4=document.querySelector('.login-panel h4');
    var sub=document.querySelector('.login-panel .subtitle');
    if(h4) h4.textContent=(c.school_short||c.school_name)+' LMS';
    if(sub) sub.textContent=c.school_name||'';
    document.title=(c.school_short||c.school_name)+' LMS';
}).catch(()=>{});
</script>
</body>
</html>
