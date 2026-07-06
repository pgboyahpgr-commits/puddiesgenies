<?php
session_start();
$tableNum = $_SESSION['table'] ?? 0;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>SmakAI — Smart Restaurant</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<style>
  body { font-family: 'Nunito', sans-serif; background: #FFF8F0; overflow-x: hidden; }
  h1,h2,h3,h4 { font-family: 'Fredoka', sans-serif; }
  .blob-bg { position: fixed; border-radius: 50%; filter: blur(60px); opacity: 0.15; z-index: -1; pointer-events: none; }
  .blob-1 { width: 400px; height: 400px; background: #FF6B6B; top: -100px; right: -100px; }
  .blob-2 { width: 300px; height: 300px; background: #4ECDC4; bottom: -50px; left: -80px; }
  .blob-3 { width: 250px; height: 250px; background: #FFE66D; top: 40%; left: 60%; }
  @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
  .float-anim { animation: float 4s ease-in-out infinite; }
  .portal-card {
    background: rgba(255,255,255,0.7); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.3); border-radius: 28px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.06);
    transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s;
    cursor: pointer; text-decoration: none; display: flex; flex-direction: column;
    align-items: center; justify-content: center; padding: 2rem 1rem;
    min-height: 160px; color: #2D3436;
  }
  .portal-card:hover { transform: translateY(-6px); box-shadow: 0 16px 48px rgba(0,0,0,0.12); }
  .portal-card:active { transform: scale(0.96); }
  .portal-card .icon { font-size: 3rem; margin-bottom: 0.6rem; }
  .portal-card .label { font-family: 'Fredoka', sans-serif; font-size: 1.1rem; font-weight: 600; }
  .portal-card .sub { font-size: 0.8rem; color: #888; margin-top: 0.2rem; }
</style>
</head>
<body>
<div class="blob-bg blob-1"></div>
<div class="blob-bg blob-2"></div>
<div class="blob-bg blob-3"></div>

<div class="max-w-lg mx-auto px-4 py-8 text-center">
  <div class="mb-8">
    <div class="text-6xl mb-2 float-anim">🍛</div>
    <h1 class="text-5xl font-bold" style="color:#2D3436;">Smak<span style="color:#FF6B6B;">AI</span></h1>
    <p class="text-gray-500 mt-1">Smart Restaurant Experience</p>
    <?php if ($tableNum > 0): ?>
    <div class="inline-block mt-3 px-5 py-2 rounded-full font-bold text-sm" style="background:#4ECDC4;color:#fff;">
      🪑 Table #<?= $tableNum ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="grid grid-cols-2 gap-4">
    <a href="/menu.php" class="portal-card">
      <div class="icon">📋</div>
      <div class="label">Menu</div>
      <div class="sub">Browse all dishes</div>
    </a>
    <a href="/akinator.php" class="portal-card">
      <div class="icon">🤖</div>
      <div class="label">AI Waiter</div>
      <div class="sub">Let me guess!</div>
    </a>
    <a href="/food-vlogs.php" class="portal-card">
      <div class="icon">🎬</div>
      <div class="label">Food Vlogs</div>
      <div class="sub">Watch & discover</div>
    </a>
    <a href="/kitchen.php" class="portal-card">
      <div class="icon">📺</div>
      <div class="label">Live Kitchen</div>
      <div class="sub">See food being made</div>
    </a>
    <a href="/chat.php" class="portal-card">
      <div class="icon">💬</div>
      <div class="label">Talk to Manager</div>
      <div class="sub">Chat or call us</div>
    </a>
    <a href="/invoice.php" class="portal-card">
      <div class="icon">🧾</div>
      <div class="label">My Bill</div>
      <div class="sub">View your invoice</div>
    </a>
  </div>

  <div class="mt-8 text-xs text-gray-400">
    Scan the QR code at your table to get started
  </div>
</div>

<script>
gsap.from('.portal-card', { y: 40, opacity: 0, duration: 0.5, stagger: 0.08, ease: 'back.out(1.7)' });
gsap.from('.float-anim', { scale: 0, opacity: 0, duration: 0.6, ease: 'back.out(2)' });
</script>
</body>
</html>
