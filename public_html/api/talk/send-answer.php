<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$callId = $input['callId'] ?? '';
$answer = $input['answer'] ?? '';
if (!$callId || !$answer) { echo json_encode(['success' => false, 'error' => 'Missing data']); exit; }
$file = __DIR__ . '/../../data/signaling/' . basename($callId) . '.json';
if (!file_exists($file)) { echo json_encode(['success' => false, 'error' => 'Not found']); exit; }
$d = json_decode(file_get_contents($file), true);
$d['answer'] = $answer;
$d['status'] = 'connected';
file_put_contents($file, json_encode($d), LOCK_EX);
echo json_encode(['success' => true]);