<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireAdmin();
$configFile = __DIR__ . '/../data/stream_config.json';
$raw = @file_get_contents($configFile);
$config = $raw ? @json_decode($raw, true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireCSRF();
  $action = $_POST['action'] ?? '';
  if ($action === 'toggle') {
    $config['video_status'] = ($config['video_status'] ?? '') === 'on' ? 'off' : 'on';
    $config['last_updated'] = date('H:i:s');
  } elseif ($action === 'update_url') {
    $config['video_url'] = trim($_POST['video_url'] ?? '');
    $config['last_updated'] = date('H:i:s');
  }
  $config['video_status'] = $config['video_status'] ?? 'off';
  $config['video_url'] = $config['video_url'] ?? '';
  $written = file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
  $_SESSION['admin_message'] = $written ? 'Stream settings saved.' : 'Error saving stream settings.';
  header('Location: /admins/stream.php');
  exit;
}

$stream = getStreamConfig();
$videoUrl = $stream['video_url'];
$videoOn = $stream['video_on'];
$embedSrc = $stream['embed_url'];
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Stream — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>
body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}
</style>
</head>
<body>
<nav class="max-w-5xl mx-auto px-4 py-4 flex items-center gap-3 flex-wrap">
  <a href="/admins/dashboard.php" class="text-xl font-bold no-underline" style="color:var(--text);" data-translate>← SmakAI Admin</a>
  <span class="text-gray-400" data-translate>/ Stream Control</span>
</nav>
<main class="max-w-5xl mx-auto px-4 pb-12">
  <h1 class="text-3xl font-bold mb-6" style="color:var(--text);" data-translate>📺 Live Stream Setup</h1>

  <?php if (isset($_SESSION['admin_message'])): ?>
  <div class="mb-4 px-4 py-3 rounded-xl text-sm font-bold <?= strpos($_SESSION['admin_message'], 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
    <?= htmlspecialchars($_SESSION['admin_message']) ?>
  </div>
  <?php unset($_SESSION['admin_message']); endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100">
      <h3 class="font-bold mb-3" data-translate>Status</h3>
      <div class="flex items-center gap-3 mb-4">
        <span class="w-4 h-4 rounded-full <?=$videoOn?'bg-green-500 animate-pulse':'bg-red-500'?>"></span>
        <span class="font-bold text-lg"><?=$videoOn?'LIVE':'OFFLINE'?></span>
      </div>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
        <input type="hidden" name="action" value="toggle" />
        <button type="submit" class="px-6 py-3 rounded-full font-bold text-white <?=$videoOn?'bg-red-500':'bg-green-500'?>">
          <?=$videoOn?'🔴 Turn Off':'🟢 Turn On'?>
        </button>
      </form>
    </div>

    <div class="bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100">
      <h3 class="font-bold mb-3" data-translate>Stream URL</h3>
      <form method="POST" class="flex flex-col gap-3">
        <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
        <input type="hidden" name="action" value="update_url" />
        <input type="text" name="video_url" id="videoUrl" value="<?=htmlspecialchars($videoUrl)?>" placeholder="Paste YouTube URL or any embed URL..." class="w-full px-4 py-3 rounded-full border-2 border-gray-200 outline-none focus:border-[#538bdf] text-sm" />
        <button type="submit" class="px-6 py-2 rounded-full font-bold text-white" style="background:#538bdf;" data-translate>Save URL</button>
      </form>
      <p class="text-xs text-gray-400 mt-2" data-translate>Supports YouTube, Vimeo, or any direct embed URL.</p>
      <p class="text-xs text-gray-400">Last updated: <?=htmlspecialchars($config['last_updated'] ?? '—')?></p>
    </div>
  </div>

  <div class="bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100">
    <h3 class="font-bold mb-3" data-translate>Preview</h3>
    <div class="relative" style="padding-top:56.25%;background:#000;border-radius:16px;overflow:hidden;">
      <?php if ($videoOn && $embedSrc): ?>
      <iframe src="<?=htmlspecialchars($embedSrc)?>" class="absolute inset-0 w-full h-full" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen style="border:0;"></iframe>
      <?php else: ?>
      <div class="absolute inset-0 flex items-center justify-center text-gray-500">
        <div class="text-center">
          <p class="text-4xl mb-2" data-translate>📴</p>
          <p data-translate>Stream is offline</p>
          <p class="text-xs text-gray-600 mt-2" data-translate>Set a URL and turn the stream ON to preview</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4 mt-4">
    <p class="text-sm text-yellow-800 font-bold mb-1" data-translate>📌 How to set up</p>
    <ol class="text-sm text-yellow-700 list-decimal list-inside space-y-1 mt-2">
      <li>Go to <a href="https://www.youtube.com" target="_blank" class="underline" data-translate>YouTube</a> and start a live stream or pick a video</li>
      <li data-translate>Copy the URL from your browser address bar</li>
      <li>Paste it in the <strong data-translate>Stream URL</strong> field above and click <strong data-translate>Save URL</strong></li>
      <li>Click <strong data-translate>Turn On</strong> — customers will see the stream on the <a href="/kitchen.php" target="_blank" class="underline" data-translate>Live Kitchen page</a></li>
    </ol>
  </div>
</main>
</body>
</html>
