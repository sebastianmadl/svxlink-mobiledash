<?php
// Mobile DTMF API – kompatibel mit:
//   GET  /mobile/api/dtmf.php?digit=5       (dtmf.php-Seite / direkter Link)
//   POST /mobile/api/dtmf.php  body: dtmf=5  (app.js / SPA)
// Hinweis: '#' muss URL-kodiert werden: digit=1%23

header('Content-Type: application/json; charset=utf-8');

// Output-Buffer bereinigen (Legacy-Includes könnten etwas ausgeben)
while (ob_get_level() > 0) { @ob_end_clean(); }
ob_start();
register_shutdown_function(function () {
    while (ob_get_level() > 0) { @ob_end_clean(); }
});

// Parameter aus GET oder POST holen – unterstützt beide Feldnamen (digit + dtmf)
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

// Ins Webroot wechseln damit Includes im Original-Script korrekt aufgelöst werden
chdir('/var/www/html');

// Für das Legacy-Script als POST bereitstellen
$_POST['dtmfsvx'] = $digit;

// Original-Handler einbinden; dessen Ausgabe wird verworfen
require 'include/buttons.php';

// Ausgabe des Legacy-Scripts wegwerfen
while (ob_get_level() > 0) { @ob_end_clean(); }

echo json_encode(['ok' => true, 'msg' => $digit . ' gesendet']);
exit;
