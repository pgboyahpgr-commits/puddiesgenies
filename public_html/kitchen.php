<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/menu-loader.php';
$tableNum = $_SESSION['table'] ?? 0;
$stream = getStreamConfig();
$isLive = $stream['video_on'];
$embedSrc = $isLive ? $stream['embed_url'] : '';
require_once __DIR__ . '/includes/header.php';
?>
<main class="max-w-5xl mx-auto px-4 pb-12">
  <div class="text-center mb-6 pt-4">
    <h1 class="text-4xl font-bold" style="color:#2D3436;">📺 <span style="color:#4ECDC4;">Live Kitchen</span></h1>
    <?php if ($tableNum > 0): ?>
    <p class="text-gray-500 mt-2">Table #<?= $tableNum ?></p>
    <?php endif; ?>
    <p class="text-xs mt-1 text-gray-400" id="kitchenStatus"><?= $isLive ? '🔴 LIVE' : '⏸️ Offline' ?></p>
  </div>

  <div class="glass-card overflow-hidden mb-4">
    <div class="relative bg-black" style="padding-top:56.25%;min-height:300px;">
      <?php if ($isLive && $embedSrc): ?>
      <iframe id="kitchenIframe" src="<?= htmlspecialchars($embedSrc) ?>" class="absolute inset-0 w-full h-full" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen style="border:0;"></iframe>
      <?php else: ?>
      <iframe id="kitchenIframe" src="about:blank" class="absolute inset-0 w-full h-full" style="border:0;"></iframe>
      <?php endif; ?>

      <div id="streamLoading" class="absolute inset-0 flex flex-col items-center justify-center" style="background:#1a1a2e;z-index:10;">
        <div class="w-12 h-12 border-4 border-gray-600 border-t-[#4ECDC4] rounded-full animate-spin mb-3"></div>
        <p class="text-gray-400 text-sm">Connecting to stream...</p>
      </div>

      <div id="streamError" class="absolute inset-0 flex flex-col items-center justify-center" style="display:none;background:#1a1a2e;z-index:10;">
        <p class="text-5xl mb-3">😕</p>
        <p class="text-gray-200 font-bold text-lg">Stream unavailable</p>
        <p class="text-gray-500 text-sm mt-1 mb-4">The video could not be loaded</p>
        <button onclick="location.reload()" class="px-6 py-2 rounded-full font-bold text-sm text-white" style="background:#4ECDC4;">Try Again</button>
      </div>

      <div id="streamOffline" class="absolute inset-0 flex flex-col items-center justify-center" style="display:none;background:#1a1a2e;z-index:10;">
        <p class="text-5xl mb-3">👨‍🍳</p>
        <p class="text-gray-200 font-bold text-lg">Kitchen is busy preparing orders</p>
        <p class="text-gray-400 text-sm mt-1">Our chefs are cooking up something delicious!</p>
        <p class="text-gray-500 text-xs mt-3">📋 <a href="/menu.php" class="underline text-[#4ECDC4]">Browse the menu</a> while you wait</p>
      </div>

      <div id="tapToPlay" class="absolute inset-0 flex flex-col items-center justify-center cursor-pointer" style="display:none;background:rgba(0,0,0,0.7);z-index:10;">
        <p class="text-5xl mb-3">▶️</p>
        <p class="text-white font-bold text-lg">Tap to play</p>
        <p class="text-gray-400 text-sm mt-1">Click to enable sound</p>
      </div>

      <div id="liveBadge" class="absolute top-3 left-3 flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold" style="display:none;z-index:20;background:rgba(220,38,38,0.9);color:#fff;">
        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span> LIVE
      </div>
    </div>
  </div>

  <div class="flex gap-3 justify-center flex-wrap">
    <a href="/talk.php" class="btn-bouncy btn-secondary px-6 py-3 no-underline text-sm">💬 Chat with Restaurant</a>
    <a href="/menu.php" class="btn-bouncy btn-outline px-6 py-3 no-underline text-sm">📋 Browse Menu</a>
    <a href="/checkout.php" class="btn-bouncy px-6 py-3 no-underline text-sm" style="background:#FF6B6B;color:#fff;">🛒 View Cart</a>
  </div>
</main>

<script src="/assets/kitchen.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
