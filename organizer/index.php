<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

require_login(["organizer", "admin"]);

$currentStation = null;
if (isset($_SESSION['station_id'])) {
    // Načíst data o stanovišti z DB podle ID
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
    $stmt->execute([$_SESSION['station_id']]);
    $currentStation = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title>Pořadatel — Vylepšené skenování</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <style>
    body {
      -webkit-user-select: none;
      -webkit-touch-callout: none;
    }
    #reader {
      position: relative;
      width: 100%;
      max-width: 500px;
      margin: 0 auto;
    }
    #reader video {
      width: 100% !important;
      height: auto !important;
      border-radius: 0.5rem;
    }
    #reader__scan_region {
      border-radius: 0.5rem !important;
    }
    .tab-active {
      border-bottom: 3px solid #3b82f6;
      color: #3b82f6;
      font-weight: 600;
    }
    .pulse-dot {
      animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    /* Range slider styling */
    input[type="range"] {
      -webkit-appearance: none;
      width: 100%;
      height: 8px;
      border-radius: 5px;
      background: linear-gradient(to right, #ef4444 0%, #f59e0b 50%, #10b981 100%);
      outline: none;
    }
    input[type="range"]::-webkit-slider-thumb {
      -webkit-appearance: none;
      appearance: none;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #3b82f6;
      cursor: pointer;
      border: 3px solid white;
      box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    input[type="range"]::-moz-range-thumb {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #3b82f6;
      cursor: pointer;
      border: 3px solid white;
      box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <div class="max-w-4xl mx-auto p-4 pb-20">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-lg p-4 mb-4 text-white">
      <h1 class="text-2xl font-bold mb-2">🎯 Pořadatelský skener</h1>
      <div id="currentStation" class="text-sm">
        <span class="font-semibold">Stanoviště:</span> <span class="bg-red-500 px-2 py-1 rounded">Není vybráno</span>
      </div>
      <div id="stationCapacity" class="text-sm mt-1 hidden">
        <span class="font-semibold">Obsazenost:</span> <span id="occupancyText">0/0</span>
      </div>
      <div id="stationNotes" class="text-sm mt-2 hidden bg-blue-800 rounded p-2">
        <span class="font-semibold">📝 Poznámky:</span> <span id="notesText"></span>
      </div>
    </div>

    <div id="evalResult" class="mt-4 mb-4 hidden"></div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-lg mt-4">
      <div class="flex border-b overflow-x-auto">
        <button onclick="switchTab('scan')" id="tabScan" class="flex-1 py-3 px-2 text-sm font-semibold tab-active whitespace-nowrap">
          📱 Skenování
        </button>
        <button onclick="switchTab('current')" id="tabCurrent" class="flex-1 py-3 px-2 text-sm font-semibold text-gray-500 whitespace-nowrap">
          👥 Aktuální <span id="currentCount" class="ml-1 bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs">0</span>
        </button>
        <button onclick="switchTab('manage')" id="tabManage" class="flex-1 py-3 px-2 text-sm font-semibold text-gray-500 whitespace-nowrap">
          ⚙️ Správa týmu
        </button>
        <button onclick="switchTab('station')" id="tabStation" class="flex-1 py-3 px-2 text-sm font-semibold text-gray-500 whitespace-nowrap">
          📍 Stanoviště
        </button>
        <button onclick="switchTab('history')" id="tabHistory" class="flex-1 py-3 px-2 text-sm font-semibold text-gray-500 whitespace-nowrap">
          📋 Historie
        </button>
      </div>

      <!-- Tab: Skenování týmu -->
      <div id="contentScan" class="p-4">
        <div id="noStationWarning" class="mb-4 p-4 bg-yellow-50 border-2 border-yellow-300 rounded-lg">
          <p class="text-yellow-800 font-semibold flex items-center">
            <span class="text-2xl mr-2">⚠️</span>
            Nejprve vyberte stanoviště v záložce "📍 Stanoviště"
          </p>
        </div>

        <div id="scanSection" class="hidden">
          <h2 class="text-lg font-semibold mb-4 flex items-center">
            <span class="text-2xl mr-2">🎫</span>
            Naskenujte QR kód týmu
          </h2>
          
          <div id="teamReader" class="mb-4 rounded-lg overflow-hidden border-2 border-gray-200"></div>
          
          <button id="startTeamScan" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition font-semibold mb-4 shadow-md">
            📷 Spustit skenování týmu
          </button>

          <!-- Manuální zadání -->
          <div class="border-t pt-4">
            <p class="text-sm text-gray-600 mb-2">Nebo zadejte kód ručně:</p>
            <div class="flex space-x-2">
              <input type="text" id="manualTeamCode" placeholder="Vložte QR kód týmu" class="flex-1 border-2 border-gray-300 rounded-lg px-3 py-2">
              <button id="manualTeamSend" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 shadow-md">
                Odeslat
              </button>
            </div>
          </div>

          <div id="teamResult" class="mt-4 hidden"></div>
        </div>
      </div>

      <!-- Tab: Aktuálně přítomní -->
      <div id="contentCurrent" class="p-4 hidden">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold flex items-center">
            <span class="text-2xl mr-2">👥</span>
            Aktuálně na stanovišti
          </h2>
          <button onclick="refreshCurrentTeams()" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 text-sm font-semibold">
            🔄 Obnovit
          </button>
        </div>
        
        <div id="currentTeamsList" class="space-y-3">
          <p class="text-gray-500 text-center py-8">Nejprve vyberte stanoviště</p>
        </div>
      </div>

      <!-- Tab: Správa týmu -->
      <div id="contentManage" class="p-4 hidden">
        <h2 class="text-lg font-semibold mb-4 flex items-center">
          <span class="text-2xl mr-2">⚙️</span>
          Správa týmu
        </h2>
        
        <!-- QR skenování týmu pro správu -->
        <div class="mb-6">
          <h3 class="font-semibold mb-2 flex items-center">
            <span class="text-2xl mr-2">📷</span>
            Naskenovat QR kód týmu
          </h3>
          <div id="manageTeamReader" class="mb-4 rounded-lg overflow-hidden border-2 border-gray-200"></div>
          <button id="startManageTeamScan" class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 transition font-semibold shadow-md">
            Spustit skenování QR
          </button>
        </div>

        <!-- Nebo manuální zadání -->
        <div class="border-t pt-4">
          <h3 class="font-semibold mb-2 flex items-center">
            <span class="text-2xl mr-2">🔢</span>
            Nebo zadat kód týmu ručně
          </h3>
          <div class="flex space-x-2">
            <input type="text" id="manageTeamCode" placeholder="Kód týmu" 
                   class="flex-1 border-2 border-gray-300 rounded-lg px-3 py-2">
            <button id="loadTeamByCode" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 font-semibold shadow-md">
              Načíst
            </button>
          </div>
        </div>

        <div id="teamManageResult" class="mt-4 hidden"></div>
      </div>

      <!-- Tab: Výběr stanoviště -->
      <div id="contentStation" class="p-4 hidden">
        <h2 class="text-lg font-semibold mb-4">Vyberte stanoviště</h2>
        
        <!-- QR skenování stanoviště -->
        <div class="mb-6">
          <h3 class="font-semibold mb-2 flex items-center">
            <span class="text-2xl mr-2">📷</span>
            Naskenovat QR kód stanoviště
          </h3>
          <div id="stationReader" class="mb-4 rounded-lg overflow-hidden border-2 border-gray-200"></div>
          <button id="startStationScan" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition font-semibold shadow-md">
            Spustit skenování QR
          </button>
        </div>

        <!-- Nebo PIN -->
        <div class="border-t pt-4">
          <h3 class="font-semibold mb-2 flex items-center">
            <span class="text-2xl mr-2">🔢</span>
            Nebo zadat PIN kód
          </h3>
          <div class="flex space-x-2">
            <input type="text" id="stationPin" placeholder="4místný PIN" maxlength="4" 
                   class="flex-1 border-2 border-gray-300 rounded-lg text-center font-mono py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
            <button id="verifyPin" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-semibold shadow-md">
              Přihlásit
            </button>
          </div>
        </div>

        <div id="stationResult" class="mt-4 hidden"></div>
      </div>

      <!-- Tab: Historie -->
      <div id="contentHistory" class="p-4 hidden">
        <h2 class="text-lg font-semibold mb-4 flex items-center">
          <span class="text-2xl mr-2">📋</span>
          Historie vyhodnocených týmů
        </h2>
        <div id="historyList" class="space-y-3">
          <p class="text-gray-500 text-center py-8">Zatím nebyl vyhodnocen žádný tým</p>
        </div>
      </div>
    </div>
  </div>

<script>
// Globální proměnné
let currentStation = <?= json_encode($currentStation ?: null) ?>;
let stationScanner = null;
let teamScanner = null;
let manageTeamScanner = null;
let scanHistory = [];
let currentTeams = [];
let isStationScanning = false;
let isTeamScanning = false;
let isManageTeamScanning = false;
let refreshInterval = null;
let currentManagedTeam = null;

// Přepínání záložek
function switchTab(tab) {
  // Skrýt všechny obsahy
  document.getElementById('contentStation').classList.add('hidden');
  document.getElementById('contentScan').classList.add('hidden');
  document.getElementById('contentCurrent').classList.add('hidden');
  document.getElementById('contentManage').classList.add('hidden');
  document.getElementById('contentHistory').classList.add('hidden');
  
  // Odstranit aktivní třídu ze všech tabů
  ['tabStation', 'tabScan', 'tabCurrent', 'tabManage', 'tabHistory'].forEach(id => {
    document.getElementById(id).classList.remove('tab-active');
    document.getElementById(id).classList.add('text-gray-500');
  });
  
  // Zobrazit vybraný obsah
  if (tab === 'station') {
    document.getElementById('contentStation').classList.remove('hidden');
    document.getElementById('tabStation').classList.add('tab-active');
    document.getElementById('tabStation').classList.remove('text-gray-500');
  } else if (tab === 'scan') {
    document.getElementById('contentScan').classList.remove('hidden');
    document.getElementById('tabScan').classList.add('tab-active');
    document.getElementById('tabScan').classList.remove('text-gray-500');
    updateScanSection();
  } else if (tab === 'current') {
    document.getElementById('contentCurrent').classList.remove('hidden');
    document.getElementById('tabCurrent').classList.add('tab-active');
    document.getElementById('tabCurrent').classList.remove('text-gray-500');
    refreshCurrentTeams();
  } else if (tab === 'manage') {
    document.getElementById('contentManage').classList.remove('hidden');
    document.getElementById('tabManage').classList.add('tab-active');
    document.getElementById('tabManage').classList.remove('text-gray-500');
  } else if (tab === 'history') {
    document.getElementById('contentHistory').classList.remove('hidden');
    document.getElementById('tabHistory').classList.add('tab-active');
    document.getElementById('tabHistory').classList.remove('text-gray-500');
    renderHistory();
  }
}

// Aktualizace sekce skenování podle toho, zda je vybráno stanoviště
function updateScanSection() {
  const noStationWarning = document.getElementById('noStationWarning');
  const scanSection = document.getElementById('scanSection');
  
  if (currentStation) {
    noStationWarning.classList.add('hidden');
    scanSection.classList.remove('hidden');
  } else {
    noStationWarning.classList.remove('hidden');
    scanSection.classList.add('hidden');
  }
}

// Aktualizace zobrazení aktuálního stanoviště
function updateCurrentStationDisplay() {
  const display = document.getElementById('currentStation');
  const capacityDisplay = document.getElementById('stationCapacity');
  const notesDisplay = document.getElementById('stationNotes');
  
  if (currentStation) {
    display.innerHTML = `
      <span class="font-semibold">Stanoviště:</span> 
      <span class="bg-green-500 px-2 py-1 rounded font-semibold">${escapeHtml(currentStation.name)}</span>
      ${currentStation.location ? `<span class="text-xs ml-2 opacity-90">(${escapeHtml(currentStation.location)})</span>` : ''}
    `;
    
    if (currentStation.capacity > 0) {
      capacityDisplay.classList.remove('hidden');
      updateOccupancyDisplay();
    } else {
      capacityDisplay.classList.add('hidden');
    }
    
    // Zobrazit poznámky pro organizátory
    if (currentStation.organizer_notes) {
      notesDisplay.classList.remove('hidden');
      document.getElementById('notesText').textContent = currentStation.organizer_notes;
    } else {
      notesDisplay.classList.add('hidden');
    }
    
    // Spustit automatické obnovování
    startAutoRefresh();
  } else {
    display.innerHTML = `<span class="font-semibold">Stanoviště:</span> <span class="bg-red-500 px-2 py-1 rounded">Není vybráno</span>`;
    capacityDisplay.classList.add('hidden');
    notesDisplay.classList.add('hidden');
    stopAutoRefresh();
  }
}

// Aktualizace zobrazení obsazenosti
function updateOccupancyDisplay() {
  if (!currentStation || !currentStation.capacity) return;
  
  const occupancy = currentTeams.length;
  const capacity = currentStation.capacity;
  const percentage = (occupancy / capacity) * 100;
  
  let colorClass = 'text-green-600';
  if (percentage >= 90) colorClass = 'text-red-600';
  else if (percentage >= 70) colorClass = 'text-orange-600';
  else if (percentage >= 50) colorClass = 'text-yellow-600';
  
  document.getElementById('occupancyText').innerHTML = `
    <span class="${colorClass} font-bold">${occupancy}/${capacity}</span>
    ${percentage >= 100 ? '<span class="ml-2 text-red-600 font-semibold">PLNÉ</span>' : ''}
  `;
}

// Automatické obnovování aktuálních týmů
function startAutoRefresh() {
  stopAutoRefresh();
  refreshInterval = setInterval(() => {
    if (currentStation) {
      refreshCurrentTeams(true); // tichý refresh
    }
  }, 10000); // každých 10 sekund
}

function stopAutoRefresh() {
  if (refreshInterval) {
    clearInterval(refreshInterval);
    refreshInterval = null;
  }
}

// Ověření stanoviště (QR nebo PIN)
async function verifyStation(type, value) {
  try {
    const response = await fetch('../api/station_verify.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({type, value})
    });
    
    const data = await response.json();
    
    if (data.success) {
      currentStation = data.station;
      
      updateCurrentStationDisplay();
      
      const hasScale = currentStation.max_points !== null && currentStation.max_points !== currentStation.reward_points;
      const scaleInfo = hasScale ? 
        `<div class="mt-2 p-2 bg-blue-50 rounded">
          <span class="text-sm font-semibold text-blue-800">Škála bodování: ${currentStation.min_points} - ${currentStation.max_points} bodů</span>
        </div>` : '';
      
      const onceOnlyInfo = currentStation.once_only ? 
        `<div class="mt-2 p-2 bg-purple-50 rounded">
          <span class="text-sm font-semibold text-purple-800">⚠️ Jednorázová účast</span>
        </div>` : '';
      
      showStationResult(`
        <div class="p-4 bg-green-100 border-2 border-green-400 rounded-lg">
          <div class="font-semibold text-xl mb-2 text-green-800 flex items-center">
            <span class="text-2xl mr-2">✓</span>
            Stanoviště vybráno
          </div>
          <div class="text-sm text-green-700 mb-3">
            <strong class="text-lg">${escapeHtml(currentStation.name)}</strong><br>
            ${escapeHtml(currentStation.description)}<br>
            <span class="text-xs mt-1 inline-block">
              ${currentStation.capacity > 0 ? `Kapacita: ${currentStation.capacity} | ` : ''}
              Body: ${currentStation.reward_points}
            </span>
          </div>
          ${scaleInfo}
          ${onceOnlyInfo}
          <button onclick="switchTab('scan')" class="mt-3 w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 font-semibold shadow-md">
            Přejít na skenování týmů →
          </button>
        </div>
      `, true);
      
      // Načíst aktuální týmy
      refreshCurrentTeams(true);
    } else {
      showStationResult(`
        <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
          <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
            <span class="text-2xl mr-2">✗</span>
            Chyba
          </div>
          <div class="text-sm text-red-700">${escapeHtml(data.message)}</div>
        </div>
      `, false);
    }
  } catch (error) {
    console.log(error)
    showStationResult(`
      <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
        <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
          <span class="text-2xl mr-2">✗</span>
          Chyba připojení
        </div>
        <div class="text-sm text-red-700">${escapeHtml(error.message)}</div>
      </div>
    `, false);
  }
}

// Zobrazení výsledku ověření stanoviště
function showStationResult(html, isSuccess) {
  const resultEl = document.getElementById('stationResult');
  resultEl.innerHTML = html;
  resultEl.classList.remove('hidden');
}

// Skenování QR kódu stanoviště
function startStationScanner() {
  if (isStationScanning) return;
  isStationScanning = true;
  
  const config = {
    fps: 10,
    qrbox: function(w, h) {
      const minEdge = Math.min(w, h);
      const qrboxSize = Math.floor(minEdge * 0.7);
      return { width: qrboxSize, height: qrboxSize };
    },
    aspectRatio: 1.0
  };
  
  if (!stationScanner) {
    stationScanner = new Html5Qrcode("stationReader");
  }
  
  stationScanner.start(
    { facingMode: "environment" },
    config,
    (decodedText) => {
      if (!isStationScanning) return;
      isStationScanning = false;
      stationScanner.stop();
      verifyStation('qr', decodedText);
    },
    (error) => {
      // Ignorovat chyby skenování
    }
  ).catch(err => {
    console.error('Camera error:', err);
    isStationScanning = false;
    showStationResult(`
      <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
        <div class="font-semibold text-red-800">Chyba kamery</div>
        <div class="text-sm text-red-700">Nepodařilo se spustit kameru. Zkuste použít PIN kód.</div>
      </div>
    `, false);
  });
}

// Ověření PIN kódu
function verifyPinCode() {
  const pin = document.getElementById('stationPin').value.trim();
  if (pin.length !== 4) {
    showStationResult(`
      <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
        <div class="text-sm text-red-700">PIN musí mít 4 číslice</div>
      </div>
    `, false);
    return;
  }
  verifyStation('pin', pin);
}

// Zpracování QR kódu týmu
async function processTeamToken(token, bypassenter) {
  if (!currentStation) {
    alert('Nejprve vyberte stanoviště!');
    return;
  }
  
  showTeamResult(`
    <div class="p-4 bg-blue-100 border-2 border-blue-300 rounded-lg">
      <div class="flex items-center justify-center">
        <svg class="animate-spin h-6 w-6 mr-3 text-blue-600" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="font-semibold">Ověřuji tým...</span>
      </div>
    </div>
  `);
  
  try {
      const response = await fetch('../api/qr_scan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({token, station_id: currentStation.id, bypassassigned: bypassenter})
      });
    
    
    const data = await response.json();
    
    if (data.success == true) {
      // Obnovit seznam aktuálních týmů
      await refreshCurrentTeams(true);
      
      // Zobrazit upozornění, pokud je tým na špatném stanovišti
      let warningHtml = '';
      if (data.wrong_station_warning && data.wrong_station_warning.has_warning) {
        warningHtml = `
          <div class="mb-3 p-3 bg-orange-100 border-2 border-orange-400 rounded-lg">
            <div class="font-semibold text-orange-800 flex items-center mb-1">
              <span class="text-xl mr-2">⚠️</span>
              ${escapeHtml(data.wrong_station_warning.message)}
            </div>
            <div class="text-sm text-orange-700">
              Přiřazené stanoviště: <strong>${escapeHtml(data.wrong_station_warning.assigned_station)}</strong><br>
              Aktuální stanoviště: <strong>${escapeHtml(data.wrong_station_warning.current_station)}</strong>
            </div>
          </div>
        `;
      }
      
      if (data.already_present) {
        showTeamResult(`
          ${warningHtml}
          <div class="p-4 bg-yellow-100 border-2 border-yellow-400 rounded-lg">
            <div class="font-semibold text-lg mb-2 text-yellow-800 flex items-center">
              <span class="text-2xl mr-2">⚠️</span>
              Tým je již přítomen
            </div>
            <div class="text-sm text-yellow-700 mb-3">
              Tým <strong>${escapeHtml(data.team)}</strong> je již zaregistrován na tomto stanovišti.
            </div>
            <button onclick="showEvaluationForResult(${data.result_id}, ${data.team_id}, '${escapeHtml(data.team)}', ${data.team_points}, ${JSON.stringify(data.station).replace(/"/g, '&quot;')})" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 font-semibold">
              Přejít k hodnocení
            </button>
          </div>
        `);
      } else {
        showTeamResult(`
          ${warningHtml}
          <div class="p-4 bg-green-100 border-2 border-green-400 rounded-lg">
            <div class="font-semibold text-xl mb-2 text-green-800 flex items-center">
              <span class="text-2xl mr-2">✓</span>
              Tým zaregistrován
            </div>
            <div class="text-sm text-green-700 mb-3">
              Tým <strong>${escapeHtml(data.team)}</strong> byl úspěšně zaregistrován na stanovišti.
            </div>
            <button onclick="showEvaluationForResult(${data.result_id}, ${data.team_id}, '${escapeHtml(data.team)}', ${data.team_points}, ${JSON.stringify(data.station).replace(/"/g, '&quot;')})" 
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 font-semibold shadow-md mb-2">
              Přejít k hodnocení
            </button>
            <button onclick="resetTeamScan()" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 font-semibold">
              Skenovat další tým
            </button>
          </div>
        `);
      }
    } else if (data.success == "warning") {
      showTeamResult(`
        <div class="mb-3 p-3 bg-orange-100 border-2 border-orange-400 rounded-lg">
            <div class="font-semibold text-orange-800 flex items-center mb-1">
              <span class="text-xl mr-2">⚠️</span>
              ${escapeHtml(data.warning.message)}
            </div>
            <div class="text-sm text-orange-700 mb-3">
              Přiřazené stanoviště: <strong>${escapeHtml(data.warning.assigned_station)}</strong><br>
              Aktuální stanoviště: <strong>${escapeHtml(data.warning.current_station)}</strong>
            </div>
            <button onclick="processTeamToken('${token}', true)" 
                    class="w-full bg-orange-400 text-white py-3 px-4 rounded-lg hover:bg-orange-300 font-semibold shadow-md mb-2">
              Přesto tým zaregistrovat
            </button>
          </div>
      `);
    } else {
      showTeamResult(`
        <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
          <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
            <span class="text-2xl mr-2">✗</span>
            Chyba
          </div>
          <div class="text-sm text-red-700">${escapeHtml(data.message)}</div>
          <button onclick="resetTeamScan()" 
                  class="mt-3 w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700">
            Zkusit znovu
          </button>
        </div>
      `);
    }
  } catch (error) {
        console.log(error)
    showTeamResult(`
      <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
        <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
          <span class="text-2xl mr-2">✗</span>
          Chyba připojení
        </div>
        <div class="text-sm text-red-700">${escapeHtml(error.message)}</div>
      </div>
    `);
  }
}

// Načtení detailu týmu pro správu
async function loadTeamDetail(teamId) {
  showTeamManageResult(`
    <div class="p-4 bg-blue-100 border-2 border-blue-300 rounded-lg">
      <div class="flex items-center justify-center">
        <svg class="animate-spin h-6 w-6 mr-3 text-blue-600" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="font-semibold">Načítám data týmu...</span>
      </div>
    </div>
  `);
  
  try {
    const response = await fetch(`../api/organizer_team_detail.php?team_id=${teamId}`);
    const data = await response.json();
    
    if (data.success) {
      currentManagedTeam = data;
      renderTeamManagement(data);
    } else {
      showTeamManageResult(`
        <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
          <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
            <span class="text-2xl mr-2">✗</span>
            Chyba
          </div>
          <div class="text-sm text-red-700">${escapeHtml(data.message)}</div>
        </div>
      `);
    }
  } catch (error) {
    showTeamManageResult(`
      <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
        <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
          <span class="text-2xl mr-2">✗</span>
          Chyba připojení
        </div>
        <div class="text-sm text-red-700">${escapeHtml(error.message)}</div>
      </div>
    `);
  }
}

// Vykreslení rozhraní pro správu týmu
function renderTeamManagement(data) {
  const team = data.team;
  const members = data.members;
  const results = data.results;
  
  const membersHtml = members.map(member => `
    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
      <div class="flex justify-between items-start mb-2">
        <div class="flex-1">
          <div class="font-semibold">${escapeHtml(member.name)}</div>
          <div class="text-sm text-gray-600">${escapeHtml(member.email)}</div>
          <div class="text-xs text-gray-500 mt-1">
            Role: <span class="font-semibold">${escapeHtml(member.role)}</span>
          </div>
        </div>
        <div class="flex space-x-2">
          <button onclick="editUser(${member.id}, '${escapeHtml(member.name)}', '${escapeHtml(member.email)}', '${member.role}')" 
                  class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
            ✏️ Upravit
          </button>
          <button onclick="removeUser(${member.id}, '${escapeHtml(member.name)}')" 
                  class="text-red-600 hover:text-red-800 text-sm font-semibold">
            🗑️ Odebrat
          </button>
        </div>
      </div>
    </div>
  `).join('');
  
  const resultsHtml = results.length > 0 ? results.map(result => {
    const statusColor = result.status === 'done' ? 'green' : (result.status === 'failed' ? 'red' : 'yellow');
    const statusText = result.status === 'done' ? 'Dokončeno' : (result.status === 'failed' ? 'Nesplněno' : 'Čeká');
    return `
      <div class="text-sm p-2 bg-gray-50 rounded border border-gray-200">
        <div class="flex justify-between">
          <span class="font-semibold">${escapeHtml(result.station_name)}</span>
          <span class="px-2 py-0.5 rounded text-xs font-semibold bg-${statusColor}-100 text-${statusColor}-800">
            ${statusText}
          </span>
        </div>
        <div class="text-xs text-gray-600 mt-1">
          Body: ${result.points} | ${new Date(result.created_at).toLocaleString('cs-CZ')}
        </div>
      </div>
    `;
  }).join('') : '<p class="text-sm text-gray-500 text-center py-4">Žádné výsledky</p>';
  
  const html = `
    <div class="space-y-4">
      <!-- Informace o týmu -->
      <div class="p-4 bg-gradient-to-r from-purple-100 to-purple-50 border-2 border-purple-300 rounded-lg">
        <div class="flex justify-between items-start mb-3">
          <div>
            <h3 class="text-xl font-bold text-purple-900">${escapeHtml(team.name)}</h3>
            <div class="text-sm text-purple-700 mt-1">
              Kód: <span class="font-mono font-semibold">${escapeHtml(team.code)}</span>
            </div>
            <div class="text-sm text-purple-700">
              Body: <span class="font-bold text-lg">${team.points}</span>
            </div>
          </div>
          <button onclick="editTeam(${team.id}, '${escapeHtml(team.name)}', ${team.points})" 
                  class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 font-semibold text-sm">
            ✏️ Upravit tým
          </button>
        </div>
      </div>
      
      <!-- Členové týmu -->
      <div class="p-4 bg-white border-2 border-gray-200 rounded-lg">
        <h4 class="font-semibold mb-3 flex items-center">
          <span class="text-xl mr-2">👥</span>
          Členové týmu (${members.length})
        </h4>
        <div class="space-y-2">
          ${membersHtml}
        </div>
      </div>
      
      <!-- Historie výsledků -->
      <div class="p-4 bg-white border-2 border-gray-200 rounded-lg">
        <h4 class="font-semibold mb-3 flex items-center">
          <span class="text-xl mr-2">📊</span>
          Historie výsledků (${results.length})
        </h4>
        <div class="space-y-2 max-h-64 overflow-y-auto">
          ${resultsHtml}
        </div>
      </div>
      
      <!-- Nebezpečná zóna -->
      <div class="p-4 bg-red-50 border-2 border-red-300 rounded-lg">
        <h4 class="font-semibold text-red-800 mb-2 flex items-center">
          <span class="text-xl mr-2">⚠️</span>
          Nebezpečná zóna
        </h4>
        <p class="text-sm text-red-700 mb-3">
          Smazání týmu je nevratná akce. Budou smazány všechny výsledky a členové budou odebráni z týmu.
        </p>
        <button onclick="deleteTeam(${team.id}, '${escapeHtml(team.name)}')" 
                class="w-full bg-red-600 text-white py-3 px-4 rounded-lg hover:bg-red-700 font-semibold">
          🗑️ Smazat tým
        </button>
      </div>
    </div>
  `;
  
  showTeamManageResult(html);
}

// Upravit tým
function editTeam(teamId, currentName, currentPoints) {
  const newName = prompt('Nový název týmu:', currentName);
  if (!newName || newName === currentName) {
    const newPoints = prompt('Nové body týmu:', currentPoints);
    if (newPoints === null) return;
    updateTeam(teamId, currentName, parseInt(newPoints));
    return;
  }
  
  const newPoints = prompt('Nové body týmu:', currentPoints);
  if (newPoints === null) return;
  
  updateTeam(teamId, newName, parseInt(newPoints));
}

async function updateTeam(teamId, name, points) {
  try {
    const response = await fetch('../api/organizer_update_team.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({team_id: teamId, name, points})
    });
    
    const data = await response.json();
    
    if (data.success) {
      alert('Tým byl úspěšně aktualizován');
      loadTeamDetail(teamId);
    } else {
      alert('Chyba: ' + data.message);
    }
  } catch (error) {
    alert('Chyba připojení: ' + error.message);
  }
}

// Upravit uživatele
function editUser(userId, currentName, currentEmail, currentRole) {
  const newName = prompt('Nové jméno:', currentName);
  if (newName === null) return;
  
  const newEmail = prompt('Nový email:', currentEmail);
  if (newEmail === null) return;
  
  const newRole = prompt('Nová role (player/organizer/admin):', currentRole);
  if (newRole === null) return;
  
  updateUser(userId, newName, newEmail, newRole);
}

async function updateUser(userId, name, email, role) {
  try {
    const response = await fetch('../api/organizer_update_user.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({user_id: userId, name, email, role})
    });
    
    const data = await response.json();
    
    if (data.success) {
      alert('Uživatel byl úspěšně aktualizován');
      loadTeamDetail(currentManagedTeam.team.id);
    } else {
      alert('Chyba: ' + data.message);
    }
  } catch (error) {
    alert('Chyba připojení: ' + error.message);
  }
}

// Odebrat uživatele z týmu
async function removeUser(userId, userName) {
  if (!confirm(`Opravdu chcete odebrat uživatele "${userName}" z týmu?`)) {
    return;
  }
  
  try {
    const response = await fetch('../api/organizer_remove_user.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({user_id: userId})
    });
    
    const data = await response.json();
    
    if (data.success) {
      alert('Uživatel byl úspěšně odebrán z týmu');
      loadTeamDetail(currentManagedTeam.team.id);
    } else {
      alert('Chyba: ' + data.message);
    }
  } catch (error) {
    alert('Chyba připojení: ' + error.message);
  }
}

// Smazat tým
async function deleteTeam(teamId, teamName) {
  if (!confirm(`VAROVÁNÍ: Opravdu chcete SMAZAT tým "${teamName}"?\n\nTato akce je NEVRATNÁ a smaže:\n- Všechny výsledky týmu\n- Všechny záznamy o týmu\n- Odebere všechny členy z týmu\n\nPokračovat?`)) {
    return;
  }
  
  if (!confirm(`Poslední potvrzení: Opravdu SMAZAT tým "${teamName}"?`)) {
    return;
  }
  
  try {
    const response = await fetch('../api/organizer_delete_team.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({team_id: teamId})
    });
    
    const data = await response.json();
    
    if (data.success) {
      alert('Tým byl úspěšně smazán');
      document.getElementById('teamManageResult').classList.add('hidden');
      document.getElementById('manageTeamCode').value = '';
      currentManagedTeam = null;
    } else {
      alert('Chyba: ' + data.message);
    }
  } catch (error) {
    alert('Chyba připojení: ' + error.message);
  }
}

// Skenování QR kódu týmu pro správu
function startManageTeamScanner() {
  if (isManageTeamScanning) return;
  isManageTeamScanning = true;
  
  const config = {
    fps: 10,
    qrbox: function(w, h) {
      const minEdge = Math.min(w, h);
      const qrboxSize = Math.floor(minEdge * 0.7);
      return { width: qrboxSize, height: qrboxSize };
    },
    aspectRatio: 1.0
  };
  
  if (!manageTeamScanner) {
    manageTeamScanner = new Html5Qrcode("manageTeamReader");
  }
  
  manageTeamScanner.start(
    { facingMode: "environment" },
    config,
    (decodedText) => {
      if (!isManageTeamScanning) return;
      isManageTeamScanning = false;
      manageTeamScanner.stop();
      
      // Ověřit token a získat team_id
      verifyTeamTokenForManagement(decodedText);
    },
    (error) => {
      // Ignorovat chyby skenování
    }
  ).catch(err => {
    console.error('Camera error:', err);
    isManageTeamScanning = false;
    alert('Nepodařilo se spustit kameru. Zkuste zadat kód týmu ručně.');
  });
}

// Ověřit QR token týmu a načíst detail
async function verifyTeamTokenForManagement(token) {
  showTeamManageResult(`
    <div class="p-4 bg-blue-100 border-2 border-blue-300 rounded-lg">
      <div class="flex items-center justify-center">
        <svg class="animate-spin h-6 w-6 mr-3 text-blue-600" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="font-semibold">Ověřuji QR kód...</span>
      </div>
    </div>
  `);
  
  try {
    // Dekódovat token a získat team_id
    const decoded = atob(token);
    const parts = decoded.split('|');
    if (parts.length < 3) {
      throw new Error('Neplatný QR kód týmu');
    }
    const teamId = parseInt(parts[0]);
    
    if (!teamId || isNaN(teamId)) {
      throw new Error('Neplatný QR kód týmu');
    }
    
    // Načíst detail týmu
    loadTeamDetail(teamId);
  } catch (error) {
    showTeamManageResult(`
      <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
        <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
          <span class="text-2xl mr-2">✗</span>
          Chyba
        </div>
        <div class="text-sm text-red-700">${escapeHtml(error.message)}</div>
      </div>
    `);
  }
}

// Načíst tým podle kódu
async function loadTeamByCode() {
  const code = document.getElementById('manageTeamCode').value.trim().toUpperCase();
  if (!code) {
    alert('Zadejte kód týmu');
    return;
  }
  
  showTeamManageResult(`
    <div class="p-4 bg-blue-100 border-2 border-blue-300 rounded-lg">
      <div class="flex items-center justify-center">
        <svg class="animate-spin h-6 w-6 mr-3 text-blue-600" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="font-semibold">Hledám tým...</span>
      </div>
    </div>
  `);
  
  try {
    // Najít tým podle kódu
    const response = await fetch(`../api/organizer_team_detail.php?team_code=${encodeURIComponent(code)}`);
    const data = await response.json();
    
    if (data.success) {
      currentManagedTeam = data;
      renderTeamManagement(data);
    } else {
      showTeamManageResult(`
        <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
          <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
            <span class="text-2xl mr-2">✗</span>
            Tým nenalezen
          </div>
          <div class="text-sm text-red-700">Tým s kódem "${escapeHtml(code)}" nebyl nalezen.</div>
        </div>
      `);
    }
  } catch (error) {
    showTeamManageResult(`
      <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
        <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
          <span class="text-2xl mr-2">✗</span>
          Chyba připojení
        </div>
        <div class="text-sm text-red-700">${escapeHtml(error.message)}</div>
      </div>
    `);
  }
}

function showTeamManageResult(html) {
  const resultEl = document.getElementById('teamManageResult');
  resultEl.innerHTML = html;
  resultEl.classList.remove('hidden');
}

// Zobrazení formuláře pro hodnocení týmu
function showEvaluationForResult(resultId, teamId, teamName, teamPoints, stationData) {
  resetTeamScan()
  const station = typeof stationData === 'string' ? JSON.parse(stationData) : stationData;
  
  const hasScale = station.max_points !== null && station.max_points !== station.reward_points;
  
  let evaluationButtons = '';
  
  if (hasScale) {
    // PROMĚNLIVÉ BODOVÁNÍ - Range slider
    const maxPoints = station.max_points;
    const minPoints = station.min_points || 0;
    const defaultValue = Math.round((maxPoints + minPoints) / 2);
    
    evaluationButtons = `
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-3">Hodnocení (${minPoints} - ${maxPoints} bodů)</label>
        
        <div class="mb-4">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm text-gray-600">Počet bodů:</span>
            <span id="pointsValue_${resultId}" class="text-3xl font-bold text-blue-600">${defaultValue}</span>
          </div>
          <input type="range" 
                 id="pointsSlider_${resultId}" 
                 min="${minPoints}" 
                 max="${maxPoints}" 
                 value="${defaultValue}"
                 class="w-full"
                 oninput="document.getElementById('pointsValue_${resultId}').textContent = this.value">
        </div>
        
        <div class="grid grid-cols-2 gap-2">
          <button onclick="submitEvaluationWithSlider(${resultId}, 'done')" 
                  class="bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 font-semibold shadow-md">
            ✓ Udělit body
          </button>
          <button onclick="submitEvaluation(${resultId}, 'failed', 0)" 
                  class="bg-red-600 text-white py-3 px-4 rounded-lg hover:bg-red-700 font-semibold shadow-md">
            ✗ Nesplněno (0 bodů)
          </button>
        </div>
      </div>
    `;
  } else {
    // FIXNÍ BODOVÁNÍ - Splněno/Nesplněno
    const points = station.reward_points;
    evaluationButtons = `
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-3">Hodnocení</label>
        <div class="space-y-2">
          <button onclick="submitEvaluation(${resultId}, 'done', ${points})" 
                  class="w-full bg-green-600 text-white py-4 px-4 rounded-lg hover:bg-green-700 font-semibold text-lg shadow-md">
            ✓ Splněno (+${points} bodů)
          </button>
          <button onclick="submitEvaluation(${resultId}, 'failed', 0)" 
                  class="w-full bg-red-600 text-white py-4 px-4 rounded-lg hover:bg-red-700 font-semibold text-lg shadow-md">
            ✗ Nesplněno (0 bodů)
          </button>
        </div>
      </div>
    `;
  }
  
  // Zobrazit poznámky pro organizátory, pokud existují
  let notesHtml = '';
  if (station.organizer_notes) {
    notesHtml = `
      <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-3 mb-4">
        <div class="font-semibold text-blue-800 mb-1 flex items-center">
          <span class="text-lg mr-2">📝</span>
          Poznámky k hodnocení
        </div>
        <div class="text-sm text-blue-700">${escapeHtml(station.organizer_notes)}</div>
      </div>
    `;
  }
  
  const html = `
    <div class="p-4 bg-white border-2 border-blue-500 rounded-lg">
      <div class="font-semibold text-xl mb-4 text-blue-800 flex items-center">
        <span class="text-2xl mr-2">📝</span>
        Hodnocení týmu
      </div>
      
      ${notesHtml}
      
      <div class="bg-gray-50 p-4 rounded-lg mb-4">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div><span class="text-gray-600">Název týmu:</span></div>
          <div class="font-semibold">${escapeHtml(teamName)}</div>
          
          <div><span class="text-gray-600">Aktuální body:</span></div>
          <div class="font-semibold text-blue-600">${teamPoints} bodů</div>
        </div>
      </div>

      ${evaluationButtons}

      <div class="border-t pt-3">
        <label class="block text-sm font-semibold mb-2">Poznámka (volitelné)</label>
        <textarea id="evaluationNote_${resultId}" rows="2" class="w-full border-2 border-gray-300 rounded-lg p-2" 
                  placeholder="Poznámka k hodnocení..."></textarea>
      </div>
    </div>
  `;
  
  showEvalResult(html);
}

// Odeslání hodnocení s hodnotou ze slideru
function submitEvaluationWithSlider(resultId, status) {
  const points = parseInt(document.getElementById(`pointsSlider_${resultId}`).value);
  submitEvaluation(resultId, status, points);
}

// Odeslání hodnocení
async function submitEvaluation(resultId, status, points) {
  const noteEl = document.getElementById(`evaluationNote_${resultId}`);
  const note = noteEl ? noteEl.value.trim() : '';
  
  showEvalResult(`
    <div class="p-4 bg-blue-100 border-2 border-blue-300 rounded-lg">
      <div class="flex items-center justify-center">
        <svg class="animate-spin h-6 w-6 mr-3 text-blue-600" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="font-semibold">Ukládám hodnocení...</span>
      </div>
    </div>
  `);
  
  try {
    const response = await fetch('../api/organizer_submit_result.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        result_id: resultId,
        status,
        points,
        note
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Přidat do historie
      scanHistory.unshift({
        team: data.team,
        status,
        points: data.points_awarded,
        timestamp: new Date()
      });
      
      // Obnovit seznam aktuálních týmů
      await refreshCurrentTeams(true);
      
      const statusText = status === 'done' ? 'Dokončeno' : 'Nesplněno';
      const statusColor = status === 'done' ? 'green' : 'red';
      
      showEvalResult(`
        <div class="p-4 bg-${statusColor}-100 border-2 border-${statusColor}-400 rounded-lg">
          <div class="font-semibold text-xl mb-2 text-${statusColor}-800 flex items-center">
            <span class="text-2xl mr-2">✓</span>
            Hodnocení uloženo
          </div>
          <div class="text-sm text-${statusColor}-700 mb-4">
            Tým <strong>${escapeHtml(data.team.name)}</strong> získal <strong>${data.points_awarded} bodů</strong>
            <br>
            <span class="text-xs">Celkové body týmu: ${data.team.points}</span>
          </div>
          <button onclick="resetEvalResult()" 
                  class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 font-semibold shadow-md">
            Skenovat další tým
          </button>
        </div>
      `);
    } else {
      showEvalResult(`
        <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
          <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
            <span class="text-2xl mr-2">✗</span>
            Chyba
          </div>
          <div class="text-sm text-red-700">${escapeHtml(data.message)}</div>
        </div>
      `);
    }
  } catch (error) {
    console.log(error)
    showEvalResult(`
      <div class="p-4 bg-red-100 border-2 border-red-400 rounded-lg">
        <div class="font-semibold text-lg mb-2 text-red-800 flex items-center">
          <span class="text-2xl mr-2">✗</span>
          Chyba připojení
        </div>
        <div class="text-sm text-red-700">${escapeHtml(error.message)}</div>
      </div>
    `);
  }
}

// Obnovení seznamu aktuálních týmů
async function refreshCurrentTeams(silent = false) {
  if (!currentStation) {
    document.getElementById('currentTeamsList').innerHTML = 
      '<p class="text-gray-500 text-center py-8">Nejprve vyberte stanoviště</p>';
    return;
  }
  
  if (!silent) {
    document.getElementById('currentTeamsList').innerHTML = 
      '<p class="text-gray-500 text-center py-8">Načítám...</p>';
  }
  
  try {
    const response = await fetch(`../api/organizer_current_teams.php?station_id=${currentStation.id}`);
    const data = await response.json();
    
    if (data.success) {
      currentTeams = data.teams;
      renderCurrentTeams();
      updateOccupancyDisplay();
      
      // Aktualizovat badge
      document.getElementById('currentCount').textContent = data.count;
    } else {
      if (!silent) {
        document.getElementById('currentTeamsList').innerHTML = 
          `<p class="text-red-500 text-center py-8">${escapeHtml(data.message)}</p>`;
      }
    }
  } catch (error) {
    if (!silent) {
      document.getElementById('currentTeamsList').innerHTML = 
        `<p class="text-red-500 text-center py-8">Chyba: ${escapeHtml(error.message)}</p>`;
    }
  }
}

refreshCurrentTeams()

// Vykreslení seznamu aktuálních týmů
function renderCurrentTeams() {
  const list = document.getElementById('currentTeamsList');
  
  if (currentTeams.length === 0) {
    list.innerHTML = '<p class="text-gray-500 text-center py-8">Žádný tým není aktuálně přítomen</p>';
    return;
  }
  
  const html = currentTeams.map(team => {
    const time = new Date(team.created_at).toLocaleTimeString('cs-CZ', {hour: '2-digit', minute: '2-digit'});
    const duration = team.minutes_present;
    
    let durationColor = 'text-green-600';
    if (duration > 30) durationColor = 'text-red-600';
    else if (duration > 15) durationColor = 'text-orange-600';
    
    return `
      <div class="p-4 bg-white border-2 border-blue-200 rounded-lg hover:shadow-md transition">
        <div class="flex justify-between items-start mb-3">
          <div>
            <div class="font-semibold text-lg flex items-center">
              <span class="w-2 h-2 bg-green-500 rounded-full mr-2 pulse-dot"></span>
              ${escapeHtml(team.team_name)}
            </div>
            <div class="text-sm text-gray-600">
              Kód: <span class="font-mono font-semibold">${escapeHtml(team.team_code)}</span>
            </div>
          </div>
          <div class="text-right text-sm">
            <div class="text-gray-500">${time}</div>
            <div class="${durationColor} font-semibold">${duration} min</div>
          </div>
        </div>
        <button onclick="showEvaluationForResult(${team.result_id}, ${team.team_id}, '${escapeHtml(team.team_name)}', ${team.team_points}, ${JSON.stringify(currentStation).replace(/"/g, '&quot;')})" 
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 font-semibold">
          Vyhodnotit
        </button>
      </div>
    `;
  }).join('');
  
  list.innerHTML = html;
}

// Reset skenování týmu
function resetTeamScan() {
  document.getElementById('teamResult').classList.add('hidden');
  document.getElementById('manualTeamCode').value = '';
}

// Zobrazení výsledku skenování týmu
function showTeamResult(html) {
  const resultEl = document.getElementById('teamResult');
  resultEl.innerHTML = html;
  resultEl.classList.remove('hidden');
}

function resetEvalResult() {
  document.getElementById('evalResult').classList.add('hidden');
}

function showEvalResult(html) {
  const resultEl = document.getElementById('evalResult');
  resultEl.innerHTML = html;
  resultEl.classList.remove('hidden');
}

// Skenování QR kódu týmu
function startTeamScanner() {
  if (!currentStation) {
    alert('Nejprve vyberte stanoviště!');
    return;
  }
  
  if (isTeamScanning) return;
  isTeamScanning = true;
  
  const config = {
    fps: 10,
    qrbox: function(w, h) {
      const minEdge = Math.min(w, h);
      const qrboxSize = Math.floor(minEdge * 0.7);
      return { width: qrboxSize, height: qrboxSize };
    },
    aspectRatio: 1.0
  };
  
  if (!teamScanner) {
    teamScanner = new Html5Qrcode("teamReader");
  }
  
  teamScanner.start(
    { facingMode: "environment" },
    config,
    (decodedText) => {
      if (!isTeamScanning) return;
      isTeamScanning = false;
      teamScanner.stop();
      processTeamToken(decodedText, false);
    },
    (error) => {
      // Ignorovat chyby skenování
    }
  ).catch(err => {
    console.error('Camera error:', err);
    isTeamScanning = false;
    alert('Nepodařilo se spustit kameru. Zkuste zadat kód ručně.');
  });
}

// Vykreslení historie
function renderHistory() {
  const historyList = document.getElementById('historyList');
  
  if (scanHistory.length === 0) {
    historyList.innerHTML = '<p class="text-gray-500 text-center py-8">Zatím nebyl vyhodnocen žádný tým</p>';
    return;
  }
  
  const html = scanHistory.map(item => {
    const statusColor = item.status === 'done' ? 'green' : 'red';
    const statusText = item.status === 'done' ? 'Dokončeno' : 'Nesplněno';
    const statusIcon = item.status === 'done' ? '✓' : '✗';
    const time = item.timestamp.toLocaleTimeString('cs-CZ');
    
    return `
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="flex justify-between items-start mb-2">
          <div class="font-semibold">${escapeHtml(item.team.name)}</div>
          <div class="text-sm text-gray-500">${time}</div>
        </div>
        <div class="flex justify-between items-center text-sm">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-${statusColor}-100 text-${statusColor}-800">
            ${statusIcon} ${statusText}
          </span>
          <span class="font-semibold text-blue-600 text-lg">${item.points} bodů</span>
        </div>
      </div>
    `;
  }).join('');
  
  historyList.innerHTML = html;
}

// HTML escape
function escapeHtml(s) {
  if (!s) return '';
  return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[m]);
}

// Event listeners
document.getElementById('startStationScan').addEventListener('click', startStationScanner);
document.getElementById('verifyPin').addEventListener('click', verifyPinCode);
document.getElementById('stationPin').addEventListener('keydown', (e) => {
  if (e.key === 'Enter') verifyPinCode();
});

document.getElementById('startTeamScan').addEventListener('click', startTeamScanner);
document.getElementById('manualTeamSend').addEventListener('click', () => {
  const code = document.getElementById('manualTeamCode').value.trim();
  if (code) processTeamToken(code, false);
});
document.getElementById('manualTeamCode').addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    const code = document.getElementById('manualTeamCode').value.trim();
    if (code) processTeamToken(code, false);
  }
});

document.getElementById('startManageTeamScan').addEventListener('click', startManageTeamScanner);
document.getElementById('loadTeamByCode').addEventListener('click', loadTeamByCode);
document.getElementById('manageTeamCode').addEventListener('keydown', (e) => {
  if (e.key === 'Enter') loadTeamByCode();
});

// Inicializace
updateCurrentStationDisplay();
updateScanSection();

// Cleanup při zavření stránky
window.addEventListener('beforeunload', () => {
  stopAutoRefresh();
});
</script>
</body>
</html>