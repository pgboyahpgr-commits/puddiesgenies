<?php
header('Content-Type: application/json');
$ordersFile = __DIR__ . '/../data/orders.json';
$orders = [];
if (file_exists($ordersFile)) {
  $content = @file_get_contents($ordersFile);
  if ($content !== false) { $decoded = json_decode($content, true); if (is_array($decoded)) $orders = $decoded; }
}
$pending = count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'pending'));
echo json_encode(['total' => count($orders), 'pending' => $pending]);
