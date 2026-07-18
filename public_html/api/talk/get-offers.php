<?php
header('Content-Type: application/json');
$files = glob(__DIR__ . '/../../data/signaling/call_*.json');
$offers = [];
$count = 0;
$staleCutoff = time() - 600;
foreach (array_reverse($files) as $f) {
  $d = json_decode(file_get_contents($f), true);
  if (!$d) continue;
  $ts = $d['ts'] ?? 0;
  if ($ts > 0 && $ts < $staleCutoff) {
    if (($d['status'] ?? '') === 'waiting' || ($d['status'] ?? '') === 'connected') {
      $d['status'] = 'ended';
      file_put_contents($f, json_encode($d, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
    continue;
  }
  if (($d['status'] ?? '') === 'waiting') {
    $offers[] = ['id' => $d['id'], 'tableId' => $d['tableId'], 'tableNum' => $d['tableNum'] ?? 0, 'ts' => $ts];
    $count++;
    if ($count >= 50) break;
  }
}
echo json_encode($offers);