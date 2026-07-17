<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$room = $_GET['room'] ?? '';
$tableNum = intval($_GET['table'] ?? 0);

if ($tableNum && $room) {
  $callsFile = __DIR__ . '/../data/call_requests.json';
  $calls = loadJSON($callsFile);
  $changed = false;
  foreach ($calls as &$c) {
    if ($c['room'] === $room || $c['table'] === $tableNum) {
      $c['status'] = 'answered';
      $changed = true;
    }
  }
  if ($changed) saveJSON($callsFile, $calls);
}

$jitsiRoom = $room ? htmlspecialchars($room) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Call — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}
/* Hide Jitsi post-call branding */
#jitsiContainer .jitsi-watermark,
#jitsiContainer .watermark,
#jitsiContainer .powered-by,
#jitsiContainer .brand-watermark { display: none !important; }
</style>
</head>
<body>
<div class="max-w-5xl mx-auto px-4 py-4">
  <nav class="flex items-center gap-3 flex-wrap mb-4">
    <a href="/admins/dashboard.php" class="text-xl font-bold no-underline" style="color:#2D3436;">← SmakAI Admin</a>
    <span class="text-gray-400">/ Call</span>
    <span class="text-sm text-gray-500 ml-auto">Table <?= $tableNum ?: '?' ?></span>
  </nav>

  <div class="text-center mb-4">
    <h1 class="text-3xl font-bold" style="color:#2D3436;">📞 <span style="color:#6c5ce7;">Voice Call</span></h1>
    <p class="text-gray-500 text-sm" id="adminCallStatus">Connecting...</p>
  </div>

  <?php if ($jitsiRoom): ?>
  <div class="bg-white/80 backdrop-blur rounded-2xl shadow border border-gray-100 overflow-hidden" style="border-radius:20px;">
    <div id="jitsiContainer" style="height:550px;width:100%;"></div>
  </div>

  <div class="flex gap-3 justify-center mt-4" id="callActions">
    <button id="endCallBtn" class="px-8 py-3 rounded-full font-bold text-white" style="background:#FF6B6B;display:none;" onclick="endCall()">🔴 End Call</button>
    <a href="/admins/talk.php" class="px-6 py-3 rounded-full font-bold no-underline inline-block text-sm" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:#2D3436;">💬 Back to Chat</a>
  </div>

  <div id="callEnded" class="text-center py-8" style="display:none;">
    <div class="text-5xl mb-3">📞</div>
    <h2 class="text-2xl font-bold" style="color:#2D3436;">Call Ended</h2>
    <p class="text-gray-500 mt-1 text-sm">Customer call completed.</p>
  </div>

  <script src="https://meet.jit.si/external_api.js"></script>
  <script>
  (function() {
    var jitsiRoom = '<?= $jitsiRoom ?>';
    var jitsiContainer = document.getElementById('jitsiContainer');
    var jitsiApi = null;
    var ended = false;

    function showEnded() {
      if (ended) return;
      ended = true;
      if (jitsiApi) { jitsiApi.dispose(); jitsiApi = null; }
      document.getElementById('jitsiContainer').innerHTML = '';
      document.getElementById('callActions').style.display = 'none';
      document.getElementById('callEnded').style.display = 'block';
      document.getElementById('adminCallStatus').textContent = 'Call ended.';
      document.getElementById('endCallBtn').style.display = 'none';
    }

    function startCall() {
      document.getElementById('adminCallStatus').textContent = 'Joining call room...';
      jitsiApi = new JitsiMeetExternalAPI('meet.jit.si', {
        roomName: jitsiRoom,
        parentNode: jitsiContainer,
        configOverrides: {
          startWithAudioMuted: false,
          startWithVideoMuted: true,
          disableDeepLinking: true,
          enableCalendarIntegration: false,
          prejoinPageEnabled: false,
          toolbarButtons: ['microphone', 'hangup'],
          hideConferenceTimer: true,
          disableShortcuts: true
        },
        interfaceConfigOverrides: {
          SHOW_JITSI_WATERMARK: false,
          SHOW_WATERMARK_FOR_GUESTS: false,
          TOOLBAR_ALWAYS_VISIBLE: true,
          DISABLE_JOIN_LEAVE_NOTIFICATIONS: true,
          FILM_STRIP_MAX_HEIGHT: 0,
          SHOW_BRAND_WATERMARK: false,
          SHOW_POWERED_BY: false
        }
      });

      jitsiApi.addListener('videoConferenceJoined', function() {
        document.getElementById('adminCallStatus').textContent = '🔊 Connected!';
        document.getElementById('endCallBtn').style.display = 'inline-block';
      });

      jitsiApi.addListener('readyToClose', function() {
        showEnded();
      });
    }

    window.endCall = function() { showEnded(); };

    document.addEventListener('DOMContentLoaded', startCall);
  })();
  </script>
  <?php else: ?>
  <div class="bg-white/80 backdrop-blur rounded-2xl p-8 shadow border border-gray-100 text-center">
    <p class="text-gray-400 text-lg mb-4">😕 No room specified</p>
    <p class="text-sm text-gray-500 mb-4">Answer a call request from the Chat page to join a room.</p>
    <a href="/admins/talk.php" class="px-6 py-3 rounded-full font-bold inline-block text-white" style="background:#6c5ce7;">💬 Go to Chat</a>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
