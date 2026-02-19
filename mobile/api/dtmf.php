<?php
// Mobile DTMF API
//   GET  api/dtmf.php?digit=5
//   POST api/dtmf.php  body: dtmf=5
// Note: '#' must be URL-encoded: digit=1%23

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

// ── Dynamic Path Detection ────────────────────────────────────────────────────
// __DIR__ = .../whatever/mobile/api
// dirname(dirname(__DIR__)) = .../whatever  (dashboard root)
$dashboardRoot = dirname(dirname(__DIR__));

chdir($dashboardRoot);

$_POST['dtmfsvx'] = $digit;

$buttonsFile = $dashboardRoot . '/include/buttons.php';
if (file_exists($buttonsFile)) {
    require $buttonsFile;
}

while (ob_get_level() > 0) { @ob_end_clean(); }

echo json_encode(['ok' => true, 'msg' => $digit . ' sent']);
exit;
