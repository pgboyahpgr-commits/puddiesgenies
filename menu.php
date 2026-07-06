<?php require_once __DIR__ . '/includes/header.php'; ?>
<main class="max-w-5xl mx-auto px-4 pb-12">
  <div id="app">
    <div class="text-center mb-8 pt-2">
      <h1 class="text-4xl font-bold" style="color:#2D3436;">📋 <span style="color:#FF6B6B;">Menu</span></h1>
      <p class="text-gray-500 mt-1">Fresh · Seasonal · Crafted with care</p>
    </div>

    <div id="status" class="text-center py-12 text-gray-400">
      <div class="inline-block w-8 h-8 border-4 border-gray-200 border-t-red-400 rounded-full animate-spin mb-2"></div>
      <div>Loading menu…</div>
    </div>

    <div id="menuContent" class="hidden">
      <div id="categoryGrid" class="space-y-6"></div>
      <div class="text-center mt-6">
        <a href="/checkout.php" class="btn-bouncy px-8 py-3 no-underline inline-block" style="background:#FF6B6B;color:#fff;">🛒 View Cart & Checkout</a>
      </div>
      <div class="text-center text-xs text-gray-300 mt-4 pt-4 border-t border-gray-100">
        Data from <a id="dataSourceLink" href="#" class="text-gray-400" target="_blank">menu.json</a>
      </div>
    </div>
  </div>
</main>

<style>
.menu-card { background: rgba(255,255,255,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; padding: 1.5rem 1.8rem; box-shadow: 0 4px 24px rgba(0,0,0,0.04); }
.menu-card h2 { font-family: 'Fredoka', sans-serif; font-size: 1.5rem; font-weight: 600; color: #2D3436; border-bottom: 3px solid #FFE66D; padding-bottom: 0.5rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
.menu-card h2 .badge { font-size: 0.7rem; font-weight: 500; background: #FFE66D; color: #5a4e3e; padding: 0.1rem 0.7rem; border-radius: 100px; margin-left: auto; }
.menu-card .cat-desc { color: #888; font-size: 0.9rem; margin-top: -0.5rem; margin-bottom: 1rem; }
.items-grid { display: grid; grid-template-columns: 1fr; gap: 0.6rem; }
@media (min-width: 640px) { .items-grid { grid-template-columns: 1fr 1fr; } }
.item-row { display: flex; align-items: center; gap: 0.8rem; padding: 0.5rem 0.6rem; border-radius: 12px; transition: background 0.15s; }
.item-row:hover { background: rgba(255,255,255,0.5); }
.item-img { width: 60px; height: 60px; border-radius: 10px; overflow: hidden; background: #f0ede7; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.item-img img { width: 100%; height: 100%; object-fit: cover; }
.item-info { flex: 1; min-width: 0; }
.item-info .name { font-weight: 600; color: #2D3436; font-size: 0.95rem; }
.item-info .desc { font-size: 0.8rem; color: #999; margin-top: 0.05rem; }
.item-price { font-weight: 700; color: #2D3436; background: #FFE66D; padding: 0.1rem 0.8rem; border-radius: 100px; font-size: 0.9rem; white-space: nowrap; }
.item-tags { display: flex; gap: 0.3rem; flex-wrap: wrap; margin-top: 0.2rem; }
.item-tag { font-size: 0.7rem; color: #888; background: #f0ede7; padding: 0.05rem 0.5rem; border-radius: 100px; white-space: nowrap; }
.item-add-btn { flex-shrink: 0; width: 36px; height: 36px; border-radius: 50%; border: none; background: #4ECDC4; color: #fff; font-size: 1.3rem; font-weight: 700; cursor: pointer; transition: transform 0.2s, background 0.2s; display: flex; align-items: center; justify-content: center; line-height: 1; }
.item-add-btn:hover { transform: scale(1.15); background: #FF6B6B; }
.item-add-btn:active { transform: scale(0.9); }
</style>

<script>
(function() {
  const MENU_URL = 'https://gist.githubusercontent.com/pgboyahpgr-commits/52f32ffc786ed004a6cbdb5ad04193cd/raw/menu.json';
  const statusEl = document.getElementById('status');
  const menuContent = document.getElementById('menuContent');
  const grid = document.getElementById('categoryGrid');
  const link = document.getElementById('dataSourceLink');

  function render(data) {
    grid.innerHTML = '';
    const cats = data?.menu?.categories || [];
    if (!cats.length) { grid.innerHTML = '<p class="text-center text-gray-400 py-8">No menu categories found.</p>'; return; }

    for (const cat of cats) {
      const name = cat.name || 'Unnamed';
      const desc = cat.description || '';
      const items = cat.items || [];
      const card = document.createElement('div');
      card.className = 'menu-card';

      const h2 = document.createElement('h2');
      h2.innerHTML = `<span>${name}</span><span class="badge">${items.length} item${items.length !== 1 ? 's' : ''}</span>`;
      card.appendChild(h2);

      if (desc) { const p = document.createElement('p'); p.className = 'cat-desc'; p.textContent = desc; card.appendChild(p); }

      const list = document.createElement('div');
      list.className = 'items-grid';

      for (const item of items) {
        const row = document.createElement('div');
        row.className = 'item-row';

        const imgDiv = document.createElement('div');
        imgDiv.className = 'item-img';
        if (item.image) { const img = document.createElement('img'); img.src = item.image; img.alt = item.name; img.loading = 'lazy'; imgDiv.appendChild(img); }
        else { imgDiv.textContent = '🍽️'; }
        row.appendChild(imgDiv);

        const info = document.createElement('div');
        info.className = 'item-info';

        const nameSpan = document.createElement('div');
        nameSpan.className = 'name';
        nameSpan.textContent = item.name || 'Unnamed';
        info.appendChild(nameSpan);

        if (item.description) { const d = document.createElement('div'); d.className = 'desc'; d.textContent = item.description; info.appendChild(d); }

        const tags = document.createElement('div');
        tags.className = 'item-tags';
        const meta = { spice_level: '🌶️', region: '📍', cooking_time: '⏱️', calories: '🔥' };
        for (const [k, e] of Object.entries(meta)) {
          let v = item[k];
          if (v !== undefined && v !== null && v !== '') {
            const tag = document.createElement('span'); tag.className = 'item-tag';
            tag.textContent = e + ' ' + v; tags.appendChild(tag);
          }
        }
        info.appendChild(tags);
        row.appendChild(info);

        if (item.price !== undefined && item.price !== null && item.price !== '') {
          const price = document.createElement('span'); price.className = 'item-price';
          const num = parseFloat(item.price); price.textContent = '₹' + (isNaN(num) ? item.price : num);
          row.appendChild(price);
        }

        const addBtn = document.createElement('button');
        addBtn.className = 'item-add-btn';
        addBtn.textContent = '+';
        addBtn.title = 'Add ' + (item.name || '') + ' to cart';
        addBtn.onclick = function(e) {
          e.stopPropagation();
          if (typeof addToCart === 'function') {
            addToCart({ id: item.id || ('item_' + Date.now()), name: item.name || 'Unnamed', price: item.price || 0, image: item.image || '' });
          }
        };
        row.appendChild(addBtn);

        list.appendChild(row);
      }
      card.appendChild(list);
      grid.appendChild(card);
    }
  }

  async function load() {
    try {
      const r = await fetch(MENU_URL, { headers: { Accept: 'application/json' } });
      if (!r.ok) throw new Error('HTTP ' + r.status);
      const data = await r.json();
      statusEl.classList.add('hidden');
      menuContent.classList.remove('hidden');
      link.href = MENU_URL;
      link.textContent = 'menu.json';
      render(data);
    } catch (e) {
      console.error(e);
      statusEl.className = 'text-center py-12 text-red-400';
      statusEl.innerHTML = `<div style="font-size:2rem;margin-bottom:0.4rem;">⚠️</div><div><strong>Could not load menu</strong></div><div style="font-size:0.9rem;margin-top:0.3rem;color:#bfa;">${e.message}</div><button onclick="location.reload()" style="margin-top:1rem;padding:0.5rem 1.8rem;border:none;background:#FF6B6B;color:#fff;border-radius:100px;cursor:pointer;">Retry</button>`;
    }
  }
  load();
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
