<?php
header('Content-Type: application/json; charset=utf-8');

$settingsFile = __DIR__ . '/configs/mobile_settings.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'msg'=>'POST required']);
    exit;
}

$lang  = $_POST['lang']  ?? null;
$theme = $_POST['theme'] ?? null;

if ($lang  !== null && !in_array($lang,  ['de','en']))               $lang  = null;
if ($theme !== null && !in_array($theme, ['system','dark','light']))  $theme = null;

if ($lang === null && $theme === null) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'nothing valid to save']);
    exit;
}

// Read existing
$s = ['language'=>'','theme'=>'system'];
if (file_exists($settingsFile)) {
    $d = json_decode(file_get_contents($settingsFile), true);
    if (is_array($d)) $s = array_merge($s, $d);
}

if ($lang  !== null) $s['language'] = $lang;
if ($theme !== null) $s['theme']    = $theme;

$written = file_put_contents($settingsFile, json_encode($s, JSON_PRETTY_PRINT));

if ($written === false) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>'Cannot write configs/mobile_settings.json — run: chmod 664 configs/mobile_settings.json']);
    exit;
}

echo json_encode(['ok'=>true,'language'=>$s['language'],'theme'=>$s['theme']]);
