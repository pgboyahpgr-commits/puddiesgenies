<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
$tableNum = $_SESSION['table'] ?? 0;
$orders = loadJSON(__DIR__ . '/data/orders.json');
$myOrders = array_filter(array_reverse($orders), fn($o) => ($o['table'] ?? 0) == $tableNum);
?>
<style>
.order-history-card{background:#fff;border-radius:16px;padding:16px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border:1px solid #f0ebe5;margin-bottom:12px;transition:box-shadow 0.2s}
.order-history-card:hover{box-shadow:0 4px 20px rgba(0,0,0,0.08)}
.order-history-card .items{display:none;margin-top:10px;padding-top:10px;border-top:1px solid #f0ebe5}
.order-history-card.expanded .items{display:block}
.order-history-card .item-row{display:flex;justify-content:space-between;padding:4px 0;font-size:0.85rem;color:#666}
</style>
<main class="max-w-3xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-2"></div>
  <div class="text-center mb-8 pt-4">
    <h1 class="text-4xl font-bold" style="color:var(--text);"><span style="color:#538bdf;" data-translate>📋 My Orders</span></h1>
    <p class="text-gray-500 mt-2" data-translate>Your past orders at this table</p>
  </div>

  <?php if (empty($myOrders)): ?>
  <div class="glass-card p-8 text-center">
    <div class="text-5xl mb-3" data-translate>🍽️</div>
    <p class="text-gray-400 text-lg font-bold" data-translate>No orders yet</p>
    <p class="text-sm text-gray-400 mt-1" data-translate>Order something delicious!</p>
    <a href="/menu.php" class="btn-bouncy btn-primary mt-4 inline-block px-6 py-3 no-underline" data-translate>📋 Browse Menu</a>
  </div>
  <?php else: ?>
  <div class="mb-3 text-sm text-gray-400" data-translate>Showing <?=count($myOrders)?> order<?=count($myOrders)>1?'s':''?></div>
  <?php foreach ($myOrders as $o): ?>
  <div class="order-history-card" onclick="this.classList.toggle('expanded')">
    <div class="flex items-start justify-between gap-3">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap mb-1">
          <span class="font-mono text-xs font-bold" style="color:#538bdf;">#<?=htmlspecialchars($o['order_id'])?></span>
          <span class="px-2 py-0.5 rounded-full text-xs font-bold <?=$o['status']==='delivered'?'bg-green-100 text-green-600':($o['status']==='preparing'?'bg-yellow-100 text-yellow-600':'bg-orange-100 text-orange-600')?>"><?=ucfirst($o['status']??'pending')?></span>
          <?php if (!empty($o['customer_name'])): ?><span class="text-xs text-gray-400" data-translate>👤 <?=htmlspecialchars($o['customer_name'])?></span><?php endif; ?>
        </div>
        <div class="text-sm text-gray-500"><?=$o['time']?> · <?=count($o['items'])?> item<?=count($o['items'])>1?'s':''?> · <?=$o['payment']==='online'?'✅ UPI':'💵 Cash'?></div>
      </div>
      <div class="text-right flex-shrink-0">
        <div class="font-bold" style="color:#f68e9a;">₹<?=$o['total']?></div>
        <a href="/invoice.php?order=<?=urlencode($o['order_id'])?>" onclick="event.stopPropagation()" class="mt-1 px-3 py-1 rounded-full text-xs font-bold no-underline inline-block" style="background:#f68e9a15;color:#f68e9a;font-family:inherit" data-translate>🧾 Invoice</a>
        <button onclick="event.stopPropagation(); reorder('<?=$o['order_id']?>')" class="mt-1 px-3 py-1 rounded-full text-xs font-bold border-none cursor-pointer" style="background:#538bdf20;color:#538bdf;font-family:inherit" data-translate>🔄 Reorder</button>
      </div>
    </div>
    <div class="items">
      <div class="text-xs font-semibold text-gray-400 mb-2" data-translate>Items Ordered</div>
      <?php foreach ($o['items'] as $item): ?>
      <div class="item-row">
        <span><?=htmlspecialchars($item['name'])?> × <?=$item['qty']??1?></span>
        <span>₹<?=($item['price']??0)*($item['qty']??1)?></span>
      </div>
      <?php endforeach; ?>
      <?php if (!empty($o['instructions'])): ?>
      <div class="mt-2 p-2 rounded-lg text-xs" style="background:#fcfaf7;color:#888;" data-translate>📝 <?=htmlspecialchars($o['instructions'])?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <div class="flex gap-3 justify-center mt-6 flex-wrap">
    <a href="/menu.php" class="btn-bouncy btn-primary px-6 py-3 no-underline" data-translate>📋 Menu</a>
    <a href="/talk.php" class="btn-bouncy btn-outline px-6 py-3 no-underline" data-translate>💬 Chat</a>
  </div>
</main>
<script>
function reorder(orderId) {
  fetch('/api/order-items.php?order_id=' + encodeURIComponent(orderId))
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success && data.items) {
        data.items.forEach(function(item) {
          if (window.addToCart) window.addToCart({ id: item.id || Date.now() + '_' + Math.random(), name: item.name, price: item.price, image: item.image || '', qty: item.qty || 1 });
        });
        if (window.showToast) window.showToast('Items added to cart!', 'success');
        setTimeout(function() { window.location.href = '/checkout.php'; }, 800);
      }
    })
    .catch(function() { if (window.showToast) window.showToast('Could not reorder', 'error'); });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
