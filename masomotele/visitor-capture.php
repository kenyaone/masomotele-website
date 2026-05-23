<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false]); exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) { echo json_encode(['ok' => false]); exit; }

$file = __DIR__ . '/visitors.json';

// Load existing
$visitors = [];
if (file_exists($file)) {
    $existing = json_decode(file_get_contents($file), true);
    if (is_array($existing)) $visitors = $existing;
}

// Check if phone already exists — update location if so
$phone = preg_replace('/\D/', '', $data['phone'] ?? '');
$found = false;
foreach ($visitors as &$v) {
    if (preg_replace('/\D/', '', $v['phone'] ?? '') === $phone) {
        $v['location'] = $data['location'] ?? $v['location'];
        $v['last_seen'] = date('Y-m-d H:i:s');
        $v['visits'] = ($v['visits'] ?? 1) + 1;
        $found = true;
        break;
    }
}

if (!$found) {
    $visitors[] = [
        'id'        => uniqid(),
        'name'      => strip_tags(trim($data['name'] ?? '')),
        'phone'     => $phone,
        'location'  => strip_tags(trim($data['location'] ?? '')),
        'registered'=> date('Y-m-d H:i:s'),
        'last_seen' => date('Y-m-d H:i:s'),
        'visits'    => 1,
    ];
}

// Sort newest first
usort($visitors, function($a, $b) {
    return strtotime($b['registered']) - strtotime($a['registered']);
});

file_put_contents($file, json_encode($visitors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
echo json_encode(['ok' => true, 'total' => count($visitors)]);
