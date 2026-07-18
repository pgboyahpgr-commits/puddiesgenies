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
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Call — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/assets/style.css" />
<style>
body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}
.status-dot{width:12px;height:12px;border-radius:50%;display:inline-block;transition:background 0.3s;}
.status-dot.offline{background:#f68e9a;}
.status-dot.connecting{background:#f1c40f;animation:pulse 1s infinite;}
.status-dot.connected{background:#2ecc71;}
.status-dot.ended{background:#999;}
.status-dot.error{background:#e74c3c;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.4;}}
.audio-bar{width:4px;border-radius:4px;background:linear-gradient(to top,#6c5ce7,#a29bfe);animation:barAnim 0.5s ease-in-out infinite alternate;display:inline-block;}
.audio-bar.bar1{height:12px;animation-delay:0s;}
.audio-bar.bar2{height:18px;animation-delay:0.1s;}
.audio-bar.bar3{height:24px;animation-delay:0.2s;}
.audio-bar.bar4{height:18px;animation-delay:0.3s;}
.audio-bar.bar5{height:12px;animation-delay:0.4s;}
@keyframes barAnim{0%{transform:scaleY(0.3);}100%{transform:scaleY(1);}}
</style>
</head>
<body>
<div class="max-w-5xl mx-auto px-4 py-4">
  <nav class="flex items-center gap-3 flex-wrap mb-4">
    <a href="/admins/dashboard.php" class="text-xl font-bold no-underline" style="color:var(--text);">← SmakAI Admin</a>
    <span class="text-gray-400">/ Call</span>
    <span class="text-sm text-gray-500 ml-auto">Table <?= $tableNum ?: '?' ?></span>
  </nav>

  <div class="text-center mb-4">
    <h1 class="text-3xl font-bold" style="color:var(--text);">📞 <span style="color:#6c5ce7;">Voice Call</span></h1>
    <p class="text-sm text-gray-400" id="adminCallStatus">Table <?= $tableNum ?: '?' ?> — Connecting...</p>
  </div>

  <div class="text-center py-10 px-6 mb-4" style="background:rgba(255,255,255,0.8);backdrop-filter:blur(8px);border-radius:20px;box-shadow:0 1px 3px rgba(0,0,0,0.05);border:1px solid #f0e8ea;">
    <div class="flex flex-col items-center gap-4">
      <div class="w-24 h-24 rounded-full flex items-center justify-center text-4xl" style="background:var(--pink-light);color:var(--primary);">🎧</div>

      <div class="flex items-center gap-2">
        <span id="adminCallStatusDot" class="status-dot offline"></span>
        <span id="adminCallStatusLabel" class="text-sm font-medium" style="color:#666;">Connecting...</span>
      </div>

      <div id="adminCallTimer" class="text-lg font-mono font-bold hidden" style="color:var(--text);">00:00</div>

      <div id="adminAudioVisualizer" class="flex items-center gap-1 h-8 hidden">
        <span class="audio-bar bar1"></span><span class="audio-bar bar2"></span><span class="audio-bar bar3"></span>
        <span class="audio-bar bar4"></span><span class="audio-bar bar5"></span>
      </div>
    </div>
  </div>

  <div class="flex gap-3 justify-center mt-4" id="callActions">
    <button id="adminMuteBtn" class="px-6 py-3 rounded-full font-bold" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);display:none;" onclick="toggleMute()">🔊 Mute</button>
    <button id="adminEndCallBtn" class="px-8 py-3 rounded-full font-bold text-white" style="background:#f68e9a;display:none;" onclick="endCall()">🔴 End Call</button>
    <a href="/admins/talk.php" class="px-6 py-3 rounded-full font-bold no-underline inline-block text-sm" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);">💬 Back to Chat</a>
  </div>

  <div id="callEnded" class="text-center py-8" style="display:none;">
    <div class="text-5xl mb-3">📞</div>
    <h2 class="text-2xl font-bold" style="color:var(--text);">Call Ended</h2>
    <p class="text-gray-500 mt-1 text-sm">Customer call completed.</p>
  </div>
</div>

<audio id="remoteAudio" autoplay style="display:none;"></audio>
<audio id="localAudio" muted style="display:none;"></audio>

<script src="/assets/call.js"></script>
<script>
(function() {
  var room = <?= json_encode($room) ?>;
  var table = <?= $tableNum ?>;
  var muted = false;
  var timerInterval = null;
  var seconds = 0;

  function startTimer() {
    document.getElementById('adminCallTimer').classList.remove('hidden');
    document.getElementById('adminAudioVisualizer').classList.remove('hidden');
    timerInterval = setInterval(function() {
      seconds++;
      var m = String(Math.floor(seconds / 60)).padStart(2, '0');
      var s = String(seconds % 60).padStart(2, '0');
      document.getElementById('adminCallTimer').textContent = m + ':' + s;
    }, 1000);
  }

  function stopTimer() {
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
  }

  window.endCall = function() {
    SmakCall.hangup();
  };

  window.toggleMute = function() {
    muted = !muted;
    var btn = document.getElementById('adminMuteBtn');
    btn.textContent = muted ? '🔇 Muted' : '🔊 Mute';
    var localAudio = document.getElementById('localAudio');
    if (localAudio && localAudio.srcObject) {
      localAudio.srcObject.getAudioTracks().forEach(function(t) { t.enabled = !muted; });
    }
  };

  document.addEventListener('DOMContentLoaded', function() {
    SmakCall.init(room, 'admin', {
      onRemoteConnected: function() {
        startTimer();
        document.getElementById('adminEndCallBtn').style.display = 'inline-block';
        document.getElementById('adminMuteBtn').style.display = 'inline-block';
        document.getElementById('adminCallStatus').textContent = '🔊 Connected with Table ' + table;
      },
      onCallEnded: function(reason) {
        stopTimer();
        document.getElementById('callActions').style.display = 'none';
        document.getElementById('callEnded').style.display = 'block';
        document.getElementById('adminCallStatusLabel').textContent = 'Call ended';
        document.getElementById('adminCallStatusDot').className = 'status-dot ended';
        document.getElementById('adminAudioVisualizer').classList.add('hidden');
      },
      onError: function(msg) {
        document.getElementById('adminCallStatusLabel').textContent = msg;
        document.getElementById('adminCallStatusDot').className = 'status-dot error';
      }
    });
    SmakCall.start();
  });
})();
</script>
</body>
</html>
