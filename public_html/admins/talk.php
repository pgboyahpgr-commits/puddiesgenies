<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$tables = loadJSON(__DIR__ . '/../data/tables.json');
$msgDir = __DIR__ . '/../data/messages/';
$selectedTable = isset($_GET['table']) ? intval($_GET['table']) : -1;
$callRequests = loadJSON(__DIR__ . '/../data/call_requests.json');
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Talk — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}</style>
</head>
<body>
<nav class="max-w-7xl mx-auto px-4 py-4 flex items-center gap-3 flex-wrap">
  <a href="/admins/dashboard.php" class="text-xl font-bold no-underline" style="color:var(--text);" data-translate>← SmakAI Admin</a>
  <span class="text-gray-400" data-translate>/ Talk</span>
</nav>
<main class="max-w-6xl mx-auto px-4 pb-12">
  <h1 class="text-3xl font-bold mb-4" style="color:var(--text);" data-translate>💬 Talk Inbox</h1>

  <?php if (!empty($callRequests)): ?>
  <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4 mb-4">
    <h3 class="font-bold text-sm mb-2" data-translate>📞 Pending Call Requests</h3>
    <?php foreach (array_reverse($callRequests) as $c): if ($c['status'] !== 'pending') continue; ?>
    <div class="flex items-center gap-3 mb-2">
      <span class="text-sm">Table <?=intval($c['table'])?> wants to call</span>
      <a href="/admins/call.php?room=<?=urlencode($c['room'])?>&table=<?=intval($c['table'])?>" class="px-4 py-1 rounded-full text-xs font-bold text-white" style="background:#6c5ce7;">🔊 Answer</a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white/80 backdrop-blur rounded-2xl p-4 shadow border border-gray-100">
      <h3 class="font-bold text-sm mb-3" data-translate>Tables</h3>
      <?php
        $guestFile = $msgDir . 'table_0.json';
        $guestCount = file_exists($guestFile) ? count(json_decode(@file_get_contents($guestFile), true) ?: []) : 0;
      ?>
      <a href="?table=0" class="flex items-center justify-between px-3 py-2 rounded-xl mb-1 <?=$selectedTable===0?'bg-[#f68e9a] text-white':'hover:bg-gray-100'?> no-underline">
        <span data-translate>👤 Guest Talk</span>
        <?php if ($guestCount > 0): ?><span class="text-xs bg-white/30 rounded-full px-2 py-0.5"><?=$guestCount?></span><?php endif; ?>
      </a>
      <?php foreach (($tables['tables'] ?? []) as $t): ?>
      <?php
        $msgFile = $msgDir . 'table_' . $t['number'] . '.json';
        $msgCount = file_exists($msgFile) ? count(json_decode(@file_get_contents($msgFile), true) ?: []) : 0;
      ?>
      <a href="?table=<?=$t['number']?>" class="flex items-center justify-between px-3 py-2 rounded-xl mb-1 <?=$selectedTable===$t['number']?'bg-[#f68e9a] text-white':'hover:bg-gray-100'?> no-underline">
        <span>Table <?=$t['number']?></span>
        <?php if ($msgCount > 0): ?><span class="text-xs bg-white/30 rounded-full px-2 py-0.5"><?=$msgCount?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="md:col-span-3 bg-white/80 backdrop-blur rounded-2xl shadow border border-gray-100 overflow-hidden flex flex-col" style="height:500px;">
      <?php if ($selectedTable >= 0): ?>
      <?php
        $msgFile = $msgDir . 'table_' . $selectedTable . '.json';
        $messages = file_exists($msgFile) ? (json_decode(@file_get_contents($msgFile), true) ?: []) : [];
      ?>
      <div class="flex-1 overflow-y-auto p-4" id="adminChatMessages" style="display:flex;flex-direction:column;">
        <?php foreach ($messages as $msg): ?>
        <div class="chat-msg <?=$msg['from']==='admin'?'admin':'table'?>"><?=htmlspecialchars($msg['text'])?></div>
        <?php endforeach; ?>
        <?php if (empty($messages)): ?><div class="text-center text-gray-400 py-8 flex-1 flex items-center justify-center" id="emptyMsg" data-translate>No messages yet</div><?php endif; ?>
      </div>
      <div class="flex gap-2 p-3 border-t border-gray-200">
        <input type="text" id="adminChatInput" placeholder="Type reply..." class="flex-1 px-4 py-3 rounded-full border-2 border-gray-200 outline-none focus:border-[#f68e9a] text-sm" />
        <button id="adminSendBtn" class="px-6 py-3 rounded-full font-bold text-white" style="background:#f68e9a;" data-translate>Send</button>
      </div>
      <?php else: ?>
      <div class="flex items-center justify-center h-full text-gray-400" data-translate>Select a table to view messages</div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
(function() {
  var csrfToken = <?=json_encode($csrfToken)?>;
  var selectedTable = <?=$selectedTable?>;
  var adminLastMsgId = 0;
  var adminPollTimer = null;

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function scrollToBottom() {
    var container = document.getElementById('adminChatMessages');
    if (container) container.scrollTop = container.scrollHeight;
  }

  function appendMessage(text, from) {
    var container = document.getElementById('adminChatMessages');
    if (!container) return;
    var emptyMsg = document.getElementById('emptyMsg');
    if (emptyMsg) emptyMsg.style.display = 'none';
    var div = document.createElement('div');
    div.className = 'chat-msg ' + (from === 'admin' ? 'admin' : 'table');
    div.textContent = text;
    container.appendChild(div);
    scrollToBottom();
  }

  function sendMessage() {
    var input = document.getElementById('adminChatInput');
    var text = input.value.trim();
    if (!text) return;
    input.disabled = true;
    document.getElementById('adminSendBtn').disabled = true;

    fetch('/api/talk/send.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        table: selectedTable,
        text: text,
        from: 'admin',
        csrf_token: csrfToken
      })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        appendMessage(text, 'admin');
        if (data.id && data.id > adminLastMsgId) adminLastMsgId = data.id;
        input.value = '';
      } else {
        alert('Send failed: ' + (data.error || 'unknown'));
      }
    })
    .catch(function() {
      alert('Network error. Try again.');
    })
    .then(function() {
      input.disabled = false;
      document.getElementById('adminSendBtn').disabled = false;
      input.focus();
    });
  }

  function getMaxMsgId() {
    var container = document.getElementById('adminChatMessages');
    if (!container) return 0;
    var msgs = container.querySelectorAll('.chat-msg');
    var maxId = 0;
    msgs.forEach(function(el, idx) {
      var dataId = parseInt(el.dataset.msgId);
      if (dataId && dataId > maxId) maxId = dataId;
    });
    if (maxId === 0) maxId = msgs.length;
    return maxId;
  }

  function pollMessages() {
    if (selectedTable === undefined || selectedTable < 0) return;
    fetch('/api/talk/read.php?table=' + selectedTable + '&after=' + adminLastMsgId + '&_=' + Date.now())
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (Array.isArray(data)) {
          data.forEach(function(msg) {
            if (msg.id && msg.id > adminLastMsgId) {
              appendMessage(msg.text, msg.from === 'admin' ? 'admin' : 'table');
              adminLastMsgId = msg.id;
            }
          });
        }
      }).catch(function(){});
  }

  document.addEventListener('DOMContentLoaded', function() {
    adminLastMsgId = getMaxMsgId();
    scrollToBottom();

    document.getElementById('adminSendBtn')?.addEventListener('click', sendMessage);
    document.getElementById('adminChatInput')?.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') sendMessage();
    });

    if (selectedTable >= 0) {
      adminPollTimer = setInterval(pollMessages, 2000);
    }
  });

  window.addEventListener('beforeunload', function() {
    if (adminPollTimer) clearInterval(adminPollTimer);
  });
})();
</script>
<style>
.chat-msg {
  max-width:80%;padding:10px 16px;border-radius:20px;margin-bottom:8px;font-size:0.9rem;line-height:1.4;
}
.chat-msg.admin { background:#e9e9e9; color:var(--text); align-self:flex-start; border-bottom-left-radius:4px; }
.chat-msg.table { background:#538bdf; color:#fff; align-self:flex-end; border-bottom-right-radius:4px; margin-left:auto; }
#adminChatMessages { display:flex; flex-direction:column; }
</style>
</body>
</html>
