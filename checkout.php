<?php
require_once __DIR__ . '/includes/header.php';
$tableNum = $_SESSION['table'] ?? 0;
?>
<main class="max-w-3xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-1"></div>
  <div class="blob-bg blob-2"></div>

  <div class="text-center mb-8 pt-4">
    <h1 class="text-5xl font-bold" style="color:#2D3436;"><span style="color:#FF6B6B;">Checkout</span></h1>
    <?php if ($tableNum > 0): ?>
    <p class="text-gray-500 mt-2">Table #<?= $tableNum ?> — Your order</p>
    <?php endif; ?>
  </div>

  <div id="cartItems" class="mb-6">
    <div class="text-center py-8 text-gray-400">Loading cart...</div>
  </div>

  <div class="glass-card p-4 mb-6">
    <div class="flex justify-between items-center">
      <span class="font-bold text-lg">Total:</span>
      <span class="font-bold text-2xl" style="color:#FF6B6B;" id="cartTotal">₹0</span>
    </div>
  </div>

  <div class="glass-card p-4 mb-6">
    <h3 class="font-bold mb-2">📝 Custom Instructions</h3>
    <textarea id="specialInstructions" placeholder="Any special requests for your order... (e.g., extra spicy, no onions)" class="w-full p-3 rounded-xl border-2 border-gray-200 bg-white/80 outline-none focus:border-[#4ECDC4] transition text-sm min-h-[80px]"></textarea>
  </div>

  <div class="flex flex-col gap-3">
    <button onclick="placeOrder('online')" id="payOnlineBtn" class="btn-bouncy py-4 text-lg w-full" style="background:#4ECDC4;color:#fff;">
      <img src="https://files.svgcdn.io/solar/qr-code-bold.svg" width="20" height="20" class="inline mr-2" />Pay Online (UPI)
    </button>
    <button onclick="placeOrder('cash')" id="payCashBtn" class="btn-bouncy btn-outline py-4 text-lg w-full">
      <img src="https://files.svgcdn.io/mdi/cash.svg" width="20" height="20" class="inline mr-2" />Pay at Counter
    </button>
  </div>
</main>

<script>
function placeOrder(mode) {
  const items = getCart();
  if (items.length === 0) { showToast('Cart is empty!', 'error'); return; }
  if (!verifyChecksum()) { showToast('Cart integrity check failed', 'error'); return; }

  const instructions = document.getElementById('specialInstructions').value.trim();
  const total = getCartTotal();
  const table = <?= $tableNum ?>;

  fetch('/data/save-order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ items, total, instructions, payment: mode, table })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      localStorage.removeItem('smak_cart');
      localStorage.removeItem('smak_checksum');
      window.location.href = '/invoice.php?order=' + data.order_id + '&paid=' + (mode === 'online' ? '1' : '0');
    } else {
      showToast('Order failed: ' + (data.error || 'unknown'), 'error');
    }
  })
  .catch(() => showToast('Network error', 'error'));
}

document.addEventListener('DOMContentLoaded', updateCartDisplay);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
