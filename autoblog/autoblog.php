<?php
/**
 * MTTI Auto-Blog System v2
 * Generates blog post via Claude → publishes immediately → sends email notification
 * Runs daily at 4am UTC (7am EAT) via cron
 *
 * Cron: 0 4 * * * /usr/local/bin/php /home/uvyzhdzt/public_html/autoblog/autoblog.php
 */

error_reporting(0);
ini_set('display_errors', 0);

// ============================================================
// CONFIGURATION
// ============================================================
define('WP_URL',        'https://masomoteletraining.co.ke');
define('WP_USER',       'admin');
define('WP_APP_PASS',   'jLxI kXL0 UiJQ iOcI 3V3Y PREp');
define('NOTIFY_EMAIL',  'musilwabonface@gmail.com');
define('FROM_EMAIL',    'noreply@masomoteletraining.co.ke');
define('LOG_FILE',      __DIR__ . '/autoblog.log');
define('DB_HOST',       'localhost');
define('DB_NAME',       'uvyzhdzt_wp265');
define('DB_USER',       'uvyzhdzt_wp265');
define('DB_PASS',       'p!PS(1S17Y');
define('DB_PREFIX',     'wpcu_');

// ============================================================
// TOPIC POOL — cycles through all courses over 2 weeks
// ============================================================
$TOPICS = [
    ['course'=>'Certified Nursing Assistant (CNA)',   'angle'=>'career opportunities and salary in Kenya healthcare sector'],
    ['course'=>'Caregiver',                           'angle'=>'entry requirements, day-to-day duties and demand in Kenya and abroad'],
    ['course'=>'CNA vs Caregiver',                    'angle'=>'key differences, fees, grades required and which suits you'],
    ['course'=>'Computer Applications',               'angle'=>'office jobs, government employment and business use in Eldoret'],
    ['course'=>'Web Design & Development',            'angle'=>'freelancing, building websites and earning online in Kenya'],
    ['course'=>'Graphic Design',                      'angle'=>'starting a design business and working with Kenyan brands'],
    ['course'=>'Digital Marketing',                   'angle'=>'social media management, growing businesses online and job opportunities'],
    ['course'=>'Cybersecurity',                       'angle'=>'protecting businesses, banking sector careers and ethical hacking in Kenya'],
    ['course'=>'Mobile Phone Repair',                 'angle'=>'self-employment, profit margins and demand in Eldoret and beyond'],
    ['course'=>'Computer Hardware Repair',            'angle'=>'starting a computer repair shop and servicing businesses in Eldoret'],
    ['course'=>'German Language',                     'angle'=>'working in Germany, visa requirements and demand for Kenyan professionals'],
    ['course'=>'TVET vs University',                  'angle'=>'cost, time to employment and best choice for Kenyan students in 2025'],
    ['course'=>'HELB and Bursary Funding',            'angle'=>'how Kenyan TVET students can access government financial support'],
    ['course'=>'All MTTI Courses',                    'angle'=>'which course matches your career goals and personality'],
];

// Pick today's topic based on day-of-year so it rotates through all topics
$topic_index = intval(date('z')) % count($TOPICS);
$topic = $TOPICS[$topic_index];

// ============================================================
// ACCESS CONTROL
// ============================================================
$is_cron   = (php_sapi_name() === 'cli');
$is_manual = isset($_GET['run']) && $_GET['run'] === 'MTTI_RUN_2026';
if (!$is_cron && !$is_manual) { http_response_code(403); die('Access denied.'); }

if (!$is_cron) {
    ob_start();
    echo "<h2 style='font-family:sans-serif;padding:2rem;color:green'>⏳ AutoBlog running... check your email and WordPress in 60 seconds.</h2>";
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
log_it("=== MTTI AutoBlog v2 Started ===");
log_it("Topic #{$topic_index}: {$topic['course']} — {$topic['angle']}");

// 1. Generate blog via Claude
$blog = generate_blog($topic);
if (!$blog) { log_it("ERROR: Blog generation failed"); exit; }

// 2. Publish immediately to WordPress
$result = publish_post($blog);
if (!$result) { log_it("ERROR: Could not publish post"); exit; }

log_it("Published: {$result['url']} (ID {$result['id']})");

// 3. Send email notification
notify_email($result, $blog, $topic);

log_it("=== Done ===");

// ============================================================
// GENERATE BLOG via Claude API
// ============================================================
function generate_blog($topic) {
    $system = "You are an SEO content writer for MTTI (Masomotele Technical Training Institute), a TVETA-accredited college in Eldoret, Kenya. Write practical, friendly blog posts for Kenyan readers aged 18-35. Key facts to weave in naturally where relevant: CNA course KES 59,000 / 6 months / minimum D grade; Caregiver KES 29,000 / 3 months / any grade; Computer Applications from KES 5,500; Web Dev KES 18,000; Cybersecurity KES 25,000; Graphic Design KES 12,000; Digital Marketing KES 10,000; German Language per level KES 8,000; Mobile Phone Repair KES 15,000; flexible installment payment plans available; M-Pesa Paybill 880100 Account 219391; location: Sagaas Centre 4th Floor Eldoret; phone/WhatsApp: 0712464936; URL: https://masomoteletraining.co.ke. IMPORTANT: Return ONLY a raw JSON object. No markdown, no code fences, no explanation. The JSON must have these exact keys: title (string), excerpt (string, max 2 sentences), content (string, full HTML ~800 words using h2/h3/p/ul/li tags), seo_keyphrase (string), tags (array of 5 strings).";

    $prompt = "Write a blog post about {$topic['course']} at MTTI Eldoret, focusing on: {$topic['angle']}. Today is " . date('l, d F Y') . ". Return raw JSON only — no markdown, no backticks, start directly with {";

    $response = claude_api($system, $prompt, 2500);
    if (!$response) return null;

    // Robust JSON extraction — find the outermost { } block
    $json_str = extract_json($response);
    if (!$json_str) {
        log_it("ERROR: Could not extract JSON from response: " . substr($response, 0, 200));
        return null;
    }

    $data = json_decode($json_str, true);
    if (!$data || empty($data['title']) || empty($data['content'])) {
        log_it("ERROR: Bad JSON structure: " . substr($json_str, 0, 200));
        return null;
    }

    return $data;
}

// Extract the first valid JSON object from a string
function extract_json($str) {
    $str = trim($str);
    // Strip markdown code fences if present
    $str = preg_replace('/^```(?:json)?\s*/i', '', $str);
    $str = preg_replace('/\s*```\s*$/', '', $str);
    $str = trim($str);

    // Find the start of the JSON object
    $start = strpos($str, '{');
    if ($start === false) return null;

    // Find matching closing brace by counting depth
    $depth = 0;
    $in_string = false;
    $escape_next = false;
    $len = strlen($str);

    for ($i = $start; $i < $len; $i++) {
        $c = $str[$i];
        if ($escape_next) { $escape_next = false; continue; }
        if ($c === '\\' && $in_string) { $escape_next = true; continue; }
        if ($c === '"') { $in_string = !$in_string; continue; }
        if ($in_string) continue;
        if ($c === '{') $depth++;
        elseif ($c === '}') {
            $depth--;
            if ($depth === 0) return substr($str, $start, $i - $start + 1);
        }
    }
    return null;
}

// ============================================================
// PUBLISH POST via WordPress REST API
// ============================================================
function publish_post($blog) {
    $endpoint = WP_URL . '/wp-json/wp/v2/posts';

    $body = json_encode([
        'title'   => $blog['title'],
        'content' => $blog['content'],
        'excerpt' => $blog['excerpt'],
        'status'  => 'publish',
        'tags'    => get_or_create_tags($blog['tags'] ?? []),
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
    return ['id' => $data['id'], 'url' => $data['link'] ?? WP_URL];
}

// ============================================================
// GET OR CREATE WORDPRESS TAGS
// ============================================================
function get_or_create_tags($tag_names) {
    $ids = [];
    foreach ($tag_names as $name) {
        $name = trim($name);
        if (!$name) continue;

        // Try to create (returns existing if slug already exists)
        $ch = curl_init(WP_URL . '/wp-json/wp/v2/tags');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['name' => $name]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(WP_USER . ':' . WP_APP_PASS),
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $res  = json_decode(curl_exec($ch), true);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 201 && isset($res['id'])) {
            $ids[] = $res['id'];
        } elseif ($code === 400 && isset($res['data']['term_id'])) {
            // Tag already exists — REST API returns term_id in error
            $ids[] = $res['data']['term_id'];
        }
    }
    return $ids;
}

// ============================================================
// EMAIL NOTIFICATION
// ============================================================
function notify_email($result, $blog, $topic) {
    $subject = "✅ MTTI Blog Published: " . $blog['title'];
    $edit_url = WP_URL . "/wp-admin/post.php?post={$result['id']}&action=edit";

    $body = "New blog post published automatically by MTTI AutoBlog.\n\n"
        . "Title: " . $blog['title'] . "\n"
        . "Topic: " . $topic['course'] . " — " . $topic['angle'] . "\n\n"
        . "Live URL:\n" . $result['url'] . "\n\n"
        . "Edit in WordPress:\n" . $edit_url . "\n\n"
        . "---\nMTTI AutoBlog v2 | masomoteletraining.co.ke";

    $headers = "From: MTTI AutoBlog <" . FROM_EMAIL . ">\r\n"
             . "Reply-To: " . NOTIFY_EMAIL . "\r\n"
             . "X-Mailer: PHP/" . phpversion();

    $sent = mail(NOTIFY_EMAIL, $subject, $body, $headers);
    log_it($sent ? "Email notification sent ✅" : "WARNING: Email failed");
}

// ============================================================
// CLAUDE API
// ============================================================
function claude_api($system, $prompt, $max_tokens = 2500) {
    $key = get_wp_option('mtti_claude_api_key');
    if (!$key) { log_it("ERROR: Claude API key missing from WP options"); return null; }

    $payload = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => $max_tokens,
        'system'     => $system,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ]);

    // Try up to 2 times
    for ($attempt = 1; $attempt <= 2; $attempt++) {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_errno($ch);
        curl_close($ch);

        if ($code === 200) {
            $data = json_decode($result, true);
            return $data['content'][0]['text'] ?? null;
        }

        log_it("Claude API attempt $attempt failed — HTTP $code, curl err $err");
        if ($attempt < 2) sleep(5);
    }
    return null;
}

// ============================================================
// GET WORDPRESS OPTION FROM DB
// ============================================================
function get_wp_option($key) {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]
            );
        } catch (Exception $e) { return null; }
    }
    $s = $pdo->prepare("SELECT option_value FROM " . DB_PREFIX . "options WHERE option_name=? LIMIT 1");
    $s->execute([$key]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['option_value'] : null;
}

// ============================================================
// LOG
// ============================================================
function log_it($msg) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents(LOG_FILE, $line, FILE_APPEND);
    if (php_sapi_name() === 'cli') echo $line;
}
