<?php require_once __DIR__ . '/includes/header.php'; ?>
<main class="max-w-4xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-1"></div>
  <div class="blob-bg blob-2"></div>

  <div class="text-center mb-6 pt-4">
    <h1 class="text-5xl font-bold" style="color:var(--text);">🤖 <span style="color:#f68e9a;" data-translate>AI Waiter</span></h1>
    <p class="text-gray-500 mt-2" data-translate>I'll help you find the perfect dish!</p>
  </div>

  <!-- Mode Toggle -->
  <div class="flex justify-center gap-3 mb-6">
    <button id="modeGuess" class="mode-btn px-6 py-3 rounded-full font-bold text-sm transition-all" style="background:#f68e9a;color:#fff;" data-translate>🎯 Guess My Dish</button>
    <button id="modeChat" class="mode-btn px-6 py-3 rounded-full font-bold text-sm transition-all" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>💬 Ask AI</button>
  </div>

  <!-- Guess Mode -->
  <div id="guessMode">
    <div class="glass-card p-6 mb-4" id="gameArea">
      <div id="statusBar" class="flex items-center gap-3 mb-4 text-sm text-gray-500">
        <span class="w-3 h-3 rounded-full bg-[#538bdf] animate-pulse" id="statusDot"></span>
        <span id="statusText" data-translate>Ready to play!</span>
        <span id="dishCount" class="ml-auto text-xs text-gray-400" data-translate>Loading...</span>
      </div>
      <div id="questionText" class="text-2xl font-bold mb-6 min-h-[3rem]" style="font-family:'Fredoka',sans-serif;color:var(--text);" data-translate>
        Think of a dish from our menu...
      </div>
      <div class="flex gap-3 mb-4 flex-wrap" id="quickButtons">
        <button class="quick-btn btn-bouncy btn-primary text-sm px-6 py-3" data-ans="yes" data-translate>✅ Yes</button>
        <button class="quick-btn btn-bouncy btn-outline text-sm px-6 py-3" data-ans="no" data-translate>❌ No</button>
        <button class="quick-btn btn-bouncy btn-outline text-sm px-6 py-3" data-ans="maybe" data-translate>🤔 Maybe</button>
      </div>
      <div class="flex gap-3">
        <input type="text" id="customAnswer" placeholder="Type yes, no, or maybe..." class="flex-1 px-5 py-3 rounded-full border-2 border-gray-200 bg-white/80 outline-none focus:border-[#f68e9a] transition text-sm" />
        <button id="answerBtn" class="btn-bouncy btn-primary px-6" data-translate>Answer</button>
      </div>
    </div>

    <div class="glass-card p-4 mb-4" id="candidatesArea">
      <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-2" data-translate>🌍 Top matches</h3>
      <div id="candidateList" class="flex flex-wrap gap-2"></div>
    </div>

    <div class="glass-card p-6 text-center" id="guessBox" style="display:none;">
      <div id="guessImageBox" class="w-48 h-48 mx-auto mb-4 rounded-2xl overflow-hidden bg-gray-100 flex items-center justify-center text-5xl" data-translate>🍽️</div>
      <div class="text-3xl font-bold mb-2" id="guessText" style="color:var(--text);" data-translate>Biryani!</div>
      <div class="text-sm text-gray-500 mb-1" id="guessConfidence" data-translate>I'm 95% sure</div>
      <div class="text-xs text-gray-400 mb-4" id="guessDesc" data-translate>Description of the dish</div>
      <div class="flex gap-3 justify-center flex-wrap">
        <button id="wrongBtn" class="btn-bouncy px-6 py-2" style="background:#f68e9a;color:#fff;" data-translate>❌ Wrong, try again</button>
        <a id="guessMenuLink" href="/menu.php" class="btn-bouncy btn-outline px-6 py-2 no-underline" data-translate>📋 Find on Menu</a>
      </div>
    </div>
    <button id="resetBtn" class="btn-bouncy btn-outline w-full py-3 mt-2 text-sm" data-translate>🔄 New Game</button>
  </div>

  <!-- AI Chat Mode -->
  <div id="chatMode" style="display:none;">
    <div class="glass-card p-6 mb-4">
      <div id="aiChatMessages" class="mb-4 max-h-80 overflow-y-auto space-y-3">
        <div class="flex gap-3 items-start">
          <div class="w-8 h-8 rounded-full bg-[#f68e9a] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">AI</div>
          <div class="bg-white/80 rounded-2xl rounded-tl-sm px-4 py-3 text-sm max-w-[80%]" style="border:1px solid #eee;" data-translate>
            Hi! Tell me what you're craving — any dish, flavor, or ingredient. I'll recommend something perfect from our menu! 🍛
          </div>
        </div>
      </div>
      <div class="flex gap-3">
        <input type="text" id="aiChatInput" placeholder="e.g. I want something spicy with paneer..." class="flex-1 px-5 py-3 rounded-full border-2 border-gray-200 bg-white/80 outline-none focus:border-[#f68e9a] transition text-sm" />
        <button id="aiChatSend" class="btn-bouncy px-6" style="background:#f68e9a;color:#fff;" data-translate>Ask</button>
      </div>
      <div class="flex items-center justify-between mt-3">
        <p class="text-xs text-gray-400" data-translate>Powered by AI — may make mistakes. Try being specific!</p>
        <p id="aiStatus" class="text-xs text-gray-400" data-translate>Checking AI...</p>
      </div>
    </div>
  </div>
</main>

<script src="/assets/ai-engine.js"></script>
<script src="/assets/akinator.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
