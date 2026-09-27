<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = $_SESSION['user'];

// Check if user has a team
if (empty($user['team_id'])) {
    ?>
    <!doctype html>
    <html lang="cs">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
      <title>QR kód týmu</title>
      <script src="https://cdn.tailwindcss.com"></script>
      <style>
        * {
          -webkit-tap-highlight-color: transparent;
        }
        
        body {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
          background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
          min-height: 100vh;
          padding-bottom: 90px;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
        }
        
        .no-team-container {
          background: white;
          border-radius: 32px;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
          padding: 48px 40px;
          text-align: center;
          max-width: 90%;
          margin: 20px;
          border: 1px solid rgba(255, 255, 255, 0.2);
          animation: slideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideIn {
          from {
            opacity: 0;
            transform: translateY(30px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        
        .icon-container {
          width: 100px;
          height: 100px;
          margin: 0 auto 24px;
          background: linear-gradient(135deg, #ea6666 0%, #992323 100%);
          border-radius: 24px;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        
        .icon-container svg {
          width: 60px;
          height: 60px;
          color: white;
        }
        
        h1 {
          font-size: 28px;
          font-weight: 800;
          color: #1e293b;
          margin-bottom: 12px;
        }
        
        .subtitle {
          color: #64748b;
          font-size: 16px;
          margin-bottom: 32px;
          line-height: 1.6;
        }
        
        .btn-primary {
          background: linear-gradient(135deg, #ea6666 0%, #992323 100%);
          color: white;
          padding: 16px 32px;
          border-radius: 16px;
          font-weight: 600;
          font-size: 17px;
          border: none;
          cursor: pointer;
          text-decoration: none;
          display: inline-flex;
          align-items: center;
          gap: 10px;
          transition: all 0.3s;
          box-shadow: 0 8px 24px rgba(234, 102, 102, 0.35);
        }
        
        .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 12px 32px rgba(234, 102, 102, 0.45);
        }
        
        .btn-primary:active {
          transform: translateY(0);
        }
        
        .info-box {
          background: #fef3c7;
          border: 2px solid #fbbf24;
          border-radius: 16px;
          padding: 16px;
          margin-top: 24px;
          color: #92400e;
        }
        
        .info-box p {
          font-size: 14px;
          line-height: 1.6;
          margin: 0;
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
        
        @media (max-width: 480px) {
          .no-team-container {
            padding: 36px 28px;
          }
          
          h1 {
            font-size: 24px;
          }
          
          .icon-container {
            width: 80px;
            height: 80px;
          }
          
          .icon-container svg {
            width: 48px;
            height: 48px;
          }
        }
      </style>
    </head>
    <body>
      <div class="no-team-container">
        <div class="icon-container">
          <svg fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
          </svg>
        </div>
        
        <h1>Nejste v žádném týmu</h1>
        <p class="subtitle">
          Pro zobrazení QR kódu musíte být členem týmu.<br>
          Vytvořte si vlastní tým nebo se připojte k existujícímu.
        </p>
        
        <a href="./team" class="btn-primary">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
          </svg>
          <span>Přejít na správu týmu</span>
        </a>
        
        <div class="info-box">
          <p>
            <strong>💡 Tip:</strong> Můžete vytvořit vlastní tým nebo požádat kapitána týmu o kód pro připojení.
          </p>
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
          
          <a href="./qr" class="nav-item active">
            <svg fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd"/>
              <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z"/>
            </svg>
            <span class="nav-label">QR kód</span>
          </a>
          
          <a href="./stations" class="nav-item">
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
    <?php
    exit;
}

$token = qr_token_for_team($user['team_id']);
require_once __DIR__ . '/includes/libs/phpqrcode.php';
ob_start();
QRcode::png($token, null, QR_ECLEVEL_M, 6);
$image = ob_get_clean();
$base64 = base64_encode($image);
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <title>QR kód týmu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    * {
      -webkit-tap-highlight-color: transparent;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      min-height: 100vh;
      padding-bottom: 90px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    
    .qr-container {
      background: white;
      border-radius: 32px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      padding: 48px;
      text-align: center;
      max-width: 90%;
      margin: 20px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .qr-code {
      background: white;
      padding: 24px;
      border-radius: 24px;
      border: 3px solid #e5e7eb;
      display: inline-block;
      margin: 24px 0;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
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
  </style>
</head>
<body>
  <div class="qr-container">
    <svg class="w-20 h-20 mx-auto mb-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd"/>
      <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z"/>
    </svg>
    <h1 class="text-4xl font-bold mb-3 text-gray-800">QR kód týmu</h1>
    <p class="text-gray-600 mb-5 text-lg">Předložte organizátorovi pro ověření</p>
    
    <div class="qr-code">
      <img src="data:image/png;base64,<?=$base64?>" alt="QR" class="mx-auto" style="max-width: 300px; width: 100%;">
    </div>
    
    <div class="mt-8 p-5 bg-gradient-to-r from-red-50 to-red-50 rounded-2xl border border-red-200">
      <div class="flex items-center justify-center gap-3 text-red-700">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <p class="text-base font-bold">Zvyšte jas displeje pro lepší čitelnost</p>
      </div>
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
      
      <a href="./qr" class="nav-item active">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd"/>
          <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z"/>
        </svg>
        <span class="nav-label">QR kód</span>
      </a>
      
      <a href="./stations" class="nav-item">
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