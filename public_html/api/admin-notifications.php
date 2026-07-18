<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');
require_once __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}
$since = $_GET['since'] ?? '';
$ordersFile = __DIR__ . '/../data/orders.json';
$callsFile = __DIR__ . '/../data/call_requests.json';
$newOrders = [];
$pendingCalls = [];
$now = date('Y-m-d H:i:s');

if ($since) {
  $orders = loadJSON($ordersFile);
  foreach (array_reverse($orders) as $o) {
    $orderTime = $o['time'] ?? '';
    if ($orderTime && $orderTime > $since) {
      if (($o['status'] ?? 'pending') === 'pending') {
        $newOrders[] = [
          'id' => $o['order_id'],
          'table' => $o['table'],
          'total' => $o['total'],
          'time' => $orderTime
        ];
      }
    }
    if (count($newOrders) >= 10) break;
  }
}

// Also check WebRTC signaling offers
$sigFiles = glob(__DIR__ . '/../data/signaling/call_*.json');
$sigCalls = [];
foreach (array_reverse($sigFiles) as $f) {
  $d = json_decode(@file_get_contents($f), true);
  if ($d && ($d['status'] ?? '') === 'waiting') {
    $sigCalls[] = ['table' => $d['tableNum'] ?? 0, 'time' => date('H:i:s', $d['ts'] ?? time()), 'status' => 'pending'];
    if (count($sigCalls) >= 5) break;
  }
}
// Legacy call requests
$calls = loadJSON($callsFile);
$changed = false;
$staleCutoff = time() - 300;
foreach ($calls as $i => $c) {
  if (($c['status'] ?? 'pending') === 'pending') {
    $callTime = is_string($c['time'] ?? null) ? strtotime($c['time']) : false;
    if ($callTime === false || $callTime < $staleCutoff) {
      $calls[$i]['status'] = 'expired';
      $changed = true;
      continue;
    }
    $pendingCalls[] = $c;
  }
}
if ($changed) saveJSON($callsFile, $calls);
// Merge legacy and signaling calls
$allPending = array_merge($pendingCalls, $sigCalls);

echo json_encode([
  'success' => true,
  'now' => $now,
  'new_orders' => $newOrders,
  'pending_calls' => $allPending,
  'total_orders' => count($newOrders),
  'total_calls' => count($allPending)
]);