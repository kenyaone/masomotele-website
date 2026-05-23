<?php
require_once __DIR__ . '/includes/init.php';

// Serve the app file for download
if (isset($_GET['download'])) {
    $file = __DIR__ . '/courier-standalone.html';
    if (file_exists($file)) {
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="MTTI-DataPost.html"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

$pageTitle = 'Download Courier App - ' . SITE_NAME;
$auth = new Auth(); $auth->requireLogin();
$auth->requireRole(['admin', 'teacher']);
require_once __DIR__ . '/templates/header.php';
?>
<div class="container" style="max-width:600px">
    <div class="text-center mb-4">
        <div style="font-size:4rem">📡</div>
        <h3>DataPost Courier App</h3>
        <p class="text-muted">Sync LMS data to Google Sheets automatically</p>
    </div>

    <div class="card p-4 mb-3">
        <h5 class="mb-3"><i class="bi bi-download me-2"></i>Download & Install</h5>
        <ol>
            <li class="mb-2"><strong>Download the app</strong> to your phone:<br>
                <a href="?download=1" class="btn btn-primary mt-2"><i class="bi bi-download me-2"></i>Download MTTI-DataPost.html</a>
            </li>
            <li class="mb-2"><strong>Open the downloaded file</strong> in Chrome on your phone</li>
            <li class="mb-2">Chrome menu (⋮) → <strong>Add to Home Screen</strong></li>
            <li class="mb-2">Now it's an app! Open it from your home screen</li>
        </ol>
    </div>

    <div class="card p-4 mb-3">
        <h5 class="mb-3"><i class="bi bi-gear me-2"></i>First Time Setup</h5>
        <ol>
            <li class="mb-2">Open the app</li>
            <li class="mb-2">Set <strong>LMS Server</strong> to: <code><?=SITE_URL?></code></li>
            <li class="mb-2">Set <strong>Google Sheets URL</strong> to your webhook</li>
            <li class="mb-2">Turn on <strong>Auto Sync</strong></li>
        </ol>
    </div>

    <div class="card p-4 mb-3">
        <h5 class="mb-3"><i class="bi bi-arrow-repeat me-2"></i>How It Works</h5>
        <div class="d-flex align-items-center mb-3">
            <div class="text-center me-3" style="min-width:60px"><div style="font-size:2rem">🏫</div><small>School</small></div>
            <div style="font-size:1.5rem">→</div>
            <div class="text-center mx-3" style="min-width:60px"><div style="font-size:2rem">📱</div><small>Phone</small></div>
            <div style="font-size:1.5rem">→</div>
            <div class="text-center ms-3" style="min-width:60px"><div style="font-size:2rem">☁️</div><small>Cloud</small></div>
        </div>
        <ul class="small">
            <li><strong>On school WiFi:</strong> App auto-picks up usage data from LMS</li>
            <li><strong>On mobile data:</strong> App auto-posts to Google Sheets</li>
            <li><strong>Back on school WiFi:</strong> Marks data as synced, picks up new data</li>
            <li>Checks every 10 seconds — fully automatic</li>
        </ul>
    </div>

    <div class="card p-4">
        <h5 class="mb-3"><i class="bi bi-table me-2"></i>Google Sheets Setup</h5>
        <ol class="small">
            <li>Create a new Google Sheet</li>
            <li>Extensions → Apps Script</li>
            <li>Paste this code:<br>
<pre class="bg-light text-dark p-2 rounded mt-1" style="font-size:0.75rem">function doPost(e) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var data = JSON.parse(e.postData.contents);
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(Object.keys(data));
  }
  sheet.appendRow(Object.values(data));
  return ContentService.createTextOutput('OK');
}</pre>
            </li>
            <li>Deploy → New Deployment → Web app → Anyone → Deploy</li>
            <li>Copy the URL and paste in the app settings</li>
        </ol>
    </div>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
