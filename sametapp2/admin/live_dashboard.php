<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = $_SESSION['user'];

if ($user['role'] !== 'admin' && $user['role'] !== 'organizer') {
    header('Location: ../dashboard');
    exit;
}

$pdo = db();

// Získat režim přiřazování
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'assignment_mode'");
$stmt->execute();
$assignment_mode = $stmt->fetchColumn() ?: 'free';
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Live Dashboard — Samet Festival</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .station-card { transition: all 0.3s ease; }
    .station-card:hover { transform: translateY(-2px); }
    .team-badge { animation: fadeIn 0.3s ease; }
    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }
    .pulse { animation: pulse 2s infinite; }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <nav class="bg-white shadow-sm border-b mb-6 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center space-x-4">
          <a href="./index" class="text-blue-600 hover:text-blue-800">← Admin</a>
          <h1 class="text-xl font-bold text-gray-900">📊 Live Dashboard</h1>
          <span class="px-3 py-1 text-sm rounded-full <?=$assignment_mode==='assigned'?'bg-blue-100 text-blue-800':'bg-green-100 text-green-800'?>">
            <?=$assignment_mode==='assigned'?'🎯 Přiřazená stanoviště':'🔓 Volný pohyb'?>
          </span>
        </div>
        <div class="flex items-center space-x-3">
          <span class="text-sm text-gray-600">Aktualizace: <span id="lastUpdate" class="font-semibold">--:--:--</span></span>
          <button onclick="toggleAutoRefresh()" id="autoRefreshBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-semibold">
            ⏸️ Pozastavit
          </button>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Statistiky -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Celkem týmů</p>
            <p class="text-2xl font-bold text-gray-900" id="totalTeams">0</p>
          </div>
          <div class="text-3xl">👥</div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Aktivní týmy</p>
            <p class="text-2xl font-bold text-green-600" id="activeTeams">0</p>
          </div>
          <div class="text-3xl">🏃</div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Čekající týmy</p>
            <p class="text-2xl font-bold text-yellow-600" id="waitingTeams">0</p>
          </div>
          <div class="text-3xl">⏳</div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Dokončené úkoly</p>
            <p class="text-2xl font-bold text-blue-600" id="completedTasks">0</p>
          </div>
          <div class="text-3xl">✅</div>
        </div>
      </div>
    </div>

    <!-- Hlavní obsah -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Stanoviště -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">📍 Stanoviště a týmy</h2>
          </div>
          <div class="p-6">
            <div id="stationsContainer" class="space-y-4">
              <div class="text-center py-8 text-gray-500">
                <div class="pulse">Načítání...</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Týmy bez přiřazení -->
      <div>
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">⏳ Týmy bez stanoviště</h2>
          </div>
          <div class="p-6">
            <div id="unassignedTeams" class="space-y-3">
              <div class="text-center py-8 text-gray-500">
                <div class="pulse">Načítání...</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Akce -->
        <?php if ($assignment_mode === 'assigned'): ?>
        <div class="mt-6 bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold text-gray-900 mb-4">🎯 Akce</h3>
          <button onclick="rebalanceTeams()" class="w-full px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 font-semibold mb-3">
            ⚖️ Přerozdělit všechny týmy
          </button>
          <p class="text-xs text-gray-500">
            Automaticky přiřadí stanoviště všem týmům bez přiřazení podle aktuální obsazenosti.
          </p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Historie aktivit -->
    <div class="mt-6 bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-900">📜 Poslední aktivity</h2>
      </div>
      <div class="p-6">
        <div id="activityLog" class="space-y-2 max-h-64 overflow-y-auto">
          <div class="text-center py-4 text-gray-500 text-sm">
            Žádné aktivity
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  let autoRefresh = true;
  let refreshInterval;
  let activities = [];

  async function loadDashboard() {
    try {
      const response = await fetch('/api/dashboard_data.php');
      const data = await response.json();
      
      if (data.success) {
        updateStatistics(data.statistics);
        updateStations(data.stations);
        updateUnassignedTeams(data.unassigned_teams);
        updateLastUpdate();
      }
    } catch (error) {
      console.error('Chyba při načítání dat:', error);
    }
  }

  function updateStatistics(stats) {
    document.getElementById('totalTeams').textContent = stats.total_teams;
    document.getElementById('activeTeams').textContent = stats.active_teams;
    document.getElementById('waitingTeams').textContent = stats.waiting_teams;
    document.getElementById('completedTasks').textContent = stats.completed_tasks;
  }

  function updateStations(stations) {
    const container = document.getElementById('stationsContainer');
    
    if (stations.length === 0) {
      container.innerHTML = '<div class="text-center py-8 text-gray-500">Žádná aktivní stanoviště</div>';
      return;
    }

    container.innerHTML = stations.map(station => {
      const isFull = station.capacity > 0 && station.current_occupancy >= station.capacity;
      const fillPercentage = station.capacity > 0 ? (station.current_occupancy / station.capacity * 100) : 0;
      
      return `
        <div class="station-card border rounded-lg p-4 ${isFull ? 'bg-red-50 border-red-200' : 'bg-white border-gray-200'}">
          <div class="flex justify-between items-start mb-3">
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 text-lg">${escapeHtml(station.name)}</h3>
              <p class="text-sm text-gray-600">${escapeHtml(station.location || 'Bez lokace')}</p>
            </div>
            <div class="text-right">
              <span class="px-2 py-1 text-xs rounded ${station.type === 'physical' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                ${station.type === 'physical' ? '📍 Fyzická' : '💻 Virtuální'}
              </span>
              ${station.capacity > 0 ? `
                <div class="mt-2 text-sm">
                  <span class="font-semibold ${isFull ? 'text-red-600' : 'text-gray-900'}">
                    ${station.current_occupancy}/${station.capacity}
                  </span>
                </div>
              ` : ''}
            </div>
          </div>
          
          ${station.capacity > 0 ? `
            <div class="mb-3">
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full transition-all ${isFull ? 'bg-red-500' : 'bg-blue-500'}" style="width: ${Math.min(fillPercentage, 100)}%"></div>
              </div>
            </div>
          ` : ''}
          
          <div class="flex flex-wrap gap-2">
            ${station.teams && station.teams.length > 0 ? station.teams.map(team => `
              <div class="team-badge px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                ${escapeHtml(team.name)}
                <span class="text-xs text-blue-600">(${team.points} bodů)</span>
              </div>
            `).join('') : '<span class="text-sm text-gray-500 italic">Žádné týmy</span>'}
          </div>
        </div>
      `;
    }).join('');
  }

  function updateUnassignedTeams(teams) {
    const container = document.getElementById('unassignedTeams');
    
    if (teams.length === 0) {
      container.innerHTML = '<div class="text-center py-4 text-gray-500 text-sm">✅ Všechny týmy mají přiřazení</div>';
      return;
    }

    container.innerHTML = teams.map(team => `
      <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50">
        <div class="flex justify-between items-center">
          <div>
            <p class="font-semibold text-gray-900">${escapeHtml(team.name)}</p>
            <p class="text-xs text-gray-500">${team.points} bodů • ${team.completed_stations} dokončeno</p>
          </div>
          <button onclick="assignStation(${team.id})" class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">
            Přiřadit
          </button>
        </div>
      </div>
    `).join('');
  }

  function updateLastUpdate() {
    const now = new Date();
    document.getElementById('lastUpdate').textContent = now.toLocaleTimeString('cs-CZ');
  }

  function toggleAutoRefresh() {
    autoRefresh = !autoRefresh;
    const btn = document.getElementById('autoRefreshBtn');
    
    if (autoRefresh) {
      btn.innerHTML = '⏸️ Pozastavit';
      btn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
      btn.classList.add('bg-green-600', 'hover:bg-green-700');
      startAutoRefresh();
    } else {
      btn.innerHTML = '▶️ Spustit';
      btn.classList.remove('bg-green-600', 'hover:bg-green-700');
      btn.classList.add('bg-gray-600', 'hover:bg-gray-700');
      stopAutoRefresh();
    }
  }

  function startAutoRefresh() {
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(loadDashboard, 3000); // Aktualizace každé 3 sekundy
  }

  function stopAutoRefresh() {
    if (refreshInterval) {
      clearInterval(refreshInterval);
      refreshInterval = null;
    }
  }

  async function assignStation(teamId) {
    try {
      const response = await fetch('/api/assign_next_station.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({team_id: teamId})
      });
      
      const data = await response.json();
      
      if (data.success) {
        addActivity(`Tým přiřazen na stanoviště: ${data.station.name}`);
        loadDashboard();
      } else {
        alert('❌ ' + data.message);
      }
    } catch (error) {
      alert('❌ Chyba při přiřazování: ' + error.message);
    }
  }

  async function rebalanceTeams() {
    if (!confirm('Opravdu chcete přerozdělit všechny týmy? Toto přiřadí stanoviště všem týmům bez přiřazení.')) {
      return;
    }
    
    try {
      const response = await fetch('/api/rebalance_teams.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
      });
      
      const data = await response.json();
      
      if (data.success) {
        alert(`✅ Přerozděleno ${data.assigned} týmů`);
        addActivity(`Přerozděleno ${data.assigned} týmů`);
        loadDashboard();
      } else {
        alert('❌ ' + data.message);
      }
    } catch (error) {
      alert('❌ Chyba při přerozdělování: ' + error.message);
    }
  }

  function addActivity(message) {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('cs-CZ');
    
    activities.unshift({time: timeStr, message: message});
    if (activities.length > 20) activities.pop();
    
    const container = document.getElementById('activityLog');
    container.innerHTML = activities.map(act => `
      <div class="flex items-start space-x-2 text-sm border-b border-gray-100 pb-2">
        <span class="text-gray-500 font-mono text-xs">${act.time}</span>
        <span class="text-gray-700">${escapeHtml(act.message)}</span>
      </div>
    `).join('');
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Inicializace
  loadDashboard();
  startAutoRefresh();
  </script>
</body>
</html>