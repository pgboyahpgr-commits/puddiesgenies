<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false]); exit;
}
if (session_status() === PHP_SESSION_NONE) session_start();
$input = json_decode(file_get_contents('php://input'), true);
$table = intval($input['table'] ?? $_SESSION['table'] ?? -1);
if ($table < 0) {
  http_response_code(400);
  echo json_encode(['success' => false]); exit;
}
$roomId = 'SmakAI_T' . $table . '_' . time();
$callsFile = __DIR__ . '/../../data/call_requests.json';
$calls = [];
if (file_exists($callsFile)) {
  $calls = json_decode(@file_get_contents($callsFile), true) ?: [];
}
$calls[] = [
  'table' => $table,
  'room' => $roomId,
  'time' => date('H:i:s'),
  'status' => 'pending'
];
if (count($calls) > 50) $calls = array_slice($calls, -50);
@file_put_contents($callsFile, json_encode($calls, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
echo json_encode(['success' => true, 'room' => $roomId]);
