<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: max-age=60');

$eventsFile = __DIR__ . '/../data/events.json';
if (!file_exists($eventsFile)) {
  echo json_encode([]);
  exit;
}
$events = json_decode(file_get_contents($eventsFile), true);
if (!is_array($events)) { echo json_encode([]); exit; }

$today = date('Y-m-d');

// Return only active events in date range
$active = array_values(array_filter($events, function($e) use ($today) {
  if (empty($e['active'])) return false;
  if (!empty($e['start_date']) && $e['start_date'] > $today) return false;
  if (!empty($e['end_date']) && $e['end_date'] < $today) return false;
  return true;
}));

echo json_encode($active);
