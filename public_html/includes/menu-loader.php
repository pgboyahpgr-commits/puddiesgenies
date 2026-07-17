<?php
require_once __DIR__ . '/config.php';

function curlFetch($url, $token = null) {
  if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SmakAI/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $headers = ['Accept: application/json'];
    if ($token) {
      $headers[] = "Authorization: token $token";
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if ($info['http_code'] >= 200 && $info['http_code'] < 300) {
      return $result;
    }
  }
  if (!$token && function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'SmakAI/1.0']]);
    return @file_get_contents($url, false, $ctx);
  }
  return null;
}

function loadMenu() {
  $localFile = __DIR__ . '/../data/menu.json';
  $cacheFile = __DIR__ . '/../data/menu-cache.json';

  // 1. Primary: local data/menu.json
  if (file_exists($localFile)) {
    $data = json_decode(@file_get_contents($localFile), true);
    if ($data && isset($data['menu'])) return $data;
  }

  // 2. Fallback: cached version from previous Gist fetch
  if (file_exists($cacheFile)) {
    $data = json_decode(@file_get_contents($cacheFile), true);
    if ($data && isset($data['menu'])) return $data;
  }

  // 3. Last resort: try remote Gist
  $raw = curlFetch(GIST_URL);
  if ($raw) {
    $data = json_decode($raw, true);
    if ($data && isset($data['menu'])) {
      @file_put_contents($cacheFile, $raw, LOCK_EX);
      return $data;
    }
  }

  return null;
}

function getCartStoragePath() {
  return __DIR__ . '/../data/cart.json';
}

function readCartFile() {
  $path = getCartStoragePath();
  if (!file_exists($path)) return [];
  $raw = @file_get_contents($path);
  $data = $raw ? json_decode($raw, true) : [];
  return is_array($data) ? $data : [];
}

function writeCartFile($data) {
  $path = getCartStoragePath();
  return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function getAllDishes($menuData) {
  $dishes = [];
  foreach ($menuData['menu']['categories'] ?? [] as $cat) {
    foreach ($cat['items'] ?? [] as $item) {
      $item['category_name'] = $cat['name'];
      $item['category_id'] = $cat['id'];
      $dishes[] = $item;
    }
  }
  return $dishes;
}

function getMenuSummary($menuData, $maxCats = 10, $maxItemsPerCat = 15) {
  $text = '';
  $catCount = 0;
  foreach ($menuData['menu']['categories'] ?? [] as $cat) {
    if ($catCount >= $maxCats) break;
    if ($catCount > 0) $text .= "\n";
    $text .= "=== " . $cat['name'] . " ===\n";
    $itemCount = 0;
    foreach ($cat['items'] ?? [] as $item) {
      if ($itemCount >= $maxItemsPerCat) break;
      if ($itemCount > 0) $text .= "\n";
      $veg = !empty($item['is_vegetarian']) ? 'Veg' : 'Non-Veg';
      $text .= "- {$item['name']} (₹{$item['price']}, {$item['spice_level']}, {$veg}, {$item['region']})";
      $itemCount++;
    }
    $catCount++;
  }
  return $text;
}

// Keep old function name for backward compatibility
function loadMenuFromGist() {
  return loadMenu();
}
function updateGist($menuData) { return false; }
function forceRefreshMenu() { return loadMenu(); }
