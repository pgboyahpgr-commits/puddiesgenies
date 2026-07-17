<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
  header('Location: /admins/dashboard.php');
  exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';
  $adminData = loadJSON(__DIR__ . '/../data/admin.json');
  if ($username === ($adminData['id'] ?? '') && password_verify($password, $adminData['password'] ?? '')) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    if (password_needs_rehash($adminData['password'], PASSWORD_BCRYPT)) {
      $adminData['password'] = password_hash($password, PASSWORD_BCRYPT);
      saveJSON(__DIR__ . '/../data/admin.json', $adminData);
    }
    header('Location: /admins/dashboard.php');
    exit;
  } elseif (isset($adminData['password']) && !password_get_info($adminData['password'])['algo']) {
    if ($username === ($adminData['id'] ?? '') && $password === $adminData['password']) {
      $_SESSION['admin_logged_in'] = true;
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      $adminData['password'] = password_hash($password, PASSWORD_BCRYPT);
      saveJSON(__DIR__ . '/../data/admin.json', $adminData);
      header('Location: /admins/dashboard.php');
      exit;
    }
  }
  $error = 'Invalid credentials';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Login — SmakAI</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet" />
<style>
  body { font-family: 'Nunito', sans-serif; background: #FFF8F0; }
  h1,h2,h3 { font-family: 'Fredoka', sans-serif; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="max-w-sm w-full">
    <div class="text-center mb-6">
      <h1 class="text-4xl font-bold" style="color:#2D3436;">Smak<span style="color:#FF6B6B;">AI</span></h1>
      <p class="text-gray-400 mt-1">Admin Panel</p>
    </div>
    <div class="bg-white/80 backdrop-blur rounded-3xl p-6 shadow-lg border border-gray-100">
      <?php if ($error): ?>
        <div class="bg-red-50 text-red-500 p-3 rounded-xl text-sm mb-4 text-center"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <input type="text" name="username" placeholder="Admin ID" required class="w-full px-4 py-3 rounded-full border-2 border-gray-200 bg-white mb-3 outline-none focus:border-[#FF6B6B] text-sm" />
        <input type="password" name="password" placeholder="Password" required class="w-full px-4 py-3 rounded-full border-2 border-gray-200 bg-white mb-4 outline-none focus:border-[#FF6B6B] text-sm" />
        <button type="submit" class="w-full py-3 rounded-full font-bold text-white" style="background:#FF6B6B;">Login</button>
      </form>
    </div>
  </div>
</body>
</html>
