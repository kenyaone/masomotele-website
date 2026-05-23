<?php
/**
 * Plugin Name: MTTI Admin Assistant
 * Description: AI-powered admin assistant for MTTI. Query students, send WhatsApp, generate reports, print documents.
 * Version: 1.1.0
 * Author: MTTI Eldoret
 */

if (!defined('ABSPATH')) exit;

define('MTTI_ASST_VER', '1.1.0');

/* ═══════════════════════════════════
   ACTIVATION — add custom roles
═══════════════════════════════════ */
register_activation_hook(__FILE__, 'mtti_asst_activate');
function mtti_asst_activate() {
    foreach ([
        'mtti_registrar_role'  => ['MTTI Registrar',  'mtti_registrar'],
        'mtti_accountant_role' => ['MTTI Accountant', 'mtti_accountant'],
        'mtti_lecturer_role'   => ['MTTI Lecturer',   'mtti_lecturer'],
    ] as $slug => [$label, $cap]) {
        if (!get_role($slug)) {
            add_role($slug, $label, ['read' => true, $cap => true]);
        }
    }
}

register_deactivation_hook(__FILE__, 'mtti_asst_deactivate');
function mtti_asst_deactivate() {
    remove_role('mtti_registrar_role');
    remove_role('mtti_accountant_role');
    remove_role('mtti_lecturer_role');
}

/* ═══════════════════════════════════
   HELPERS
═══════════════════════════════════ */
function mtti_asst_get_role() {
    $u = wp_get_current_user();
    if (!$u->ID) return null;
    if ($u->has_cap('manage_options'))  return 'admin';
    if ($u->has_cap('mtti_registrar'))  return 'registrar';
    if ($u->has_cap('mtti_accountant')) return 'accountant';
    if ($u->has_cap('mtti_lecturer'))   return 'lecturer';
    return null;
}

function mtti_asst_role_label($role) {
    return ['admin'=>'Admin','registrar'=>'Registrar','accountant'=>'Accountant','lecturer'=>'Lecturer'][$role] ?? 'Staff';
}

function mtti_asst_permissions($role) {
    $all = ['query_students','send_whatsapp','fee_reports','enrollment_reports','attendance_reports','print_admission','print_certificate','manage_users'];
    return match($role) {
        'admin'      => $all,
        'registrar'  => ['query_students','print_admission','print_certificate','enrollment_reports','send_whatsapp'],
        'accountant' => ['query_students','fee_reports','send_whatsapp'],
        'lecturer'   => ['query_students','attendance_reports'],
        default      => [],
    };
}

function mtti_asst_can($role, $perm) {
    return in_array($perm, mtti_asst_permissions($role));
}

/* ═══════════════════════════════════
   SMART MODEL ROUTER
   Simple queries → Groq (free/cheap)
   Complex queries → Claude
═══════════════════════════════════ */
function mtti_asst_is_complex($message) {
    $complex_keywords = ['generate','print','certificate','admission letter','report','compare','summary','send whatsapp','remind','calculate','analysis'];
    $msg = strtolower($message);
    foreach ($complex_keywords as $kw) {
        if (strpos($msg, $kw) !== false) return true;
    }
    return false;
}

function mtti_asst_call_groq($messages, $system) {
    $groq_key = get_option('mtti_groq_api_key', '');
    if (!$groq_key) return null;

    $payload = [
        'model'       => 'llama-3.3-70b-versatile',
        'max_tokens'  => 1500,
        'temperature' => 0.3,
        'messages'    => array_merge(
            [['role' => 'system', 'content' => $system]],
            $messages
        ),
    ];

    $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', [
        'timeout' => 25,
        'headers' => [
            'Authorization' => 'Bearer ' . $groq_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode($payload),
    ]);

    if (is_wp_error($response)) return null;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['choices'][0]['message']['content'] ?? null;
}

function mtti_asst_call_claude($messages, $system) {
    $claude_key = get_option('mtti_claude_api_key', '');
    if (!$claude_key) return null;

    $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'timeout' => 30,
        'headers' => [
            'x-api-key'         => $claude_key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ],
        'body' => json_encode([
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => 1500,
            'system'     => $system,
            'messages'   => $messages,
        ]),
    ]);

    if (is_wp_error($response)) return null;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['content'][0]['text'] ?? null;
}

function mtti_asst_call_ai($messages, $system, $message) {
    $use_complex = mtti_asst_is_complex($message);

    if (!$use_complex) {
        $result = mtti_asst_call_groq($messages, $system);
        if ($result) return ['text' => $result, 'model' => 'groq'];
    }

    $result = mtti_asst_call_claude($messages, $system);
    if ($result) return ['text' => $result, 'model' => 'claude'];

    $result = mtti_asst_call_groq($messages, $system);
    if ($result) return ['text' => $result, 'model' => 'groq'];

    return ['text' => null, 'model' => null];
}

/* ═══════════════════════════════════
   ADMIN MENU
═══════════════════════════════════ */
add_action('admin_menu', 'mtti_asst_menu');
function mtti_asst_menu() {
    $role = mtti_asst_get_role();
    if (!$role) return;
    add_menu_page('MTTI Assistant', 'MTTI Assistant', 'read', 'mtti-assistant', 'mtti_asst_page', 'dashicons-format-chat', 3);
    add_submenu_page('mtti-assistant', 'Chat', 'Chat', 'read', 'mtti-assistant', 'mtti_asst_page');
    if ($role === 'admin') {
        add_submenu_page('mtti-assistant', 'Staff & Settings', 'Staff & Settings', 'manage_options', 'mtti-assistant-settings', 'mtti_asst_settings_page');
    }
}

/* ═══════════════════════════════════
   AJAX: CHAT
═══════════════════════════════════ */
function mtti_asst_fetch_db_context($role, $message) {
    global $wpdb;
    $p = $wpdb->prefix;
    $msg = strtolower($message);
    $data = [];

    if (strpos($msg, 'fee') !== false || strpos($msg, 'balance') !== false || strpos($msg, 'student') !== false || strpos($msg, 'enroll') !== false) {
        $students = $wpdb->get_results("
            SELECT p.ID,
                MAX(CASE WHEN pm.meta_key='_student_name' THEN pm.meta_value END) as name,
                MAX(CASE WHEN pm.meta_key='_student_phone' THEN pm.meta_value END) as phone,
                MAX(CASE WHEN pm.meta_key='_student_course' THEN pm.meta_value END) as course,
                MAX(CASE WHEN pm.meta_key='_student_fee_total' THEN pm.meta_value END) as fee_total,
                MAX(CASE WHEN pm.meta_key='_student_fee_paid' THEN pm.meta_value END) as fee_paid,
                MAX(CASE WHEN pm.meta_key='_student_fee_balance' THEN pm.meta_value END) as fee_balance,
                MAX(CASE WHEN pm.meta_key='_student_status' THEN pm.meta_value END) as status,
                MAX(CASE WHEN pm.meta_key='_admission_number' THEN pm.meta_value END) as admission_no
            FROM {$p}posts p
            LEFT JOIN {$p}postmeta pm ON p.ID = pm.post_id
            WHERE p.post_type = 'mtti_student' AND p.post_status = 'publish'
            GROUP BY p.ID ORDER BY p.post_date DESC LIMIT 100
        ", ARRAY_A);
        $data['students'] = $students ?: [];
        $data['total'] = count($students ?: []);
    }

    if (strpos($msg, 'course') !== false || strpos($msg, 'enroll') !== false || strpos($msg, 'month') !== false) {
        $courses = $wpdb->get_results("
            SELECT pm.meta_value as course, COUNT(*) as count
            FROM {$p}posts p JOIN {$p}postmeta pm ON p.ID=pm.post_id AND pm.meta_key='_student_course'
            WHERE p.post_type='mtti_student' AND p.post_status='publish'
            GROUP BY pm.meta_value
        ", ARRAY_A);
        $data['by_course'] = $courses ?: [];
        $data['this_month'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}posts WHERE post_type='mtti_student' AND post_status='publish' AND MONTH(post_date)=MONTH(NOW()) AND YEAR(post_date)=YEAR(NOW())");
    }

    return $data;
}

add_action('wp_ajax_mtti_asst_chat', 'mtti_asst_ajax_chat');
function mtti_asst_ajax_chat() {
    check_ajax_referer('mtti_asst_nonce', 'nonce');
    $role = mtti_asst_get_role();
    if (!$role) wp_send_json_error(['msg' => 'Access denied.']);

    $message = sanitize_text_field($_POST['message'] ?? '');
    $history = json_decode(stripslashes($_POST['history'] ?? '[]'), true);
    if (!$message) wp_send_json_error(['msg' => 'Empty message.']);

    $perms     = mtti_asst_permissions($role);
    $db_data   = mtti_asst_fetch_db_context($role, $message);
    $user_name = wp_get_current_user()->display_name;
    $prefix    = $GLOBALS['wpdb']->prefix;

    $system = "You are the MTTI Admin Assistant for Masomotele Technical Training Institute, Eldoret Kenya.
You are talking to {$user_name} (role: {$role}).

PERMISSIONS: " . implode(', ', $perms) . "

DATABASE prefix: '{$prefix}'. Key tables:
- {$prefix}posts — student posts (post_type='mtti_student')
- {$prefix}postmeta — meta keys: _student_name, _student_phone, _student_course, _student_fee_total, _student_fee_paid, _student_fee_balance, _student_status, _admission_number, _enrollment_date
- {$prefix}users — WordPress users

'.json_encode($db_data).'

MTTI CONTEXT: TVETA accredited. Courses: CNA/Healthcare, ICT, German Language, Short Technical Courses. Location: Sagaas Centre 4th Floor Eldoret. WhatsApp via SendPulse.

RESPONSE RULES:
- Respond ONLY with valid JSON, no markdown, no code blocks.
- For data results: {\"type\":\"table\",\"title\":\"...\",\"columns\":[...],\"rows\":[[...]],\"summary\":\"...\"}
- For reports: {\"type\":\"report\",\"title\":\"...\",\"summary\":\"...\"}
- For WhatsApp (always confirm first): {\"type\":\"whatsapp_confirm\",\"recipients\":\"phone1,phone2\",\"message\":\"...\",\"count\":N}
- For documents: {\"type\":\"document\",\"doc_type\":\"admission_letter|certificate\",\"student_id\":\"...\",\"student_name\":\"...\"}
- For plain answers: {\"type\":\"text\",\"content\":\"...\"}
- For permission denied: {\"type\":\"text\",\"content\":\"Sorry [name], as [role] you can only: [list allowed things].\"}
- Address user by first name. Be concise and professional.";

    $messages = [];
    foreach ((array)$history as $h) {
        if (isset($h['role'], $h['content'])) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $result = mtti_asst_call_ai($messages, $system, $message);

    if (!$result['text']) {
        wp_send_json_error(['msg' => 'AI service unavailable. Check your API keys in MTTI Assistant Settings.']);
    }

    $raw = trim(preg_replace('/^```json\s*|\s*```$/s', '', $result['text']));
    $raw = trim($raw);
    $json_objects = [];
    preg_match_all('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $raw, $m);
    $parsed = null;
    foreach (array_reverse($m[0] ?? []) as $candidate) {
        $try = json_decode($candidate, true);
        if ($try && isset($try['type'])) { $parsed = $try; break; }
    }
    if (!$parsed) { $parsed = ['type'=>'text','content'=>$raw]; }

    wp_send_json_success(['reply' => $parsed, 'role' => $role, 'model' => $result['model']]);
}

/* ═══════════════════════════════════
   AJAX: SEND WHATSAPP
═══════════════════════════════════ */
add_action('wp_ajax_mtti_asst_send_whatsapp', 'mtti_asst_ajax_whatsapp');
function mtti_asst_ajax_whatsapp() {
    check_ajax_referer('mtti_asst_nonce', 'nonce');
    $role = mtti_asst_get_role();
    if (!$role || !mtti_asst_can($role, 'send_whatsapp')) {
        wp_send_json_error(['msg' => 'Permission denied.']);
    }

    $message   = sanitize_textarea_field($_POST['wa_message'] ?? '');
    $recipients = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['recipients'] ?? ''))));

    $sp_key = get_option('mtti_sendpulse_token', get_option('mtti_sendpulse_key', ''));
    $sent = 0;
    foreach ($recipients as $phone) {
        $r = wp_remote_post('https://api.sendpulse.com/whatsapp/contacts/sendByPhones', [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer '.$sp_key, 'Content-Type' => 'application/json'],
            'body'    => json_encode(['phone' => $phone, 'message' => ['type' => 'text', 'text' => ['body' => $message]]]),
        ]);
        if (!is_wp_error($r)) $sent++;
    }

    wp_send_json_success(['msg' => "Sent to {$sent} of ".count($recipients)." recipients.", 'sent' => $sent]);
}

/* ═══════════════════════════════════
   CHAT PAGE
═══════════════════════════════════ */
function mtti_asst_page() {
    $role  = mtti_asst_get_role();
    $nonce = wp_create_nonce('mtti_asst_nonce');
    $label = mtti_asst_role_label($role);
    $name  = wp_get_current_user()->display_name;
    $firstname = explode(' ', $name)[0];
    $groq_ok   = get_option('mtti_groq_api_key')   ? true : false;
    $claude_ok = get_option('mtti_claude_api_key')  ? true : false;
    ?>
    <style>
    #mtti-asst{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;max-width:920px;margin:20px auto;}
    .ah{background:#0a4a1a;color:#fff;padding:18px 24px;border-radius:12px 12px 0 0;display:flex;align-items:center;gap:14px;}
    .ah h1{font-size:18px;margin:0;font-weight:600;}
    .badge{background:#FF9700;color:#0a4a1a;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;margin-left:auto;}
    .model-tag{font-size:10px;padding:2px 7px;border-radius:10px;font-weight:600;margin-left:6px;}
    .model-groq{background:#4CAF50;color:#fff;}
    .model-claude{background:#7B68EE;color:#fff;}
    .ab{background:#f7f7f7;border:1px solid #ddd;border-top:none;border-radius:0 0 12px 12px;}
    .qbtns{display:flex;gap:8px;flex-wrap:wrap;padding:12px 18px 6px;background:#fff;border-bottom:1px solid #eee;}
    .qbtn{background:#f0f8ea;border:1px solid #3a7a2a;color:#0a4a1a;padding:6px 13px;border-radius:20px;font-size:12px;cursor:pointer;}
    .qbtn:hover{background:#0a4a1a;color:#fff;}
    #msgs{height:450px;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:12px;}
    .msg{max-width:82%;padding:12px 16px;border-radius:12px;font-size:14px;line-height:1.6;}
    .mu{background:#0a4a1a;color:#fff;align-self:flex-end;border-radius:12px 12px 2px 12px;}
    .ma{background:#fff;color:#222;align-self:flex-start;border:1px solid #e5e5e5;border-radius:12px 12px 12px 2px;}
    .ma table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13px;}
    .ma th{background:#0a4a1a;color:#fff;padding:7px 10px;text-align:left;font-weight:600;}
    .ma td{padding:6px 10px;border-bottom:1px solid #f0f0f0;}
    .ma tr:last-child td{border-bottom:none;}
    .ma tr:hover td{background:#f9f9f9;}
    .confirm-box{background:#fff8e1;border:1px solid #FF9700;border-radius:8px;padding:14px;margin-top:10px;}
    .confirm-box p{font-size:13px;margin:0 0 8px;color:#444;}
    .wa-msg{background:#f5f5f5;border-radius:6px;padding:8px 12px;font-size:13px;white-space:pre-wrap;margin-bottom:10px;}
    .btn-ok{background:#0a4a1a;color:#fff;border:none;padding:8px 22px;border-radius:6px;font-weight:700;cursor:pointer;font-size:13px;}
    .btn-no{background:#eee;color:#555;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:13px;margin-left:8px;}
    .inp-area{display:flex;gap:10px;padding:14px 18px;background:#fff;border-top:1px solid #eee;border-radius:0 0 12px 12px;}
    #ainp{flex:1;padding:10px 14px;border:1px solid #ccc;border-radius:8px;font-size:14px;resize:none;outline:none;}
    #ainp:focus{border-color:#0a4a1a;}
    #asend{background:#0a4a1a;color:#fff;border:none;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;}
    #asend:hover{background:#0d5c21;}
    #asend:disabled{background:#aaa;cursor:not-allowed;}
    .typing{color:#888;font-size:13px;font-style:italic;}
    .status-bar{display:flex;gap:10px;padding:6px 18px;background:#fff;border-bottom:1px solid #eee;font-size:11px;color:#888;}
    .dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:4px;}
    .dot-on{background:#4CAF50;}
    .dot-off{background:#ccc;}
    </style>

    <div id="mtti-asst">
        <div class="ah">
            <span style="font-size:26px">🤖</span>
            <div>
                <h1>MTTI Admin Assistant</h1>
                <div style="font-size:12px;color:#a8d8b0;margin-top:2px">Hi <strong><?=esc_html($firstname)?></strong> · <?=esc_html($label)?></div>
            </div>
            <span class="badge"><?=esc_html($label)?></span>
        </div>
        <div class="ab">
            <div class="status-bar">
                <span><span class="dot <?=$groq_ok?'dot-on':'dot-off'?>"></span>Groq <?=$groq_ok?'✓ (free)':'not set'?></span>
                <span><span class="dot <?=$claude_ok?'dot-on':'dot-off'?>"></span>Claude <?=$claude_ok?'✓':'not set'?></span>
                <span style="margin-left:auto">Simple queries → Groq · Complex → Claude</span>
            </div>
            <div class="qbtns">
                <?php
                $qs = [];
                if (mtti_asst_can($role,'query_students'))     $qs[] = 'Students with fee balance';
                if (mtti_asst_can($role,'enrollment_reports')) $qs[] = 'Enrollment this month';
                if (mtti_asst_can($role,'fee_reports'))        $qs[] = 'Fee collection summary';
                if (mtti_asst_can($role,'send_whatsapp'))      $qs[] = 'Send fee reminder to CNA students';
                if (mtti_asst_can($role,'attendance_reports')) $qs[] = 'Attendance report';
                if (mtti_asst_can($role,'print_admission'))    $qs[] = 'Print admission letter';
                if (mtti_asst_can($role,'print_certificate'))  $qs[] = 'Generate certificate';
                foreach ($qs as $q):
                ?>
                <button class="qbtn" onclick="qs('<?=esc_js($q)?>')"><?=esc_html($q)?></button>
                <?php endforeach; ?>
            </div>
            <div id="msgs">
                <div class="msg ma">
                    Hi <strong><?=esc_html($firstname)?></strong>! I'm your MTTI Assistant. As <strong><?=esc_html($label)?></strong> I can help with: <em><?=esc_html(implode(', ', mtti_asst_permissions($role)))?></em>.<br><br>
                    Just type what you need. I use <strong>Groq (free)</strong> for quick queries and <strong>Claude</strong> for complex tasks.
                </div>
            </div>
            <div class="inp-area">
                <textarea id="ainp" rows="2" placeholder="e.g. Show all CNA students with balance above 3000"></textarea>
                <button id="asend" onclick="send()">Send</button>
            </div>
        </div>
    </div>

    <script>
    var nonce='<?=esc_js($nonce)?>',ajax='<?=esc_js(admin_url('admin-ajax.php'))?>',hist=[];

    document.getElementById('ainp').addEventListener('keydown',function(e){
        if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();}
    });

    function qs(q){document.getElementById('ainp').value=q;send();}

    function send(){
        var inp=document.getElementById('ainp'),msg=inp.value.trim();
        if(!msg)return;
        inp.value='';
        append('u',msg);
        hist.push({role:'user',content:msg});
        var btn=document.getElementById('asend');
        btn.disabled=true;btn.textContent='...';
        addTyping();
        var fd=new FormData();
        fd.append('action','mtti_asst_chat');
        fd.append('nonce',nonce);
        fd.append('message',msg);
        fd.append('history',JSON.stringify(hist.slice(-8)));
        fetch(ajax,{method:'POST',body:fd})
        .then(r=>r.json())
        .then(res=>{
            rmTyping();btn.disabled=false;btn.textContent='Send';
            if(!res.success){appendHTML('ma','<span style="color:#c00">'+esc(res.data.msg||'Error')+'</span>');return;}
            hist.push({role:'assistant',content:JSON.stringify(res.data.reply)});
            render(res.data.reply, res.data.model);
        })
        .catch(()=>{
            rmTyping();btn.disabled=false;btn.textContent='Send';
            append('ma','Network error. Please try again.');
        });
    }

    function render(r,model){
        var tag=model?'<span class="model-tag model-'+model+'">'+model+'</span>':'';
        if(r.type==='text'){
            appendHTML('ma',tag+' '+esc(r.content||''));
        } else if(r.type==='table'){
            var h='<strong>'+esc(r.title||'Results')+'</strong>'+tag;
            h+='<table><thead><tr>';
            (r.columns||[]).forEach(c=>h+='<th>'+esc(c)+'</th>');
            h+='</tr></thead><tbody>';
            (r.rows||[]).forEach(row=>{
                h+='<tr>';
                (Array.isArray(row)?row:[row]).forEach(cell=>h+='<td>'+esc(String(cell??''))+'</td>');
                h+='</tr>';
            });
            h+='</tbody></table>';
            if(r.summary) h+='<div style="font-size:12px;color:#888;margin-top:6px">'+esc(r.summary)+'</div>';
            appendHTML('ma',h);
        } else if(r.type==='report'){
            appendHTML('ma','<strong>'+esc(r.title||'Report')+'</strong>'+tag+'<br><span style="font-size:13px;color:#555">'+esc(r.summary||'')+'</span>');
        } else if(r.type==='whatsapp_confirm'){
            var id='wa'+Date.now();
            var h='<strong>WhatsApp Ready to Send</strong>'+tag
                +'<div class="confirm-box">'
                +'<p><strong>Recipients:</strong> '+(r.count||'?')+' students</p>'
                +'<div class="wa-msg">'+esc(r.message||'')+'</div>'
                +'<button class="btn-ok" onclick="doWA(\''+id+'\',\''+r.recipients.replace(/'/g,"\\'")+ '\',\''+esc(r.message||'').replace(/'/g,"\\'")+ '\')">Send Now</button>'
                +'<button class="btn-no" onclick="noWA(\''+id+'\')">Cancel</button>'
                +'</div>';
            appendHTML('ma',h,id);
        } else if(r.type==='document'){
            appendHTML('ma',tag+'<p style="margin:0">Document request: <strong>'+esc(r.doc_type||'')+'</strong> for <strong>'+esc(r.student_name||r.student_id||'')+'</strong>. Open the student record to generate.</p>');
        } else {
            append('ma',JSON.stringify(r));
        }
    }

    function doWA(id,rec,msg){
        var box=document.getElementById(id);
        if(box) box.innerHTML='<p style="color:#888;font-size:13px">Sending...</p>';
        var fd=new FormData();
        fd.append('action','mtti_asst_send_whatsapp');
        fd.append('nonce',nonce);
        fd.append('recipients',rec);
        fd.append('wa_message',msg);
        fetch(ajax,{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
            if(box) box.innerHTML='<p style="color:#1a7a40;font-weight:600">'+esc(res.data?.msg||'Done!')+'</p>';
        });
    }
    function noWA(id){var b=document.getElementById(id);if(b)b.innerHTML='<p style="color:#888;font-size:12px">Cancelled.</p>';}

    function append(cls,txt){
        var d=document.createElement('div');
        d.className='msg m'+cls;d.textContent=txt;
        document.getElementById('msgs').appendChild(d);scroll();
    }
    function appendHTML(cls,html,id){
        var d=document.createElement('div');
        d.className='msg m'+cls;
        if(id)d.id=id;
        d.innerHTML=html;
        document.getElementById('msgs').appendChild(d);scroll();
    }
    function addTyping(){
        var d=document.createElement('div');
        d.className='msg typing';d.id='typing';d.textContent='Thinking...';
        document.getElementById('msgs').appendChild(d);scroll();
    }
    function rmTyping(){var t=document.getElementById('typing');if(t)t.remove();}
    function scroll(){var m=document.getElementById('msgs');m.scrollTop=m.scrollHeight;}
    function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML;}
    </script>
    <?php
}

/* ═══════════════════════════════════
   SETTINGS PAGE
═══════════════════════════════════ */
function mtti_asst_settings_page() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    if (isset($_POST['_mtti_asst_settings_nonce']) && wp_verify_nonce($_POST['_mtti_asst_settings_nonce'], 'mtti_asst_settings')) {
        $user_id  = intval($_POST['user_id'] ?? 0);
        $new_role = sanitize_text_field($_POST['new_role'] ?? '');
        if ($user_id && $new_role) {
            $u = new WP_User($user_id);
            $u->set_role($new_role);
            echo '<div class="notice notice-success is-dismissible"><p>Role updated successfully.</p></div>';
        }
    }

    $users     = get_users(['number' => 50]);
    $groq_key  = get_option('mtti_groq_api_key', '');
    $claude_key= get_option('mtti_claude_api_key', '');
    $sp_key    = get_option('mtti_sendpulse_token', get_option('mtti_sendpulse_key', ''));
    ?>
    <div class="wrap">
        <h1>MTTI Assistant — Settings</h1>
        <h2>API Status</h2>
        <table class="widefat" style="max-width:500px;margin-bottom:20px">
            <tr><th>Service</th><th>Status</th><th>Used for</th></tr>
            <tr>
                <td>Groq</td>
                <td><?=$groq_key?'<span style="color:green">✓ Configured</span>':'<span style="color:#c00">✗ Not set</span>'?></td>
                <td>Simple queries (free)</td>
            </tr>
            <tr>
                <td>Claude (Anthropic)</td>
                <td><?=$claude_key?'<span style="color:green">✓ Configured</span>':'<span style="color:#c00">✗ Not set</span>'?></td>
                <td>Complex tasks</td>
            </tr>
            <tr>
                <td>SendPulse</td>
                <td><?=$sp_key?'<span style="color:green">✓ Configured</span>':'<span style="color:#c00">✗ Not set</span>'?></td>
                <td>WhatsApp messages</td>
            </tr>
        </table>
        <p style="color:#555;font-size:13px">API keys are managed in your main MTTI MIS plugin settings. No need to re-enter them here.</p>

        <h2 style="margin-top:24px">Assign Staff Roles</h2>
        <form method="post" style="max-width:700px">
            <?php wp_nonce_field('mtti_asst_settings','_mtti_asst_settings_nonce'); ?>
            <table class="widefat">
                <thead><tr><th>User</th><th>Email</th><th>Current Role</th><th>Change Role</th></tr></thead>
                <tbody>
                <?php foreach($users as $u):
                    $cur = implode(', ', $u->roles);
                ?>
                <tr>
                    <td><strong><?=esc_html($u->display_name)?></strong></td>
                    <td style="color:#666;font-size:12px"><?=esc_html($u->user_email)?></td>
                    <td><?=esc_html($cur)?></td>
                    <td>
                        <select onchange="document.getElementById('uid').value=<?=(int)$u->ID?>;this.form.new_role.value=this.value;this.form.submit()">
                            <option value="">— no change —</option>
                            <option value="administrator">Admin</option>
                            <option value="mtti_registrar_role">Registrar</option>
                            <option value="mtti_accountant_role">Accountant</option>
                            <option value="mtti_lecturer_role">Lecturer</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <input type="hidden" name="user_id" id="uid" value="">
            <input type="hidden" name="new_role" value="">
        </form>
    </div>
    <?php
}
