<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$adminFile = __DIR__ . '/../data/admin.json';
$admin = loadJSON($adminFile);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $msg = '❌ Security token expired. Please try again.';
  } elseif (isset($_POST['update_creds'])) {
    $newId = trim($_POST['new_id'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    if ($newId && $newPass) {
      $admin['id'] = $newId;
      $admin['password'] = password_hash($newPass, PASSWORD_BCRYPT);
      saveJSON($adminFile, $admin);
      $msg = '✅ Credentials updated!';
    } else {
      $msg = '❌ Both ID and password are required.';
    }
  }
  if (isset($_POST['clear_orders'])) {
    file_put_contents(__DIR__ . '/../data/orders.json', '[]', LOCK_EX);
    $msg = '✅ All orders cleared!';
  }
  if (isset($_POST['reset_stream'])) {
    file_put_contents(__DIR__ . '/../data/stream_config.json', json_encode(['video_url'=>'','video_status'=>'off','last_updated'=>''], JSON_PRETTY_PRINT), LOCK_EX);
    $msg = '✅ Stream config reset!';
  }
}
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Settings — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}</style>
</head>
<body>
<nav class="max-w-7xl mx-auto px-4 py-4 flex items-center gap-3">
  <a href="/admins/dashboard.php" class="text-xl font-bold no-underline" style="color:#2D3436;">← SmakAI Admin</a>
  <span class="text-gray-400">/ Settings</span>
</nav>
<main class="max-w-2xl mx-auto px-4 pb-12">
  <h1 class="text-3xl font-bold mb-6" style="color:#2D3436;">⚙️ Settings</h1>
  <?php if ($msg): ?><div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-2xl mb-4 text-sm"><?=htmlspecialchars($msg)?></div><?php endif; ?>

  <div class="bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100 mb-4">
    <h3 class="font-bold mb-3">🔐 Change Admin Credentials</h3>
    <form method="POST" class="flex flex-col gap-3">
      <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
      <input name="new_id" placeholder="New Admin ID" required class="px-4 py-3 rounded-full border-2 border-gray-200 outline-none focus:border-[#FF6B6B] text-sm" />
      <input name="new_password" placeholder="New Password (min 6 chars)" required minlength="6" class="px-4 py-3 rounded-full border-2 border-gray-200 outline-none focus:border-[#FF6B6B] text-sm" />
      <button name="update_creds" class="px-6 py-3 rounded-full font-bold text-white" style="background:#FF6B6B;">Update Credentials</button>
    </form>
  </div>

  <div class="bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100 mb-4">
    <h3 class="font-bold mb-3 text-red-500">⚠️ Danger Zone</h3>
    <div class="flex flex-col gap-3">
      <form method="POST" onsubmit="return confirm('Clear ALL orders? This cannot be undone.')">
        <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
        <button name="clear_orders" class="px-6 py-3 rounded-full font-bold text-white w-full" style="background:#FF6B6B;">🗑️ Clear All Orders</button>
      </form>
      <form method="POST" onsubmit="return confirm('Reset stream config?')">
        <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
        <button name="reset_stream" class="px-6 py-3 rounded-full font-bold w-full" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:#2D3436;">🔄 Reset Stream Config</button>
      </form>
    </div>
  </div>

  <div class="bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100 mb-4">
    <h3 class="font-bold mb-3">🔄 Sync Menu from Gist</h3>
    <p class="text-sm text-gray-500 mb-3">Force-refresh the menu cache from GitHub Gist (clears local cache)</p>
    <a href="/admins/sync-menu.php" class="inline-block px-6 py-3 rounded-full font-bold text-white" style="background:#4ECDC4;">Refresh Menu</a>
  </div>

  <div class="bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100">
    <h3 class="font-bold mb-3">🖼️ Update Dish Images</h3>
    <p class="text-sm text-gray-500 mb-3">Fetch real food images from Wikimedia Commons for all dishes</p>
    <a href="/admins/update-images.php?offset=0" class="inline-block px-6 py-3 rounded-full font-bold text-white" style="background:#4ECDC4;">Start Batch Update</a>
  </div>
</main>
</body>
</html>
