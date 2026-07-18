<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
if (session_status() === PHP_SESSION_NONE) session_start();

$room = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['room'] ?? '');
$action = $_GET['action'] ?? '';
$from = $_GET['from'] ?? '';

if (!$room || !$action) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Missing room or action']); exit;
}

$signalDir = __DIR__ . '/../../data/signals';
if (!is_dir($signalDir)) @mkdir($signalDir, 0755, true);

$offerFile = $signalDir . '/' . $room . '_offer.json';
$answerFile = $signalDir . '/' . $room . '_answer.json';
$iceDir = $signalDir . '/' . $room . '_ice';
if (!is_dir($iceDir)) @mkdir($iceDir, 0755, true);

// ─── OFFER ───────────────────────────────────────────────────
if ($action === 'offer') {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['sdp'])) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Missing sdp']); exit;
    }
    file_put_contents($offerFile, json_encode(['sdp' => $input['sdp'], 'type' => 'offer', 'time' => time()]), LOCK_EX);
    echo json_encode(['success' => true]);
    exit;
  }
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($offerFile)) {
      echo json_encode(['success' => false, 'available' => false]); exit;
    }
    $data = json_decode(file_get_contents($offerFile), true);
    echo json_encode(['success' => true, 'available' => true, 'sdp' => $data['sdp']]);
    exit;
  }
}

// ─── ANSWER ───────────────────────────────────────────────────
if ($action === 'answer') {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['sdp'])) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Missing sdp']); exit;
    }
    file_put_contents($answerFile, json_encode(['sdp' => $input['sdp'], 'type' => 'answer', 'time' => time()]), LOCK_EX);
    echo json_encode(['success' => true]);
    exit;
  }
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($answerFile)) {
      echo json_encode(['success' => false, 'available' => false]); exit;
    }
    $data = json_decode(file_get_contents($answerFile), true);
    echo json_encode(['success' => true, 'available' => true, 'sdp' => $data['sdp']]);
    exit;
  }
}

// ─── ICE CANDIDATES ──────────────────────────────────────────
if ($action === 'ice') {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['candidate'])) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Missing candidate']); exit;
    }
    $from = $from === 'admin' ? 'admin' : 'caller';
    $iceFile = $iceDir . '/' . $from . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.json';
    file_put_contents($iceFile, json_encode([
      'candidate' => $input['candidate'],
      'from' => $from,
      'time' => time()
    ]), LOCK_EX);
    // Keep only last 50 candidates
    $files = glob($iceDir . '/' . $from . '_*.json');
    if (count($files) > 50) {
      $toDelete = array_slice($files, 0, count($files) - 50);
      foreach ($toDelete as $f) @unlink($f);
    }
    echo json_encode(['success' => true]);
    exit;
  }
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $since = intval($_GET['since'] ?? 0);
    $peer = $from === 'admin' ? 'caller' : 'admin';
    $files = glob($iceDir . '/' . $peer . '_*.json');
    $candidates = [];
    foreach ($files as $f) {
      $data = json_decode(file_get_contents($f), true);
      if ($data && $data['time'] > $since) {
        $candidates[] = ['candidate' => $data['candidate'], 'time' => $data['time']];
      }
    }
    echo json_encode(['success' => true, 'candidates' => $candidates]);
    exit;
  }
}

// ─── STATUS ───────────────────────────────────────────────────
if ($action === 'status') {
  $hasOffer = file_exists($offerFile);
  $hasAnswer = file_exists($answerFile);
  echo json_encode([
    'success' => true,
    'offer' => $hasOffer,
    'answer' => $hasAnswer,
    'active' => $hasOffer
  ]);
  exit;
}

// ─── END ──────────────────────────────────────────────────────
if ($action === 'end') {
  if (file_exists($offerFile)) @unlink($offerFile);
  if (file_exists($answerFile)) @unlink($answerFile);
  if (is_dir($iceDir)) {
    $files = glob($iceDir . '/*.json');
    foreach ($files as $f) @unlink($f);
    @rmdir($iceDir);
  }
  echo json_encode(['success' => true]);
  exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid action']);
