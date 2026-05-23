<?php
/**
 * MTTI Auto-Blog System — Email Approval Version
 * Generates blog post via Claude → saves as WordPress draft
 * Sends email to Masa for approval
 * On approval → publishes live → Blog2Social posts to Facebook
 *
 * Deploy to: /home/uvyzhdzt/public_html/autoblog/autoblog.php
 * Cron: 0 4 * * * /usr/bin/php /home/uvyzhdzt/public_html/autoblog/autoblog.php
 */

error_reporting(0);
ini_set('display_errors', 0);

// ============================================================
// CONFIGURATION
// ============================================================
define('WP_URL',        'https://masomoteletraining.co.ke');
define('WP_USER',       'admin');
define('WP_APP_PASS',   'jLxI kXL0 UiJQ iOcI 3V3Y PREp');
define('APPROVE_SECRET','MTTI_BLOG_APPROVE_2026');
define('SELF_URL',      WP_URL . '/autoblog/autoblog.php');
define('NOTIFY_EMAIL',  'musilwabonface@gmail.com');
define('FROM_EMAIL',    'noreply@masomoteletraining.co.ke');
define('LOG_FILE',      __DIR__ . '/autoblog.log');
define('DB_HOST',       'localhost');
define('DB_NAME',       'uvyzhdzt_wp265');
define('DB_USER',       'uvyzhdzt_wp265');
define('DB_PASS',       'p!PS(1S17Y');
define('DB_PREFIX',     'wpcu_');

// ============================================================
// TOPIC ROTATION — CNA most, Computer Repair second, Mobile third
// ============================================================
$TOPIC_ROTATION = [
    'monday'    => ['course'=>'CNA',              'angle'=>'benefits and career opportunities for Kenyan students'],
    'tuesday'   => ['course'=>'Computer Repair',  'angle'=>'skills, employment and self employment in Eldoret'],
    'wednesday' => ['course'=>'CNA and Caregiver','angle'=>'entry requirements, fees and differences'],
    'thursday'  => ['course'=>'Mobile Phone Repair','angle'=>'business opportunity and demand in Kenya'],
    'friday'    => ['course'=>'CNA',              'angle'=>'student success stories and job placement'],
    'saturday'  => ['course'=>'Computer Repair',  'angle'=>'starting your own tech business in Eldoret'],
    'sunday'    => ['course'=>'All MTTI Courses', 'angle'=>'why MTTI graduates get employed fast'],
];

// ============================================================
// ROUTING — approval/rejection callbacks
// ============================================================
if (isset($_GET['action'], $_GET['secret']) && $_GET['secret'] === APPROVE_SECRET) {
    $post_id = intval($_GET['post_id'] ?? 0);
    if ($_GET['action'] === 'approve' && $post_id) handle_approve($post_id);
    elseif ($_GET['action'] === 'reject' && $post_id) handle_reject($post_id);
    exit;
}

// ============================================================
// CRON / MANUAL RUN PROTECTION
// ============================================================
$is_cron   = (php_sapi_name() === 'cli');
$is_manual = isset($_GET['run']) && $_GET['run'] === APPROVE_SECRET;
if (!$is_cron && !$is_manual) { http_response_code(403); die('Access denied.'); }

// Respond to browser immediately, continue in background
if (!$is_cron) {
    ob_start();
    echo "<h2 style='font-family:sans-serif;padding:2rem;color:green'>⏳ AutoBlog running...<br>Check your email <b>musilwabonface@gmail.com</b> in about 60 seconds.<br>Also check WordPress → Posts → Drafts.</h2>";
    $size = ob_get_length();
    header("Content-Length: $size");
    header("Connection: close");
    ob_end_flush();
    flush();
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    ignore_user_abort(true);
    set_time_limit(120);
}

// ============================================================
// MAIN
// ============================================================
log_it("=== MTTI AutoBlog Started ===");

$day   = strtolower(date('l'));
$topic = $TOPIC_ROTATION[$day];
log_it("Day: $day | Course: {$topic['course']}");

// 1. Generate blog via Claude
$blog = generate_blog($topic);
if (!$blog) { log_it("ERROR: Blog generation failed"); exit; }

// 2. Save as WordPress draft
$post_id = save_wordpress_draft($blog);
if (!$post_id) { log_it("ERROR: Could not save draft"); exit; }
log_it("Draft saved. Post ID: $post_id");

// 3. Send email approval to Masa
$sent = send_email_approval($post_id, $blog, $topic);
log_it($sent ? "Email sent to musilwabonface@gmail.com ✅" : "WARNING: Email failed");

log_it("=== Done ===");

// ============================================================
// GENERATE BLOG via Claude API
// ============================================================
function generate_blog($topic) {
    $system = "You are an expert SEO content writer for MTTI (Masomotele Technical Training Institute), a TVETA-accredited technical college in Eldoret, Kenya. Write friendly, practical blog posts for Kenyan readers. Always include these details naturally: CNA fee=KES 59,000/6 months/minimum grade D, Caregiver=KES 29,000/3 months/any KCSE grade, payment via M-Pesa Paybill 880100 Account 219391, Lipa Mdogo Mdogo flexible payment plan, free computer classes for all students, location: Sagaas Center 4th Floor Eldoret opposite AIC Fellowship on the way to Referral Hospital, phone: 0712464936, WhatsApp: wa.me/254712464936. Return JSON only with these exact keys: title (string), excerpt (2 sentences max), content (full HTML ~800 words using h2, p, ul, li tags — no inline styles), seo_keyphrase (string), tags (array of 5 strings). No markdown, no backticks, JSON only.";

    $prompt = "Write a blog post about {$topic['course']} at MTTI Eldoret, focusing on {$topic['angle']}. Today is " . date('l, d F Y') . ". Return JSON only.";

    $response = claude_api($system, $prompt, 2000);
    if (!$response) return null;

    $clean = trim(preg_replace('/```json|```/', '', $response));
    $data  = json_decode($clean, true);

    if (!$data || empty($data['title'])) {
        log_it("ERROR: Bad JSON from Claude: " . substr($clean, 0, 200));
        return null;
    }
    return $data;
}

// ============================================================
// SAVE AS WORDPRESS DRAFT via REST API
// ============================================================
function save_wordpress_draft($blog) {
    $endpoint = WP_URL . '/wp-json/wp/v2/posts';

    $body = json_encode([
        'title'   => $blog['title'],
        'content' => $blog['content'],
        'excerpt' => $blog['excerpt'],
        'status'  => 'draft',
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(WP_USER . ':' . WP_APP_PASS),
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $result = curl_exec($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 201) {
        log_it("WP API error $code: " . substr($result, 0, 300));
        return null;
    }

    $data = json_decode($result, true);
    return $data['id'] ?? null;
}

// ============================================================
// SEND EMAIL APPROVAL
// ============================================================
function send_email_approval($post_id, $blog, $topic) {
    $approve_url = SELF_URL . "?action=approve&post_id={$post_id}&secret=" . APPROVE_SECRET;
    $reject_url  = SELF_URL . "?action=reject&post_id={$post_id}&secret=" . APPROVE_SECRET;
    $edit_url    = WP_URL . "/wp-admin/post.php?post={$post_id}&action=edit";

    $subject = "📝 MTTI Blog Ready for Approval — " . date('l, d F Y');

    $body = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#333'>

<div style='background:#3D6318;padding:20px;border-radius:8px 8px 0 0;text-align:center'>
  <h1 style='color:#fff;margin:0;font-size:22px'>🤖 MTTI AutoBlog</h1>
  <p style='color:rgba(255,255,255,0.8);margin:5px 0 0'>Daily Post Ready for Your Approval</p>
</div>

<div style='background:#fff;border:1px solid #ddd;padding:25px;border-radius:0 0 8px 8px'>

  <p style='color:#666;margin-top:0'>📅 <strong>" . date('l, d F Y') . "</strong> &nbsp;|&nbsp; 📚 Course: <strong>{$topic['course']}</strong></p>

  <div style='background:#f9f9f9;border-left:4px solid #3D6318;padding:15px;margin:20px 0;border-radius:4px'>
    <h2 style='margin:0 0 10px;color:#3D6318;font-size:18px'>{$blog['title']}</h2>
    <p style='margin:0;color:#555;font-size:14px'>{$blog['excerpt']}</p>
  </div>

  <table width='100%' style='margin:25px 0'>
    <tr>
      <td style='padding:5px'>
        <a href='{$approve_url}' style='display:block;background:#3D6318;color:#fff;text-align:center;padding:15px;border-radius:6px;text-decoration:none;font-size:16px;font-weight:bold'>
          ✅ APPROVE &amp; PUBLISH
        </a>
      </td>
      <td style='padding:5px'>
        <a href='{$reject_url}' style='display:block;background:#cc0000;color:#fff;text-align:center;padding:15px;border-radius:6px;text-decoration:none;font-size:16px;font-weight:bold'>
          ❌ REJECT
        </a>
      </td>
    </tr>
  </table>

  <p style='text-align:center'>
    <a href='{$edit_url}' style='color:#3D6318;font-size:14px'>✏️ Edit in WordPress first</a>
  </p>

  <hr style='border:none;border-top:1px solid #eee;margin:20px 0'>
  <p style='color:#999;font-size:12px;text-align:center;margin:0'>
    After approval, Blog2Social will automatically post to Facebook 📘<br>
    MTTI AutoBlog System — masomoteletraining.co.ke
  </p>

</div>
</body>
</html>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: MTTI AutoBlog <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";

    $sent = mail(NOTIFY_EMAIL, $subject, $body, $headers);
    return $sent;
}

// ============================================================
// HANDLE APPROVAL — publish post
// ============================================================
function handle_approve($post_id) {
    $ch = curl_init(WP_URL . "/wp-json/wp/v2/posts/{$post_id}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode(['status' => 'publish']),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(WP_USER . ':' . WP_APP_PASS),
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $result = curl_exec($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        $data = json_decode($result, true);
        $url  = $data['link'] ?? WP_URL;
        log_it("Post $post_id APPROVED and published ✅ — $url");

        // Send confirmation email
        $subject = "✅ MTTI Blog Published — " . date('d F Y');
        $body = "<h2 style='font-family:Arial;color:#3D6318'>Blog Published Successfully!</h2>
                 <p style='font-family:Arial'>🌐 <a href='{$url}'>{$url}</a></p>
                 <p style='font-family:Arial'>📘 Blog2Social is posting to Facebook automatically.</p>";
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: MTTI AutoBlog <" . FROM_EMAIL . ">\r\n";
        mail(NOTIFY_EMAIL, $subject, $body, $headers);

        echo "<div style='font-family:Arial;max-width:500px;margin:50px auto;text-align:center;padding:30px;border:1px solid #ddd;border-radius:8px'>
                <h2 style='color:#3D6318'>✅ Blog Published!</h2>
                <p>Your blog is now live on the website.</p>
                <p>📘 Blog2Social is posting to Facebook automatically.</p>
                <p><a href='{$url}' style='color:#3D6318'>{$url}</a></p>
              </div>";
    } else {
        log_it("ERROR approving post $post_id: $code");
        echo "<div style='font-family:Arial;max-width:500px;margin:50px auto;text-align:center;padding:30px;border:1px solid #ddd;border-radius:8px'>
                <h2 style='color:red'>❌ Error Publishing</h2>
                <p>Please publish manually in WordPress.</p>
              </div>";
    }
}

// ============================================================
// HANDLE REJECTION — keep as draft
// ============================================================
function handle_reject($post_id) {
    log_it("Post $post_id REJECTED — kept as draft");
    $edit_url = WP_URL . "/wp-admin/post.php?post={$post_id}&action=edit";

    $subject = "❌ MTTI Blog Rejected — Kept as Draft";
    $body = "<h2 style='font-family:Arial;color:#cc6600'>Blog Kept as Draft</h2>
             <p style='font-family:Arial'><a href='{$edit_url}'>Edit it in WordPress</a></p>";
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: MTTI AutoBlog <" . FROM_EMAIL . ">\r\n";
    mail(NOTIFY_EMAIL, $subject, $body, $headers);

    echo "<div style='font-family:Arial;max-width:500px;margin:50px auto;text-align:center;padding:30px;border:1px solid #ddd;border-radius:8px'>
            <h2 style='color:#cc6600'>Post Kept as Draft</h2>
            <p><a href='{$edit_url}' style='color:#3D6318'>Edit it in WordPress</a></p>
          </div>";
}

// ============================================================
// CLAUDE API
// ============================================================
function claude_api($system, $prompt, $max_tokens = 2000) {
    $key = get_wp_option('mtti_claude_api_key');
    if (!$key) { log_it("ERROR: Claude API key missing"); return null; }

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => $max_tokens,
            'system'     => $system,
            'messages'   => [['role'=>'user','content'=>$prompt]],
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $key,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 60,
    ]);
    $result = curl_exec($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) { log_it("Claude API error $code"); return null; }
    $data = json_decode($result, true);
    return $data['content'][0]['text'] ?? null;
}

// ============================================================
// GET WORDPRESS OPTION FROM DB
// ============================================================
function get_wp_option($key) {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]);
        } catch (Exception $e) { return null; }
    }
    $s = $pdo->prepare("SELECT option_value FROM ".DB_PREFIX."options WHERE option_name=? LIMIT 1");
    $s->execute([$key]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['option_value'] : null;
}

// ============================================================
// LOG
// ============================================================
function log_it($msg) {
    $line = '['.date('Y-m-d H:i:s').'] '.$msg.PHP_EOL;
    file_put_contents(LOG_FILE, $line, FILE_APPEND);
    if (php_sapi_name() === 'cli') echo $line;
}
