<?php
session_start();

// ── Dynamische Pfad-Erkennung ─────────────────────────────────────────────────
// __DIR__ = Verzeichnis dieser Datei (z.B. /var/www/html/thomas/mobile)
// dirname(__DIR__) = Dashboard-Root   (z.B. /var/www/html/thomas)
$dashboardRoot = dirname(__DIR__);

$svxConfPath = '/etc/svxlink/svxlink.conf';
$svxconfig = file_exists($svxConfPath) ? parse_ini_file($svxConfPath, true, INI_SCANNER_RAW) : [];

$callsign   = $svxconfig['ReflectorLogic']['CALLSIGN']    ?? 'NOCALL';
$reflHost   = $svxconfig['ReflectorLogic']['HOSTS']       ?? ($svxconfig['ReflectorLogic']['DNS_DOMAIN'] ?? '');
if (strpos($reflHost,',') !== false) $reflHost = trim(explode(',',$reflHost)[0]);
$defaultTG  = $svxconfig['ReflectorLogic']['DEFAULT_TG']  ?? '—';
$monTGs     = array_values(array_filter(array_map('trim', explode(',', $svxconfig['ReflectorLogic']['MONITOR_TGS'] ?? $defaultTG))));

// ── DTMF/TG Schnellbefehle aus config.php des Dashboards ─────────────────────
$dtmfBtns = [];
$cfgFile = $dashboardRoot . '/include/config.php';
if (is_file($cfgFile)) {
    $txt = file_get_contents($cfgFile);
    $txt = preg_replace('!/\*.*?\*/!s', '', $txt);
    $lines = preg_split("/\r\n|\r|\n/", $txt);
    $clean = "";
    foreach ($lines as $line) {
        $t = ltrim($line);
        if (preg_match('/^(\/\/|#)/', $t)) continue;
        $clean .= $line . "\n";
    }
    if (preg_match_all('/define\s*\(\s*[\"\'](?:KEY|TG)\d+[\"\']\s*,\s*array\s*\(\s*[\"\']([^\"\']+)[\"\']\s*,\s*[\"\']([^\"\']+)[\"\']\s*,\s*[\"\']([^\"\']+)[\"\']\s*\)\s*\)\s*;/i', $clean, $m, PREG_SET_ORDER)) {
        foreach ($m as $r) {
            $dtmfBtns[] = ['l'=>$r[1],'d'=>$r[2],'c'=>$r[3]];
        }
    }
}
if (!$dtmfBtns) $dtmfBtns = [
    ['l'=>'OE 232','d'=>'*91232#','c'=>'orange'],['l'=>'VIE 2321','d'=>'*912321#','c'=>'orange'],
    ['l'=>'VIE L 23211','d'=>'*9123211#','c'=>'orange'],['l'=>'Parrot','d'=>'*919990#','c'=>'green'],
    ['l'=>'EL trennen','d'=>'##','c'=>'red'],['l'=>'TG trennen','d'=>'*9#','c'=>'red'],
];

// ── Hardware Info ─────────────────────────────────────────────────────────────
$t   = @file_get_contents('/sys/class/thermal/thermal_zone0/temp');
$tv  = $t ? round($t/1000) : 0;
$mem = @file_get_contents('/proc/meminfo');
$mp  = '—';
if ($mem && preg_match('/MemTotal:\s+(\d+)/',$mem,$mt) && preg_match('/MemAvailable:\s+(\d+)/',$mem,$ma))
    $mp = round((1-$ma[1]/$mt[1])*100).'%';
$up = @file_get_contents('/proc/uptime');
$us = '—';
if ($up){ $s=floatval(explode(' ',$up)[0]); $us=floor($s/86400).'d '.floor(($s%86400)/3600).'h '.floor(($s%3600)/60).'m'; }
$la  = sys_getloadavg();
$nc  = max(1,(int)@shell_exec('nproc 2>/dev/null'));
$df  = @disk_free_space('/'); $dt2 = @disk_total_space('/');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#0d1117">
<title><?=htmlspecialchars($callsign)?> Mobile</title>
<!-- RELATIVE Pfade – funktionieren egal in welchem Verzeichnis das Dashboard liegt -->
<link rel="stylesheet" href="css/app.css">
<link rel="icon" href="../images/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="../images/favicon.ico" type="image/x-icon">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" href="images/icon-192.png">
</head>
<body>

<header>
  <div class="h-inner">
    <div class="h-left">
      <img src="../images/svxlink.ico" class="logo">
      <div class="h-text">
        <div class="cs"><?=htmlspecialchars($callsign)?></div>
        <div class="rf"><?=htmlspecialchars($reflHost)?></div>
      </div>
    </div>
    <div class="badge connected" id="badge">
      <span class="bdot" id="bdot"></span><span id="btext">VERBUNDEN</span>
    </div>
  </div>
</header>

<main>

<!-- DASHBOARD -->
<section class="page active" id="p-dashboard">

  <div class="livebar">
    <div class="lc"><div class="ll">RADIO</div><div class="lv" id="ls-radio">—</div></div>
    <div class="ls"></div>
    <div class="lc"><div class="ll">TG AKTIV</div><div class="lv blue" id="ls-tg">—</div></div>
    <div class="ls"></div>
    <div class="lc"><div class="ll">SPRECHER</div><div class="lv grn" id="ls-talker">—</div></div>
  </div>

  <div class="card">
    <div class="ch"><div class="ct"><span class="d grn"></span>SVXReflector Aktivität</div><span class="ltag" id="live-acts" style="display:none;">● <span id="liveTag" class="live-tag" style="display:none;">LIVE</span></span></div>
    <div id="alist" class="alist"><div class="empty">Warte auf Daten…</div></div>
  </div>

  <div class="card">
    <div class="ch"><div class="ct"><span class="d blue" id="ref-dot"></span>Reflector Status</div></div>
    <div class="rows">
      <div class="row"><span class="rl">Host</span><span class="rv"><?=htmlspecialchars($reflHost)?></span></div>
      <div class="row"><span class="rl">Anmeldename</span><span class="rv blue"><?=htmlspecialchars($callsign)?></span></div>
      <div class="row"><span class="rl">Verbindung</span><span class="rv ok" id="rconn">● Verbunden</span></div>
      <div class="row"><span class="rl">Default TG</span><span class="rv blue"><?=htmlspecialchars($defaultTG)?></span></div>
      <div class="row"><span class="rl">Monitor TGs</span><span class="rv"><?=htmlspecialchars(implode(', ',$monTGs))?></span></div>
    </div>
  </div>

  <div class="card">
    <div class="ch"><div class="ct"><span class="d org"></span>Hardware Info</div></div>
    <div class="hwg">
      <div class="hw"><span class="hl">Hostname</span><span class="hv"><?=htmlspecialchars(gethostname())?></span></div>
      <div class="hw"><span class="hl">Uptime</span><span class="hv" id="hw-up"><?=htmlspecialchars($us)?></span></div>
      <div class="hw"><span class="hl">CPU Load</span><span class="hv" id="hw-cpu"><?=round($la[0]*100/$nc,1).'%'?></span></div>
      <div class="hw"><span class="hl">CPU Temp</span><span class="hv <?=$tv>70?'red':'ok'?>" id="hw-tmp"><?=$tv>0?$tv.'°C':'—'?></span></div>
      <div class="hw"><span class="hl">Memory</span><span class="hv" id="hw-mem"><?=htmlspecialchars($mp)?></span></div>
      <div class="hw"><span class="hl">Disk</span><span class="hv"><?=($dt2&&$df!==false)?round((1-$df/$dt2)*100).'%':'—'?></span></div>
    </div>
  </div>

  <a class="backlink" href="../index.php">↗ Vollständiges Dashboard öffnen</a>
  <div class="byline">SvxLink Mobile Dashboard Ver 1.0 © by OE1SXM 2026</div>
</section>

<!-- AKTIVITÄT -->
<section class="page" id="p-activity">
  <div class="card">
    <div class="ch"><div class="ct"><span class="d grn"></span>Letzte Sprecher</div><span class="ltag">● LIVE</span></div>
    <div id="alist2" class="alist"><div class="empty">Warte auf Daten…</div></div>
  </div>
  <p class="note" id="lu-note">Echtzeit via Server-Sent Events</p>
</section>

<!-- TALK GROUPS -->
<section class="page" id="p-tg">
  <div class="card">
    <div class="ch"><div class="ct"><span class="d blue"></span>Monitor TGs</div></div>
    <div class="tglist">
      <?php foreach($monTGs as $tg): ?>
      <div class="tgrow" onclick="activateTG('<?=htmlspecialchars($tg)?>')">
        <span class="tgn"><?=htmlspecialchars($tg)?></span>
        <span class="tgname">Talk Group <?=htmlspecialchars($tg)?></span>
        <span class="tgarr">›</span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="ch"><div class="ct">Manuell aktivieren</div></div>
    <div class="tgman">
      <input id="tgi" class="txti" type="number" inputmode="numeric" placeholder="TG Nummer…">
      <button class="btn blue" onclick="activateTGMan()">Aktivieren</button>
    </div>
  </div>
  <div class="card">
    <div class="ch"><div class="ct">Quick-Aktionen</div></div>
    <div class="arow">
      <button class="btn red" onclick="sendDTMF('*9#')">TG trennen</button>
      <button class="btn red" onclick="sendDTMF('##')">EL trennen</button>
    </div>
  </div>
</section>

<!-- DTMF -->
<section class="page" id="p-dtmf">
  <div class="card">
    <div class="ch"><div class="ct">DTMF Tastatur</div></div>
    <div class="ddisp" id="ddisp">—</div>
    <div class="dkeys">
      <?php foreach(['1','2','3','*','4','5','6','0','7','8','9','#'] as $k): ?>
      <button class="dkey" onclick="kp('<?=$k?>')"><?=$k?></button>
      <?php endforeach; ?>
    </div>
    <div class="dact">
      <button class="btn gray" onclick="kc()">⌫ Löschen</button>
      <button class="btn blue" onclick="ks()">Senden</button>
    </div>
  </div>
  <div class="card">
    <div class="ch"><div class="ct">Schnellbefehle</div></div>
    <div class="qgrid">
      <?php foreach($dtmfBtns as $b):
        $c = in_array($b['c'],['orange','green','red','blue','purple'])?$b['c']:'blue'; ?>
      <button class="btn <?=$c?>" onclick="sendDTMF('<?=htmlspecialchars($b['d'])?>')">
        <?=htmlspecialchars($b['l'])?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>

</main>

<div id="toast" class="toast"></div>

<nav>
  <button class="nb active" data-p="dashboard" onclick="go('dashboard',this)"><span>📡</span><span>Dashboard</span></button>
  <button class="nb" data-p="activity" onclick="go('activity',this)"><span>📶</span><span>Aktivität</span></button>
  <button class="nb" data-p="tg" onclick="go('tg',this)"><span>🗣</span><span>Talk Groups</span></button>
  <button class="nb" data-p="dtmf" onclick="go('dtmf',this)"><span>⌨</span><span>DTMF</span></button>
</nav>

<script>const CFG={defaultTG:<?=json_encode($defaultTG)?>,callsign:<?=json_encode($callsign)?>};</script>
<script src="js/app.js"></script>
</body>
</html>
