<?php
function toEmbedUrl($url) {
  if (preg_match('/(?:youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/|live\/|shorts\/)|youtu\.be\/)([\w-]+)/', $url, $m)) {
    return 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&mute=1&enablejsapi=1';
  }
  return $url;
}

function getStreamConfig() {
  $raw = @file_get_contents(__DIR__ . '/../data/stream_config.json');
  $cfg = $raw ? @json_decode($raw, true) : [];
  $url = isset($cfg['video_url']) ? trim($cfg['video_url']) : '';
  return [
    'video_url' => $url,
    'video_on' => ($cfg['video_status'] ?? '') === 'on' && $url !== '',
    'embed_url' => ($cfg['video_status'] ?? '') === 'on' && $url !== '' ? toEmbedUrl($url) : '',
    'last_updated' => $cfg['last_updated'] ?? ''
  ];
}
