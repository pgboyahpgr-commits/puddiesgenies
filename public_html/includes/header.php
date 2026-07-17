<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Table token from URL works on EVERY page: /anything?t=x7k9m2a4
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<style>
  body { font-family: 'Nunito', sans-serif; }
  h1,h2,h3,h4,h5,h6 { font-family: 'Fredoka', sans-serif; }
</style>
</head>
<body class="bg-[#FFF8F0] min-h-screen">
<div class="max-w-7xl mx-auto px-4">
<nav class="flex items-center justify-between py-4 flex-wrap gap-3">
  <a href="/menu.php" class="flex items-center gap-2 no-underline">
    <img src="https://files.svgcdn.io/solar/plate-wheat-line.svg" width="32" height="32" alt="" />
    <span class="text-2xl font-bold" style="font-family:'Fredoka',sans-serif;color:#2D3436;">Smak<span style="color:#FF6B6B;">AI</span></span>
  </a>
  <div class="flex items-center gap-4">
    <?php if ($tableNum > 0): ?>
    <span class="text-sm px-3 py-1 rounded-full" style="background:#4ECDC4;color:#fff;font-weight:600;">
      Table <?= $tableNum ?>
    </span>
    <?php endif; ?>
    <a href="/menu.php" class="text-sm font-semibold no-underline px-4 py-2 rounded-full transition" style="color:#2D3436;background:rgba(255,255,255,0.6);border:1px solid #ddd;">Menu</a>
    <a href="/akinator.php" class="text-sm font-semibold no-underline px-4 py-2 rounded-full transition" style="color:#2D3436;background:rgba(255,255,255,0.6);border:1px solid #ddd;">AI Waiter</a>
    <a href="/checkout.php" class="relative text-sm font-semibold no-underline px-4 py-2 rounded-full transition" style="color:#2D3436;background:rgba(255,255,255,0.6);border:1px solid #ddd;">
      <img src="https://files.svgcdn.io/solar/cart-large-3-bold.svg" width="18" height="18" class="inline" alt="Cart" />
      <span id="cartBadge" class="absolute -top-1 -right-1 bg-[#FF6B6B] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold" style="display:none;">0</span>
    </a>
  </div>
</nav>
</div>

<!-- Events Banner -->
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
        var bg = e.bg_color || '#FFE66D';
        var el = document.createElement('div');
        el.className = 'rounded-2xl px-4 py-3 mb-2 flex items-center justify-between gap-3 text-sm';
        el.style.background = bg;

        var html = '<div class="flex-1 min-w-0">';
        if (e.discount_percent > 0) {
          html += '<span class="font-bold text-lg mr-2" style="color:#FF6B6B;">-' + e.discount_percent + '%</span>';
        }
        html += '<span class="font-bold" style="color:#2D3436;">' + escapeHtml(e.title) + '</span>';
        if (e.description) html += '<p class="text-xs opacity-70 mt-0.5">' + escapeHtml(e.description) + '</p>';
        html += '</div>';

        if (e.type === 'popup') {
          // Show as popup once per session
          if (!sessionStorage.getItem('evt_popup_' + e.id)) {
            html += '<button onclick="this.parentElement.remove();sessionStorage.setItem(\'evt_popup_' + e.id + '\',\'1\')" class="flex-shrink-0 text-lg" style="border:none;background:none;cursor:pointer;">✕</button>';
            setTimeout(function() {
              var popup = document.createElement('div');
              popup.className = 'fixed inset-0 z-[99999] flex items-center justify-center p-4';
              popup.style.background = 'rgba(0,0,0,0.5)';
              popup.style.backdropFilter = 'blur(4px)';
              popup.innerHTML = '<div class="rounded-3xl p-6 max-w-md w-full text-center shadow-2xl" style="background:' + bg + ';animation:popupIn 0.3s cubic-bezier(0.34,1.56,0.64,1);">'
                + '<div class="text-5xl mb-3">🎉</div>'
                + '<h3 class="text-2xl font-bold mb-2" style="color:#2D3436;">' + escapeHtml(e.title) + '</h3>'
                + (e.description ? '<p class="text-sm mb-4 opacity-80">' + escapeHtml(e.description) + '</p>' : '')
                + (e.discount_percent > 0 ? '<div class="text-4xl font-bold mb-4" style="color:#FF6B6B;">-' + e.discount_percent + '%</div>' : '')
                + '<button onclick="this.closest(\'.fixed\').remove();sessionStorage.setItem(\'evt_popup_' + e.id + '\',\'1\')" class="px-8 py-3 rounded-full font-bold text-white" style="background:#FF6B6B;border:none;cursor:pointer;">Got it!</button>'
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
