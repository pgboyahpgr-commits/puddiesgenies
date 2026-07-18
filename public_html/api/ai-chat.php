<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'POST only']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$messages = $input['messages'] ?? [];
if (empty($messages)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'No messages']); exit;
}

// Try pollinations AI first (free, no key needed)
$payload = json_encode([
  'model' => 'openai',
  'messages' => $messages,
  'max_tokens' => 512,
  'temperature' => 0.7,
]);

$ch = curl_init('https://gen.pollinations.ai/v1/chat/completions');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $payload,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_CONNECTTIMEOUT => 8,
  CURLOPT_TIMEOUT => 20,
  CURLOPT_SSL_VERIFYPEER => false,
]);

$result = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($code === 200 && $result) {
  $data = json_decode($result, true);
  $content = $data['choices'][0]['message']['content'] ?? '';
  if ($content) {
    echo json_encode(['success' => true, 'content' => $content, 'provider' => 'pollinations']);
    exit;
  }
}

// Fallback: try g4f.space
$fallbackUrls = [
  'https://g4f.space/v1/chat/completions',
  'https://nexra.aryahcr.cc/api/chat/gpt',
];

foreach ($fallbackUrls as $url) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
  ]);
  $result = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($code === 200 && $result) {
    $data = json_decode($result, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    if ($content) {
      echo json_encode(['success' => true, 'content' => $content, 'provider' => 'fallback']);
      exit;
    }
  }
}

echo json_encode(['success' => false, 'error' => 'All AI providers failed', 'detail' => $error ?: "HTTP $code"]);
