(function() {
var CART_KEY = 'smak_cart';
var TOKEN_KEY = 'smak_cart_token';
var SERVER_URL = '/api/cart.php';

function getCartToken() {
  var token = localStorage.getItem(TOKEN_KEY);
  if (!token) {
    token = 'cart_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem(TOKEN_KEY, token);
  }
  return token;
}

function getCart() {
  try {
    var raw = localStorage.getItem(CART_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch(e) { return []; }
}

function setCart(items) {
  localStorage.setItem(CART_KEY, JSON.stringify(items));
  updateCartBadge();
  syncToServer(items);
}

var syncTimer = null;
function syncToServer(items) {
  if (syncTimer) clearTimeout(syncTimer);
  syncTimer = setTimeout(function() {
    var token = getCartToken();
    fetch(SERVER_URL + '?token=' + encodeURIComponent(token), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: items || getCart(), token: token })
    }).catch(function() {});
  }, 500);
}

function loadCartFromServer(callback) {
  var token = getCartToken();
  fetch(SERVER_URL + '?token=' + encodeURIComponent(token), { cache: 'no-store' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success && data.items && data.items.length > 0) {
        localStorage.setItem(CART_KEY, JSON.stringify(data.items));
      }
      updateCartBadge();
      updateCartDisplay();
      if (callback) callback(data);
    })
    .catch(function() {
      if (callback) callback(null);
    });
}

function clearServerCart() {
  var token = getCartToken();
  fetch(SERVER_URL + '?token=' + encodeURIComponent(token), { method: 'DELETE' })
    .catch(function() {});
  localStorage.removeItem(CART_KEY);
  updateCartBadge();
}

function getCartCount() {
  var items = getCart();
  var count = 0;
  for (var i = 0; i < items.length; i++) count += (items[i].qty || 1);
  return count;
}

function getCartTotal() {
  var items = getCart();
  var total = 0;
  for (var i = 0; i < items.length; i++) total += (items[i].price || 0) * (items[i].qty || 1);
  return total;
}

function updateCartBadge() {
  var badge = document.getElementById('cartBadge');
  var count = getCartCount();
  if (badge) {
    if (count > 0) { badge.textContent = count; badge.style.display = 'flex'; }
    else { badge.style.display = 'none'; }
  }
  var floatBtn = document.getElementById('floatCartBtn');
  var floatCount = document.getElementById('floatCartCount');
  if (floatBtn && floatCount) {
    if (count > 0) {
      floatBtn.style.display = 'block';
      floatCount.textContent = count;
      var inner = floatBtn.querySelector('.float-cart-inner');
      if (inner) inner.classList.add('float-cart-pulse');
    } else { floatBtn.style.display = 'none'; }
  }
  var mobileBar = document.getElementById('mobileCartBar');
  var mobileCount = document.getElementById('mobileCartCount');
  var mobileTotal = document.getElementById('mobileCartTotal');
  if (mobileBar && mobileCount && mobileTotal) {
    if (count > 0) {
      mobileBar.style.display = 'block';
      requestAnimationFrame(function() { mobileBar.style.transform = 'translateY(0)'; });
      mobileCount.textContent = count + ' item' + (count !== 1 ? 's' : '');
      mobileTotal.textContent = '\u20B9' + getCartTotal();
    } else {
      mobileBar.style.transform = 'translateY(100%)';
      setTimeout(function() { mobileBar.style.display = 'none'; }, 300);
    }
  }
  if (typeof window.updateMobileCart === 'function') window.updateMobileCart();
}

function addToCart(dish) {
  var items = getCart();
  var existing = null;
  for (var i = 0; i < items.length; i++) {
    if (String(items[i].id) === String(dish.id)) { existing = items[i]; break; }
  }
  if (existing) { existing.qty = (existing.qty || 1) + 1; }
  else { items.push({ id: dish.id, name: dish.name, price: dish.price, qty: 1, image: dish.image || '' }); }
  setCart(items);
  showToast((dish.name || 'Item') + ' added!', 'success');
}

function removeFromCart(id) {
  var items = getCart();
  var filtered = [];
  for (var i = 0; i < items.length; i++) { if (String(items[i].id) !== String(id)) filtered.push(items[i]); }
  setCart(filtered);
  updateCartDisplay();
}

function updateQty(id, delta) {
  var items = getCart();
  var item = null;
  for (var i = 0; i < items.length; i++) { if (String(items[i].id) === String(id)) { item = items[i]; break; } }
  if (!item) return;
  item.qty = Math.max(0, (item.qty || 1) + delta);
  if (item.qty === 0) { removeFromCart(id); return; }
  setCart(items);
  updateCartDisplay();
}

function updateCartDisplay() {
  var container = document.getElementById('cartItems');
  var totalEl = document.getElementById('cartTotal');
  if (!container) return;
  var items = getCart();
  if (items.length === 0) {
    container.innerHTML = '<div class="text-center py-8 text-gray-400">Your cart is empty</div>';
    if (totalEl) totalEl.textContent = '\u20B90';
    updateCartBadge();
    return;
  }
  var html = '';
  for (var i = 0; i < items.length; i++) {
    var item = items[i];
    var id = String(item.id).replace(/'/g, "\\'");
    html += '<div class="flex items-center justify-between glass-card p-3 mb-2" data-cart-id="' + id + '">';
    html += '<div class="flex-1"><div class="font-semibold text-sm">' + escapeHtml(item.name) + '</div><div class="text-xs text-gray-400">\u20B9' + (item.price || 0) + ' \u00d7 ' + (item.qty || 1) + '</div></div>';
    html += '<div class="flex items-center gap-2">';
    html += '<button class="cart-qty-btn" data-qty-id="' + id + '" data-qty-delta="-1" style="width:32px;height:32px;border-radius:50%;border:none;background:#f0ede7;font-size:1.2rem;font-weight:700;cursor:pointer;">\u2212</button>';
    html += '<span class="font-bold w-6 text-center">' + (item.qty || 1) + '</span>';
    html += '<button class="cart-qty-btn" data-qty-id="' + id + '" data-qty-delta="1" style="width:32px;height:32px;border-radius:50%;border:none;background:#f0ede7;font-size:1.2rem;font-weight:700;cursor:pointer;">+</button>';
    html += '</div></div>';
  }
  container.innerHTML = html;
  if (totalEl) totalEl.textContent = '\u20B9' + getCartTotal();
  updateCartBadge();
}

function escapeHtml(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function showToast(msg, type) {
  if (!type) type = 'info';
  var container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  var el = document.createElement('div');
  el.className = 'toast toast-' + type;
  el.textContent = msg;
  container.appendChild(el);
  requestAnimationFrame(function() { el.classList.add('show'); });
  setTimeout(function() {
    el.classList.remove('show');
    setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 400);
  }, 2500);
}

function upgradeDishImage(dishName, imgEl) {
  if (!dishName || !imgEl) return;
  var key = 'smak_img_' + String(dishName).replace(/\s+/g, '_');
  var cached = localStorage.getItem(key);
  if (cached) { imgEl.src = cached; return; }
  fetch('/includes/image-fetcher.php?dish=' + encodeURIComponent(dishName))
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data && data.url) { imgEl.src = data.url; localStorage.setItem(key, data.url); }
    })
    .catch(function() {});
}

// Event delegation for all cart buttons
document.addEventListener('click', function(e) {
  var addBtn = e.target.closest('.dish-add-btn');
  if (addBtn) {
    var card = addBtn.closest('.dish-card');
    if (card) {
      addToCart({
        id: card.dataset.dishId,
        name: card.dataset.dishName,
        price: parseInt(card.dataset.dishPrice) || 0,
        image: card.dataset.dishImage || ''
      });
      addBtn.classList.add('added');
      setTimeout(function() { addBtn.classList.remove('added'); }, 300);
    }
    return;
  }
  var qtyBtn = e.target.closest('.cart-qty-btn');
  if (qtyBtn) {
    updateQty(qtyBtn.dataset.qtyId, parseInt(qtyBtn.dataset.qtyDelta) || 0);
    return;
  }
});

document.addEventListener('DOMContentLoaded', function() {
  updateCartBadge();
  updateCartDisplay();
  // Load cart from server in background (fixes phantom items)
  loadCartFromServer();
  try {
    gsap.from('.glass-card', { y: 40, opacity: 0, duration: 0.6, stagger: 0.06, ease: 'back.out(1.7)' });
  } catch(e) {}
});

// Expose public functions
window.getCart = getCart;
window.getCartTotal = getCartTotal;
window.updateCartDisplay = updateCartDisplay;
window.showToast = showToast;
window.upgradeDishImage = upgradeDishImage;
window.addToCart = addToCart;
window.updateCartBadge = updateCartBadge;
window.clearServerCart = clearServerCart;
window.syncToServer = syncToServer;
})();
