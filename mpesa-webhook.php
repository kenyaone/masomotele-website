<?php
/**
 * ============================================
 * M-Pesa Webhook Handler for MTTI
 * ============================================
 */

require_once __DIR__ . '/wp-load.php';
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$log_file = __DIR__ . '/mpesa_webhook.log';

if (!$data || !isset($data['Body']['stkCallback']['MerchantRequestID'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid webhook data']);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - INVALID: Missing required fields\n", FILE_APPEND);
    exit;
}

$stk_callback = $data['Body']['stkCallback'];
$callback_metadata = $stk_callback['CallbackMetadata']['Item'] ?? [];
$result_code = $stk_callback['ResultCode'] ?? null;
$account_reference = $stk_callback['AccountReference'] ?? '';
$bill_ref_number = $account_reference;

$amount = 0;
$phone = '';
$transaction_id = '';

foreach ($callback_metadata as $item) {
    if ($item['Name'] === 'Amount') {
        $amount = intval($item['Value']);
    } elseif ($item['Name'] === 'MpesaReceiptNumber') {
        $transaction_id = $item['Value'];
    } elseif ($item['Name'] === 'PhoneNumber') {
        $phone = $item['Value'];
    }
}

if ($result_code != 0) {
    http_response_code(200);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - FAILED: Payment failed\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Payment failed']);
    exit;
}

global $wpdb;

$students_table = $wpdb->prefix . 'mtti_students';
$payments_table = $wpdb->prefix . 'mtti_payments';
$enrollments_table = $wpdb->prefix . 'mtti_enrollments';
$courses_table = $wpdb->prefix . 'mtti_courses';
$balances_table = $wpdb->prefix . 'mtti_student_balances';

if (empty($bill_ref_number)) {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'No admission number']);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: No admission number\n", FILE_APPEND);
    exit;
}

// Check duplicate
$duplicate = $wpdb->get_var($wpdb->prepare(
    "SELECT payment_id FROM $payments_table WHERE transaction_reference = %s LIMIT 1",
    $transaction_id
));

if ($duplicate) {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'Duplicate transaction']);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - DUPLICATE: $transaction_id\n", FILE_APPEND);
    exit;
}

// Get student
$student = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $students_table WHERE admission_number = %s LIMIT 1",
    $bill_ref_number
));

if (!$student) {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: Student not found - $bill_ref_number\n", FILE_APPEND);
    exit;
}

$student_id = intval($student->student_id);
$student_email = $student->email ?? '';
$student_fname = $student->first_name ?? '';
$student_lname = $student->last_name ?? '';
$student_name = trim("$student_fname $student_lname");

// Get enrollment
$enrollment = $wpdb->get_row($wpdb->prepare(
    "SELECT e.*, c.fee, c.course_name
     FROM $enrollments_table e
     LEFT JOIN $courses_table c ON e.course_id = c.course_id
     WHERE e.student_id = %d
     ORDER BY e.enrollment_id DESC
     LIMIT 1",
    $student_id
));

if (!$enrollment) {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'No enrollment found']);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: No enrollment\n", FILE_APPEND);
    exit;
}

$enrollment_id = intval($enrollment->enrollment_id);
$course_fee = floatval($enrollment->fee);
$course_name = $enrollment->course_name ?? 'Unknown';

// Generate receipt
$receipt_number = 'MPESA-' . time() . '-' . rand(10000, 99999);

// Insert payment — columns matched to actual wpcu_mtti_payments schema
$payment_insert_data = [
    'student_id'            => $student_id,
    'enrollment_id'         => $enrollment_id,
    'amount'                => $amount,
    'amount_paid'           => $amount,       // NOT NULL — same as amount
    'gross_amount'          => $amount,
    'total_fees'            => $course_fee,   // NOT NULL
    'discount'              => 0,
    'transaction_reference' => $transaction_id,
    'receipt_number'        => $receipt_number,
    'status'                => 'Completed',
    'payment_method'        => 'M-Pesa',
    'payment_for'           => $course_name,  // NOT NULL
    'payment_date'          => current_time('Y-m-d'), // date type, not datetime
    'recorded_by'           => 0,             // NOT NULL — 0 = system/webhook
    'notes'                 => 'M-Pesa STK Push | Ref: ' . $transaction_id,
];

$payment_inserted = $wpdb->insert($payments_table, $payment_insert_data);

if (!$payment_inserted) {
    http_response_code(200);
    $error = $wpdb->last_error;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: Insert failed - $error\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
    exit;
}

$payment_id = intval($wpdb->insert_id);

// Calculate balance
$total_paid = floatval($wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM $payments_table 
     WHERE enrollment_id = %d AND status = 'Completed'",
    $enrollment_id
)));

$balance_remaining = max(0, $course_fee - $total_paid);

// Update balance table
$balance_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT balance_id FROM $balances_table WHERE enrollment_id = %d LIMIT 1",
    $enrollment_id
));

if ($balance_exists) {
    $wpdb->update(
        $balances_table,
        [
            'total_paid' => $total_paid,
            'balance' => $balance_remaining,
            'last_payment_date' => current_time('mysql')
        ],
        ['enrollment_id' => $enrollment_id],
        ['%f', '%f', '%s'],
        ['%d']
    );
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Updated balance\n", FILE_APPEND);
} else {
    $wpdb->insert(
        $balances_table,
        [
            'student_id' => $student_id,
            'enrollment_id' => $enrollment_id,
            'total_fee' => $course_fee,
            'discount_amount' => 0,
            'total_paid' => $total_paid,
            'balance' => $balance_remaining,
            'last_payment_date' => current_time('mysql')
        ],
        ['%d', '%d', '%f', '%f', '%f', '%f', '%s']
    );
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Created balance record\n", FILE_APPEND);
}

// Email disabled — PHPMailer crashes on Truehost without SMTP config.
// Install WP Mail SMTP plugin and configure SMTP to re-enable.
// if (!empty($student_email)) { ... }

// Log success
file_put_contents($log_file, date('Y-m-d H:i:s') . " - SUCCESS: Payment $payment_id | Student: $student_name | Amount: KES $amount | Balance: KES " . number_format($balance_remaining, 2) . " | Receipt: $receipt_number\n", FILE_APPEND);

// Return success
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Payment recorded and balance updated',
    'payment_id' => $payment_id,
    'student_name' => $student_name,
    'amount' => $amount,
    'receipt_number' => $receipt_number,
    'total_paid' => $total_paid,
    'balance' => $balance_remaining
]);
?>