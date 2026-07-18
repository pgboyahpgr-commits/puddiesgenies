<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
$orderId = $_GET['order_id'] ?? '';
if (!$orderId) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Missing order_id']); exit;
}
$ordersFile = __DIR__ . '/../data/orders.json';
$orders = [];
if (file_exists($ordersFile)) {
  $content = @file_get_contents($ordersFile);
  if ($content !== false) { $decoded = json_decode($content, true); if (is_array($decoded)) $orders = $decoded; }
}
foreach ($orders as $o) {
  if ($o['order_id'] === $orderId) {
    echo json_encode(['success' => true, 'items' => $o['items']]); exit;
  }
}
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Order not found']);
