<?php
// Pfade
$configsDir    = dirname(__FILE__);
$mobileRoot    = dirname($configsDir);
$dashboardRoot = dirname($mobileRoot);

// DL3EL Dashboard config.php (optional)
$dashCfgFile = $dashboardRoot . '/include/config.php';
if (file_exists($dashCfgFile)) include_once $dashCfgFile;

// App-Metadaten
include_once $configsDir . '/mobile_config.php';

// Einstellungen aus JSON
$settingsFile = $configsDir . '/mobile_settings.json';
$settings = ['language'=>'','theme'=>'system'];
if (file_exists($settingsFile)) {
    $d = json_decode(file_get_contents($settingsFile), true);
    if (is_array($d)) $settings = array_merge($settings, $d);
}
$language = $settings['language'];
$theme    = $settings['theme'];

// Hilfsfunktion
if (!function_exists('cdef')) {
    function cdef($n, $d=null){ return defined($n)?constant($n):$d; }
}

// SVXLink Pfade
$svxConfPath  = rtrim(cdef('SVXCONFPATH','/etc/svxlink'),'/').'/'.cdef('SVXCONFIG','svxlink.conf');
$svxLogPath   = rtrim(cdef('SVXLOGPATH','/var/log'),'/');
$svxLogPrefix = cdef('SVXLOGPREFIX','svxlink');
$svxLogFile   = '';
foreach([
    "$svxLogPath/$svxLogPrefix.log",
    "$svxLogPath/$svxLogPrefix",
    "$svxLogPath/$svxLogPrefix/$svxLogPrefix.log",
    "$svxLogPath/$svxLogPrefix/$svxLogPrefix",
] as $c){ if(is_file($c)){$svxLogFile=$c;break;} }
$dtmfFile    = cdef('DTMF_FILE','/tmp/dtmf_svx');
$buttonsFile = $dashboardRoot.'/include/buttons.php';
$cpuTempFile = '/sys/class/thermal/thermal_zone0/temp';
$memInfoFile = '/proc/meminfo';
$uptimeFile  = '/proc/uptime';

// App-Metadaten
$appVersion = cdef('APP_VERSION','1.0');
$appYear    = cdef('APP_YEAR',date('Y'));
$appAuthor  = cdef('APP_AUTHOR','OE1SXM');
$byline     = "SvxLink Mobile Dashboard Ver {$appVersion} © by {$appAuthor} {$appYear}";

// Alle Sprachstrings (beide Sprachen — für JS CFG.allStrings)
$allStrings = [
'de' => [
    'badge_connected'    => 'VERBUNDEN',
    'badge_disconnected' => 'GETRENNT',
    'badge_connecting'   => 'VERBINDUNG…',
    'radio'              => 'RADIO',
    'active_tg'          => 'TG AKTIV',
    'talker'             => 'SPRECHER',
    'activity_title'     => 'SVXReflector Aktivität',
    'reflector_status'   => 'Reflector Status',
    'host'               => 'Host',
    'callsign_lbl'       => 'Anmeldename',
    'connection'         => 'Verbindung',
    'connected_dot'      => '● Verbunden',
    'default_tg'         => 'Default TG',
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
    'theme_system'       => 'System',
    'theme_dark'         => 'Dunkel',
    'theme_light'        => 'Hell',
    'btn_el'             => 'EL trennen',
    'btn_tg'             => 'TG trennen',
    'toast_sending'      => 'Sende: ',
    'toast_sent'         => '✓ DTMF gesendet',
    'toast_invalid_tg'   => 'Ungültige TG-Nummer',
    'toast_last_updated' => '↻ Letzte Aktualisierung: ',
    'no_activity_short'  => 'Noch keine Aktivität im Log',
    'talkgroup'          => 'Talk Group',
],
'en' => [
    'badge_connected'    => 'CONNECTED',
    'badge_disconnected' => 'DISCONNECTED',
    'badge_connecting'   => 'CONNECTING…',
    'radio'              => 'RADIO',
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
    'theme_system'       => 'System',
    'theme_dark'         => 'Dark',
    'theme_light'        => 'Light',
    'btn_el'             => 'Disconnect EL',
    'btn_tg'             => 'Disconnect TG',
    'toast_sending'      => 'Sending: ',
    'toast_sent'         => '✓ DTMF sent',
    'toast_invalid_tg'   => 'Invalid TG number',
    'toast_last_updated' => '↻ Last updated: ',
    'no_activity_short'  => 'No activity in log yet',
    'talkgroup'          => 'Talk Group',
],
];

// Aktuelle Sprache — fallback auf 'de'
$lang = $allStrings[$language] ?? $allStrings['de'];
