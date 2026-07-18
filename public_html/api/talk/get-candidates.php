<?php
header('Content-Type: application/json');
$callId = $_GET['callId'] ?? '';
$side = $_GET['side'] ?? 'offerer';
$after = intval($_GET['after'] ?? 0);
if (!$callId) { echo json_encode([]); exit; }
$file = __DIR__ . '/../../data/signaling/' . basename($callId) . '.json';
if (!file_exists($file)) { echo json_encode([]); exit; }
$d = json_decode(file_get_contents($file), true);
$key = $side === 'answerer' ? 'candidates' : 'answerCandidates';
$cands = $d[$key] ?? [];
echo json_encode(array_slice($cands, $after));