<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/read-json.php';

// Ensure critical data dirs exist
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
$msgDir = $dataDir . '/messages';
if (!is_dir($msgDir)) @mkdir($msgDir, 0755, true);

function requireAdmin() {
  if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admins/index.php');
    exit;
  }
}
function loadJSON($path) {
  $data = readJSON($path);
  return $data ?: [];
}
function saveJSON($path, $data) {
  $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  if (!$content) return false;
  if (function_exists('file_put_contents')) {
    return file_put_contents($path, $content, LOCK_EX) !== false;
  }
  $handle = @fopen($path, 'w');
  if (!$handle) return false;
  $written = fwrite($handle, $content);
  fclose($handle);
  return $written !== false;
}
function getTableNum() {
  return $_SESSION['table'] ?? 0;
}
function getTableToken() {
  return $_SESSION['table_token'] ?? '';
}
function csrfToken() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}
function verifyCSRF($token) {
  return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function requireCSRF() {
  $token = $_POST['csrf_token'] ?? '';
  if (!verifyCSRF($token)) {
    http_response_code(403);
    die('Security token expired. Please go back and try again.');
  }
}
