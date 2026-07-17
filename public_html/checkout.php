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

  <div class="glass-card p-4 mb-6" id="priceBreakdown">
    <div id="subtotalRow" class="flex justify-between text-sm text-gray-500 mb-1">
      <span>Subtotal</span>
      <span id="subtotalAmount">₹0</span>
    </div>
    <div id="taxRow" class="flex justify-between text-sm text-gray-500 mb-1">
      <span>Tax (5% GST)</span>
      <span id="taxAmount">₹0</span>
    </div>
    <div class="border-t border-gray-200 my-2"></div>
    <div class="flex justify-between items-center">
      <span class="font-bold text-lg">Total</span>
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
// Override updateCartDisplay to show itemized breakdown with calculated prices
var origUpdateDisplay = window.updateCartDisplay;
window.updateCartDisplay = function() {
  var container = document.getElementById('cartItems');
  if (!container) return;
  var items = window.getCart ? window.getCart() : [];
  if (items.length === 0) {
    container.innerHTML = '<div class="glass-card p-6 text-center"><div class="text-4xl mb-2">🛒</div><p class="text-gray-400">Your cart is empty</p><a href="/menu.php" class="inline-block mt-3 btn-bouncy px-6 py-2 no-underline" style="background:#FF6B6B;color:#fff;">Browse Menu</a></div>';
    document.getElementById('cartTotal').textContent = '₹0';
    if (document.getElementById('subtotalAmount')) document.getElementById('subtotalAmount').textContent = '₹0';
    if (document.getElementById('taxAmount')) document.getElementById('taxAmount').textContent = '₹0';
    return;
  }

  var html = '';
  var subtotal = 0;
  for (var i = 0; i < items.length; i++) {
    var item = items[i];
    var itemTotal = (item.price || 0) * (item.qty || 1);
    subtotal += itemTotal;
    var id = String(item.id).replace(/'/g, "\\'");
    html += '<div class="flex items-center justify-between glass-card p-3 mb-2">';
    html += '<div class="flex-1 min-w-0">';
    html += '<div class="font-semibold text-sm">' + escapeHtml(item.name) + '</div>';
    html += '<div class="text-xs text-gray-400">₹' + (item.price || 0) + ' × ' + (item.qty || 1) + '</div>';
    html += '</div>';
    html += '<div class="flex items-center gap-3">';
    html += '<div class="flex items-center border-2 border-gray-200 rounded-full overflow-hidden">';
    html += '<button class="cart-qty-btn" data-qty-id="' + id + '" data-qty-delta="-1" style="width:30px;height:30px;border:none;background:transparent;font-size:1.1rem;font-weight:700;cursor:pointer;">−</button>';
    html += '<span class="font-bold w-6 text-center text-sm">' + (item.qty || 1) + '</span>';
    html += '<button class="cart-qty-btn" data-qty-id="' + id + '" data-qty-delta="1" style="width:30px;height:30px;border:none;background:transparent;font-size:1.1rem;font-weight:700;cursor:pointer;">+</button>';
    html += '</div>';
    html += '<div class="font-bold text-sm w-16 text-right" style="color:#FF6B6B;">₹' + itemTotal + '</div>';
    html += '</div></div>';
  }
  container.innerHTML = html;

  var tax = Math.round(subtotal * 0.05);
  var total = subtotal + tax;

  document.getElementById('cartTotal').textContent = '₹' + total;
  if (document.getElementById('subtotalAmount')) document.getElementById('subtotalAmount').textContent = '₹' + subtotal;
  if (document.getElementById('taxAmount')) document.getElementById('taxAmount').textContent = '₹' + tax;
};

function placeOrder(mode) {
  var items = window.getCart ? window.getCart() : [];
  if (items.length === 0) { if (window.showToast) window.showToast('Cart is empty!', 'error'); return; }

  var instructions = document.getElementById('specialInstructions').value.trim();
  var subtotal = 0;
  for (var i = 0; i < items.length; i++) subtotal += (items[i].price || 0) * (items[i].qty || 1);
  var tax = Math.round(subtotal * 0.05);
  var total = subtotal + tax;
  var table = <?= $tableNum ?>;

  document.getElementById('payOnlineBtn').disabled = true;
  document.getElementById('payCashBtn').disabled = true;

  fetch('/api/save-order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ items: items, total: total, subtotal: subtotal, tax: tax, instructions: instructions, payment: mode, table: table })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success) {
      if (window.clearServerCart) window.clearServerCart();
      window.location.href = '/invoice.php?order=' + data.order_id + '&paid=' + (mode === 'online' ? '1' : '0');
    } else {
      if (window.showToast) window.showToast('Order failed: ' + (data.error || 'unknown'), 'error');
      document.getElementById('payOnlineBtn').disabled = false;
      document.getElementById('payCashBtn').disabled = false;
    }
  })
  .catch(function() {
    if (window.showToast) window.showToast('Network error', 'error');
    document.getElementById('payOnlineBtn').disabled = false;
    document.getElementById('payCashBtn').disabled = false;
  });
}

function escapeHtml(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

document.addEventListener('DOMContentLoaded', function() {
  window.updateCartDisplay();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
