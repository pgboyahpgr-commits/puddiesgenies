<?php
require_once __DIR__ . '/includes/header.php';
$dishQuery = $_GET['dish'] ?? '';
?>
<main class="max-w-6xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-3"></div>
  <div class="text-center mb-8 pt-4">
    <h1 class="text-4xl font-bold" style="color:#2D3436;">🎬 <span style="color:#FFE66D;">Food Vlogs</span></h1>
    <p class="text-gray-500 mt-2">Discover dishes through food vlogs</p>
  </div>

  <div class="flex gap-3 mb-6 max-w-xl mx-auto">
    <input type="text" id="vlogSearch" placeholder="Search food vlogs..." value="<?= htmlspecialchars($dishQuery) ?>" class="flex-1 px-5 py-3 rounded-full border-2 border-gray-200 bg-white/80 outline-none focus:border-[#FFE66D] transition text-sm" />
    <button onclick="searchVlogs()" class="btn-bouncy px-6" style="background:#FFE66D;color:#2D3436;font-weight:700;">🔍</button>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5" id="vlogGrid">
    <div class="col-span-full text-center py-12 text-gray-400" id="vlogStatus">
      🔍 Search for any dish to see food vlogs
    </div>
  </div>
</main>

<script>
let currentVideos = [];

function searchVlogs() {
  const q = document.getElementById('vlogSearch').value.trim();
  if (!q) return;
  const grid = document.getElementById('vlogGrid');
  const status = document.getElementById('vlogStatus');
  grid.innerHTML = `<div class="col-span-full text-center py-12"><div class="w-10 h-10 border-4 border-gray-200 border-t-[#FFE66D] rounded-full animate-spin mx-auto mb-3"></div><p class="text-gray-400">Searching for "${q}"...</p></div>`;

  fetch('https://ytapis.djalokyt27.workers.dev/search?q=' + encodeURIComponent(q + ' food vlog'))
    .then(r => r.json())
    .then(data => {
      if (!Array.isArray(data) || data.length === 0) {
        grid.innerHTML = `<div class="col-span-full text-center py-12 text-gray-400">😕 No vlogs found for "${q}"</div>`;
        return;
      }
      currentVideos = data.filter(v => v && v.id);
      if (currentVideos.length === 0) {
        grid.innerHTML = `<div class="col-span-full text-center py-12 text-gray-400">😕 No vlogs found</div>`;
        return;
      }
      grid.innerHTML = currentVideos.map(v => `
        <div class="glass-card overflow-hidden cursor-pointer" onclick="watchVideo('${v.id}')">
          <div class="dish-card-img-wrap">
            <img src="${v.thumbnail || 'https://i.ytimg.com/vi/' + v.id + '/hqdefault.jpg'}" alt="${v.title || ''}" loading="lazy" class="w-full h-44 object-cover" />
            <div class="overlay">▶ Watch</div>
          </div>
          <div class="p-3">
            <h3 class="font-semibold text-sm line-clamp-2">${v.title || 'Untitled'}</h3>
            <p class="text-xs text-gray-400 mt-1">${v.author || ''}</p>
          </div>
        </div>
      `).join('');
    })
    .catch(() => {
      grid.innerHTML = `<div class="col-span-full text-center py-12 text-gray-400">⚠️ Failed to search. Try again.</div>`;
    });
}

function watchVideo(id) {
  const grid = document.getElementById('vlogGrid');
  grid.innerHTML = `
    <div class="col-span-full glass-card overflow-hidden p-2 mb-4">
      <div class="relative" style="padding-top:56.25%;">
        <iframe src="https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0" class="absolute inset-0 w-full h-full" allow="autoplay; encrypted-media" allowfullscreen></iframe>
      </div>
    </div>
    <div class="col-span-full text-center">
      <button onclick="searchVlogs()" class="btn-bouncy btn-outline px-6 py-2 text-sm">← Back to results</button>
    </div>
  `;
}

document.getElementById('vlogSearch')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') searchVlogs();
});

<?php if ($dishQuery): ?>
document.addEventListener('DOMContentLoaded', searchVlogs);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
