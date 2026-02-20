<?php
session_start();
require_once __DIR__ . '/configs/config.php';

// Erststart?
if (empty($language)) { header('Location: setup.php'); exit; }

// SVXLink Konfiguration
$svxconfig = file_exists($svxConfPath) ? parse_ini_file($svxConfPath,true,INI_SCANNER_RAW) : [];
$callsign  = $svxconfig['ReflectorLogic']['CALLSIGN']   ?? 'NOCALL';
$reflHost  = $svxconfig['ReflectorLogic']['HOSTS']      ?? ($svxconfig['ReflectorLogic']['DNS_DOMAIN'] ?? '');
if (strpos($reflHost,',')!==false) $reflHost=trim(explode(',',$reflHost)[0]);
$defaultTG = $svxconfig['ReflectorLogic']['DEFAULT_TG'] ?? '—';
$monTGs    = array_values(array_filter(array_map('trim',explode(',',$svxconfig['ReflectorLogic']['MONITOR_TGS']??$defaultTG))));

// DTMF Schnellbefehle
$dtmfBtns = [];
if (is_file($dashCfgFile)) {
    $txt = preg_replace('!/\*.*?\*/!s','',file_get_contents($dashCfgFile));
    $clean='';
    foreach(preg_split("/\r\n|\r|\n/",$txt) as $line){
        if(!preg_match('/^(\/\/|#)/',ltrim($line))) $clean.=$line."\n";
    }
    if(preg_match_all('/define\s*\(\s*[\"\'](?:KEY|TG)\d+[\"\']\s*,\s*array\s*\(\s*[\"\']([^\"\']+)[\"\']\s*,\s*[\"\']([^\"\']+)[\"\']\s*,\s*[\"\']([^\"\']+)[\"\']\s*\)\s*\)\s*;/i',$clean,$m,PREG_SET_ORDER))
        foreach($m as $r) $dtmfBtns[]=['l'=>$r[1],'d'=>$r[2],'c'=>$r[3]];
}
if(!$dtmfBtns) $dtmfBtns=[
    ['l'=>'OE 232','d'=>'*91232#','c'=>'orange'],
    ['l'=>'VIE 2321','d'=>'*912321#','c'=>'orange'],
    ['l'=>'VIE L 23211','d'=>'*9123211#','c'=>'orange'],
    ['l'=>'Parrot','d'=>'*919990#','c'=>'green'],
    ['l'=>$lang['btn_el'],'d'=>'##','c'=>'red'],
    ['l'=>$lang['btn_tg'],'d'=>'*9#','c'=>'red'],
];

// Hardware
$t=$_t=@file_get_contents($cpuTempFile); $tv=$t?round($t/1000):0;
$mem=@file_get_contents($memInfoFile); $mp='—';
if($mem&&preg_match('/MemTotal:\s+(\d+)/',$mem,$mt)&&preg_match('/MemAvailable:\s+(\d+)/',$mem,$ma))
    $mp=round((1-$ma[1]/$mt[1])*100).'%';
$up=@file_get_contents($uptimeFile); $us='—';
if($up){$s2=floatval(explode(' ',$up)[0]);$us=floor($s2/86400).'d '.floor(($s2%86400)/3600).'h '.floor(($s2%3600)/60).'m';}
$la=sys_getloadavg(); $nc=max(1,(int)@shell_exec('nproc 2>/dev/null'));
$df=@disk_free_space('/'); $dt2=@disk_total_space('/');

$themeClass = $theme==='dark'?'theme-dark':($theme==='light'?'theme-light':'');
$htmlLang   = $language==='en'?'en':'de';
?>
<!DOCTYPE html>
<html lang="<?=$htmlLang?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#0d1117">
<title><?=htmlspecialchars($callsign)?> Mobile</title>
<link rel="stylesheet" href="css/app.css">
<link rel="icon" href="../images/favicon.ico" type="image/x-icon">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" href="images/icon-192.png">
</head>
<body class="<?=$themeClass?>">

<!-- SETTINGS MODAL -->
<div id="settings-overlay" class="settings-overlay" onclick="closeSettings(event)">
  <div class="settings-modal">
    <div class="settings-header">
      <span class="settings-title" data-i18n="settings_title"><?=$lang['settings_title']?></span>
      <button class="settings-close-btn" onclick="closeSettings()">✕</button>
    </div>
    <div class="settings-section">
      <div class="settings-label" data-i18n="settings_language"><?=$lang['settings_language']?></div>
      <div class="settings-row">
        <button class="sopt-btn <?=$language==='en'?'active':''?>" onclick="setSetting('lang','en')">🇬🇧 English</button>
        <button class="sopt-btn <?=$language==='de'?'active':''?>" onclick="setSetting('lang','de')">🇩🇪 Deutsch</button>
      </div>
    </div>
    <div class="settings-section">
      <div class="settings-label" data-i18n="settings_theme"><?=$lang['settings_theme']?></div>
      <div class="settings-row">
        <button class="sopt-btn <?=$theme==='system'?'active':''?>" onclick="setSetting('theme','system')">⚙ <span data-i18n="theme_system"><?=$lang['theme_system']?></span></button>
        <button class="sopt-btn <?=$theme==='dark'?'active':''?>"   onclick="setSetting('theme','dark')">🌙 <span data-i18n="theme_dark"><?=$lang['theme_dark']?></span></button>
        <button class="sopt-btn <?=$theme==='light'?'active':''?>"  onclick="setSetting('theme','light')">☀ <span data-i18n="theme_light"><?=$lang['theme_light']?></span></button>
      </div>
    </div>
  </div>
</div>

<!-- HEADER -->
<header>
  <div class="h-inner">
    <div class="h-left">
      <img src="../images/svxlink.ico" class="logo" onerror="this.style.display='none'">
      <div class="h-text">
        <div class="cs"><?=htmlspecialchars($callsign)?></div>
        <div class="rf"><?=htmlspecialchars($reflHost)?></div>
      </div>
    </div>
    <div class="badge connected" id="badge" onclick="openSettings()" style="cursor:pointer" title="Settings">
      <span class="bdot"></span><span id="btext" data-i18n="badge_connected"><?=$lang['badge_connected']?></span>
    </div>
  </div>
</header>

<main>

<!-- DASHBOARD -->
<section class="page active" id="p-dashboard">
  <div class="livebar">
    <div class="lc"><div class="ll" data-i18n="radio"><?=$lang['radio']?></div><div class="lv" id="ls-radio">—</div></div>
    <div class="ls"></div>
    <div class="lc"><div class="ll" data-i18n="active_tg"><?=$lang['active_tg']?></div><div class="lv blue" id="ls-tg">—</div></div>
    <div class="ls"></div>
    <div class="lc"><div class="ll" data-i18n="talker"><?=$lang['talker']?></div><div class="lv grn" id="ls-talker">—</div></div>
  </div>
  <div class="card">
    <div class="ch"><div class="ct"><span class="d grn"></span><span data-i18n="activity_title"><?=$lang['activity_title']?></span></div>
      <span class="ltag" id="live-acts" style="display:none;">● <span id="liveTag" class="live-tag" style="display:none;">LIVE</span></span></div>
    <div id="alist" class="alist"><div class="empty" data-i18n="waiting"><?=$lang['waiting']?></div></div>
  </div>
  <div class="card">
    <div class="ch"><div class="ct"><span class="d blue" id="ref-dot"></span><span data-i18n="reflector_status"><?=$lang['reflector_status']?></span></div></div>
    <div class="rows">
      <div class="row"><span class="rl" data-i18n="host"><?=$lang['host']?></span><span class="rv"><?=htmlspecialchars($reflHost)?></span></div>
      <div class="row"><span class="rl" data-i18n="callsign_lbl"><?=$lang['callsign_lbl']?></span><span class="rv blue"><?=htmlspecialchars($callsign)?></span></div>
      <div class="row"><span class="rl" data-i18n="connection"><?=$lang['connection']?></span><span class="rv ok" id="rconn" data-i18n-live="connected_dot"><?=$lang['connected_dot']?></span></div>
      <div class="row"><span class="rl" data-i18n="default_tg"><?=$lang['default_tg']?></span><span class="rv blue"><?=htmlspecialchars($defaultTG)?></span></div>
      <div class="row"><span class="rl" data-i18n="monitor_tgs"><?=$lang['monitor_tgs']?></span><span class="rv"><?=htmlspecialchars(implode(', ',$monTGs))?></span></div>
    </div>
  </div>
  <div class="card">
    <div class="ch"><div class="ct"><span class="d org"></span><span data-i18n="hardware_info"><?=$lang['hardware_info']?></span></div></div>
    <div class="hwg">
      <div class="hw"><span class="hl" data-i18n="hostname"><?=$lang['hostname']?></span><span class="hv"><?=htmlspecialchars(gethostname())?></span></div>
      <div class="hw"><span class="hl" data-i18n="uptime"><?=$lang['uptime']?></span><span class="hv" id="hw-up"><?=htmlspecialchars($us)?></span></div>
      <div class="hw"><span class="hl" data-i18n="cpu_load"><?=$lang['cpu_load']?></span><span class="hv" id="hw-cpu"><?=round($la[0]*100/$nc,1).'%'?></span></div>
      <div class="hw"><span class="hl" data-i18n="cpu_temp"><?=$lang['cpu_temp']?></span><span class="hv <?=$tv>70?'red':'ok'?>" id="hw-tmp"><?=$tv>0?$tv.'°C':'—'?></span></div>
      <div class="hw"><span class="hl" data-i18n="memory"><?=$lang['memory']?></span><span class="hv" id="hw-mem"><?=htmlspecialchars($mp)?></span></div>
      <div class="hw"><span class="hl" data-i18n="disk"><?=$lang['disk']?></span><span class="hv"><?=($dt2&&$df!==false)?round((1-$df/$dt2)*100).'%':'—'?></span></div>
    </div>
  </div>
  <a class="backlink" href="../index.php" data-i18n="open_dashboard"><?=$lang['open_dashboard']?></a>
  <div class="byline"><?=htmlspecialchars($byline)?></div>
</section>

<!-- AKTIVITÄT -->
<section class="page" id="p-activity">
  <div class="card">
    <div class="ch"><div class="ct"><span class="d grn"></span><span data-i18n="recent_talkers"><?=$lang['recent_talkers']?></span></div><span class="ltag">● LIVE</span></div>
    <div id="alist2" class="alist"><div class="empty" data-i18n="waiting"><?=$lang['waiting']?></div></div>
  </div>
  <p class="note" id="lu-note" data-i18n="realtime_sse"><?=$lang['realtime_sse']?></p>
</section>

<!-- TALK GROUPS -->
<section class="page" id="p-tg">
  <div class="card">
    <div class="ch"><div class="ct"><span class="d blue"></span><span data-i18n="monitor_tgs_title"><?=$lang['monitor_tgs_title']?></span></div></div>
    <div class="tglist">
      <?php foreach($monTGs as $tg): ?>
      <div class="tgrow" onclick="activateTG('<?=htmlspecialchars($tg)?>')">
        <span class="tgn"><?=htmlspecialchars($tg)?></span>
        <span class="tgname"><span data-i18n="talkgroup"><?=$lang['talkgroup']?></span> <?=htmlspecialchars($tg)?></span>
        <span class="tgarr">›</span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="ch"><div class="ct" data-i18n="activate_manually"><?=$lang['activate_manually']?></div></div>
    <div class="tgman">
      <input id="tgi" class="txti" type="number" inputmode="numeric" placeholder="<?=$lang['tg_placeholder']?>" data-i18n-ph="tg_placeholder">
      <button class="btn blue" onclick="activateTGMan()" data-i18n="activate_btn"><?=$lang['activate_btn']?></button>
    </div>
  </div>
  <div class="card">
    <div class="ch"><div class="ct" data-i18n="quick_actions"><?=$lang['quick_actions']?></div></div>
    <div class="arow">
      <button class="btn red" onclick="sendDTMF('*9#')" data-i18n="disconnect_tg"><?=$lang['disconnect_tg']?></button>
      <button class="btn red" onclick="sendDTMF('##')"  data-i18n="disconnect_el"><?=$lang['disconnect_el']?></button>
    </div>
  </div>
</section>

<!-- DTMF -->
<section class="page" id="p-dtmf">
  <div class="card">
    <div class="ch"><div class="ct" data-i18n="dtmf_keypad"><?=$lang['dtmf_keypad']?></div></div>
    <div class="ddisp" id="ddisp">—</div>
    <div class="dkeys">
      <?php foreach(['1','2','3','*','4','5','6','0','7','8','9','#'] as $k): ?>
      <button class="dkey" onclick="kp('<?=$k?>')"><?=$k?></button>
      <?php endforeach; ?>
    </div>
    <div class="dact">
      <button class="btn gray" onclick="kc()" data-i18n="clear_btn"><?=$lang['clear_btn']?></button>
      <button class="btn blue" onclick="ks()"  data-i18n="send_btn"><?=$lang['send_btn']?></button>
    </div>
  </div>
  <div class="card">
    <div class="ch"><div class="ct" data-i18n="quick_commands"><?=$lang['quick_commands']?></div></div>
    <div class="qgrid">
      <?php foreach($dtmfBtns as $b):
        $c=in_array($b['c'],['orange','green','red','blue','purple'])?$b['c']:'blue'; ?>
      <button class="btn <?=$c?>" onclick="sendDTMF('<?=htmlspecialchars($b['d'])?>')"><?=htmlspecialchars($b['l'])?></button>
      <?php endforeach; ?>
    </div>
  </div>
</section>

</main>

<div id="toast" class="toast"></div>

<nav>
  <button class="nb active" data-p="dashboard" onclick="go('dashboard',this)"><span>📡</span><span data-i18n="nav_dashboard"><?=$lang['nav_dashboard']?></span></button>
  <button class="nb" data-p="activity"  onclick="go('activity',this)"><span>📶</span><span data-i18n="nav_activity"><?=$lang['nav_activity']?></span></button>
  <button class="nb" data-p="tg"        onclick="go('tg',this)"><span>🗣</span><span data-i18n="nav_tg"><?=$lang['nav_tg']?></span></button>
  <button class="nb" data-p="dtmf"      onclick="go('dtmf',this)"><span>⌨</span><span data-i18n="nav_dtmf"><?=$lang['nav_dtmf']?></span></button>
</nav>

<script>
const CFG = {
    defaultTG: <?=json_encode($defaultTG)?>,
    callsign:  <?=json_encode($callsign)?>,
    lang:      <?=json_encode($language)?>,
    theme:     <?=json_encode($theme)?>,
    // Active strings for JS
    str: <?=json_encode($allStrings[$language] ?? $allStrings['de'])?>,
    // Both languages for live switching
    allStrings: <?=json_encode($allStrings)?>
};
</script>
<script src="js/app.js"></script>
</body>
</html>
