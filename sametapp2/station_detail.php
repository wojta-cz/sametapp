<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = get_user_by_googleid($_SESSION['user']['google_id']);
$pdo = db();

$station_id = intval($_GET['id'] ?? 0);
if (!$station_id) {
    header('Location: stations');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND active=1");
$stmt->execute([$station_id]);
$station = $stmt->fetch();

if (!$station) {
    header('Location: stations');
    exit;
}

$completed = false;
if ($user['team_id']) {
    $stmt = $pdo->prepare("SELECT * FROM results WHERE team_id = ? AND station_id = ? AND status = 'done'");
    $stmt->execute([$user['team_id'], $station_id]);
    $completed = $stmt->fetch();
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <title><?=htmlspecialchars($station['name'])?> — Samet Festival</title>
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
      border: 1px solid rgba(0, 0, 0, 0.04);
    }
    
    .gradient-primary {
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
    }
    
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 10px 18px;
      border-radius: 24px;
      font-size: 14px;
      font-weight: 600;
      gap: 8px;
      border: 1px solid rgba(0, 0, 0, 0.08);
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
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:active {
      transform: translateY(0);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-virtual {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
    }
    
    .btn-virtual:hover {
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
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
    
    textarea {
      border: 2px solid #e5e7eb;
      border-radius: 16px;
      padding: 16px 20px;
      font-size: 16px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      width: 100%;
      resize: vertical;
    }
    
    textarea:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }
    
    .header-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 0 0 32px 32px;
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
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

  <div class="px-5">
    <div class="card p-7 mb-5 fade-in">
      <div class="flex justify-between items-start mb-5">
        <div class="flex-1">
          <h2 class="text-3xl font-bold text-gray-800 mb-4"><?=htmlspecialchars($station['name'])?></h2>
          <span class="badge <?=$station['type']==='physical'?'bg-red-100 text-red-800 border-red-200':'bg-green-100 text-green-800 border-green-200'?>">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <?php if ($station['type']==='physical'): ?>
              <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
              <?php else: ?>
              <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>
              <?php endif; ?>
            </svg>
            <span><?=$station['type']==='physical'?'Fyzická stanice':'Virtuální stanice'?></span>
          </span>
        </div>
        <div class="text-right ml-5">
          <p class="text-sm text-gray-500 font-medium">Odměna</p>
          <p class="text-4xl font-bold text-red-600"><?=$station['reward_points']?></p>
          <p class="text-sm text-gray-500">bodů</p>
        </div>
      </div>

      <?php if ($station['location']): ?>
      <div class="mb-5 p-5 bg-gradient-to-br from-red-50 to-red-100 rounded-2xl border border-red-200">
        <div class="flex items-center gap-3 text-red-800">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
          </svg>
          <div>
            <p class="text-sm font-semibold">Místo konání</p>
            <p class="font-bold text-lg"><?=htmlspecialchars($station['location'])?></p>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="mb-7">
        <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2 text-lg">
          <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
          </svg>
          <span>Popis úkolu</span>
        </h3>
        <p class="text-gray-700 whitespace-pre-line leading-relaxed text-base"><?=htmlspecialchars($station['description'])?></p>
      </div>

      <?php if ($completed): ?>
        <!-- Already completed -->
        <div class="p-6 bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-200 rounded-2xl">
          <div class="flex items-center gap-4">
            <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div>
              <p class="font-bold text-green-800 text-lg">Tento úkol jste již splnili!</p>
              <p class="text-base text-green-700 mt-1">Získali jste <?=$completed['points']?> bodů.</p>
            </div>
          </div>
        </div>
      <?php elseif (!$user['team_id']): ?>
        <!-- No team -->
        <div class="p-6 bg-gradient-to-br from-yellow-50 to-yellow-100 border-2 border-yellow-200 rounded-2xl">
          <div class="flex items-center gap-4 mb-4">
            <svg class="w-12 h-12 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
              <p class="font-bold text-yellow-800 text-lg">Nejste v žádném týmu</p>
              <p class="text-base text-yellow-700 mt-1">Pro splnění úkolu se musíte připojit k týmu.</p>
            </div>
          </div>
          <a href="./team" class="btn-primary">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
            </svg>
            <span>Přejít na správu týmu</span>
          </a>
        </div>
      <?php elseif ($station['type'] === 'physical'): ?>
        <!-- Physical station - show QR -->
        <div class="p-6 bg-gradient-to-br from-red-50 to-red-50 border-2 border-red-200 rounded-2xl">
          <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-lg">
            <svg class="w-7 h-7 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
            </svg>
            <span>Jak splnit tuto stanici</span>
          </h3>
          <ol class="text-base text-gray-700 space-y-3 mb-5 ml-1">
            <li class="flex items-start gap-3">
              <span class="flex-shrink-0 w-7 h-7 bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
              <span>Dostavte se na místo konání stanice</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="flex-shrink-0 w-7 h-7 bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
              <span>Splňte zadaný úkol</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="flex-shrink-0 w-7 h-7 bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-bold">3</span>
              <span>Nechte pořadatele naskenovat váš QR kód týmu</span>
            </li>
          </ol>
          <a href="./qr" class="btn-primary">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd"/>
              <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z"/>
            </svg>
            <span>Ukázat QR kód</span>
          </a>
        </div>
      <?php else: ?>
        <!-- Virtual station - go to interactive interface -->
        <div class="p-6 bg-gradient-to-br from-green-50 to-teal-50 border-2 border-green-200 rounded-2xl">
          <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-lg">
            <svg class="w-7 h-7 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>
            </svg>
            <span>Virtuální stanoviště</span>
          </h3>
          <p class="text-gray-700 mb-5">Toto je interaktivní virtuální stanoviště. Klikněte na tlačítko níže pro spuštění úkolu.</p>
          <a href="./virtual_station?id=<?=$station_id?>" class="btn-primary btn-virtual">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
            </svg>
            <span>Spustit virtuální stanoviště</span>
          </a>
        </div>
      <?php endif; ?>
    </div>
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
</body>
</html>