<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$ordersFile = __DIR__ . '/../data/orders.json';
$orders = loadJSON($ordersFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  requireCSRF();
  $id = $_POST['order_id'] ?? '';
  foreach ($orders as &$o) {
    if ($o['order_id'] === $id) {
      if ($_POST['action'] === 'preparing') $o['status'] = 'preparing';
      elseif ($_POST['action'] === 'delivered') $o['status'] = 'delivered';
      elseif ($_POST['action'] === 'delete') { $orders = array_filter($orders, fn($x) => $x['order_id'] !== $id); }
      break;
    }
  }
  saveJSON($ordersFile, array_values($orders));
  header('Location: /admins/orders.php');
  exit;
}
$filter = $_GET['filter'] ?? 'all';
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Orders — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}</style>
</head>
<body>
<nav class="max-w-7xl mx-auto px-4 py-4 flex items-center gap-3 flex-wrap">
  <a href="/admins/dashboard.php" class="text-xl font-bold no-underline" style="color:#2D3436;">← SmakAI Admin</a>
  <span class="text-gray-400">/ Orders</span>
</nav>
<main class="max-w-7xl mx-auto px-4 pb-12">
  <h1 class="text-3xl font-bold mb-4" style="color:#2D3436;">📦 Orders</h1>
  <div class="flex gap-2 mb-4 flex-wrap">
    <?php foreach (['all'=>'All','pending'=>'Pending','preparing'=>'Preparing','delivered'=>'Delivered'] as $k=>$v): ?>
    <a href="?filter=<?=$k?>" class="px-4 py-2 rounded-full text-sm font-semibold no-underline <?=$filter===$k?'bg-[#FF6B6B] text-white':'bg-white/80 border border-gray-200 text-gray-600'?>"><?=$v?></a>
    <?php endforeach; ?>
  </div>
  <div class="bg-white/80 backdrop-blur rounded-2xl shadow border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
      <thead><tr class="bg-gray-50 text-gray-500">
        <th class="text-left p-3">Order ID</th><th class="text-left p-3">Table</th><th class="text-left p-3">Items</th><th class="text-left p-3">Total</th><th class="text-left p-3">Payment</th><th class="text-left p-3">Status</th><th class="text-left p-3">Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach (array_reverse($orders) as $o): ?>
        <?php if ($filter !== 'all' && ($o['status'] ?? 'pending') !== $filter) continue; ?>
        <tr class="border-t border-gray-100">
          <td class="p-3 font-mono text-xs"><?=htmlspecialchars($o['order_id'])?></td>
          <td class="p-3">T<?=$o['table']?></td>
          <td class="p-3"><?=count($o['items'])?> items</td>
          <td class="p-3 font-bold">₹<?=$o['total']?></td>
          <td class="p-3"><?=$o['payment']==='online'?'✅ UPI':'💵 Cash'?></td>
          <td class="p-3"><span class="px-2 py-1 rounded-full text-xs font-bold <?=$o['status']==='delivered'?'bg-green-100 text-green-600':($o['status']==='preparing'?'bg-yellow-100 text-yellow-600':'bg-orange-100 text-orange-600')?>"><?=ucfirst($o['status']??'pending')?></span></td>
          <td class="p-3">
            <form method="POST" class="flex gap-1 flex-wrap">
              <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
              <input type="hidden" name="order_id" value="<?=$o['order_id']?>" />
              <?php if (($o['status']??'pending') === 'pending'): ?>
              <button name="action" value="preparing" class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border-none cursor-pointer">Preparing</button>
              <?php elseif (($o['status']??'') === 'preparing'): ?>
              <button name="action" value="delivered" class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border-none cursor-pointer">Delivered</button>
              <?php endif; ?>
              <button name="action" value="delete" class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-600 border-none cursor-pointer" onclick="return confirm('Delete this order?')">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?><tr><td colspan="7" class="p-6 text-center text-gray-400">No orders</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
