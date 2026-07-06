<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
$tableNum = $_SESSION['table'] ?? 0;
$chatTable = $tableNum > 0 ? $tableNum : 0;
?>
<main class="max-w-3xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-1"></div>
  <div class="text-center mb-8 pt-4">
    <h1 class="text-4xl font-bold" style="color:#2D3436;">💬 <span style="color:#4ECDC4;">Chat</span> with Restaurant</h1>
    <?php if ($tableNum > 0): ?>
    <p class="text-gray-500 mt-2">Table #<?= $tableNum ?></p>
    <?php else: ?>
    <p class="text-gray-500 mt-2">Guest</p>
    <?php endif; ?>
  </div>

  <div class="glass-card p-4 mb-4" style="height:400px;overflow-y:auto;display:flex;flex-direction:column;" id="chatContainer" data-table="<?= $chatTable ?>">
    <div id="chatMessages" class="flex-1 flex flex-col" style="justify-content:flex-end;">
      <div class="text-center text-sm text-gray-400 py-4">Chat started</div>
    </div>
  </div>

  <div class="flex gap-3">
    <input type="text" id="chatInput" placeholder="Type a message..." class="flex-1 px-5 py-3 rounded-full border-2 border-gray-200 bg-white/80 outline-none focus:border-[#4ECDC4] transition text-sm" />
    <button id="sendBtn" class="btn-bouncy btn-secondary px-6">Send</button>
  </div>

  <button id="callBtn" class="btn-bouncy w-full mt-4 py-3" style="background:#6c5ce7;color:#fff;">
    <img src="https://files.svgcdn.io/solar/phone-calling-rounded-bold.svg" width="18" height="18" class="inline mr-2" />Call Restaurant
  </button>

  <div class="flex gap-3 justify-center mt-4">
    <a href="/menu.php" class="btn-bouncy btn-outline px-6 py-3 no-underline text-sm">📋 Menu</a>
    <a href="/kitchen.php" class="btn-bouncy btn-outline px-6 py-3 no-underline text-sm">📺 Kitchen</a>
  </div>
</main>

<script src="/assets/chat.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
