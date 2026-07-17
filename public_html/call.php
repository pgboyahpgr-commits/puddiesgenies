<?php
require_once __DIR__ . '/includes/header.php';
$room = $_GET['room'] ?? '';
$tableNum = $_SESSION['table'] ?? 0;
$jitsiRoom = $room ? htmlspecialchars($room) : ('SmakAI_Guest_' . time());
?>
<main class="max-w-4xl mx-auto px-4 pb-12">
  <div class="blob-bg blob-1"></div>
  <div class="text-center mb-4 pt-4">
    <h1 class="text-4xl font-bold" style="color:#2D3436;">📞 <span style="color:#6c5ce7;">Voice Call</span></h1>
    <?php if ($tableNum > 0): ?>
    <p class="text-gray-500 mt-1">Table #<?= $tableNum ?></p>
    <?php endif; ?>
    <p class="text-xs text-gray-400 mt-1" id="callStatus">Connecting...</p>
  </div>

  <div class="glass-card overflow-hidden mb-4" style="border-radius:20px;">
    <div id="jitsiContainer" style="height:500px;width:100%;"></div>
  </div>

  <div class="flex gap-3 justify-center flex-wrap" id="callActions">
    <button id="endCallBtn" class="btn-bouncy px-8 py-3" style="background:#FF6B6B;color:#fff;display:none;" onclick="endCall()">🔴 End Call</button>
    <a href="/talk.php" class="btn-bouncy btn-outline px-6 py-3 no-underline text-sm">💬 Back to Talk</a>
    <a href="/menu.php" class="btn-bouncy btn-outline px-6 py-3 no-underline text-sm">📋 Menu</a>
  </div>

  <div id="callEnded" class="text-center py-8" style="display:none;">
    <div class="text-5xl mb-3">📞</div>
    <h2 class="text-2xl font-bold" style="color:#2D3436;">Call Ended</h2>
    <p class="text-gray-500 mt-1 text-sm">Thanks for using SmakAI!</p>
  </div>
</main>

<style>
/* Hide Jitsi post-call branding overlay */
#jitsiContainer .jitsi-watermark,
#jitsiContainer .watermark,
#jitsiContainer .powered-by,
#jitsiContainer .brand-watermark { display: none !important; }
</style>

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
    document.getElementById('callStatus').textContent = 'Call ended.';
    document.getElementById('endCallBtn').style.display = 'none';
  }

  function startCall() {
    document.getElementById('callStatus').textContent = 'Joining call room...';
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
      document.getElementById('callStatus').textContent = '🔊 Connected!';
      document.getElementById('endCallBtn').style.display = 'inline-block';
    });

    jitsiApi.addListener('readyToClose', function() {
      showEnded();
    });
  }

  window.endCall = function() {
    showEnded();
  };

  document.addEventListener('DOMContentLoaded', startCall);
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
