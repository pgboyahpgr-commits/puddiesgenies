<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$urlToken = $_GET['t'] ?? '';
if ($urlToken) {
  $tablesFile = __DIR__ . '/../data/tables.json';
  if (file_exists($tablesFile)) {
    $tables = @json_decode(@file_get_contents($tablesFile), true);
    foreach ($tables['tables'] ?? [] as $t) {
      if (($t['token'] ?? '') === $urlToken) {
        $_SESSION['table'] = $t['number'];
        $_SESSION['table_token'] = $urlToken;
        break;
      }
    }
  }
}

$tableNum = $_SESSION['table'] ?? 0;
$tableToken = $_SESSION['table_token'] ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>SmakAI — Smart Restaurant</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="/assets/style.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('smak_dark',document.body.classList.contains('dark')?'1':'0')}if(localStorage.getItem('smak_dark')==='1')document.body.classList.add('dark')</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="/assets/translate.js"></script>
<style>
  body { font-family: 'Nunito', sans-serif; }
  h1,h2,h3,h4,h5,h6 { font-family: 'Fredoka', sans-serif; }
</style>
</head>
<body class="min-h-screen" style="background:#FDFDFC;">
<div class="max-w-7xl mx-auto px-4">
<nav class="flex items-center justify-between py-4 flex-wrap gap-3">
  <a href="/" class="flex items-center gap-2 no-underline">
    <span class="text-2xl font-bold" style="font-family:'Fredoka',sans-serif;color:var(--text);">Smak<span class="gradient-text" data-translate>AI</span></span>
  </a>
  <div class="flex items-center gap-2 sm:gap-3">
    <?php if ($tableNum > 0): ?>
    <span class="text-xs sm:text-sm px-2 sm:px-3 py-1 rounded-full" style="background:var(--pink-light);color:var(--primary);font-weight:600;border:1px solid var(--pink-light);" data-translate>
      Table <?= $tableNum ?>
    </span>
    <?php endif; ?>
    <a href="/menu.php" class="nav-link" data-translate>📋 Menu</a>
    <a href="/akinator.php" class="nav-link hidden sm:inline-block" data-translate>🤖 AI</a>
    <a href="/location.php" class="nav-link hidden sm:inline-block" data-translate>📍 Map</a>
    <a href="/food-vlogs.php" class="nav-link hidden sm:inline-block" data-translate>🎬 Vlogs</a>
    <a href="/my-orders.php" class="nav-link hidden sm:inline-block" data-translate>📋 Orders</a>
    <a href="/checkout.php" class="relative nav-link" data-translate>🛒 Cart</a>
    <button id="darkToggle" class="dark-mode-toggle" onclick="toggleDark()" title="Toggle dark mode"><span class="light-icon">🌙</span><span class="dark-icon">☀️</span></button>
    <select id="langSelect" class="lang-select">
      <option value="en" data-translate>🌐 English</option>
      <option value="hi" data-translate>🇮🇳 Hindi</option>
      <option value="ta" data-translate>🇮🇳 Tamil</option>
      <option value="te" data-translate>🇮🇳 Telugu</option>
      <option value="bn" data-translate>🇧🇩 Bengali</option>
      <option value="kn" data-translate>🇮🇳 Kannada</option>
      <option value="ml" data-translate>🇮🇳 Malayalam</option>
      <option value="gu" data-translate>🇮🇳 Gujarati</option>
      <option value="mr" data-translate>🇮🇳 Marathi</option>
      <option value="pa" data-translate>🇮🇳 Punjabi</option>
      <option value="ur" data-translate>🇵🇰 Urdu</option>
      <option value="fr" data-translate>🇫🇷 French</option>
      <option value="de" data-translate>🇩🇪 German</option>
      <option value="es" data-translate>🇪🇸 Spanish</option>
      <option value="zh-CN" data-translate>🇨🇳 Chinese</option>
      <option value="ar" data-translate>🇸🇦 Arabic</option>
    </select>
  </div>
</nav>
</div>

<div id="eventsBanner" class="max-w-7xl mx-auto px-4 mb-2" style="display:none;"></div>

<script>
(function() {
  fetch('/api/events.php', { cache: 'no-store' })
    .then(function(r) { return r.json(); })
    .then(function(events) {
      if (!events || !events.length) return;
      var banner = document.getElementById('eventsBanner');
      if (!banner) return;

      events.forEach(function(e) {
        var bg = e.bg_color || '#4138c2';
        var el = document.createElement('div');
        el.className = 'rounded-2xl px-4 py-3 mb-2 flex items-center justify-between gap-3 text-sm';
        el.style.background = bg;

        var html = '<div class="flex-1 min-w-0">';
        if (e.discount_percent > 0) {
          html += '<span class="font-bold text-lg mr-2" style="color:#f68e9a;" data-translate>-' + e.discount_percent + '%</span>';
        }
        html += '<span class="font-bold" style="color:var(--text);" data-translate>' + escapeHtml(e.title) + '</span>';
        if (e.description) html += '<p class="text-xs opacity-70 mt-0.5" data-translate>' + escapeHtml(e.description) + '</p>';
        html += '</div>';

        if (e.type === 'popup') {
          if (!sessionStorage.getItem('evt_popup_' + e.id)) {
            html += '<button onclick="this.parentElement.remove();sessionStorage.setItem(\'evt_popup_' + e.id + '\',\'1\')" class="flex-shrink-0 text-lg" style="border:none;background:none;cursor:pointer;" data-translate>✕</button>';
            setTimeout(function() {
              var popup = document.createElement('div');
              popup.className = 'fixed inset-0 z-[99999] flex items-center justify-center p-4';
              popup.style.background = 'rgba(0,0,0,0.5)';
              popup.style.backdropFilter = 'blur(4px)';
              popup.innerHTML = '<div class="rounded-3xl p-6 max-w-md w-full text-center shadow-2xl" style="background:' + bg + ';animation:popupIn 0.3s cubic-bezier(0.34,1.56,0.64,1);">'
                + '<div class="text-5xl mb-3" data-translate>🎉</div>'
                + '<h3 class="text-2xl font-bold mb-2" style="color:var(--text);" data-translate>' + escapeHtml(e.title) + '</h3>'
                + (e.description ? '<p class="text-sm mb-4 opacity-80" data-translate>' + escapeHtml(e.description) + '</p>' : '')
                + (e.discount_percent > 0 ? '<div class="text-4xl font-bold mb-4" style="color:#f68e9a;" data-translate>-' + e.discount_percent + '%</div>' : '')
                + '<button onclick="this.closest(\'.fixed\').remove();sessionStorage.setItem(\'evt_popup_' + e.id + '\',\'1\')" class="px-8 py-3 rounded-full font-bold text-white" style="background:#f68e9a;border:none;cursor:pointer;" data-translate>Got it!</button>'
                + '</div>';
              banner.parentNode.appendChild(popup);
            }, 1000);
          }
          return;
        }

        el.innerHTML = html;
        banner.appendChild(el);
        banner.style.display = 'block';
        el.querySelector('button')?.addEventListener('click', function() { el.remove(); if (!banner.children.length) banner.style.display = 'none'; });
      });
    })
    .catch(function() {});

  function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
})();
</script>
<style>
@keyframes popupIn { from { transform: scale(0.85); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>

<div id="nameModal" class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="display:none;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);">
  <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl text-center" style="animation:modalIn 0.3s cubic-bezier(0.34,1.56,0.64,1);">
    <div class="text-5xl mb-3" data-translate>👋</div>
    <h2 class="text-2xl font-bold mb-1" style="color:var(--text);" data-translate>What's your name?</h2>
    <p class="text-sm mb-4" style="color:var(--text-muted);" data-translate>So we know who's ordering</p>
    <input type="text" id="nameInput" placeholder="Enter your name..." maxlength="30" class="w-full px-4 py-3 rounded-2xl border-2 border-gray-200 outline-none focus:border-[#f68e9a] text-center text-lg font-semibold mb-4" style="transition:border-color 0.2s;" />
    <button id="nameSave" class="w-full px-6 py-3 rounded-full font-bold text-white text-lg" style="background:#f68e9a;border:none;cursor:pointer;" data-translate>Save</button>
    <button id="nameSkip" class="mt-2 text-xs underline bg-transparent border-none cursor-pointer" style="color:var(--text-muted);" data-translate>Skip — I'll set it later</button>
  </div>
</div>

<script>
(function() {
  var did = localStorage.getItem('smak_device_id');
  if (!did) {
    did = 'dev_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2,9);
    localStorage.setItem('smak_device_id', did);
  }
  window.DEVICE_ID = did;

  var uname = localStorage.getItem('smak_username') || '';
  var unameTime = parseInt(localStorage.getItem('smak_username_time') || '0');
  var expired = uname && unameTime && (Date.now() - unameTime > 12 * 60 * 60 * 1000);
  if (expired) {
    localStorage.removeItem('smak_username');
    localStorage.removeItem('smak_username_time');
    uname = '';
  }
  if (uname) { window.USER_NAME = uname; }
  else {
    var nm = document.getElementById('nameModal');
    var ni = document.getElementById('nameInput');
    var ns = document.getElementById('nameSave');
    var nk = document.getElementById('nameSkip');
    if (nm && ni && ns) {
      setTimeout(function() { nm.style.display = 'flex'; }, 1200);
      ns.addEventListener('click', function() {
        var v = ni.value.trim();
        if (v) {
          localStorage.setItem('smak_username', v);
          localStorage.setItem('smak_username_time', Date.now());
          window.USER_NAME = v;
          nm.style.display = 'none';
        }
      });
      ni.addEventListener('keydown', function(e) { if (e.key === 'Enter') ns.click(); });
      nk?.addEventListener('click', function() {
        localStorage.setItem('smak_username', '');
        window.USER_NAME = '';
        nm.style.display = 'none';
      });
    }
  }
})();
</script>
