<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = $_SESSION['user'];

if ($user['role'] !== 'admin') {
    header('Location: ../dashboard');
    exit;
}

$pdo = db();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_settings') {
        $assignment_mode = $_POST['assignment_mode'] ?? 'free';
        $time_lock_enabled = isset($_POST['time_lock_enabled']) ? '1' : '0';
        
        // Update or insert assignment_mode
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('assignment_mode', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$assignment_mode, $assignment_mode]);
        
        // Update or insert time_lock_enabled
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('time_lock_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$time_lock_enabled, $time_lock_enabled]);
        
        header('Location: settings?success=1');
        exit;
    }
}

// Get current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

$assignment_mode = $settings['assignment_mode'] ?? 'free';
$time_lock_enabled = ($settings['time_lock_enabled'] ?? '0') === '1';
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Nastavení systému — Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <nav class="bg-white shadow-sm border-b mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <a href="./index" class="text-blue-600 hover:text-blue-800 mr-4">← Admin</a>
          <h1 class="text-xl font-bold text-gray-900">Nastavení systému</h1>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      Nastavení bylo úspěšně uloženo.
    </div>
    <?php endif; ?>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-6">🔒 Časový zámek stanovišť</h2>
      
      <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_settings">
        
        <div class="space-y-4">
          <div class="flex items-start">
            <div class="flex items-center h-5">
              <input 
                type="checkbox" 
                id="time_lock_enabled" 
                name="time_lock_enabled" 
                value="1"
                <?= $time_lock_enabled ? 'checked' : '' ?>
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              >
            </div>
            <div class="ml-3">
              <label for="time_lock_enabled" class="font-medium text-gray-900">
                🔒 Aktivovat časový zámek
              </label>
              <p class="text-sm text-gray-600">
                Když je časový zámek aktivní, běžní uživatelé (hráči) nemohou vidět ani přiřazovat stanoviště. 
                Mohou se pouze přihlásit a spravovat svůj tým. Administrátoři a organizátoři mají vždy plný přístup.
              </p>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Režim přiřazování stanovišť</h3>
          
          <div class="space-y-4">
            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input 
                  type="radio" 
                  id="mode_free" 
                  name="assignment_mode" 
                  value="free" 
                  <?= $assignment_mode === 'free' ? 'checked' : '' ?>
                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                >
              </div>
              <div class="ml-3">
                <label for="mode_free" class="font-medium text-gray-900">
                  🔓 Volný pohyb
                </label>
                <p class="text-sm text-gray-600">
                  Týmy se mohou pohybovat po jakýchkoliv stanovištích v jakémkoliv pořadí. 
                  Týmy si samy vybírají, kam půjdou dál.
                </p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input 
                  type="radio" 
                  id="mode_assigned" 
                  name="assignment_mode" 
                  value="assigned" 
                  <?= $assignment_mode === 'assigned' ? 'checked' : '' ?>
                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                >
              </div>
              <div class="ml-3">
                <label for="mode_assigned" class="font-medium text-gray-900">
                  🎯 Přiřazená stanoviště
                </label>
                <p class="text-sm text-gray-600">
                  Systém automaticky přiřadí týmům následující stanoviště podle aktuální obsazenosti a kapacity stanovišť.
                  Týmy budou směrovány na nejméně obsazená stanoviště s dostupnou kapacitou.
                </p>
              </div>
            </div>
          </div>
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

      <div class="mt-8 p-4 bg-blue-50 rounded-lg">
        <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Jak to funguje?</h3>
        <ul class="text-sm text-blue-800 space-y-2">
          <li><strong>Časový zámek:</strong> Když je aktivní, běžní hráči nemohou vidět seznam stanovišť ani dashboard s informacemi o stanovištích. Místo toho vidí zprávu, že aplikace je časově uzamčena. Administrátoři a organizátoři mají vždy plný přístup bez omezení.</li>
          <li><strong>Volný pohyb:</strong> Týmy vidí seznam všech aktivních stanovišť a mohou si vybrat, kam půjdou. Ideální pro menší akce nebo když chcete dát účastníkům větší svobodu.</li>
          <li><strong>Přiřazená stanoviště:</strong> Po dokončení stanoviště systém automaticky přiřadí tým na další stanoviště s nejnižší obsazeností. Pomáhá rovnoměrně rozložit týmy a minimalizovat čekání. Ideální pro větší akce s mnoha týmy.</li>
        </ul>
      </div>
    </div>

    <div class="mt-6 bg-white shadow rounded-lg p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">📊 Aktuální stav stanovišť</h2>
      
      <?php
      $stmt = $pdo->query("
        SELECT 
          s.id,
          s.name,
          s.capacity,
          s.current_occupancy,
          s.active,
          COUNT(DISTINCT t.id) as teams_here
        FROM stations s
        LEFT JOIN teams t ON t.current_station_id = s.id
        WHERE s.active = 1
        GROUP BY s.id
        ORDER BY s.id
      ");
      $stations_status = $stmt->fetchAll();
      ?>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stanoviště</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kapacita</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktuální obsazenost</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Týmy zde</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($stations_status as $st): ?>
            <tr>
              <td class="px-4 py-3 text-sm font-medium text-gray-900">
                <?= htmlspecialchars($st['name']) ?>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                <?= $st['capacity'] > 0 ? $st['capacity'] : '∞' ?>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                <?= $st['current_occupancy'] ?>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                <?= $st['teams_here'] ?>
              </td>
              <td class="px-4 py-3 text-sm">
                <?php 
                $is_full = $st['capacity'] > 0 && $st['current_occupancy'] >= $st['capacity'];
                if ($is_full): 
                ?>
                  <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">Plné</span>
                <?php else: ?>
                  <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Dostupné</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>