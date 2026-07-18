<?php
header('Content-Type: application/json');
$callId = $_GET['callId'] ?? '';
if (!$callId) { echo json_encode(['error' => 'No callId']); exit; }
$file = __DIR__ . '/../../data/signaling/' . basename($callId) . '.json';
if (!file_exists($file)) { echo json_encode(['error' => 'Not found']); exit; }
$d = json_decode(file_get_contents($file), true);
echo json_encode(['offer' => $d['offer'] ?? '', 'tableId' => $d['tableId'] ?? '']);