<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
if (session_status() === PHP_SESSION_NONE) session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'POST only']); exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$table = intval($input['table'] ?? $_SESSION['table'] ?? -1);
$text = trim($input['text'] ?? '');
if ($table < 0 || !$text) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Invalid data']); exit;
}

// Allow 'from' field: 'table' (default) or 'admin'
$from = $input['from'] ?? 'table';
if ($from !== 'admin' && $from !== 'table') $from = 'table';

// If admin, verify they're logged in
if ($from === 'admin') {
  if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin auth required']); exit;
  }
  // Verify CSRF token
  $csrf = $input['csrf_token'] ?? '';
  if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']); exit;
  }
}

$msgDir = __DIR__ . '/../../data/messages';
if (!is_dir($msgDir)) {
  if (!@mkdir($msgDir, 0755, true) && !is_dir($msgDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not create messages directory']); exit;
  }
}
$msgFile = $msgDir . '/table_' . $table . '.json';
$chat = [];
if (file_exists($msgFile)) {
  $raw = @file_get_contents($msgFile);
  if ($raw !== false) $chat = json_decode($raw, true) ?: [];
}
$nextId = 1;
foreach ($chat as $m) {
  if (isset($m['id']) && $m['id'] >= $nextId) $nextId = $m['id'] + 1;
}
$chat[] = [
  'id' => $nextId,
  'from' => $from,
  'text' => $text,
  'time' => date('H:i:s')
];
if (count($chat) > 200) $chat = array_slice($chat, -200);
$written = @file_put_contents($msgFile, json_encode($chat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
echo json_encode(['success' => $written !== false, 'id' => $nextId]);
