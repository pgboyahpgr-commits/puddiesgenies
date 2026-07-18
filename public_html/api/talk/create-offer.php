<?php
header('Content-Type: application/json');
session_start();
$input = json_decode(file_get_contents('php://input'), true);
$offer = $input['offer'] ?? '';
$tableId = $input['tableId'] ?? ('Table ' . ($_SESSION['table'] ?? 0));
if (!$offer) { echo json_encode(['success' => false, 'error' => 'No offer']); exit; }
$deviceId = $input['device_id'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$callId = 'call_' . time() . '_' . substr(md5(mt_rand()), 0, 8);
$data = [
  'id' => $callId, 'tableId' => $tableId, 'tableNum' => $_SESSION['table'] ?? 0,
  'offer' => $offer, 'answer' => null, 'status' => 'waiting', 'ts' => time(),
  'candidates' => [], 'answerCandidates' => [], 'device_id' => $deviceId
];
file_put_contents(__DIR__ . '/../../data/signaling/' . $callId . '.json', json_encode($data), LOCK_EX);
echo json_encode(['success' => true, 'callId' => $callId]);