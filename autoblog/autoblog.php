<?php
/**
 * MTTI Auto-Blog System — Blogs Only
 * Generates blog post via Claude → saves as WordPress draft
 * Sends WhatsApp to Masa for approval
 * On approval → publishes live → Blog2Social posts to Facebook automatically
 *
 * Deploy to: /home/uvyzhdzt/public_html/autoblog/autoblog.php
 * Cron: 0 4 * * * /usr/bin/php /home/uvyzhdzt/public_html/autoblog/autoblog.php
 * (4am UTC = 7am EAT Kenya time)
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
define('SP_PHONE',      '254712464936');
define('LOG_FILE',      __DIR__ . '/autoblog.log');
define('DB_HOST',       'localhost');
define('DB_NAME',       'uvyzhdzt_wp265');
define('DB_USER',       'uvyzhdzt_wp265');
define('DB_PASS',       'p!PS(1S17Y');
define('DB_PREFIX',     'wpcu_');

// ============================================================
// TOPIC ROTATION — CNA most, then Computer Repair, Mobile Repair
// ============================================================
$TOPIC_ROTATION = [
    'monday'    => ['course'=>'CNA',             'angle'=>'benefits and career opportunities for Kenyan students'],
    'tuesday'   => ['course'=>'Computer Repair', 'angle'=>'skills, employment and self employment in Eldoret'],
    'wednesday' => ['course'=>'CNA and Caregiver','angle'=>'entry requirements, fees and differences'],
    'thursday'  => ['course'=>'Mobile Phone Repair','angle'=>'business opportunity and demand in Kenya'],
    'friday'    => ['course'=>'CNA',             'angle'=>'student success stories and job placement'],
    'saturday'  => ['course'=>'Computer Repair', 'angle'=>'starting your own tech business in Eldoret'],
    'sunday'    => ['course'=>'All MTTI Courses','angle'=>'why MTTI graduates get employed fast'],
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
    echo "<h2 style='font-family:sans-serif;padding:2rem;color:green'>⏳ AutoBlog running in background...<br>Check your WhatsApp in 60 seconds and WordPress Drafts.</h2>";
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

// 3. Send WhatsApp approval to Masa
$sent = send_whatsapp_approval($post_id, $blog, $topic);
log_it($sent ? "WhatsApp sent ✅" : "WARNING: WhatsApp failed");

log_it("=== Done ===");

// ============================================================
// GENERATE BLOG via Claude API
// ============================================================
function generate_blog($topic) {
    $system = "You are an expert SEO content writer for MTTI (Masomotele Technical Training Institute), a TVETA-accredited technical college in Eldoret, Kenya. Write friendly, practical blog posts for Kenyan readers. Always include: CNA fee=KES59,000/6months/min grade D, Caregiver=KES29,000/3months/any grade, payment via M-Pesa Paybill 880100 Account 219391, Lipa Mdogo Mdogo payment plan, free computer classes for all students, location: Sagaas Center 4th Floor Eldoret opposite AIC Fellowship on the way to Referral Hospital, phone: 0712464936, WhatsApp: wa.me/254712464936. Return JSON only with keys: title, excerpt (2 sentences max), content (full HTML ~800 words using h2 tags, p tags, ul/li tags), seo_keyphrase, tags (array of 5 strings). No markdown, no backticks.";

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
// SEND WHATSAPP APPROVAL MESSAGE
// ============================================================
function send_whatsapp_approval($post_id, $blog, $topic) {
    $approve_url = SELF_URL . "?action=approve&post_id={$post_id}&secret=" . APPROVE_SECRET;
    $reject_url  = SELF_URL . "?action=reject&post_id={$post_id}&secret=" . APPROVE_SECRET;
    $edit_url    = WP_URL . "/wp-admin/post.php?post={$post_id}&action=edit";

    $message = "🤖 *MTTI AutoBlog — Daily Post Ready*\n\n"
        . "📅 " . date('l, d F Y') . "\n"
        . "📚 *Course:* {$topic['course']}\n\n"
        . "📝 *Title:*\n{$blog['title']}\n\n"
        . "📖 *Preview:*\n{$blog['excerpt']}\n\n"
        . "━━━━━━━━━━━━━━━\n"
        . "✅ *APPROVE & PUBLISH:*\n{$approve_url}\n\n"
        . "❌ *REJECT (keep draft):*\n{$reject_url}\n\n"
        . "✏️ *EDIT IN WORDPRESS:*\n{$edit_url}\n"
        . "━━━━━━━━━━━━━━━\n"
        . "_Blog2Social will auto-post to Facebook after approval_";

    return sendpulse_whatsapp(SP_PHONE, $message);
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
        log_it("Post $post_id APPROVED and published ✅");
        sendpulse_whatsapp(SP_PHONE,
            "✅ *Blog published!*\n\n"
            . "🌐 {$url}\n\n"
            . "Blog2Social is posting to Facebook automatically. 📘"
        );
        echo "<h2 style='color:green;font-family:sans-serif;padding:2rem'>✅ Blog published! Blog2Social will post to Facebook automatically.</h2>";
    } else {
        log_it("ERROR approving post $post_id: $code");
        echo "<h2 style='color:red;font-family:sans-serif;padding:2rem'>❌ Error publishing. Check WordPress.</h2>";
    }
}

// ============================================================
// HANDLE REJECTION — keep as draft
// ============================================================
function handle_reject($post_id) {
    log_it("Post $post_id REJECTED — kept as draft");
    sendpulse_whatsapp(SP_PHONE,
        "❌ *Blog rejected — kept as draft.*\n\n"
        . "Edit here:\n" . WP_URL . "/wp-admin/post.php?post={$post_id}&action=edit"
    );
    echo "<h2 style='color:orange;font-family:sans-serif;padding:2rem'>Post kept as draft. Edit in WordPress when ready.</h2>";
}

// ============================================================
// SENDPULSE WHATSAPP
// ============================================================
function sendpulse_whatsapp($phone, $message) {
    $token = get_sendpulse_token();
    if (!$token) return false;

    $ch = curl_init('https://api.sendpulse.com/whatsapp/contacts/sendByPhone');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'phone'   => $phone,
            'message' => ['type' => 'text', 'text' => $message],
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "Authorization: Bearer $token",
        ],
        CURLOPT_TIMEOUT => 20,
    ]);
    $result = curl_exec($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}

// ============================================================
// SENDPULSE OAUTH TOKEN
// ============================================================
function get_sendpulse_token() {
    $cache = sys_get_temp_dir() . '/mtti_sp_token.json';
    if (file_exists($cache)) {
        $c = json_decode(file_get_contents($cache), true);
        if ($c && $c['expires'] > time()) return $c['token'];
    }

    $client_id     = 'dc5d02e7d81e6f4fa56543973a1b93ee';
    $client_secret = '90a9b78f856ec5f9c4e1621f41070d35';
    if (!$client_id || !$client_secret) {
        log_it("ERROR: SendPulse credentials missing"); return null;
    }

    $ch = curl_init('https://api.sendpulse.com/oauth/access_token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'grant_type'    => 'client_credentials',
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT    => 15,
    ]);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($data['access_token'])) return null;
    file_put_contents($cache, json_encode(['token'=>$data['access_token'],'expires'=>time()+3300]));
    return $data['access_token'];
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
