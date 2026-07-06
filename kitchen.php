<?php
require_once __DIR__ . '/includes/auth.php';
$tableNum = $_SESSION['table'] ?? 0;
$token = $_SESSION['table_token'] ?? '';
$tables = loadJSON(__DIR__ . '/data/tables.json');
$tableConfig = null;
foreach ($tables['tables'] ?? [] as $t) {
  if ($t['token'] === $token) { $tableConfig = $t; break; }
}
$streamConfig = loadJSON(__DIR__ . '/data/stream_config.json');
$streamEnabled = $tableConfig['stream_enabled'] ?? false;
$videoUrl = $streamConfig['video_url'] ?? '';
$streamOn = $streamConfig['video_status'] === 'on' && $streamEnabled;
require_once __DIR__ . '/includes/header.php';
?>
<main class="max-w-4xl mx-auto px-4 pb-12">
  <div class="text-center mb-8 pt-4">
    <h1 class="text-4xl font-bold" style="color:#2D3436;">📺 <span style="color:#4ECDC4;">Live Kitchen</span></h1>
    <?php if ($tableNum > 0): ?>
    <p class="text-gray-500 mt-2">Table #<?= $tableNum ?></p>
    <?php endif; ?>
  </div>

  <div class="glass-card overflow-hidden mb-4">
    <div class="relative" style="padding-top:56.25%;">
      <?php if ($streamOn && $videoUrl): ?>
        <iframe src="<?= htmlspecialchars($videoUrl) ?>" class="absolute inset-0 w-full h-full" allow="autoplay; encrypted-media" allowfullscreen></iframe>
      <?php else: ?>
        <div class="absolute inset-0 flex flex-col items-center justify-center stream-offline">
          <img src="https://files.svgcdn.io/solar/pause-circle-bold.svg" width="64" height="64" class="opacity-50 mb-3" />
          <p class="text-xl font-bold" style="color:#fff;">
            <?= !$streamEnabled ? 'Stream not available for your table yet' : 'Kitchen stream is offline' ?>
          </p>
          <p class="text-sm text-gray-400 mt-1">
            <?= !$streamEnabled ? 'Available when your order is being prepared' : 'Check back soon!' ?>
          </p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="flex gap-3 justify-center">
    <a href="/chat.php" class="btn-bouncy btn-secondary px-6 py-3 no-underline">💬 Chat</a>
    <a href="/menu.php" class="btn-bouncy btn-outline px-6 py-3 no-underline">📋 Menu</a>
  </div>
</main>

<script>
(function() {
  const iframe = document.querySelector('iframe');
  const statusUrl = '/admins/stream-status.php';
  function pollStream() {
    fetch(statusUrl + '?t=<?= $token ?>')
      .then(r => r.json())
      .then(data => {
        if (data.reload) location.reload();
      })
      .catch(() => {});
  }
  setInterval(pollStream, 3000);
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
