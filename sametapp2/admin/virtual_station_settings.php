<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = $_SESSION['user'];

if ($user['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$pdo = db();

// Get station ID
$station_id = intval($_GET['id'] ?? 0);
if (!$station_id) {
    header('Location: stations.php');
    exit;
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_virtual_settings') {
        $custom_background = trim($_POST['custom_background'] ?? '');
        $custom_colors = json_encode([
            'primary' => trim($_POST['color_primary'] ?? '#667eea'),
            'secondary' => trim($_POST['color_secondary'] ?? '#764ba2'),
            'text' => trim($_POST['color_text'] ?? '#ffffff')
        ]);
        $station_logo = trim($_POST['station_logo'] ?? '');
        $audio_atmosphere = trim($_POST['audio_atmosphere'] ?? '');
        $show_score = isset($_POST['show_score']) ? 1 : 0;
        $require_physical_qr = isset($_POST['require_physical_qr']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE stations 
            SET custom_background = ?, custom_colors = ?, station_logo = ?,
                audio_atmosphere = ?, show_score = ?, require_physical_qr = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $custom_background, $custom_colors, $station_logo,
            $audio_atmosphere, $show_score, $require_physical_qr, $station_id
        ]);
        
        header('Location: virtual_station_settings.php?id=' . $station_id . '&success=1');
        exit;
    }
}

// Get station details
$stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
$stmt->execute([$station_id]);
$station = $stmt->fetch();

if (!$station || $station['type'] !== 'virtual') {
    header('Location: stations.php');
    exit;
}

// Get task count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM station_tasks WHERE station_id = ?");
$stmt->execute([$station_id]);
$task_count = $stmt->fetchColumn();

$custom_colors = json_decode($station['custom_colors'] ?? '{}', true);
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Virtuální stanoviště — <?= htmlspecialchars($station['name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .section-card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .section-title {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 16px;
      color: #1f2937;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .info-banner {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 24px;
    }
    .breadcrumb {
      background: white;
      padding: 12px 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
    }
    .breadcrumb a {
      color: #667eea;
      text-decoration: none;
    }
    .breadcrumb a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <nav class="bg-white shadow-sm border-b mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <a href="./stations.php" class="text-blue-600 hover:text-blue-800 mr-4">← Stanoviště</a>
          <h1 class="text-xl font-bold text-gray-900">
            Virtuální stanoviště: <?= htmlspecialchars($station['name']) ?>
          </h1>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="./stations.php">Stanoviště</a>
      <span>→</span>
      <span>Nastavení virtuálního stanoviště</span>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      Nastavení bylo úspěšně uloženo.
    </div>
    <?php endif; ?>

    <!-- Quick Actions Banner -->
    <div class="info-banner">
      <h2 class="text-2xl font-bold mb-3">🎮 Správa virtuálního stanoviště</h2>
      <p class="mb-4">Virtuální stanoviště funguje s více úkoly. Nejprve nastavte úkoly, pak upravte vizuální styl.</p>
      <div class="flex gap-3">
        <a href="./virtual_station_tasks.php?id=<?=$station_id?>" class="px-6 py-3 bg-white text-purple-700 rounded-lg font-semibold hover:bg-gray-100 transition">
          📝 Spravovat úkoly (<?=$task_count?>)
        </a>
        <a href="./station_settings.php?id=<?=$station_id?>" class="px-6 py-3 bg-purple-800 text-white rounded-lg font-semibold hover:bg-purple-900 transition">
          ⚙️ Základní nastavení
        </a>
      </div>
    </div>

    <?php if ($task_count === 0): ?>
    <div class="section-card bg-yellow-50 border-2 border-yellow-400">
      <div class="flex items-start gap-4">
        <svg class="w-12 h-12 text-yellow-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div>
          <h3 class="text-lg font-bold text-yellow-800 mb-2">⚠️ Žádné úkoly!</h3>
          <p class="text-yellow-700 mb-3">
            Toto virtuální stanoviště zatím nemá žádné úkoly. Týmy nebudou moci toto stanoviště absolvovat.
          </p>
          <a href="./virtual_station_tasks.php?id=<?=$station_id?>" class="inline-block px-4 py-2 bg-yellow-600 text-white rounded-lg font-semibold hover:bg-yellow-700">
            Přidat první úkol →
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-0">
      <input type="hidden" name="action" value="update_virtual_settings">
      
      <!-- Vizuální úpravy -->
      <div class="section-card">
        <div class="section-title">
          🎨 Vizuální úpravy
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Vlastní pozadí (URL)</label>
            <input type="url" name="custom_background" value="<?=htmlspecialchars($station['custom_background']??'')?>" class="w-full border-gray-300 rounded-md" placeholder="https://...">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Logo stanoviště (URL)</label>
            <input type="url" name="station_logo" value="<?=htmlspecialchars($station['station_logo']??'')?>" class="w-full border-gray-300 rounded-md" placeholder="https://...">
          </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Primární barva</label>
            <input type="color" name="color_primary" value="<?=$custom_colors['primary']??'#667eea'?>" class="w-full h-10 border-gray-300 rounded-md">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sekundární barva</label>
            <input type="color" name="color_secondary" value="<?=$custom_colors['secondary']??'#764ba2'?>" class="w-full h-10 border-gray-300 rounded-md">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Barva textu</label>
            <input type="color" name="color_text" value="<?=$custom_colors['text']??'#ffffff'?>" class="w-full h-10 border-gray-300 rounded-md">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Zvuková atmosféra (URL)</label>
          <input type="url" name="audio_atmosphere" value="<?=htmlspecialchars($station['audio_atmosphere']??'')?>" class="w-full border-gray-300 rounded-md" placeholder="https://...">
        </div>
      </div>

      <!-- Zobrazení -->
      <div class="section-card">
        <div class="section-title">
          👁️ Zobrazení
        </div>
        <div class="space-y-3">
          <label class="flex items-center">
            <input type="checkbox" name="show_score" value="1" <?=$station['show_score']?'checked':''?> class="rounded border-gray-300 text-blue-600">
            <span class="ml-2 text-sm font-medium text-gray-700">Zobrazit skóre po dokončení</span>
          </label>
        </div>
      </div>

      <!-- Speciální funkce -->
      <div class="section-card">
        <div class="section-title">
          🔧 Speciální funkce
        </div>
        <div class="space-y-4">
          <label class="flex items-start">
            <input type="checkbox" name="require_physical_qr" value="1" <?=$station['require_physical_qr']?'checked':''?> class="rounded border-gray-300 text-blue-600 mt-1">
            <span class="ml-2">
              <span class="text-sm font-medium text-gray-700 block">Vyžadovat načtení fyzického QR kódu</span>
              <span class="text-xs text-gray-500">Virtuální úkol bude přístupný až po naskenování fyzického QR kódu stanoviště</span>
            </span>
          </label>
        </div>
      </div>

      <div class="section-card">
        <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold text-lg">
          💾 Uložit nastavení virtuálního stanoviště
        </button>
      </div>
    </form>
  </div>
</body>
</html>