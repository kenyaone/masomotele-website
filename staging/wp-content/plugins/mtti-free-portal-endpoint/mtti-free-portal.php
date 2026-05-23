<?php
/**
 * Plugin Name: MTTI Free Portal Endpoint
 * Description: REST API endpoint and admin page for Masomotele Free Learning Portal
 * Version: 1.0
 * Author: M.T.T.I Eldoret
 */

if (!defined('ABSPATH')) exit;

// ---- 1. CREATE TABLE ----
function mtti_free_create_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'free_learners';
    $charset = $wpdb->get_charset_collate();
    // Use dbDelta to handle both create and alter (adds new columns to existing table)
    $sql = "CREATE TABLE $table (
        id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        full_name        VARCHAR(200)    NOT NULL,
        phone            VARCHAR(30)     NOT NULL,
        school           VARCHAR(200)    NOT NULL,
        school_verified  TINYINT(1)      DEFAULT 0,
        county           VARCHAR(100)    NOT NULL,
        class            VARCHAR(50)     NOT NULL,
        consent          TINYINT(1)      DEFAULT 0,
        ip_address       VARCHAR(45)     DEFAULT '',
        registered       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY phone (phone),
        KEY county (county),
        KEY class (class),
        KEY school_verified (school_verified)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('init', 'mtti_free_create_table');

// ---- 2. REST ENDPOINT — Register/Update ----
add_action('rest_api_init', function () {
    register_rest_route('mtti/v1', '/free-learner', [
        'methods'             => 'POST',
        'callback'            => 'mtti_free_save_learner',
        'permission_callback' => '__return_true',
    ]);
    // Sign In endpoint — lookup by phone
    register_rest_route('mtti/v1', '/free-learner-signin', [
        'methods'             => 'GET',
        'callback'            => 'mtti_free_signin',
        'permission_callback' => '__return_true',
    ]);
});

function mtti_free_signin(WP_REST_Request $request) {
    global $wpdb;
    $table = $wpdb->prefix . 'free_learners';
    $phone = sanitize_text_field($request->get_param('phone') ?? '');

    if (empty($phone)) {
        return new WP_REST_Response(['success'=>false,'message'=>'Phone required'], 400);
    }

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT full_name, phone, school, county, class FROM $table WHERE phone = %s LIMIT 1",
        $phone
    ));

    if (!$row) {
        return new WP_REST_Response(['success'=>false,'message'=>'No account found for this number'], 404);
    }

    return new WP_REST_Response([
        'success' => true,
        'learner' => [
            'name'   => $row->full_name,
            'phone'  => $row->phone,
            'school' => $row->school,
            'county' => $row->county,
            'class'  => $row->class,
        ]
    ], 200);
}

function mtti_free_save_learner(WP_REST_Request $request) {
    global $wpdb;
    $table           = $wpdb->prefix . 'free_learners';
    $name            = sanitize_text_field($request->get_param('name')             ?? '');
    $phone           = sanitize_text_field($request->get_param('phone')            ?? '');
    $school          = sanitize_text_field($request->get_param('school')           ?? '');
    $county          = sanitize_text_field($request->get_param('county')           ?? '');
    $class           = sanitize_text_field($request->get_param('class')            ?? '');
    $school_verified = $request->get_param('school_verified') ? 1 : 0;
    $consent         = $request->get_param('consent')         ? 1 : 0;

    if (empty($name) || empty($phone) || empty($school) || empty($county) || empty($class)) {
        return new WP_REST_Response(['success' => false, 'message' => 'All fields required'], 400);
    }

    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE phone = %s LIMIT 1", $phone));
    if ($existing) {
        $wpdb->update($table, [
            'full_name'       => $name,
            'school'          => $school,
            'school_verified' => $school_verified,
            'county'          => $county,
            'class'           => $class,
            'consent'         => $consent,
        ], ['id' => $existing]);
        return new WP_REST_Response(['success'=>true,'message'=>'Welcome back!','learner_id'=>$existing,'returning'=>true], 200);
    }

    $inserted = $wpdb->insert($table, [
        'full_name'       => $name,
        'phone'           => $phone,
        'school'          => $school,
        'school_verified' => $school_verified,
        'county'          => $county,
        'class'           => $class,
        'consent'         => $consent,
        'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '',
        'registered'      => current_time('mysql'),
    ]);

    if ($inserted === false) {
        return new WP_REST_Response(['success'=>false,'message'=>'DB error: '.$wpdb->last_error], 500);
    }
    return new WP_REST_Response(['success'=>true,'message'=>'Learner registered!','learner_id'=>$wpdb->insert_id,'returning'=>false], 201);
}

// ---- 3. ADMIN MENU ----
add_action('admin_menu', function () {
    add_menu_page(
        'Free Portal Learners',
        '🌐 Free Portal',
        'manage_options',
        'mtti-free-learners',
        'mtti_free_learners_page',
        'dashicons-welcome-learn-more',
        26
    );
});

function mtti_free_learners_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'free_learners';

    // CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY registered DESC", ARRAY_A);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="free-learners-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Name','Phone','School','County','Class','Registered']);
        foreach ($rows as $r) fputcsv($out, [$r['id'],$r['full_name'],$r['phone'],$r['school'],$r['county'],$r['class'],$r['registered']]);
        fclose($out); exit;
    }

    $county_f = sanitize_text_field($_GET['county'] ?? '');
    $class_f  = sanitize_text_field($_GET['class']  ?? '');
    $search   = sanitize_text_field($_GET['s']      ?? '');
    $where = 'WHERE 1=1'; $args = [];
    if ($county_f) { $where .= ' AND county = %s'; $args[] = $county_f; }
    if ($class_f)  { $where .= ' AND class = %s';  $args[] = $class_f; }
    if ($search)   { $where .= ' AND (full_name LIKE %s OR school LIKE %s OR phone LIKE %s)'; $args = array_merge($args, ["%$search%","%$search%","%$search%"]); }
    $query    = $args ? $wpdb->prepare("SELECT * FROM $table $where ORDER BY registered DESC", $args) : "SELECT * FROM $table ORDER BY registered DESC";
    $learners = $wpdb->get_results($query);
    $total    = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $counties = $wpdb->get_col("SELECT DISTINCT county FROM $table ORDER BY county");
    $classes  = $wpdb->get_col("SELECT DISTINCT class FROM $table ORDER BY class");
    $export_url = admin_url('admin.php?page=mtti-free-learners&export=csv');
    ?>
    <div class="wrap">
      <h1>🌐 Masomotele Free Portal — Learners
        <a href="<?php echo $export_url; ?>" class="page-title-action">⬇ Export CSV</a>
        <button class="page-title-action" onclick="document.getElementById('test-panel').style.display=document.getElementById('test-panel').style.display==='none'?'block':'none'">🧪 Test Endpoint</button>
      </h1>

      <!-- TEST PANEL -->
      <div id="test-panel" style="display:none;background:white;border:1px solid #ddd;border-radius:8px;padding:24px;margin:16px 0;max-width:680px">
        <h3 style="margin-bottom:14px;color:#0a5e2a">🧪 Test Free Learner Endpoint</h3>
        <p style="color:#555;font-size:.9rem;margin-bottom:14px">Sends a test POST to <code><?php echo esc_url(rest_url('mtti/v1/free-learner')); ?></code></p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
          <div><label style="display:block;font-size:.75rem;font-weight:700;color:#0a5e2a;text-transform:uppercase;margin-bottom:3px">Name</label><input type="text" id="t_name" value="Test Student" style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:6px"></div>
          <div><label style="display:block;font-size:.75rem;font-weight:700;color:#0a5e2a;text-transform:uppercase;margin-bottom:3px">Phone</label><input type="text" id="t_phone" value="0712464936" style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:6px"></div>
          <div><label style="display:block;font-size:.75rem;font-weight:700;color:#0a5e2a;text-transform:uppercase;margin-bottom:3px">School</label><input type="text" id="t_school" value="MTTI Test School" style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:6px"></div>
          <div><label style="display:block;font-size:.75rem;font-weight:700;color:#0a5e2a;text-transform:uppercase;margin-bottom:3px">County</label><input type="text" id="t_county" value="Uasin Gishu" style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:6px"></div>
        </div>
        <button onclick="runTest()" style="background:#0a5e2a;color:white;border:none;padding:9px 22px;border-radius:6px;font-weight:700;cursor:pointer">▶ Run Test</button>
        <button onclick="document.getElementById('test-panel').style.display='none'" style="background:#eee;border:none;padding:9px 16px;border-radius:6px;cursor:pointer;margin-left:8px">✕ Close</button>
        <div id="test-result" style="display:none;margin-top:16px;border-radius:8px;overflow:hidden">
          <div id="test-status" style="padding:9px 14px;font-weight:700;font-size:.88rem"></div>
          <pre id="test-json" style="margin:0;padding:12px 14px;background:#1a1a2e;color:#a8ff78;font-size:.8rem;overflow-x:auto"></pre>
          <div id="test-advice" style="padding:9px 14px;font-size:.82rem;color:#555;background:#fafafa;border-top:1px solid #eee"></div>
        </div>
        <script>
        function runTest() {
          var btn = event.target; btn.textContent='⏳ Testing...'; btn.disabled=true;
          fetch('<?php echo esc_url(rest_url("mtti/v1/free-learner")); ?>',{
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({name:document.getElementById('t_name').value,phone:document.getElementById('t_phone').value,school:document.getElementById('t_school').value,county:document.getElementById('t_county').value,class:'Form 4'})
          }).then(r=>r.json().then(d=>({ok:r.ok,status:r.status,data:d}))).then(r=>{
            var s=document.getElementById('test-status'),j=document.getElementById('test-json'),a=document.getElementById('test-advice');
            document.getElementById('test-result').style.display='block';
            if(r.ok){s.style.background='#e8f5e9';s.style.color='#2e7d32';s.textContent='✅ HTTP '+r.status+' — '+(r.data.returning?'Returning student updated':'New learner saved!');}
            else if(r.status===404){s.style.background='#ffeaea';s.style.color='#c62828';s.textContent='❌ 404 — Plugin not active or endpoint not registered';}
            else{s.style.background='#ffeaea';s.style.color='#c62828';s.textContent='❌ HTTP '+r.status;}
            j.textContent=JSON.stringify(r.data,null,2);
            a.textContent=r.ok?'✔ Endpoint working! Portal login will save student data correctly.':'Check the JSON above for details. Make sure this plugin is Active in WP Admin → Plugins.';
          }).catch(e=>{
            document.getElementById('test-result').style.display='block';
            document.getElementById('test-status').style.cssText='background:#ffeaea;color:#c62828;padding:9px 14px;font-weight:700';
            document.getElementById('test-status').textContent='❌ Network error — '+e.message;
            document.getElementById('test-advice').textContent='Make sure WordPress REST API is enabled and permalink settings are saved.';
          }).finally(()=>{btn.textContent='▶ Run Test';btn.disabled=false;});
        }
        </script>
      </div>

      <!-- STATS -->
      <div style="display:flex;gap:14px;margin:16px 0;flex-wrap:wrap">
        <div style="background:#e8f5e9;border-left:4px solid #2e7d32;padding:12px 20px;border-radius:6px">
          <strong style="font-size:1.5rem;color:#2e7d32"><?php echo $total; ?></strong><br>
          <span style="font-size:.85rem;color:#555">Total Registered Learners</span>
        </div>
        <div style="background:#e3f2fd;border-left:4px solid #1565c0;padding:12px 20px;border-radius:6px">
          <strong style="font-size:.95rem;color:#1565c0">POST /wp-json/mtti/v1/free-learner</strong><br>
          <span style="font-size:.85rem;color:#555">API Endpoint — Active</span>
        </div>
      </div>

      <!-- FILTERS -->
      <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
        <input type="hidden" name="page" value="mtti-free-learners">
        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, school, phone..." style="padding:6px 10px;border:1px solid #ccc;border-radius:4px;min-width:200px">
        <select name="county" style="padding:6px 10px;border:1px solid #ccc;border-radius:4px">
          <option value="">All Counties</option>
          <?php foreach ($counties as $c): ?><option value="<?php echo esc_attr($c); ?>" <?php selected($county_f,$c); ?>><?php echo esc_html($c); ?></option><?php endforeach; ?>
        </select>
        <select name="class" style="padding:6px 10px;border:1px solid #ccc;border-radius:4px">
          <option value="">All Classes</option>
          <?php foreach ($classes as $cl): ?><option value="<?php echo esc_attr($cl); ?>" <?php selected($class_f,$cl); ?>><?php echo esc_html($cl); ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="button">Filter</button>
        <a href="<?php echo admin_url('admin.php?page=mtti-free-learners'); ?>" class="button">Reset</a>
      </form>

      <p style="color:#555;margin-bottom:10px">Showing <?php echo count($learners); ?> of <?php echo $total; ?> learners</p>

      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr><th>#</th><th>Full Name</th><th>Phone</th><th>School</th><th>✓</th><th>County</th><th>Class</th><th>Consent</th><th>Registered</th></tr>
        </thead>
        <tbody>
          <?php if ($learners): foreach ($learners as $l): ?>
          <tr>
            <td><?php echo $l->id; ?></td>
            <td><strong><?php echo esc_html($l->full_name); ?></strong></td>
            <td><?php echo esc_html($l->phone); ?></td>
            <td><?php echo esc_html($l->school); ?></td>
            <td><?php echo !empty($l->school_verified) ? '<span style="color:#2e7d32" title="Verified from school list">✅</span>' : '<span style="color:#e65100" title="Manually entered">⚠️</span>'; ?></td>
            <td><?php echo esc_html($l->county); ?></td>
            <td><span style="background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:10px;font-size:.8rem;font-weight:600"><?php echo esc_html($l->class); ?></span></td>
            <td><?php echo !empty($l->consent) ? '<span style="color:#2e7d32;font-size:.8rem">✅ Yes</span>' : '<span style="color:#999;font-size:.8rem">—</span>'; ?></td>
            <td><?php echo date('d M Y H:i', strtotime($l->registered)); ?></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="9" style="text-align:center;color:#999;padding:30px">No learners registered yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}
