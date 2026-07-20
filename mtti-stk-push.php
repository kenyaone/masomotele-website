<?php
/**
 * M-Pesa STK Push Handler for MTTI — DISABLED 2026-07-19.
 * This was a direct Safaricom Daraja integration still wired to SANDBOX
 * credentials (see MPESA_ENV below), so it was showing students a false
 * "STK Push sent!" success message without processing any real payment.
 * PesaPal is the one real, configured online payment gateway now — the UI
 * box that called this endpoint (learner portal payments tab) has been
 * removed. Left in place (not deleted) with the request short-circuited
 * below, in case a real production Daraja integration is wired up later.
 */

header('Content-Type: application/json');
http_response_code(410);
echo json_encode(['success' => false, 'message' => 'This payment method has been retired. Please use PesaPal.']);
exit;

require_once __DIR__ . '/wp-load.php';

// ── CONFIG ────────────────────────────────────────────────────────────────────
// SANDBOX credentials — replace with Production values when go-live
define('MPESA_ENV',             'sandbox'); // 'sandbox' or 'production'
define('MPESA_CONSUMER_KEY',    'ymvygmNowv6uW8fdusQ9mzSa9dIJvfPQHQKCH1RhWjImggb2');
define('MPESA_CONSUMER_SECRET', 'QobsOUzb7kCLjHslwSsYOPcAZKjkkQAQVG8PBKEkXzpoGtyY9q55F0tUT0QHFx3o');
define('MPESA_SHORTCODE',       '174379');  // Sandbox test shortcode
define('MPESA_PASSKEY',         'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('MPESA_CALLBACK_URL',    'https://masomoteletraining.co.ke/mpesa-webhook.php');

define('MPESA_BASE_URL', MPESA_ENV === 'production'
    ? 'https://api.safaricom.co.ke'
    : 'https://sandbox.safaricom.co.ke'
);

$log_file = __DIR__ . '/mpesa_stk.log';

// ── HELPERS ───────────────────────────────────────────────────────────────────
function mpesa_log($msg) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

function mpesa_get_token() {
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);
    $url = MPESA_BASE_URL . '/oauth/v1/generate?grant_type=client_credentials';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Basic $credentials"],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        mpesa_log("Token error: $err");
        return null;
    }
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

function mpesa_stk_push($token, $phone, $amount, $account_ref, $description) {
    $timestamp = date('YmdHis');
    $password  = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
    $url       = MPESA_BASE_URL . '/mpesa/stkpush/v1/processrequest';

    $payload = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'TransactionType'   => 'CustomerPayBillOnline',
        'Amount'            => intval($amount),
        'PartyA'            => $phone,
        'PartyB'            => MPESA_SHORTCODE,
        'PhoneNumber'       => $phone,
        'CallBackURL'       => MPESA_CALLBACK_URL,
        'AccountReference'  => $account_ref,
        'TransactionDesc'   => $description,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer $token",
            'Content-Type: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        mpesa_log("STK Push error: $err");
        return null;
    }
    return json_decode($response, true);
}

// ── VALIDATE REQUEST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$amount = intval($input['amount'] ?? 0);
$phone  = preg_replace('/\D/', '', $input['phone'] ?? '');
$source = sanitize_text_field($input['source'] ?? 'portal');

// Must be logged in OR calling with server secret key
$server_secret  = 'MTTI_STK_SECRET_2026';
$request_secret = $input['secret'] ?? '';

if (!is_user_logged_in() && $request_secret !== $server_secret) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// If using secret key, force source to admin
if ($request_secret === $server_secret) {
    $source = 'admin';
}

// Normalize phone to 254XXXXXXXXX
if (substr($phone, 0, 1) === '0') $phone = '254' . substr($phone, 1);
if (substr($phone, 0, 3) !== '254') $phone = '254' . $phone;

// Get student
global $wpdb;
$students_table   = $wpdb->prefix . 'mtti_students';
$enrollments_table = $wpdb->prefix . 'mtti_enrollments';
$courses_table    = $wpdb->prefix . 'mtti_courses';

if ($source === 'admin') {
    // Allow if logged-in admin/staff OR using server secret key
    if ($request_secret !== $server_secret && !current_user_can('manage_options') && !current_user_can('mtti_staff')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit;
    }
    $student_id = intval($input['student_id'] ?? 0);
    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $students_table WHERE student_id = %d LIMIT 1",
        $student_id
    ));
} else {
    // Student initiating from portal
    $current_user_id = get_current_user_id();
    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $students_table WHERE user_id = %d LIMIT 1",
        $current_user_id
    ));
}

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Validate amount
if ($amount < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

// Validate phone
if (strlen($phone) !== 12 || substr($phone, 0, 3) !== '254') {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number. Use format 07XXXXXXXX']);
    exit;
}

$admission_number = $student->admission_number;
$student_name     = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

// Get token
$token = mpesa_get_token();
if (!$token) {
    mpesa_log("Failed to get access token for $admission_number");
    echo json_encode(['success' => false, 'message' => 'Could not connect to M-Pesa. Try again.']);
    exit;
}

// Initiate STK Push
$result = mpesa_stk_push(
    $token,
    $phone,
    $amount,
    $admission_number,
    'MTTI Fees - ' . $admission_number
);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'STK Push failed. Try again.']);
    exit;
}

if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
    mpesa_log("STK Push sent | Student: $student_name ($admission_number) | Phone: $phone | Amount: KES $amount | CheckoutID: " . ($result['CheckoutRequestID'] ?? ''));
    echo json_encode([
        'success'            => true,
        'message'            => 'STK Push sent! Check your phone and enter M-Pesa PIN.',
        'checkout_request_id'=> $result['CheckoutRequestID'] ?? '',
        'student_name'       => $student_name,
        'amount'             => $amount,
        'phone'              => $phone,
    ]);
} else {
    $err_msg = $result['errorMessage'] ?? $result['ResultDesc'] ?? 'Unknown error';
    mpesa_log("STK Push failed | Student: $admission_number | Error: $err_msg | Response: " . json_encode($result));
    echo json_encode(['success' => false, 'message' => 'M-Pesa error: ' . $err_msg]);
}
