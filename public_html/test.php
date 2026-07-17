<?php
// SmakAI Diagnostics
header('Content-Type: text/plain; charset=utf-8');

echo "=== SmakAI Server Check ===\n\n";

echo "1. PHP Version: " . PHP_VERSION . "\n";
echo "2. Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "3. Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
echo "4. This file path: " . __FILE__ . "\n\n";

echo "5. Session status: ";
echo (session_status() === PHP_SESSION_NONE) ? "not started" : "started or disabled";
echo "\n";

echo "6. Session test:\n";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['test_key'] = 'working';
echo "   Wrote test_key = " . ($_SESSION['test_key'] ?? 'FAILED') . "\n";

echo "\n7. File checks:\n";
$files = [
  'includes/header.php',
  'includes/auth.php',
  'includes/read-json.php',
  'includes/config.php',
  'includes/menu-loader.php',
  'data/tables.json',
  'data/orders.json',
  'data/admin.json',
  'api/talk/send.php',
  'api/talk/read.php',
  'api/talk/request-call.php',
  'api/proxy.php',
  'assets/talk.js',
  'assets/script.js',
];
$dir = __DIR__;
foreach ($files as $f) {
  $path = $dir . '/' . $f;
  $exists = file_exists($path) ? 'OK' : 'MISSING';
  $size = file_exists($path) ? filesize($path) : 0;
  echo "   $f: $exists ($size bytes)\n";
}

echo "\n8. Directory permissions:\n";
$dirs = ['', 'data', 'data/messages'];
foreach ($dirs as $d) {
  $path = $dir . '/' . $d;
  if (is_dir($path)) {
    $w = is_writable($path) ? 'writable' : 'NOT writable';
    echo "   $d/: $w\n";
  } else {
    echo "   $d/: MISSING\n";
  }
}

echo "\n9. JSON decode test:\n";
$sample = '{"test": "hello", "number": 42}';
$decoded = json_decode($sample, true);
echo "   Input: $sample\n";
echo "   Output: " . ($decoded ? json_encode($decoded) : 'FAILED') . "\n";

echo "\n10. file_put_contents test:\n";
$testFile = $dir . '/data/_write_test.txt';
$written = file_put_contents($testFile, 'write test');
echo "   Written: " . ($written ? 'OK' : 'FAILED') . "\n";
if ($written) {
  $read = file_get_contents($testFile);
  echo "   Read back: " . ($read === 'write test' ? 'OK' : 'FAILED') . "\n";
  unlink($testFile);
  echo "   Cleanup: OK\n";
}

echo "\n11. Chat test:\n";
$msgDir = $dir . '/data/messages';
if (is_dir($msgDir) && is_writable($msgDir)) {
  $testChat = $msgDir . '/_test_write.json';
  $data = [['id' => 1, 'from' => 'table', 'text' => 'test', 'time' => '00:00']];
  $w = file_put_contents($testChat, json_encode($data));
  echo "   Write chat file: " . ($w ? 'OK' : 'FAILED') . "\n";
  if ($w) {
    $r = json_decode(file_get_contents($testChat), true);
    echo "   Read chat file: " . ($r ? 'OK' : 'FAILED') . "\n";
    unlink($testChat);
  }
} else {
  echo "   Chat dir not writable: CHECK PERMISSIONS\n";
}

echo "\n12. .htaccess check:\n";
$hta = $dir . '/.htaccess';
if (file_exists($hta)) {
  $c = file_get_contents($hta);
  echo "   Root .htaccess: " . (strlen($c) > 0 ? 'contains ' . strlen($c) . ' chars' : 'empty') . "\n";
} else {
  echo "   Root .htaccess: not found\n";
}

$hta2 = $dir . '/data/.htaccess';
if (file_exists($hta2)) {
  echo "   data/.htaccess: exists\n";
} else {
  echo "   data/.htaccess: not found\n";
}

echo "\n=== Done ===";