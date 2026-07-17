<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: max-age=120');

require_once __DIR__ . '/../includes/menu-loader.php';

$menuData = loadMenuFromGist();

if ($menuData) {
  echo json_encode($menuData);
} else {
  http_response_code(503);
  echo json_encode(['error' => 'Menu data unavailable']);
}
