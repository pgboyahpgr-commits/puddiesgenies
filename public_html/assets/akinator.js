var allDishes = [];
var probabilities = [];
var askedKeys = new Set();
var currentQ = 0;
var wrongIndex = -1;
var gameOver = false;
var currentMode = 'guess';
var guessConversation = [];

// ─── Menu Data Loader ───────────────────────────────────────────
function loadAllDishes() {
  if (window.__AKINATOR_DISHES && window.__AKINATOR_DISHES.length > 0) {
    allDishes = window.__AKINATOR_DISHES;
    SmakAI.__allDishes = allDishes;
    return Promise.resolve(allDishes);
  }

  // Try loading cached in sessionStorage
  var cached = sessionStorage.getItem('smak_menu_cache');
  if (cached) {
    try {
      var parsed = JSON.parse(cached);
      allDishes = extractDishes(parsed);
      SmakAI.__allDishes = allDishes;
      return Promise.resolve(allDishes);
    } catch(e) {}
  }

  // Fetch from API
  return fetch('/api/menu.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var extracted = extractDishes(data);
      allDishes = extracted;
      SmakAI.__allDishes = extracted;
      try { sessionStorage.setItem('smak_menu_cache', JSON.stringify(data)); } catch(e) {}
      return extracted;
    })
    .catch(function() {
      allDishes = [];
      SmakAI.__allDishes = [];
      return [];
    });
}

function extractDishes(menuData) {
  var dishes = [];
  for (var ci = 0; ci < (menuData.menu?.categories || []).length; ci++) {
    var cat = menuData.menu.categories[ci];
    for (var ii = 0; ii < (cat.items || []).length; ii++) {
      var item = cat.items[ii];
      dishes.push({
        name: item.name || '',
        category_id: cat.id || '',
        category_name: cat.name || '',
        is_vegetarian: !!item.is_vegetarian,
        spice_level: item.spice_level || 'mild',
        price: parseInt(item.price) || 0,
        region: item.region || 'North India',
        popular: !!item.popular,
        description: item.description || '',
        image: item.image || ''
      });
    }
  }
  return dishes;
}

// ─── Init ───────────────────────────────────────────────────────
function initAkinator() {
  return loadAllDishes();
}

// ─── Mode Switch ────────────────────────────────────────────────
function switchMode(mode) {
  currentMode = mode;
  document.getElementById('guessMode').style.display = mode === 'guess' ? 'block' : 'none';
  document.getElementById('chatMode').style.display = mode === 'chat' ? 'block' : 'none';
  var guessBtn = document.getElementById('modeGuess');
  var chatBtn = document.getElementById('modeChat');
  if (mode === 'guess') {
    guessBtn.style.background = '#FF6B6B'; guessBtn.style.color = '#fff';
    chatBtn.style.background = 'rgba(255,255,255,0.6)'; chatBtn.style.border = '1px solid #ddd'; chatBtn.style.color = '#2D3436';
    startNewGame();
  } else {
    chatBtn.style.background = '#FF6B6B'; chatBtn.style.color = '#fff';
    guessBtn.style.background = 'rgba(255,255,255,0.6)'; guessBtn.style.border = '1px solid #ddd'; guessBtn.style.color = '#2D3436';
  }
}

// ─── Akinator Game ──────────────────────────────────────────────
function startNewGame() {
  if (!allDishes.length) { initAkinator().then(startNewGame); return; }
  probabilities = allDishes.map(function() { return 1 / allDishes.length; });
  askedKeys = new Set();
  currentQ = 0;
  wrongIndex = -1;
  gameOver = false;
  guessConversation = [];
  var guessBox = document.getElementById('guessBox');
  if (guessBox) guessBox.style.display = 'none';
  var gameArea = document.getElementById('gameArea');
  if (gameArea) {
    var inputs = gameArea.querySelectorAll('button, input');
    for (var i = 0; i < inputs.length; i++) inputs[i].disabled = false;
  }
  var customAnswer = document.getElementById('customAnswer');
  if (customAnswer) customAnswer.value = '';
  var qText = document.getElementById('questionText');
  if (qText) qText.textContent = 'Think of a dish from our menu...';
  var sText = document.getElementById('statusText');
  if (sText) sText.textContent = 'Ready to play!';
  var cList = document.getElementById('candidateList');
  if (cList) cList.innerHTML = '';
  askNext();
}

function askNext() {
  if (gameOver) return;
  var available = SmakAI.QUESTIONS_FALLBACK.filter(function(q) { return !askedKeys.has(q.key); });
  if (available.length === 0 || currentQ >= 12) { makeGuess(); return; }

  var scored = available.map(function(q) {
    var yesCount = 0, total = 0;
    for (var i = 0; i < allDishes.length; i++) {
      if (probabilities[i] < 0.01) continue;
      total++;
      if (q.map(allDishes[i])) yesCount++;
    }
    if (total === 0) return { q: q, score: 0 };
    var yesRatio = yesCount / total;
    return { q: q, score: 1 - Math.abs(yesRatio - 0.5) * 2 };
  });

  scored.sort(function(a, b) { return b.score - a.score; });
  var best = scored[0].q;
  askedKeys.add(best.key);

  var qText = document.getElementById('questionText');
  if (qText) qText.textContent = best.icon + ' ' + best.q;
  var sText = document.getElementById('statusText');
  if (sText) sText.textContent = 'Question ' + (currentQ + 1) + ' of 12';
  updateCandidates();
  window.__currentQuestion = best;
}

function handleAnswer(answer) {
  if (gameOver) return;
  var q = window.__currentQuestion;
  if (!q) return;
  var yes = answer === 'yes' ? 1 : answer === 'no' ? 0 : 0.5;
  if (wrongIndex >= 0) { probabilities[wrongIndex] = 0; wrongIndex = -1; }

  var newProbs = probabilities.map(function(p, i) {
    var matches = q.map(allDishes[i]);
    return p * Math.max(matches ? yes : (1 - yes), 0.05);
  });
  var sum = newProbs.reduce(function(a, b) { return a + b; }, 0);
  probabilities = sum > 0 ? newProbs.map(function(p) { return p / sum; }) : allDishes.map(function() { return 1 / allDishes.length; });

  currentQ++;
  var maxP = Math.max.apply(null, probabilities);
  if (maxP >= 0.75 || currentQ >= 12) { makeGuess(); } else { askNext(); }
  updateCandidates();
}

function makeGuess() {
  var maxP = Math.max.apply(null, probabilities);
  if (maxP <= 0 || isNaN(maxP)) { startNewGame(); return; }
  var idx = probabilities.indexOf(maxP);
  var dish = allDishes[idx];
  if (!dish) return;

  gameOver = true;
  wrongIndex = idx;

  document.getElementById('guessText').textContent = '🍛 ' + dish.name + '!';
  document.getElementById('guessConfidence').textContent = "I'm " + Math.round(maxP * 100) + '% sure this is what you want!';
  document.getElementById('guessDesc').textContent = dish.description || (dish.category_name || '') + ' — ₹' + (dish.price || '?');
  document.getElementById('guessBox').style.display = 'block';
  document.getElementById('questionText').textContent = '🎉 I guessed it!';

  var gameArea = document.getElementById('gameArea');
  if (gameArea) {
    var inputs = gameArea.querySelectorAll('button, input');
    for (var i = 0; i < inputs.length; i++) inputs[i].disabled = true;
  }

  if (dish.image) {
    document.getElementById('guessImageBox').innerHTML = '<img src="' + dish.image + '" alt="' + dish.name + '" class="w-full h-full object-cover" />';
  } else {
    SmakAI.fetchDishImage(dish.name).then(function(url) {
      if (url) {
        document.getElementById('guessImageBox').innerHTML = '<img src="' + url + '" alt="' + dish.name + '" class="w-full h-full object-cover" />';
      }
    });
  }
  document.getElementById('guessMenuLink').href = '/menu.php?search=' + encodeURIComponent(dish.name);
}

function updateCandidates() {
  var list = document.getElementById('candidateList');
  if (!list) return;
  var items = probabilities.map(function(p, i) { return { p: p, dish: allDishes[i] }; });
  items.sort(function(a, b) { return b.p - a.p; });
  items = items.slice(0, 5);
  list.innerHTML = items.map(function(item, i) {
    var pct = Math.round(item.p * 100);
    if (pct < 1) return '';
    return '<span class="px-3 py-1 rounded-full text-sm ' + (i === 0 ? 'bg-[#FF6B6B] text-white font-bold' : 'bg-gray-100 text-gray-600') + '">' + item.dish.name + ' (' + pct + '%)</span>';
  }).join('');
}

// ─── AI Chat Mode ──────────────────────────────────────────────
function aiChatSend() {
  var input = document.getElementById('aiChatInput');
  var text = input.value.trim();
  if (!text) return;
  var container = document.getElementById('aiChatMessages');

  // User message
  var userDiv = document.createElement('div');
  userDiv.className = 'flex gap-3 items-start flex-row-reverse';
  userDiv.innerHTML = '<div class="w-8 h-8 rounded-full bg-[#4ECDC4] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">You</div><div class="bg-[#4ECDC4]/10 rounded-2xl rounded-tr-sm px-4 py-3 text-sm max-w-[80%]" style="border:1px solid #ddd;">' + escapeHtml(text) + '</div>';
  container.appendChild(userDiv);

  input.value = '';
  input.disabled = true;
  document.getElementById('aiChatSend').disabled = true;

  // Loading indicator
  var progressMsgs = [
    '🤔 Analyzing your request...', '🔍 Searching ' + (allDishes.length || 865) + ' dishes...',
    '🍳 Finding the perfect match...', '⏳ Still working on it...', '🔄 This is taking longer than expected...'
  ];
  var loadingDiv = document.createElement('div');
  loadingDiv.className = 'flex gap-3 items-start';
  loadingDiv.id = 'aiLoading';
  loadingDiv.innerHTML = '<div class="w-8 h-8 rounded-full bg-[#FF6B6B] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">AI</div><div class="bg-white/80 rounded-2xl rounded-tl-sm px-4 py-3 text-sm" style="border:1px solid #eee;"><span class="inline-block animate-pulse" id="aiProgressMsg">' + progressMsgs[0] + '</span></div>';
  container.appendChild(loadingDiv);
  container.scrollTop = container.scrollHeight;

  var progressStep = 0;
  var progressTimer = setInterval(function() {
    progressStep++;
    var el = document.getElementById('aiProgressMsg');
    if (el && progressStep < progressMsgs.length) el.textContent = progressMsgs[progressStep];
  }, 4000);

  // Try OpenRouter AI first, fallback to local search
  SmakAI.getRecommendation(text, allDishes).then(function(data) {
    var loading = document.getElementById('aiLoading');
    if (loading) loading.remove();
    if (progressTimer) clearInterval(progressTimer);

    if (data.success) {
      if (data.text) {
        var textDiv = document.createElement('div');
        textDiv.className = 'flex gap-3 items-start';
        textDiv.innerHTML = '<div class="w-8 h-8 rounded-full bg-[#FF6B6B] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">AI</div>'
          + '<div class="bg-white/80 rounded-2xl rounded-tl-sm px-4 py-3 text-sm max-w-[80%]" style="border:1px solid #eee;">'
          + escapeHtml(data.text) + '</div>';
        container.appendChild(textDiv);
      }
      if (data.dishes && data.dishes.length > 0) {
        var cardsDiv = document.createElement('div');
        cardsDiv.className = 'flex gap-3 items-start';
        cardsDiv.innerHTML = '<div class="w-8 h-8 rounded-full bg-[#FF6B6B] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">AI</div>'
          + '<div class="flex-1 space-y-2">' + data.dishes.map(function(d) { return renderDishCard(d); }).join('') + '</div>';
        container.appendChild(cardsDiv);
      }
    } else {
      // Try local search directly
      var local = SmakAI.searchDishes(text, allDishes);
      if (local.length) {
        var textDiv = document.createElement('div');
        textDiv.className = 'flex gap-3 items-start';
        textDiv.innerHTML = '<div class="w-8 h-8 rounded-full bg-[#FF6B6B] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">AI</div>'
          + '<div class="bg-white/80 rounded-2xl rounded-tl-sm px-4 py-3 text-sm max-w-[80%]" style="border:1px solid #eee;">Here is what I found for "' + escapeHtml(text) + '":</div>';
        container.appendChild(textDiv);
        var cardsDiv = document.createElement('div');
        cardsDiv.className = 'flex gap-3 items-start';
        cardsDiv.innerHTML = '<div class="w-8 h-8 rounded-full bg-[#FF6B6B] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">AI</div>'
          + '<div class="flex-1 space-y-2">' + local.map(function(d) { return renderDishCard(d); }).join('') + '</div>';
        container.appendChild(cardsDiv);
      } else {
        var errDiv = document.createElement('div');
        errDiv.className = 'flex gap-3 items-start';
        errDiv.innerHTML = '<div class="w-8 h-8 rounded-full bg-[#FF6B6B] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">AI</div>'
          + '<div class="bg-white/80 rounded-2xl rounded-tl-sm px-4 py-3 text-sm max-w-[80%]" style="border:1px solid #eee;color:#FF6B6B;">'
          + 'Sorry, I couldn\'t find anything matching that. Try asking about a specific dish or ingredient!</div>';
        container.appendChild(errDiv);
      }
    }
    container.scrollTop = container.scrollHeight;
  });
}

function renderDishCard(d) {
  var img = d.image ? '<img src="' + escapeHtml(d.image) + '" alt="' + escapeHtml(d.name) + '" class="w-full h-full object-cover" />' : '🍽️';
  var vegTag = d.is_vegetarian ? '🥬 Veg' : '🍗 Non-Veg';
  var popTag = d.popular ? '<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">⭐ Popular</span>' : '';
  return '<div class="dish-result-card" onclick="window.location.href=\'/menu.php?search=' + encodeURIComponent(d.name) + '\'">'
    + '<div class="dish-result-img">' + img + '</div>'
    + '<div class="dish-result-info">'
    + '<div class="dish-result-name">' + escapeHtml(d.name) + ' ' + popTag + '</div>'
    + '<div class="dish-result-meta">' + vegTag + ' · ₹' + d.price + ' · ' + escapeHtml(d.region || '') + ' · 🌶️ ' + escapeHtml(d.spice_level || '') + '</div>'
    + (d.description ? '<div class="dish-result-desc">' + escapeHtml(d.description) + '</div>' : '')
    + '<div class="dish-result-cat">' + escapeHtml(d.category || d.category_name || '') + '</div>'
    + '</div></div>';
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─── AI Health Check ───────────────────────────────────────────
function checkAiHealth() {
  var el = document.getElementById('aiStatus');
  if (!el) return;
  SmakAI.checkAiHealth().then(function(d) {
    window.__aiAvailable = d.available;
    if (d.available) {
      el.textContent = '✅ AI Online (OpenRouter)';
      el.className = 'text-xs text-green-600';
    } else {
      el.textContent = '⚠️ AI Offline — Using local search';
      el.className = 'text-xs text-yellow-600';
    }
  }).catch(function() {
    window.__aiAvailable = false;
    el.textContent = '⚠️ AI Offline — Using local search';
    el.className = 'text-xs text-yellow-600';
  });
}

// ─── Event Bindings ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  initAkinator().then(function() {
    var dishCount = document.getElementById('dishCount');
    if (dishCount) dishCount.textContent = allDishes.length + ' dishes';
    startNewGame();
  });

  checkAiHealth();

  document.getElementById('modeGuess')?.addEventListener('click', function() { switchMode('guess'); });
  document.getElementById('modeChat')?.addEventListener('click', function() { switchMode('chat'); });
  document.getElementById('resetBtn')?.addEventListener('click', startNewGame);
  document.getElementById('wrongBtn')?.addEventListener('click', function() {
    if (wrongIndex >= 0) {
      probabilities[wrongIndex] = 0;
      var sum = probabilities.reduce(function(a, b) { return a + b; }, 0);
      probabilities = sum > 0 ? probabilities.map(function(p) { return p / sum; }) : allDishes.map(function() { return 1 / allDishes.length; });
      wrongIndex = -1;
      gameOver = false;
      document.getElementById('guessBox').style.display = 'none';
      var gameArea = document.getElementById('gameArea');
      if (gameArea) {
        var inputs = gameArea.querySelectorAll('button, input');
        for (var i = 0; i < inputs.length; i++) inputs[i].disabled = false;
      }
      askNext();
    }
  });

  var quickBtns = document.querySelectorAll('.quick-btn');
  for (var i = 0; i < quickBtns.length; i++) {
    quickBtns[i].addEventListener('click', function() { handleAnswer(this.dataset.ans); });
  }

  document.getElementById('answerBtn')?.addEventListener('click', function() {
    var input = document.getElementById('customAnswer');
    if (!input) return;
    var val = input.value.toLowerCase().trim();
    if (!val) return;
    if (['yes','yeah','sure','yep','spicy','hot','veg','vegetarian','rice','gravy','curry','sweet','dessert','north','south','chicken','mutton','fish','egg'].some(function(w) { return val.indexOf(w) >= 0; })) {
      handleAnswer('yes');
    } else if (['no','nope','not','neither','nah','n','none'].some(function(w) { return val.indexOf(w) >= 0; })) {
      handleAnswer('no');
    } else {
      handleAnswer('maybe');
    }
    input.value = '';
  });

  document.getElementById('customAnswer')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('answerBtn')?.click();
  });
  document.getElementById('aiChatSend')?.addEventListener('click', aiChatSend);
  document.getElementById('aiChatInput')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') aiChatSend();
  });
});
