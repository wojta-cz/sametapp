<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = get_user_by_googleid($_SESSION['user']['google_id']);
$pdo = db();

// Check time lock for regular players
$time_lock_active = is_time_lock_active();
$can_access = can_access_stations($user['role']);

$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'assignment_mode'");
$stmt->execute();
$assignment_mode = $stmt->fetchColumn() ?: 'free';

// Get total count of active stations
$stmt = $pdo->query("SELECT COUNT(*) FROM stations WHERE active=1");
$total_stations = $stmt->fetchColumn();

$completed = [];
$assigned_station_id = null;
$team = null;

if ($user['team_id']) {
    $stmt = $pdo->prepare("SELECT station_id FROM results WHERE team_id = ? AND status = 'done'");
    $stmt->execute([$user['team_id']]);
    $completed = array_column($stmt->fetchAll(), 'station_id');
    
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$user['team_id']]);
    $team = $stmt->fetch();
    $assigned_station_id = $team['assigned_station_id'] ?? null;
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <title>Stanoviště — Samet Festival</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
    }
    
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 8px 16px;
      border-radius: 24px;
      font-size: 13px;
      font-weight: 600;
      gap: 6px;
      border: 1px solid rgba(0, 0, 0, 0.08);
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      color: white;
      padding: 14px 24px;
      border-radius: 16px;
      font-weight: 600;
      font-size: 15px;
      border: none;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
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
    
    .loading-spinner {
      display: inline-block;
      width: 40px;
      height: 40px;
      border: 4px solid #f3f4f6;
      border-top: 4px solid #ea6666;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="px-5 pb-5 pt-5 pb-2 bg-gradient-to-r from-gray-50 to-slate-100">
    <div class="flex justify-between items-center">
      <div>
        <img class="h-12" src="./includes/images/logo.png">
      </div>
        <div>
         <a href="./logout" class=" hover:text-white transition-colors rounded-xl hover:bg-white/10">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        </a> 
      </div>
      
    </div>
  </div>

  <div class="px-5 mt-3">
    <?php if ($time_lock_active && !$can_access): ?>
    <!-- Time Lock Message for Regular Players -->
    <div class="card p-10 text-center fade-in">
      <svg class="w-24 h-24 mx-auto mb-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
      </svg>
      <h3 class="text-2xl font-bold text-gray-800 mb-3">Aplikace je časově uzamčena</h3>
      <p class="text-gray-600 mb-4 text-lg">Stanoviště budou dostupná po spuštění akce.</p>
      <p class="text-gray-500">Můžete si zatím vytvořit nebo připojit se k týmu v záložce "Tým".</p>
    </div>
    <?php else: ?>
    
    <?php if (!$user['team_id']): ?>
    <div class="card p-6 mb-5 fade-in">
      <div class="flex items-center gap-4 mb-4">
        <svg class="w-12 h-12 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <p class="font-bold text-gray-800 text-lg">Nejste v žádném týmu</p>
          <p class="text-base text-gray-600">Připojte se k týmu pro účast</p>
        </div>
      </div>
      <button onclick="location.href='./team'" class="btn-primary">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
        </svg>
        <span>Přejít na týmy</span>
      </button>
    </div>
    <?php endif; ?>

    <?php if ($assignment_mode === 'assigned'): ?>
    <div class="card p-5 mb-5 fade-in">
      <div class="flex items-center gap-4">
        <svg class="w-10 h-10 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <p class="font-bold text-gray-800">Režim přiřazených stanovišť</p>
          <p class="text-sm text-gray-600 mt-1">
            <?php if ($assigned_station_id): ?>
              Vaše stanoviště je zvýrazněno
            <?php else: ?>
              Systém vám automaticky přiřadí stanoviště
            <?php endif; ?>
          </p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($assignment_mode === 'assigned' && $user['team_id'] && !$assigned_station_id): ?>
    <div class="card p-8 mb-5 text-center fade-in">
      <svg class="w-20 h-20 mx-auto mb-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
      </svg>
      <p class="text-gray-800 font-bold text-lg mb-2">Nemáte přiřazené stanoviště</p>
      <p class="text-gray-600 mb-5">Najděte si další úkol automaticky</p>
      <button onclick="assignNextStation()" class="btn-primary">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>Přiřadit stanoviště automaticky</span>
      </button>
    </div>
    <?php endif; ?>

    <div id="stationsContainer" class="space-y-4 pb-5">
      <!-- Stations will be loaded here dynamically -->
    </div>
    
    <div id="loadingIndicator" class="text-center py-8 hidden">
      <div class="loading-spinner mx-auto"></div>
      <p class="mt-3 text-gray-600">Načítám další stanoviště...</p>
    </div>
    
    <div id="noMoreStations" class="text-center py-8 hidden">
      <p class="text-gray-500">Žádná další stanoviště</p>
    </div>
    <?php endif; ?>
  </div>

  <!-- Bottom Navigation -->
  <nav class="nav-bar">
    <div class="flex justify-around items-center">
      <a href="./index" class="nav-item">
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
      
      <a href="./stations" class="nav-item active">
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
  const ITEMS_PER_PAGE = 10;
  let currentPage = 0;
  let isLoading = false;
  let hasMore = true;
  const assignedStationId = <?= $assigned_station_id ? $assigned_station_id : 'null' ?>;
  const assignmentMode = '<?= $assignment_mode ?>';
  const completed = <?= json_encode($completed) ?>;
  const totalStations = <?= $total_stations ?>;
  
  async function loadStations() {
    if (!canAccess) {
      return; // Don't load stations if time lock is active
    }
    
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    document.getElementById('loadingIndicator').classList.remove('hidden');
    
    try {
      const response = await fetch(`./api/stations_list.php?page=${currentPage}&limit=${ITEMS_PER_PAGE}`);
      const data = await response.json();
      
      if (data.success) {
        renderStations(data.stations);
        currentPage++;
        hasMore = data.has_more;
        
        if (!hasMore) {
          document.getElementById('noMoreStations').classList.remove('hidden');
        }
      }
    } catch (error) {
      console.error('Error loading stations:', error);
    } finally {
      isLoading = false;
      document.getElementById('loadingIndicator').classList.add('hidden');
    }
  }
  
  function renderStations(stations) {
    const container = document.getElementById('stationsContainer');
    
    // If first page and has assigned station, render it first
    if (currentPage === 0 && assignedStationId) {
      const assignedStation = stations.find(s => s.id == assignedStationId);
      if (assignedStation) {
        container.innerHTML = renderStation(assignedStation, true);
        stations = stations.filter(s => s.id != assignedStationId);
      }
    }
    
    stations.forEach(station => {
      container.innerHTML += renderStation(station, false);
    });
  }
  
  function renderStation(s, isFirst) {
    const isCompleted = completed.includes(s.id);
    const isAssigned = (s.id == assignedStationId);
    const isFull = s.capacity > 0 && s.current_occupancy >= s.capacity;
    let canAccessStation = true;
    
    if (assignmentMode === 'assigned' && !isAssigned && !isCompleted) {
      canAccessStation = false;
    }
    
    if (s.once_only && isCompleted) {
      canAccessStation = false;
    }
    
    if (isFull && !isCompleted) {
      canAccessStation = false;
    }
    
    const opacity = !canAccessStation ? 'opacity-60' : '';
    
    let assignedBadge = '';
    if (isAssigned) {
      assignedBadge = `
        <div class="mb-4 badge bg-red-100 text-red-800 border-red-200 w-full justify-center">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
          </svg>
          <span>Vaše přiřazené stanoviště</span>
        </div>
      `;
    }
    
    const completedIcon = isCompleted ? `
      <svg class="w-10 h-10 text-green-500 ml-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
      </svg>
    ` : '';
    
    const typeIcon = s.type === 'physical' ? `
      <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
    ` : `
      <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>
    `;
    
    const capacityBadge = s.capacity > 0 ? `
      <span class="badge ${isFull ? 'bg-red-100 text-red-800 border-red-200' : 'bg-gray-100 text-gray-800 border-gray-200'}">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
        </svg>
        <span>${s.current_occupancy}/${s.capacity}</span>
      </span>
    ` : '';
    
    const locationInfo = s.location ? `
      <div class="text-sm text-gray-500 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
        </svg>
        <span>${escapeHtml(s.location)}</span>
      </div>
    ` : '';
    
    let actionButton = '';
    if (isCompleted) {
      actionButton = `
        <div class="badge bg-green-100 text-green-800 border-green-200 w-full justify-center">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span>Splněno</span>
        </div>
      `;
    } else if (canAccessStation) {
      actionButton = `
        <a href="./station_detail?id=${s.id}" class="btn-primary">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
          </svg>
          <span>Přejít na stanoviště</span>
        </a>
      `;
    } else if (isFull) {
      actionButton = `
        <div class="badge bg-red-100 text-red-800 border-red-200 w-full justify-center">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
          <span>Stanoviště je plné</span>
        </div>
      `;
    } else if (assignmentMode === 'assigned') {
      actionButton = `
        <div class="badge bg-gray-100 text-gray-600 border-gray-200 w-full justify-center">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
          </svg>
          <span>Není vám přiřazeno</span>
        </div>
      `;
    }
    
    return `
      <div class="card p-6 fade-in ${opacity}">
        ${assignedBadge}
        
        <div class="flex justify-between items-start mb-4">
          <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-800 mb-2">${escapeHtml(s.name)}</h3>
            <p class="text-base text-gray-600 line-clamp-2">${escapeHtml(s.description)}</p>
          </div>
          ${completedIcon}
        </div>
        
        <div class="flex items-center gap-2 mb-4 flex-wrap">
          <span class="badge ${s.type === 'physical' ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-green-100 text-green-800 border-green-200'}">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              ${typeIcon}
            </svg>
            <span>${s.type === 'physical' ? 'Fyzická' : 'Virtuální'}</span>
          </span>
          <span class="badge bg-red-100 text-red-800 border-red-200">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            <span>${s.reward_points} bodů</span>
          </span>
          ${capacityBadge}
        </div>

        ${locationInfo}

        ${actionButton}
      </div>
    `;
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
        alert('✅ ' + data.message + '\n\nStanoviště: ' + data.station.name);
        window.location.reload();
      } else {
        alert('❌ ' + data.message);
      }
    } catch (error) {
      alert('❌ Chyba při přiřazování stanoviště: ' + error.message);
    }
  }
  
  // Infinite scroll
  window.addEventListener('scroll', () => {
    if (canAccess && (window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
      loadStations();
    }
  });
  
  // Initial load
  if (canAccess) {
    loadStations();
  }
  </script>
</body>
</html>