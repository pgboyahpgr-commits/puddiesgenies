<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$eventsFile = __DIR__ . '/../data/events.json';
$events = loadJSON($eventsFile);
if (!is_array($events)) $events = [];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $msg = '❌ Security token expired.';
  } elseif (isset($_POST['add_event']) || isset($_POST['edit_event'])) {
    $editId = $_POST['event_id'] ?? '';
    $newEvent = [
      'id' => $editId ?: 'evt_' . time(),
      'title' => $_POST['title'] ?? '',
      'description' => $_POST['description'] ?? '',
      'type' => $_POST['type'] ?? 'banner',
      'start_date' => $_POST['start_date'] ?? '',
      'end_date' => $_POST['end_date'] ?? '',
      'active' => isset($_POST['active']),
      'discount_percent' => intval($_POST['discount_percent'] ?? 0),
      'bg_color' => $_POST['bg_color'] ?? '#FFE66D',
      'created_at' => date('c')
    ];
    if (empty($newEvent['title'])) { $msg = '❌ Title is required.'; }
    else {
      if ($editId) {
        foreach ($events as &$e) { if ($e['id'] === $editId) { $e = $newEvent; break; } }
      } else {
        $events[] = $newEvent;
      }
      saveJSON($eventsFile, $events);
      $msg = '✅ Event saved!';
    }
  } elseif (isset($_POST['delete_event'])) {
    $delId = $_POST['event_id'] ?? '';
    $events = array_values(array_filter($events, fn($e) => $e['id'] !== $delId));
    saveJSON($eventsFile, $events);
    $msg = '🗑️ Event deleted.';
  } elseif (isset($_POST['toggle_event'])) {
    $togId = $_POST['event_id'] ?? '';
    foreach ($events as &$e) { if ($e['id'] === $togId) { $e['active'] = !($e['active'] ?? false); break; } }
    saveJSON($eventsFile, $events);
    $msg = '✅ Toggled.';
  }
  header('Location: /admins/events.php');
  exit;
}

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Events — Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}</style>
</head>
<body>
<nav class="max-w-7xl mx-auto px-4 py-4 flex items-center gap-3 flex-wrap">
  <a href="/admins/dashboard.php" class="text-xl font-bold no-underline" style="color:#2D3436;">← Admin</a>
  <span class="text-gray-400">/ Events</span>
</nav>
<main class="max-w-4xl mx-auto px-4 pb-12">
  <h1 class="text-3xl font-bold mb-2" style="color:#2D3436;">🎉 Events & Promotions</h1>
  <p class="text-sm text-gray-400 mb-6">Create banners, popups, and discounts that show on customer pages during specific dates.</p>

  <?php if ($msg): ?><div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-2xl mb-4 text-sm"><?=htmlspecialchars($msg)?></div><?php endif; ?>

  <button onclick="openForm()" class="px-5 py-2.5 rounded-full font-bold text-white mb-6" style="background:#4ECDC4;">+ New Event</button>

  <div id="eventForm" class="hidden bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100 mb-6">
    <h3 class="font-bold mb-3" id="formTitle">New Event</h3>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
      <input type="hidden" name="event_id" id="formId" value="" />
      <input type="hidden" name="add_event" id="formAction" value="1" />
      <div class="md:col-span-2">
        <label class="text-xs text-gray-400 block mb-1">Event Title *</label>
        <input name="title" id="formTitleInput" placeholder="e.g. Weekend Special: 20% Off" required class="w-full px-4 py-2.5 rounded-full border border-gray-200 text-sm" />
      </div>
      <div class="md:col-span-2">
        <label class="text-xs text-gray-400 block mb-1">Description</label>
        <textarea name="description" id="formDesc" placeholder="Describe the offer or event..." rows="2" class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 text-sm resize-none"></textarea>
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">Type</label>
        <select name="type" id="formType" class="w-full px-4 py-2.5 rounded-full border border-gray-200 text-sm">
          <option value="banner">📢 Banner (top of page)</option>
          <option value="popup">🪟 Popup (on page load)</option>
          <option value="discount">🏷️ Discount Badge</option>
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">Discount % (0 = none)</label>
        <input name="discount_percent" id="formDiscount" type="number" min="0" max="100" value="0" class="w-full px-4 py-2.5 rounded-full border border-gray-200 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">Start Date</label>
        <input name="start_date" id="formStart" type="date" class="w-full px-4 py-2.5 rounded-full border border-gray-200 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">End Date</label>
        <input name="end_date" id="formEnd" type="date" class="w-full px-4 py-2.5 rounded-full border border-gray-200 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-400 block mb-1">Background Color</label>
        <input name="bg_color" id="formBg" type="color" value="#FFE66D" class="w-full h-10 rounded-full border border-gray-200 cursor-pointer" />
      </div>
      <div class="flex items-center">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" id="formActive" checked /> Active</label>
      </div>
      <div class="md:col-span-2">
        <button type="submit" class="w-full px-5 py-3 rounded-full font-bold text-white" style="background:#4ECDC4;">Save Event</button>
      </div>
    </form>
  </div>

  <?php if (empty($events)): ?>
  <div class="text-center py-12 text-gray-400 bg-white/60 rounded-2xl">
    <div class="text-4xl mb-2">🎉</div>
    <p>No events yet. Create your first promotion!</p>
  </div>
  <?php else: ?>
  <div class="space-y-3">
    <?php foreach (array_reverse($events) as $e):
      $isActive = !empty($e['active']);
      $today = date('Y-m-d');
      $inDateRange = (!$e['start_date'] || $e['start_date'] <= $today) && (!$e['end_date'] || $e['end_date'] >= $today);
      $status = $isActive && $inDateRange ? '🟢 Live' : ($isActive ? '🟡 Scheduled' : '🔴 Inactive');
      $typeLabel = match($e['type'] ?? 'banner') { 'popup' => '🪟 Popup', 'discount' => '🏷️ Discount', default => '📢 Banner' };
    ?>
    <div class="bg-white/80 backdrop-blur rounded-2xl p-4 shadow border border-gray-100 flex items-center justify-between gap-3 flex-wrap">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="font-bold" style="color:#2D3436;"><?=htmlspecialchars($e['title'])?></span>
          <span class="text-xs px-2 py-0.5 rounded-full" style="background:<?=$e['bg_color']??'#FFE66D'?>30;color:#2D3436;"><?=$typeLabel?></span>
          <?php if (!empty($e['discount_percent'])): ?><span class="text-xs font-bold text-red-500">-<?=intval($e['discount_percent'])?>%</span><?php endif; ?>
        </div>
        <?php if ($e['description'] ?? ''): ?><p class="text-xs text-gray-400 mt-1 truncate"><?=htmlspecialchars($e['description'])?></p><?php endif; ?>
        <div class="text-xs text-gray-400 mt-1">
          <?php if ($e['start_date'] ?? ''): ?><span>📅 <?=htmlspecialchars($e['start_date'])?> → <?=htmlspecialchars($e['end_date'] ?? '∞')?></span><?php endif; ?>
          <span class="ml-2"><?=$status?></span>
        </div>
      </div>
      <div class="flex gap-2 flex-shrink-0">
        <form method="POST" class="inline">
          <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
          <input type="hidden" name="toggle_event" value="1" />
          <input type="hidden" name="event_id" value="<?=htmlspecialchars($e['id'])?>" />
          <button type="submit" class="text-xs px-3 py-1.5 rounded-full font-bold" style="background:<?=$isActive?'#FF6B6B':'#4ECDC4'?>;color:#fff;border:none;cursor:pointer;"><?=$isActive?'Deactivate':'Activate'?></button>
        </form>
        <button onclick="editEvent('<?=htmlspecialchars($e['id'], ENT_QUOTES)?>','<?=htmlspecialchars($e['title'], ENT_QUOTES)?>','<?=htmlspecialchars($e['description'] ?? '', ENT_QUOTES)?>','<?=htmlspecialchars($e['type'] ?? 'banner')?>','<?=htmlspecialchars($e['start_date'] ?? '')?>','<?=htmlspecialchars($e['end_date'] ?? '')?>',<?=intval($e['discount_percent'] ?? 0)?>,'<?=htmlspecialchars($e['bg_color'] ?? '#FFE66D')?>',<?=!empty($e['active'])?'true':'false'?>)" class="text-blue-500 text-xs font-bold" style="border:none;background:none;cursor:pointer;">Edit</button>
        <form method="POST" class="inline" onsubmit="return confirm('Delete this event?')">
          <input type="hidden" name="csrf_token" value="<?=$csrfToken?>" />
          <input type="hidden" name="delete_event" value="1" />
          <input type="hidden" name="event_id" value="<?=htmlspecialchars($e['id'])?>" />
          <button type="submit" class="text-red-500 text-xs font-bold" style="border:none;background:none;cursor:pointer;">Delete</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
<script>
function openForm() {
  document.getElementById('eventForm').classList.toggle('hidden');
  document.getElementById('eventForm').scrollIntoView({behavior:'smooth'});
}
function editEvent(id, title, desc, type, start, end, discount, bg, active) {
  var f = document.getElementById('eventForm');
  f.classList.remove('hidden');
  document.getElementById('formTitle').textContent = 'Edit Event';
  document.getElementById('formId').value = id;
  document.getElementById('formTitleInput').value = title;
  document.getElementById('formDesc').value = desc;
  document.getElementById('formType').value = type;
  document.getElementById('formStart').value = start;
  document.getElementById('formEnd').value = end;
  document.getElementById('formDiscount').value = discount;
  document.getElementById('formBg').value = bg;
  document.getElementById('formActive').checked = active;
  document.querySelector('[name="add_event"]').name = 'edit_event';
  f.scrollIntoView({behavior:'smooth'});
}
</script>
</body>
</html>
