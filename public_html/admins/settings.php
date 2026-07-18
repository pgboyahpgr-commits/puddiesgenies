<?php
require_once __DIR__ . '/../includes/admin-header.php';
$adminFile = __DIR__ . '/../data/admin.json';
$admin = loadJSON($adminFile);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireCSRF();
  if (isset($_POST['update_creds'])) {
    $newId = trim($_POST['new_id'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    if ($newId && $newPass) {
      $admin['id'] = $newId;
      $admin['password'] = password_hash($newPass, PASSWORD_BCRYPT);
      saveJSON($adminFile, $admin);
      $msg = '✅ Credentials updated!';
    } else { $msg = '❌ Both ID and password are required.'; }
  }
  if (isset($_POST['clear_orders'])) {
    file_put_contents(__DIR__ . '/../data/orders.json', '[]', LOCK_EX);
    $msg = '✅ All orders cleared!';
  }
  if (isset($_POST['reset_stream'])) {
    file_put_contents(__DIR__ . '/../data/stream_config.json', json_encode(['video_url'=>'','video_status'=>'off','last_updated'=>''], JSON_PRETTY_PRINT), LOCK_EX);
    $msg = '✅ Stream config reset!';
  }
  if (isset($_POST['clear_chat'])) {
    $msgDir = __DIR__ . '/../data/messages';
    if (is_dir($msgDir)) {
      $files = glob($msgDir . '/*.json');
      foreach ($files as $f) { @unlink($f); }
      $msg = '✅ All chat messages cleared!';
    }
  }
  if (isset($_POST['clear_call_requests'])) {
    file_put_contents(__DIR__ . '/../data/call_requests.json', '[]', LOCK_EX);
    file_put_contents(__DIR__ . '/../data/active_calls.json', '[]', LOCK_EX);
    $msg = '✅ Call requests cleared!';
  }
}
?>
<main class="max-w-2xl mx-auto px-4 pb-12" style="margin-top:20px;">
  <h1 class="text-3xl font-bold mb-6" style="color:var(--text);" data-translate>⚙️ Settings</h1>
  <?php if ($msg): ?><div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-2xl mb-4 text-sm"><?=htmlspecialchars($msg)?></div><?php endif; ?>

  <div class="card p-5 mb-4">
    <h3 class="font-bold mb-3" data-translate>🔐 Change Admin Credentials</h3>
    <form method="POST" class="flex flex-col gap-3">
      <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
      <input name="new_id" placeholder="New Admin ID" required class="input-field" />
      <input name="new_password" placeholder="New Password (min 6 chars)" required minlength="6" class="input-field" />
      <button name="update_creds" class="btn btn-primary" data-translate>Update Credentials</button>
    </form>
  </div>

  <div class="card p-5 mb-4">
    <h3 class="font-bold mb-3" data-translate>🔄 Refresh Menu</h3>
    <p class="text-sm text-gray-500 mb-3" data-translate>Force-refresh menu cache from GitHub Gist</p>
    <a href="/admins/sync-menu.php" class="btn" style="background:#538bdf;color:#fff;" data-translate>Refresh Menu</a>
  </div>

  <div class="card p-5 mb-4">
    <h3 class="font-bold mb-3" data-translate>🖼️ Update Dish Images</h3>
    <p class="text-sm text-gray-500 mb-3" data-translate>Fetch real food images from Wikimedia Commons</p>
    <a href="/admins/update-images.php?offset=0" class="btn" style="background:#538bdf;color:#fff;" data-translate>Start Batch Update</a>
  </div>

  <div class="card p-5 mb-4" style="border-color:rgba(174,40,36,0.2);">
    <h3 class="font-bold mb-3" style="color:#ae2824;" data-translate>⚠️ Admin Actions</h3>
    <div class="flex flex-col gap-3">
      <form method="POST" onsubmit="return confirm('Clear ALL orders? Cannot be undone.')">
        <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
        <button name="clear_orders" class="btn w-full" style="background:#f68e9a;color:#fff;" data-translate>🗑️ Clear All Orders</button>
      </form>
      <form method="POST" onsubmit="return confirm('Clear ALL chat messages?')">
        <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
        <button name="clear_chat" class="btn w-full" style="background:var(--border-light);color:var(--text);" data-translate>💬 Clear Chat Messages</button>
      </form>
      <form method="POST" onsubmit="return confirm('Clear ALL call requests?')">
        <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
        <button name="clear_call_requests" class="btn w-full" style="background:var(--border-light);color:var(--text);" data-translate>📞 Clear Call Requests</button>
      </form>
      <form method="POST" onsubmit="return confirm('Reset stream configuration?')">
        <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
        <button name="reset_stream" class="btn w-full" style="background:var(--border-light);color:var(--text);" data-translate>🔄 Reset Stream Config</button>
      </form>
    </div>
  </div>
</main>
</div>
</body>
</html>
