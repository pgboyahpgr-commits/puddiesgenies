window.SmakAI = window.SmakAI || {};

// ─── OpenRouter API via PHP proxy ───────────────────────────────
SmakAI.callOpenRouter = function(messages, model) {
  model = model || 'openrouter/free';
  return fetch('/api/proxy.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ messages: messages, model: model })
  }).then(function(r) { return r.json(); });
};

SmakAI.checkAiHealth = function() {
  return fetch('/api/proxy.php', { cache: 'no-store' })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      SmakAI.__aiAvailable = d.available;
      SmakAI.__aiProvider = 'openrouter';
      SmakAI.__aiModel = d.model || 'meta-llama/llama-3.3-70b-instruct:free';
      return d;
    })
    .catch(function() {
      SmakAI.__aiAvailable = false;
      return { available: false };
    });
};

// ─── Exclusion Term Extraction ──────────────────────────────────
SmakAI.extractExcludeTerms = function(query) {
  var q = query.toLowerCase();
  var terms = [];
  var re = /\b(?:no|not|without|except|avoid|excluding|none|doesn't|doesnt|don't|dont)\s+(?:(?:have|eat|want|like|contain|include|use|any)\s+)?(\w+)/gi;
  var m;
  while ((m = re.exec(q)) !== null) {
    var t = m[1].toLowerCase().trim();
    if (t.length > 1) terms.push(t);
  }
  return terms;
};

SmakAI.buildCleanQuery = function(query) {
  return query.replace(/\b(?:no|not|without|except|avoid|excluding|none|doesn't|doesnt|don't|dont)\s+(?:(?:have|eat|want|like|contain|include|use|any)\s+)?\w+/gi, '').replace(/\s+/g, ' ').trim();
};

// ─── Exclusion Aliases ──────────────────────────────────────────
SmakAI.EXCLUSION_ALIASES = {
  rice: ['rice', 'biryani', 'pulao'],
};

SmakAI.getExclusionChecks = function(term) {
  return SmakAI.EXCLUSION_ALIASES[term] || [term];
};

// ─── Apply Exclusion Filter ─────────────────────────────────────
SmakAI.applyExclusionFilter = function(dishes, query) {
  if (!dishes || !dishes.length) return dishes;
  var terms = SmakAI.extractExcludeTerms(query);
  if (!terms.length) return dishes;
  return dishes.filter(function(d) {
    var name = (d.name || '').toLowerCase();
    var cat = (d.category_name || d.category || '').toLowerCase();
    for (var i = 0; i < terms.length; i++) {
      var checks = SmakAI.getExclusionChecks(terms[i]);
      for (var j = 0; j < checks.length; j++) {
        if (name.indexOf(checks[j]) >= 0) return false;
        if (cat.indexOf(checks[j]) >= 0) return false;
      }
    }
    return true;
  });
};

// ─── Local Dish Search ──────────────────────────────────────────
SmakAI.searchDishes = function(query, allDishes) {
  if (!allDishes || !allDishes.length) return [];
  var q = query.toLowerCase();
  var excludeTerms = SmakAI.extractExcludeTerms(q);
  var cleanQ = SmakAI.buildCleanQuery(q);

  var isVeg = /\b(?:veg|vegetarian|paneer|vegetable)\b/.test(cleanQ);
  if (/\bno\s+meat\b/i.test(q)) isVeg = 1;
  var isNonVeg = /\b(?:chicken|mutton|fish|meat|egg|non.?veg|prawn)\b/.test(cleanQ);
  var isSweet = /\bsweet|dessert|sugar|chocolate|cake|ice.?cream|pastry\b/.test(cleanQ);
  var isSpicy = /\bspicy|hot|spice|chilli|chili|pepper\b/.test(cleanQ);
  var isCheap = /\b(?:cheap|budget|affordable|under\s*\d+|below\s*\d+)\b/.test(q);
  var isRice = /\brice\b/.test(cleanQ);
  var isChicken = /\bchicken\b/.test(cleanQ);
  var isMutton = /\bmutton|lamb\b/.test(cleanQ);
  var isFish = /\bfish|prawn|seafood|crab|shrimp\b/.test(cleanQ);

  var scored = [];

  for (var i = 0; i < allDishes.length; i++) {
    var d = allDishes[i];
    var name = (d.name || '').toLowerCase();
    var desc = (d.description || '').toLowerCase();
    var catName = (d.category_name || d.category || '').toLowerCase();
    var spice = (d.spice_level || '').toLowerCase();
    var score = 0;

    // Veg/Non-veg signals
    if (isVeg && d.is_vegetarian) score += 10;
    if (isNonVeg && !d.is_vegetarian) score += 10;
    if (isNonVeg && d.is_vegetarian) score -= 20;

    // Category + description matches
    if (isSweet && (catName.indexOf('dessert') >= 0 || catName.indexOf('sweet') >= 0)) score += 10;
    if (isSweet && /chocolate|cake|ice.cream|sweet|halwa|kheer|pudding|pastry/.test(name)) score += 10;
    if (isSweet && /chocolate|cake|sweet|dessert/.test(desc)) score += 5;
    if (isSpicy && spice === 'hot') score += 5;
    if (isCheap && (d.price || 999) < 200) score += 5;
    if (isRice && /rice|biryani|pulao/.test(name)) score += 8;
    if (isChicken && /chicken/.test(name)) score += 10;
    if (isMutton && /mutton|lamb/.test(name)) score += 10;
    if (isFish && /fish|prawn|seafood|crab/.test(name)) score += 10;

    // Direct keyword match on clean query
    var words = cleanQ.split(' ');
    for (var w = 0; w < words.length; w++) {
      var word = words[w].trim();
      if (word.length <= 2) continue;
      if (name.indexOf(word) >= 0) score += 15;
      if (desc.indexOf(word) >= 0) score += 5;
    }

    // Exclusion penalty
    for (var e = 0; e < excludeTerms.length; e++) {
      var checks = SmakAI.getExclusionChecks(excludeTerms[e]);
      for (var c = 0; c < checks.length; c++) {
        if (name.indexOf(checks[c]) >= 0) { score -= 100; c = checks.length; }
        if (catName.indexOf(checks[c]) >= 0) { score -= 100; c = checks.length; }
      }
    }

    if (score > 0) scored.push({ dish: d, score: score });
  }

  // Fallback: if no positive matches but exclusions exist, show non-excluded dishes
  if (!scored.length && excludeTerms.length) {
    for (var i = 0; i < allDishes.length; i++) {
      var d = allDishes[i];
      var name = (d.name || '').toLowerCase();
      var catName = (d.category_name || d.category || '').toLowerCase();
      var excluded = false;
      for (var e = 0; e < excludeTerms.length; e++) {
        var checks = SmakAI.getExclusionChecks(excludeTerms[e]);
        for (var c = 0; c < checks.length; c++) {
          if (name.indexOf(checks[c]) >= 0) { excluded = true; break; }
          if (catName.indexOf(checks[c]) >= 0) { excluded = true; break; }
        }
        if (excluded) break;
      }
      if (!excluded) scored.push({ dish: d, score: 1 });
    }
    // Shuffle
    for (var i = scored.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = scored[i]; scored[i] = scored[j]; scored[j] = tmp;
    }
    scored = scored.slice(0, 5);
  }

  scored.sort(function(a, b) { return b.score - a.score; });
  return scored.slice(0, 5).map(function(s) { return s.dish; });
};

// ─── AI Recommendation ──────────────────────────────────────────
SmakAI.getRecommendation = function(query, allDishes) {
  var summary = SmakAI.getMenuSummary(allDishes);

  var messages = [
    {
      role: 'system',
      content: 'You are a helpful AI waiter. Recommend dishes from the menu based on the user\'s request. If the user says "no X", "without X", "doesn\'t have X", etc., you MUST EXCLUDE those dishes. Return ONLY exact dish names from the menu, one per line. No explanations, no numbering.'
    },
    {
      role: 'user',
      content: 'Menu:\n' + summary + '\n\nUser request: "' + query + '"\n\nRecommend 5 dish names from the menu, one per line. Exclude any dishes the user said no/without/except to.'
    }
  ];

  return SmakAI.callOpenRouter(messages).then(function(data) {
    if (!data.success || !data.content) {
      // Fallback to local search
      return { success: true, text: "Here's what I found:", dishes: SmakAI.searchDishes(query, allDishes), provider: 'local' };
    }

    var lines = data.content.split('\n');
    var names = [];
    for (var i = 0; i < lines.length; i++) {
      var line = lines[i].trim();
      line = line.replace(/^[\d\s\.\-\)]+/, '');
      if (line.length > 2) names.push(line);
    }

    // Match names against menu
    var matched = [];
    for (var n = 0; n < names.length; n++) {
      var target = names[n].toLowerCase();
      var best = null;
      var bestScore = 0;
      for (var di = 0; di < allDishes.length; di++) {
        var dn = (allDishes[di].name || '').toLowerCase();
        var pct = 0;
        // Simple similarity
        var minLen = Math.min(target.length, dn.length);
        var matches = 0;
        for (var ch = 0; ch < minLen; ch++) {
          if (target[ch] === dn[ch]) matches++;
        }
        pct = (matches / Math.max(target.length, dn.length)) * 100;
        if (pct > bestScore) {
          bestScore = pct;
          best = allDishes[di];
        }
      }
      if (best && bestScore > 60 && matched.indexOf(best) < 0) {
        matched.push(best);
      }
    }

    // Apply exclusion filter
    matched = SmakAI.applyExclusionFilter(matched, query);

    if (matched.length) {
      return { success: true, text: "Here's what I found for \"" + query + "\":", dishes: matched.slice(0, 5), provider: 'openrouter' };
    }

    // Fallback to local search
    return { success: true, text: "Here's what I found:", dishes: SmakAI.searchDishes(query, allDishes), provider: 'local' };
  });
};

// ─── Akinator AI Question ───────────────────────────────────────
SmakAI.QUESTIONS_FALLBACK = [
  { key: 'veg', q: 'Is it vegetarian?', icon: '🥬', map: function(a) { return a.is_vegetarian === true; } },
  { key: 'spice', q: 'Is it spicy?', icon: '🌶️', map: function(a) { return a.spice_level === 'hot'; } },
  { key: 'type', q: 'Is it a main course dish?', icon: '🍛', map: function(a) { return ['biryani','curries','thali','tandoor','south_indian','rice'].indexOf(a.category_id) >= 0; } },
  { key: 'rice', q: 'Does it have rice or bread?', icon: '🍚', map: function(a) { return ['biryani','breads','south_indian','thali','rice'].indexOf(a.category_id) >= 0 || (a.name || '').toLowerCase().indexOf('rice') >= 0 || (a.name || '').toLowerCase().indexOf('roti') >= 0 || (a.name || '').toLowerCase().indexOf('naan') >= 0; } },
  { key: 'snack', q: 'Is it a snack or appetizer?', icon: '🥟', map: function(a) { return ['appetizers','street_food','snacks','chaat'].indexOf(a.category_id) >= 0; } },
  { key: 'sweet', q: 'Is it a dessert or sweet?', icon: '🍰', map: function(a) { return ['desserts','sweets','beverages'].indexOf(a.category_id) >= 0 || a.spice_level === 'sweet'; } },
  { key: 'chicken', q: 'Is it a chicken dish?', icon: '🐔', map: function(a) { return (a.name || '').toLowerCase().indexOf('chicken') >= 0; } },
  { key: 'mutton', q: 'Is it a lamb or mutton dish?', icon: '🐑', map: function(a) { return (a.name || '').toLowerCase().indexOf('mutton') >= 0 || (a.name || '').toLowerCase().indexOf('lamb') >= 0; } },
  { key: 'price', q: 'Is it under ₹200?', icon: '💰', map: function(a) { return (a.price || 0) < 200; } },
  { key: 'region_north', q: 'Is it from North India?', icon: '🏔️', map: function(a) { return ['North India','Punjab','Mughlai','Kashmir','Rajasthan','Delhi'].indexOf(a.region) >= 0; } },
  { key: 'region_south', q: 'Is it from South India?', icon: '🌴', map: function(a) { return ['South India','Kerala','Tamil Nadu','Hyderabad','Karnataka','Goa','Coastal India'].indexOf(a.region) >= 0; } },
  { key: 'popular', q: 'Is it a popular dish?', icon: '⭐', map: function(a) { return a.popular === true; } },
];

SmakAI.getAkinatorQuestion = function(state) {
  var summary = SmakAI.getMenuSummary(SmakAI.__allDishes || []);
  var conversation = 'You are playing a YES/NO guessing game. The user is thinking of an Indian dish.\n';
  conversation += 'Menu categories: Biryani, Curries, Tandoor, Breads, South Indian, Appetizers, Desserts, etc.\n';
  conversation += 'Ask ONE yes/no question to narrow it down. Be creative, ask about ingredients, cooking method, region, etc.\n';

  if (state.answers && state.answers.length) {
    conversation += '\nPrevious Q&A:\n';
    for (var i = 0; i < state.answers.length; i++) {
      conversation += 'Q' + (i + 1) + ': (previous question)\nA' + (i + 1) + ': ' + state.answers[i] + '\n';
    }
  }
  conversation += '\nBased on these answers, ask your NEXT yes/no question.\nJust output the question, nothing else.';

  var messages = [
    { role: 'system', content: 'You ask creative yes/no questions to guess what dish someone is thinking of.' },
    { role: 'user', content: conversation }
  ];

  return SmakAI.callOpenRouter(messages).then(function(data) {
    if (data.success && data.content) {
      return { question: data.content.trim(), fallback: false };
    }
    return null;
  });
};

// ─── Akinator Final Guess ──────────────────────────────────────
SmakAI.makeFinalGuess = function(state, allDishes) {
  if (!allDishes || !allDishes.length) return { name: 'Biryani', confidence: 50 };

  var scored = [];
  for (var i = 0; i < allDishes.length; i++) {
    var d = allDishes[i];
    var score = 50;
    var name = (d.name || '').toLowerCase();
    var desc = (d.description || '').toLowerCase();
    var cat = (d.category_name || '').toLowerCase();
    var region = (d.region || '').toLowerCase();

    for (var a = 0; a < (state.answers || []).length; a++) {
      var ans = (state.answers[a] || '').toLowerCase();
      if (ans.indexOf('yes') >= 0 || ans.indexOf('yeah') >= 0) {
        if (ans.indexOf('vegetarian') >= 0 && d.is_vegetarian) score += 20;
        if (ans.indexOf('spicy') >= 0 && d.spice_level === 'hot') score += 20;
        if (ans.indexOf('rice') >= 0 && /rice|biryani|pulao/.test(name)) score += 20;
        if (ans.indexOf('chicken') >= 0 && /chicken/.test(name)) score += 25;
        if (ans.indexOf('mutton') >= 0 && /mutton|lamb/.test(name)) score += 25;
        if (ans.indexOf('dessert') >= 0 && cat === 'desserts') score += 25;
        if (ans.indexOf('south') >= 0 && /south|kerala|tamil/.test(region)) score += 20;
        if (ans.indexOf('snack') >= 0 && /snack|appetizer|chaat/.test(cat)) score += 20;
        if (ans.indexOf('tandoor') >= 0 && /tandoor/.test(cat)) score += 20;
        if (ans.indexOf('gravy') >= 0 && /gravy|curry|masala/.test(desc)) score += 20;
      } else {
        if (ans.indexOf('vegetarian') >= 0 && d.is_vegetarian) score -= 15;
        if (ans.indexOf('spicy') >= 0 && d.spice_level === 'hot') score -= 15;
        if (ans.indexOf('rice') >= 0 && /rice|biryani|pulao/.test(name)) score -= 15;
        if (ans.indexOf('chicken') >= 0 && /chicken/.test(name)) score -= 15;
        if (ans.indexOf('mutton') >= 0 && /mutton|lamb/.test(name)) score -= 15;
        if (ans.indexOf('dessert') >= 0 && cat === 'desserts') score -= 15;
        if (ans.indexOf('south') >= 0 && /south|kerala|tamil/.test(region)) score -= 15;
      }
    }

    if (d.popular) score += 5;
    scored.push({ dish: d, score: Math.max(1, score) });
  }

  scored.sort(function(a, b) { return b.score - a.score; });
  var top = scored[0].dish;
  top.confidence = Math.min(95, scored[0].score);
  return top;
};

// ─── Menu Summary Builder ───────────────────────────────────────
SmakAI.getMenuSummary = function(allDishes) {
  if (!allDishes || !allDishes.length) return '';
  var cats = {};
  for (var i = 0; i < allDishes.length; i++) {
    var d = allDishes[i];
    var cn = d.category_name || d.category || 'Other';
    if (!cats[cn]) cats[cn] = [];
    cats[cn].push(d.name);
  }
  var lines = [];
  for (var c in cats) {
    lines.push(c + ': ' + cats[c].join(', '));
  }
  return lines.join('\n');
};

// ─── Dish Image Fetcher ─────────────────────────────────────────
SmakAI.fetchDishImage = function(dishName) {
  return fetch('/includes/image-fetcher.php?dish=' + encodeURIComponent(dishName))
    .then(function(r) { return r.json(); })
    .then(function(data) { return (data && data.url) ? data.url : null; })
    .catch(function() { return null; });
};
