<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/auth.php';

$deviceId = $_GET['device_id'] ?? $_POST['device_id'] ?? '';
if (!$deviceId) { echo json_encode(['success' => false, 'error' => 'device_id required']); exit; }

$profDir = __DIR__ . '/../data/profiles';
if (!is_dir($profDir)) @mkdir($profDir, 0755, true);
$profFile = $profDir . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $deviceId) . '.json';
$profile = file_exists($profFile) ? json_decode(file_get_contents($profFile), true) : [];
$defaults = ['device_id' => $deviceId, 'name' => '', 'points' => 0, 'orders' => 0, 'badges' => [], 'created' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')];
$profile = array_merge($defaults, $profile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  if ($name) $profile['name'] = $name;
  $profile['updated'] = date('Y-m-d H:i:s');
  file_put_contents($profFile, json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
  echo json_encode(['success' => true, 'profile' => $profile]);
  exit;
}

echo json_encode(['success' => true, 'profile' => $profile]);
