<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/menu-loader.php';
requireAdmin();
$menu = loadMenuFromGist();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireCSRF();
  $action = $_POST['action'] ?? '';
  if ($action === 'add' || $action === 'edit') {
    $catId = $_POST['category'] ?? 'biryani';
    $itemId = $_POST['item_id'] ?? ('item_' . time());
    $price = intval($_POST['price'] ?? 0);
    if ($price <= 0) {
      $_SESSION['admin_message'] = 'Price must be greater than 0';
      header('Location: /admins/menu.php');
      exit;
    }
    $newItem = [
      'id' => $itemId,
      'name' => $_POST['name'] ?? '',
      'description' => $_POST['description'] ?? '',
      'image' => $_POST['image'] ?? '',
      'price' => $price,
      'spice_level' => $_POST['spice_level'] ?? 'mild',
      'is_vegetarian' => isset($_POST['is_vegetarian']),
      'region' => $_POST['region'] ?? 'North India',
      'cooking_time' => $_POST['cooking_time'] ?? '30 mins',
      'calories' => intval($_POST['calories'] ?? 0),
      'popular' => isset($_POST['popular'])
    ];
    if (empty($newItem['name'])) {
      $_SESSION['admin_message'] = 'Dish name is required';
      header('Location: /admins/menu.php');
      exit;
    }
    foreach ($menu['menu']['categories'] as &$cat) {
      if ($cat['id'] === $catId) {
        if ($action === 'add') {
          $cat['items'][] = $newItem;
        } else {
          foreach ($cat['items'] as &$item) {
            if ($item['id'] === $itemId) { $item = $newItem; break; }
          }
        }
        break;
      }
    }
    if (isset($menu['menu']['metadata'])) {
      $total = 0;
      foreach ($menu['menu']['categories'] as $c) { $total += count($c['items']); }
      $menu['menu']['metadata']['total_items'] = $total;
    }
    $ok = updateGist($menu);
    $_SESSION['admin_message'] = $ok ? 'Dish saved and Gist updated.' : 'Dish saved locally but Gist update failed.';
    header('Location: /admins/menu.php');
    exit;
  }
  if ($action === 'delete') {
    $itemId = $_POST['item_id'] ?? '';
    foreach ($menu['menu']['categories'] as &$cat) {
      $filtered = array();
      foreach ($cat['items'] as $i) { if ($i['id'] !== $itemId) { $filtered[] = $i; } }
      $cat['items'] = array_values($filtered);
    }
    if (isset($menu['menu']['metadata'])) {
      $total = 0;
      foreach ($menu['menu']['categories'] as $c) { $total += count($c['items']); }
      $menu['menu']['metadata']['total_items'] = $total;
    }
    $ok = updateGist($menu);
    $_SESSION['admin_message'] = $ok ? 'Dish deleted and Gist updated.' : 'Dish deleted locally but Gist update failed.';
    header('Location: /admins/menu.php');
    exit;
  }
}
$categories = $menu['menu']['categories'] ?? [];
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Menu — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}</style>
</head>
<body>
<nav class="max-w-7xl mx-auto px-4 py-4 flex items-center gap-3 flex-wrap">
  <a href="/admins/dashboard.php" class="text-xl font-bold no-underline" style="color:#2D3436;">← SmakAI Admin</a>
  <span class="text-gray-400">/ Menu Editor</span>
</nav>
<main class="max-w-7xl mx-auto px-4 pb-12">
  <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <h1 class="text-3xl font-bold" style="color:#2D3436;">📋 Menu Editor</h1>
    <button onclick="document.getElementById('addForm').classList.toggle('hidden')" class="px-4 py-2 rounded-full font-bold text-white" style="background:#4ECDC4;">+ Add Dish</button>
  </div>

  <?php if (isset($_SESSION['admin_message'])): ?>
  <div class="mb-4 px-4 py-3 rounded-xl text-sm font-bold <?= strpos($_SESSION['admin_message'], 'Error') !== false || strpos($_SESSION['admin_message'], 'fail') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
    <?= htmlspecialchars($_SESSION['admin_message']) ?>
  </div>
  <?php unset($_SESSION['admin_message']); endif; ?>

  <div id="addForm" class="hidden bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100 mb-6">
    <h3 class="font-bold mb-3" id="formTitle">Add New Dish</h3>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3" id="dishForm">
      <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
      <input type="hidden" name="action" id="formAction" value="add" />
      <input type="hidden" name="item_id" id="formItemId" value="" />
      <div>
        <label class="text-xs text-gray-400 block mb-1">🍛 Dish Name *</label>
        <input name="name" id="formName" placeholder="e.g. Butter Chicken" required class="w-full px-3 py-2 rounded-full border border-gray-200 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">💰 Price (₹) *</label>
        <input name="price" id="formPrice" type="number" min="1" placeholder="e.g. 299" required class="w-full px-3 py-2 rounded-full border border-gray-200 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">📁 Category</label>
        <select name="category" id="formCategory" class="w-full px-3 py-2 rounded-full border border-gray-200 text-sm">
          <?php foreach ($categories as $cat): ?>
          <option value="<?=$cat['id']?>"><?=htmlspecialchars($cat['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">📝 Description</label>
        <input name="description" id="formDesc" placeholder="What's in this dish? Ingredients, taste notes..." class="w-full px-3 py-2 rounded-full border border-gray-200 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">🖼️ Image URL</label>
        <input name="image" id="formImage" placeholder="https://example.com/dish.jpg" class="w-full px-3 py-2 rounded-full border border-gray-200 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">🌶️ Spice Level</label>
        <select name="spice_level" id="formSpice" class="w-full px-3 py-2 rounded-full border border-gray-200 text-sm">
          <option value="mild">Mild (gentle on stomach)</option><option value="medium">Medium (balanced heat)</option><option value="hot">Hot (🔥 spicy kick)</option>
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">📍 Region / Origin</label>
        <input name="region" id="formRegion" placeholder="e.g. Punjab, South India, Mughlai..." class="w-full px-3 py-2 rounded-full border border-gray-200 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">⏱️ Cooking Time</label>
        <input name="cooking_time" id="formCook" placeholder="e.g. 30 mins" class="w-full px-3 py-2 rounded-full border border-gray-200 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">🔥 Calories</label>
        <input name="calories" id="formCal" type="number" min="0" placeholder="e.g. 450" class="w-full px-3 py-2 rounded-full border border-gray-200 text-sm" />
      </div>
      <div class="flex gap-4 items-center">
        <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="is_vegetarian" id="formVeg" /> 🥬 Vegetarian</label>
        <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="popular" id="formPop" /> ⭐ Popular</label>
      </div>
      <button type="submit" class="px-4 py-2 rounded-full font-bold text-white" style="background:#4ECDC4;">Save Dish</button>
    </form>
  </div>

  <?php foreach ($categories as $cat): ?>
  <div class="mb-6">
    <h2 class="text-xl font-bold mb-2" style="color:#2D3436;"><?=htmlspecialchars($cat['name'])?> <span class="text-sm font-normal text-gray-400">(<?=count($cat['items'])?> items)</span></h2>
    <div class="bg-white/80 backdrop-blur rounded-2xl shadow border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead><tr class="bg-gray-50 text-gray-500">
          <th class="text-left p-2">Name</th><th class="text-left p-2">Price</th><th class="text-left p-2">Spice</th><th class="text-left p-2">Veg</th><th class="text-left p-2">Actions</th>
        </tr></thead>
        <tbody>
          <?php foreach ($cat['items'] as $item): ?>
          <tr class="border-t border-gray-100">
            <td class="p-2 font-medium"><?=htmlspecialchars($item['name'])?></td>
            <td class="p-2">₹<?=intval($item['price'])?></td>
            <td class="p-2"><?=ucfirst($item['spice_level'] ?? 'mild')?></td>
            <td class="p-2"><?=!empty($item['is_vegetarian'])?'🥬 Veg':'🍗 Non-Veg'?></td>
            <td class="p-2 flex gap-2">
              <button type="button" class="text-blue-500 text-xs font-bold" onclick="editDish('<?=htmlspecialchars($item['id'])?>','<?=htmlspecialchars($item['name'], ENT_QUOTES)?>',<?=intval($item['price'])?>,'<?=htmlspecialchars($cat['id'])?>','<?=htmlspecialchars($item['description'] ?? '', ENT_QUOTES)?>','<?=htmlspecialchars($item['image'] ?? '', ENT_QUOTES)?>','<?=htmlspecialchars($item['spice_level'] ?? 'mild')?>','<?=htmlspecialchars($item['region'] ?? '', ENT_QUOTES)?>','<?=htmlspecialchars($item['cooking_time'] ?? '', ENT_QUOTES)?>',<?=intval($item['calories'] ?? 0)?>,<?=!empty($item['is_vegetarian'])?'true':'false'?>,<?=!empty($item['popular'])?'true':'false'?>)">Edit</button>
              <form method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="item_id" value="<?=htmlspecialchars($item['id'])?>" />
                <button type="submit" class="text-red-500 text-xs font-bold" onclick="return confirm('Delete <?=htmlspecialchars($item['name'])?>?')">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>
</main>
<script>
function editDish(id, name, price, cat, desc, image, spice, region, cook, cal, veg, pop) {
  document.getElementById('addForm').classList.remove('hidden');
  document.getElementById('formTitle').textContent = 'Edit Dish';
  document.getElementById('formAction').value = 'edit';
  document.getElementById('formItemId').value = id;
  document.getElementById('formName').value = name;
  document.getElementById('formPrice').value = price;
  document.getElementById('formCategory').value = cat;
  document.getElementById('formDesc').value = desc;
  document.getElementById('formImage').value = image;
  document.getElementById('formSpice').value = spice;
  document.getElementById('formRegion').value = region;
  document.getElementById('formCook').value = cook;
  document.getElementById('formCal').value = cal;
  document.getElementById('formVeg').checked = veg;
  document.getElementById('formPop').checked = pop;
  document.getElementById('addForm').scrollIntoView({ behavior: 'smooth' });
}
</script>
</body>
</html>
