// ─── Dish Detail Modal ──────────────────────────────────────────
(function() {
var modalHTML = '';
var modalInit = false;

function getModalHTML() {
  return '<div id="dishModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display:none;background:rgba(0,0,0,0.55);backdrop-filter:blur(4px);" onclick="if(event.target===this)DishModal.close()">'
    + '<div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-2xl" style="animation:dishModalIn 0.3s cubic-bezier(0.34,1.56,0.64,1);border:1px solid rgba(255,255,255,0.3);" onclick="event.stopPropagation()">'
    + '<div class="relative">'
    + '<button onclick="DishModal.close()" class="absolute top-3 right-3 w-9 h-9 rounded-full bg-white/90 shadow flex items-center justify-center text-lg z-10 hover:bg-white" style="border:none;cursor:pointer;">✕</button>'
    + '<div id="dishModalImage" class="w-full h-56 bg-gray-100 flex items-center justify-center text-6xl rounded-t-3xl overflow-hidden"></div>'
    + '</div>'
    + '<div class="p-5 pt-4">'
    + '<div class="flex items-start justify-between mb-2">'
    + '<div><h2 id="dishModalName" class="text-2xl font-bold" style="font-family:\'Fredoka\',sans-serif;color:#2D3436;"></h2>'
    + '<div id="dishModalBadges" class="flex gap-2 mt-1"></div></div>'
    + '<div id="dishModalPrice" class="text-2xl font-bold" style="color:#f68e9a;"></div>'
    + '</div>'
    + '<p id="dishModalDesc" class="text-sm text-gray-500 mb-3"></p>'
    + '<div class="flex flex-wrap gap-2 mb-4" id="dishModalTags"></div>'
    + '<div class="flex items-center gap-4 mb-4">'
    + '<div class="flex items-center border-2 border-gray-200 rounded-full overflow-hidden">'
    + '<button onclick="DishModal.changeQty(-1)" class="w-10 h-10 flex items-center justify-center font-bold text-lg hover:bg-gray-100" style="border:none;background:transparent;cursor:pointer;">−</button>'
    + '<span id="dishModalQty" class="w-10 text-center font-bold">1</span>'
    + '<button onclick="DishModal.changeQty(1)" class="w-10 h-10 flex items-center justify-center font-bold text-lg hover:bg-gray-100" style="border:none;background:transparent;cursor:pointer;">+</button>'
    + '</div>'
    + '<button id="dishModalAddBtn" class="flex-1 py-3 rounded-full font-bold text-white text-sm" style="background:#538bdf;border:none;cursor:pointer;" onclick="DishModal.addToCart()">🛒 Add to Cart</button>'
    + '</div>'
    + '<div id="dishModalVideo" class="mb-2" style="display:none;">'
    + '<h4 class="text-sm font-bold mb-2 text-gray-500 uppercase tracking-wider">🎬 Food Videos</h4>'
    + '<div id="dishModalVideoContainer" class="rounded-2xl overflow-hidden bg-gray-100" style="aspect-ratio:16/9;"></div>'
    + '</div>'
    + '</div></div></div>'
    + '<style>@keyframes dishModalIn{from{transform:scale(0.9);opacity:0}to{transform:scale(1);opacity:1}}</style>';
}

window.DishModal = {
  _data: null,
  _qty: 1,

  open: function(dish) {
    if (!modalInit) {
      modalHTML = getModalHTML();
      var div = document.createElement('div');
      div.innerHTML = modalHTML;
      document.body.appendChild(div.firstElementChild);
      modalInit = true;
    }
    this._data = dish;
    this._qty = 1;
    document.getElementById('dishModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    var img = document.getElementById('dishModalImage');
    img.innerHTML = dish.image
      ? '<img src="' + dish.image.replace(/"/g,'&quot;') + '" alt="' + (dish.name||'').replace(/"/g,'&quot;') + '" class="w-full h-full object-cover" />'
      : '🍽️';

    document.getElementById('dishModalName').textContent = dish.name || '';
    document.getElementById('dishModalPrice').textContent = '₹' + (dish.price || 0);

    var badges = document.getElementById('dishModalBadges');
    badges.innerHTML = (dish.is_vegetarian ? '<span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">🥬 Veg</span>' : '<span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full">🍗 Non-Veg</span>')
      + (dish.popular ? '<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">⭐ Popular</span>' : '');

    document.getElementById('dishModalDesc').textContent = dish.description || 'No description available.';

    var tags = document.getElementById('dishModalTags');
    tags.innerHTML = (dish.spice_level ? '<span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">🌶️ ' + dish.spice_level.charAt(0).toUpperCase() + dish.spice_level.slice(1) + '</span>' : '')
      + (dish.region ? '<span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">📍 ' + dish.region + '</span>' : '')
      + (dish.category_name || dish.category ? '<span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">📁 ' + (dish.category_name || dish.category) + '</span>' : '');

    this.updateQtyDisplay();
    this.loadVideo(dish.name);
  },

  close: function() {
    var el = document.getElementById('dishModal');
    if (el) el.style.display = 'none';
    document.body.style.overflow = '';
  },

  changeQty: function(delta) {
    this._qty = Math.max(1, this._qty + delta);
    this.updateQtyDisplay();
  },

  updateQtyDisplay: function() {
    document.getElementById('dishModalQty').textContent = this._qty;
    document.getElementById('dishModalAddBtn').textContent = '🛒 Add to Cart · ₹' + (this._data ? this._data.price * this._qty : 0);
  },

  addToCart: function() {
    if (!this._data) return;
    var d = this._data;
    // Add multiple times based on qty
    if (typeof window.addToCart === 'function') {
      for (var i = 0; i < this._qty; i++) {
        window.addToCart({ id: d.id, name: d.name, price: d.price, image: d.image || '' });
      }
    }
    this.close();
    if (typeof window.showToast === 'function') {
      window.showToast('🛒 ' + d.name + ' × ' + this._qty + ' added!', 'success');
    }
  },

  loadVideo: function(dishName) {
    var container = document.getElementById('dishModalVideoContainer');
    var section = document.getElementById('dishModalVideo');
    if (!container || !section) return;
    section.style.display = 'none';
    container.innerHTML = '<div class="w-full h-full flex items-center justify-center text-gray-300 text-sm">Loading...</div>';

    var apis = [
      'https://ytapis.djalokyt27.workers.dev/search?q=' + encodeURIComponent(dishName + ' recipe'),
      'https://invidious.private.coffee/api/v1/search?q=' + encodeURIComponent(dishName + ' recipe') + '&type=video&sort=relevance',
    ];

    function tryApi(idx) {
      if (idx >= apis.length) {
        container.innerHTML = '<div class="w-full h-full flex items-center justify-center text-gray-300 text-sm">No videos found</div>';
        return;
      }
      fetch(apis[idx])
        .then(function(r) { if (!r.ok) throw new Error(); return r.json(); })
        .then(function(data) {
          var videos = [];
          if (Array.isArray(data)) videos = data.filter(function(v) { return v && v.id; });
          else if (data && Array.isArray(data.videos)) videos = data.videos.filter(function(v) { return v && v.videoId; });
          if (videos.length > 0) {
            var v = videos[0];
            var vid = v.id || v.videoId;
            section.style.display = 'block';
            container.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + vid + '?autoplay=0&rel=0" class="w-full h-full" allow="autoplay; encrypted-media" allowfullscreen style="border:none;"></iframe>';
          } else {
            tryApi(idx + 1);
          }
        })
        .catch(function() { tryApi(idx + 1); });
    }
    tryApi(0);
  }
};

// Open modal on dish card click (not on add button click)
document.addEventListener('click', function(e) {
  if (e.target.closest('.dish-add-btn')) return;
  var card = e.target.closest('.dish-card');
  if (card && typeof DishModal !== 'undefined') {
    DishModal.open({
      id: card.dataset.dishId || 'd_' + Date.now(),
      name: card.dataset.dishName || '',
      price: parseInt(card.dataset.dishPrice) || 0,
      image: card.dataset.dishImage || '',
      description: card.dataset.dishDesc || card.querySelector('.dish-desc')?.textContent || '',
      spice_level: card.dataset.dishSpice || 'mild',
      region: card.dataset.dishRegion || '',
      is_vegetarian: card.dataset.dishVeg === '1',
      popular: card.dataset.dishPopular === '1',
      category_name: card.dataset.category || '',
      category: card.dataset.category || ''
    });
  }
});

})();
