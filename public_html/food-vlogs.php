<?php require_once __DIR__ . '/includes/header.php'; ?>
<main class="max-w-6xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-3"></div>
  <div class="text-center mb-8 pt-4">
    <h1 class="text-4xl font-bold" style="color:var(--text);">🎬 <span style="color:#f7b6bf;" data-translate>Food Vlogs</span></h1>
    <p class="text-gray-500 mt-2" data-translate>Discover dishes through food vlogs</p>
  </div>

  <div class="flex gap-3 mb-3 max-w-xl mx-auto">
    <input type="text" id="vlogSearch" placeholder="Search food vlogs..." class="flex-1 px-5 py-3 rounded-full border-2 border-gray-200 bg-white/80 outline-none focus:border-[#f7b6bf] transition text-sm" />
    <button onclick="searchVlogs()" class="btn-bouncy px-6" style="background:#f7b6bf;color:var(--text);font-weight:700;" data-translate>🔍</button>
  </div>
  <div class="flex gap-2 flex-wrap justify-center mb-6 max-w-xl mx-auto" id="searchSuggestions"></div>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5" id="vlogGrid">
    <div class="col-span-full text-center py-12 text-gray-400" id="vlogStatus" data-translate>
      🔍 Search for any dish to see food vlogs
    </div>
  </div>
</main>

<script>
var currentVideos = [];
var popularSearches = ['Butter Chicken','Biryani','Pizza','Pasta','Sushi','Dosa','Burger','Tacos','Noodles','Curry','BBQ','Ramen','Dim Sum','Pad Thai','Croissant','Donuts','Ice Cream','Cheesecake'];
var trendingQueries = ['Tandoori Chicken','Biryani','Pasta','Pizza','Sushi','BBQ Ribs','Thai Curry','Greek Salad','Fish Tacos','Ramen Noodles','Dim Sum','Croissant','Macarons','Cheesecake','Ice Cream','Burger','Tacos','Pad Thai','Donuts','Croissant'];

// Auto-load trending videos on page load
var trendingIndex = 0;
function loadTrending() {
  if (trendingIndex >= trendingQueries.length) { trendingIndex = 0; }
  var q = trendingQueries[trendingIndex];
  trendingIndex++;
  tryVideosApi(q, 0);
}

setTimeout(loadTrending, 500);

(function initSuggestions() {
  var container = document.getElementById('searchSuggestions');
  if (!container) return;
  popularSearches.forEach(function(term) {
    var btn = document.createElement('button');
    btn.textContent = '🔥 ' + term;
    btn.className = 'px-3 py-1.5 rounded-full text-xs font-semibold border-none cursor-pointer transition';
    btn.style.cssText = 'background:#fdf6f0;color:#8a7a6a;font-family:inherit';
    btn.onmouseover = function() { this.style.background = '#f7b6bf'; this.style.color = '#fff'; };
    btn.onmouseout = function() { this.style.background = '#fdf6f0'; this.style.color = '#8a7a6a'; };
    btn.onclick = function() { document.getElementById('vlogSearch').value = term; searchVlogs(); };
    container.appendChild(btn);
  });
})();

function searchVlogs() {
  var q = document.getElementById('vlogSearch').value.trim();
  if (!q) return;
  var grid = document.getElementById('vlogGrid');
  grid.innerHTML = '<div class="col-span-full text-center py-12"><div class="w-10 h-10 border-4 border-gray-200 border-t-[#f7b6bf] rounded-full animate-spin mx-auto mb-3"></div><p class="text-gray-400" data-translate>Searching for "' + q + '"...</p></div>';
  tryVideosApi(q, 0);
}

function tryVideosApi(q, attempt) {
  var apis = [
    'https://ytapis.djalokyt27.workers.dev/search?q=' + encodeURIComponent(q + ' food vlog'),
    'https://invidious.private.coffee/api/v1/search?q=' + encodeURIComponent(q + ' food') + '&type=video&sort=relevance',
    'https://invidious.snopyta.org/api/v1/search?q=' + encodeURIComponent(q + ' food') + '&type=video&sort=relevance'
  ];
  if (attempt >= apis.length) { showFallbackSearch(q); return; }
  fetch(apis[attempt])
    .then(function(r) { if (!r.ok) throw new Error('fail'); return r.json(); })
    .then(function(data) { renderResults(data, q); })
    .catch(function() { tryVideosApi(q, attempt + 1); });
}

function renderResults(data, q) {
  var grid = document.getElementById('vlogGrid');
  var videos = [];
  if (Array.isArray(data)) {
    videos = data.filter(function(v) { return v && v.id; });
  } else if (data && Array.isArray(data.videos)) {
    videos = data.videos.filter(function(v) { return v && v.videoId; }).map(function(v) {
      return { id: v.videoId, title: v.title, author: v.author, thumbnail: v.videoThumbnails && v.videoThumbnails[0] ? v.videoThumbnails[0].url : '' };
    });
  }
  if (videos.length === 0) { showFallbackSearch(q); return; }
  currentVideos = videos;
  grid.innerHTML = videos.map(function(v) {
    var thumb = v.thumbnail || 'https://i.ytimg.com/vi/' + v.id + '/hqdefault.jpg';
    return '<div class="glass-card overflow-hidden cursor-pointer" onclick="watchVideo(\'' + v.id + '\')">' +
      '<div class="dish-card-img-wrap"><img src="' + thumb + '" alt="' + (v.title || '') + '" loading="lazy" class="w-full h-44 object-cover" onerror="this.parentNode.innerHTML=\'<div class=\\\'w-full h-44 flex items-center justify-center bg-gray-100 text-gray-400\\\' data-translate>🎬</div>\'" /><div class="overlay" data-translate>▶ Watch</div></div>' +
      '<div class="p-3"><h3 class="font-semibold text-sm line-clamp-2" data-translate>' + (v.title || 'Untitled') + '</h3>' +
      '<p class="text-xs text-gray-400 mt-1" data-translate>' + (v.author || '') + '</p></div></div>';
  }).join('');
  if (window.retranslate) window.retranslate();
}

function showFallbackSearch(q) {
  // On page load without search, try next trending query
  if (!q) { loadTrending(); return; }
  document.getElementById('vlogGrid').innerHTML =
    '<div class="col-span-full text-center py-8"><p class="text-gray-400 mb-4" data-translate>😕 Could not load video results</p>' +
    '<a href="https://www.youtube.com/results?search_query=' + encodeURIComponent(q + ' food recipe') + '" target="_blank" class="btn-bouncy px-6 py-3 inline-block no-underline" style="background:#f68e9a;color:#fff;" data-translate>🔍 Search on YouTube</a></div>';
  if (window.retranslate) window.retranslate();
}

function watchVideo(id) {
  var grid = document.getElementById('vlogGrid');
  grid.innerHTML = '<div class="col-span-full glass-card overflow-hidden p-2 mb-4">' +
    '<div class="relative" style="padding-top:56.25%;">' +
    '<iframe src="https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0" class="absolute inset-0 w-full h-full" allow="autoplay; encrypted-media" allowfullscreen onerror="this.parentNode.innerHTML=\'<div class=\\\'absolute inset-0 flex items-center justify-center text-gray-400\\\'><p data-translate>Video unavailable</p></div>\'"></iframe>' +
    '</div></div>' +
    '<div class="col-span-full text-center"><button onclick="searchVlogs()" class="btn-bouncy btn-outline px-6 py-2 text-sm" data-translate>← Back to results</button></div>';
}

document.getElementById('vlogSearch')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') searchVlogs();
});

// Auto-search if dish param in URL
var dishParam = new URLSearchParams(window.location.search).get('dish');
if (dishParam) {
  document.getElementById('vlogSearch').value = dishParam;
  document.addEventListener('DOMContentLoaded', searchVlogs);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
