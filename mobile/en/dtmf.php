<?php
// ── Dynamic Path Detection ────────────────────────────────────────────────────
// __DIR__ = .../whatever/mobile/en
// dirname(dirname(__DIR__)) = .../whatever  (dashboard root)
$dashboardRoot = dirname(dirname(__DIR__));
$cfgPath = $dashboardRoot . '/include/config.php';
if (file_exists($cfgPath)) { include_once $cfgPath; }

function cdef($name, $default=null) {
    return defined($name) ? constant($name) : $default;
}

// Read shortcuts from config.php (KEY1..KEY40, TG1..TG40, COLOR1..COLOR40)
$shortcuts = [];
for ($i=1; $i<=40; $i++) {
    $key = cdef("KEY{$i}", null);
    $tg  = cdef("TG{$i}", null);
    $col = cdef("COLOR{$i}", null);
    if ($key === null || $tg === null) continue;
    if ($col === null || trim($col) === '') $col = '#2b6cb0';
    $shortcuts[] = ['label'=>$tg, 'send'=>$key, 'color'=>$col];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>DTMF</title>
  <!-- Paths relative to mobile/en/ — one level deeper than mobile/ -->
  <link rel="icon" href="../../images/favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../../images/favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="../../images/svxlink.ico">
  <link rel="stylesheet" href="../css/app.css">
</head>
<body>
<div class="page">
  <div class="page-header">
    <a class="back-link" href="index.php">← Dashboard</a>
    <h2 style="margin:0;">DTMF</h2>
  </div>
  <div class="card">
    <h3 style="margin-top:0;">Keypad</h3>
    <div class="dtmf-pad">
      <?php
        $keys = ['1','2','3','*','4','5','6','0','7','8','9','#'];
        foreach ($keys as $k) {
            echo '<button class="dtmf-key" onclick="sendDTMF(' . json_encode($k) . ')">'.htmlspecialchars($k).'</button>';
        }
      ?>
    </div>
  </div>
  <div class="card">
    <h3 style="margin-top:0;">Quick Commands</h3>
    <?php if (count($shortcuts) === 0): ?>
      <div class="muted">No quick commands configured.</div>
    <?php else: ?>
      <div class="shortcuts-grid">
        <?php foreach ($shortcuts as $s): ?>
          <button class="shortcut-btn" style="background: <?php echo htmlspecialchars($s['color']); ?>"
                  onclick="sendDTMF(<?php echo json_encode($s['send']); ?>)">
            <?php echo htmlspecialchars($s['label']); ?>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<script>
function sendDTMF(d) {
  // api/ lives in mobile/api/ — go up one level from en/ then into api/
  fetch('../api/dtmf.php?digit=' + encodeURIComponent(d)).catch(()=>{});
}
</script>
</body>
</html>
