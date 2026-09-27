<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = $_SESSION['user'];

$team_id = $user['team_id'] ?? null;
if (!$team_id) {
    header('Location: stations?error=no_team');
    exit;
}

$pdo = db();

// Získat informace o týmu
$stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch();

// Pokud je zadáno station_id v URL, získat info o stanovišti
$station_id = intval($_GET['station_id'] ?? 0);
$station = null;
if ($station_id) {
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND active = 1");
    $stmt->execute([$station_id]);
    $station = $stmt->fetch();
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <title>Skenovat QR kód stanoviště</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      min-height: 100vh;
      padding: 20px;
    }
    
    .scan-container {
      max-width: 500px;
      margin: 0 auto;
      background: white;
      border-radius: 24px;
      padding: 30px 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    #qr-reader {
      border-radius: 12px;
      overflow: hidden;
      margin: 20px 0;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      color: white;
      padding: 14px 28px;
      border-radius: 12px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      width: 100%;
      font-size: 16px;
      transition: all 0.3s;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }
    
    .btn-secondary {
      background: #f3f4f6;
      color: #374151;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      width: 100%;
      font-size: 15px;
      transition: all 0.3s;
      margin-top: 12px;
    }
    
    .btn-secondary:hover {
      background: #e5e7eb;
    }
    
    .info-box {
      border-radius: 12px;
      padding: 16px;
      margin: 16px 0;
      font-size: 14px;
      background: rgb(249 250 251 / var(--tw-bg-opacity, 1))
    }
    
    .success-box {
      background: #d1fae5;
      border: 2px solid #6ee7b7;
      border-radius: 12px;
      padding: 16px;
      margin: 16px 0;
      font-size: 14px;
      color: #065f46;
    }
    
    .error-box {
      background: #fee2e2;
      border: 2px solid #fca5a5;
      border-radius: 12px;
      padding: 16px;
      margin: 16px 0;
      font-size: 14px;
      color: #991b1b;
    }
    
    .station-info {
      background: #f9fafb;
      border-radius: 12px;
      padding: 16px;
      margin: 16px 0;
    }
  </style>
</head>
<body>
  <div class="scan-container">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">📷 Skenovat QR kód stanoviště</h1>
      <p class="text-gray-600">Tým: <strong><?=htmlspecialchars($team['name'])?></strong></p>
    </div>

    <?php if ($station): ?>
    <div class="station-info">
      <h3 class="font-semibold text-gray-900 mb-2">📍 Stanoviště:</h3>
      <p class="text-lg font-bold text-red-600"><?=htmlspecialchars($station['name'])?></p>
      <p class="text-sm text-gray-600 mt-1"><?=htmlspecialchars($station['description'])?></p>
    </div>
    <?php endif; ?>

    <div class="info-box">
      💡 <strong>Jak to funguje:</strong><br>
      1. Najděte QR kód na fyzickém stanovišti<br>
      2. Naskenujte ho pomocí kamery níže<br>
      3. Po úspěšném skenu se vám zpřístupní virtuální úkol
    </div>

    <div id="qr-reader" style="display: none;"></div>
    
    <button id="startScanBtn" onclick="startScanning()" class="btn-primary">
      📷 Spustit skenování
    </button>
    
    <button id="stopScanBtn" onclick="stopScanning()" class="btn-secondary" style="display: none;">
      ⏹️ Zastavit skenování
    </button>

    <div id="resultMessage" style="display: none;"></div>

    <div class="text-center mt-6">
      <a href="./stations" class="text-gray-600 hover:text-gray-900 text-sm">← Zpět na stanoviště</a>
    </div>
  </div>

  <script>
  let html5QrCode = null;
  let isScanning = false;

  async function startScanning() {
    const qrReader = document.getElementById('qr-reader');
    const startBtn = document.getElementById('startScanBtn');
    const stopBtn = document.getElementById('stopScanBtn');
    const resultMessage = document.getElementById('resultMessage');
    
    qrReader.style.display = 'block';
    startBtn.style.display = 'none';
    stopBtn.style.display = 'block';
    resultMessage.style.display = 'none';
    
    html5QrCode = new Html5Qrcode("qr-reader");
    isScanning = true;
    
    try {
      await html5QrCode.start(
        { facingMode: "environment" },
        {
          fps: 10,
          qrbox: { width: 250, height: 250 }
        },
        onScanSuccess,
        onScanFailure
      );
    } catch (err) {
      console.error("Chyba při spuštění skeneru:", err);
      showError("Nepodařilo se spustit kameru. Zkontrolujte oprávnění.");
      stopScanning();
    }
  }

  async function stopScanning() {
    if (html5QrCode && isScanning) {
      try {
        await html5QrCode.stop();
        html5QrCode.clear();
      } catch (err) {
        console.error("Chyba při zastavení skeneru:", err);
      }
    }
    
    const qrReader = document.getElementById('qr-reader');
    const startBtn = document.getElementById('startScanBtn');
    const stopBtn = document.getElementById('stopScanBtn');
    
    qrReader.style.display = 'none';
    startBtn.style.display = 'block';
    stopBtn.style.display = 'none';
    isScanning = false;
  }

  async function onScanSuccess(decodedText, decodedResult) {
    console.log("QR kód naskenován:", decodedText);
    
    // Zastavit skenování
    await stopScanning();
    
    // Odeslat QR kód na server
    try {
      const response = await fetch('./api/team_scan_station_qr.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          qr_token: decodedText
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        showSuccess(data.message + '<br><br>Přesměrování na virtuální úkol...');
        
        // Přesměrovat na virtuální stanoviště
        setTimeout(() => {
          window.location.href = './virtual_station?id=' + data.station.id;
        }, 2000);
      } else {
        showError(data.error || 'Chyba při zpracování QR kódu');
      }
    } catch (error) {
      console.error("Chyba při odesílání:", error);
      showError('Chyba při komunikaci se serverem: ' + error.message);
    }
  }

  function onScanFailure(error) {
    // Ignorovat běžné chyby při skenování
    // console.warn("Scan error:", error);
  }

  function showSuccess(message) {
    const resultMessage = document.getElementById('resultMessage');
    resultMessage.className = 'success-box';
    resultMessage.innerHTML = '✅ ' + message;
    resultMessage.style.display = 'block';
  }

  function showError(message) {
    const resultMessage = document.getElementById('resultMessage');
    resultMessage.className = 'error-box';
    resultMessage.innerHTML = '❌ ' + message;
    resultMessage.style.display = 'block';
  }
  </script>
</body>
</html>