let lastMsgId = 0;
let isPolling = true;
let pollErrorCount = 0;

function getTableNum() {
  const el = document.getElementById('chatContainer');
  return el ? parseInt(el.dataset.table) || 0 : 0;
}

function setStatus(msg, isError) {
  const el = document.getElementById('chatStatus');
  if (el) { el.textContent = msg; el.style.color = isError ? '#FF6B6B' : '#999'; }
}

function pollMessages() {
  if (!isPolling) return;
  const table = getTableNum();
  fetch('/api/talk/read.php?table=' + table + '&after=' + lastMsgId + '&_=' + Date.now())
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function(data) {
      pollErrorCount = 0;
      if (data && Array.isArray(data)) {
        data.forEach(function(msg) {
          if (msg.id > lastMsgId) {
            appendMessage(msg.text, msg.from === 'admin' ? 'admin' : 'table');
            lastMsgId = msg.id;
          }
        });
      }
      setStatus('Connected');
    })
    .catch(function(e) {
      pollErrorCount++;
      if (pollErrorCount === 1) setStatus('Connecting...', true);
      if (pollErrorCount >= 3) setStatus('Connection issue — check server', true);
    });
  setTimeout(pollMessages, 2000);
}

function appendMessage(text, from) {
  const container = document.getElementById('chatMessages');
  if (!container) return;
  const div = document.createElement('div');
  div.className = 'chat-msg ' + (from === 'table' ? 'table' : 'admin');
  div.textContent = text;
  container.appendChild(div);
  const chatContainer = document.getElementById('chatContainer');
  if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
}

function sendMessage() {
  const input = document.getElementById('chatInput');
  const text = input.value.trim();
  if (!text) return;
  const table = getTableNum();
  input.disabled = true;
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
    } else {
      setStatus('Send failed: ' + (data.error || 'unknown'), true);
    }
  })
  .catch(function(e) {
    setStatus('Network error sending message', true);
  })
  .then(function() { input.disabled = false; input.focus(); });
}

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('sendBtn')?.addEventListener('click', sendMessage);
  document.getElementById('chatInput')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') sendMessage();
  });
  document.getElementById('callBtn')?.addEventListener('click', function() {
    const table = getTableNum();
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

  setStatus('Loading...');
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
      setStatus('Connected');
    })
    .catch(function() { setStatus('Could not connect to chat server', true); });
  pollMessages();
});
