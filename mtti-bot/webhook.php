<?php
/**
 * M.T.T.I WhatsApp Chatbot
 * Powered by Claude AI via Anthropic API
 * WhatsApp delivery via Africa's Talking
 * 
 * DEPLOYMENT: Upload to your WordPress hosting
 * URL: https://yoursite.com/mtti-bot/webhook.php
 */

// ============================================================
// CONFIGURATION — Edit these values
// ============================================================
define('ANTHROPIC_API_KEY', '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');
define('AT_API_KEY', 'your-africastalking-api-key-here');
define('AT_USERNAME', 'your-africastalking-username');
define('AT_WHATSAPP_PRODUCT_ID', 'your-whatsapp-product-id');
define('BOT_PHONE', '+254XXXXXXXXX'); // Your WhatsApp Business number
define('ADMIN_PHONE', '+254XXXXXXXXX'); // Your personal number for lead alerts
define('LEADS_FILE', __DIR__ . '/data/leads.json');
define('CONVERSATIONS_FILE', __DIR__ . '/data/conversations.json');
define('LOG_FILE', __DIR__ . '/data/bot.log');
define('MAX_HISTORY', 10); // Messages to remember per conversation
define('DAILY_BUDGET_LIMIT', 100); // Max API calls per day

// ============================================================
// SYSTEM PROMPT — M.T.T.I Knowledge Base
// ============================================================
define('MTTI_SYSTEM_PROMPT', '
You are the official WhatsApp assistant for Masomotele Technical Training Institute (M.T.T.I), located at Sagaas Center, Eldoret, Uasin Gishu County, Kenya.

YOUR PERSONALITY:
- Friendly, helpful, and encouraging
- You speak English, Swahili, and Sheng naturally — match whatever language the student uses
- Keep responses SHORT (2-4 sentences max for WhatsApp). Use line breaks for readability.
- Use emojis sparingly but warmly
- Your goal is to ANSWER their question and CAPTURE their interest so they enroll
- Never say "I am an AI" or "I am a chatbot" — you are the M.T.T.I admissions assistant
- If you cannot answer something, say "Let me connect you with our admissions team" and ask for their name and phone number

INSTITUTION DETAILS:
- Full Name: Masomotele Technical Training Institute (M.T.T.I)
- Motto: "Start Learning, Start Earning"
- Accreditation: TVETA (Technical and Vocational Education and Training Authority) accredited
- Location: Sagaas Center, Eldoret, Uasin Gishu County
- Established: Operational for multiple years with proven graduate employment outcomes
- Open Hours: Monday-Friday 8:00 AM - 5:00 PM, Saturday 9:00 AM - 1:00 PM

COURSES OFFERED:
(Update these with your actual current fees and dates)

1. COMPUTER APPLICATIONS
   - Duration: 1 month
   - Fee: KES 5,000 (installments available)
   - Content: Microsoft Office (Word, Excel, PowerPoint), Internet & Email, Typing, Basic troubleshooting
   - Certificate: M.T.T.I Certificate + TVETA recognized
   - Best for: Office jobs, data entry, administration

2. WEB DEVELOPMENT
   - Duration: 2 months
   - Fee: KES 12,000 (installments available)
   - Content: HTML, CSS, JavaScript, WordPress, responsive design, hosting & deployment
   - Certificate: M.T.T.I Certificate
   - Best for: Freelancing, tech companies, self-employment building websites

3. GRAPHIC DESIGN
   - Duration: 2 months
   - Fee: KES 10,000 (installments available)
   - Content: Photoshop, Illustrator, CorelDraw, branding, logo design, social media graphics, print design
   - Certificate: M.T.T.I Certificate
   - Best for: Marketing agencies, printing shops, freelancing, self-employment

4. DIGITAL MARKETING
   - Duration: 1 month
   - Fee: KES 8,000 (installments available)
   - Content: Social media marketing, Google Ads, SEO, content marketing, email marketing, analytics
   - Certificate: M.T.T.I Certificate
   - Best for: Business owners, marketing roles, freelancing

5. CYBERSECURITY
   - Duration: 2 months
   - Fee: KES 15,000 (installments available)
   - Content: Network security, ethical hacking basics, security auditing, data protection, incident response
   - Certificate: M.T.T.I Certificate
   - Best for: IT departments, banks, government, telecom companies

6. MOBILE PHONE REPAIR
   - Duration: 1 month
   - Fee: KES 7,000 (installments available)
   - Content: Hardware repair, software troubleshooting, screen replacement, soldering, business setup
   - Certificate: M.T.T.I Certificate
   - Best for: Self-employment, phone repair shops

PAYMENT & ENROLLMENT:
- Payment methods: M-Pesa (Paybill: [YOUR_PAYBILL]), Bank transfer, Cash at office
- Installment plans: Pay 50% to start, balance within first 2 weeks
- Registration fee: KES 500 (included in course fee)
- What to bring for enrollment: National ID or birth certificate, 1 passport photo, registration fee
- Walk-in enrollment: Monday-Friday 8AM-5PM at Sagaas Center
- Next intake: [UPDATE WITH CURRENT DATE]

SCHOLARSHIPS & DISCOUNTS:
- Early bird: 10% discount for registering 2 weeks before intake
- Group discount: 15% for 3 or more students registering together
- Referral: KES 500 off for each friend you refer who enrolls
- Scholarship: Limited partial scholarships available for vulnerable youth — ask admissions

FACILITIES:
- 2 fully equipped computer labs (40 workstations)
- High-speed internet
- Practical workshop for mobile repair
- Digital library with offline learning resources (RACHEL server)
- Comfortable learning environment with natural lighting

EMPLOYMENT OUTCOMES:
- 75% of graduates get employed or start businesses within 6 months
- Job placement assistance program
- Internship connections with local businesses
- Alumni network for continued support
- Portfolio building during the course

FREQUENTLY ASKED QUESTIONS:
Q: Do I need prior experience?
A: No! All courses start from the basics. You just need willingness to learn.

Q: Can I study while working?
A: Yes, ask about our flexible scheduling. Some courses offer weekend or evening options.

Q: What if I miss a class?
A: We offer catch-up sessions. Talk to your trainer.

Q: Is the certificate recognized?
A: Yes, M.T.T.I is TVETA-accredited. Our certificates are recognized by employers across Kenya.

Q: Can I take more than one course?
A: Absolutely! Many students take Computer Applications first, then specialize. We offer package discounts.

Q: Do you help with job placement?
A: Yes! We have a job placement program that connects graduates with employers. We also help you build a professional portfolio during training.

LEAD CAPTURE INSTRUCTIONS:
When a student shows interest in a specific course, naturally ask:
1. Their first name
2. Which course they are interested in
3. When they would like to start

Format your response to include a clear next step — visiting campus, calling admissions, or reserving a spot.

If someone seems ready to enroll, give them the M-Pesa Paybill and tell them to send the registration fee with their name as reference, then visit the office with their ID and photo.

IMPORTANT RULES:
- Never make up information you do not have
- Never discuss other institutions or compare with competitors
- If asked about courses you do not offer, say "We do not currently offer that, but here is what we have that might interest you..." and suggest the closest match
- Always end with a clear call to action (visit, call, register, ask another question)
- If someone is rude or abusive, stay professional and offer to connect them with management
');

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function bot_log($message) {
    $dir = dirname(LOG_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

function load_json($file) {
    if (!file_exists($file)) return array();
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : array();
}

function save_json($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function check_daily_limit() {
    $today = date('Y-m-d');
    $counter_file = __DIR__ . '/data/daily_count.json';
    $counts = load_json($counter_file);
    
    if (!isset($counts[$today])) {
        $counts = array($today => 0); // Reset old days
    }
    
    if ($counts[$today] >= DAILY_BUDGET_LIMIT) {
        return false;
    }
    
    $counts[$today]++;
    save_json($counter_file, $counts);
    return true;
}

// ============================================================
// CONVERSATION HISTORY MANAGEMENT
// ============================================================

function get_conversation($phone) {
    $conversations = load_json(CONVERSATIONS_FILE);
    if (isset($conversations[$phone])) {
        return $conversations[$phone];
    }
    return array('messages' => array(), 'started' => date('Y-m-d H:i:s'));
}

function save_conversation($phone, $user_msg, $bot_reply) {
    $conversations = load_json(CONVERSATIONS_FILE);
    
    if (!isset($conversations[$phone])) {
        $conversations[$phone] = array('messages' => array(), 'started' => date('Y-m-d H:i:s'));
    }
    
    $conversations[$phone]['messages'][] = array('role' => 'user', 'content' => $user_msg);
    $conversations[$phone]['messages'][] = array('role' => 'assistant', 'content' => $bot_reply);
    $conversations[$phone]['last_active'] = date('Y-m-d H:i:s');
    
    // Keep only last N messages
    if (count($conversations[$phone]['messages']) > MAX_HISTORY * 2) {
        $conversations[$phone]['messages'] = array_slice($conversations[$phone]['messages'], -(MAX_HISTORY * 2));
    }
    
    save_json(CONVERSATIONS_FILE, $conversations);
}

// ============================================================
// LEAD CAPTURE
// ============================================================

function save_lead($phone, $name, $course_interest, $source_message) {
    $leads = load_json(LEADS_FILE);
    
    $lead = array(
        'phone' => $phone,
        'name' => $name,
        'course_interest' => $course_interest,
        'first_contact' => date('Y-m-d H:i:s'),
        'source' => 'whatsapp_bot',
        'status' => 'new',
        'message' => substr($source_message, 0, 500),
    );
    
    // Update existing or add new
    $found = false;
    foreach ($leads as &$existing) {
        if ($existing['phone'] === $phone) {
            $existing['name'] = $name ?: $existing['name'];
            $existing['course_interest'] = $course_interest ?: $existing['course_interest'];
            $existing['last_contact'] = date('Y-m-d H:i:s');
            $existing['contact_count'] = ($existing['contact_count'] ?? 1) + 1;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $leads[] = $lead;
    }
    
    save_json(LEADS_FILE, $leads);
    return $lead;
}

function detect_lead_info($message, $bot_reply) {
    $info = array('name' => null, 'course' => null);
    
    // Detect course interest from conversation
    $courses = array(
        'computer' => 'Computer Applications',
        'web' => 'Web Development',
        'graphic' => 'Graphic Design',
        'design' => 'Graphic Design',
        'digital marketing' => 'Digital Marketing',
        'marketing' => 'Digital Marketing',
        'cyber' => 'Cybersecurity',
        'security' => 'Cybersecurity',
        'hacking' => 'Cybersecurity',
        'mobile' => 'Mobile Phone Repair',
        'phone repair' => 'Mobile Phone Repair',
    );
    
    $msg_lower = strtolower($message);
    foreach ($courses as $keyword => $course_name) {
        if (strpos($msg_lower, $keyword) !== false) {
            $info['course'] = $course_name;
            break;
        }
    }
    
    // Try to detect name (simple: "my name is X" or "I'm X" or "I am X")
    if (preg_match('/(?:my name is|i\'?m|i am|jina ni|naitwa)\s+([A-Z][a-z]+)/i', $message, $matches)) {
        $info['name'] = ucfirst(strtolower($matches[1]));
    }
    
    return $info;
}

// ============================================================
// CLAUDE AI — Generate Response
// ============================================================

function ask_claude($phone, $user_message) {
    $conversation = get_conversation($phone);
    
    // Build messages array with history
    $messages = array();
    foreach ($conversation['messages'] as $msg) {
        $messages[] = array('role' => $msg['role'], 'content' => $msg['content']);
    }
    $messages[] = array('role' => 'user', 'content' => $user_message);
    
    $payload = array(
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 300, // Keep WhatsApp responses short
        'system' => MTTI_SYSTEM_PROMPT,
        'messages' => $messages,
    );
    
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
        ),
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        bot_log("CURL ERROR: {$error}");
        return "Thank you for your message! Our team will get back to you shortly. You can also call us or visit us at Sagaas Center, Eldoret.";
    }
    
    $data = json_decode($response, true);
    
    if ($http_code !== 200) {
        bot_log("API ERROR ({$http_code}): " . ($data['error']['message'] ?? 'Unknown'));
        return "Thank you for your message! Our team will get back to you shortly. You can also call us or visit us at Sagaas Center, Eldoret.";
    }
    
    if (isset($data['content'][0]['text'])) {
        return $data['content'][0]['text'];
    }
    
    return "Thank you for reaching out to M.T.T.I! How can I help you today?";
}

// ============================================================
// SEND WHATSAPP MESSAGE — Africa's Talking
// ============================================================

function send_whatsapp($to, $message) {
    $payload = array(
        'username' => AT_USERNAME,
        'productId' => AT_WHATSAPP_PRODUCT_ID,
        'to' => $to,
        'message' => $message,
    );
    
    $ch = curl_init('https://content.africastalking.com/whatsapp/send');
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'apiKey: ' . AT_API_KEY,
            'Accept: application/json',
        ),
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ));
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        bot_log("SEND ERROR to {$to}: {$error}");
        return false;
    }
    
    bot_log("SENT to {$to}: " . substr($message, 0, 100) . "...");
    return true;
}

// Send lead alert to admin
function notify_admin($lead) {
    $name = $lead['name'] ?: 'Unknown';
    $course = $lead['course_interest'] ?: 'Not specified';
    $phone = $lead['phone'];
    
    $alert = "🔔 NEW MTTI LEAD\n\n";
    $alert .= "👤 Name: {$name}\n";
    $alert .= "📱 Phone: {$phone}\n";
    $alert .= "📚 Interest: {$course}\n";
    $alert .= "🕐 Time: " . date('d M Y H:i') . "\n\n";
    $alert .= "💬 Message: \"{$lead['message']}\"\n\n";
    $alert .= "👉 Call them within 1 hour for best conversion!";
    
    send_whatsapp(ADMIN_PHONE, $alert);
}

// ============================================================
// WEBHOOK — Receives incoming WhatsApp messages
// ============================================================

// Read incoming webhook
$input = file_get_contents('php://input');
bot_log("INCOMING: " . substr($input, 0, 500));

$data = json_decode($input, true);

// Africa's Talking WhatsApp webhook format
$from = null;
$message_text = null;

if (isset($data['messages']) && is_array($data['messages'])) {
    // Standard AT WhatsApp webhook
    foreach ($data['messages'] as $msg) {
        $from = $msg['from'] ?? null;
        $message_text = $msg['text']['body'] ?? ($msg['body'] ?? null);
        break; // Process first message
    }
} elseif (isset($data['from'])) {
    // Simplified format
    $from = $data['from'];
    $message_text = $data['text']['body'] ?? ($data['body'] ?? ($data['message'] ?? null));
}

// Validate
if (!$from || !$message_text) {
    bot_log("Invalid webhook data — no from/message found");
    http_response_code(200);
    echo json_encode(array('status' => 'ok', 'note' => 'no message to process'));
    exit;
}

// Clean phone number
$from = preg_replace('/[^0-9+]/', '', $from);
$message_text = trim($message_text);

bot_log("FROM: {$from} | MSG: {$message_text}");

// Check daily budget
if (!check_daily_limit()) {
    bot_log("DAILY LIMIT REACHED — sending fallback");
    send_whatsapp($from, "Thank you for contacting M.T.T.I! 🎓\n\nOur team will respond to you shortly. In the meantime:\n\n📍 Visit us: Sagaas Center, Eldoret\n📞 Call: " . ADMIN_PHONE . "\n🕐 Mon-Fri 8AM-5PM, Sat 9AM-1PM");
    http_response_code(200);
    exit;
}

// Generate AI response
$reply = ask_claude($from, $message_text);

// Save conversation
save_conversation($from, $message_text, $reply);

// Detect and save lead info
$lead_info = detect_lead_info($message_text, $reply);
if ($lead_info['course'] || $lead_info['name']) {
    $lead = save_lead($from, $lead_info['name'], $lead_info['course'], $message_text);
    
    // Notify admin of new lead with course interest
    if ($lead_info['course']) {
        notify_admin($lead);
    }
}

// Send reply
send_whatsapp($from, $reply);

// Return 200 to acknowledge webhook
http_response_code(200);
echo json_encode(array('status' => 'ok'));
