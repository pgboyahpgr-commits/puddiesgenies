<?php require_once __DIR__ . '/includes/header.php'; ?>
<main class="max-w-6xl mx-auto px-4 pb-24">
  <div class="text-center mb-6 pt-2">
    <h1 class="text-4xl font-bold" style="color:var(--text);">📋 <span style="color:#f68e9a;" data-translate>Menu</span></h1>
    <p class="text-gray-500 mt-1 text-sm" data-translate>Fresh · Seasonal · Crafted with care</p>
  </div>

  <div class="relative mb-4 max-w-xl mx-auto">
    <input type="text" id="searchInput" placeholder="Search dishes, ingredients, categories..." class="w-full px-5 py-3.5 pl-12 rounded-2xl border-2 border-gray-200 bg-white/80 outline-none focus:border-[#f68e9a] transition text-sm shadow-sm" />
    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg" data-translate>🔍</span>
    <button id="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-lg hidden" style="background:none;border:none;cursor:pointer;" data-translate>✕</button>
  </div>

  <div id="categoryTabs" class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide"></div>
  <div id="menuContent">
    <div class="text-center py-16 text-gray-400" id="menuLoading">
      <div class="w-12 h-12 border-4 border-gray-200 border-t-[#f68e9a] rounded-full animate-spin mx-auto mb-4"></div>
      <div class="font-bold text-lg" data-translate>Loading menu...</div>
    </div>
  </div>
</main>

<style>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.cat-tab {
  white-space: nowrap; padding: 0.5rem 1.2rem; border-radius: 100px; font-size: 0.85rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; border: 1.5px solid #e5e5e5; background: rgba(255,255,255,0.6); color: #666;
  flex-shrink: 0;
}
.cat-tab:hover { border-color: #f68e9a; color: #f68e9a; }
.cat-tab.active { background: #f68e9a; color: #fff; border-color: #f68e9a; }
.menu-section { scroll-margin-top: 1rem; }
.menu-section-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.8rem 0; margin-bottom: 0.5rem;
  border-bottom: 3px solid #f7b6bf;
}
.menu-section-header h2 { font-family: 'Fredoka', sans-serif; font-size: 1.4rem; font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 0.5rem; }
.menu-section-header .badge { font-size: 0.7rem; font-weight: 500; background: #f7b6bf; color: #5a4e3e; padding: 0.1rem 0.7rem; border-radius: 100px; }
.dish-grid { display: grid; grid-template-columns: 1fr; gap: 0.7rem; }
@media (min-width: 640px) { .dish-grid { grid-template-columns: 1fr 1fr; } }
@media (min-width: 1024px) { .dish-grid { grid-template-columns: 1fr 1fr 1fr; } }
.dish-card {
  display: flex; align-items: center; gap: 0.8rem;
  background: rgba(255,255,255,0.7); backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.3); border-radius: 16px;
  padding: 0.8rem; box-shadow: 0 2px 12px rgba(0,0,0,0.04);
  transition: transform 0.2s, box-shadow 0.2s;
}
.dish-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(0,0,0,0.08); }
.dish-card:active { transform: scale(0.98); }
.dish-img { width: 72px; height: 72px; border-radius: 12px; overflow: hidden; background: #f0ede7; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
.dish-img img { width: 100%; height: 100%; object-fit: cover; }
.dish-info { flex: 1; min-width: 0; }
.dish-info .dish-name { font-weight: 600; color: var(--text); font-size: 0.95rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.dish-info .dish-name .veg-badge { font-size: 0.7rem; }
.dish-info .dish-name .pop-badge { font-size: 0.65rem; background: #f7b6bf; padding: 0.05rem 0.5rem; border-radius: 100px; }
.dish-info .dish-desc { font-size: 0.75rem; color: #999; margin-top: 0.1rem; line-height: 1.3; }
.dish-tags { display: flex; gap: 0.25rem; flex-wrap: wrap; margin-top: 0.2rem; }
.dish-tag { font-size: 0.65rem; color: #888; background: #f0ede7; padding: 0.05rem 0.5rem; border-radius: 100px; white-space: nowrap; }
.dish-right { display: flex; flex-direction: column; align-items: flex-end; gap: 0.4rem; flex-shrink: 0; }
.dish-price { font-weight: 700; color: var(--text); background: #f7b6bf; padding: 0.15rem 0.8rem; border-radius: 100px; font-size: 0.9rem; white-space: nowrap; }
.dish-add-btn {
  width: 34px; height: 34px; border-radius: 50%; border: none;
  background: #538bdf; color: #fff; font-size: 1.2rem; font-weight: 700;
  cursor: pointer; transition: transform 0.2s, background 0.15s;
  display: flex; align-items: center; justify-content: center; line-height: 1;
}
.dish-add-btn:hover { transform: scale(1.15); background: #f68e9a; }
.dish-add-btn:active { transform: scale(0.9); }
.dish-add-btn.added { background: #538bdf; transform: scale(1.2); }
.no-results { text-align: center; padding: 3rem 1rem; color: #999; }
.no-results .icon { font-size: 3rem; margin-bottom: 0.5rem; }
</style>

<script>
(function() {
  var searchInput = document.getElementById('searchInput');
  var clearBtn = document.getElementById('clearSearch');
  var tabs = document.getElementById('categoryTabs');
  var content = document.getElementById('menuContent');
  var searchTerm = '';
  var activeCategory = null;
  var menuData = null;

  function loadMenu() {
    var cached = sessionStorage.getItem('smak_menu_render');
    if (cached) {
      try {
        var parsed = JSON.parse(cached);
        if (parsed && parsed.categories) {
          menuData = parsed;
          renderAll();
          return;
        }
      } catch(e) {}
    }

    fetch('/api/menu.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var cats = data?.menu?.categories || [];
        menuData = { categories: cats };
        try { sessionStorage.setItem('smak_menu_render', JSON.stringify(menuData)); } catch(e) {}
        renderAll();
      })
      .catch(function() {
        content.innerHTML = '<div class="text-center py-16 text-gray-400"><div class="text-5xl mb-3" data-translate>📋</div><div class="font-bold text-lg" data-translate>Menu not available</div><div class="text-sm mt-1" data-translate>Please check back later</div></div>';
      });
  }

  function getAllDishes() {
    if (!menuData) return [];
    var dishes = [];
    for (var ci = 0; ci < menuData.categories.length; ci++) {
      var cat = menuData.categories[ci];
      for (var ii = 0; ii < (cat.items || []).length; ii++) {
        var item = cat.items[ii];
        item.category_name = cat.name;
        item.category_id = cat.id;
        dishes.push(item);
      }
    }
    return dishes;
  }

  function renderAll() {
    var cats = menuData.categories;
    if (!cats || !cats.length) {
      content.innerHTML = '<div class="text-center py-16 text-gray-400"><div class="text-5xl mb-3" data-translate>📋</div><div class="font-bold text-lg" data-translate>Menu not available</div><div class="text-sm mt-1" data-translate>Please check back later</div></div>';
      return;
    }
    renderTabs(cats);
    renderSections(cats);
    handleSearchParam();
    filterDishes();
    if (window.retranslate) window.retranslate();
  }

  function renderTabs(cats) {
    tabs.innerHTML = '<div class="cat-tab active" data-cat="" data-translate>All</div>';
    for (var i = 0; i < cats.length; i++) {
      tabs.innerHTML += '<div class="cat-tab" data-cat="' + escAttr(cats[i].id) + '" data-translate>' + escHtml(cats[i].name) + '</div>';
    }
    tabs.querySelectorAll('.cat-tab').forEach(function(t) {
      t.addEventListener('click', function() {
        tabs.querySelectorAll('.cat-tab').forEach(function(x) { x.classList.remove('active'); });
        this.classList.add('active');
        activeCategory = this.dataset.cat;
        filterDishes();
      });
    });
  }

  function renderSections(cats) {
    var html = '';
    for (var ci = 0; ci < cats.length; ci++) {
      var cat = cats[ci];
      var items = cat.items || [];
      html += '<div class="menu-section" data-cat-id="' + escAttr(cat.id) + '">';
      html += '<div class="menu-section-header"><h2>' + escHtml(cat.name) + ' <span class="badge" data-translate>' + items.length + ' item' + (items.length !== 1 ? 's' : '') + '</span></h2></div>';
      if (cat.description) html += '<p class="text-sm text-gray-400 mb-3" data-translate>' + escHtml(cat.description) + '</p>';
      html += '<div class="dish-grid">';
      for (var ii = 0; ii < items.length; ii++) {
        var item = items[ii];
        html += buildDishCard(item, cat.id);
      }
      html += '</div></div>';
    }
    content.innerHTML = html;
  }

  function buildDishCard(item, catId) {
    var price = parseInt(item.price) || 0;
    var name = escHtml(item.name || '');
    var desc = escHtml(item.description || '');
    var image = escAttr(item.image || '');
    var spice = escHtml(item.spice_level || '');
    var region = escHtml(item.region || '');
    var isVeg = item.is_vegetarian;
    var isPop = item.popular;
    var id = item.id || 'i_' + Date.now() + '_' + Math.floor(Math.random() * 900 + 100);

    var imgHtml = image ? '<img src="' + image + '" alt="' + name + '" loading="lazy" />' : '<span style="font-size:1.8rem;" data-translate>🍽️</span>';

    return '<div class="dish-card" data-dish-id="' + id + '" data-dish-name="' + escAttr(item.name) + '" data-dish-price="' + price + '" data-dish-image="' + image + '" data-category="' + escAttr(catId) + '" data-dish-region="' + escAttr(item.region || '') + '" data-dish-veg="' + (isVeg ? '1' : '0') + '" data-dish-popular="' + (isPop ? '1' : '0') + '" data-dish-spice="' + escAttr(item.spice_level || 'mild') + '" data-dish-desc="' + escAttr(item.description || '') + '">'
      + '<div class="dish-img" data-translate>' + imgHtml + '</div>'
      + '<div class="dish-info">'
      + '<div class="dish-name">' + name + ' ' + (isVeg ? '<span class="veg-badge" data-translate>🥬</span>' : '<span class="veg-badge" data-translate>🍗</span>') + (isPop ? '<span class="pop-badge" data-translate>⭐ Popular</span>' : '') + '</div>'
      + (desc ? '<div class="dish-desc" data-translate>' + desc + '</div>' : '')
      + '<div class="dish-tags">'
      + (spice ? '<span class="dish-tag" data-translate>🌶️ ' + spice + '</span>' : '')
      + (region ? '<span class="dish-tag" data-translate>📍 ' + region + '</span>' : '')
      + '</div></div>'
      + '<div class="dish-right">'
      + '<div class="dish-price" data-translate>₹' + price + '</div>'
      + '<button class="dish-add-btn" title="Add ' + name + '">+</button>'
      + '</div></div>';
  }

  function filterDishes() {
    var term = searchTerm.toLowerCase().trim();
    var sections = content.querySelectorAll('.menu-section');
    var hasResults = false;

    sections.forEach(function(section) {
      var catId = section.dataset.catId;
      var catMatch = !activeCategory || catId === activeCategory;
      var cards = section.querySelectorAll('.dish-card');
      var visibleCount = 0;

      cards.forEach(function(card) {
        var name = (card.dataset.dishName || '').toLowerCase();
        var desc = (card.querySelector('.dish-desc')?.textContent || '').toLowerCase();
        var match = (!term || name.indexOf(term) >= 0 || desc.indexOf(term) >= 0);
        var show = match && catMatch;
        card.style.display = show ? '' : 'none';
        if (show) visibleCount++;
      });

      var sectionVisible = catMatch && (!term || visibleCount > 0);
      section.style.display = sectionVisible ? '' : 'none';
      if (sectionVisible) hasResults = true;
    });

    var existing = content.querySelector('.no-results');
    if (!hasResults && !existing) {
      var div = document.createElement('div');
      div.className = 'no-results';
      div.innerHTML = '<div class="icon" data-translate>😕</div><div class="font-bold text-lg" data-translate>No dishes found</div><div class="text-sm mt-1" data-translate>Try a different search term</div>';
      content.appendChild(div);
    } else if (hasResults && existing) {
      existing.remove();
    }
  }

  function handleSearchParam() {
    var params = new URLSearchParams(window.location.search);
    var searchParam = params.get('search');
    if (searchParam) {
      searchInput.value = searchParam;
      searchTerm = searchParam;
      clearBtn.classList.remove('hidden');
      setTimeout(function() {
        var visible = content.querySelector('.dish-card:not([style*="display: none"])');
        if (visible) visible.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 300);
    }
  }

  function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function escAttr(str) {
    if (!str) return '';
    return String(str).replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // Events
  searchInput.addEventListener('input', function() {
    searchTerm = this.value;
    clearBtn.classList.toggle('hidden', !searchTerm);
    activeCategory = null;
    tabs.querySelectorAll('.cat-tab').forEach(function(t) { t.classList.remove('active'); });
    var at = tabs.querySelector('.cat-tab:first-child');
    if (at) at.classList.add('active');
    filterDishes();
  });

  clearBtn.addEventListener('click', function() {
    searchInput.value = '';
    searchTerm = '';
    clearBtn.classList.add('hidden');
    filterDishes();
  });

  loadMenu();
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
