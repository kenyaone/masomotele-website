<?php
/**
 * M.T.T.I WhatsApp Bot — Leads Dashboard
 * View and manage leads captured by the chatbot
 * 
 * URL: https://yoursite.com/mtti-bot/dashboard.php
 * PROTECT THIS WITH A PASSWORD OR .htaccess!
 */

// Simple password protection — CHANGE THIS
$PASSWORD = 'mtti2026';
session_start();

if (isset($_POST['password'])) {
    if ($_POST['password'] === $PASSWORD) {
        $_SESSION['bot_auth'] = true;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dashboard.php');
    exit;
}

if (!isset($_SESSION['bot_auth']) || !$_SESSION['bot_auth']) {
    ?>
    <!DOCTYPE html>
    <html><head><title>M.T.T.I Bot Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>body{font-family:Arial;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f0f4f8}
    .login{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);text-align:center}
    input{padding:12px;margin:8px 0;border:1px solid #ddd;border-radius:6px;font-size:16px;width:250px}
    button{padding:12px 30px;background:#1B5E7B;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer}
    </style></head><body>
    <div class="login">
        <h2>🤖 M.T.T.I Bot Dashboard</h2>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter password" required><br>
            <button type="submit">Login</button>
        </form>
    </div>
    </body></html>
    <?php
    exit;
}

// Load data
$leads_file = __DIR__ . '/data/leads.json';
$conversations_file = __DIR__ . '/data/conversations.json';
$daily_file = __DIR__ . '/data/daily_count.json';
$log_file = __DIR__ . '/data/bot.log';

$leads = file_exists($leads_file) ? json_decode(file_get_contents($leads_file), true) : array();
$conversations = file_exists($conversations_file) ? json_decode(file_get_contents($conversations_file), true) : array();
$daily = file_exists($daily_file) ? json_decode(file_get_contents($daily_file), true) : array();

// Sort leads by most recent
usort($leads, function($a, $b) {
    return strtotime($b['last_contact'] ?? $b['first_contact']) - strtotime($a['last_contact'] ?? $a['first_contact']);
});

// Stats
$total_leads = count($leads);
$today_leads = count(array_filter($leads, function($l) {
    return date('Y-m-d', strtotime($l['first_contact'])) === date('Y-m-d');
}));
$this_week = count(array_filter($leads, function($l) {
    return strtotime($l['first_contact']) > strtotime('-7 days');
}));
$today_api = $daily[date('Y-m-d')] ?? 0;
$total_conversations = count($conversations);

// Course interest breakdown
$course_counts = array();
foreach ($leads as $l) {
    $c = $l['course_interest'] ?: 'Not specified';
    $course_counts[$c] = ($course_counts[$c] ?? 0) + 1;
}
arsort($course_counts);

// Update lead status
if (isset($_POST['update_status'])) {
    $phone = $_POST['lead_phone'];
    $status = $_POST['new_status'];
    foreach ($leads as &$l) {
        if ($l['phone'] === $phone) {
            $l['status'] = $status;
            $l['status_updated'] = date('Y-m-d H:i:s');
            break;
        }
    }
    file_put_contents($leads_file, json_encode($leads, JSON_PRETTY_PRINT));
    header('Location: dashboard.php');
    exit;
}

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="mtti_leads_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('Phone', 'Name', 'Course Interest', 'Status', 'First Contact', 'Last Contact', 'Messages', 'Source'));
    foreach ($leads as $l) {
        fputcsv($out, array(
            $l['phone'], $l['name'] ?? '', $l['course_interest'] ?? '',
            $l['status'] ?? 'new', $l['first_contact'], $l['last_contact'] ?? '',
            $l['contact_count'] ?? 1, $l['source'] ?? 'whatsapp'
        ));
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>M.T.T.I Bot Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, Arial, sans-serif; background: #f0f4f8; color: #333; }
        .header { background: linear-gradient(135deg, #1B5E7B 0%, #2E86AB 100%); color: #fff; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20px; }
        .header a { color: #fff; text-decoration: none; opacity: 0.8; font-size: 13px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #1B5E7B; }
        .stat-card .label { font-size: 12px; color: #888; margin-top: 4px; }
        
        /* Course breakdown */
        .course-bar { display: flex; align-items: center; gap: 10px; padding: 6px 0; }
        .course-bar .bar { height: 20px; background: #1B5E7B; border-radius: 4px; min-width: 20px; }
        .course-bar .name { font-size: 13px; min-width: 150px; }
        .course-bar .count { font-size: 13px; font-weight: bold; }
        
        /* Leads table */
        .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px; overflow: hidden; }
        .card-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .card-header h2 { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f8f9fa; padding: 10px 12px; text-align: left; font-weight: 600; color: #555; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; }
        tr:hover td { background: #f8fbff; }
        
        /* Status badges */
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-new { background: #e3f2fd; color: #1565c0; }
        .badge-contacted { background: #fff3e0; color: #e65100; }
        .badge-enrolled { background: #e8f5e9; color: #2e7d32; }
        .badge-lost { background: #fce4ec; color: #c62828; }
        
        /* Buttons */
        .btn { padding: 6px 14px; border: none; border-radius: 5px; font-size: 12px; cursor: pointer; }
        .btn-primary { background: #1B5E7B; color: #fff; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .btn-outline { background: transparent; border: 1px solid #ddd; color: #555; }
        
        select.status-select { padding: 4px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; }
        
        @media (max-width: 768px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            table { font-size: 12px; }
            td, th { padding: 8px 6px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🤖 M.T.T.I WhatsApp Bot Dashboard</h1>
        <div>
            <a href="dashboard.php?export=1" style="margin-right: 15px;">📥 Export CSV</a>
            <a href="dashboard.php?logout=1">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="number"><?php echo $total_leads; ?></div>
                <div class="label">Total Leads</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $today_leads; ?></div>
                <div class="label">Today's Leads</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $this_week; ?></div>
                <div class="label">This Week</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $total_conversations; ?></div>
                <div class="label">Active Chats</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $today_api; ?>/<?php echo DAILY_BUDGET_LIMIT; ?></div>
                <div class="label">API Calls Today</div>
            </div>
        </div>
        
        <!-- Course Interest Breakdown -->
        <div class="card">
            <div class="card-header"><h2>📚 Course Interest</h2></div>
            <div style="padding: 15px 20px;">
                <?php 
                $max_count = max($course_counts ?: array(1));
                foreach ($course_counts as $course => $count): 
                    $pct = round(($count / $max_count) * 100);
                ?>
                <div class="course-bar">
                    <span class="name"><?php echo htmlspecialchars($course); ?></span>
                    <div class="bar" style="width: <?php echo max($pct, 5); ?>%;"></div>
                    <span class="count"><?php echo $count; ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($course_counts)): ?>
                <p style="color: #999;">No leads yet. Leads will appear here once students start messaging.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Leads Table -->
        <div class="card">
            <div class="card-header">
                <h2>👥 All Leads</h2>
                <span style="font-size: 12px; color: #888;"><?php echo $total_leads; ?> total</span>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Course Interest</th>
                            <th>Status</th>
                            <th>First Contact</th>
                            <th>Messages</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): 
                            $status = $lead['status'] ?? 'new';
                            $badge_class = 'badge-' . $status;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($lead['name'] ?: '—'); ?></strong></td>
                            <td>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $lead['phone']); ?>" target="_blank" style="color: #25D366; text-decoration: none;">
                                    <?php echo htmlspecialchars($lead['phone']); ?> 💬
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($lead['course_interest'] ?: '—'); ?></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span></td>
                            <td><?php echo date('j M, g:ia', strtotime($lead['first_contact'])); ?></td>
                            <td><?php echo $lead['contact_count'] ?? 1; ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="lead_phone" value="<?php echo htmlspecialchars($lead['phone']); ?>">
                                    <select name="new_status" class="status-select" onchange="this.form.submit()">
                                        <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>🆕 New</option>
                                        <option value="contacted" <?php echo $status === 'contacted' ? 'selected' : ''; ?>>📞 Contacted</option>
                                        <option value="enrolled" <?php echo $status === 'enrolled' ? 'selected' : ''; ?>>✅ Enrolled</option>
                                        <option value="lost" <?php echo $status === 'lost' ? 'selected' : ''; ?>>❌ Lost</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($leads)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: #999;">No leads captured yet. Leads appear here when students message your WhatsApp number.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Log -->
        <div class="card">
            <div class="card-header"><h2>📋 Recent Activity Log</h2></div>
            <div style="padding: 15px 20px; max-height: 300px; overflow-y: auto;">
                <pre style="font-size: 11px; line-height: 1.6; color: #555; white-space: pre-wrap;"><?php
                if (file_exists($log_file)) {
                    $lines = file($log_file);
                    $recent = array_slice($lines, -30);
                    echo htmlspecialchars(implode('', array_reverse($recent)));
                } else {
                    echo "No activity logged yet.";
                }
                ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
