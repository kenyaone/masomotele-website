<?php
/**
 * MASOMOTELE FREE PORTAL — WordPress Endpoint
 * ============================================
 * INSTRUCTIONS: Paste this entire block into the bottom of your
 * mtti-mis.php plugin file (before the closing ?>  if any)
 *
 * This creates:
 *   1. DB table: wpcu_free_learners
 *   2. REST endpoint: POST /wp-json/mtti/v1/free-learner
 *   3. Admin menu: Students → Free Portal Learners
 */

// ---- 1. CREATE TABLE ON PLUGIN ACTIVATION ----
// Also runs on every load if table doesn't exist (safe)
function mtti_create_free_learners_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'free_learners';
    $charset = $wpdb->get_charset_collate();

    if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        $sql = "CREATE TABLE $table (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            full_name    VARCHAR(200)    NOT NULL,
            phone        VARCHAR(30)     NOT NULL,
            school       VARCHAR(200)    NOT NULL,
            county       VARCHAR(100)    NOT NULL,
            class        VARCHAR(50)     NOT NULL,
            ip_address   VARCHAR(45)     DEFAULT '',
            registered   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY phone (phone),
            KEY county (county),
            KEY class (class)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
add_action('init', 'mtti_create_free_learners_table');


// ---- 2. REST API ENDPOINT ----
add_action('rest_api_init', function() {
    register_rest_route('mtti/v1', '/free-learner', [
        'methods'             => 'POST',
        'callback'            => 'mtti_save_free_learner',
        'permission_callback' => '__return_true', // Public endpoint — no auth needed
    ]);
});

function mtti_save_free_learner(WP_REST_Request $request) {
    global $wpdb;

    $name   = sanitize_text_field($request->get_param('name')   ?? '');
    $phone  = sanitize_text_field($request->get_param('phone')  ?? '');
    $school = sanitize_text_field($request->get_param('school') ?? '');
    $county = sanitize_text_field($request->get_param('county') ?? '');
    $class  = sanitize_text_field($request->get_param('class')  ?? '');

    // Validate
    if(empty($name) || empty($phone) || empty($school) || empty($county) || empty($class)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'All fields required'
        ], 400);
    }

    // Check for duplicate phone (same student re-visiting)
    $table    = $wpdb->prefix . 'free_learners';
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE phone = %s LIMIT 1", $phone
    ));

    if($existing) {
        // Update their details in case school/class changed
        $wpdb->update($table,
            ['full_name'=>$name,'school'=>$school,'county'=>$county,'class'=>$class],
            ['id' => $existing]
        );
        return new WP_REST_Response([
            'success'  => true,
            'message'  => 'Welcome back!',
            'learner_id' => $existing,
            'returning'  => true
        ], 200);
    }

    // New learner — insert
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $inserted = $wpdb->insert($table, [
        'full_name'  => $name,
        'phone'      => $phone,
        'school'     => $school,
        'county'     => $county,
        'class'      => $class,
        'ip_address' => $ip,
        'registered' => current_time('mysql'),
    ]);

    if($inserted === false) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'DB error: ' . $wpdb->last_error
        ], 500);
    }

    return new WP_REST_Response([
        'success'    => true,
        'message'    => 'Learner registered!',
        'learner_id' => $wpdb->insert_id,
        'returning'  => false
    ], 201);
}


// ---- 3. ADMIN PAGE — VIEW LEARNERS ----
add_action('admin_menu', function() {
    add_submenu_page(
        'mtti-students',          // Parent menu slug (your existing Students menu)
        'Free Portal Learners',
        '🌐 Free Portal',
        'manage_options',
        'mtti-free-learners',
        'mtti_free_learners_page'
    );
});

function mtti_free_learners_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'free_learners';

    // Export CSV
    if(isset($_GET['export']) && $_GET['export'] === 'csv') {
        mtti_export_learners_csv($table);
        exit;
    }

    // Filters
    $county_filter = sanitize_text_field($_GET['county'] ?? '');
    $class_filter  = sanitize_text_field($_GET['class']  ?? '');
    $search        = sanitize_text_field($_GET['s']      ?? '');

    $where = 'WHERE 1=1';
    $args  = [];
    if($county_filter) { $where .= ' AND county = %s'; $args[] = $county_filter; }
    if($class_filter)  { $where .= ' AND class = %s';  $args[] = $class_filter;  }
    if($search)        { $where .= ' AND (full_name LIKE %s OR school LIKE %s OR phone LIKE %s)';
                         $args  = array_merge($args, ["%$search%","%$search%","%$search%"]); }

    $query   = $args ? $wpdb->prepare("SELECT * FROM $table $where ORDER BY registered DESC", $args)
                     : "SELECT * FROM $table ORDER BY registered DESC";
    $learners = $wpdb->get_results($query);
    $total    = $wpdb->get_var("SELECT COUNT(*) FROM $table");

    // County and class options for filter
    $counties = $wpdb->get_col("SELECT DISTINCT county FROM $table ORDER BY county");
    $classes  = $wpdb->get_col("SELECT DISTINCT class FROM $table ORDER BY class");

    $export_url = admin_url('admin.php?page=mtti-free-learners&export=csv');
    ?>
    <div class="wrap">
      <h1>🌐 Masomotele Free Portal — Learners
        <a href="<?php echo $export_url; ?>" class="page-title-action">⬇ Export CSV</a>
        <button class="page-title-action" onclick="mtti_toggle_test()" style="cursor:pointer">🧪 Test Endpoint</button>
      </h1>

      <!-- ===== TEST ENDPOINT PANEL ===== -->
      <div id="mtti-test-panel" style="display:none;background:white;border:1px solid #ddd;border-radius:8px;padding:24px;margin:16px 0;max-width:700px">
        <h3 style="margin-bottom:16px;color:#0a5e2a">🧪 Test the Free Learner Endpoint</h3>
        <p style="color:#555;font-size:0.9rem;margin-bottom:16px">
          Sends a test POST request to <code style="background:#f0f0f0;padding:2px 6px;border-radius:4px"><?php echo esc_url(rest_url('mtti/v1/free-learner')); ?></code><br>
          A test record will be saved — you can delete it from the table below.
        </p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
          <div>
            <label style="display:block;font-size:0.78rem;font-weight:700;color:#0a5e2a;text-transform:uppercase;margin-bottom:4px">Full Name</label>
            <input type="text" id="t_name" value="Test Student" style="width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:0.9rem">
          </div>
          <div>
            <label style="display:block;font-size:0.78rem;font-weight:700;color:#0a5e2a;text-transform:uppercase;margin-bottom:4px">Phone</label>
            <input type="text" id="t_phone" value="0700000000" style="width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:0.9rem">
          </div>
          <div>
            <label style="display:block;font-size:0.78rem;font-weight:700;color:#0a5e2a;text-transform:uppercase;margin-bottom:4px">School</label>
            <input type="text" id="t_school" value="MTTI Test School" style="width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:0.9rem">
          </div>
          <div>
            <label style="display:block;font-size:0.78rem;font-weight:700;color:#0a5e2a;text-transform:uppercase;margin-bottom:4px">County</label>
            <input type="text" id="t_county" value="Uasin Gishu" style="width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:0.9rem">
          </div>
          <div>
            <label style="display:block;font-size:0.78rem;font-weight:700;color:#0a5e2a;text-transform:uppercase;margin-bottom:4px">Class</label>
            <select id="t_class" style="width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:0.9rem">
              <option value="Form 4">Form 4</option>
              <option value="Form 3">Form 3</option>
              <option value="Grade 10 CBE">Grade 10 CBE</option>
            </select>
          </div>
        </div>

        <button id="mtti-test-btn" onclick="mtti_run_test()" style="background:#0a5e2a;color:white;border:none;padding:10px 24px;border-radius:6px;font-size:0.9rem;font-weight:700;cursor:pointer">
          ▶ Run Test
        </button>
        <button onclick="mtti_toggle_test()" style="background:#ccc;color:#333;border:none;padding:10px 18px;border-radius:6px;font-size:0.9rem;font-weight:600;cursor:pointer;margin-left:8px">
          ✕ Close
        </button>

        <!-- Result box -->
        <div id="mtti-test-result" style="display:none;margin-top:18px;border-radius:8px;overflow:hidden">
          <div id="mtti-test-status" style="padding:10px 16px;font-weight:700;font-size:0.9rem"></div>
          <pre id="mtti-test-json" style="margin:0;padding:14px 16px;background:#1a1a2e;color:#a8ff78;font-size:0.82rem;overflow-x:auto;line-height:1.6"></pre>
          <div id="mtti-test-advice" style="padding:10px 16px;font-size:0.83rem;color:#555;border-top:1px solid #eee;background:#fafafa"></div>
        </div>
      </div>

      <script>
      function mtti_toggle_test() {
        var p = document.getElementById('mtti-test-panel');
        p.style.display = p.style.display === 'none' ? 'block' : 'none';
      }

      function mtti_run_test() {
        var btn = document.getElementById('mtti-test-btn');
        btn.textContent = '⏳ Testing...';
        btn.disabled = true;

        var payload = {
          name:   document.getElementById('t_name').value,
          phone:  document.getElementById('t_phone').value,
          school: document.getElementById('t_school').value,
          county: document.getElementById('t_county').value,
          class:  document.getElementById('t_class').value
        };

        var url = '<?php echo esc_url(rest_url("mtti/v1/free-learner")); ?>';

        fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        })
        .then(function(res) {
          return res.json().then(function(data) {
            return { status: res.status, ok: res.ok, data: data };
          });
        })
        .then(function(r) {
          showResult(r.ok, r.status, r.data);
        })
        .catch(function(err) {
          showResult(false, 0, { error: err.message });
        })
        .finally(function() {
          btn.textContent = '▶ Run Test';
          btn.disabled = false;
        });
      }

      function showResult(ok, status, data) {
        var res    = document.getElementById('mtti-test-result');
        var stat   = document.getElementById('mtti-test-status');
        var json   = document.getElementById('mtti-test-json');
        var advice = document.getElementById('mtti-test-advice');

        res.style.display = 'block';

        if (ok) {
          stat.style.background = '#e8f5e9';
          stat.style.color = '#2e7d32';
          if (data.returning) {
            stat.textContent = '✅ HTTP ' + status + ' — Returning student updated successfully';
            advice.textContent = '✔ Endpoint is working. This phone number already exists — record was updated. The portal login will work correctly.';
          } else {
            stat.textContent = '✅ HTTP ' + status + ' — New learner saved to database!';
            advice.textContent = '✔ Endpoint is working perfectly. The wpcu_free_learners table exists and is accepting data. Your portal login is live!';
          }
        } else if (status === 400) {
          stat.style.background = '#fff3e0';
          stat.style.color = '#e65100';
          stat.textContent = '⚠️ HTTP 400 — Validation error (fill in all test fields above)';
          advice.textContent = 'The endpoint is reachable but rejected the data. Make sure all fields are filled in the test form.';
        } else if (status === 404 || status === 0) {
          stat.style.background = '#ffeaea';
          stat.style.color = '#c62828';
          stat.textContent = '❌ Endpoint not found — PHP snippet may not be saved yet';
          advice.textContent = 'Action needed: Go to Plugins → Plugin Editor → mtti-mis.php, paste the free-learner-endpoint.php code at the bottom, and save.';
        } else if (status === 500) {
          stat.style.background = '#ffeaea';
          stat.style.color = '#c62828';
          stat.textContent = '❌ HTTP 500 — Database error';
          advice.textContent = 'Check the error message in the JSON below. The table may not have been created — try visiting any page on the site first, then re-test.';
        } else {
          stat.style.background = '#ffeaea';
          stat.style.color = '#c62828';
          stat.textContent = '❌ HTTP ' + status + ' — Unexpected error';
          advice.textContent = 'Check the JSON response below for details.';
        }

        json.textContent = JSON.stringify(data, null, 2);
        advice.style.display = 'block';
        res.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
      </script>

      <!-- STATS ROW -->
      <div style="display:flex;gap:16px;margin:16px 0;flex-wrap:wrap;align-items:center;">
        <div style="background:#e8f5e9;border-left:4px solid #2e7d32;padding:12px 20px;border-radius:6px;">
          <strong style="font-size:1.5rem;color:#2e7d32"><?php echo $total; ?></strong><br>
          <span style="font-size:0.85rem;color:#555">Total Registered Learners</span>
        </div>
        <div style="background:#e3f2fd;border-left:4px solid #1565c0;padding:12px 20px;border-radius:6px;">
          <strong style="font-size:1rem;color:#1565c0">POST /wp-json/mtti/v1/free-learner</strong><br>
          <span style="font-size:0.85rem;color:#555">API Endpoint</span>
        </div>
      </div>

      <!-- Filters -->
      <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
        <input type="hidden" name="page" value="mtti-free-learners">
        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, school, phone..." style="padding:6px 10px;border:1px solid #ccc;border-radius:4px;min-width:200px">
        <select name="county" style="padding:6px 10px;border:1px solid #ccc;border-radius:4px;">
          <option value="">All Counties</option>
          <?php foreach($counties as $c): ?>
            <option value="<?php echo esc_attr($c); ?>" <?php selected($county_filter,$c); ?>><?php echo esc_html($c); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="class" style="padding:6px 10px;border:1px solid #ccc;border-radius:4px;">
          <option value="">All Classes</option>
          <?php foreach($classes as $cl): ?>
            <option value="<?php echo esc_attr($cl); ?>" <?php selected($class_filter,$cl); ?>><?php echo esc_html($cl); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="button">Filter</button>
        <a href="<?php echo admin_url('admin.php?page=mtti-free-learners'); ?>" class="button">Reset</a>
      </form>

      <p style="color:#555;margin-bottom:10px">Showing <?php echo count($learners); ?> of <?php echo $total; ?> learners</p>

      <table class="wp-list-table widefat fixed striped" style="border-radius:6px;overflow:hidden;">
        <thead>
          <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Phone</th>
            <th>School</th>
            <th>County</th>
            <th>Class</th>
            <th>Registered</th>
          </tr>
        </thead>
        <tbody>
          <?php if($learners): foreach($learners as $i => $l): ?>
          <tr>
            <td><?php echo $l->id; ?></td>
            <td><strong><?php echo esc_html($l->full_name); ?></strong></td>
            <td><?php echo esc_html($l->phone); ?></td>
            <td><?php echo esc_html($l->school); ?></td>
            <td><?php echo esc_html($l->county); ?></td>
            <td><span style="background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:10px;font-size:0.8rem;font-weight:600"><?php echo esc_html($l->class); ?></span></td>
            <td><?php echo date('d M Y H:i', strtotime($l->registered)); ?></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="7" style="text-align:center;color:#999;padding:30px">No learners found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

function mtti_export_learners_csv($table) {
    global $wpdb;
    $learners = $wpdb->get_results("SELECT * FROM $table ORDER BY registered DESC", ARRAY_A);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="masomotele-free-learners-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Full Name','Phone','School','County','Class','Registered']);
    foreach($learners as $row) {
        fputcsv($out, [$row['id'],$row['full_name'],$row['phone'],$row['school'],$row['county'],$row['class'],$row['registered']]);
    }
    fclose($out);
}

// ---- END MASOMOTELE FREE PORTAL ENDPOINT ----
