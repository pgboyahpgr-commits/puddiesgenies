var lastMsgId = 0;
var isPolling = true;
var pollErrorCount = 0;

function getTableNum() {
  var el = document.getElementById('chatContainer');
  return el ? parseInt(el.dataset.table) || 0 : 0;
}

function setStatus(msg, type) {
  var el = document.getElementById('chatStatus');
  var dot = document.getElementById('statusDot');
  if (!el) return;
  el.textContent = msg;
  if (type === 'error') { el.style.color = '#f68e9a'; if (dot) { dot.className = 'status-dot offline'; } }
  else if (type === 'connected') { el.style.color = '#2ecc71'; if (dot) { dot.className = 'status-dot online'; } }
  else { el.style.color = '#999'; if (dot) { dot.className = 'status-dot connecting'; } }
}

function timeStr() {
  var d = new Date();
  return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}

function appendMessage(text, from) {
  var container = document.getElementById('chatMessages');
  if (!container) return;
  var div = document.createElement('div');
  div.className = 'chat-msg ' + (from === 'table' ? 'table' : 'admin');
  var span = document.createElement('span');
  span.textContent = text;
  div.appendChild(span);
  var time = document.createElement('div');
  time.className = 'msg-time';
  time.textContent = timeStr();
  if (from === 'table') {
    var status = document.createElement('span');
    status.className = 'msg-status';
    status.textContent = '✓';
    time.appendChild(status);
  }
  div.appendChild(time);
  container.appendChild(div);
  var chatContainer = document.getElementById('chatContainer') || document.querySelector('.chat-messages');
  if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
}

function pollMessages() {
  if (!isPolling) return;
  var table = getTableNum();
  fetch('/api/talk/read.php?table=' + table + '&after=' + lastMsgId + '&_=' + Date.now())
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function(data) {
      pollErrorCount = 0;
      setStatus('Connected', 'connected');
      if (data && Array.isArray(data)) {
        data.forEach(function(msg) {
          if (msg.id > lastMsgId) {
            appendMessage(msg.text, msg.from === 'admin' ? 'admin' : 'table');
            lastMsgId = msg.id;
          }
        });
      }
    })
    .catch(function(e) {
      pollErrorCount++;
      if (pollErrorCount === 1) setStatus('Connecting...', 'error');
      if (pollErrorCount >= 3) setStatus('Connection issue', 'error');
    });
  setTimeout(pollMessages, 2000);
}

function sendMessage() {
  var input = document.getElementById('chatInput');
  var text = input.value.trim();
  if (!text) return;
  var table = getTableNum();
  input.disabled = true;
  setStatus('Sending...');
  fetch('/api/talk/send.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ table: table, text: text, from: 'table' })
  })
  .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
  .then(function(data) {
    if (data.success) {
      appendMessage(text, 'table');
      input.value = '';
      setStatus('Connected', 'connected');
    } else {
      setStatus('Send failed', 'error');
    }
  })
  .catch(function(e) {
    setStatus('Network error', 'error');
  })
  .then(function() { input.disabled = false; input.focus(); });
}

function showTyping(show) {
  var el = document.getElementById('typingIndicator');
  if (el) el.classList.toggle('show', show);
}

document.addEventListener('DOMContentLoaded', function() {
  var sendBtn = document.getElementById('sendBtn');
  var chatInput = document.getElementById('chatInput');
  if (sendBtn) sendBtn.addEventListener('click', sendMessage);
  if (chatInput) chatInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') sendMessage();
  });

  document.getElementById('callBtn')?.addEventListener('click', function() {
    var table = getTableNum();
    fetch('/api/talk/request-call.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ table: table })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success && data.room) {
        appendMessage('📞 Call request sent! Redirecting...', 'table');
        setTimeout(function() { window.location.href = '/call.php?room=' + data.room; }, 1500);
      } else {
        appendMessage('⚠️ Could not place call. Try again.', 'table');
      }
    })
    .catch(function() {
      appendMessage('⚠️ Network error. Try again.', 'table');
    });
  });

  setStatus('Connecting...');
  fetch('/api/talk/read.php?table=' + getTableNum() + '&_=' + Date.now())
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function(data) {
      if (Array.isArray(data)) {
        data.forEach(function(msg) {
          if (msg.id > lastMsgId) {
            appendMessage(msg.text, msg.from === 'admin' ? 'admin' : 'table');
            lastMsgId = msg.id;
          }
        });
      }
      setStatus('Connected', 'connected');
    })
    .catch(function() { setStatus('Could not connect', 'error'); });
  pollMessages();
});
