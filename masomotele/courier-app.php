<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();
$auth = new Auth();

// API endpoints stay the same as datapost.php
// This file is the PWA interface for admin/teacher courier

// Check role - only admin/teacher
if ($auth->isLoggedIn() && !in_array($auth->getRole(), ['admin', 'teacher'])) {
    header('Location: ' . SITE_URL . '/dashboard.php');
    exit;
}

// Serve PWA manifest
if (isset($_GET['manifest'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'name' => 'M.T.T.I DataPost Courier',
        'short_name' => 'Courier',
        'description' => 'Sync LMS data to cloud automatically',
        'start_url' => SITE_URL . '/courier-app.php',
        'display' => 'standalone',
        'background_color' => '#1a5276',
        'theme_color' => '#1a5276',
        'orientation' => 'portrait',
        'icons' => [
            ['src' => SITE_URL . '/courier-icon.php?s=192', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => SITE_URL . '/courier-icon.php?s=512', 'sizes' => '512x512', 'type' => 'image/png']
        ]
    ]);
    exit;
}

// Serve SW
if (isset($_GET['sw'])) {
    header('Content-Type: application/javascript');
    echo "
const CACHE = 'courier-v1';
self.addEventListener('install', e => self.skipWaiting());
self.addEventListener('activate', e => self.clients.claim());
self.addEventListener('fetch', e => {
    if (e.request.url.includes('api=')) return;
    e.respondWith(fetch(e.request).catch(() => caches.match(e.request)));
});
";
    exit;
}

// Serve icon (simple SVG->PNG placeholder)
if (isset($_GET['icon'])) {
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="192" height="192" viewBox="0 0 192 192"><rect width="192" height="192" rx="30" fill="#1a5276"/><text x="96" y="110" text-anchor="middle" font-size="80" fill="white">📱</text><text x="96" y="160" text-anchor="middle" font-size="24" fill="#3498db" font-family="Arial" font-weight="bold">SYNC</text></svg>';
    exit;
}

$pageTitle = 'Courier App - ' . SITE_NAME;

// Get settings
$settings = [];
$rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'courier_%'");
foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];

$sheetsUrl = $settings['courier_sheets_webhook'] ?? '';
$emailDest = $settings['courier_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1a5276">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?=SITE_URL?>/courier-app.php?manifest=1">
    <title>M.T.T.I Courier</title>
    <link href="<?= SITE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #0d1b2a; color: white; min-height: 100vh; font-family: -apple-system, system-ui, sans-serif; }
        .status-card { background: rgba(255,255,255,0.1); border-radius: 16px; padding: 20px; margin-bottom: 12px; backdrop-filter: blur(10px); }
        .big-num { font-size: 2.5rem; font-weight: 700; line-height: 1; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.5; } }
        .sync-ring { width: 200px; height: 200px; border-radius: 50%; border: 6px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; position: relative; }
        .sync-ring.active { border-color: #27ae60; box-shadow: 0 0 30px rgba(39,174,96,0.3); }
        .sync-ring.syncing { animation: spin 2s linear infinite; border-color: #3498db; border-top-color: transparent; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .sync-inner { text-align: center; }
        .log-entry { font-size: 0.75rem; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .net-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
        .net-lan { background: #f39c12; }
        .net-internet { background: #27ae60; }
        .net-offline { background: #e74c3c; }
    </style>
</head>
<body>
    <div class="container" style="max-width:480px; padding-top:20px;">
        <!-- Header -->
        <div class="text-center mb-3">
            <h5 class="mb-0"><i class="bi bi-broadcast me-2"></i>M.T.T.I Courier</h5>
            <small class="text-muted">Auto-sync LMS data to cloud</small>
        </div>

        <!-- Last Sync + Actions -->
        <div class="status-card d-flex justify-content-between align-items-center mb-2">
            <div>
                <small class="text-muted">Last synced</small>
                <div id="lastSyncLabel" class="fw-bold small">Never</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?=SITE_URL?>/datapost.php?api=download_csv" class="btn btn-sm btn-outline-light" title="Download CSV">
                    <i class="bi bi-download"></i>
                </a>
                <button class="btn btn-sm btn-outline-light" onclick="showQR()" title="Show QR">
                    <i class="bi bi-qr-code"></i>
                </button>
            </div>
        </div>

        <!-- QR Code Modal -->
        <div id="qrModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:9999;display:none;align-items:center;justify-content:center;" onclick="this.style.display='none'">
            <div style="background:#fff;padding:20px;border-radius:12px;text-align:center;">
                <div style="font-size:12px;color:#333;margin-bottom:8px;font-weight:bold">Scan to open LMS on this network</div>
                <canvas id="qrCanvas"></canvas>
                <div style="font-size:11px;color:#666;margin-top:8px"><?=str_replace('localhost','192.168.0.10',SITE_URL)?>/portal/</div>
                <div style="font-size:11px;color:#999;margin-top:4px">Tap anywhere to close</div>
            </div>
        </div>

        <!-- Network Status -->
        <div class="status-card d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted">Network</small>
                <div id="netLabel" class="fw-bold">Detecting...</div>
            </div>
            <div>
                <span id="netDot" class="net-dot net-offline"></span>
                <span id="netType" class="ms-1 small">Unknown</span>
            </div>
        </div>

        <!-- Sync Ring -->
        <div id="syncRing" class="sync-ring">
            <div class="sync-inner">
                <div id="syncIcon" style="font-size:3rem">📡</div>
                <div id="syncLabel" class="small mt-1">Waiting</div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="status-card text-center">
                    <div id="carriedNum" class="big-num text-warning">0</div>
                    <small>Carried</small>
                </div>
            </div>
            <div class="col-4">
                <div class="status-card text-center">
                    <div id="postedNum" class="big-num text-success">0</div>
                    <small>Posted</small>
                </div>
            </div>
            <div class="col-4">
                <div class="status-card text-center">
                    <div id="cycleNum" class="big-num text-info">0</div>
                    <small>Cycles</small>
                </div>
            </div>
        </div>

        <!-- Auto Mode Toggle -->
        <div class="status-card d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold"><i class="bi bi-lightning me-2"></i>Auto Sync</div>
                <small class="text-muted">Picks up on LAN, posts on internet</small>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="autoToggle" checked style="width:50px;height:26px;">
            </div>
        </div>

        <!-- Manual Buttons -->
        <div class="row g-2 mb-3">
            <div class="col-6">
                <button class="btn btn-outline-warning w-100" onclick="manualPickup()" id="pickupBtn">
                    <i class="bi bi-download me-1"></i>Pick Up
                </button>
            </div>
            <div class="col-6">
                <button class="btn btn-outline-success w-100" onclick="manualPost()" id="postBtn">
                    <i class="bi bi-cloud-upload me-1"></i>Post Now
                </button>
            </div>
        </div>

        <!-- Google Sheets Config -->
        <div class="status-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="fw-bold"><i class="bi bi-table me-2"></i>Google Sheets</small>
                <span id="sheetsStatus" class="badge bg-secondary">Not set</span>
            </div>
            <input type="url" id="sheetsInput" class="form-control form-control-sm bg-dark text-white border-secondary" 
                placeholder="Paste webhook URL here" value="<?=htmlspecialchars($sheetsUrl)?>">
            <div class="mt-1"><small class="text-muted">Get URL from Apps Script → Deploy → Web app</small></div>
        </div>

        <!-- Activity Log -->
        <div class="status-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="fw-bold"><i class="bi bi-list me-2"></i>Activity Log</small>
                <button class="btn btn-sm btn-outline-secondary" onclick="clearLog()" style="font-size:0.7rem">Clear</button>
            </div>
            <div id="logContainer" style="max-height:200px;overflow-y:auto">
                <div class="log-entry text-muted">App started. Waiting for network...</div>
            </div>
        </div>

        <!-- Install Banner -->
        <div id="installBanner" class="status-card text-center" style="display:none; background:rgba(39,174,96,0.2);">
            <p class="mb-2"><i class="bi bi-phone me-1"></i>Install this app on your phone</p>
            <button class="btn btn-success btn-sm" onclick="installApp()"><i class="bi bi-download me-1"></i>Add to Home Screen</button>
        </div>

        <div class="text-center mt-3 mb-4">
            <a href="<?=SITE_URL?>/datapost.php" class="text-muted small">← Back to DataPost Dashboard</a>
        </div>
    </div>

<script>
// CONFIG
var SERVER = '<?=SITE_URL?>';
var STORE_KEY = 'mtti_courier_data';
var LOG_KEY = 'mtti_courier_log';
var SHEETS_KEY = 'mtti_courier_sheets';
var CHECK_INTERVAL = 10000; // 10 seconds
var lastNetwork = null;
var syncing = false;
var totalPosted = parseInt(localStorage.getItem('mtti_posted') || '0');
var totalCycles = parseInt(localStorage.getItem('mtti_cycles') || '0');

// State
var carried = JSON.parse(localStorage.getItem(STORE_KEY) || '{"entries":[],"ids":[],"pickup_time":null}');

// Init
updateStats();
checkSheetsConfig();
startAutoSync();

// Register Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(SERVER + '/courier-app.php?sw=1').catch(function(){});
}

// Last sync display
function updateLastSync() {
    var c = JSON.parse(localStorage.getItem(STORE_KEY) || '{}');
    var label = document.getElementById('lastSyncLabel');
    if (!label) return;
    var t = c.delivered_at || c.pickup_time;
    if (!t) { label.textContent = 'Never'; return; }
    var d = new Date(t);
    var now = new Date();
    var diff = Math.floor((now - d) / 60000);
    if (diff < 1) label.textContent = 'Just now';
    else if (diff < 60) label.textContent = diff + ' min ago';
    else if (diff < 1440) label.textContent = Math.floor(diff/60) + 'h ago';
    else label.textContent = d.toLocaleDateString();
}
setInterval(updateLastSync, 30000);
updateLastSync();

// QR Code (simple canvas-based)
function showQR() {
    var modal = document.getElementById('qrModal');
    modal.style.display = 'flex';
    var url = SERVER.replace('localhost','192.168.0.10') + '/portal/';
    // Simple QR using public CDN
    if (typeof QRCode === 'undefined') {
        var s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
        s.onload = function(){ renderQR(url); };
        document.head.appendChild(s);
    } else { renderQR(url); }
}
function renderQR(url) {
    var canvas = document.getElementById('qrCanvas');
    canvas.innerHTML = '';
    new QRCode(canvas, {text: url, width: 200, height: 200, correctLevel: QRCode.CorrectLevel.M});
}

// Install prompt
var deferredPrompt = null;
window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredPrompt = e;
    document.getElementById('installBanner').style.display = 'block';
});

function installApp() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt = null;
        document.getElementById('installBanner').style.display = 'none';
    }
}

// NETWORK DETECTION
function detectNetwork() {
    if (!navigator.onLine) { setNetwork('offline'); return; }
    var controller = new AbortController();
    var timer = setTimeout(function(){ controller.abort(); setNetwork('internet'); }, 2500);
    fetch(SERVER + '/datapost.php?api=ping&t=' + Date.now(), {
        signal: controller.signal, credentials: 'include', cache: 'no-store'
    })
    .then(function(r){ return r.ok ? r.json() : Promise.reject(); })
    .then(function(){ clearTimeout(timer); setNetwork('lan'); })
    .catch(function(){ clearTimeout(timer); if(navigator.onLine) setNetwork('internet'); });
}

function setNetwork(status) {
    var dot = document.getElementById('netDot');
    var label = document.getElementById('netLabel');
    var type = document.getElementById('netType');
    
    if (status === 'lan') {
        dot.className = 'net-dot net-lan';
        label.textContent = 'On School LAN';
        type.textContent = 'WiFi';
    } else if (status === 'internet') {
        dot.className = 'net-dot net-internet';
        label.textContent = 'Internet Available';
        type.textContent = 'Online';
    } else {
        dot.className = 'net-dot net-offline';
        label.textContent = 'No Connection';
        type.textContent = 'Offline';
    }
    
    // Auto actions on network change
    if (status !== lastNetwork && document.getElementById('autoToggle').checked) {
        if (status === 'lan') {
            checkPendingMarkSynced();
            if (!carried.delivered && (carried.ids||[]).length === 0) {
                addLog('🏫 LAN detected — picking up data...');
                setTimeout(doPickup, 2000);
            }
        }
        if (status === 'internet' && carried.entries.length > 0) {
            addLog('🌐 Internet detected — posting to cloud...');
            setTimeout(doPost, 2000);
        }
    }
    
    lastNetwork = status;
}

// AUTO SYNC LOOP
function startAutoSync() {
    setInterval(function() {
        detectNetwork();
    }, CHECK_INTERVAL);
    detectNetwork();
}

// PICKUP from LAN server
function doPickup() {
    if (syncing) return;
    syncing = true;
    setSyncState('syncing', 'Picking up...');
    
    fetch(SERVER + '/datapost.php?api=sync_package', { credentials: 'include' })
        .then(function(r) { return r.json(); })
        .then(function(pkg) {
            if (pkg.success && pkg.entry_ids && pkg.entry_ids.length > 0) {
                // Store on phone
                carried.entries = pkg;
                carried.ids = pkg.entry_ids;
                carried.pickup_time = new Date().toISOString();
                localStorage.setItem(STORE_KEY, JSON.stringify(carried));
                
                addLog('✅ Picked up ' + pkg.entry_ids.length + ' events');
                setSyncState('active', 'Carrying ' + pkg.entry_ids.length);
            } else {
                addLog('📭 No new data to pick up');
                setSyncState('active', 'No new data');
            }
            syncing = false;
            updateStats();
        })
        .catch(function(e) {
            addLog('❌ Pickup failed: ' + e.message);
            setSyncState('active', 'Pickup failed');
            syncing = false;
        });
}

// POST to Google Sheets
function doPost() {
    if (syncing) return;
    if (!carried.entries || !carried.ids || carried.ids.length === 0) {
        addLog('📭 Nothing to post');
        return;
    }
    
    var sheetsUrl = document.getElementById('sheetsInput').value || localStorage.getItem(SHEETS_KEY);
    if (!sheetsUrl) {
        addLog('⚠️ No Google Sheets URL configured');
        return;
    }
    
    syncing = true;
    setSyncState('syncing', 'Posting...');
    
    var pkg = carried.entries;
    var summary = pkg.summary;
    var promises = [];
    
    // Post summary
    promises.push(
        fetch(sheetsUrl, {
            method: 'POST',
            mode: 'no-cors',
            body: JSON.stringify({
                _row_type: 'SUMMARY',
                _date: summary.generated_at,
                _server: summary.server_name,
                total_students: summary.total_students,
                total_enrolments: summary.total_enrolments,
                active_today: summary.active_today,
                active_this_week: summary.active_this_week,
                total_events: summary.unsynced_events
            })
        })
    );
    
    // Post each lesson view
    var lessonViews = summary.lesson_views || [];
    for (var i = 0; i < lessonViews.length; i++) {
        var lv = lessonViews[i];
        promises.push(
            fetch(sheetsUrl, {
                method: 'POST',
                mode: 'no-cors',
                body: JSON.stringify({
                    _row_type: 'LESSON',
                    _date: summary.generated_at,
                    lesson: lv.lesson,
                    class: lv.class_name,
                    views: lv.views,
                    time_seconds: lv.total_time
                })
            })
        );
    }
    
    // Post each quiz score
    var quizScores = summary.quiz_scores || [];
    for (var j = 0; j < quizScores.length; j++) {
        var qs = quizScores[j];
        promises.push(
            fetch(sheetsUrl, {
                method: 'POST',
                mode: 'no-cors',
                body: JSON.stringify({
                    _row_type: 'QUIZ',
                    _date: summary.generated_at,
                    quiz: qs.quiz,
                    class: qs.class_name,
                    avg_score: qs.avg_score,
                    attempts: qs.attempts,
                    passed: qs.passed
                })
            })
        );
    }
    
    Promise.all(promises)
        .then(function() {
            addLog('✅ Posted ' + carried.ids.length + ' events to Google Sheets');
            totalPosted += carried.ids.length;
            totalCycles++;
            localStorage.setItem('mtti_posted', totalPosted.toString());
            localStorage.setItem('mtti_cycles', totalCycles.toString());
            
            // Mark delivered — keep data until server confirms on next LAN visit
            carried.delivered = true;
            carried.delivered_at = new Date().toISOString();
            localStorage.setItem(STORE_KEY, JSON.stringify(carried));
            fetch(SERVER + '/datapost.php?api=log_sync', {
                method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
                body: JSON.stringify({action:'post', entries: carried.ids.length, destination:'google_sheets', status:'success', device: navigator.userAgent.substr(0,100)})
            }).catch(function(){});
            setSyncState('active', 'Delivered!');
            syncing = false;
            updateStats();
        })
        .catch(function(e) {
            addLog('❌ Post failed: ' + e.message);
            setSyncState('active', 'Post failed');
            syncing = false;
        });
}

// Mark synced — clears data only after server confirmation
function markSynced() {
    var idsToMark = carried.ids || [];
    if (idsToMark.length === 0) return;
    fetch(SERVER + '/datapost.php?api=mark_synced', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
        body: JSON.stringify({ entry_ids: idsToMark, destination: 'google_sheets' })
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        if (res.success) {
            addLog('✅ Server confirmed ' + idsToMark.length + ' events marked synced');
            carried = { entries: [], ids: [], pickup_time: null, delivered: false };
            localStorage.setItem(STORE_KEY, JSON.stringify(carried));
            updateStats();
        } else { addLog('⚠️ Server error on mark_synced — keeping data'); }
    })
    .catch(function() { addLog('⏳ Not on LAN — data kept for next visit'); });
}

function checkPendingMarkSynced() {
    if (carried.delivered && (carried.ids||[]).length > 0) {
        addLog('🔄 Back on LAN — confirming ' + carried.ids.length + ' delivered events...');
        markSynced();
    }
}

// UI helpers
function setSyncState(state, label) {
    var ring = document.getElementById('syncRing');
    var icon = document.getElementById('syncIcon');
    var lbl = document.getElementById('syncLabel');
    
    ring.className = 'sync-ring ' + state;
    lbl.textContent = label;
    
    if (state === 'syncing') { icon.textContent = '🔄'; }
    else if (state === 'active') { icon.textContent = '📡'; }
    else { icon.textContent = '⏸️'; }
}

function updateStats() {
    document.getElementById('carriedNum').textContent = (carried.ids || []).length;
    document.getElementById('postedNum').textContent = totalPosted;
    document.getElementById('cycleNum').textContent = totalCycles;
}

function addLog(msg) {
    var container = document.getElementById('logContainer');
    var time = new Date().toLocaleTimeString();
    var entry = document.createElement('div');
    entry.className = 'log-entry';
    entry.textContent = time + ' — ' + msg;
    container.insertBefore(entry, container.firstChild);
    
    // Keep max 50 entries
    while (container.children.length > 50) {
        container.removeChild(container.lastChild);
    }
}

function clearLog() {
    document.getElementById('logContainer').innerHTML = '<div class="log-entry text-muted">Log cleared</div>';
}

function checkSheetsConfig() {
    var url = document.getElementById('sheetsInput').value;
    var badge = document.getElementById('sheetsStatus');
    if (url && url.includes('script.google.com')) {
        badge.textContent = 'Configured';
        badge.className = 'badge bg-success';
        localStorage.setItem(SHEETS_KEY, url);
    } else {
        badge.textContent = 'Not set';
        badge.className = 'badge bg-secondary';
    }
}

document.getElementById('sheetsInput').addEventListener('change', checkSheetsConfig);

// Manual buttons
function manualPickup() {
    addLog('📥 Manual pickup...');
    doPickup();
}

function manualPost() {
    addLog('📤 Manual post...');
    doPost();
}

// Network change listeners
window.addEventListener('online', function() { detectNetwork(); });
window.addEventListener('offline', function() { detectNetwork(); });
</script>
</body>
</html>
