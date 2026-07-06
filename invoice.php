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
<main class="max-w-2xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-1"></div>
  <div class="text-center mb-8 pt-4">
    <h1 class="text-4xl font-bold" style="color:#2D3436;">
      <?php if ($paid === '1'): ?>
        ✅ <span style="color:#4ECDC4;">Paid</span>
      <?php else: ?>
        🧾 <span style="color:#FF6B6B;">Invoice</span>
      <?php endif; ?>
    </h1>
  </div>

  <?php if (!$order): ?>
    <div class="glass-card p-8 text-center">
      <p class="text-gray-400">Order not found.</p>
      <a href="/menu.php" class="btn-bouncy btn-primary mt-4 inline-block px-6 py-3 no-underline">Back to Menu</a>
    </div>
  <?php else: ?>
    <div class="glass-card p-6 mb-4">
      <div class="flex justify-between items-start mb-4">
        <div>
          <p class="text-sm text-gray-400">Order #<?= htmlspecialchars($order['order_id']) ?></p>
          <p class="text-sm text-gray-400">Table #<?= $order['table'] ?></p>
          <p class="text-sm text-gray-400"><?= $order['time'] ?></p>
        </div>
        <div>
          <?php if ($paid === '1'): ?>
            <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-bold" style="background:#4ECDC4;color:#fff;">
              ✅ PAID
            </span>
          <?php else: ?>
            <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-bold" style="background:#FF6B6B;color:#fff;">
              💵 Pay at Counter
            </span>
          <?php endif; ?>
        </div>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <?php foreach ($order['items'] as $item): ?>
        <div class="flex justify-between py-2">
          <span><?= htmlspecialchars($item['name']) ?> × <?= $item['qty'] ?? 1 ?></span>
          <span class="font-bold">₹<?= ($item['price'] ?? 0) * ($item['qty'] ?? 1) ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($order['instructions']): ?>
      <div class="border-t border-gray-200 pt-3 mt-3">
        <p class="text-sm text-gray-500">📝 <?= htmlspecialchars($order['instructions']) ?></p>
      </div>
      <?php endif; ?>

      <div class="border-t border-gray-200 pt-3 mt-3 flex justify-between items-center">
        <span class="font-bold text-lg">Total</span>
        <span class="font-bold text-2xl" style="color:#FF6B6B;">₹<?= $order['total'] ?></span>
      </div>
    </div>

    <?php if ($paid === '1'): ?>
      <div class="glass-card p-4 text-center mb-4" style="background:rgba(78,205,196,0.1);border-color:#4ECDC4;">
        <p class="text-sm">✅ Payment successful! Your order is being prepared.</p>
      </div>
    <?php else: ?>
      <div class="glass-card p-4 text-center mb-4" style="background:rgba(255,107,107,0.1);border-color:#FF6B6B;">
        <p class="text-sm font-bold">Please pay at the counter to receive your bill.</p>
        <p class="text-xs text-gray-400 mt-1">Your order has been sent to the kitchen.</p>
      </div>
    <?php endif; ?>

    <div class="flex gap-3 justify-center flex-wrap">
      <a href="/menu.php" class="btn-bouncy btn-primary px-6 py-3 no-underline">📋 Order More</a>
      <a href="/chat.php" class="btn-bouncy btn-outline px-6 py-3 no-underline">💬 Chat with Restaurant</a>
    </div>
  <?php endif; ?>
</main>

<script>document.addEventListener('DOMContentLoaded', () => gsap.from('.glass-card', { y: 20, opacity: 0, duration: 0.5, stagger: 0.1 }));</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
