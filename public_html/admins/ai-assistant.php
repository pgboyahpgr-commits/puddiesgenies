<?php
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/menu-loader.php';

$orders = loadJSON(__DIR__ . '/../data/orders.json');
$menuData = loadMenuFromGist();
$allDishes = $menuData ? getAllDishes($menuData) : [];
$streamConfig = loadJSON(__DIR__ . '/../data/stream_config.json');
$tables = loadJSON(__DIR__ . '/../data/tables.json');

$totalRevenue = 0;
foreach ($orders as $o) { $totalRevenue += intval($o['total'] ?? 0); }

$platformInfo = [
  'orderCount' => count($orders),
  'dishCount' => count($allDishes),
  'totalRevenue' => $totalRevenue,
  'features' => [
    'Menu Management' => 'Add/edit/delete dishes via admin panel',
    'Table Ordering' => 'Customers scan QR, browse menu, order online',
    'Live Kitchen Stream' => 'Stream kitchen to customers via YouTube embed',
    'Call Waiter' => 'Customers can call waiter from table via chat',
    'AI Akinator' => 'AI guesses customer dish preference via questions',
    'Multi-language' => 'Translate site into 16 languages',
    'Voice Ordering' => 'Customers can order by voice',
  ],
];
?><style>
.chat-container{max-width:800px;margin:0 auto}
.msg{max-width:85%;padding:12px 18px;border-radius:20px;font-size:14px;line-height:1.5;animation:fadeUp 0.3s ease;margin-bottom:10px}
.msg.user{background:rgba(83,139,223,0.1);color:#3A7A7C;align-self:flex-end;border-bottom-right-radius:4px;margin-left:auto}
.msg.bot{background:#EDE6E0;color:#2D2D2D;align-self:flex-start;border-bottom-left-radius:4px;margin-right:auto}
.msg .time{font-size:10px;opacity:0.4;margin-top:4px;color:#6B6B6B}
.msg.user .time{text-align:right}
.chat-box{height:55vh;overflow-y:auto;padding:12px 4px;display:flex;flex-direction:column;scroll-behavior:smooth}
.chat-box::-webkit-scrollbar{width:3px}
.chat-box::-webkit-scrollbar-thumb{background:#DEDDD8;border-radius:10px}
.input-row{display:flex;gap:8px;padding:10px 0}
.input-row input{flex:1;padding:12px 18px;border-radius:40px;border:1.5px solid #DEDDD8;background:#F6F4F0;color:#1A1A1A;font-size:14px;outline:none;font-family:inherit;transition:border-color 0.2s}
.input-row input:focus{border-color:#538bdf;background:#fff}
.input-row button{padding:12px 22px;border-radius:40px;border:none;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;background:rgba(83,139,223,0.12);color:#538bdf;border:1px solid rgba(83,139,223,0.1);transition:.2s}
.input-row button:hover{background:rgba(83,139,223,0.2)}
.suggestions{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
.suggestions button{padding:6px 16px;border-radius:40px;font-size:12px;border:1px solid #EDE6E0;background:#fff;color:#6B6B6B;cursor:pointer;transition:.2s;font-family:inherit;font-weight:600}
.suggestions button:hover{background:rgba(246,142,154,0.08);color:#f68e9a;border-color:rgba(246,142,154,0.2)}
.typing{display:flex;gap:4px;padding:8px 0}
.typing span{width:6px;height:6px;border-radius:50%;background:#DEDDD8;animation:bounce 1.4s infinite}
.typing span:nth-child(2){animation-delay:0.2s}.typing span:nth-child(3){animation-delay:0.4s}
@keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
</style>
<main class="max-w-5xl mx-auto px-4 pb-10 pt-6">
  <div style="text-align:center;margin-bottom:24px;" class="animate-fade-up">
    <h1 class="text-3xl font-bold" style="font-family:'Fredoka',sans-serif;color:#1A1A1A;" data-translate>🤖 AI Assistant</h1>
    <p style="color:#9B9B9B;font-size:14px;margin:4px 0 0;" data-translate>Ask about SmakAI features, setup, or management</p>
  </div>
  <div class="card chat-container animate-fade-up" style="padding:20px;">
    <div class="chat-box" id="chatBox">
      <div class="msg bot">👋 Hi! I'm your SmakAI assistant. Ask me anything!<div class="time" data-translate>just now</div></div>
    </div>
    <div class="suggestions" id="suggestions">
      <button onclick="ask('How do I add a new menu item?')" data-translate>➕ Add menu item</button>
      <button onclick="ask('How does table ordering work?')" data-translate>🪑 Table ordering</button>
      <button onclick="ask('How to set up the live kitchen stream?')" data-translate>📺 Kitchen stream</button>
      <button onclick="ask('How do customers call the waiter?')" data-translate>📞 Call waiter</button>
      <button onclick="ask('What is the AI Akinator feature?')" data-translate>🤖 AI Akinator</button>
    </div>
    <div class="input-row">
      <input type="text" id="msgInput" placeholder="Ask about SmakAI..." />
      <button id="sendBtn" data-translate>Send</button>
    </div>
  </div>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:20px;" class="animate-fade-up">
    <a href="/admins/dashboard.php" class="action-pill pill-teal" data-translate>⬅ Dashboard</a>
    <a href="/admins/menu.php" class="action-pill pill-coral" data-translate>📋 Menu</a>
    <a href="/admins/orders.php" class="action-pill pill-teal" data-translate>📦 Orders</a>
  </div>
</main>
<script>
const chatBox=document.getElementById('chatBox'),msgInput=document.getElementById('msgInput'),sendBtn=document.getElementById('sendBtn');
function addMsg(text,role){const d=document.createElement('div');d.className='msg '+role;const t=document.createElement('div');t.className='time';t.textContent=new Date().toLocaleTimeString();const s=document.createElement('span');if(role==='bot')s.innerHTML=text;else s.textContent=text;d.appendChild(s);d.appendChild(t);chatBox.appendChild(d);chatBox.scrollTop=chatBox.scrollHeight;}
function showTyping(){const d=document.createElement('div');d.className='typing';d.id='typingIndicator';d.innerHTML='<span></span><span></span><span></span>';chatBox.appendChild(d);chatBox.scrollTop=chatBox.scrollHeight;}
function hideTyping(){const e=document.getElementById('typingIndicator');if(e)e.remove();}
const platformInfo=<?=json_encode($platformInfo)?>;
const features=platformInfo.features;
const systemPrompt='You are SmakAI Bot. Answer concisely. Platform info:\n- Orders: '+platformInfo.orderCount+'\n- Dishes: '+platformInfo.dishCount+'\n- Revenue: ₹'+platformInfo.totalRevenue+'\nFeatures:\n'+Object.entries(features).map(function(kv){return '- '+kv[0]+': '+kv[1];}).join('\n')+'\n\nAdmin pages: dashboard, menu, orders, todays-special, tables, stream, events, talk, settings, ai-assistant, sync-menu';
var messages=[{role:'system',content:systemPrompt}];
async function ask(text){if(!text||!text.trim())return;msgInput.value='';addMsg(escapeHtml(text.trim()),'user');messages.push({role:'user',content:text.trim()});if(messages.length>21)messages=[messages[0]].concat(messages.slice(-20));showTyping();sendBtn.disabled=true;try{var r=await fetch('/api/proxy.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({messages:messages})});var d=await r.json();hideTyping();if(d.success&&d.content){addMsg(d.content.replace(/\n/g,'<br>'),'bot');messages.push({role:'assistant',content:d.content});}else addMsg('⚠️ Error. Try again.','bot');}catch(e){hideTyping();addMsg('⚠️ Network error.','bot');}sendBtn.disabled=false;}
sendBtn.addEventListener('click',function(){ask(msgInput.value);});msgInput.addEventListener('keydown',function(e){if(e.key==='Enter')ask(msgInput.value);});
function escapeHtml(s){if(!s)return '';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
</script>
</div>
</body>
</html>
