<?php
// Mobile DTMF API
//   GET  api/dtmf.php?digit=5
//   POST api/dtmf.php  body: dtmf=5
// Hinweis: '#' URL-kodiert senden: digit=1%23

header('Content-Type: application/json; charset=utf-8');

while (ob_get_level() > 0) { @ob_end_clean(); }
ob_start();
register_shutdown_function(function () {
    while (ob_get_level() > 0) { @ob_end_clean(); }
});

$digit = $_GET['digit'] ?? $_POST['digit'] ?? $_POST['dtmf'] ?? '';
$digit = trim($digit);

if ($digit === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'missing digit']);
    exit;
}

if (!preg_match('/^[0-9A-D\*#]+$/i', $digit)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'invalid: ' . htmlspecialchars($digit)]);
    exit;
}

// ── Dynamische Pfad-Erkennung ─────────────────────────────────────────────────
// __DIR__ = .../whatever/mobile/api
// dirname(dirname(__DIR__)) = .../whatever  (Dashboard-Root, dort liegt include/buttons.php)
$dashboardRoot = dirname(dirname(__DIR__));

// In den Dashboard-Root wechseln, damit relative Includes im Legacy-Script funktionieren
chdir($dashboardRoot);

$_POST['dtmfsvx'] = $digit;

$buttonsFile = $dashboardRoot . '/include/buttons.php';
if (file_exists($buttonsFile)) {
    require $buttonsFile;
}

while (ob_get_level() > 0) { @ob_end_clean(); }

echo json_encode(['ok' => true, 'msg' => $digit . ' gesendet']);
exit;
