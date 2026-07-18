<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/auth.php';

$profDir = __DIR__ . '/../data/profiles';
$files = glob($profDir . '/*.json');
$users = [];
foreach ($files as $f) {
  $d = json_decode(file_get_contents($f), true);
  if ($d && $d['name']) $users[] = ['name' => $d['name'], 'points' => intval($d['points']), 'orders' => intval($d['orders']), 'badges' => $d['badges'] ?? []];
}
usort($users, function($a, $b) { return $b['points'] - $a['points']; });
echo json_encode(['success' => true, 'leaderboard' => array_slice($users, 0, 50)]);
