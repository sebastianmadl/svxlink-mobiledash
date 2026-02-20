<?php
// DTMF API Endpoint
// Liest DTMF-Code aus POST und schreibt ihn nach /tmp/dtmf_svx
// Kompatibel mit SP2ONG/SP0DZ Dashboard (buttons.php Logik)
//
// POST body: dtmf=*91232#
// Returns:   JSON {ok: true, code: "*91232#"}

header('Content-Type: application/json; charset=utf-8');
while(ob_get_level()>0){@ob_end_clean();}

require_once dirname(__DIR__).'/configs/config.php';

// Input validieren
$code = trim($_POST['dtmf'] ?? $_POST['dtmfsvx'] ?? $_GET['dtmf'] ?? '');

if ($code === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'missing dtmf code']);
    exit;
}

// Nur gültige DTMF-Zeichen erlauben
if (!preg_match('/^[0-9A-D\*#]+$/i', $code)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'invalid characters in dtmf code']);
    exit;
}

// DTMF senden: echo 'CODE' > /tmp/dtmf_svx
$result = file_put_contents($dtmfFile, $code . "\n");

if ($result === false) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>'could not write to '.$dtmfFile]);
    exit;
}

echo json_encode(['ok'=>true,'code'=>$code]);
