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
    update_virtual_station_codes($station_id);
    header('Location: station_codes?regenerated=' . $station_id);
    exit;
}

// Get all active stations
$stmt = $pdo->query("SELECT * FROM stations WHERE active = 1 ORDER BY id");
$stations = $stmt->fetchAll();

// Generate QR tokens for stations that don't have them
foreach ($stations as &$station) {
    $codes = update_virtual_station_codes($station['id']);
    $station['virtual_qr_token'] = $codes['qr_token'];
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

  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  body {
    margin: 0;
    padding: 0;
    background: white;
  }

  .no-print, nav, footer {
    display: none !important;
  }

  /* A4 stránka — standard 210 × 297 mm */
  .print-page {
    page-break-after: always;
    width: 210mm;
    height: 297mm;

    /* Vnitřní okraje (A4 doporučení) */
    padding: 20mm;

    box-sizing: border-box;

    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;

    text-align: center;
    background: white;
    font-family: 'Arial', sans-serif;
    position: relative;
  }

  /* Dekorativní horní lišta */
  .print-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 8mm;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  }

  /* Hlavní nadpis "STANOVIŠTĚ" */
  .print-label {
    font-size: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #6b7280;
    margin-bottom: 8mm;
    margin-top: 15mm;
  }

  /* Název stanoviště — velký a výrazný */
  .print-title {
    font-size: 48px;
    font-weight: 900;
    margin-bottom: 15mm;
    color: #111827;
    line-height: 1.2;
    max-width: 160mm;
  }

  /* QR kód wrapper — s elegantním rámečkem a stínem */
  .qr-wrapper {
    padding: 10mm;
    background: white;
    border: 4px solid #3b82f6;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    display: inline-block;
    margin-bottom: 15mm;
  }

  .qr-wrapper img {
    width: 100mm;
    height: 100mm;
    display: block;
  }

  /* Instrukce pod QR kódem */
  .print-instructions {
    font-size: 18px;
    color: #374151;
    line-height: 1.6;
    max-width: 150mm;
    margin-bottom: 10mm;
  }

  .print-instructions strong {
    color: #1f2937;
    font-weight: 700;
  }

  /* Dekorativní spodní lišta */
  .print-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 8mm;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  }

  /* Ikona QR */
  .qr-icon {
    font-size: 32px;
    margin-bottom: 5mm;
    opacity: 0.7;
  }
}

/* Screen styles */
.station-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  padding: 1.5rem;
  transition: transform 0.2s;
}

.station-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 no-print">
      <?php foreach ($stations as $s): 
        // Generate QR code image
        ob_start();
        QRcode::png($s['virtual_qr_token'], null, QR_ECLEVEL_M, 8);
        $qr_image = ob_get_clean();
        $qr_base64 = base64_encode($qr_image);
      ?>
      <div class="station-card">
        <h3 class="text-lg font-bold text-gray-900 mb-3"><?=htmlspecialchars($s['name'])?></h3>
        <div class="flex justify-center mb-3">
          <img src="data:image/png;base64,<?=$qr_base64?>" alt="QR kód" class="w-48 h-48 border-2 border-gray-200 rounded">
        </div>
        <p class="text-sm text-gray-600">Naskenujte tento QR kód pro rychlý přístup ke stanovišti</p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Print version -->
    <div class="print-only">
      <?php foreach ($stations as $s): 
        // Generate QR code image
        ob_start();
        QRcode::png($s['virtual_qr_token'], null, QR_ECLEVEL_M, 10);
        $qr_image = ob_get_clean();
        $qr_base64 = base64_encode($qr_image);
      ?>
      <div class="print-page">
        <div class="print-header"></div>
        
        <div class="qr-icon">📱</div>
        
        <div class="print-label">Stanoviště</div>
        
        <h1 class="print-title"><?=htmlspecialchars($s['name'])?></h1>
        
        <div class="qr-wrapper">
          <img src="data:image/png;base64,<?=$qr_base64?>" alt="QR kód">
        </div>
        
        <div class="print-instructions">
          <strong>Naskenujte QR kód</strong> pomocí mobilní aplikace<br>
          a splňte úkoly pro dokončení stanoviště.
        </div>
        
        <div class="print-footer"></div>
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