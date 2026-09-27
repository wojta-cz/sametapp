<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login(['admin']);
$user = $_SESSION['user'];
$pdo = db();

// Statistiky
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'teams' => $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn(),
    'stations' => $pdo->query("SELECT COUNT(*) FROM stations WHERE active=1")->fetchColumn(),
    'results' => $pdo->query("SELECT COUNT(*) FROM results WHERE status='pending'")->fetchColumn()
];
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard — Samet Festival</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <!-- Navigation -->
  <nav class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <h1 class="text-xl font-bold text-gray-900">🎯 Admin Panel</h1>
        </div>
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-600"><?=htmlspecialchars($user['name'])?></span>
          <a href="../logout" class="text-sm text-red-600 hover:text-red-800">Odhlásit</a>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
          </div>
          <div class="ml-5">
            <p class="text-sm font-medium text-gray-500">Uživatelé</p>
            <p class="text-2xl font-semibold text-gray-900"><?=$stats['users']?></p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
          </div>
          <div class="ml-5">
            <p class="text-sm font-medium text-gray-500">Týmy</p>
            <p class="text-2xl font-semibold text-gray-900"><?=$stats['teams']?></p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
          </div>
          <div class="ml-5">
            <p class="text-sm font-medium text-gray-500">Stanice</p>
            <p class="text-2xl font-semibold text-gray-900"><?=$stats['stations']?></p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="ml-5">
            <p class="text-sm font-medium text-gray-500">Čekající</p>
            <p class="text-2xl font-semibold text-gray-900"><?=$stats['results']?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Live Dashboard - Zvýrazněný -->
    <div class="mb-8">
      <a href="./live_dashboard" class="block bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg hover:shadow-xl transition p-8 text-white">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold mb-2">📊 Live Dashboard</h2>
            <p class="text-blue-100">Sledujte týmy v reálném čase a spravujte přiřazování stanovišť</p>
          </div>
          <div class="text-6xl opacity-50">→</div>
        </div>
      </a>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <a href="./users" class="block bg-white rounded-lg shadow hover:shadow-lg transition p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">👥 Správa uživatelů</h3>
        <p class="text-sm text-gray-600">Spravujte uživatele a jejich role</p>
      </a>

      <a href="./teams" class="block bg-white rounded-lg shadow hover:shadow-lg transition p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">🏆 Správa týmů</h3>
        <p class="text-sm text-gray-600">Spravujte týmy a jejich body</p>
      </a>

      <a href="./stations" class="block bg-white rounded-lg shadow hover:shadow-lg transition p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">📍 Správa stanic</h3>
        <p class="text-sm text-gray-600">Přidávejte a upravujte stanice</p>
      </a>

      <a href="./results" class="block bg-white rounded-lg shadow hover:shadow-lg transition p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">✅ Schvalování výsledků</h3>
        <p class="text-sm text-gray-600">Schvalujte nebo zamítejte výsledky</p>
      </a>

      <a href="./settings" class="block bg-white rounded-lg shadow hover:shadow-lg transition p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">⚙️ Nastavení</h3>
        <p class="text-sm text-gray-600">Globální nastavení aplikace</p>
      </a>

      <a href="../dashboard" class="block bg-gray-100 rounded-lg shadow hover:shadow-lg transition p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">← Zpět na Dashboard</h3>
        <p class="text-sm text-gray-600">Návrat do hlavního menu</p>
      </a>
    </div>
  </div>
</body>
</html>