<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/menu-loader.php';

$method = $_SERVER['REQUEST_METHOD'];
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!$token) {
  // Generate a token if not provided
  $token = bin2hex(random_bytes(16));
  echo json_encode(['success' => true, 'token' => $token, 'items' => []]);
  exit;
}

$carts = readCartFile();
$cart = $carts[$token] ?? ['items' => [], 'updated_at' => ''];

if ($method === 'GET') {
  echo json_encode(['success' => true, 'token' => $token, 'items' => $cart['items']]);
  exit;
}

if ($method === 'DELETE') {
  unset($carts[$token]);
  writeCartFile($carts);
  echo json_encode(['success' => true, 'token' => $token, 'items' => []]);
  exit;
}

if ($method === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true);
  $items = $input['items'] ?? [];
  if (!is_array($items)) $items = [];
  // Validate items
  $clean = [];
  foreach ($items as $item) {
    if (!empty($item['name']) && isset($item['price']) && isset($item['qty'])) {
      $clean[] = [
        'id' => strval($item['id'] ?? ''),
        'name' => strval($item['name']),
        'price' => floatval($item['price']),
        'qty' => max(1, intval($item['qty'])),
        'image' => strval($item['image'] ?? '')
      ];
    }
  }
  $carts[$token] = ['items' => $clean, 'updated_at' => date('Y-m-d H:i:s')];
  writeCartFile($carts);
  echo json_encode(['success' => true, 'token' => $token, 'items' => $clean]);
  exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
