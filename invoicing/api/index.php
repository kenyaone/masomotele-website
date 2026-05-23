<?php
/**
 * Masomotele Invoicing System - Backend API
 * Drop in alongside public.html. Uses SQLite (no MySQL needed).
 * 
 * Endpoints:
 *   GET  api/?action=settings            → load company settings
 *   POST api/?action=settings            → save company settings
 *   GET  api/?action=records             → list all records (with optional ?type=invoice etc.)
 *   POST api/?action=save_record         → save a new document record
 *   POST api/?action=update_record       → update existing record
 *   POST api/?action=delete_record       → delete a record
 *   GET  api/?action=stats               → dashboard stats
 *   POST api/?action=upload_logo         → upload logo file
 *   GET  api/?action=next_number&type=X  → get next doc number
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

define('DB_PATH', __DIR__ . '/../data/invoicing.db');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');

// Build full URL dynamically so logo works from any domain/subfolder
$_scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
// api/ is one level deep inside the app folder, so go up one level for uploads/
$_base      = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
define('UPLOADS_URL', $_scheme . '://' . $_host . $_base . '/uploads/');

// Ensure data directory exists
if (!is_dir(dirname(DB_PATH))) {
    mkdir(dirname(DB_PATH), 0755, true);
}
if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initDB($pdo);
    }
    return $pdo;
}

function initDB(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        );

        CREATE TABLE IF NOT EXISTS records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL,
            doc_number TEXT NOT NULL,
            client_name TEXT,
            client_address TEXT,
            client_contact TEXT,
            items TEXT,
            subtotal REAL DEFAULT 0,
            vat_amount REAL DEFAULT 0,
            total REAL DEFAULT 0,
            vat_enabled INTEGER DEFAULT 0,
            vat_rate REAL DEFAULT 16,
            payment_method TEXT,
            notes TEXT,
            doc_date TEXT,
            due_date TEXT,
            valid_until TEXT,
            delivery_to TEXT,
            delivery_address TEXT,
            status TEXT DEFAULT 'active',
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS counters (
            type TEXT PRIMARY KEY,
            next_number INTEGER DEFAULT 1
        );

        INSERT OR IGNORE INTO counters (type, next_number) VALUES
            ('invoice', 1), ('quotation', 1), ('receipt', 1), ('delivery', 1);

        INSERT OR IGNORE INTO settings (key, value) VALUES
            ('company_name', 'Your Company Name'),
            ('company_address', 'P.O. Box 12345, Nairobi, Kenya'),
            ('company_phone', '+254 700 123456'),
            ('company_email', 'info@company.co.ke'),
            ('company_vat', ''),
            ('company_logo', ''),
            ('vat_enabled', '1'),
            ('vat_rate', '16');
    ");
}

function respond(bool $success, $data = null, string $message = ''): void {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? $_POST;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();

    switch ($action) {

        // ── SETTINGS ──────────────────────────────────────────────
        case 'settings':
            if ($method === 'GET') {
                $rows = $db->query("SELECT key, value FROM settings")->fetchAll();
                $settings = [];
                foreach ($rows as $r) $settings[$r['key']] = $r['value'];
                respond(true, $settings);
            }
            if ($method === 'POST') {
                $body = getBody();
                $allowed = ['company_name','company_address','company_phone','company_email',
                            'company_vat','company_logo','vat_enabled','vat_rate'];
                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
                foreach ($allowed as $key) {
                    if (isset($body[$key])) {
                        $stmt->execute([$key, $body[$key]]);
                    }
                }
                respond(true, null, 'Settings saved');
            }
            break;

        // ── LOGO UPLOAD ───────────────────────────────────────────
        case 'upload_logo':
            if ($method === 'POST' && isset($_FILES['logo'])) {
                $file = $_FILES['logo'];
                $allowed_types = ['image/jpeg','image/png','image/gif','image/webp'];
                if (!in_array($file['type'], $allowed_types)) {
                    respond(false, null, 'Invalid file type');
                }
                if ($file['size'] > 2 * 1024 * 1024) {
                    respond(false, null, 'File too large (max 2MB)');
                }
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $ext;
                $dest = UPLOADS_DIR . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $url = UPLOADS_URL . $filename;
                    $db->prepare("INSERT OR REPLACE INTO settings (key,value) VALUES ('company_logo',?)")
                       ->execute([$url]);
                    respond(true, ['url' => $url], 'Logo uploaded');
                } else {
                    respond(false, null, 'Upload failed');
                }
            }
            break;

        // ── NEXT DOCUMENT NUMBER ──────────────────────────────────
        case 'next_number':
            $type = $_GET['type'] ?? 'invoice';
            $prefixes = ['invoice'=>'INV','quotation'=>'QUO','receipt'=>'REC','delivery'=>'DN'];
            $prefix = $prefixes[$type] ?? 'DOC';
            $row = $db->prepare("SELECT next_number FROM counters WHERE type=?");
            $row->execute([$type]);
            $num = $row->fetchColumn() ?: 1;
            respond(true, ['number' => $prefix . '-' . str_pad($num, 3, '0', STR_PAD_LEFT)]);
            break;

        // ── SAVE RECORD ───────────────────────────────────────────
        case 'save_record':
            if ($method === 'POST') {
                $b = getBody();
                $type = $b['type'] ?? 'invoice';
                $prefixes = ['invoice'=>'INV','quotation'=>'QUO','receipt'=>'REC','delivery'=>'DN'];
                $prefix = $prefixes[$type] ?? 'DOC';

                // Get and increment counter
                $row = $db->prepare("SELECT next_number FROM counters WHERE type=?");
                $row->execute([$type]);
                $num = $row->fetchColumn() ?: 1;
                $doc_number = $b['doc_number'] ?? ($prefix . '-' . str_pad($num, 3, '0', STR_PAD_LEFT));

                $db->prepare("UPDATE counters SET next_number=next_number+1 WHERE type=?")
                   ->execute([$type]);

                $stmt = $db->prepare("
                    INSERT INTO records 
                    (type, doc_number, client_name, client_address, client_contact,
                     items, subtotal, vat_amount, total, vat_enabled, vat_rate,
                     payment_method, notes, doc_date, due_date, valid_until,
                     delivery_to, delivery_address, status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                    $type,
                    $doc_number,
                    $b['client_name'] ?? '',
                    $b['client_address'] ?? '',
                    $b['client_contact'] ?? '',
                    is_array($b['items'] ?? null) ? json_encode($b['items']) : ($b['items'] ?? '[]'),
                    floatval($b['subtotal'] ?? 0),
                    floatval($b['vat_amount'] ?? 0),
                    floatval($b['total'] ?? 0),
                    intval($b['vat_enabled'] ?? 1),
                    floatval($b['vat_rate'] ?? 16),
                    $b['payment_method'] ?? '',
                    $b['notes'] ?? '',
                    $b['doc_date'] ?? date('Y-m-d'),
                    $b['due_date'] ?? '',
                    $b['valid_until'] ?? '',
                    $b['delivery_to'] ?? '',
                    $b['delivery_address'] ?? '',
                    $b['status'] ?? 'active'
                ]);
                $id = $db->lastInsertId();
                respond(true, ['id' => $id, 'doc_number' => $doc_number], 'Record saved');
            }
            break;

        // ── UPDATE RECORD ─────────────────────────────────────────
        case 'update_record':
            if ($method === 'POST') {
                $b = getBody();
                $id = intval($b['id'] ?? 0);
                if (!$id) respond(false, null, 'Invalid ID');

                $stmt = $db->prepare("
                    UPDATE records SET
                        client_name=?, client_address=?, client_contact=?,
                        items=?, subtotal=?, vat_amount=?, total=?,
                        payment_method=?, notes=?, doc_date=?, due_date=?,
                        valid_until=?, delivery_to=?, delivery_address=?,
                        status=?, updated_at=datetime('now')
                    WHERE id=?
                ");
                $stmt->execute([
                    $b['client_name'] ?? '',
                    $b['client_address'] ?? '',
                    $b['client_contact'] ?? '',
                    is_array($b['items'] ?? null) ? json_encode($b['items']) : ($b['items'] ?? '[]'),
                    floatval($b['subtotal'] ?? 0),
                    floatval($b['vat_amount'] ?? 0),
                    floatval($b['total'] ?? 0),
                    $b['payment_method'] ?? '',
                    $b['notes'] ?? '',
                    $b['doc_date'] ?? date('Y-m-d'),
                    $b['due_date'] ?? '',
                    $b['valid_until'] ?? '',
                    $b['delivery_to'] ?? '',
                    $b['delivery_address'] ?? '',
                    $b['status'] ?? 'active',
                    $id
                ]);
                respond(true, null, 'Record updated');
            }
            break;

        // ── DELETE RECORD ─────────────────────────────────────────
        case 'delete_record':
            if ($method === 'POST') {
                $b = getBody();
                $id = intval($b['id'] ?? 0);
                if (!$id) respond(false, null, 'Invalid ID');
                $db->prepare("DELETE FROM records WHERE id=?")->execute([$id]);
                respond(true, null, 'Record deleted');
            }
            break;

        // ── LIST RECORDS ──────────────────────────────────────────
        case 'records':
            $type = $_GET['type'] ?? 'all';
            $search = $_GET['search'] ?? '';
            $limit = intval($_GET['limit'] ?? 100);
            $offset = intval($_GET['offset'] ?? 0);

            $where = "WHERE status != 'deleted'";
            $params = [];

            if ($type !== 'all') {
                $where .= " AND type=?";
                $params[] = $type;
            }
            if ($search) {
                $where .= " AND (client_name LIKE ? OR doc_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $stmt = $db->prepare("SELECT * FROM records $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            // Parse items JSON
            foreach ($rows as &$row) {
                $row['items'] = json_decode($row['items'] ?? '[]', true) ?: [];
            }

            respond(true, $rows);
            break;

        // ── STATS ─────────────────────────────────────────────────
        case 'stats':
            $stats = [];
            foreach (['invoice','quotation','receipt','delivery'] as $t) {
                $row = $db->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM records WHERE type=? AND status!='deleted'");
                $row->execute([$t]);
                $stats[$t] = $row->fetch();
            }
            $total_rev = $db->query("SELECT COALESCE(SUM(total),0) as r FROM records WHERE type IN ('invoice','receipt') AND status!='deleted'")->fetchColumn();
            $stats['total_revenue'] = $total_rev;
            respond(true, $stats);
            break;

        default:
            respond(false, null, 'Unknown action');
    }

} catch (Exception $e) {
    respond(false, null, 'Server error: ' . $e->getMessage());
}
