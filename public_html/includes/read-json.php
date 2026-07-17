<?php
function readJSON($path) {
  if (file_exists($path) && is_readable($path)) {
    $data = null;
    if (function_exists('file_get_contents')) {
      $data = @file_get_contents($path);
    }
    if (!$data && function_exists('curl_init')) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'file://' . realpath($path));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $data = curl_exec($ch);
      curl_close($ch);
    }
    if (!$data) {
      $handle = @fopen($path, 'r');
      if ($handle) {
        $data = fread($handle, filesize($path));
        fclose($handle);
      }
    }
    if ($data) {
      $decoded = json_decode($data, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
      }
    }
  }
  return null;
}
