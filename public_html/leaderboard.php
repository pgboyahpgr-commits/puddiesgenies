<?php require_once __DIR__ . '/includes/header.php'; ?>
<main class="max-w-3xl mx-auto px-4 pb-24">
  <div class="text-center mb-8 pt-4">
    <h1 class="text-4xl font-bold" style="color:var(--text);">🏆 <span style="color:#f68e9a;" data-translate>Foodie</span> Leaderboard</h1>
    <p class="mt-2" style="color: var(--text-muted);" data-translate>Earn points with every order. Climb the ranks!</p>
  </div>

  <div id="profileCard" class="glass-card p-4 mb-6" style="display:none;">
    <div class="flex items-center gap-4">
      <div class="text-4xl" id="myAvatar" data-translate>🏅</div>
      <div class="flex-1">
        <div class="font-bold text-lg" id="myName" data-translate>—</div>
        <div class="flex gap-3 mt-1">
          <span class="text-sm" style="color: var(--text-muted);">⭐ <span id="myPoints">0</span> points</span>
          <span class="text-sm" style="color: var(--text-muted);">🛒 <span id="myOrders">0</span> orders</span>
        </div>
        <div id="myBadges" class="flex gap-1 mt-1 flex-wrap"></div>
      </div>
    </div>
  </div>

  <div id="rankings" class="space-y-2">
    <div class="text-center py-8" id="loadingState">
      <div class="w-10 h-10 border-4 border-gray-700 border-t-[#f68e9a] rounded-full animate-spin mx-auto mb-3"></div>
      <p style="color: var(--text-muted);" data-translate>Loading leaderboard...</p>
    </div>
  </div>

  <div class="flex gap-3 justify-center mt-6 flex-wrap">
    <a href="/menu.php" class="btn-bouncy px-5 py-2.5 no-underline text-sm" style="background:#f68e9a;color:#fff;" data-translate>📋 Order Now</a>
    <a href="/akinator.php" class="btn-bouncy btn-outline px-5 py-2.5 no-underline text-sm" data-translate>🤖 AI Waiter</a>
    <a href="/" class="btn-bouncy btn-outline px-5 py-2.5 no-underline text-sm" data-translate>🏠 Home</a>
  </div>
</main>

<style>
  .rank-item{display:flex;align-items:center;gap:12px;background: var(--bg-card);backdrop-filter:blur(8px);border-radius:16px;padding:12px 16px;border: 1px solid var(--border);transition:transform 0.2s,box-shadow 0.2s}
  .rank-item:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.06)}
  .rank-number{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0}
  .rank-1{background:linear-gradient(135deg,#FFD700,#FFA500);color:#fff;font-size:1.1rem}
  .rank-2{background:linear-gradient(135deg,#C0C0C0,#A8A8A8);color:#fff;font-size:1rem}
  .rank-3{background:linear-gradient(135deg,#CD7F32,#A0522D);color:#fff;font-size:0.95rem}
  .badge-pill{font-size:0.65rem;padding:2px 8px;border-radius:40px;font-weight:600}
  .badge-pill.first_order{background:#f7b6bf;color:#fff}
  .badge-pill.regular{background:#4138c2;color:var(--text)}
  .badge-pill.vip{background:#538bdf;color:#fff}
  .badge-pill.legend{background:#f68e9a;color:#fff}
  .badge-pill.big_spender{background:#f5b06b;color:#fff}
</style>

<script>
const badgeLabels = { first_order: '🌟 First Order', regular: '⭐ Regular', vip: '👑 VIP', legend: '🏆 Legend', big_spender: '💰 Big Spender' };

fetch('/api/leaderboard.php').then(function(r){return r.json()}).then(function(data){
  document.getElementById('loadingState').style.display='none';
  var board = data.leaderboard || [];
  var container = document.getElementById('rankings');
  if (board.length===0){container.innerHTML='<div class="text-center py-12"><div class="text-5xl mb-3" data-translate>🍽️</div><p class="font-bold text-lg" data-translate>No foodies yet</p><p style="color: var(--text-muted);" data-translate>Be the first to order and earn points!</p></div>';return;}
  var html='';
  for(var i=0;i<board.length;i++){
    var u=board[i];var rank=i+1;var cls='rank-number '+(rank===1?'rank-1':rank===2?'rank-2':rank===3?'rank-3':'');
    var medal=rank===1?'🥇':rank===2?'🥈':rank===3?'🥉':'#'+rank;
    html+='<div class="rank-item"><div class="'+cls+'" data-translate>'+medal+'</div><div class="flex-1"><div class="font-semibold" data-translate>'+escapeHtml(u.name)+'</div><div class="flex gap-2 mt-0.5"><span style="color: var(--text-muted);font-size:0.8rem;" data-translate>⭐ '+u.points+' pts</span><span style="color: var(--text-muted);font-size:0.8rem;" data-translate>🛒 '+u.orders+' orders</span></div></div><div class="flex gap-1 flex-wrap">'+(u.badges||[]).map(function(b){var label=badgeLabels[b]||b;return '<span class="badge-pill '+b+'" data-translate>'+label+'</span>';}).join('')+'</div></div>';
  }
  container.innerHTML=html;
  if(window.retranslate)window.retranslate();
}).catch(function(){document.getElementById('loadingState').innerHTML='<p style="color: var(--text-muted);" data-translate>Could not load leaderboard</p>';if(window.retranslate)window.retranslate();});

// Show user's profile
var did = window.DEVICE_ID;
if(did){
  fetch('/api/user-profile.php?device_id='+encodeURIComponent(did)).then(function(r){return r.json()}).then(function(d){
    if(d.success&&d.profile){
      var p=d.profile;document.getElementById('profileCard').style.display='block';document.getElementById('myName').textContent=p.name||'Guest';
      document.getElementById('myPoints').textContent=p.points||0;document.getElementById('myOrders').textContent=p.orders||0;
      var badgesEl=document.getElementById('myBadges');badgesEl.innerHTML='';
      (p.badges||[]).forEach(function(b){var el=document.createElement('span');el.className='badge-pill '+b;el.textContent=badgeLabels[b]||b;badgesEl.appendChild(el);});
    }
  }).catch(function(){});
}

function escapeHtml(s){if(!s)return '';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
