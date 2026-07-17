<?php
header('Content-Type: application/json');
require_once __DIR__ . '/read-json.php';
$dishName = $_GET['dish'] ?? '';
if (!$dishName) { echo json_encode(['url' => null]); exit; }

$cacheFile = __DIR__ . '/../data/image_cache.json';
$cache = readJSON($cacheFile) ?: [];
if (isset($cache[$dishName])) {
  echo json_encode(['url' => $cache[$dishName], 'cached' => true]);
  exit;
}

function fetchURL($url) {
  if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'user_agent' => 'SmakAI/1.0']]);
    $result = @file_get_contents($url, false, $ctx);
    if ($result) return $result;
  }
  if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SmakAI/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    if ($result) return $result;
  }
  return null;
}

$search = urlencode($dishName . ' food dish');
$apiUrl = "https://commons.wikimedia.org/w/api.php?action=query&list=search&srsearch={$search}&srnamespace=6&srlimit=3&format=json&origin=*";
$resp = fetchURL($apiUrl);
if ($resp) {
  $data = json_decode($resp, true);
  $results = $data['query']['search'] ?? [];
  foreach ($results as $r) {
    $title = $r['title'];
    $infoUrl = "https://commons.wikimedia.org/w/api.php?action=query&titles=" . urlencode($title) . "&prop=imageinfo&iiprop=url&iiurlwidth=400&format=json&origin=*";
    $infoResp = fetchURL($infoUrl);
    if ($infoResp) {
      $infoData = json_decode($infoResp, true);
      $pages = $infoData['query']['pages'] ?? [];
      foreach ($pages as $page) {
        if (isset($page['imageinfo'][0]['url'])) {
          $url = $page['imageinfo'][0]['url'];
          $cache[$dishName] = $url;
          file_put_contents($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE), LOCK_EX);
          echo json_encode(['url' => $url, 'cached' => false]);
          exit;
        }
      }
    }
  }
}
$cache[$dishName] = null;
file_put_contents($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE), LOCK_EX);
echo json_encode(['url' => null]);
