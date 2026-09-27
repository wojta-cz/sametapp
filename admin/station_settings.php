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
    if ($_POST['action'] === 'update_station_settings') {
        $capacity = intval($_POST['capacity'] ?? 0);
        $max_points = $_POST['max_points'] !== '' ? intval($_POST['max_points']) : null;
        $min_points = intval($_POST['min_points'] ?? 0);
        $once_only = isset($_POST['once_only']) ? 1 : 0;
        $organizer_notes = trim($_POST['organizer_notes'] ?? '');
        
        // NEW: Handle start_time for timed station activation
        $start_time = null;
        if (!empty($_POST['start_time'])) {
            $start_time = $_POST['start_time'];
        }
        
        $stmt = $pdo->prepare("
            UPDATE stations 
            SET capacity = ?, max_points = ?, min_points = ?, once_only = ?, organizer_notes = ?, start_time = ?
            WHERE id = ?
        ");
        $stmt->execute([$capacity, $max_points, $min_points, $once_only, $organizer_notes, $start_time, $station_id]);
        
        header('Location: station_settings.php?id=' . $station_id . '&success=1');
        exit;
    }
}

// Get station details
$stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
$stmt->execute([$station_id]);
$station = $stmt->fetch();

if (!$station) {
    header('Location: stations.php');
    exit;
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Nastavení stanoviště — <?= htmlspecialchars($station['name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
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
            Nastavení: <?= htmlspecialchars($station['name']) ?>
          </h1>
        </div>
        <?php if ($station['type'] === 'virtual'): ?>
        <div class="flex items-center space-x-3">
          <a href="./virtual_station_tasks.php?id=<?=$station_id?>" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 font-semibold">
            📝 Spravovat úkoly
          </a>
          <a href="./virtual_station_settings.php?id=<?=$station_id?>" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold">
            🎨 Vizuální nastavení
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="./stations.php">Stanoviště</a>
      <span>→</span>
      <span>Základní nastavení</span>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      Nastavení bylo úspěšně uloženo.
    </div>
    <?php endif; ?>

    <div class="bg-white shadow rounded-lg p-6">
      <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_station_settings">
        
        <!-- NEW: Timed Station Activation -->
        <div class="border-b pb-6">
          <h3 class="text-sm font-medium text-gray-900 mb-4">⏰ Časované spuštění</h3>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Čas spuštění stanoviště
            </label>
            <input 
              type="datetime-local" 
              name="start_time" 
              value="<?= $station['start_time'] ? date('Y-m-d\TH:i', strtotime($station['start_time'])) : '' ?>"
              class="w-full border-gray-300 rounded-md"
            >
            <p class="mt-1 text-sm text-gray-500">
              Pokud je nastaveno, stanoviště se zobrazí týmům až od tohoto času. 
              Nechte prázdné pro okamžité zpřístupnění.
            </p>
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            👥 Kapacita stanoviště
          </label>
          <input 
            type="number" 
            name="capacity" 
            value="<?= $station['capacity'] ?>"
            min="0"
            class="w-full border-gray-300 rounded-md"
          >
          <p class="mt-1 text-sm text-gray-500">
            Maximální počet týmů, které mohou být na stanovišti současně. 
            Hodnota 0 = neomezená kapacita.
          </p>
        </div>

        <div class="border-t pt-6">
          <h3 class="text-sm font-medium text-gray-900 mb-4">⭐ Bodování</h3>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Minimální body
              </label>
              <input 
                type="number" 
                name="min_points" 
                value="<?= $station['min_points'] ?>"
                min="0"
                class="w-full border-gray-300 rounded-md"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Maximální body
              </label>
              <input 
                type="number" 
                name="max_points" 
                value="<?= $station['max_points'] ?? '' ?>"
                min="0"
                placeholder="Nevyplněno = pevný počet bodů"
                class="w-full border-gray-300 rounded-md"
              >
            </div>
          </div>
          
          <p class="mt-2 text-sm text-gray-500">
            <strong>Proměnlivé bodování:</strong> Pokud je vyplněn maximální počet bodů, organizátor může udělit body v rozmezí 
            <?= $station['min_points'] ?>-<?= $station['max_points'] ?? $station['reward_points'] ?> bodů pomocí posuvníku.
            <br>
            <strong>Fixní bodování:</strong> Pokud není vyplněn maximální počet bodů, organizátor vidí pouze tlačítka "Splněno" (<?= $station['reward_points'] ?> bodů) nebo "Nesplněno" (0 bodů).
          </p>
        </div>

        <div class="border-t pt-6">
          <div class="flex items-start">
            <div class="flex items-center h-5">
              <input 
                type="checkbox" 
                id="once_only" 
                name="once_only" 
                value="1"
                <?= $station['once_only'] ? 'checked' : '' ?>
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              >
            </div>
            <div class="ml-3">
              <label for="once_only" class="font-medium text-gray-900">
                🔒 Účast pouze jednou
              </label>
              <p class="text-sm text-gray-600">
                Každý tým může toto stanoviště absolvovat pouze jednou. 
                Po dokončení se stanoviště už nebude týmu zobrazovat.
              </p>
            </div>
          </div>
        </div>

        <div class="border-t pt-6">
          <label class="block text-sm font-medium text-gray-900 mb-2">
            📝 Poznámky pro organizátory
          </label>
          <textarea 
            name="organizer_notes" 
            rows="4"
            class="w-full border-gray-300 rounded-md"
            placeholder="Např.: Hodnoťte kreativitu a originalitu řešení. Za perfektní provedení dejte plný počet bodů."
          ><?= htmlspecialchars($station['organizer_notes'] ?? '') ?></textarea>
          <p class="mt-1 text-sm text-gray-500">
            Tyto poznámky uvidí organizátor při skenování týmu na tomto stanovišti. 
            Můžete zde uvést kritéria hodnocení, na co se zaměřit, apod.
          </p>
        </div>

        <div class="pt-4 border-t">
          <button 
            type="submit" 
            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold"
          >
            💾 Uložit nastavení
          </button>
        </div>
      </form>
    </div>

    <div class="mt-6 bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Statistiky stanoviště</h3>
      
      <?php
      // Get statistics
      $stmt = $pdo->prepare("
        SELECT 
          COUNT(DISTINCT team_id) as total_teams,
          COUNT(*) as total_attempts,
          SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed,
          SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
          SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
          AVG(CASE WHEN status = 'done' THEN points ELSE NULL END) as avg_points
        FROM results
        WHERE station_id = ?
      ");
      $stmt->execute([$station_id]);
      $stats = $stmt->fetch();
      ?>
      
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="p-4 bg-gray-50 rounded">
          <div class="text-2xl font-bold text-gray-900"><?= $stats['total_teams'] ?></div>
          <div class="text-sm text-gray-600">Celkem týmů</div>
        </div>
        
        <div class="p-4 bg-green-50 rounded">
          <div class="text-2xl font-bold text-green-700"><?= $stats['completed'] ?></div>
          <div class="text-sm text-gray-600">Dokončeno</div>
        </div>
        
        <div class="p-4 bg-red-50 rounded">
          <div class="text-2xl font-bold text-red-700"><?= $stats['failed'] ?></div>
          <div class="text-sm text-gray-600">Neúspěšných</div>
        </div>
        
        <div class="p-4 bg-yellow-50 rounded">
          <div class="text-2xl font-bold text-yellow-700"><?= $stats['pending'] ?></div>
          <div class="text-sm text-gray-600">Čekajících</div>
        </div>
        
        <div class="p-4 bg-blue-50 rounded">
          <div class="text-2xl font-bold text-blue-700">
            <?= $stats['avg_points'] ? number_format($stats['avg_points'], 1) : '0' ?>
          </div>
          <div class="text-sm text-gray-600">Průměr bodů</div>
        </div>
        
        <div class="p-4 bg-purple-50 rounded">
          <div class="text-2xl font-bold text-purple-700"><?= $station['current_occupancy'] ?></div>
          <div class="text-sm text-gray-600">Aktuální obsazenost</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>