<?php
/**
 * Template Name: MTTI Admission Clean
 * Description: Clean standalone admission form page — no Astra chrome
 */
if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Admission — Masomotele Technical Training Institute</title>
    <meta name="description" content="Apply online for MTTI courses and get your admission letter instantly.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
    <style>
        :root{--green:#2E7D32;--green-dark:#1B5E20;--green-light:#4CAF50;--orange:#FF9800;--orange-dark:#F57C00;--white:#fff;--off:#f7f9f7;--dark:#1a1a2e;--gray:#666;--border:#e8ede8;}
        *{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{font-family:'Poppins',sans-serif;line-height:1.7;color:var(--dark);overflow-x:hidden;background:var(--off);}

        /* HEADER */
        header{position:fixed;top:0;left:0;right:0;z-index:1000;padding:12px 0;background:rgba(27,94,32,.97);backdrop-filter:blur(12px);box-shadow:0 4px 20px rgba(0,0,0,.2);}
        .hdr{max-width:1320px;margin:0 auto;padding:0 24px;display:flex;justify-content:space-between;align-items:center;}
        .logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
        .logo-text{color:#fff;font-size:1.25rem;font-weight:700;}
        .logo-text span{color:var(--orange);}
        nav ul{display:flex;list-style:none;gap:28px;align-items:center;}
        nav a{color:#fff;text-decoration:none;font-weight:500;font-size:.9rem;opacity:.9;transition:opacity .2s;}
        nav a:hover{opacity:1;}
        .nav-dropdown{position:relative;}
        .dropdown-menu{display:none;position:absolute;top:calc(100% + 8px);left:50%;transform:translateX(-50%);background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);min-width:210px;overflow:hidden;z-index:9999;list-style:none;}
        .nav-dropdown:hover .dropdown-menu,.nav-dropdown.mopen .dropdown-menu{display:block;}
        .dropdown-menu li a{display:block;padding:11px 18px;color:var(--dark)!important;font-size:.88rem;font-weight:500;opacity:1!important;border-bottom:1px solid #f0f0f0;transition:background .2s;text-decoration:none;}
        .dropdown-menu li:last-child a{border-bottom:none;}
        .dropdown-menu li a:hover{background:var(--off);color:var(--green)!important;}
        .nav-pill{background:var(--orange);padding:10px 22px!important;border-radius:50px;font-weight:600!important;opacity:1!important;box-shadow:0 3px 12px rgba(255,152,0,.4);}
        .nav-pill:hover{background:var(--orange-dark)!important;}
        .menu-btn{display:none;background:none;border:none;color:#fff;font-size:1.6rem;cursor:pointer;padding:4px 8px;}

        /* PAGE BODY */
        .page-body{padding:100px 24px 60px;max-width:900px;margin:0 auto;}

        /* FOOTER */
        footer{background:var(--dark);color:rgba(255,255,255,.7);padding:40px 24px 24px;}
        .footer-wrap{max-width:1320px;margin:0 auto;}
        .footer-cols{display:grid;grid-template-columns:2fr 1fr 1fr;gap:40px;margin-bottom:30px;}
        .footer-logo{display:flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:12px;}
        .footer-logo .logo-text{font-size:1rem;}
        .footer-brand p{font-size:.85rem;line-height:1.7;color:rgba(255,255,255,.6);}
        .footer-col h4{color:#fff;font-size:.9rem;font-weight:700;margin-bottom:12px;}
        .footer-col ul{list-style:none;}
        .footer-col ul li{margin-bottom:7px;}
        .footer-col ul li a{color:rgba(255,255,255,.6);text-decoration:none;font-size:.85rem;transition:color .2s;}
        .footer-col ul li a:hover{color:var(--orange);}
        .footer-bottom{border-top:1px solid rgba(255,255,255,.1);padding-top:20px;text-align:center;font-size:.8rem;color:rgba(255,255,255,.5);}
        .footer-bottom a{color:var(--orange);text-decoration:none;}

        /* MOBILE */
        @media(max-width:768px){
            nav{display:none;position:absolute;top:62px;left:0;right:0;background:rgba(27,94,32,.98);padding:10px 0;border-radius:0 0 14px 14px;box-shadow:0 8px 24px rgba(0,0,0,.2);}
            nav.open{display:block;}
            nav ul{flex-direction:column;gap:0;padding:0 20px;}
            nav ul li a{display:block;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.1);}
            .menu-btn{display:block;}
            .dropdown-menu{position:static;transform:none;box-shadow:none;border-radius:0;background:rgba(255,255,255,.08);display:none;}
            .nav-dropdown.mopen .dropdown-menu{display:block;}
            .dropdown-menu li a{color:#fff!important;border-color:rgba(255,255,255,.1);padding-left:32px;}
            .footer-cols{grid-template-columns:1fr;}
            .page-body{padding-top:90px;}
        }

        /* Remove any WP admin bar overlap */
        body.admin-bar header{top:32px;}
        @media screen and (max-width:782px){body.admin-bar header{top:46px;}}
    </style>
</head>
<body <?php body_class(); ?>>

<!-- HEADER -->
<header id="hdr">
    <div class="hdr">
        <a href="https://masomoteletraining.co.ke/" class="logo">
            <img src="https://masomoteletraining.co.ke/wp-content/uploads/2025/12/Logo.jpeg" alt="MTTI Logo" style="width:46px;height:46px;border-radius:10px;object-fit:cover;">
            <div class="logo-text">M.T.T.<span>I</span></div>
        </a>
        <nav id="nav">
            <ul>
                <li><a href="https://masomoteletraining.co.ke/#about">About</a></li>
                <li><a href="https://masomoteletraining.co.ke/#courses">Courses</a></li>
                <li><a href="https://masomoteletraining.co.ke/#gallery">Gallery</a></li>
                <li><a href="https://masomoteletraining.co.ke/#blog">Blog</a></li>
                <li class="nav-dropdown">
                    <a href="#" onclick="return false;">Portals ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="https://masomoteletraining.co.ke/student-portal/" target="_blank">🎓 Student Portal</a></li>
                        <li><a href="https://masomoteletraining.co.ke/lecturer-portal/" target="_blank">👨‍🏫 Lecturer Portal</a></li>
                        <li><a href="https://masomoteletraining.co.ke/masomotele/" target="_blank">📚 Free Learning</a></li>
                        <li><a href="https://masomoteletraining.co.ke/wp-content/plugins/mtti-fixed/verify-certificate-custom.php" target="_blank">🏅 Verify Certificate</a></li>
                    </ul>
                </li>
                <li><a href="https://masomoteletraining.co.ke/#contact">Contact</a></li>
                <li><a href="/online-admission/" class="nav-pill">Online Admission</a></li>
            </ul>
        </nav>
        <button class="menu-btn" onclick="toggleNav()" aria-label="Menu">☰</button>
    </div>
</header>

<!-- PAGE CONTENT -->
<div class="page-body">
    <?php
    while (have_posts()) {
        the_post();
        the_content();
    }
    ?>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-wrap">
        <div class="footer-cols">
            <div class="footer-brand">
                <a href="https://masomoteletraining.co.ke/" class="footer-logo">
                    <img src="https://masomoteletraining.co.ke/wp-content/uploads/2025/12/Logo.jpeg" alt="MTTI Logo" style="width:38px;height:38px;border-radius:8px;object-fit:cover;">
                    <div class="logo-text">M.T.T.<span>I</span></div>
                </a>
                <p>Masomotele Technical Training Institute — Empowering youth with practical skills for employment and self-employment in Kenya.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="https://masomoteletraining.co.ke/">Home</a></li>
                    <li><a href="https://masomoteletraining.co.ke/#courses">Courses</a></li>
                    <li><a href="https://masomoteletraining.co.ke/#contact">Contact Us</a></li>
                    <li><a href="https://masomoteletraining.co.ke/student-portal/" target="_blank">Student Portal</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <ul>
                    <li><a href="tel:+254712464936">+254 712 464 936</a></li>
                    <li><a href="mailto:info@masomoteletraining.co.ke">info@masomoteletraining.co.ke</a></li>
                    <li><a href="#">Sagaas Centre, 4th Floor, Eldoret</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; 2026 Masomotele Technical Training Institute. All rights reserved. | <a href="https://masomoteletraining.co.ke/wp-content/plugins/mtti-fixed/verify-certificate-custom.php" target="_blank">Verify Certificate</a></span>
        </div>
    </div>
</footer>

<script>
function toggleNav(){document.getElementById('nav').classList.toggle('open');}
document.querySelectorAll('.nav-dropdown>a').forEach(function(a){
    a.addEventListener('click',function(e){
        e.preventDefault();e.stopPropagation();
        var dd=this.parentElement,isOpen=dd.classList.contains('mopen');
        document.querySelectorAll('.nav-dropdown').forEach(function(d){d.classList.remove('mopen');});
        if(!isOpen) dd.classList.add('mopen');
    });
});
document.querySelectorAll('.dropdown-menu a').forEach(function(a){
    a.addEventListener('click',function(e){
        e.stopPropagation();
        document.querySelectorAll('.nav-dropdown').forEach(function(d){d.classList.remove('mopen');});
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
