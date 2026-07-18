<?php require_once __DIR__ . '/includes/header.php'; ?>
<main class="max-w-3xl mx-auto px-4 pb-24">
  <div class="text-center mb-6 pt-4">
    <h1 class="text-4xl font-bold" style="color:var(--text);">🍷 <span style="color:#538bdf;" data-translate>Food</span> <span style="color:#f68e9a;" data-translate>Sommelier</span></h1>
    <p class="mt-2" style="color: var(--text-muted);" data-translate>Tell me what you're in the mood for, and I'll recommend the perfect dish</p>
  </div>

  <div class="glass-card p-5 mb-4">
    <div class="flex gap-3 flex-wrap justify-center mb-4">
      <button class="mood-btn" data-mood="craving something spicy and bold" data-translate>🌶️ Spicy</button>
      <button class="mood-btn" data-mood="want something light and healthy" data-translate>🥗 Healthy</button>
      <button class="mood-btn" data-mood="in the mood for something sweet" data-translate>🍰 Sweet</button>
      <button class="mood-btn" data-mood="want a hearty filling meal" data-translate>🍛 Hearty</button>
      <button class="mood-btn" data-mood="feeling adventurous, want to try something new" data-translate>🎲 Adventurous</button>
      <button class="mood-btn" data-mood="want something quick and light to eat" data-translate>⚡ Quick Bite</button>
    </div>
    <div class="flex gap-2">
      <input type="text" id="moodInput" placeholder="Describe your craving... e.g., 'I want something creamy and rich with mushrooms'" class="flex-1 px-4 py-3 rounded-2xl border-2 border-gray-700 bg-white/5 outline-none focus:border-[#538bdf] transition text-sm">
      <button id="recommendBtn" class="px-5 py-3 rounded-2xl font-bold text-white text-sm" style="background:#538bdf;border:none;cursor:pointer;white-space:nowrap;" data-translate>🍷 Suggest</button>
    </div>
    <div id="moodError" class="text-sm mt-2" style="color:#f68e9a;display:none;"></div>
  </div>

  <div id="sommelierResults" style="display:none;">
    <div id="recommendationSummary" class="glass-card p-4 mb-4"></div>
    <h3 class="font-bold text-lg mb-3" style="color:var(--text);" data-translate>🍽️ Recommended Dishes</h3>
    <div id="dishResults" class="space-y-2"></div>
  </div>

  <div id="sommelierLoading" class="text-center py-8" style="display:none;">
    <div class="w-12 h-12 border-4 border-gray-700 border-t-[#538bdf] rounded-full animate-spin mx-auto mb-3"></div>
    <div class="font-bold text-lg" data-translate>🍷 Consulting the sommelier...</div>
    <p style="color: var(--text-muted);font-size:0.85rem;" data-translate>Analyzing the menu and your mood</p>
  </div>

  <div class="flex gap-3 justify-center mt-6 flex-wrap">
    <a href="/menu.php" class="btn-bouncy px-5 py-2.5 no-underline text-sm" style="background:#f68e9a;color:#fff;" data-translate>📋 Full Menu</a>
    <a href="/akinator.php" class="btn-bouncy btn-outline px-5 py-2.5 no-underline text-sm" data-translate>🤖 AI Waiter</a>
    <a href="/" class="btn-bouncy btn-outline px-5 py-2.5 no-underline text-sm" data-translate>🏠 Home</a>
  </div>
</main>

<style>
  .mood-btn{padding:8px 18px;border-radius:40px;font-size:0.8rem;font-weight:600;border:2px solid #e0dcd5;background: var(--bg-card);cursor:pointer;transition:all 0.2s;font-family:inherit;color:var(--text)}
  .mood-btn:hover{border-color:#538bdf;color:#538bdf;transform:translateY(-2px)}
  .mood-btn.active{background:#538bdf;color:#fff;border-color:#538bdf}
  .dish-rec-card{display:flex;gap:12px;background: var(--bg-card);backdrop-filter:blur(8px);border-radius:16px;padding:12px;border: 1px solid var(--border);transition:transform 0.2s;cursor:pointer}
  .dish-rec-card:hover{transform:translateY(-2px)}
  .dish-rec-img{width:64px;height:64px;border-radius:12px;overflow:hidden;background: rgba(255,255,255,0.03);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
  .dish-rec-img img{width:100%;height:100%;object-fit:cover}
</style>

<script>
document.querySelectorAll('.mood-btn').forEach(function(b){
  b.addEventListener('click',function(){
    document.querySelectorAll('.mood-btn').forEach(function(x){x.classList.remove('active');});
    this.classList.add('active');
    document.getElementById('moodInput').value=this.dataset.mood;
  });
});
document.getElementById('moodInput').addEventListener('keydown',function(e){if(e.key==='Enter')askSommelier();});
document.getElementById('recommendBtn').addEventListener('click',askSommelier);

function askSommelier(){
  var mood=document.getElementById('moodInput').value.trim();
  if(!mood){document.getElementById('moodError').textContent='Please describe your mood or craving!';document.getElementById('moodError').style.display='block';return;}
  document.getElementById('moodError').style.display='none';
  document.getElementById('sommelierLoading').style.display='block';
  document.getElementById('sommelierResults').style.display='none';
  document.getElementById('recommendBtn').disabled=true;
  document.getElementById('recommendBtn').textContent='⏳';

  fetch('/menu.json?t='+Date.now()).then(function(r){return r.json()}).then(function(menuData){
    var dishes=[];
    (menuData.menu.categories||[]).forEach(function(c){
      (c.items||[]).forEach(function(d){dishes.push({name:d.name,price:d.price,desc:d.description||'',cat:c.name,spice:d.spice_level||'mild',veg:d.is_vegetarian,img:d.image||''});});
    });
    var prompt='You are a Food Sommelier. The customer says: "'+mood+'". From this menu, recommend 3-5 dishes that match their mood. For each dish, explain WHY it fits. Return ONLY a JSON array: [{name:"Dish Name",reason:"Why this fits...",price:299}]. Menu: '+JSON.stringify(dishes);
    return fetch('/api/proxy.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({messages:[{role:'user',content:prompt}],max_tokens:2048,temperature:0.7})});
  }).then(function(r){return r.json()}).then(function(data){
    document.getElementById('sommelierLoading').style.display='none';
    document.getElementById('recommendBtn').disabled=false;
    document.getElementById('recommendBtn').textContent='🍷 Suggest';
    if(!data.success||!data.content){document.getElementById('moodError').textContent='Sorry, the sommelier is busy. Try again!';document.getElementById('moodError').style.display='block';return;}
    var json=data.content.replace(/```json\s*/g,'').replace(/\s*```/g,'');
    var recs;
    try{recs=JSON.parse(json);}catch(e){recs=JSON.parse('['+json.replace(/^\[/,'').replace(/\]$/,'')+']');}
    if(!Array.isArray(recs)||!recs.length){recs=[{name:data.content.split('\n')[0],reason:data.content,price:0}];}
    document.getElementById('sommelierResults').style.display='block';
    var summary='<div class="font-bold text-lg mb-2" data-translate>🍷 Sommelier\'s Recommendation</div><p style="color: var(--text-muted);">Based on your mood: <em data-translate>"'+escapeHtml(mood)+'"</em></p>';
    document.getElementById('recommendationSummary').innerHTML=summary;
    var html='';
    recs.forEach(function(r){
      var name=r.name||'Recommended Dish';
      var reason=r.reason||'';
      var price=r.price||'';
      html+='<div class="dish-rec-card" onclick="addToCartFromSommelier(\''+escapeHtml(name)+'\','+price+')"><div class="dish-rec-img" data-translate>🍽️</div><div class="flex-1"><div class="font-semibold">'+escapeHtml(name)+(price?' <span class="font-bold" style="color:#f68e9a;" data-translate>₹'+price+'</span>':'')+'</div><div class="text-sm mt-1" style="color: var(--text-muted);" data-translate>'+escapeHtml(reason)+'</div><div class="text-xs mt-1" style="color:#538bdf;font-weight:600;" data-translate>➕ Add to Cart</div></div></div>';
    });
    document.getElementById('dishResults').innerHTML=html;
    if(window.retranslate)window.retranslate();
  }).catch(function(){
    document.getElementById('sommelierLoading').style.display='none';
    document.getElementById('recommendBtn').disabled=false;
    document.getElementById('recommendBtn').textContent='🍷 Suggest';
    document.getElementById('moodError').textContent='Network error. Please try again.';document.getElementById('moodError').style.display='block';
  });
}

function addToCartFromSommelier(name,price){
  if(typeof window.addToCart==='function'){
    window.addToCart({id:'sommelier_'+Date.now(),name:name,price:price,image:'',description:''});
    if(window.showToast)window.showToast('Added to cart!','success');
  }
}
function escapeHtml(s){if(!s)return '';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
