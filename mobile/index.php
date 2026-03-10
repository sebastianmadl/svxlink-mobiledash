<?php
session_start();
require_once __DIR__ . '/settings.php';
if (empty($language)) { header('Location: setup.php'); exit; }
$svxconfig = file_exists($svxConfPath) ? parse_ini_file($svxConfPath,true,INI_SCANNER_RAW) : [];
$callsign  = $svxconfig['ReflectorLogic']['CALLSIGN']   ?? 'NOCALL';
$reflHost  = $svxconfig['ReflectorLogic']['HOSTS']      ?? ($svxconfig['ReflectorLogic']['DNS_DOMAIN'] ?? '');
if (strpos($reflHost,',')!==false) $reflHost=trim(explode(',',$reflHost)[0]);
$defaultTG = $svxconfig['ReflectorLogic']['DEFAULT_TG'] ?? '—';
$monTGs    = array_values(array_filter(array_map('trim',explode(',',$svxconfig['ReflectorLogic']['MONITOR_TGS']??$defaultTG))));
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
    <div class="settings-section">
      <div class="settings-label" data-i18n="editors_label"><?=$lang['editors_label']?></div>
      <div class="settings-row">
        <button class="sopt-btn" style="width:100%;text-align:left;justify-content:space-between" onclick="openTgEditor()">
          <span>
            <span class="d <?=$tgSource!=='off'?'grn':'rd'?>" id="tge-dot" style="display:inline-block;margin-right:8px;vertical-align:middle"></span>
            <span data-i18n="tg_rename_edit"><?=$lang['tg_rename_edit']?></span>
          </span>
          <span style="color:var(--mu)">›</span>
        </button>
      </div>
    </div>
    <div style="text-align:center;padding:12px 0 4px;font-size:10px;color:var(--di);font-family:var(--cond);letter-spacing:.4px"><?=htmlspecialchars($byline)?></div>
  </div>
</div>

<!-- TG RENAME EDITOR -->
<div id="tge-overlay" class="tge-overlay">
  <div class="tge-panel">
    <div class="tge-header">
      <button class="tge-back" onclick="closeTgEditor()">‹</button>
      <span class="tge-title" data-i18n="tg_rename_edit"><?=$lang['tg_rename_edit']?></span>
      <button class="tge-close" onclick="closeTgEditor()">✕</button>
    </div>

    <!-- Scrollable body -->
    <div class="tge-body">

      <!-- Toggle -->
      <div class="tge-toggle-row">
        <span class="tge-toggle-label" data-i18n="tg_rename_label"><?=$lang['tg_rename_label']?></span>
        <button class="tge-toggle <?=$tgSource!=='off'?'on':''?>" id="tgr-toggle" onclick="toggleTgRename()">
          <span class="tge-toggle-knob"></span>
          <span class="tge-toggle-text" id="tgr-toggle-text"><?=$tgSource!=='off'?$lang['tg_rename_on']:$lang['tg_rename_off']?></span>
        </button>
      </div>

      <!-- Modus — immer sichtbar -->
      <div class="settings-section" style="padding:10px 0 6px">
        <div class="settings-label" style="margin-bottom:8px">Modus</div>
        <div class="settings-row">
          <button class="sopt-btn <?=$tgSource==='custom'?'active':''?>" id="tgsrc-custom" onclick="setTgSource('custom')" data-i18n="tg_src_custom"><?=$lang['tg_src_custom']?></button>
          <button class="sopt-btn <?=$tgSource==='dl3el'?'active':''?>"  id="tgsrc-dl3el"  onclick="setTgSource('dl3el')"  data-i18n="tg_src_dl3el"><?=$lang['tg_src_dl3el']?></button>
          <button class="sopt-btn <?=$tgSource==='mixed'?'active':''?>"  id="tgsrc-mixed"  onclick="setTgSource('mixed')"  data-i18n="tg_src_mixed"><?=$lang['tg_src_mixed']?></button>
        </div>
      </div>

      <!-- TG Liste — scrollbar, nimmt restlichen Platz -->
      <div class="settings-label" style="margin:10px 0 6px" data-i18n="monitor_tgs_title"><?=$lang['monitor_tgs_title']?></div>
      <div class="tge-list" id="tge-list">
        <?php foreach($monTGs as $tg): ?>
        <div class="tge-row" onclick="selectTg('<?=htmlspecialchars($tg)?>')" id="tge-row-<?=htmlspecialchars($tg)?>">
          <span class="tge-num"><?=htmlspecialchars($tg)?></span>
          <span class="tge-name" id="tge-name-<?=htmlspecialchars($tg)?>"><?=htmlspecialchars(tgName($tg))?></span>
          <button class="tge-del-btn" id="tge-del-<?=htmlspecialchars($tg)?>"
            style="margin-left:auto;background:none;border:none;color:var(--red);font-size:16px;cursor:pointer;padding:0 6px;flex-shrink:0;display:<?=isset($tgNames[$tg])?'':'none'?>"
            onclick="event.stopPropagation();deleteTgMonEntry('<?=htmlspecialchars($tg)?>')" title="Custom-Namen entfernen">✕</button>
          <span class="tge-arr" id="tge-arr-<?=htmlspecialchars($tg)?>" style="color:var(--di);font-size:18px;flex-shrink:0;<?=isset($tgNames[$tg])?'display:none':''?>">›</span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Eigene Einträge Block — gleicher Stil wie Monitor-Block, nur non-monitored TGs -->
      <?php
        $nonMonCustom = array_diff_key($tgNames, array_flip($monTGs));
      ?>
      <div id="tge-custom-block" style="<?=in_array($tgSource,['custom','mixed'])?'':'display:none'?>">
        <div style="height:1px;background:var(--b1);margin:16px 0 12px"></div>
        <div class="settings-label" style="margin:0 0 6px" data-i18n="custom_tgs_title"><?=$lang['custom_tgs_title']?></div>
        <div class="tge-list" id="tgs-inline-list">
          <?php if(empty($nonMonCustom)): ?>
          <div style="padding:10px;color:var(--mu);font-size:12px;text-align:center">—</div>
          <?php else: foreach($nonMonCustom as $tg => $name): ?>
          <div class="tge-row" style="padding:10px 14px">
            <span class="tge-num"><?=htmlspecialchars($tg)?></span>
            <span style="flex:1;font-family:var(--cond);font-size:13px;color:var(--fg)"><?=htmlspecialchars($name)?></span>
            <button onclick="deleteTgEntry('<?=htmlspecialchars($tg)?>')" style="margin-left:8px;background:none;border:none;color:var(--red);font-size:16px;cursor:pointer;padding:0 6px;flex-shrink:0">✕</button>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Custom Einträge Editor — nur bei Custom und Mixed -->
      <div class="tge-editor" id="tge-custom-add" style="<?=in_array($tgSource,['custom','mixed'])?'':'display:none'?>">
        <button class="btn blue" style="width:100%;margin-top:4px;font-size:15px;letter-spacing:1px" onclick="openAddPopup()">
          ➕&nbsp; <span data-i18n="tg_add_entry"><?=$lang['tg_add_entry']?></span>
        </button>
        <!-- Hinweis auf direkte Bearbeitung -->
        <div style="margin-top:14px;padding:10px 12px;background:rgba(96,165,250,.07);border:1px solid var(--blu-bd);border-radius:8px;display:flex;gap:8px;align-items:flex-start">
          <span style="color:var(--blu);font-size:14px;flex-shrink:0">ℹ</span>
          <span id="tge-json-hint" style="font-size:11px;color:var(--mu);font-family:var(--cond);letter-spacing:.2px;line-height:1.5" data-i18n="tg_json_hint"><?=$lang['tg_json_hint']?></span>
        </div>
      </div>

    </div><!-- end .tge-body -->
  </div>
</div>

<!-- TG NAME EDITOR POPUP (Bottom Sheet) -->
<div id="tge-popup-overlay" class="tge-popup-overlay" onclick="closeTgPopup(event)">
  <div class="tge-popup">
    <div class="tge-popup-header">
      <span class="tge-popup-tg" id="tge-popup-tg-label">TG —</span>
      <button class="tge-popup-close" onclick="closeTgPopup()">✕</button>
    </div>
    <div class="tge-popup-body">
      <!-- DL3EL readonly notice (hidden by default) -->
      <div id="tge-popup-readonly" class="tge-popup-readonly" style="display:none" data-i18n="tg_readonly_hint"><?=$lang['tg_readonly_hint']?></div>
      <!-- Editable section (hidden in dl3el) -->
      <div id="tge-popup-edit">
        <div class="tge-popup-hint" data-i18n="tg_new_name"><?=$lang['tg_new_name']?></div>
        <input type="text" class="txti" id="tge-popup-input" placeholder="Name..." style="width:100%;margin-bottom:12px">
        <button class="btn blue" style="width:100%" onclick="saveTgPopup()" data-i18n="tg_rename_save"><?=$lang['tg_rename_save']?></button>
      </div>
    </div>
  </div>
</div>

<!-- TG ADD ENTRY POPUP (Bottom Sheet) -->
<div id="tge-add-overlay" class="tge-popup-overlay" onclick="closeAddPopup(event)">
  <div class="tge-popup">
    <div class="tge-popup-header">
      <span class="tge-popup-tg" data-i18n="tg_add_entry"><?=$lang['tg_add_entry']?></span>
      <button class="tge-popup-close" onclick="closeAddPopup()">✕</button>
    </div>
    <div class="tge-popup-body">
      <div class="tge-popup-hint" data-i18n="tg_src_editor_hint"><?=$lang['tg_src_editor_hint']?></div>
      <div style="display:flex;gap:8px;margin-bottom:12px">
        <input type="text" class="txti" id="tgs-add-tg" placeholder="TG Nr." style="width:100px;flex-shrink:0" inputmode="numeric">
        <input type="text" class="txti" id="tgs-add-name" placeholder="Name...">
      </div>
      <button class="btn blue" style="width:100%" onclick="saveAddPopup()" data-i18n="tg_rename_save"><?=$lang['tg_rename_save']?></button>
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
    <div class="lc"><div class="ll" data-i18n="radio"><?=$lang['radio']?></div><div class="lv blue" id="ls-radio">—</div></div>
    <div class="ls"></div>
    <div class="lc"><div class="ll" data-i18n="active_tg"><?=$lang['active_tg']?></div><div class="lv blue" id="ls-tg">—</div></div>
    <div class="ls"></div>
    <div class="lc"><div class="ll" data-i18n="talker"><?=$lang['talker']?></div><div class="lv blue" id="ls-talker">—</div></div>
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
        <span class="tgname"><?=htmlspecialchars(tgName($tg))?></span>
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
      <button class="btn gray" onclick="openTgEditor()">
        <span class="d <?=$tgSource!=='off'?'grn':'rd'?>" id="tge-dot2" style="display:inline-block;margin-right:5px;vertical-align:middle"></span>
        <span data-i18n="tg_source_edit"><?=$lang['tg_source_edit']?></span>
      </button>
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

<!-- Global Byline — sichtbar wenn man ans Ende scrollt -->
<div class="byline-footer"><?=htmlspecialchars($byline)?></div>

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
    tgNames:        <?=json_encode($tgNames ?? [])?>,
    tgSource:        <?=json_encode($tgSource)?>,
    callerTgNames:   <?=json_encode($callerTgNames ?? [])?>,
    monTGs:          <?=json_encode($monTGs)?>,
    callsign:  <?=json_encode($callsign)?>,
    lang:      <?=json_encode($language)?>,
    theme:     <?=json_encode($theme)?>,
    str: <?=json_encode($allStrings[$language] ?? $allStrings['de'])?>,
    allStrings: <?=json_encode($allStrings)?>
};
</script>
<script src="js/app.js"></script>
</body>
</html>
