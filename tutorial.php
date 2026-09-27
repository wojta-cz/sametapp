<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = get_user_by_googleid($_SESSION['user']['google_id']);
$pdo = db();

// Check if user has seen tutorial
$stmt = $pdo->prepare("SELECT tutorial_seen FROM users WHERE google_id = ?");
$stmt->execute([$_SESSION['user']['google_id']]);
$tutorial_seen = $stmt->fetchColumn();

// If already seen, redirect to index
if ($tutorial_seen) {
    header('Location: index.php');
    exit;
}

// Mark tutorial as seen if user clicks continue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_seen'])) {
    $stmt = $pdo->prepare("UPDATE users SET tutorial_seen = 1 WHERE google_id = ?");
    $stmt->execute([$_SESSION['user']['google_id']]);
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <title>Tutoriál — Samet Festival</title>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    * {
      -webkit-tap-highlight-color: transparent;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(135deg, #ea6666ff 0%, #b52525ff 100%);
      min-height: 100vh;
      padding: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .tutorial-container {
      max-width: 600px;
      width: 100%;
      background: white;
      border-radius: 24px;
      padding: 32px 24px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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
    
    .tutorial-header {
      text-align: center;
      margin-bottom: 32px;
    }
    
    .tutorial-icon {
      width: 80px;
      height: 80px;
      margin: 0 auto 16px;
      background: linear-gradient(135deg, #ea6666ff 0%, #b52525ff 100%);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .tutorial-icon svg {
      width: 48px;
      height: 48px;
      color: white;
    }
    
    h1 {
      font-size: 28px;
      font-weight: 800;
      color: #1e293b;
      margin-bottom: 8px;
    }
    
    .subtitle {
      color: #64748b;
      font-size: 16px;
    }
    
    .step-card {
      background: #f8f9fa;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 16px;
      border: 2px solid #e9ecef;
      transition: all 0.3s;
    }
    
    .step-card:hover {
      border-color: #667eea;
      transform: translateX(4px);
    }
    
    .step-number {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, #ea6666ff 0%, #b52525ff 100%);
      color: white;
      border-radius: 10px;
      font-weight: 800;
      font-size: 18px;
      margin-bottom: 12px;
    }
    
    .step-title {
      font-size: 18px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 8px;
    }
    
    .step-description {
      font-size: 15px;
      color: #64748b;
      line-height: 1.6;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #ea6666ff 0%, #b52525ff 100%);
      color: white;
      padding: 16px 32px;
      border-radius: 14px;
      font-weight: 600;
      font-size: 17px;
      border: none;
      cursor: pointer;
      width: 100%;
      transition: all 0.3s;
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.35);
      margin-top: 24px;
    }
    
    .btn-primary:active {
      transform: scale(0.98);
    }
    
    .features {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 24px;
      padding-top: 24px;
      border-top: 2px solid #e9ecef;
    }
    
    .feature-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #eff6ff;
      color: #1e40af;
      padding: 8px 14px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      border: 1px solid #bfdbfe;
    }
    
    .feature-badge svg {
      width: 16px;
      height: 16px;
    }
    
    @media (max-width: 480px) {
      .tutorial-container {
        padding: 24px 20px;
      }
      
      h1 {
        font-size: 24px;
      }
      
      .tutorial-icon {
        width: 70px;
        height: 70px;
      }
      
      .tutorial-icon svg {
        width: 40px;
        height: 40px;
      }
    }
  </style>
</head>
<body>
  <div class="tutorial-container">
    <div class="tutorial-header">
      <div class="tutorial-icon">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
        </svg>
      </div>
      <h1>🎯 Jak aplikace funguje</h1>
      <p class="subtitle">Naučte se základy během pár sekund</p>
    </div>
    
    <div class="step-card">
      <div class="step-number">1</div>
      <div class="step-title">Vytvořte nebo připojte se k týmu</div>
      <div class="step-description">
        Po přihlášení si vytvořte vlastní tým nebo se připojte k existujícímu pomocí kódu týmu.
      </div>
    </div>
    
    <div class="step-card">
      <div class="step-number">2</div>
      <div class="step-title">Procházejte stanoviště</div>
      <div class="step-description">
        Systém vám přiřadí aktuální stanoviště podle kapacity.
      </div>
    </div>
    
    <div class="step-card">
      <div class="step-number">3</div>
      <div class="step-title">Skenujte QR kódy</div>
      <div class="step-description">
        U fyzických stanovišť organizátor naskenuje váš qr kód. U virtuálních skenujete qr kódy vy.
      </div>
    </div>
    
    <div class="step-card">
      <div class="step-number">4</div>
      <div class="step-title">Sbírejte body a soutěžte</div>
      <div class="step-description">
        Za každé splněné stanoviště získáte body. Sledujte žebříček a bojujte o první místo!
      </div>
    </div>
    
    <div class="features">
      <div class="feature-badge">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        Fyzická stanoviště
      </div>
      <div class="feature-badge">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>
        </svg>
        Virtuální úkoly
      </div>
      <div class="feature-badge">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
        </svg>
        Týmová hra
      </div>
      <div class="feature-badge">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
        </svg>
        Žebříček
      </div>
    </div>
    
    <form method="POST">
      <input type="hidden" name="mark_seen" value="1">
      <button type="submit" class="btn-primary">
        🚀 Začít používat aplikaci
      </button>
    </form>
  </div>
</body>
</html>