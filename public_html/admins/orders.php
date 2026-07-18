<?php
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/menu-loader.php';
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
<style>
.order-card{background:#fff;border-radius:16px;padding:16px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border:1px solid #f0ebe5;transition:box-shadow 0.2s;cursor:pointer}
.order-card:hover{box-shadow:0 4px 20px rgba(0,0,0,0.08)}
.order-card.expanded{border-color:#538bdf;box-shadow:0 0 0 2px rgba(83,139,223,0.15)}
.order-items{display:none;margin-top:12px;padding-top:12px;border-top:1px solid #f0ebe5}
.order-card.expanded .order-items{display:block}
.order-item{display:flex;justify-content:space-between;align-items:center;padding:6px 0;font-size:0.85rem;border-bottom:1px dashed #f5f0ea}
.order-item:last-child{border-bottom:none}
.status-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 14px;border-radius:100px;font-size:0.75rem;font-weight:700;white-space:nowrap}
.status-badge.pending{background:#fef3e2;color:#d97706}
.status-badge.preparing{background:#fef9c3;color:#a16207}
.status-badge.delivered{background:#dcfce7;color:#16a34a}
</style>
<main class="max-w-7xl mx-auto px-4 pb-12">
  <h1 class="text-3xl font-bold mb-4" style="color:var(--text);" data-translate>📦 Orders</h1>
  <div class="flex gap-2 mb-4 flex-wrap">
    <?php foreach (['all'=>'All','pending'=>'Pending','preparing'=>'Preparing','delivered'=>'Delivered'] as $k=>$v): ?>
    <a href="?filter=<?=$k?>" class="px-4 py-2 rounded-full text-sm font-semibold no-underline transition <?=$filter===$k?'bg-[#f68e9a] text-white':'bg-white/80 border border-gray-200 text-gray-600 hover:border-gray-300'?>"><?=$v?></a>
    <?php endforeach; ?>
  </div>

  <div class="space-y-3" id="ordersContainer">
    <?php
    $displayed = 0;
    foreach (array_reverse($orders) as $o):
      if ($filter !== 'all' && ($o['status'] ?? 'pending') !== $filter) continue;
      $displayed++;
      $itemCount = count($o['items']);
      $itemSummary = implode(', ', array_map(fn($i) => $i['name'] . ' ×' . ($i['qty']??1), array_slice($o['items'], 0, 3)));
      if ($itemCount > 3) $itemSummary .= '…';
    ?>
    <div class="order-card" onclick="toggleOrder(this)">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap mb-1">
            <span class="font-mono text-xs font-bold" style="color:#538bdf;">#<?=htmlspecialchars($o['order_id'])?></span>
            <?php if (!empty($o['customer_name'])): ?>
            <span class="text-xs text-gray-500" data-translate>👤 <?=htmlspecialchars($o['customer_name'])?></span>
            <?php endif; ?>
            <span class="text-xs text-gray-400">T<?=$o['table']?></span>
            <span class="text-xs text-gray-400"><?=$o['time']?></span>
          </div>
          <div class="text-sm text-gray-600 truncate"><?=htmlspecialchars($itemSummary)?></div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <span class="font-bold" style="color:#f68e9a;">₹<?=$o['total']?></span>
          <span class="status-badge <?=$o['status']??'pending'?>"><?=ucfirst($o['status']??'pending')?></span>
        </div>
      </div>

      <div class="order-items">
        <div class="text-xs font-semibold text-gray-400 mb-2" data-translate>Order Items</div>
        <?php foreach ($o['items'] as $item): 
          $itemTotal = ($item['price']??0) * ($item['qty']??1);
        ?>
        <div class="order-item">
          <span><?=htmlspecialchars($item['name'])?> <span class="text-gray-400">× <?=$item['qty']??1?></span></span>
          <span class="font-semibold">₹<?=$itemTotal?></span>
        </div>
        <?php endforeach; ?>
        <div class="flex justify-between items-center pt-2 font-bold text-sm">
          <span data-translate>Total</span>
          <span style="color:#f68e9a;">₹<?=$o['total']?></span>
        </div>
        <?php if (!empty($o['instructions'])): ?>
        <div class="mt-2 p-2 rounded-lg" style="background:#fcfaf7;">
          <span class="text-xs text-gray-500" data-translate>📝 <?=htmlspecialchars($o['instructions'])?></span>
        </div>
        <?php endif; ?>
        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100">
          <span class="text-xs text-gray-400"><?=$o['payment']==='online'?'✅ UPI':'💵 Cash'?></span>
          <button onclick="printReceipt('<?=$o['order_id']?>')" class="px-3 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border-none cursor-pointer hover:bg-gray-200 transition ml-auto" data-translate>🖨️ Print</button>
          <form method="POST" class="flex gap-1">
            <input type="hidden" name="csrf_token" value="<?=$csrfToken?>">
            <input type="hidden" name="order_id" value="<?=$o['order_id']?>">
            <?php if (($o['status']??'pending') === 'pending'): ?>
            <button name="action" value="preparing" class="px-3 py-1.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border-none cursor-pointer hover:bg-yellow-200 transition" data-translate>👨‍🍳 Preparing</button>
            <?php elseif (($o['status']??'') === 'preparing'): ?>
            <button name="action" value="delivered" class="px-3 py-1.5 rounded-full text-xs font-bold bg-green-100 text-green-700 border-none cursor-pointer hover:bg-green-200 transition" data-translate>✅ Delivered</button>
            <?php endif; ?>
            <button name="action" value="delete" class="px-3 py-1.5 rounded-full text-xs font-bold bg-red-50 text-red-500 border-none cursor-pointer hover:bg-red-100 transition" onclick="return confirm('Delete this order?')" data-translate>🗑️</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if ($displayed === 0): ?>
    <div class="text-center py-12 text-gray-400">
      <div class="text-4xl mb-2" data-translate>📭</div>
      <p data-translate>No orders found</p>
    </div>
    <?php endif; ?>
  </div>
</main>
<style>@media print{body *{visibility:hidden}#receiptPrint,#receiptPrint *{visibility:visible}#receiptPrint{position:fixed;top:0;left:0;width:80mm;padding:10px;background:#fff;color:#000;font-size:12px;font-family:'Courier New',monospace}#receiptPrint h2{text-align:center;font-size:16px;margin:0 0 5px}#receiptPrint .rp-line{border-top:1px dashed #000;margin:5px 0}#receiptPrint .rp-row{display:flex;justify-content:space-between}#receiptPrint .rp-footer{text-align:center;margin-top:10px;font-size:10px;color:#666}}</style>
<script>
function toggleOrder(el) {
  el.classList.toggle('expanded');
}
function printReceipt(orderId) {
  var card = event.target.closest('.order-card');
  if (!card) return;
  var html = '<div id="receiptPrint"><h2>SmakAI</h2><div style="text-align:center;font-size:10px;margin-bottom:5px">' + card.querySelector('.font-mono').textContent + '</div><div style="text-align:center;font-size:10px;margin-bottom:5px">' + (card.querySelector('.text-gray-400:nth-child(3)')?.textContent || '') + ' | ' + (card.querySelector('.text-gray-400:nth-child(4)')?.textContent || '') + '</div><div class="rp-line"></div>';
  card.querySelectorAll('.order-item').forEach(function(row) {
    var name = row.querySelector('span:first-child')?.textContent || '';
    var price = row.querySelector('span:last-child')?.textContent || '';
    html += '<div class="rp-row"><span>' + name + '</span><span>' + price + '</span></div>';
  });
  html += '<div class="rp-line"></div><div class="rp-row" style="font-weight:bold"><span>Total</span><span>' + (card.querySelector('[style*="color:#f68e9a"]')?.textContent || '') + '</span></div>';
  var instr = card.querySelector('[data-translate*="📝"]');
  if (instr) html += '<div class="rp-line"></div><div style="font-size:10px">' + instr.textContent + '</div>';
  html += '<div class="rp-footer">Thank you!</div></div>';
  var existing = document.getElementById('receiptPrint');
  if (existing) existing.remove();
  document.body.insertAdjacentHTML('beforeend', html);
  window.print();
  setTimeout(function() { var e = document.getElementById('receiptPrint'); if (e) e.remove(); }, 100);
}
</script>
</div>
</body>
</html>
