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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO stations (name, description, type, reward_points, location, correct_answer, active) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['type'],
            intval($_POST['points']),
            $_POST['location'] ?: null,
            $_POST['correct_answer'] ?: null,
            1
        ]);
        $new_station_id = $pdo->lastInsertId();
        
        // Redirect to appropriate settings page
        if ($_POST['type'] === 'virtual') {
            header('Location: virtual_station_tasks.php?id=' . $new_station_id . '&new=1');
        } else {
            header('Location: station_settings.php?id=' . $new_station_id . '&success=1');
        }
        exit;
    } elseif ($_POST['action'] === 'toggle_active') {
        $id = intval($_POST['station_id']);
        $stmt = $pdo->prepare("UPDATE stations SET active = NOT active WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: stations.php');
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $id = intval($_POST['station_id']);
        $stmt = $pdo->prepare("DELETE FROM stations WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: stations.php?deleted=1');
        exit;
    }
}

// Get all stations
$stmt = $pdo->query("SELECT * FROM stations ORDER BY id");
$stations = $stmt->fetchAll();

// Get task counts for virtual stations
$virtual_task_counts = [];
$stmt = $pdo->query("
    SELECT station_id, COUNT(*) as task_count 
    FROM station_tasks 
    GROUP BY station_id
");
while ($row = $stmt->fetch()) {
    $virtual_task_counts[$row['station_id']] = $row['task_count'];
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Správa stanic — Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <nav class="bg-white shadow-sm border-b mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <a href="./index.php" class="text-blue-600 hover:text-blue-800 mr-4">← Admin</a>
          <h1 class="text-xl font-bold text-gray-900">Správa stanic</h1>
        </div>
        <div class="flex items-center space-x-3">
          <a href="./station_codes.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold">
            📱 QR kódy & PIN
          </a>
          <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            + Přidat stanici
          </button>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      Stanice byla úspěšně vytvořena.
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($stations as $s): ?>
      <div class="bg-white rounded-lg shadow p-6 <?=$s['active']?'':'opacity-50'?>">
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-lg font-semibold text-gray-900"><?=htmlspecialchars($s['name'])?></h3>
          <span class="px-2 py-1 text-xs rounded <?=$s['type']==='physical'?'bg-blue-100 text-blue-800':'bg-green-100 text-green-800'?>">
            <?=$s['type']==='physical'?'Fyzická':'Virtuální'?>
          </span>
        </div>
        
        <p class="text-sm text-gray-600 mb-4"><?=htmlspecialchars($s['description'])?></p>
        
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">Body:</span>
            <span class="font-semibold"><?=$s['reward_points']?></span>
          </div>
          <?php if ($s['type'] === 'virtual'): ?>
          <div class="flex justify-between">
            <span class="text-gray-500">Úkolů:</span>
            <span class="font-semibold"><?=$virtual_task_counts[$s['id']] ?? 0?></span>
          </div>
          <?php endif; ?>
          <?php if ($s['capacity'] > 0): ?>
          <div class="flex justify-between">
            <span class="text-gray-500">Kapacita:</span>
            <span class="font-semibold"><?=$s['current_occupancy']?>/<?=$s['capacity']?></span>
          </div>
          <?php endif; ?>
          <?php if ($s['location']): ?>
          <div class="flex justify-between">
            <span class="text-gray-500">Místo:</span>
            <span class="font-semibold"><?=htmlspecialchars($s['location'])?></span>
          </div>
          <?php endif; ?>
          <div class="flex justify-between">
            <span class="text-gray-500">Status:</span>
            <span class="font-semibold <?=$s['active']?'text-green-600':'text-red-600'?>">
              <?=$s['active']?'Aktivní':'Neaktivní'?>
            </span>
          </div>
        </div>

        <div class="mt-4 space-y-2">
          <?php if ($s['type'] === 'virtual'): ?>
          <a href="./virtual_station_tasks.php?id=<?=$s['id']?>" class="block w-full text-center text-sm py-2 px-3 bg-purple-600 text-white rounded-md hover:bg-purple-700">
            📝 Spravovat úkoly
          </a>
          <a href="./virtual_station_settings.php?id=<?=$s['id']?>" class="block w-full text-center text-sm py-2 px-3 bg-green-600 text-white rounded-md hover:bg-green-700">
            🎨 Vizuální nastavení
          </a>
          <?php else: ?>
          <a href="./station_settings.php?id=<?=$s['id']?>" class="block w-full text-center text-sm py-2 px-3 bg-purple-600 text-white rounded-md hover:bg-purple-700">
            ⚙️ Nastavení
          </a>
          <?php endif; ?>
          <div class="flex space-x-2">
            <form method="POST" class="flex-1">
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="station_id" value="<?=$s['id']?>">
              <button type="submit" class="w-full text-sm py-2 px-3 border border-gray-300 rounded-md hover:bg-gray-50">
                <?=$s['active']?'Deaktivovat':'Aktivovat'?>
              </button>
            </form>
            <form method="POST" onsubmit="return confirm('Opravdu smazat?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="station_id" value="<?=$s['id']?>">
              <button type="submit" class="text-sm py-2 px-3 text-red-600 border border-red-300 rounded-md hover:bg-red-50">
                Smazat
              </button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Create Modal -->
  <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Přidat novou stanici</h3>
        <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="create">
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Název stanice *</label>
          <input type="text" name="name" required class="w-full border-gray-300 rounded-md">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Popis úkolu *</label>
          <textarea name="description" rows="3" required class="w-full border-gray-300 rounded-md"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Typ *</label>
            <select name="type" id="stationType" onchange="toggleFields()" required class="w-full border-gray-300 rounded-md">
              <option value="physical">Fyzická</option>
              <option value="virtual">Virtuální</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Body *</label>
            <input type="number" name="points" value="10" required class="w-full border-gray-300 rounded-md">
          </div>
        </div>

        <div id="locationField">
          <label class="block text-sm font-medium text-gray-700 mb-1">Místo konání</label>
          <input type="text" name="location" class="w-full border-gray-300 rounded-md" placeholder="např. Tělocvična">
        </div>

        <div id="answerField" class="hidden">
          <label class="block text-sm font-medium text-gray-700 mb-1">Správná odpověď</label>
          <input type="text" name="correct_answer" class="w-full border-gray-300 rounded-md" placeholder="Pro virtuální stanice">
        </div>

        <div id="virtualInfo" class="hidden bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
          <p class="text-sm text-blue-800">
            <strong>💡 Tip:</strong> Po vytvoření virtuálního stanoviště budete přesměrováni na správu úkolů, kde můžete přidat jednotlivé úkoly pro týmy.
          </p>
        </div>

        <div class="flex justify-end space-x-3 pt-4">
          <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
            Zrušit
          </button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Vytvořit stanici
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function toggleFields() {
    const type = document.getElementById('stationType').value;
    const locationField = document.getElementById('locationField');
    const answerField = document.getElementById('answerField');
    const virtualInfo = document.getElementById('virtualInfo');
    
    if (type === 'physical') {
      locationField.classList.remove('hidden');
      answerField.classList.add('hidden');
      virtualInfo.classList.add('hidden');
    } else {
      locationField.classList.add('hidden');
      answerField.classList.remove('hidden');
      virtualInfo.classList.remove('hidden');
    }
  }
  </script>
</body>
</html>