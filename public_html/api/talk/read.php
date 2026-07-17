<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$table = intval($_GET['table'] ?? -1);
if ($table < 0) { echo json_encode([]); exit; }
$msgFile = __DIR__ . '/../../data/messages/table_' . $table . '.json';
$after = intval($_GET['after'] ?? 0);
if (!file_exists($msgFile)) { echo json_encode([]); exit; }

$raw = @file_get_contents($msgFile);
if ($raw === false) { echo json_encode([]); exit; }
$data = json_decode($raw, true);
if (!is_array($data)) { echo json_encode([]); exit; }

if ($after > 0) {
  $filtered = [];
  foreach ($data as $m) {
    if (isset($m['id']) && $m['id'] > $after) {
      $filtered[] = $m;
    }
  }
  echo json_encode($filtered);
} else {
  echo json_encode($data);
}
