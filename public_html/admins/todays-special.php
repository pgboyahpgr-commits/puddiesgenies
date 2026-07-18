<?php require_once __DIR__ . '/../includes/admin-header.php';

$specialFile = __DIR__ . '/../data/todays_special.json';
$special = [];
$msg = '';

if (file_exists($specialFile)) {
  $special = json_decode(file_get_contents($specialFile), true) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dish_name'])) {
  $special = [
    'name' => trim($_POST['dish_name'] ?? ''),
    'image' => trim($_POST['dish_image'] ?? ''),
    'price' => trim($_POST['dish_price'] ?? ''),
    'desc' => trim($_POST['dish_desc'] ?? ''),
    'active' => !empty($_POST['active']),
  ];
  file_put_contents($specialFile, json_encode($special, JSON_PRETTY_PRINT), LOCK_EX);
  $msg = '✅ Today\'s special saved!';
}
?><main class="max-w-3xl mx-auto px-4 pb-10 pt-6">
  <h1 class="text-3xl font-bold mb-2" style="font-family:'Fredoka',sans-serif;color:#1A1A1A;" data-translate>🌟 Today's Special</h1>
  <p style="font-size:0.9rem;color:#9B9B9B;margin:0 0 24px;" data-translate>Feature a dish prominently on the customer menu page.</p>

  <?php if ($msg): ?>
  <div class="admin-msg msg-primary" style="margin-bottom:16px;"><?=htmlspecialchars($msg)?></div>
  <?php endif; ?>

  <div class="card animate-fade-up" style="padding:24px;">
    <h3 style="font-family:'Fredoka',sans-serif;font-weight:700;color:#1A1A1A;margin:0 0 20px;" data-translate>🌟 Featured Dish</h3>
    <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
      <input type="hidden" name="csrf_token" value="<?=$csrfToken?>">
      <div>
        <label style="font-size:0.82rem;font-weight:600;color:#6B6B6B;display:block;margin-bottom:4px;" data-translate>Dish Name</label>
        <input type="text" name="dish_name" value="<?=htmlspecialchars($special['name'] ?? '')?>" placeholder="e.g. Butter Chicken" class="input-field">
      </div>
      <div>
        <label style="font-size:0.82rem;font-weight:600;color:#6B6B6B;display:block;margin-bottom:4px;" data-translate>Image URL</label>
        <input type="text" name="dish_image" value="<?=htmlspecialchars($special['image'] ?? '')?>" placeholder="https://..." class="input-field">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
          <label style="font-size:0.82rem;font-weight:600;color:#6B6B6B;display:block;margin-bottom:4px;" data-translate>Price (₹)</label>
          <input type="text" name="dish_price" value="<?=htmlspecialchars($special['price'] ?? '')?>" placeholder="299" class="input-field">
        </div>
        <div style="display:flex;align-items:center;">
          <label style="display:flex;align-items:center;gap:6px;font-size:0.9rem;font-weight:600;cursor:pointer;">
            <input type="checkbox" name="active" value="1" <?=!empty($special['active']) ? 'checked' : ''?> style="accent-color:#f7b6bf;width:18px;height:18px;" data-translate>
            Show on Menu
          </label>
        </div>
      </div>
      <div>
        <label style="font-size:0.82rem;font-weight:600;color:#6B6B6B;display:block;margin-bottom:4px;" data-translate>Description</label>
        <textarea name="dish_desc" rows="3" placeholder="Describe this special dish..." class="input-field"><?=htmlspecialchars($special['desc'] ?? '')?></textarea>
      </div>
      <?php if (!empty($special['image'])): ?>
      <div>
        <p style="font-size:0.78rem;color:#9B9B9B;margin:0 0 6px;" data-translate>Preview:</p>
        <img src="<?=htmlspecialchars($special['image'])?>" style="width:100%;max-height:180px;object-fit:cover;border-radius:12px;background:#F6F4F0;" onerror="this.style.display='none'">
      </div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary" style="background:#4138c2;" data-translate>💾 Save Today's Special</button>
    </form>
  </div>
</main>
</div>
</body>
</html>
