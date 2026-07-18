<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
$tableNum = $_SESSION['table'] ?? 0;
$chatTable = $tableNum > 0 ? $tableNum : 0;
?>
<style>
.chat-wrap{max-width:700px;margin:0 auto}
.chat-card{background:rgba(255,255,255,0.85);backdrop-filter:blur(16px);border-radius:24px;box-shadow:0 8px 40px rgba(0,0,0,0.06),0 1px 4px rgba(0,0,0,0.04);border:1px solid rgba(255,255,255,0.5);overflow:hidden;display:flex;flex-direction:column}
.chat-header{display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #f0ebe5}
.chat-header-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#538bdf,#4138c2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;flex-shrink:0}
.chat-header-info{flex:1;min-width:0}
.chat-header-info .name{font-weight:700;color:var(--text);font-size:0.95rem}
.chat-header-info .status{font-size:0.75rem;color:#999;display:flex;align-items:center;gap:4px}
.status-dot{width:7px;height:7px;border-radius:50%;display:inline-block}
.status-dot.online{background:#2ecc71}
.status-dot.offline{background:#ccc}
.status-dot.connecting{background:#f39c12;animation:pulse 1s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
.chat-messages{height:420px;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;scroll-behavior:smooth;background:#fcfaf7}
.chat-messages::-webkit-scrollbar{width:4px}
.chat-messages::-webkit-scrollbar-thumb{background:#e0d8d0;border-radius:10px}
.chat-msg{max-width:82%;padding:10px 16px;border-radius:18px;margin-bottom:8px;font-size:0.9rem;line-height:1.45;position:relative;animation:fadeUp 0.25s ease}
.chat-msg.table{background:#538bdf;color:#fff;align-self:flex-end;border-bottom-right-radius:4px;margin-left:auto}
.chat-msg.admin{background:#fff;color:var(--text);align-self:flex-start;border-bottom-left-radius:4px;border:1px solid #ede6e0;box-shadow:0 1px 3px rgba(0,0,0,0.04)}
.chat-msg .msg-time{font-size:0.65rem;opacity:0.55;margin-top:4px;text-align:right}
.chat-msg.table .msg-time{color:rgba(255,255,255,0.7)}
.chat-msg.admin .msg-time{color:#aaa}
.chat-msg .msg-status{font-size:0.6rem;margin-left:4px;opacity:0.7}
.typing-indicator{display:none;align-items:center;gap:4px;padding:8px 0 4px 4px;align-self:flex-start}
.typing-indicator.show{display:flex}
.typing-indicator span{width:7px;height:7px;border-radius:50%;background:#c4bdb5;animation:bounce 1.4s infinite}
.typing-indicator span:nth-child(2){animation-delay:0.2s}
.typing-indicator span:nth-child(3){animation-delay:0.4s}
@keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
.chat-input-area{display:flex;gap:8px;padding:14px 20px;border-top:1px solid #f0ebe5;background:#fff}
.chat-input-area input{flex:1;padding:11px 18px;border-radius:40px;border:1.5px solid #e8e0d8;background:#f8f6f3;color:var(--text);font-size:0.9rem;outline:none;font-family:inherit;transition:border-color 0.2s,background 0.2s}
.chat-input-area input:focus{border-color:#538bdf;background:#fff}
.chat-input-area .send-btn{width:42px;height:42px;border-radius:50%;border:none;background:#538bdf;color:#fff;font-size:1.2rem;cursor:pointer;transition:transform 0.15s,background 0.15s;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.chat-input-area .send-btn:hover{transform:scale(1.08);background:#4278c8}
.chat-input-area .send-btn:active{transform:scale(0.92)}
.chat-actions{display:flex;gap:10px;margin-top:16px}
.chat-actions a,.chat-actions button{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;border-radius:40px;font-size:0.85rem;font-weight:700;border:none;cursor:pointer;text-decoration:none;transition:transform 0.15s,box-shadow 0.15s;font-family:inherit}
.chat-actions a:hover,.chat-actions button:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,0.08)}
.chat-actions .action-call{background:linear-gradient(135deg,#6c5ce7,#a29bfe);color:#fff}
.chat-actions .action-menu{background:#f0ebe5;color:var(--text)}
.chat-actions .action-kitchen{background:rgba(83,139,223,0.1);color:#538bdf}
.date-divider{text-align:center;font-size:0.7rem;color:#bbb;margin:8px 0 12px;position:relative}
.date-divider::before,.date-divider::after{content:'';position:absolute;top:50%;width:30%;height:1px;background:#ede6e0}
.date-divider::before{left:0}
.date-divider::after{right:0}
</style>
<main class="max-w-3xl mx-auto px-4 pb-10 pt-6">
  <div class="chat-wrap animate-fade-up">
    <div class="chat-card" id="chatContainer" data-table="<?= $chatTable ?>">
      <div class="chat-header">
        <div class="chat-header-avatar" data-translate>🏪</div>
        <div class="chat-header-info">
          <div class="name" data-translate>Restaurant Manager</div>
          <div class="status"><span class="status-dot offline" id="statusDot"></span><span id="chatStatus" data-translate>Initializing...</span></div>
        </div>
        <?php if ($tableNum > 0): ?>
        <div style="font-size:0.75rem;color:#aaa;background:#f5f1ec;padding:4px 12px;border-radius:100px;" data-translate>Table <?= $tableNum ?></div>
        <?php endif; ?>
      </div>
      <div class="chat-messages" id="chatMessages">
        <div class="date-divider" data-translate>Today</div>
        <div class="chat-msg admin" data-translate>👋 Welcome! How can we help you today?</div>
      </div>
      <div class="typing-indicator" id="typingIndicator"><span></span><span></span><span></span></div>
      <div class="chat-input-area">
        <input type="text" id="chatInput" placeholder="Type a message..." />
        <button class="send-btn" id="sendBtn" title="Send">➤</button>
      </div>
    </div>
    <div class="chat-actions">
      <button class="action-call" id="callBtn" data-translate>📞 Call Restaurant</button>
      <a href="/menu.php" class="action-menu" data-translate>📋 Menu</a>
      <a href="/kitchen.php" class="action-kitchen" data-translate>📺 Kitchen</a>
    </div>
  </div>
</main>
<script src="/assets/talk.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
