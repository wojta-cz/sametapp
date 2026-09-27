<?php
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$user = get_user_by_googleid($_SESSION['user']['google_id']);

require_once __DIR__ . '/includes/functions.php';

// Check if tutorial has been seen
$pdo = db();
$stmt = $pdo->prepare("SELECT tutorial_seen FROM users WHERE google_id = ?");
$stmt->execute([$_SESSION['user']['google_id']]);
$tutorial_seen = $stmt->fetchColumn();

if (!$tutorial_seen) {
    header('Location: tutorial.php');
    exit;
}

// Check time lock for regular players
$time_lock_active = is_time_lock_active();
$can_access = can_access_stations($user['role']);

if (empty($user['team_id'])) {
    // Handled by JavaScript
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <title>Dashboard — Samet Festival</title>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" type="text/css" href="./includes/fonts/helvetica/style.css">
  <style>
    * {
      -webkit-tap-highlight-color: transparent;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
      min-height: 100vh;
      padding-bottom: 90px;
    }
    
    .card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid rgba(0, 0, 0, 0.04);
    }
    
    .card:active {
      transform: scale(0.98);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .gradient-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
    }
    
    .gradient-success {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      box-shadow: 0 8px 24px rgba(17, 153, 142, 0.25);
    }
    
    .gradient-warning {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      box-shadow: 0 8px 24px rgba(240, 147, 251, 0.25);
    }
    
    .gradient-info {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      box-shadow: 0 8px 24px rgba(79, 172, 254, 0.25);
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      color: white;
      padding: 16px 28px;
      border-radius: 16px;
      font-weight: 600;
      font-size: 16px;
      border: none;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 6px 20px rgba(234, 102, 102, 0.35);
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      text-decoration: none;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(234, 102, 102, 0.4);
    }
    
    .btn-primary:active {
      transform: translateY(0);
      box-shadow: 0 4px 12px rgba(234, 102, 102, 0.3);
    }
    
    .nav-bar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-top: 1px solid rgba(0, 0, 0, 0.06);
      box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
      z-index: 1000;
      padding-bottom: env(safe-area-inset-bottom);
    }
    
    .nav-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 12px 0;
      text-decoration: none;
      color: #9ca3af;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      gap: 6px;
      position: relative;
    }
    
    .nav-item.active {
      color: #ea6666ff;
    }
    
    .nav-item.active::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 40px;
      height: 3px;
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      border-radius: 0 0 3px 3px;
    }
    
    .nav-item svg {
      width: 26px;
      height: 26px;
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .nav-item.active svg {
      transform: scale(1.1);
    }
    
    .nav-label {
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }
    
    .stat-card {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
      border-radius: 16px;
      padding: 20px;
      text-align: center;
      border: 1px solid rgba(102, 126, 234, 0.15);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .stat-card:active {
      transform: scale(0.95);
    }
    
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 8px 16px;
      border-radius: 24px;
      font-size: 14px;
      font-weight: 600;
      gap: 8px;
      border: 1px solid rgba(0, 0, 0, 0.08);
    }
    
    .fade-in {
      animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .header-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 0 0 32px 32px;
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
    }

    .text-red {
      color: red;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <div class="px-5 pb-5 pt-5 pb-2 bg-gradient-to-r from-gray-50 to-slate-100">
    <div class="flex justify-between items-center">
      <div>
        <img class="h-12" src="./includes/images/logo.png">
      </div>
        <div class="flex flex-row gap-5">
          <?php if ($user['role'] === 'admin') {
            echo '
            <a href="./admin" class=" hover:text-white transition-colors rounded-xl hover:bg-white/10">
            <svg class="w-7 h-7" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" >
  <path fill-rule="evenodd" d="M17 10v1.126c.367.095.714.24 1.032.428l.796-.797 1.415 1.415-.797.796c.188.318.333.665.428 1.032H21v2h-1.126c-.095.367-.24.714-.428 1.032l.797.796-1.415 1.415-.796-.797a3.979 3.979 0 0 1-1.032.428V20h-2v-1.126a3.977 3.977 0 0 1-1.032-.428l-.796.797-1.415-1.415.797-.796A3.975 3.975 0 0 1 12.126 16H11v-2h1.126c.095-.367.24-.714.428-1.032l-.797-.796 1.415-1.415.796.797A3.977 3.977 0 0 1 15 11.126V10h2Zm.406 3.578.016.016c.354.358.574.85.578 1.392v.028a2 2 0 0 1-3.409 1.406l-.01-.012a2 2 0 0 1 2.826-2.83ZM5 8a4 4 0 1 1 7.938.703 7.029 7.029 0 0 0-3.235 3.235A4 4 0 0 1 5 8Zm4.29 5H7a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h6.101A6.979 6.979 0 0 1 9 15c0-.695.101-1.366.29-2Z" clip-rule="evenodd"/>
</svg>


            </a>
            ';
          }?>
          <?php if ($user['role'] === 'admin' || $user['role'] === 'organizer') {
            echo '
            <a href="./organizer" class=" hover:text-white transition-colors rounded-xl hover:bg-white/10">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" >
  <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M2.9917 4.9834V18.917M9.96265 4.9834V18.917M15.9378 4.9834V18.917m2.9875-13.9336V18.917"/>
  <path stroke="currentColor" stroke-linecap="round" d="M5.47925 4.4834V19.417m1.9917-14.9336V19.417M21.4129 4.4834V19.417M13.4461 4.4834V19.417"/>
</svg>
            </a>
            ';
          }?>
         <a href="./logout" class=" hover:text-white transition-colors rounded-xl hover:bg-white/10">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        </a> 
      </div>
      
    </div>
  </div>

  <main class="px-5 pb-5 mt-6">
    <?php if ($time_lock_active && !$can_access): ?>
    <!-- Time Lock Message for Regular Players -->
    <div class="card p-10 text-center fade-in">
      <svg class="w-24 h-24 mx-auto mb-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
      </svg>
      <h3 class="text-2xl font-bold text-gray-800 mb-3">Aplikace je časově uzamčena</h3>
      <p class="text-gray-600 mb-4 text-lg">Stanoviště a dashboard budou dostupné po spuštění akce.</p>
      <p class="text-gray-500">Můžete si zatím vytvořit nebo připojit se k týmu v záložce "Tým".</p>
    </div>
    <?php else: ?>
    <!-- Status Section -->
    <div id="teamStatus" class="mb-5 fade-in">
      <div class="card p-8 text-center">
        <div class="animate-spin inline-block w-10 h-10 border-4 border-purple-500 border-t-transparent rounded-full"></div>
        <p class="mt-3 text-gray-600 font-medium">Načítám data...</p>
      </div>
    </div>

    <!-- Quick Stats -->
    <div id="quickStats" class="hidden mb-5 fade-in"></div>

    <!-- Recent History -->
    <div id="historySection" class="hidden fade-in">
      <h2 class="text-gray-800 text-xl font-bold mb-4 px-1">Poslední aktivity</h2>
      <div id="historyList" class="space-y-3"></div>
    </div>
    <?php endif; ?>
  </main>

  <!-- Bottom Navigation -->
  <nav class="nav-bar">
    <div class="flex justify-around items-center">
      <a href="./index" class="nav-item active">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
        </svg>
        <span class="nav-label">Domů</span>
      </a>
      
      <a href="./team" class="nav-item">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
        </svg>
        <span class="nav-label">Tým</span>
      </a>
      
      <a href="./qr" class="nav-item">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd"/>
          <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z"/>
        </svg>
        <span class="nav-label">QR kód</span>
      </a>
      
      <a href="./stations" class="nav-item">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
        </svg>
        <span class="nav-label">Stanoviště</span>
      </a>
      
      <a href="./leaderboard" class="nav-item">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
        </svg>
        <span class="nav-label">Žebříček</span>
      </a>
    </div>
  </nav>

<script>
const canAccess = <?= $can_access ? 'true' : 'false' ?>;
let refreshInterval = null;

async function loadDashboard() {
  if (!canAccess) {
    return; // Don't load dashboard if time lock is active
  }
  
  try {
    const response = await fetch('./api/team_dashboard.php');
    const data = await response.json();
    
    if (!data.success) {
      showError(data.message);
      return;
    }
    
    if (!data.has_team) {
      showNoTeam();
      return;
    }
    
    renderTeamStatus(data);
    renderQuickStats(data);
    renderHistory(data.history);
    
  } catch (error) {
    showError('Chyba při načítání dat: ' + error.message);
  }
}

function showNoTeam() {
  document.getElementById('teamStatus').innerHTML = `
    <div class="card p-10 text-center">
      <svg class="w-24 h-24 mx-auto mb-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      <h3 class="text-2xl font-bold text-gray-800 mb-3">Nejste v žádném týmu</h3>
      <p class="text-gray-600 mb-8 text-lg">Pro účast v soutěži musíte vytvořit nebo se připojit k týmu.</p>
      <button onclick="location.href='./team'" class="btn-primary">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
        </svg>
        <span>Přejít na správu týmu</span>
      </button>
    </div>
  `;
}

function showError(message) {
  document.getElementById('teamStatus').innerHTML = `
    <div class="card p-10 text-center">
      <svg class="w-24 h-24 mx-auto mb-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <h3 class="text-2xl font-bold text-red-600 mb-3">Chyba</h3>
      <p class="text-gray-700 text-lg">${escapeHtml(message)}</p>
    </div>
  `;
}

function renderTeamStatus(data) {
  const statusEl = document.getElementById('teamStatus');
  let statusCard = '';
  
  if (data.status === 'completed') {
    statusCard = `
      <div class="card p-10 text-center gradient-success text-white">
        <svg class="w-28 h-28 mx-auto mb-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <h2 class="text-4xl font-bold mb-3">Gratulujeme!</h2>
        <p class="text-2xl mb-6 text-white/95">Máte všechna stanoviště hotová!</p>
        <div class="text-7xl font-bold mb-2">${data.team.points}</div>
        <div class="text-xl text-white/95">bodů</div>
      </div>
    `;
  } else if (data.status === 'working') {
    statusCard = `
      <div class="card p-7 gradient-info text-white">
        <div class="flex items-center gap-5 mb-5">
          <svg class="w-14 h-14 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
          </svg>
          <div class="flex-1">
            <h3 class="text-2xl font-bold">Pracujete na stanovišti</h3>
            <p class="text-white/95 text-lg mt-1">${escapeHtml(data.current_station.name)}</p>
          </div>
        </div>
        <div class="badge bg-white/25 text-white w-full justify-center backdrop-blur-sm">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
          </svg>
          <span>Čeká se na vyhodnocení</span>
        </div>
      </div>
    `;
  } else if (data.status === 'assigned') {
    statusCard = `
      <div class="card p-7 bg-gradient-to-r from-red-400 to-red-500 text-white">
        <div class="flex items-center gap-5 mb-5">
          <svg class="w-14 h-14 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
          </svg>
          <div class="flex-1">
            <p class="text-base text-white/95">Máte přiřazené stanoviště:</p>
            <h3 class="text-2xl font-bold">${escapeHtml(data.assigned_station.name)}</h3>
            <p class="text-base text-white/95 mt-1">${escapeHtml(data.assigned_station.location)}</p>
          </div>
        </div>
        <a href="./station_detail?id=${data.assigned_station.id}" class="badge bg-white/25 text-white w-full justify-center backdrop-blur-sm cursor-pointer hover:bg-white/35 transition-all">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span>Přejít na stanoviště</span>
        </a>
      </div>
    `;
  } else {
    statusCard = `
      <div class="card p-7">
        <div class="flex items-center gap-5 mb-5">
          <svg class="w-14 h-14 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
          </svg>
          <div class="flex-1">
            <h3 class="text-2xl font-bold text-gray-800">Nemáte žádné stanoviště</h3>
            <p class="text-gray-600 text-lg">Najděte si další úkol</p>
          </div>
        </div>
        <button onclick="assignNextStation()" class="btn-primary">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span>Přiřadit stanoviště automaticky</span>
        </button>
      </div>
    `;
  }
  
  statusEl.innerHTML = statusCard;
}

function renderQuickStats(data) {
  const statsEl = document.getElementById('quickStats');
  statsEl.classList.remove('hidden');
  
  const progress = data.progress || {completed: 0, total: 10};
  const percentage = Math.round((progress.completed / progress.total) * 100);
  
  statsEl.innerHTML = `
    <div class="grid grid-cols-3 gap-4">
      <div class="stat-card col-span-2">
        <div class="text-sm text-gray-600 font-medium">Celkem bodů</div>
        <div class="text-5xl font-bold text-red-600 mb-1">${data.team.points}</div>
      </div>
      <div class="stat-card" style="padding-left: 15px; padding-right: 15px">
              <div class="text-sm text-gray-600 font-medium">Stanoviště</div>
              <div class="text-4xl font-bold text-red-600 mb-1">${progress.completed}/${progress.total}</div>
      </div>
    </div>
  `;
}

function renderHistory(history) {
  if (!history || history.length === 0) {
    document.getElementById('historySection').classList.add('hidden');
    return;
  }
  
  document.getElementById('historySection').classList.remove('hidden');
  
  const historyHtml = history.slice(0, 5).map(item => {
    const statusConfig = {
      'done': {bg: 'bg-green-100', text: 'text-green-800', label: 'Dokončeno', border: 'border-green-200'},
      'failed': {bg: 'bg-red-100', text: 'text-red-800', label: 'Nesplněno', border: 'border-red-200'},
      'default': {bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Čeká', border: 'border-yellow-200'}
    };
    
    const status = statusConfig[item.status] || statusConfig.default;
    const date = new Date(item.created_at);
    const timeStr = date.toLocaleTimeString('cs-CZ', {hour: '2-digit', minute: '2-digit'});
    
    return `
      <div class="card p-5">
        <div class="flex justify-between items-start mb-3">
          <div class="font-bold text-gray-800 text-lg">${escapeHtml(item.station_name)}</div>
          <div class="text-sm text-gray-500 font-medium">${timeStr}</div>
        </div>
        <div class="flex justify-between items-center">
          <span class="badge ${status.bg} ${status.text} ${status.border}">
            ${status.label}
          </span>
          <span class="font-bold text-2xl ${item.points > 0 ? 'text-green-600' : 'text-gray-400'}">
            ${item.points > 0 ? '+' : ''}${item.points}
          </span>
        </div>
      </div>
    `;
  }).join('');
  
  document.getElementById('historyList').innerHTML = historyHtml;
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

async function assignNextStation() {
  try {
    const response = await fetch('./api/assign_next_station.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'}
    });
    
    const data = await response.json();
    
    if (data.success) {
      alert('✅ ' + data.message + '\n\nPřiřazeno stanoviště: ' + data.station.name);
      window.location.reload();
    } else {
      alert('❌ ' + data.message);
    }
  } catch (error) {
    alert('❌ Chyba při přiřazování stanoviště: ' + error.message);
  }
}

function startAutoRefresh() {
  if (canAccess) {
    refreshInterval = setInterval(loadDashboard, 30000);
  }
}

function stopAutoRefresh() {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
}

if (canAccess) {
  loadDashboard();
  startAutoRefresh();
}

window.addEventListener('beforeunload', stopAutoRefresh);
</script>
</body>
</html>