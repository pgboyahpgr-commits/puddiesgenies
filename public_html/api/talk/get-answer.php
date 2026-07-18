<?php
header('Content-Type: application/json');
$callId = $_GET['callId'] ?? '';
if (!$callId) { echo json_encode(['answer' => null, 'status' => 'error']); exit; }
$file = __DIR__ . '/../../data/signaling/' . basename($callId) . '.json';
if (!file_exists($file)) { echo json_encode(['answer' => null, 'status' => 'ended']); exit; }
$d = json_decode(file_get_contents($file), true);
if ($d['answer']) {
  echo json_encode(['answer' => $d['answer'], 'status' => $d['status'] ?? 'connected']);
} else {
  echo json_encode(['answer' => null, 'status' => $d['status'] ?? 'waiting']);
}