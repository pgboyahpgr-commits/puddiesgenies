<?php
require_once __DIR__ . '/includes/header.php';
$room = $_GET['room'] ?? '';
$tableNum = $_SESSION['table'] ?? 0;
?>
<main class="max-w-2xl mx-auto px-4 pb-12 text-center">
  <div class="blob-bg blob-1"></div>
  <div class="glass-card p-8 mt-8">
    <img src="https://files.svgcdn.io/solar/phone-calling-rounded-bold.svg" width="64" height="64" class="mx-auto mb-4 opacity-60" />
    <h2 class="text-2xl font-bold mb-2" style="font-family:'Fredoka',sans-serif;">📞 Voice Call</h2>
    <p class="text-gray-500 mb-6" id="callStatus">Connecting to restaurant...</p>
    <div class="flex gap-4 justify-center">
      <button id="endCallBtn" class="btn-bouncy px-8 py-3" style="background:#FF6B6B;color:#fff;display:none;" onclick="endCall()">🔴 End Call</button>
      <a href="/chat.php" class="btn-bouncy btn-outline px-6 py-3 no-underline">💬 Back to Chat</a>
    </div>
  </div>
</main>

<script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
<script>
const room = '<?= $room ?>';
let peer = null;
let conn = null;
let localStream = null;

function endCall() {
  if (conn) conn.close();
  if (localStream) localStream.getTracks().forEach(t => t.stop());
  if (peer) peer.destroy();
  document.getElementById('callStatus').textContent = 'Call ended.';
  document.getElementById('endCallBtn').style.display = 'none';
}

if (room) {
  peer = new Peer('smak_' + room + '_' + Date.now());
  peer.on('open', function(id) {
    document.getElementById('callStatus').textContent = 'Connected! Waiting for the other person...';
    conn = peer.connect('smak_admin_' + room);
    conn.on('open', function() {
      document.getElementById('callStatus').textContent = '🔊 Call connected!';
      document.getElementById('endCallBtn').style.display = 'inline-block';
      navigator.mediaDevices.getUserMedia({ audio: true, video: false })
        .then(stream => {
          localStream = stream;
          const call = peer.call('smak_admin_' + room, stream);
          call.on('stream', remoteStream => {
            const audio = new Audio();
            audio.srcObject = remoteStream;
            audio.play();
          });
        })
        .catch(() => {
          document.getElementById('callStatus').textContent = 'Mic access needed for voice call.';
        });
    });
  });
  peer.on('error', function() {
    document.getElementById('callStatus').textContent = '⚠️ Could not establish voice connection. Try text chat instead.';
  });
} else {
  document.getElementById('callStatus').textContent = 'No room specified.';
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
