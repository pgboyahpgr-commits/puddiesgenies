<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/menu-loader.php';
requireAdmin();
$result = forceRefreshMenu();
if ($result) {
  $count = count(getAllDishes($result));
  $msg = "✅ Menu refreshed! $count dishes loaded from Gist.";
} else {
  $msg = "❌ Failed to fetch menu from Gist. Check the cache or Gist URL.";
}
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Sync Menu — SmakAI Admin</title>
<script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Fredoka&family=Nunito&display=swap" rel="stylesheet"/>
<style>body{font-family:'Nunito',sans-serif;background:#FFF8F0;}h1{font-family:'Fredoka',sans-serif;}</style>
<meta http-equiv="refresh" content="2;url=/admins/settings.php">
</head>
<body>
<div class="max-w-xl mx-auto px-4 py-12 text-center">
  <div class="bg-white/80 backdrop-blur rounded-3xl p-8 shadow-lg border border-gray-100">
    <h1 class="text-2xl font-bold mb-4" style="color:var(--text);" data-translate>🔄 Sync Menu</h1>
    <p class="text-sm"><?=$msg?></p>
    <p class="text-xs text-gray-400 mt-3" data-translate>Redirecting back to Settings...</p>
  </div>
</div>
</body>
</html>
