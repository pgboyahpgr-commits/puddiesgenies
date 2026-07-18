<?php
require_once __DIR__ . '/includes/header.php';
$room = $_GET['room'] ?? '';
$tableNum = $_SESSION['table'] ?? 0;
if (!$room) { echo '<main class="max-w-4xl mx-auto px-4 pb-12 pt-12 text-center"><p class="text-gray-400" data-translate>No room specified</p><a href="/talk.php" class="btn-bouncy btn-outline px-6 py-3 no-underline text-sm inline-block mt-4" data-translate>💬 Back to Talk</a></main>'; require_once __DIR__ . '/includes/footer.php'; exit; }
?>
<main class="max-w-4xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-1"></div>
  <div class="text-center mb-6 pt-4">
    <h1 class="text-4xl font-bold" style="color:var(--text);"><span style="color:#6c5ce7;" data-translate>Voice Call</span></h1>
    <?php if ($tableNum > 0): ?>
    <p class="text-gray-500 mt-1" data-translate>Table <?= $tableNum ?></p>
    <?php endif; ?>
  </div>

  <div class="glass-card overflow-hidden mb-6 text-center py-10 px-6" style="border-radius:20px;">
    <div class="flex flex-col items-center gap-4">
      <div id="callAvatar" class="w-24 h-24 rounded-full flex items-center justify-center text-4xl" style="background:var(--pink-light);color:var(--primary);">🎧</div>

      <div class="flex items-center gap-2">
        <span id="callStatusDot" class="status-dot offline"></span>
        <span id="callStatusLabel" class="text-sm font-medium" style="color:var(--text-muted);" data-translate>Connecting...</span>
      </div>

      <div id="callTimerDisplay" class="text-lg font-mono font-bold hidden" style="color:var(--text);">00:00</div>

      <div id="audioVisualizer" class="flex items-center gap-1 h-8 hidden">
        <span class="audio-bar bar1"></span><span class="audio-bar bar2"></span><span class="audio-bar bar3"></span>
        <span class="audio-bar bar4"></span><span class="audio-bar bar5"></span>
      </div>
    </div>
  </div>

  <div class="flex gap-3 justify-center flex-wrap" id="callActions">
    <button id="muteBtn" class="btn-bouncy px-6 py-3" style="background:var(--pink-light);color:var(--text);display:none;" onclick="toggleMute()" data-translate>🔊 Mute</button>
    <button id="endCallBtn" class="btn-bouncy px-8 py-3" style="background:#f68e9a;color:#fff;display:none;" onclick="endCall()" data-translate>🔴 End Call</button>
    <a href="/talk.php" class="btn-bouncy btn-outline px-6 py-3 no-underline text-sm" data-translate>💬 Back to Talk</a>
    <a href="/menu.php" class="btn-bouncy btn-outline px-6 py-3 no-underline text-sm" data-translate>📋 Menu</a>
  </div>

  <div id="callEnded" class="text-center py-8" style="display:none;">
    <div class="text-5xl mb-3">📞</div>
    <h2 class="text-2xl font-bold" style="color:var(--text);" data-translate>Call Ended</h2>
    <p class="text-gray-500 mt-1 text-sm" data-translate>Thanks for using SmakAI!</p>
  </div>
</main>

<audio id="remoteAudio" autoplay style="display:none;"></audio>
<audio id="localAudio" muted style="display:none;"></audio>

<style>
.status-dot { width:12px;height:12px;border-radius:50%;display:inline-block;transition:background 0.3s; }
.status-dot.offline { background:#f68e9a; }
.status-dot.connecting { background:#f1c40f;animation:pulse 1s infinite; }
.status-dot.connected { background:#2ecc71; }
.status-dot.ended { background:#999; }
.status-dot.error { background:#e74c3c; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.4;} }

.audio-bar { width:4px;border-radius:4px;background:linear-gradient(to top,#6c5ce7,#a29bfe);animation:barAnim 0.5s ease-in-out infinite alternate; }
.audio-bar.bar1 { height:12px;animation-delay:0s; }
.audio-bar.bar2 { height:18px;animation-delay:0.1s; }
.audio-bar.bar3 { height:24px;animation-delay:0.2s; }
.audio-bar.bar4 { height:18px;animation-delay:0.3s; }
.audio-bar.bar5 { height:12px;animation-delay:0.4s; }
@keyframes barAnim { 0%{transform:scaleY(0.3);} 100%{transform:scaleY(1);} }
</style>

<script src="/assets/call.js"></script>
<script>
(function() {
  var room = <?= json_encode($room) ?>;
  var table = <?= $tableNum ?>;
  var muted = false;
  var timerInterval = null;
  var seconds = 0;

  function startTimer() {
    document.getElementById('callTimerDisplay').classList.remove('hidden');
    document.getElementById('audioVisualizer').classList.remove('hidden');
    timerInterval = setInterval(function() {
      seconds++;
      var m = String(Math.floor(seconds / 60)).padStart(2, '0');
      var s = String(seconds % 60).padStart(2, '0');
      document.getElementById('callTimerDisplay').textContent = m + ':' + s;
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
    var btn = document.getElementById('muteBtn');
    btn.textContent = muted ? '🔇 Muted' : '🔊 Mute';
    btn.dataset.translate = muted ? 'Muted' : 'Mute';
    var localAudio = document.getElementById('localAudio');
    if (localAudio && localAudio.srcObject) {
      localAudio.srcObject.getAudioTracks().forEach(function(t) { t.enabled = !muted; });
    }
  };

  document.addEventListener('DOMContentLoaded', function() {
    SmakCall.init(room, 'customer', {
      onRemoteConnected: function() {
        startTimer();
        document.getElementById('endCallBtn').style.display = 'inline-block';
        document.getElementById('muteBtn').style.display = 'inline-block';
      },
      onCallEnded: function(reason) {
        stopTimer();
        document.getElementById('callActions').style.display = 'none';
        document.getElementById('callEnded').style.display = 'block';
        document.getElementById('callStatusLabel').textContent = 'Call ended';
        document.getElementById('callStatusDot').className = 'status-dot ended';
        document.getElementById('audioVisualizer').classList.add('hidden');
      },
      onError: function(msg) {
        document.getElementById('callStatusLabel').textContent = msg;
        document.getElementById('callStatusDot').className = 'status-dot error';
      }
    });
    SmakCall.start();
  });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
