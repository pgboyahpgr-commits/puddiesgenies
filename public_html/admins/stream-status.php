<?php
header('Content-Type: application/json');
$raw = @file_get_contents(__DIR__ . '/../data/stream_config.json');
$config = $raw ? @json_decode($raw, true) : [];
$videoUrl = isset($config['video_url']) ? trim($config['video_url']) : '';
$videoOn = ($config['video_status'] ?? '') === 'on' && $videoUrl !== '';
$lastUpdated = $config['last_updated'] ?? '';
$lastPolled = $_GET['since'] ?? '';
echo json_encode([
  'reload' => ($lastPolled && $lastUpdated && $lastPolled !== $lastUpdated) ? true : false,
  'on' => $videoOn ? '1' : '0',
  'last_updated' => $lastUpdated
]);
