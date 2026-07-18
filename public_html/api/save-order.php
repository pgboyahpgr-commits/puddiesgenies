<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'POST only']); exit;
}
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Invalid order data']); exit;
}
foreach ($input['items'] as $item) {
  if (empty($item['name']) || !isset($item['price']) || !isset($item['qty'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid item in order']); exit;
  }
}
$table = intval($input['table'] ?? $_SESSION['table'] ?? 0);
$total = floatval($input['total'] ?? 0);
if ($total <= 0) {
  $total = array_reduce($input['items'], fn($s, $i) => $s + floatval($i['price']) * intval($i['qty'] ?? 1), 0);
}
$payment = in_array($input['payment'] ?? '', ['cash', 'online']) ? $input['payment'] : 'cash';
$ordersFile = __DIR__ . '/../data/orders.json';
$orders = [];
if (file_exists($ordersFile)) {
  $content = @file_get_contents($ordersFile);
  if ($content !== false) { $decoded = json_decode($content, true); if (is_array($decoded)) $orders = $decoded; }
}
$orderId = 'ORD' . date('Ymd') . str_pad(count($orders) + 1, 4, '0', STR_PAD_LEFT);
$orders[] = [
  'order_id' => $orderId,
  'table' => $table,
  'customer_name' => trim($input['customer_name'] ?? ''),
  'items' => $input['items'],
  'total' => $total,
  'instructions' => trim($input['instructions'] ?? ''),
  'payment' => $payment,
  'status' => 'pending',
  'time' => date('Y-m-d H:i:s')
];
file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
echo json_encode(['success' => true, 'order_id' => $orderId]);
