<?php
header('Content-Type: application/json; charset=utf-8');
while(ob_get_level()>0){@ob_end_clean();}

require_once dirname(__DIR__).'/settings.php';
$code = trim($_POST['dtmf'] ?? $_POST['dtmfsvx'] ?? $_GET['dtmf'] ?? '');

if ($code === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'missing dtmf code']);
    exit;
}
if (!preg_match('/^[0-9A-D\*#]+$/i', $code)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'invalid characters in dtmf code']);
    exit;
}
$result = file_put_contents($dtmfFile, $code . "\n");

if ($result === false) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>'could not write to '.$dtmfFile]);
    exit;
}

echo json_encode(['ok'=>true,'code'=>$code]);
