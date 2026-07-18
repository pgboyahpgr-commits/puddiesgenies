<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/menu-loader.php';
requireAdmin();

$orders = loadJSON(__DIR__ . '/../data/orders.json');
$streamConfig = loadJSON(__DIR__ . '/../data/stream_config.json');
$tables = loadJSON(__DIR__ . '/../data/tables.json');
$menuData = loadMenuFromGist();
$allDishes = $menuData ? getAllDishes($menuData) : [];

$today = date('Y-m-d');
$thisWeek = date('Y-m-d', strtotime('-7 days'));

$todayOrders = array();
$weekOrders = array();
$pendingOrders = array();
$totalRevenue = 0;
$todayRevenue = 0;
$weekRevenue = 0;
$dishCounters = array();

foreach ($orders as $o) {
  $d = substr($o['time'] ?? '', 0, 10);
  if ($d === $today) { $todayOrders[] = $o; $todayRevenue += intval($o['total'] ?? 0); }
  if ($d >= $thisWeek) { $weekOrders[] = $o; $weekRevenue += intval($o['total'] ?? 0); }
  if (($o['status'] ?? '') === 'pending') $pendingOrders[] = $o;
  $totalRevenue += intval($o['total'] ?? 0);
  foreach ($o['items'] ?? [] as $item) {
    $n = $item['name'] ?? 'Unknown';
    $dishCounters[$n] = ($dishCounters[$n] ?? 0) + ($item['qty'] ?? 1);
  }
}
arsort($dishCounters);
$popularDishes = array_slice($dishCounters, 0, 10);

// Orders by day for chart
$dayLabels = array();
$dayCounts = array();
for ($i = 6; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-$i days"));
  $dayLabels[] = date('D', strtotime($d));
  $dayCounts[] = count(array_filter($orders, function($o) use ($d) { return substr($o['time'] ?? '', 0, 10) === $d; }));
}
$maxDay = max($dayCounts) ?: 1;

$streamOn = ($streamConfig['video_status'] ?? '') === 'on' && !empty($streamConfig['video_url'] ?? '');
$activeTables = count(array_filter($tables['tables'] ?? [], function($t) { return !empty($t['token']); }));
$totalItems = count($allDishes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Dashboard — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet" />
<style>
body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1,h2,h3{font-family:'Fredoka',sans-serif;}
.stat-card{background:rgba(255,255,255,0.8);backdrop-filter:blur(12px);border-radius:20px;padding:1.2rem;box-shadow:0 4px 20px rgba(0,0,0,0.04);border:1px solid rgba(255,255,255,0.3);transition:transform 0.2s;}
.stat-card:hover{transform:translateY(-3px);}
.chart-bar{height:100%;border-radius:8px 8px 0 0;transition:height 0.6s cubic-bezier(0.34,1.56,0.64,1);min-height:6px;}
</style>
</head>
<body>
<nav class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between flex-wrap gap-3">
  <div>
    <a href="/admins/dashboard.php" style="text-decoration:none;"><span class="text-2xl font-bold" style="color:var(--text);">Smak<span style="color:#f68e9a;">AI</span></span></a>
    <span class="text-sm text-gray-400 ml-2" data-translate>Admin</span>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="/admins/dashboard.php" class="px-4 py-2 rounded-full text-sm font-semibold" style="background:#f68e9a;color:#fff;text-decoration:none;" data-translate>Dashboard</a>
    <a href="/admins/orders.php" class="px-4 py-2 rounded-full text-sm font-semibold no-underline" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>Orders</a>
    <a href="/admins/menu.php" class="px-4 py-2 rounded-full text-sm font-semibold no-underline" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>Menu</a>
    <a href="/admins/tables.php" class="px-4 py-2 rounded-full text-sm font-semibold no-underline" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>Tables</a>
    <a href="/admins/stream.php" class="px-4 py-2 rounded-full text-sm font-semibold no-underline" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>Stream</a>
    <a href="/admins/talk.php" class="px-4 py-2 rounded-full text-sm font-semibold no-underline" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>Chat</a>
    <a href="/admins/events.php" class="px-4 py-2 rounded-full text-sm font-semibold no-underline" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>🎉 Events</a>
    <a href="/admins/settings.php" class="px-4 py-2 rounded-full text-sm font-semibold no-underline" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>Settings</a>
    <a href="/admins/logout.php" class="px-4 py-2 rounded-full text-sm font-semibold no-underline" style="background:rgba(255,255,255,0.6);border:1px solid #ddd;color:var(--text);" data-translate>Logout</a>
  </div>
</nav>
<main class="max-w-7xl mx-auto px-4 pb-12">
  <h1 class="text-3xl font-bold mb-6" style="color:var(--text);" data-translate>📊 Dashboard</h1>

  <!-- Stats Grid -->
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <div class="stat-card text-center">
      <p class="text-xs text-gray-400 uppercase tracking-wide" data-translate>Today Orders</p>
      <p class="text-3xl font-bold mt-1" style="color:#f68e9a;"><?= count($todayOrders) ?></p>
      <p class="text-xs text-gray-400 mt-1"><?= count($weekOrders) ?> this week</p>
    </div>
    <div class="stat-card text-center">
      <p class="text-xs text-gray-400 uppercase tracking-wide" data-translate>Pending</p>
      <p class="text-3xl font-bold mt-1" style="color:#F59E0B;"><?= count($pendingOrders) ?></p>
      <p class="text-xs text-gray-400 mt-1" data-translate>Needs attention</p>
    </div>
    <div class="stat-card text-center">
      <p class="text-xs text-gray-400 uppercase tracking-wide" data-translate>Revenue</p>
      <p class="text-3xl font-bold mt-1" style="color:#10B981;">₹<?= $todayRevenue ?></p>
      <p class="text-xs text-gray-400 mt-1">₹<?= $weekRevenue ?> this week</p>
    </div>
    <div class="stat-card text-center">
      <p class="text-xs text-gray-400 uppercase tracking-wide" data-translate>Stream</p>
      <p class="text-3xl font-bold mt-1" style="color:<?= $streamOn ? '#10B981' : '#EF4444' ?>;"><?= $streamOn ? 'LIVE' : 'OFF' ?></p>
      <p class="text-xs text-gray-400 mt-1"><a href="/admins/stream.php" style="color:#f68e9a;" data-translate>Manage</a></p>
    </div>
    <div class="stat-card text-center">
      <p class="text-xs text-gray-400 uppercase tracking-wide" data-translate>Tables</p>
      <p class="text-3xl font-bold mt-1" style="color:#6366F1;"><?= count($tables['tables'] ?? []) ?></p>
      <p class="text-xs text-gray-400 mt-1"><?= $activeTables ?> active</p>
    </div>
    <div class="stat-card text-center">
      <p class="text-xs text-gray-400 uppercase tracking-wide" data-translate>Menu</p>
      <p class="text-3xl font-bold mt-1" style="color:#EC4899;"><?= $totalItems ?></p>
      <p class="text-xs text-gray-400 mt-1" data-translate>dishes</p>
    </div>
  </div>

  <!-- Chart + Popular Dishes -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Weekly Orders Chart -->
    <div class="bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100">
      <h3 class="font-bold mb-4" data-translate>📈 Orders This Week</h3>
      <div class="flex items-end gap-3" style="height:160px;">
        <?php for ($i = 0; $i < 7; $i++): ?>
        <div class="flex-1 flex flex-col items-center gap-1">
          <div class="w-full flex flex-col items-center justify-end" style="height:140px;">
            <div class="chart-bar w-full max-w-[32px]" style="height:<?= max(6, round($dayCounts[$i] / $maxDay * 130)) ?>px;background:<?= $dayCounts[$i] > 0 ? '#f68e9a' : '#eee' ?>;"></div>
          </div>
          <span class="text-xs text-gray-400"><?= $dayLabels[$i] ?></span>
          <span class="text-xs font-bold"><?= $dayCounts[$i] ?></span>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Popular Dishes -->
    <div class="bg-white/80 backdrop-blur rounded-2xl p-5 shadow border border-gray-100">
      <h3 class="font-bold mb-4" data-translate>⭐ Popular Dishes</h3>
      <?php if ($popularDishes): ?>
      <div class="space-y-2">
        <?php 
        $rank = 1;
        $maxPop = reset($popularDishes) ?: 1;
        foreach ($popularDishes as $name => $count): 
          $pct = round($count / $maxPop * 100);
        ?>
        <div>
          <div class="flex justify-between text-sm mb-1">
            <span class="font-medium"><?= htmlspecialchars($name) ?></span>
            <span class="text-gray-400"><?= $count ?> ordered</span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-full rounded-full" style="width:<?= $pct ?>%;background:<?= $rank === 1 ? '#f68e9a' : ($rank <= 3 ? '#538bdf' : '#f7b6bf') ?>;transition:width 0.6s;"></div>
          </div>
        </div>
        <?php $rank++; endforeach; ?>
      </div>
      <?php else: ?>
      <p class="text-gray-400 text-sm text-center py-4" data-translate>No orders yet — data will appear here</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="bg-white/80 backdrop-blur rounded-2xl shadow border border-gray-100 overflow-hidden mb-8">
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
      <h3 class="font-bold" data-translate>📋 Recent Orders</h3>
      <a href="/admins/orders.php" class="text-sm font-semibold" style="color:#f68e9a;" data-translate>View All →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="bg-gray-50 text-gray-500">
          <th class="text-left p-3" data-translate>Order</th><th class="text-left p-3" data-translate>Table</th><th class="text-left p-3" data-translate>Items</th><th class="text-left p-3" data-translate>Total</th><th class="text-left p-3" data-translate>Status</th><th class="text-left p-3" data-translate>Time</th>
        </tr></thead>
        <tbody>
          <?php $recent = array_slice(array_reverse($orders), 0, 8); ?>
          <?php foreach ($recent as $o): ?>
          <tr class="border-t border-gray-100">
            <td class="p-3 font-mono text-xs text-gray-500">#<?= htmlspecialchars(substr($o['order_id'] ?? '', 0, 8)) ?></td>
            <td class="p-3 font-medium">Table <?= $o['table'] ?></td>
            <td class="p-3"><?= count($o['items']) ?> items</td>
            <td class="p-3 font-bold">₹<?= intval($o['total'] ?? 0) ?></td>
            <td class="p-3">
              <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= ($o['status'] ?? '') === 'delivered' ? 'bg-green-100 text-green-700' : (($o['status'] ?? '') === 'preparing' ? 'bg-yellow-100 text-yellow-700' : 'bg-orange-100 text-orange-700') ?>">
                <?= ucfirst($o['status'] ?? 'Pending') ?>
              </span>
            </td>
            <td class="p-3 text-gray-400 text-xs"><?= htmlspecialchars(substr($o['time'] ?? '', 11, 5)) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recent)): ?>
          <tr><td colspan="6" class="p-8 text-center text-gray-400" data-translate>No orders yet — they'll appear here</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>
