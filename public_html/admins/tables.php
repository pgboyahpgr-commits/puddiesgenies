<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$tablesFile = __DIR__ . '/../data/tables.json';
$tables = loadJSON($tablesFile);

function generateToken() {
  return bin2hex(random_bytes(8));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireCSRF();
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $num = intval($_POST['number'] ?? 0);
    if ($num > 0) {
      $exists = false;
      foreach ($tables['tables'] ?? [] as $t) { if ($t['number'] === $num) { $exists = true; break; } }
      if (!$exists) {
        $tables['tables'][] = ['number' => $num, 'token' => generateToken(), 'stream_enabled' => false, 'chat_enabled' => true];
      }
    }
  } elseif ($action === 'delete') {
    $num = intval($_POST['number'] ?? 0);
    $tables['tables'] = array_values(array_filter($tables['tables'] ?? [], fn($t) => $t['number'] !== $num));
  } elseif ($action === 'toggle_stream') {
    $num = intval($_POST['number'] ?? 0);
    foreach ($tables['tables'] ?? [] as &$t) {
      if ($t['number'] === $num) { $t['stream_enabled'] = !$t['stream_enabled']; break; }
    }
  } elseif ($action === 'toggle_chat') {
    $num = intval($_POST['number'] ?? 0);
    foreach ($tables['tables'] ?? [] as &$t) {
      if ($t['number'] === $num) { $t['chat_enabled'] = !$t['chat_enabled']; break; }
    }
  } elseif ($action === 'regenerate') {
    $num = intval($_POST['number'] ?? 0);
    foreach ($tables['tables'] ?? [] as &$t) {
      if ($t['number'] === $num) { $t['token'] = generateToken(); break; }
    }
  }
  saveJSON($tablesFile, $tables);
  header('Location: /admins/tables.php');
  exit;
}

$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'puddisgenies.ct.ws');
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Tables — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>
body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}
.qr-img{width:120px;height:120px;border-radius:12px;border:2px solid #eee;cursor:pointer;transition:transform .2s;}
.qr-img:hover{transform:scale(1.05);}
.qr-modal{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;}
.qr-modal.show{display:flex;}
.qr-modal img{max-width:90vw;max-height:90vh;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,0.3);}
@media print { .no-print{display:none;} body{background:#fff;} }
</style>
</head>
<body>
<nav class="max-w-7xl mx-auto px-4 py-4 flex items-center gap-3 flex-wrap no-print">
  <a href="/admins/dashboard.php" class="text-xl font-bold no-underline" style="color:#2D3436;">← SmakAI Admin</a>
  <span class="text-gray-400">/ Tables</span>
</nav>
<main class="max-w-7xl mx-auto px-4 pb-12">
  <div class="flex items-center justify-between mb-4 flex-wrap gap-3 no-print">
    <h1 class="text-3xl font-bold" style="color:#2D3436;">📋 Tables</h1>
    <div class="flex gap-2">
      <form method="POST" class="flex gap-2">
        <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
        <input type="hidden" name="action" value="add" />
        <input type="number" name="number" placeholder="Table #" required class="px-3 py-2 rounded-full border border-gray-200 text-sm w-28" />
        <button type="submit" class="px-4 py-2 rounded-full font-bold text-white" style="background:#4ECDC4;">+ Add</button>
      </form>
      <button onclick="window.print()" class="px-4 py-2 rounded-full font-bold" style="background:#FFE66D;color:#2D3436;">🖨️ Print All</button>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach (($tables['tables'] ?? []) as $t): 
      $tableUrl = $siteUrl . '/menu.php?t=' . $t['token'];
    ?>
    <div class="bg-white/80 backdrop-blur rounded-2xl p-4 shadow border border-gray-100 text-center">
      <h2 class="text-lg font-bold mb-1" style="color:#2D3436;">Table <?=$t['number']?></h2>
      <p class="text-xs text-gray-400 mb-2 font-mono break-all"><?=htmlspecialchars($tableUrl)?></p>
      
      <div class="flex justify-center mb-3">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?=urlencode($tableUrl)?>" 
             alt="QR for Table <?=$t['number']?>" class="qr-img" 
             onclick="showQR(this.src)"
             onerror="this.outerHTML='<div class=\'qr-img flex items-center justify-center bg-gray-100 text-gray-400 text-xs\'>QR</div>'" />
      </div>

      <div class="flex items-center justify-center gap-2 mb-2">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
          <input type="hidden" name="action" value="toggle_stream" />
          <input type="hidden" name="number" value="<?=$t['number']?>" />
          <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold <?=$t['stream_enabled']?'bg-green-100 text-green-600':'bg-gray-100 text-gray-400'?>">
            📺 <?=$t['stream_enabled']?'ON':'OFF'?>
          </button>
        </form>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
          <input type="hidden" name="action" value="toggle_chat" />
          <input type="hidden" name="number" value="<?=$t['number']?>" />
          <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold <?=$t['chat_enabled']?'bg-green-100 text-green-600':'bg-gray-100 text-gray-400'?>">
            💬 <?=$t['chat_enabled']?'ON':'OFF'?>
          </button>
        </form>
      </div>

      <div class="flex gap-2 justify-center no-print">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
          <input type="hidden" name="action" value="regenerate" />
          <input type="hidden" name="number" value="<?=$t['number']?>" />
          <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-600" onclick="return confirm('Regenerate token for Table <?=$t['number']?>? Old QR codes will stop working.')">🔄 New Token</button>
        </form>
        <form method="POST" onsubmit="return confirm('Delete Table <?=$t['number']?>?')">
          <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
          <input type="hidden" name="action" value="delete" />
          <input type="hidden" name="number" value="<?=$t['number']?>" />
          <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-600">🗑️ Delete</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($tables['tables'])): ?>
    <div class="col-span-full text-center py-12 text-gray-400">No tables configured. Add one above.</div>
    <?php endif; ?>
  </div>
</main>

<div id="qrModal" class="qr-modal" onclick="this.classList.remove('show')">
  <img id="qrModalImg" src="" alt="QR Code" />
</div>

<script>
function showQR(src) {
  var modal = document.getElementById('qrModal');
  var img = document.getElementById('qrModalImg');
  img.src = src.replace('size=200x200', 'size=800x800');
  modal.classList.add('show');
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') document.getElementById('qrModal').classList.remove('show');
});
</script>
</body>
</html>
