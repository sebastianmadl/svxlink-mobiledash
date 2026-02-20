<?php
$settingsFile  = __DIR__ . '/configs/mobile_settings.json';
$mobileCfgFile = __DIR__ . '/configs/mobile_config.php';

// Bereits konfiguriert → weiter
$s = ['language'=>'','theme'=>'system'];
if (file_exists($settingsFile)) {
    $d = json_decode(file_get_contents($settingsFile), true);
    if (is_array($d)) $s = array_merge($s, $d);
}
if (!empty($s['language'])) { header('Location: index.php'); exit; }

// App-Metadaten
if (file_exists($mobileCfgFile)) include_once $mobileCfgFile;
$byline = 'SvxLink Mobile Dashboard Ver '
        . (defined('APP_VERSION') ? APP_VERSION : '1.0')
        . ' © by '
        . (defined('APP_AUTHOR')  ? APP_AUTHOR  : 'OE1SXM')
        . ' '
        . (defined('APP_YEAR')    ? APP_YEAR    : date('Y'));
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <meta name="theme-color" content="#0d1117">
  <title>SVXLink Mobile – Setup</title>
  <link rel="stylesheet" href="css/app.css">
  <style>
    body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg)}
    .sw{display:flex;flex-direction:column;align-items:center;gap:22px;padding:32px 24px;max-width:380px;width:100%}
    .si{width:148px;height:148px;object-fit:contain;border-radius:26px;box-shadow:0 0 48px rgba(96,165,250,.35)}
    .sb{font-family:var(--cond);font-size:12px;color:var(--mu);text-align:center;letter-spacing:.5px;line-height:1.5}
    .st{font-family:var(--cond);font-size:21px;font-weight:700;color:var(--fg);text-align:center;letter-spacing:1px}
    .ss{font-size:13px;color:var(--mu);text-align:center;line-height:1.6}

    /* Sprach-Buttons */
    .lb{display:flex;gap:14px;width:100%}
    .lbtn{flex:1;display:flex;flex-direction:column;align-items:center;gap:10px;padding:22px 8px;
          background:var(--card);border:2px solid var(--b1);border-radius:var(--r);cursor:pointer;
          transition:.18s;font-family:var(--cond);font-size:14px;font-weight:700;color:var(--fg);
          letter-spacing:.5px;-webkit-tap-highlight-color:transparent}
    .lbtn:active,.lbtn.sel{border-color:var(--blu-bd);background:var(--blu-bg);color:var(--blu)}
    .lbtn .flag{font-size:44px;line-height:1}

    /* Trennlinie mit Label */
    .divider{width:100%;display:flex;align-items:center;gap:10px;color:var(--mu)}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--b1)}
    .divider span{font-family:var(--cond);font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;white-space:nowrap}

    /* Theme-Buttons */
    .tb{display:flex;gap:10px;width:100%}
    .tbtn{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px 6px;
          background:var(--card);border:2px solid var(--b1);border-radius:var(--r);cursor:pointer;
          transition:.18s;font-family:var(--cond);font-size:12px;font-weight:700;color:var(--mu);
          letter-spacing:.5px;-webkit-tap-highlight-color:transparent}
    .tbtn:active,.tbtn.sel{border-color:var(--blu-bd);background:var(--blu-bg);color:var(--blu)}
    .tbtn .ticon{font-size:28px;line-height:1}

    /* Start-Button */
    .startbtn{width:100%;padding:14px;background:var(--blu-bg);border:2px solid var(--blu-bd);
              border-radius:var(--r);color:var(--blu);font-family:var(--cond);font-size:16px;
              font-weight:700;letter-spacing:1px;cursor:pointer;transition:.18s;opacity:.4;pointer-events:none}
    .startbtn.ready{opacity:1;pointer-events:all}
    .startbtn.ready:active{filter:brightness(.85)}

    .spin{width:30px;height:30px;border:3px solid var(--b2);border-top-color:var(--blu);
          border-radius:50%;animation:sp .7s linear infinite;display:none}
    @keyframes sp{to{transform:rotate(360deg)}}
    .err{color:var(--red);font-size:13px;text-align:center;display:none}
  </style>
</head>
<body>
<div class="sw">
  <img src="images/icon-512.png" class="si" alt="SVXLink Mobile">
  <div class="sb"><?=htmlspecialchars($byline)?></div>
  <div class="st">Welcome / Willkommen</div>


  <div class="divider"><span>Language / Sprache</span></div>

  <!-- Sprache: English links, Deutsch rechts -->
  <div class="lb">
    <button class="lbtn" id="btn-en" onclick="selLang('en')">
      <span class="flag">🇬🇧</span><span>English</span>
	</button>
     <button class="lbtn" id="btn-de" onclick="selLang('de')">
      <span class="flag">🇩🇪</span><span>Deutsch</span>
    </button>
  </div>

  <div class="divider"><span>Theme / Farbschema</span></div>

  <!-- Theme-Auswahl -->
  <div class="tb">
    <button class="tbtn sel" id="tbtn-system" onclick="selTheme('system')">
      <span class="ticon">⚙</span><span>System</span>
    </button>
    <button class="tbtn" id="tbtn-dark" onclick="selTheme('dark')">
      <span class="ticon">🌙</span><span>Dark</span>
    </button>
    <button class="tbtn" id="tbtn-light" onclick="selTheme('light')">
      <span class="ticon">☀</span><span>Light</span>
    </button>
  </div>

  <!-- Start-Button — wird aktiv sobald Sprache gewählt -->
  <button class="startbtn" id="startbtn" onclick="doStart()">▶ <span id="startlabel">Los / Start</span></button>

  <div class="spin" id="spin"></div>
  <div class="err"  id="err"></div>
</div>
<script>
var selLangVal  = '';
var selThemeVal = 'system';

var startLabels = {de: 'Fortfahren', en: 'Continue'};
function selLang(lang) {
    selLangVal = lang;
    document.querySelectorAll('.lbtn').forEach(b => b.classList.remove('sel'));
    document.getElementById('btn-'+lang).classList.add('sel');
    document.getElementById('startbtn').classList.add('ready');
    document.getElementById('startlabel').textContent = startLabels[lang] || 'Start';
}

function selTheme(theme) {
    selThemeVal = theme;
    document.querySelectorAll('.tbtn').forEach(b => b.classList.remove('sel'));
    document.getElementById('tbtn-'+theme).classList.add('sel');
    // Live-Vorschau
    document.body.classList.remove('theme-dark','theme-light');
    if (theme === 'dark')  document.body.classList.add('theme-dark');
    if (theme === 'light') document.body.classList.add('theme-light');
}

function doStart() {
    if (!selLangVal) return;
    document.getElementById('startbtn').disabled = true;
    document.querySelectorAll('.lbtn,.tbtn').forEach(b => b.disabled = true);
    document.getElementById('spin').style.display = 'block';
    document.getElementById('err').style.display  = 'none';

    fetch('settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'lang=' + encodeURIComponent(selLangVal) + '&theme=' + encodeURIComponent(selThemeVal)
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { window.location.href = 'index.php'; }
        else { showErr(d.msg || 'Unknown error'); }
    })
    .catch(e => showErr('Network error: ' + e));
}

function showErr(msg) {
    document.querySelectorAll('.lbtn,.tbtn').forEach(b => b.disabled = false);
    document.getElementById('startbtn').disabled = false;
    document.getElementById('spin').style.display = 'none';
    const e = document.getElementById('err');
    e.textContent = msg; e.style.display = 'block';
}

// Theme-Vorschau CSS (gleich wie app.css — inline für setup.php)
var style = document.createElement('style');
style.textContent = `
body.theme-dark{--bg:#0d1117;--card:#111827;--up:#1a2332;--b1:#1f2937;--b2:#2d3748;--fg:#e5e7eb;--mu:#6b7280;--di:#4b5563;--blu:#60a5fa;--blu-bg:#1e3a5f;--blu-bd:#2563eb;--grn:#4ade80;--grn-bg:#14532d;--grn-bd:#16a34a;--org:#fb923c;--org-bg:#431407;--org-bd:#ea580c;--red:#f87171;--red-bg:#450a0a;--red-bd:#dc2626;--r:12px;}
body.theme-light{--bg:#f0f4f8;--card:#ffffff;--up:#e8edf2;--b1:#d1d9e0;--b2:#b8c4ce;--fg:#1a202c;--mu:#4a5568;--di:#718096;--blu:#1d4ed8;--blu-bg:#dbeafe;--blu-bd:#93c5fd;--grn:#15803d;--grn-bg:#dcfce7;--grn-bd:#86efac;--org:#c2410c;--org-bg:#ffedd5;--org-bd:#fdba74;--red:#b91c1c;--red-bg:#fee2e2;--red-bd:#fca5a5;--r:12px;}`;
document.head.appendChild(style);
</script>
</body>
</html>
