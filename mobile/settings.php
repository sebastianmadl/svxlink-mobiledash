<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'settings.php'
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $settingsFile = __DIR__ . '/configs/mobile_settings.json';
    $lang  = $_POST['lang']  ?? null;
    $theme = $_POST['theme'] ?? null;
    if ($lang      !== null && !in_array($lang,      ['de', 'en']))                $lang      = null;
    if ($theme     !== null && !in_array($theme,     ['system', 'dark', 'light'])) $theme     = null;
    $tgNamesVal = isset($_POST['tg_names'])  ? json_decode($_POST['tg_names'], true) : null;
    $tgSrcVal   = isset($_POST['tg_source']) && in_array($_POST['tg_source'], ['off','custom','dl3el','mixed']) ? $_POST['tg_source'] : null;
    if ($lang === null && $theme === null && $tgNamesVal === null && $tgSrcVal === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'nothing valid to save']);
        exit;
    }
    $s = ['language' => '', 'theme' => 'system'];
    if (file_exists($settingsFile)) {
        $d = json_decode(file_get_contents($settingsFile), true);
        if (is_array($d)) $s = array_merge($s, $d);
    }
    if ($lang       !== null) $s['language']  = $lang;
    if ($theme      !== null) $s['theme']     = $theme;
    if ($tgNamesVal !== null && is_array($tgNamesVal)) $s['tg_names']  = $tgNamesVal;
    if ($tgSrcVal   !== null) $s['tg_source'] = $tgSrcVal;
    $written = file_put_contents($settingsFile, json_encode($s, JSON_PRETTY_PRINT));
    if ($written === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Cannot write mobile_settings.json — chmod 666 mobile/configs/mobile_settings.json']);
        exit;
    }
    echo json_encode(['ok' => true, 'language' => $s['language'], 'theme' => $s['theme'], 'tg_source' => $s['tg_source'] ?? 'off']);
    exit;
}
$mobileRoot    = dirname(__FILE__);
$dashboardRoot = dirname($mobileRoot);
$dashCfgFile = $dashboardRoot . '/include/config.php';
if (file_exists($dashCfgFile)) include_once $dashCfgFile;
require_once $mobileRoot . '/configs/config.php';

function parseCallerPhp($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    $result = [];
    if (preg_match_all('/<span[^>]*color:#b5651d[^>]*>(\d+)<\/span>.*?<td[^>]*font-weight:bold[^>]*>&nbsp;<b>\s*([^<]+)<\/b>/s', $content, $m, PREG_SET_ORDER)) {
        foreach ($m as $r) $result[trim($r[1])] = trim($r[2]);
    }
    return $result;
}

function parseTgdbPhp($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    $result = [];
    // Extract entries from $tgdb_array = [ 'key' => 'value', ... ]
    if (preg_match_all("/['\"](\d+)['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $m, PREG_SET_ORDER)) {
        foreach ($m as $r) $result[trim($r[1])] = trim($r[2]);
    }
    return $result;
}

function tgName($tg) {
    global $tgNames, $tgSource, $callerTgNames;
    $custom = $tgNames[$tg]       ?? null;
    $caller = $callerTgNames[$tg] ?? null;
    switch ($tgSource) {
        case 'custom': return $custom ?? 'Talk Group ' . $tg;
        case 'dl3el':  return $caller ?? 'Talk Group ' . $tg;
        case 'mixed':  return $custom ?? $caller ?? 'Talk Group ' . $tg;
        default:       return 'Talk Group ' . $tg;
    }
}

if (!function_exists('cdef')) {
    function cdef($n, $d = null) { return defined($n) ? constant($n) : $d; }
}
$settingsFile = $mobileRoot . '/configs/mobile_settings.json';
$settings = ['language' => '', 'theme' => 'system', 'tg_names' => [], 'tg_source' => 'off'];
if (file_exists($settingsFile)) {
    $d = json_decode(file_get_contents($settingsFile), true);
    if (is_array($d)) $settings = array_merge($settings, $d);
}
$language  = $settings['language'];
$theme     = $settings['theme'];
$tgNames  = $settings['tg_names']  ?? [];
$tgSource = $settings['tg_source'] ?? 'off';
$tgdbFile      = $dashboardRoot . '/include/tgdb.php';
$callerTgNames = parseTgdbPhp($tgdbFile);
$appVersion = '2.0';
$appAuthor  = cdef('APP_AUTHOR', 'OE1SXM');
$appYear    = date('Y');
$byline     = "SvxLink Mobile Dashboard Ver {$appVersion} © by {$appAuthor} {$appYear}";
$svxConfPath  = rtrim(cdef('SVXCONFPATH', '/etc/svxlink'), '/') . '/' . cdef('SVXCONFIG', 'svxlink.conf');
$svxLogPath   = rtrim(cdef('SVXLOGPATH',  '/var/log'), '/');
$svxLogPrefix = cdef('SVXLOGPREFIX', 'svxlink');
$svxLogFile   = '';
foreach ([
    "$svxLogPath/$svxLogPrefix.log",
    "$svxLogPath/$svxLogPrefix",
    "$svxLogPath/$svxLogPrefix/$svxLogPrefix.log",
    "$svxLogPath/$svxLogPrefix/$svxLogPrefix",
] as $c) { if (is_file($c)) { $svxLogFile = $c; break; } }

$dtmfFile    = cdef('DTMF_FILE', '/tmp/dtmf_svx');
$buttonsFile = $dashboardRoot . '/include/buttons.php';
$cpuTempFile = '/sys/class/thermal/thermal_zone0/temp';
$memInfoFile = '/proc/meminfo';
$uptimeFile  = '/proc/uptime';
$allStrings = [
'de' => [
    'badge_connected'    => 'VERBUNDEN',
	'badge_disconnected' => 'GETRENNT',
	'badge_connecting'   => 'VERBINDUNG…',
	'radio'              => 'RADIO',
	'listening'          => 'Warten',
	'active_tg'          => 'TG AKTIV',
	'talker'             => 'SPRECHER',
	'activity_title'     => 'SVXReflector Aktivität',
	'reflector_status'   => 'Reflector Status',
	'host'               => 'Host',
	'callsign_lbl'       => 'Anmeldename',
	'connection'         => 'Verbindung',
	'connected_dot'      => '● Verbunden',
	'default_tg'         => 'Standard TG',
	'monitor_tgs'        => 'Monitor TGs',
	'hardware_info'      => 'Hardware Info',
	'hostname'           => 'Hostname',
	'uptime'             => 'Uptime',
	'cpu_load'           => 'CPU Last',
	'cpu_temp'           => 'CPU Temp',
	'memory'             => 'Arbeitsspeicher',
	'disk'               => 'Speicher',
	'open_dashboard'     => '↗ Vollständiges Dashboard öffnen',
	'recent_talkers'     => 'Letzte Sprecher',
	'realtime_sse'       => 'Echtzeit via Server-Sent Events',
	'monitor_tgs_title'  => 'Monitor TGs',
	'activate_manually'  => 'Manuell aktivieren',
	'tg_placeholder'     => 'TG Nummer…',
	'activate_btn'       => 'Aktivieren',
	'quick_actions'      => 'Quick-Aktionen',
	'disconnect_tg'      => 'TG trennen',
	'disconnect_el'      => 'EL trennen',
	'dtmf_keypad'        => 'DTMF Tastatur',
	'clear_btn'          => '⌫ Löschen',
	'send_btn'           => 'Senden',
	'quick_commands'     => 'Schnellbefehle',
	'nav_dashboard'      => 'Dashboard',
	'nav_activity'       => 'Aktivität',
	'nav_tg'             => 'Talk Groups',
	'nav_dtmf'           => 'DTMF',
	'waiting'            => 'Warte auf Daten…',
	'no_activity'        => 'Noch keine Aktivität im Log',
	'settings_title'     => 'Einstellungen',
	'settings_language'  => 'Sprache',
	'settings_theme'     => 'Farbschema',
	'editors_label'      => 'Editoren',
	'tg_rename_label'    => 'Sprechgruppen umbenennen',
	'tg_rename_on'       => 'Aktiviert',
	'tg_rename_off'      => 'Deaktiviert',
	'tg_rename_edit'     => 'Sprechgruppen umbenennen',
	'tg_rename_save'     => 'Speichern',
	'tg_source_label'    => 'Sprechgruppen',
	'tg_source_edit'     => 'Sprechgruppen',
	'tg_src_custom'      => 'Custom',
	'tg_src_dl3el'       => 'DL3EL',
	'tg_src_mixed'       => 'DL3EL + Custom',
	'tg_src_editor_hint' => 'TG Nummer und Name eintragen',
	'theme_system'       => 'System',
	'theme_dark'         => 'Dunkel',
	'theme_light'        => 'Hell',
	'btn_el'             => 'EL trennen',
	'btn_tg'             => 'TG trennen',
	'toast_sending'      => 'Sende: ',
	'toast_sent'         => '✓ DTMF gesendet',
	'toast_saved'        => '✓ Gespeichert',
	'toast_invalid_tg'   => 'Ungültige TG-Nummer',
	'toast_last_updated' => '↻ Letzte Aktualisierung: ',
	'no_activity_short'  => 'Noch keine Aktivität im Log',
	'talkgroup'          => 'Talk Group',
	'tg_json_hint'       => 'Namen können auch direkt in configs/mobile_settings.json bearbeitet werden.',
	'tg_readonly_hint'   => '🔒 DL3EL Datenbank — Namen sind schreibgeschützt',
	'tg_new_name'        => 'Neuen Namen eingeben',
	'custom_tgs_title'   => 'Eigene Einträge',
	'tg_add_entry'       => 'Neuen Eintrag hinzufügen',
	'col_name'           => 'Name',
	'tg_original_label'  => 'Original',
	'module_label'       => 'Modul',
],
'en' => [
    'badge_connected'    => 'CONNECTED',
	'badge_disconnected' => 'DISCONNECTED',
	'badge_connecting'   => 'CONNECTING…',
	'radio'              => 'RADIO',
	'listening'          => 'Listening',
	'active_tg'          => 'ACTIVE TG',
	'talker'             => 'TALKER',
	'activity_title'     => 'SVXReflector Activity',
	'reflector_status'   => 'Reflector Status',
	'host'               => 'Host',
	'callsign_lbl'       => 'Callsign',
	'connection'         => 'Connection',
	'connected_dot'      => '● Connected',
	'default_tg'         => 'Default TG',
	'monitor_tgs'        => 'Monitor TGs',
	'hardware_info'      => 'Hardware Info',
	'hostname'           => 'Hostname',
	'uptime'             => 'Uptime',
    'cpu_load'           => 'CPU Load',
	'cpu_temp'           => 'CPU Temp',
	'memory'             => 'Memory',
	'disk'               => 'Disk',
	'open_dashboard'     => '↗ Open Full Dashboard',
	'recent_talkers'     => 'Recent Talkers',
	'realtime_sse'       => 'Real-time via Server-Sent Events',
	'monitor_tgs_title'  => 'Monitor TGs',
	'activate_manually'  => 'Activate Manually',
	'tg_placeholder'     => 'TG number…',
	'activate_btn'       => 'Activate',
	'quick_actions'      => 'Quick Actions',
	'disconnect_tg'      => 'Disconnect TG',
	'disconnect_el'      => 'Disconnect EL',
	'dtmf_keypad'        => 'DTMF Keypad',
	'clear_btn'          => '⌫ Clear',
	'send_btn'           => 'Send',
	'quick_commands'     => 'Quick Commands',
	'nav_dashboard'      => 'Dashboard',
	'nav_activity'       => 'Activity',
	'nav_tg'             => 'Talk Groups',
	'nav_dtmf'           => 'DTMF',
	'waiting'            => 'Waiting for data…',
	'no_activity'        => 'No activity in log yet',
	'settings_title'     => 'Settings',
	'settings_language'  => 'Language',
	'settings_theme'     => 'Theme',
	'editors_label'      => 'Editors',
	'tg_rename_label'    => 'Rename Talk Groups',
	'tg_rename_on'       => 'Enabled',
	'tg_rename_off'      => 'Disabled',
	'tg_rename_edit'     => 'Rename Talk Groups',
	'tg_rename_save'   	 => 'Save',
	'tg_source_label'    => 'Talk Groups',
	'tg_source_edit'     => 'Talk Groups',
	'tg_src_custom'      => 'Custom',
	'tg_src_dl3el'		 => 'DL3EL',
	'tg_src_mixed'       => 'DL3EL + Custom',
	'tg_src_editor_hint' => 'Enter TG number and name',
	'theme_system'       => 'System',
	'theme_dark'         => 'Dark',
	'theme_light'        => 'Light',
	'btn_el'             => 'Disconnect EL',
	'btn_tg'             => 'Disconnect TG',
	'toast_sending'      => 'Sending: ',
	'toast_sent'         => '✓ DTMF sent',
	'toast_saved'        => '✓ Saved',
	'toast_invalid_tg'   => 'Invalid TG number',
	'toast_last_updated' => '↻ Last updated: ',
	'no_activity_short'  => 'No activity in log yet',
	'talkgroup'          => 'Talk Group',
	'tg_json_hint'       => 'Names can also be edited directly in configs/mobile_settings.json.',
	'tg_readonly_hint'   => '🔒 DL3EL Database — Names are read-only',
	'tg_new_name'        => 'Enter new name',
	'custom_tgs_title'   => 'Custom Entries',
	'tg_add_entry'       => 'Add new entry',
	'col_name'           => 'Name',
	'tg_original_label'  => 'Original',
	'module_label'       => 'Module',
],
];

$lang = $allStrings[$language] ?? $allStrings['de'];
