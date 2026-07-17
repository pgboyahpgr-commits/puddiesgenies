<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/config.php';

$openRouterKey = defined('OPENROUTER_KEY') ? OPENROUTER_KEY : '';

if (!str_starts_with($openRouterKey, 'sk-or-')) {
  http_response_code(500);
  echo json_encode(['error' => 'OpenRouter key not configured']);
  exit;
}

// GET /api/proxy.php?models — list available free models
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['models'])) {
  $ch = curl_init('https://openrouter.ai/api/v1/models');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $openRouterKey],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
  ]);
  $result = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($code === 200 && $result) {
    $data = json_decode($result, true);
    $freeModels = array_filter($data['data'] ?? [], fn($m) => str_contains($m['id'] ?? '', ':free') || in_array($m['id'] ?? '', [
      'meta-llama/llama-3.3-70b-instruct:free',
      'google/gemma-4-31b-it:free',
      'nousresearch/hermes-3-llama-3.1-405b:free',
      'openrouter/free'
    ]));
    echo json_encode(['success' => true, 'models' => array_values($freeModels)]);
  } else {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch models']);
  }
  exit;
}

// GET /api/proxy.php — health check
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $start = microtime(true);
  $ch = curl_init('https://openrouter.ai/api/v1/models');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $openRouterKey],
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
  ]);
  curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $latency = round((microtime(true) - $start) * 1000);
  curl_close($ch);
  echo json_encode([
    'available' => $code === 200,
    'provider' => 'openrouter',
    'latency_ms' => $latency,
    'model' => 'openrouter/free'
  ]);
  exit;
}

// POST /api/proxy.php — chat completion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true);
  $messages = $input['messages'] ?? [];
  $model = $input['model'] ?? 'openrouter/free';

  if (empty($messages)) {
    http_response_code(400);
    echo json_encode(['error' => 'No messages provided']);
    exit;
  }

  $payload = json_encode([
    'model' => $model,
    'messages' => $messages,
    'max_tokens' => 512,
    'temperature' => 0.7,
  ]);

  $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $openRouterKey,
      'Content-Type: application/json',
      'Accept: application/json',
    ],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
  ]);

  $result = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);
  curl_close($ch);

  if ($error || $code < 200 || $code >= 300 || !$result) {
    http_response_code(502);
    echo json_encode(['error' => 'OpenRouter error', 'detail' => $error ?: "HTTP $code"]);
    exit;
  }

  $data = json_decode($result, true);
  $content = $data['choices'][0]['message']['content'] ?? '';

  echo json_encode([
    'success' => true,
    'content' => $content,
    'model' => $data['model'] ?? $model,
  ]);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
