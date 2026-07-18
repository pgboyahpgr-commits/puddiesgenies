<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
$orderId = $_GET['order'] ?? '';
$paid = $_GET['paid'] ?? '0';
$orders = loadJSON(__DIR__ . '/data/orders.json');
$order = null;
foreach ($orders as $o) {
  if ($o['order_id'] === $orderId) { $order = $o; break; }
}
?>
<style>
@media print{body *{visibility:hidden}#invoicePrint,#invoicePrint *{visibility:visible}#invoicePrint{position:fixed;top:0;left:0;width:100%;max-width:80mm;padding:15px;background:#fff;color:#000;font-size:12px;font-family:'Courier New',monospace;margin:0 auto}#invoicePrint h2{text-align:center;font-size:16px;margin:0 0 5px}#invoicePrint .ip-line{border-top:1px dashed #000;margin:6px 0}#invoicePrint .ip-row{display:flex;justify-content:space-between}#invoicePrint .ip-footer{text-align:center;margin-top:10px;font-size:10px;color:#666}.status-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 14px;border-radius:100px;font-size:.75rem;font-weight:700}.status-badge.pending{background:#fef3e2;color:#d97706}.status-badge.preparing{background:#fef9c3;color:#a16207}.status-badge.delivered{background:#dcfce7;color:#16a34a}
</style>
<main class="max-w-2xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-1"></div>
  <div class="text-center mb-6 pt-4">
    <h1 class="text-4xl font-bold" style="color:var(--text);">
      <?php if ($paid === '1'): ?>
        ✅ <span style="color:#538bdf;" data-translate>Paid</span>
      <?php else: ?>
        🧾 <span style="color:#f68e9a;" data-translate>Invoice</span>
      <?php endif; ?>
    </h1>
  </div>

  <?php if (!$order): ?>
    <div class="glass-card p-8 text-center">
      <div class="text-4xl mb-3" data-translate>📭</div>
      <p class="text-gray-400 font-bold" data-translate>Order not found.</p>
      <a href="/my-orders.php" class="btn-bouncy btn-primary mt-4 inline-block px-6 py-3 no-underline" data-translate>📋 My Orders</a>
    </div>
  <?php else:
    $subtotal = 0;
    foreach ($order['items'] as $item) { $subtotal += ($item['price']??0) * ($item['qty']??1); }
    $tax = round($subtotal * 0.05);
    $status = $order['status'] ?? 'pending';
  ?>
  <div id="invoicePrint">
    <h2 style="color:#f68e9a;">SmakAI</h2>
    <div style="text-align:center;font-size:11px;margin-bottom:4px;">Smart Restaurant</div>
    <div class="ip-line"></div>
    <div class="ip-row"><span>Order</span><span>#<?=htmlspecialchars($order['order_id'])?></span></div>
    <div class="ip-row"><span>Table</span><span>#<?=$order['table']?></span></div>
    <?php if (!empty($order['customer_name'])): ?><div class="ip-row"><span>Customer</span><span><?=htmlspecialchars($order['customer_name'])?></span></div><?php endif; ?>
    <div class="ip-row"><span>Time</span><span><?=$order['time']?></span></div>
    <div class="ip-row"><span>Payment</span><span><?=$order['payment']==='online'?'UPI':'Cash'?></span></div>
    <div class="ip-line"></div>
    <?php foreach ($order['items'] as $item):
      $lineTotal = ($item['price']??0) * ($item['qty']??1);
    ?>
    <div class="ip-row"><span><?=htmlspecialchars($item['name'])?> × <?=$item['qty']??1?></span><span>₹<?=$lineTotal?></span></div>
    <?php endforeach; ?>
    <div class="ip-line"></div>
    <div class="ip-row"><span>Subtotal</span><span>₹<?=$subtotal?></span></div>
    <div class="ip-row"><span>GST (5%)</span><span>₹<?=$tax?></span></div>
    <div class="ip-row" style="font-weight:bold;font-size:14px"><span>Total</span><span>₹<?=$order['total']?></span></div>
    <?php if (!empty($order['instructions'])): ?>
    <div class="ip-line"></div>
    <div style="font-size:10px">📝 <?=htmlspecialchars($order['instructions'])?></div>
    <?php endif; ?>
    <div class="ip-line"></div>
    <div class="ip-row"><span>Status</span><span style="text-transform:uppercase"><?=$status?></span></div>
    <div class="ip-footer">Thank you! 🍽️</div>
  </div>

  <div class="glass-card p-6 mb-4">
    <div class="flex justify-between items-start mb-3">
      <div>
        <p class="text-sm text-gray-400">Order #<?= htmlspecialchars($order['order_id']) ?></p>
        <p class="text-sm text-gray-400">Table #<?= $order['table'] ?></p>
        <?php if (!empty($order['customer_name'])): ?><p class="text-sm text-gray-400" data-translate>👤 <?= htmlspecialchars($order['customer_name']) ?></p><?php endif; ?>
        <p class="text-sm text-gray-400"><?= $order['time'] ?></p>
      </div>
      <div class="text-right">
        <?php if ($paid === '1'): ?>
          <span class="status-badge" style="background:#538bdf;color:#fff;" data-translate>✅ PAID</span>
        <?php else: ?>
          <span class="status-badge" style="background:#f68e9a;color:#fff;" data-translate>💵 Pay at Counter</span>
        <?php endif; ?>
        <div class="mt-1"><span class="status-badge <?=$status?>"><?=ucfirst($status)?></span></div>
      </div>
    </div>

    <div class="border-t border-gray-200 pt-3">
      <?php foreach ($order['items'] as $item):
        $lineTotal = ($item['price']??0) * ($item['qty']??1);
      ?>
      <div class="flex justify-between py-1.5 text-sm">
        <span><?=htmlspecialchars($item['name'])?> <span class="text-gray-400">× <?=$item['qty']??1?></span></span>
        <span class="font-semibold">₹<?=$lineTotal?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="border-t border-gray-200 pt-3 mt-2 space-y-1 text-sm">
      <div class="flex justify-between text-gray-500">
        <span data-translate>Subtotal</span>
        <span>₹<?=$subtotal?></span>
      </div>
      <div class="flex justify-between text-gray-500">
        <span data-translate>GST (5%)</span>
        <span>₹<?=$tax?></span>
      </div>
      <div class="flex justify-between font-bold text-lg pt-1 border-t border-gray-100">
        <span data-translate>Total</span>
        <span style="color:#f68e9a;">₹<?=$order['total']?></span>
      </div>
    </div>

    <?php if (!empty($order['instructions'])): ?>
    <div class="mt-3 p-2.5 rounded-xl text-sm" style="background:#fcfaf7;color:#666;">
      <span data-translate>📝 <?=htmlspecialchars($order['instructions'])?></span>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($paid === '1'): ?>
    <div class="glass-card p-4 text-center mb-4" style="background:rgba(83,139,223,0.08);border-color:#538bdf;">
      <p class="text-sm font-bold" style="color:#538bdf;" data-translate>✅ Payment successful! Your order is being prepared.</p>
    </div>
  <?php else: ?>
    <div class="glass-card p-4 text-center mb-4" style="background:rgba(246,142,154,0.08);border-color:#f68e9a;">
      <p class="text-sm font-bold" style="color:#f68e9a;" data-translate>Please pay at the counter</p>
      <p class="text-xs text-gray-400 mt-1" data-translate>Your order has been sent to the kitchen.</p>
    </div>
  <?php endif; ?>

  <div class="flex gap-3 justify-center flex-wrap">
    <button onclick="window.print()" class="btn-bouncy px-6 py-3" style="background:var(--border-light);color:var(--text);" data-translate>🖨️ Print</button>
    <a href="/menu.php" class="btn-bouncy btn-primary px-6 py-3 no-underline" data-translate>📋 Order More</a>
    <a href="/my-orders.php" class="btn-bouncy btn-outline px-6 py-3 no-underline" data-translate>📋 My Orders</a>
    <a href="/talk.php" class="btn-bouncy btn-outline px-6 py-3 no-underline" data-translate>💬 Chat</a>
  </div>
  <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
