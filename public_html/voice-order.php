<?php require_once __DIR__ . '/includes/header.php'; ?>
<main class="max-w-3xl mx-auto px-4 pb-24">
  <div class="text-center mb-6 pt-4">
    <h1 class="text-4xl font-bold" style="color:var(--text);">🎤 <span style="color:#f7b6bf;" data-translate>Voice</span> Ordering</h1>
    <p class="mt-2" style="color: var(--text-muted);" data-translate>Just speak what you want — AI will find it on the menu</p>
  </div>

  <div class="glass-card p-6 mb-4 text-center">
    <div id="voiceStatus" class="mb-4">
      <div class="text-6xl mb-3" id="micIcon" data-translate>🎤</div>
      <div class="font-bold text-lg mb-1" style="color:var(--text);" data-translate>Tap to start speaking</div>
      <p style="color: var(--text-muted);font-size:0.85rem;" data-translate>Say something like "I want butter chicken and naan" or "two coffees please"</p>
    </div>

    <button id="voiceBtn" class="w-20 h-20 rounded-full text-3xl mx-auto mb-3" style="background:#f7b6bf;color:#fff;border:none;cursor:pointer;transition:all 0.3s;display:flex;align-items:center;justify-content:center;" data-translate>
      🎤
    </button>
    <div id="voiceTranscript" class="text-sm mb-3" style="color: var(--text-muted);min-height:20px;" data-translate>—</div>

    <div id="voiceResults" style="display:none;">
      <div class="border-t border-gray-700 pt-4 mt-2">
        <h3 class="font-bold mb-3" style="color:var(--text);" data-translate>🍽️ Matching Dishes</h3>
        <div id="voiceDishList" class="space-y-2 text-left"></div>
      </div>
      <div id="voiceAIHelp" style="display:none;" class="mt-3 p-3 rounded-xl text-sm text-left" style="background:rgba(108,92,231,0.08);">
        <div class="font-semibold mb-1" style="color:#538bdf;" data-translate>🤖 AI Suggestion</div>
        <div id="voiceAIText" style="color: var(--text-muted);"></div>
      </div>
    </div>
  </div>

  <div class="flex gap-3 justify-center flex-wrap">
    <a href="/menu.php" class="btn-bouncy px-5 py-2.5 no-underline text-sm" style="background:#f68e9a;color:#fff;" data-translate>📋 Menu</a>
    <a href="/sommelier.php" class="btn-bouncy btn-outline px-5 py-2.5 no-underline text-sm" data-translate>🍷 Sommelier</a>
    <a href="/checkout.php" class="btn-bouncy btn-secondary px-5 py-2.5 no-underline text-sm" data-translate>🛒 Cart</a>
    <a href="/" class="btn-bouncy btn-outline px-5 py-2.5 no-underline text-sm" data-translate>🏠 Home</a>
  </div>
</main>

<style>
  .voice-dish-item{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background: var(--bg-card);border-radius:12px;border: 1px solid var(--border);}
  .voice-dish-item .add-v{width:32px;height:32px;border-radius:50%;background:#f7b6bf;color:#fff;border:none;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform 0.2s}
  .voice-dish-item .add-v:hover{transform:scale(1.15)}
  .listening #micIcon{animation:micPulse 1s infinite}
  @keyframes micPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.15)}}
  .listening #voiceBtn{background:#f68e9a!important;animation:micPulse 1s infinite}
</style>

<script>
var recognition=null;
var voiceBtn=document.getElementById('voiceBtn');
var voiceStatus=document.getElementById('voiceStatus');

if(!('webkitSpeechRecognition' in window||'SpeechRecognition' in window)){
  voiceStatus.innerHTML='<div class="text-5xl mb-3" data-translate>😕</div><div class="font-bold text-lg mb-1" style="color:var(--text);" data-translate>Voice not supported</div><p style="color: var(--text-muted);font-size:0.85rem;" data-translate>Your browser doesn\'t support voice recognition. Try Chrome or Edge.</p>';
  voiceBtn.style.display='none';
} else {
  var SpeechRecognition=window.SpeechRecognition||window.webkitSpeechRecognition;
  recognition=new SpeechRecognition();
  recognition.lang='en-IN';recognition.continuous=false;recognition.interimResults=true;

  voiceBtn.addEventListener('click',function(){
    if(voiceBtn.dataset.listening==='true'){
      recognition.stop();voiceBtn.dataset.listening='false';voiceBtn.style.background='#f7b6bf';
      voiceBtn.parentElement.classList.remove('listening');return;
    }
    voiceBtn.dataset.listening='true';voiceBtn.style.background='#f68e9a';
    voiceBtn.parentElement.classList.add('listening');
    document.getElementById('voiceTranscript').textContent='Listening...';
    recognition.start();
  });

  recognition.onresult=function(e){
    var transcript='';
    for(var i=e.resultIndex;i<e.results.length;i++){transcript+=e.results[i][0].transcript;}
    document.getElementById('voiceTranscript').textContent='"'+transcript+'"';
    if(e.results[0].isFinal){
      voiceBtn.dataset.listening='false';voiceBtn.style.background='#f7b6bf';
      voiceBtn.parentElement.classList.remove('listening');
      searchVoiceOrder(transcript);
    }
  };
  recognition.onerror=function(){
    voiceBtn.dataset.listening='false';voiceBtn.style.background='#f7b6bf';
    voiceBtn.parentElement.classList.remove('listening');
    document.getElementById('voiceTranscript').textContent='Could not recognize. Try again.';
  };
}

function searchVoiceOrder(text){
  var q=text.toLowerCase();
  fetch('/menu.json?t='+Date.now()).then(function(r){return r.json()}).then(function(menuData){
    var allDishes=[];
    (menuData.menu.categories||[]).forEach(function(c){(c.items||[]).forEach(function(d){d.catName=c.name;allDishes.push(d);});});
    var matches=allDishes.filter(function(d){
      var n=d.name.toLowerCase();var desc=(d.description||'').toLowerCase();
      return n.includes(q)||desc.includes(q)||q.split(' ').some(function(w){return w.length>2&&(n.includes(w)||desc.includes(w));});
    });
    document.getElementById('voiceResults').style.display='block';
    var list=document.getElementById('voiceDishList');
    if(matches.length){
      var html='';
      matches.slice(0,8).forEach(function(d){
        html+='<div class="voice-dish-item"><div><div class="font-semibold text-sm" data-translate>'+escapeHtml(d.name)+'</div><div class="text-xs" style="color: var(--text-muted);" data-translate>₹'+d.price+' · '+escapeHtml(d.catName)+'</div></div><button class="add-v" onclick="addVoiceItem(\''+escapeHtml(d.name)+'\','+d.price+')" data-translate>+</button></div>';
      });
      list.innerHTML=html;
    } else {
      list.innerHTML='<div class="text-center py-4" style="color: var(--text-muted);"><p data-translate>No direct matches for "'+escapeHtml(text)+'"</p><p class="text-xs mt-1" data-translate>Let AI find the closest dish...</p></div>';
      voiceAI(text,allDishes);
    }
    if(window.retranslate)window.retranslate();
  }).catch(function(){});
}

function voiceAI(text,allDishes){
  document.getElementById('voiceAIHelp').style.display='block';
  document.getElementById('voiceAIText').textContent='Analyzing your request...';
  var prompt='Customer said: "'+text+'". Find the best matching dish(es) from this menu. Return only JSON array: [{name:"Dish Name",price:299,reason:"why it fits"}]. If no close match, suggest the closest option. Menu: '+JSON.stringify(allDishes.map(function(d){return{name:d.name,price:d.price,desc:d.description||'',cat:d.catName};}).slice(0,50));
  fetch('/api/proxy.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({messages:[{role:'user',content:prompt}],max_tokens:1024,temperature:0.5})})
  .then(function(r){return r.json()}).then(function(data){
    if(data.success&&data.content){
      document.getElementById('voiceAIText').innerHTML=data.content.replace(/\n/g,'<br>');
    }
  }).catch(function(){});
}

function addVoiceItem(name,price){
  if(typeof window.addToCart==='function'){
    window.addToCart({id:'voice_'+Date.now(),name:name,price:price,image:'',description:''});
    if(window.showToast)window.showToast('Added '+name+' to cart!','success');
  }
}
function escapeHtml(s){if(!s)return '';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
