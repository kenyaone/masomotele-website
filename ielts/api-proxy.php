<?php
/**
 * MTTI IELTS — Claude API Proxy
 * Place at: masomoteletraining.co.ke/ielts/api-proxy.php
 * Keeps your API key server-side, rate-limits students, logs usage
 */

// ─── CONFIG ──────────────────────────────────────────────────────────────────
define('ANTHROPIC_API_KEY', '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'); // ← Replace with your key
define('MAX_REQUESTS_PER_HOUR', 20);   // Per student IP
define('MAX_TOKENS', 1000);            // Max tokens per request
define('LOG_FILE', __DIR__ . '/logs/api-usage.log');
define('RATE_FILE', __DIR__ . '/logs/rate-limit.json');
define('ALLOWED_MODEL', 'claude-haiku-4-5-20251001'); // Cheapest model

// ─── CORS HEADERS ────────────────────────────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://masomoteletraining.co.ke');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── ONLY ALLOW POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ─── REFERER CHECK (only allow requests from your site) ──────────────────────
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!str_contains($referer, 'masomoteletraining.co.ke')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// ─── ENSURE LOG DIRECTORY EXISTS ─────────────────────────────────────────────
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
    // Block direct access to logs folder
    file_put_contents(__DIR__ . '/logs/.htaccess', "Deny from all\n");
}

// ─── RATE LIMITING ───────────────────────────────────────────────────────────
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip = trim(explode(',', $ip)[0]); // Handle proxies

$rates = [];
if (file_exists(RATE_FILE)) {
    $rates = json_decode(file_get_contents(RATE_FILE), true) ?? [];
}

$now = time();
$hourAgo = $now - 3600;

// Clean old entries
foreach ($rates as $storedIp => $data) {
    $rates[$storedIp]['requests'] = array_filter(
        $data['requests'] ?? [],
        fn($t) => $t > $hourAgo
    );
    if (empty($rates[$storedIp]['requests'])) unset($rates[$storedIp]);
}

$userRequests = count($rates[$ip]['requests'] ?? []);

if ($userRequests >= MAX_REQUESTS_PER_HOUR) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Rate limit reached. You can make ' . MAX_REQUESTS_PER_HOUR . ' AI requests per hour. Please wait and try again.',
        'retry_after' => 3600
    ]);
    logUsage($ip, 'RATE_LIMITED', 0);
    exit;
}

// ─── PARSE REQUEST BODY ──────────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['messages']) || !is_array($body['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

// ─── SANITIZE — force safe model & token limit ───────────────────────────────
$payload = [
    'model'      => ALLOWED_MODEL,
    'max_tokens' => min((int)($body['max_tokens'] ?? MAX_TOKENS), MAX_TOKENS),
    'messages'   => $body['messages']
];

// Optional system prompt passthrough (sanitized)
if (!empty($body['system']) && is_string($body['system'])) {
    $payload['system'] = substr($body['system'], 0, 2000); // Cap length
}

// ─── CALL ANTHROPIC API ──────────────────────────────────────────────────────
$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01'
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['error' => 'Connection to AI service failed. Please try again.']);
    logUsage($ip, 'CURL_ERROR: ' . $curlError, 0);
    exit;
}

// ─── RECORD RATE LIMIT HIT ───────────────────────────────────────────────────
$rates[$ip]['requests'][] = $now;
file_put_contents(RATE_FILE, json_encode($rates), LOCK_EX);

// ─── LOG USAGE ───────────────────────────────────────────────────────────────
$responseData = json_decode($response, true);
$tokensUsed = $responseData['usage']['output_tokens'] ?? 0;
logUsage($ip, 'OK_' . $httpCode, $tokensUsed);

// ─── RETURN RESPONSE ─────────────────────────────────────────────────────────
http_response_code($httpCode);
echo $response;


// ─── LOGGING FUNCTION ────────────────────────────────────────────────────────
function logUsage(string $ip, string $status, int $tokens): void {
    $line = implode(' | ', [
        date('Y-m-d H:i:s'),
        $ip,
        $status,
        "tokens:{$tokens}"
    ]) . PHP_EOL;
    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
