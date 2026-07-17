<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$providers = [
  ['url' => 'https://gen.pollinations.ai/v1/chat/completions', 'model' => 'openai', 'label' => 'pollinations'],
  ['url' => 'https://g4f.space/api/auto/chat/completions', 'model' => 'auto', 'label' => 'g4f'],
];

$available = null;
$latency = null;

foreach ($providers as $p) {
  $payload = json_encode([
    'model' => $p['model'],
    'messages' => [['role' => 'user', 'content' => 'Say hi']],
    'max_tokens' => 5,
  ]);

  if (!function_exists('curl_init')) continue;
  $start = microtime(true);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $p['url']);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_USERAGENT, 'SmakAI/1.0');
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
  $result = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);
  curl_close($ch);
  $latency = round((microtime(true) - $start) * 1000);

  if (!$error && $code >= 200 && $code < 300 && $result) {
    $data = json_decode($result, true);
    if ($data && (isset($data['choices'][0]['message']['content']) || isset($data['choices'][0]['text']))) {
      $available = $p['label'];
      break;
    }
  }
}

echo json_encode([
  'available' => $available !== null,
  'provider' => $available ?: null,
  'latency_ms' => $latency,
  'checked_at' => date('H:i:s'),
]);
