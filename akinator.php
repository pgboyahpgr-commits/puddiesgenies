<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/menu-loader.php';
$menuData = loadMenuFromGist();
$akinatorDishes = [];
foreach ($menuData['menu']['categories'] ?? [] as $cat) {
  foreach ($cat['items'] ?? [] as $item) {
    $akinatorDishes[] = [
      'name' => $item['name'],
      'category_id' => $cat['id'],
      'is_vegetarian' => !empty($item['is_vegetarian']),
      'spice_level' => $item['spice_level'] ?? 'mild'
    ];
    if (count($akinatorDishes) >= 100) break 2;
  }
}
?>
<script>
window.__AKINATOR_DISHES = <?= json_encode($akinatorDishes) ?>;
</script>
<main class="max-w-3xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-1"></div>
  <div class="blob-bg blob-2"></div>

  <div class="text-center mb-8 pt-4">
    <h1 class="text-5xl font-bold" style="color:#2D3436;">🤖 AI <span style="color:#FF6B6B;">Waiter</span></h1>
    <p class="text-gray-500 mt-2">Think of a dish — I'll guess it in 6 questions</p>
  </div>

  <div class="glass-card p-6 mb-6" id="gameArea">
    <div id="statusBar" class="flex items-center gap-3 mb-4 text-sm text-gray-500">
      <span class="w-3 h-3 rounded-full bg-[#4ECDC4] animate-pulse" id="statusDot"></span>
      <span id="statusText">Ready to play!</span>
    </div>

    <div id="questionText" class="text-2xl font-bold mb-6 min-h-[3rem]" style="font-family:'Fredoka',sans-serif;color:#2D3436;">
      Think of a dish from our menu...
    </div>

    <div class="flex gap-3 mb-4 flex-wrap" id="quickButtons">
      <button class="quick-btn btn-bouncy btn-primary text-sm px-6 py-3" data-ans="yes">✅ Yes</button>
      <button class="quick-btn btn-bouncy btn-outline text-sm px-6 py-3" data-ans="no">❌ No</button>
      <button class="quick-btn btn-bouncy btn-outline text-sm px-6 py-3" data-ans="maybe">🤔 Maybe</button>
    </div>

    <div class="flex gap-3">
      <input type="text" id="customAnswer" placeholder="Type your answer..." class="flex-1 px-5 py-3 rounded-full border-2 border-gray-200 bg-white/80 outline-none focus:border-[#FF6B6B] transition text-sm" />
      <button id="answerBtn" class="btn-bouncy btn-primary px-6">Answer</button>
    </div>
    <p class="text-xs text-gray-400 mt-2">Try: "spicy" or "has rice" or "veg"</p>
  </div>

  <div class="glass-card p-4 mb-4" id="candidatesArea">
    <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-2">🌍 Possible dishes</h3>
    <div id="candidateList" class="flex flex-wrap gap-2"></div>
  </div>

  <div class="glass-card p-6 text-center" id="guessBox" style="display:none;">
    <div class="text-3xl mb-2" id="guessText">🍛 Biryani!</div>
    <div class="text-sm text-gray-500 mb-3" id="guessConfidence">I'm 95% sure</div>
    <div id="dishImageContainer" class="mb-3"></div>
    <div class="flex gap-3 justify-center flex-wrap">
      <button id="wrongBtn" class="btn-bouncy px-6 py-2" style="background:#FF6B6B;color:#fff;">❌ No, not it</button>
      <a href="/menu.php" class="btn-bouncy btn-outline px-6 py-2 no-underline">📋 Browse Menu</a>
    </div>
  </div>

  <button id="resetBtn" class="btn-bouncy btn-outline w-full py-3 mt-4 text-sm" onclick="startNewGame()">🔄 New Game</button>
</main>

<script src="/assets/akinator.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
