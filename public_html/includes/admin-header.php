<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$csrfToken = csrfToken();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>SmakAI Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="/assets/style.css" />
<script src="https://cdn.tailwindcss.com"></script>
<script src="/assets/translate.js"></script>
<script src="/assets/admin-notifications.js"></script>
<script>function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('smak_dark',document.body.classList.contains('dark')?'1':'0')}if(localStorage.getItem('smak_dark')==='1')document.body.classList.add('dark')</script>
<style>
body{margin:0;min-height:100vh;background:#fff;display:flex;flex-direction:column}.admin-page{flex:1}
nav{background:#fff;border-bottom:1px solid #e8e0e2;position:sticky;top:0;z-index:100;backdrop-filter:blur(12px)}
</style>
</head>
<body>
<nav>
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between flex-wrap gap-2">
    <div class="flex items-center gap-3">
      <a href="/admins/dashboard.php" style="text-decoration:none;display:flex;align-items:center;gap:8px;">
        <span style="font-size:1.4rem;font-weight:800;font-family:'Fredoka',sans-serif;background:linear-gradient(135deg,#f68e9a,#4138c2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;" data-translate>SmakAI</span>
        <span style="font-size:0.72rem;color:#888;font-weight:600;background:#faf6f7;padding:3px 12px;border-radius:100px;" data-translate>Admin</span>
      </a>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
      <?php $navItems = [
        'dashboard.php' => '📊 Dashboard',
        'orders.php' => '📦 Orders<span id="orderBadge" style="display:none;background:#f68e9a;color:#fff;font-size:0.6rem;padding:1px 6px;border-radius:100px;margin-left:2px;"></span>',
        'menu.php' => '📋 Menu',
        'tables.php' => '🪑 Tables',
        'stream.php' => '📺 Stream',
        'talk.php' => '💬 Chat',
        'todays-special.php' => '🌟 Special',
        'ai-assistant.php' => '🤖 AI',
        'events.php' => '🎉 Events',
        'settings.php' => '⚙️ Settings',
      ];
      foreach ($navItems as $file => $label): ?>
      <a href="/admins/<?=$file?>" class="nav-link <?=$currentPage===$file?'active':''?>" data-translate><?=$label?></a>
      <?php endforeach; ?>
      <a href="/admins/logout.php" class="nav-link" style="color:#ae2824;" data-translate>🚪 Logout</a>
      <button id="darkToggle" class="dark-mode-toggle" onclick="toggleDark()" title="Toggle dark mode"><span class="light-icon">🌙</span><span class="dark-icon">☀️</span></button>
      <select id="langSelect" class="lang-select">
        <option value="en" data-translate>🌐 English</option>
        <option value="hi" data-translate>🇮🇳 Hindi</option>
        <option value="ta" data-translate>🇮🇳 Tamil</option>
        <option value="te" data-translate>🇮🇳 Telugu</option>
        <option value="bn" data-translate>🇧🇩 Bengali</option>
        <option value="kn" data-translate>🇮🇳 Kannada</option>
        <option value="ml" data-translate>🇮🇳 Malayalam</option>
        <option value="gu" data-translate>🇮🇳 Gujarati</option>
        <option value="mr" data-translate>🇮🇳 Marathi</option>
        <option value="pa" data-translate>🇮🇳 Punjabi</option>
        <option value="ur" data-translate>🇵🇰 Urdu</option>
        <option value="fr" data-translate>🇫🇷 French</option>
        <option value="de" data-translate>🇩🇪 German</option>
        <option value="es" data-translate>🇪🇸 Spanish</option>
        <option value="zh-CN" data-translate>🇨🇳 Chinese</option>
        <option value="ar" data-translate>🇸🇦 Arabic</option>
      </select>
    </div>
  </div>
</nav>
<div class="admin-page">
