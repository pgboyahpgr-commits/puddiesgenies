<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/menu-loader.php';
require_once __DIR__ . '/../includes/config.php';

$menuData = loadMenuFromGist();
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');
$mode = $input['mode'] ?? 'recommend';

// In guess mode, empty message means "start a new game"
if (!$userMessage && $mode !== 'guess') {
  echo json_encode(['success' => false, 'error' => 'No message provided']);
  exit;
}

// Build a full dish index for lookups
$allDishes = [];
$dishByName = [];
if ($menuData && isset($menuData['menu'])) {
  foreach ($menuData['menu']['categories'] ?? [] as $cat) {
    foreach ($cat['items'] ?? [] as $item) {
      $item['category_name'] = $cat['name'];
      $item['category_id'] = $cat['id'];
      $allDishes[] = $item;
      $dishByName[strtolower(trim($item['name']))] = $item;
    }
  }
}

$menuSummary = $menuData ? getMenuSummary($menuData, 33, 25) : '';

if ($mode === 'guess') {
  handleGuessMode($userMessage, $menuSummary, $allDishes, $dishByName);
} else {
  handleRecommendMode($userMessage, $menuSummary, $allDishes, $dishByName);
}

// ─── RECOMMEND MODE ────────────────────────────────────────────
function handleRecommendMode($query, $menuSummary, $allDishes, $dishByName) {
  // Try g4f API first for understanding the query
  $aiDishNames = callG4fForRecommendation($query, $menuSummary);
  $matchedDishes = [];

  if (!empty($aiDishNames)) {
    // Match AI-suggested dish names against our menu
    foreach ($aiDishNames as $suggested) {
      $suggestedLower = strtolower(trim($suggested));
      $bestMatch = null;
      $bestScore = 0;

      foreach ($dishByName as $name => $dish) {
        similar_text($suggestedLower, $name, $pct);
        if ($pct > $bestScore) {
          $bestScore = $pct;
          $bestMatch = $dish;
        }
      }

      if ($bestMatch && $bestScore > 60) {
        $matchedDishes[$bestMatch['name']] = $bestMatch;
      }
    }
  }

  // Post-processing: filter out dishes matching exclusion terms (belt-and-suspenders)
  if (!empty($matchedDishes)) {
    $matchedDishes = applyExclusionFilter($matchedDishes, $query);
  }

  // If AI gave us good matches, return them with full details
  if (!empty($matchedDishes)) {
    $dishes = array_values($matchedDishes);
    $dishes = array_slice($dishes, 0, 5);
    echo json_encode([
      'success' => true,
      'text' => "Here's what I found for \"$query\":",
      'dishes' => formatDishes($dishes),
      'provider' => 'g4f'
    ]);
    return;
  }

  // Fallback: use enhanced local search
  $localResults = localSearchDishes($query, $allDishes);
  if (!empty($localResults)) {
    echo json_encode([
      'success' => true,
      'text' => "Here's what I found for \"$query\":",
      'dishes' => formatDishes($localResults),
      'provider' => 'local'
    ]);
    return;
  }

  // Absolute fallback: random popular dishes
  $popular = array_filter($allDishes, function($d) { return !empty($d['popular']); });
  if (empty($popular)) $popular = $allDishes;
  shuffle($popular);
  $picks = array_slice($popular, 0, 4);

  echo json_encode([
    'success' => true,
    'text' => "I couldn't find specific matches for \"$query\", but here are some popular dishes:",
    'dishes' => formatDishes($picks),
    'provider' => 'local'
  ]);
}

// ─── GUESS MODE ────────────────────────────────────────────────
function handleGuessMode($answer, $menuSummary, $allDishes, $dishByName) {
  if (!isset($_SESSION['guess_state'])) {
    $_SESSION['guess_state'] = ['step' => 0, 'answers' => [], 'eliminated' => []];
  }

  $state = &$_SESSION['guess_state'];
  $state['step']++;

  if ($state['step'] > 1) {
    $state['answers'][] = strtolower(trim($answer));
  }

  // If too many steps, make a guess
  if ($state['step'] >= 12) {
    $guess = makeFinalGuess($state, $allDishes);
    $_SESSION['guess_state'] = ['step' => 0, 'answers' => [], 'eliminated' => []];
    echo json_encode(['success' => true, 'guess' => $guess['name'], 'confidence' => $guess['confidence'], 'dish' => formatDish($guess)]);
    return;
  }

  // Call g4f for adaptive questioning
  $question = callG4fForGuess($state, $menuSummary);

  if ($question) {
    echo json_encode(['success' => true, 'question' => $question, 'step' => $state['step'], 'maxSteps' => 12]);
  } else {
    // Fallback: use Bayesian approach
    $fallbackQ = getFallbackQuestion($state, $allDishes);
    echo json_encode(['success' => true, 'question' => $fallbackQ, 'step' => $state['step'], 'maxSteps' => 12, 'fallback' => true]);
  }
}

function callG4fForRecommendation($query, $menuSummary) {
  $prompt = "You are a helpful AI waiter. The user asked: \"$query\".\n\n";
  $prompt .= "Based STRICTLY on the menu below, recommend the TOP 5 dish names that best match.\n";
  $prompt .= "CRITICAL: If the user says 'no X', 'not X', 'without X', 'except X', or 'avoid X', you MUST EXCLUDE those dishes entirely. For example, 'spicy but no rice' should ONLY recommend spicy dishes that do NOT contain rice.\n";
  $prompt .= "Return ONLY exact dish names from the menu, one per line. No explanations, no numbering.\n\n";
  if ($menuSummary) $prompt .= "MENU:\n$menuSummary";

  $response = callG4f($prompt);
  if (!$response) return [];

  $lines = explode("\n", trim($response));
  $names = [];
  foreach ($lines as $line) {
    $line = trim($line);
    $line = preg_replace('/^[\d\s\.\-\)]+/', '', $line);
    $line = preg_replace('/[™®©]/', '', $line);
    $line = trim($line);
    if (strlen($line) > 2) $names[] = $line;
  }
  return $names;
}

function callG4fForGuess($state, $menuSummary) {
  $conversation = "You are playing a YES/NO guessing game. The user is thinking of an Indian dish.\n";
  $conversation .= "Menu categories: Biryani, Curries, Tandoor, Breads, South Indian, Appetizers, Desserts, etc.\n";
  $conversation .= "Ask ONE yes/no question to narrow it down. Be creative, ask about ingredients, cooking method, region, etc.\n";

  if (!empty($state['answers'])) {
    $conversation .= "\nPrevious Q&A:\n";
    $idx = 1;
    foreach ($state['answers'] as $a) {
      $conversation .= "Q$idx: (question)\nA$idx: $a\n";
      $idx++;
    }
    $conversation .= "\nBased on these answers, ask your NEXT yes/no question.\n";
  }

  $conversation .= "\nJust output the question, nothing else.";

  return callG4f($conversation);
}

function callG4f($prompt) {
  $providers = [
    ['url' => 'https://gen.pollinations.ai/v1/chat/completions', 'model' => 'openai'],
    ['url' => 'https://g4f.space/api/auto/chat/completions', 'model' => 'auto'],
    ['url' => 'https://g4f.space/api/pollinations/chat/completions', 'model' => 'auto'],
    ['url' => 'https://g4f.space/api/groq/chat/completions', 'model' => 'llama-3.1-8b-instant'],
  ];

  foreach ($providers as $p) {
    $payload = json_encode([
      'model' => $p['model'],
      'messages' => [['role' => 'user', 'content' => $prompt]],
      'max_tokens' => 200,
      'temperature' => 0.7
    ]);

    if (!function_exists('curl_init')) continue;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $p['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SmakAI/1.0');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);

    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $code < 200 || $code >= 300 || !$result) continue;

    $data = json_decode($result, true);
    if (!$data) continue;

    $content = null;
    if (isset($data['choices'][0]['message']['content'])) $content = $data['choices'][0]['message']['content'];
    elseif (isset($data['choices'][0]['text'])) $content = $data['choices'][0]['text'];
    elseif (isset($data['response'])) $content = $data['response'];

    if ($content && strlen(trim($content)) > 3) return trim($content);
  }
  return null;
}

function getFallbackQuestion($state, $allDishes) {
  $questions = [
    'Is it a vegetarian dish?',
    'Is it spicy?',
    'Is it a main course dish?',
    'Does it have rice or bread?',
    'Is it a snack or appetizer?',
    'Is it a dessert or sweet?',
    'Is it a chicken dish?',
    'Is it a lamb or mutton dish?',
    'Does it have a gravy or sauce?',
    'Is it cooked in a tandoor (clay oven)?',
    'Is it from South India?',
    'Is it a popular dish?',
  ];

  $asked = count($state['answers']);
  if ($asked < count($questions)) return $questions[$asked];
  return 'Is it a dish you eat with rice?';
}

function makeFinalGuess($state, $allDishes) {
  if (empty($allDishes)) return ['name' => 'Biryani', 'confidence' => 50];

  // Score each dish based on answers
  $scored = [];
  foreach ($allDishes as $d) {
    $score = 50;
    $name = strtolower($d['name']);
    $desc = strtolower($d['description'] ?? '');
    $cat = strtolower($d['category_name'] ?? '');
    $region = strtolower($d['region'] ?? '');

    foreach ($state['answers'] as $ans) {
      if (strpos($ans, 'yes') !== false) {
        if (strpos($ans, 'vegetarian') !== false && !empty($d['is_vegetarian'])) $score += 20;
        if (strpos($ans, 'spicy') !== false && ($d['spice_level'] ?? '') === 'hot') $score += 20;
        if (strpos($ans, 'rice') !== false && preg_match('/rice|biryani|pulao/', $name)) $score += 20;
        if (strpos($ans, 'chicken') !== false && preg_match('/chicken/', $name)) $score += 25;
        if (strpos($ans, 'mutton') !== false && preg_match('/mutton|lamb/', $name)) $score += 25;
        if (strpos($ans, 'dessert') !== false && $cat === 'desserts') $score += 25;
        if (strpos($ans, 'south') !== false && preg_match('/south|kerala|tamil/', $region)) $score += 20;
        if (strpos($ans, 'snack') !== false && preg_match('/snack|appetizer|chaat/', $cat)) $score += 20;
        if (strpos($ans, 'tandoor') !== false && preg_match('/tandoor/', $cat)) $score += 20;
        if (strpos($ans, 'gravy') !== false && preg_match('/gravy|curry|masala/', $desc)) $score += 20;
      } else {
        if (strpos($ans, 'vegetarian') !== false && !empty($d['is_vegetarian'])) $score -= 15;
        if (strpos($ans, 'spicy') !== false && ($d['spice_level'] ?? '') === 'hot') $score -= 15;
        if (strpos($ans, 'rice') !== false && preg_match('/rice|biryani|pulao/', $name)) $score -= 15;
        if (strpos($ans, 'chicken') !== false && preg_match('/chicken/', $name)) $score -= 15;
        if (strpos($ans, 'mutton') !== false && preg_match('/mutton|lamb/', $name)) $score -= 15;
        if (strpos($ans, 'dessert') !== false && $cat === 'desserts') $score -= 15;
        if (strpos($ans, 'south') !== false && preg_match('/south|kerala|tamil/', $region)) $score -= 15;
      }
    }

    if (!empty($d['popular'])) $score += 5;

    $scored[] = ['dish' => $d, 'score' => max(1, $score)];
  }

  usort($scored, function($a, $b) { return $b['score'] - $a['score']; });
  $top = $scored[0]['dish'];
  $confidence = min(95, $scored[0]['score']);
  $top['confidence'] = $confidence;
  return $top;
}

function localSearchDishes($query, $allDishes) {
  $q = strtolower($query);

  // Extract exclusion terms: words after "no", "not", "without", "doesn't", "don't", etc.
  $excludeTerms = [];
  preg_match_all('/\b(?:no|not|without|except|avoid|excluding|none|doesn\'t|doesnt|don\'t|dont)\s+(?:(?:have|eat|want|like|contain|include|use|any)\s+)?(\w+)/i', $q, $m);
  foreach ($m[1] as $t) {
    $t = strtolower(trim($t));
    if (strlen($t) > 1) $excludeTerms[] = $t;
  }

  // Build clean query without negation phrases for positive matching
  $cleanQ = trim(preg_replace('/\b(?:no|not|without|except|avoid|excluding|none|doesn\'t|doesnt|don\'t|dont)\s+(?:(?:have|eat|want|like|contain|include|use|any)\s+)?\w+/i', '', $q));
  $cleanQ = preg_replace('/\s+/', ' ', $cleanQ);

  $isVeg = (int)preg_match('/\b(veg|vegetarian|paneer|vegetable)\b/', $cleanQ);
  if (preg_match('/\bno\s+meat\b/i', $q)) $isVeg = 1;
  $isNonVeg = (int)preg_match('/\b(chicken|mutton|fish|meat|egg|non.?veg|prawn)\b/', $cleanQ);
  $isSweet = (int)preg_match('/\bsweet|dessert|sugar|chocolate|cake|ice.?cream|pastry\b/', $cleanQ);
  $isSpicy = (int)preg_match('/\bspicy|hot|spice\b/', $cleanQ);
  $isCheap = (int)preg_match('/\bcheap|budget|under\s*\d+|affordable\b/', $cleanQ);
  $isRice = (int)preg_match('/\brice|biryani|pulao\b/', $cleanQ);
  $isBread = (int)preg_match('/\broti|naan|bread|paratha|kulcha\b/', $cleanQ);
  $isChicken = (int)preg_match('/\bchicken\b/', $cleanQ);
  $isMutton = (int)preg_match('/\bmutton|lamb\b/', $cleanQ);
  $isFish = (int)preg_match('/\bfish|prawn|seafood\b/', $cleanQ);

  $scored = [];
  foreach ($allDishes as $d) {
    $score = 0;
    $name = strtolower($d['name']);
    $desc = strtolower($d['description'] ?? '');
    $catName = strtolower($d['category_name'] ?? '');
    $region = strtolower($d['region'] ?? '');
    $spice = strtolower($d['spice_level'] ?? '');

    if ($isVeg && !empty($d['is_vegetarian'])) $score += 5;
    if ($isNonVeg && empty($d['is_vegetarian'])) $score += 5;
    if ($isSweet && (strpos($catName, 'dessert') !== false || strpos($catName, 'sweet') !== false)) $score += 10;
    if ($isSweet && preg_match('/chocolate|cake|ice.cream|sweet|halwa|kheer|pudding|pastry/', $name)) $score += 10;
    if ($isSweet && preg_match('/chocolate|cake|sweet|dessert/', $desc)) $score += 5;
    if ($isSpicy && $spice === 'hot') $score += 5;
    if ($isCheap && ($d['price'] ?? 999) < 200) $score += 5;
    if ($isRice && preg_match('/rice|biryani|pulao/', $name)) $score += 8;
    if ($isChicken && preg_match('/chicken/', $name)) $score += 10;
    if ($isMutton && preg_match('/mutton|lamb/', $name)) $score += 10;
    if ($isFish && preg_match('/fish|prawn|seafood|crab/', $name)) $score += 10;

    // Direct keyword match on clean query (negated words removed)
    $words = explode(' ', $cleanQ);
    foreach ($words as $word) {
      $word = trim($word);
      if (strlen($word) <= 2) continue;
      if (strpos($name, $word) !== false) $score += 15;
      if (strpos($desc, $word) !== false) $score += 5;
    }

    // Exclusion penalty: heavily penalize dishes matching excluded terms
    foreach ($excludeTerms as $ex) {
      // Aliases for common exclusion terms (e.g., "no rice" also excludes biryani)
      $patterns = ['rice' => ['rice', 'biryani', 'pulao']];
      $checks = $patterns[$ex] ?? [$ex];
      foreach ($checks as $c) {
        if (strpos($name, $c) !== false) { $score -= 100; break; }
        if (strpos($catName, $c) !== false) { $score -= 100; break; }
      }
    }

    if ($score > 0) $scored[] = ['dish' => $d, 'score' => $score];
  }

  // If no positive matches but exclusions exist, show dishes that don't match exclusions
  if (empty($scored) && !empty($excludeTerms)) {
    $patterns = ['rice' => ['rice', 'biryani', 'pulao']];
    foreach ($allDishes as $d) {
      $name = strtolower($d['name']);
      $catName = strtolower($d['category_name'] ?? '');
      $excluded = false;
      foreach ($excludeTerms as $ex) {
        $checks = $patterns[$ex] ?? [$ex];
        foreach ($checks as $c) {
          if (strpos($name, $c) !== false) { $excluded = true; break; }
          if (strpos($catName, $c) !== false) { $excluded = true; break; }
        }
        if ($excluded) break;
      }
      if (!$excluded) $scored[] = ['dish' => $d, 'score' => 1];
    }
    shuffle($scored);
    $scored = array_slice($scored, 0, 5);
  }

  usort($scored, function($a, $b) { return $b['score'] - $a['score']; });
  return array_map(function($s) { return $s['dish']; }, array_slice($scored, 0, 5));
}

function formatDishes($dishes) {
  return array_map('formatDish', $dishes);
}

function formatDish($d) {
  return [
    'name' => $d['name'] ?? '',
    'price' => intval($d['price'] ?? 0),
    'description' => $d['description'] ?? '',
    'image' => $d['image'] ?? '',
    'category' => $d['category_name'] ?? '',
    'region' => $d['region'] ?? '',
    'spice_level' => $d['spice_level'] ?? 'mild',
    'is_vegetarian' => !empty($d['is_vegetarian']),
    'popular' => !empty($d['popular'])
  ];
}

function applyExclusionFilter($dishes, $query) {
  $excludeTerms = [];
  preg_match_all('/\b(?:no|not|without|except|avoid|excluding|none|doesn\'t|doesnt|don\'t|dont)\s+(?:(?:have|eat|want|like|contain|include|use|any)\s+)?(\w+)/i', $query, $m);
  foreach ($m[1] as $t) {
    $t = strtolower(trim($t));
    if (strlen($t) > 1) $excludeTerms[] = $t;
  }
  if (empty($excludeTerms)) return $dishes;
  $patterns = ['rice' => ['rice', 'biryani', 'pulao']];
  return array_filter($dishes, function($dish) use ($excludeTerms, $patterns) {
    $name = strtolower($dish['name']);
    $cat = strtolower($dish['category_name'] ?? '');
    foreach ($excludeTerms as $ex) {
      $checks = $patterns[$ex] ?? [$ex];
      foreach ($checks as $c) {
        if (strpos($name, $c) !== false) return false;
        if (strpos($cat, $c) !== false) return false;
      }
    }
    return true;
  });
}
