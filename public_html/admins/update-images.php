<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/menu-loader.php';
requireAdmin();

$menu = loadMenuFromGist();
$cacheFile = __DIR__ . '/../data/image_cache.json';
$cache = loadJSON($cacheFile);

$batchSize = 10;
$offset = intval($_GET['offset'] ?? 0);
$processed = 0;
$updated = 0;

$allItems = getAllDishes($menu ?: []);
$names = array_column($allItems, 'name');
$batch = array_slice($names, $offset, $batchSize);

foreach ($batch as $name) {
  if (isset($cache[$name]) && $cache[$name] !== null) {
    $processed++;
    continue;
  }
  $search = urlencode($name . ' food');
  $apiUrl = "https://commons.wikimedia.org/w/api.php?action=query&list=search&srsearch={$search}&srnamespace=6&srlimit=1&format=json&origin=*";
  $resp = curlFetch($apiUrl);
  if ($resp) {
    $data = json_decode($resp, true);
    $results = $data['query']['search'] ?? [];
    if (!empty($results)) {
      $title = $results[0]['title'];
      $infoUrl = "https://commons.wikimedia.org/w/api.php?action=query&titles=" . urlencode($title) . "&prop=imageinfo&iiprop=url&iiurlwidth=400&format=json&origin=*";
      $infoResp = curlFetch($infoUrl);
      if ($infoResp) {
        $infoData = json_decode($infoResp, true);
        $pages = $infoData['query']['pages'] ?? [];
        foreach ($pages as $page) {
          if (isset($page['imageinfo'][0]['url'])) {
            $cache[$name] = $page['imageinfo'][0]['url'];
            $updated++;
            break;
          }
        }
      }
    }
  }
  $processed++;
  if (!isset($cache[$name])) $cache[$name] = null;
  usleep(200000);
}

saveJSON($cacheFile, $cache);
$remaining = max(0, count($allItems) - ($offset + $batchSize));
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Update Images — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1{font-family:'Fredoka',sans-serif;}</style>
</head>
<body>
<div class="max-w-xl mx-auto px-4 py-12 text-center">
  <div class="bg-white/80 backdrop-blur rounded-3xl p-8 shadow-lg border border-gray-100">
    <h1 class="text-2xl font-bold mb-4" style="color:var(--text);" data-translate>🖼️ Update Images</h1>
    <p class="text-sm text-gray-500 mb-4">Processed <?=$processed?> dishes (<?=$updated?> new images) — <?=$remaining?> remaining</p>
    <?php if ($remaining > 0): ?>
    <a href="?offset=<?=$offset + $batchSize?>" class="px-6 py-3 rounded-full font-bold text-white inline-block" style="background:#538bdf;">Continue (<?=$remaining?> left)</a>
    <?php else: ?>
    <p class="text-green-600 font-bold mb-4" data-translate>✅ All images processed!</p>
    <a href="/admins/dashboard.php" class="px-6 py-3 rounded-full font-bold inline-block" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>← Back to Dashboard</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
