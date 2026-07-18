<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');
$file = __DIR__ . '/../data/todays_special.json';
if (file_exists($file)) {
  $data = json_decode(file_get_contents($file), true);
  if ($data && !empty($data['active']) && !empty($data['dish_name'])) {
    echo json_encode(['success' => true, 'special' => $data]);
    exit;
  }
}
echo json_encode(['success' => false, 'special' => null]);