<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$callId = $input['callId'] ?? '';
$candidate = $input['candidate'] ?? '';
$side = $input['side'] ?? 'offerer'; // 'offerer' or 'answerer'
if (!$callId || !$candidate) { echo json_encode(['success' => false]); exit; }
$file = __DIR__ . '/../../data/signaling/' . basename($callId) . '.json';
if (!file_exists($file)) { echo json_encode(['success' => false]); exit; }
$d = json_decode(file_get_contents($file), true);
$key = $side === 'answerer' ? 'answerCandidates' : 'candidates';
$d[$key][] = $candidate;
file_put_contents($file, json_encode($d), LOCK_EX);
echo json_encode(['success' => true]);