<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/station_functions.php';
require_once __DIR__ . '/../includes/libs/phpqrcode.php';
require_login();
$user = $_SESSION['user'];

if ($user['role'] !== 'admin') {
    header('Location: ../dashboard');
    exit;
}

$pdo = db();

// Handle regenerate codes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'regenerate') {
    $station_id = intval($_POST['station_id']);
    update_station_codes($station_id);
    header('Location: station_codes?regenerated=' . $station_id);
    exit;
}

// Get all active stations
$stmt = $pdo->query("SELECT * FROM stations WHERE active = 1 ORDER BY id");
$stations = $stmt->fetchAll();

// Generate QR tokens for stations that don't have them
foreach ($stations as &$station) {
    if (empty($station['qr_token']) || empty($station['pin_code'])) {
        $codes = update_station_codes($station['id']);
        $station['qr_token'] = $codes['qr_token'];
        $station['pin_code'] = $codes['pin_code'];
    }
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>QR kódy a PIN stanovišť — Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      .no-print { display: none; }
      .page-break { page-break-after: always; }
      body { background: white; }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <nav class="bg-white shadow-sm border-b mb-6 no-print">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <a href="./stations" class="text-blue-600 hover:text-blue-800 mr-4">← Zpět na stanice</a>
          <h1 class="text-xl font-bold text-gray-900">QR kódy a PIN stanovišť</h1>
        </div>
        <div class="flex items-center space-x-3">
          <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            🖨️ Vytisknout vše
          </button>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php if (isset($_GET['regenerated'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded no-print">
      Kódy byly úspěšně vygenerovány.
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($stations as $s): 
        // Generate QR code image
        ob_start();
        QRcode::png($s['qr_token'], null, QR_ECLEVEL_M, 8);
        $qr_image = ob_get_clean();
        $qr_base64 = base64_encode($qr_image);
      ?>
      <div class="bg-white rounded-lg shadow-lg p-6 page-break">
        <!-- Header -->
        <div class="border-b pb-4 mb-4">
          <h2 class="text-2xl font-bold text-gray-900 mb-1"><?=htmlspecialchars($s['name'])?></h2>
          <p class="text-sm text-gray-600"><?=htmlspecialchars($s['description'])?></p>
          <?php if ($s['location']): ?>
          <p class="text-sm text-gray-500 mt-1">📍 <?=htmlspecialchars($s['location'])?></p>
          <?php endif; ?>
        </div>

        <!-- QR Code -->
        <div class="text-center mb-6">
          <div class="inline-block p-4 bg-white border-4 border-gray-200 rounded-lg">
            <img src="data:image/png;base64,<?=$qr_base64?>" alt="QR kód" class="w-48 h-48 mx-auto">
          </div>
          <p class="text-xs text-gray-500 mt-2">QR kód pro pořadatele</p>
        </div>

        <!-- PIN Code -->
        <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-6 text-center mb-4">
          <p class="text-sm text-gray-600 mb-2 font-semibold">PIN KÓD</p>
          <p class="text-5xl font-bold text-blue-600 font-mono tracking-widest"><?=$s['pin_code']?></p>
          <p class="text-xs text-gray-500 mt-2">Zadejte v aplikaci pořadatele</p>
        </div>

        <!-- Info -->
        <div class="text-sm text-gray-600 space-y-1 mb-4">
          <div class="flex justify-between">
            <span>Body za splnění:</span>
            <span class="font-semibold"><?=$s['reward_points']?></span>
          </div>
          <div class="flex justify-between">
            <span>Typ:</span>
            <span class="font-semibold"><?=$s['type'] === 'physical' ? 'Fyzická' : 'Virtuální'?></span>
          </div>
        </div>

        <!-- Actions -->
        <div class="no-print">
          <form method="POST" onsubmit="return confirm('Opravdu vygenerovat nové kódy? Staré přestanou fungovat.')">
            <input type="hidden" name="action" value="regenerate">
            <input type="hidden" name="station_id" value="<?=$s['id']?>">
            <button type="submit" class="w-full text-sm py-2 px-3 border border-gray-300 rounded-md hover:bg-gray-50">
              🔄 Vygenerovat nové kódy
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Instructions -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6 no-print">
      <h3 class="text-lg font-semibold text-blue-900 mb-3">📋 Instrukce pro pořadatele</h3>
      <div class="text-sm text-blue-800 space-y-2">
        <p><strong>1. Výběr stanoviště:</strong></p>
        <ul class="list-disc ml-6 space-y-1">
          <li>Naskenujte QR kód stanoviště pomocí aplikace pořadatele</li>
          <li>Nebo zadejte 4místný PIN kód ručně</li>
        </ul>
        
        <p class="mt-3"><strong>2. Skenování týmů:</strong></p>
        <ul class="list-disc ml-6 space-y-1">
          <li>Po výběru stanoviště můžete skenovat QR kódy týmů</li>
          <li>Zobrazí se detailní informace o týmu</li>
          <li>Ohodnoťte jejich výkon (dokončeno/nedokončeno/vlastní body)</li>
        </ul>
        
        <p class="mt-3"><strong>3. Bezpečnost:</strong></p>
        <ul class="list-disc ml-6 space-y-1">
          <li>QR kódy jsou platné 24 hodin</li>
          <li>PIN kódy jsou trvalé, ale lze je regenerovat</li>
          <li>Při regeneraci přestanou staré kódy fungovat</li>
        </ul>
      </div>
    </div>
  </div>
</body>
</html>