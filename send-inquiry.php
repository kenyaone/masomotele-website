<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Accept JSON body (from fetch) or form-encoded fallback
$raw  = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : null;
if (!$data) $data = $_POST;

// Sanitise inputs
function clean($v) { return htmlspecialchars(trim($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$name     = clean($data['name']     ?? '');
$phone    = clean($data['phone']    ?? '');
$location = clean($data['location'] ?? '');
$course   = clean($data['course']   ?? '');
$message  = clean($data['message']  ?? '');
$source   = clean($data['source']   ?? 'inquiry_form');
$utmSrc   = clean($data['utm']['utm_source']   ?? '');
$utmCamp  = clean($data['utm']['utm_campaign'] ?? '');
$ts       = date('Y-m-d H:i:s');

if (!$name || !$phone || !$course) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

// ── Save lead to CSV (outside web root) ───────────────────────────────────────
$logFile = dirname(__DIR__) . '/mtti_leads.csv';
$isNew   = !file_exists($logFile);
$fp      = fopen($logFile, 'a');
if ($fp) {
    if ($isNew) fputcsv($fp, ['Timestamp','Name','Phone','Location','Course','Message','Source','UTM Source','UTM Campaign']);
    fputcsv($fp, [$ts, $name, $phone, $location, $course, $message, $source, $utmSrc, $utmCamp]);
    fclose($fp);
}

// ── Send email via PHP mail() (uses server sendmail) ─────────────────────────
$to      = 'info@masomoteletraining.co.ke';
$subject = "New Inquiry: {$course} — {$name}";

$body  = "New course inquiry from the MTTI website.\n";
$body .= str_repeat('-', 48) . "\n";
$body .= "Name:      {$name}\n";
$body .= "Phone:     {$phone}\n";
$body .= "Location:  {$location}\n";
$body .= "Course:    {$course}\n";
if ($message) $body .= "Message:   {$message}\n";
$body .= str_repeat('-', 48) . "\n";
$body .= "Source:    {$source}\n";
if ($utmSrc)  $body .= "UTM Source:   {$utmSrc}\n";
if ($utmCamp) $body .= "UTM Campaign: {$utmCamp}\n";
$body .= "Time:      {$ts}\n";

$headers  = "From: MTTI Website <noreply@masomoteletraining.co.ke>\r\n";
$headers .= "Reply-To: {$name} <noreply@masomoteletraining.co.ke>\r\n";
$headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = @mail($to, $subject, $body, $headers);

// ── Respond ───────────────────────────────────────────────────────────────────
echo json_encode(['ok' => true, 'saved' => true, 'emailed' => (bool)$sent]);
