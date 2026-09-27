<?php
require_once __DIR__ . '/includes/auth.php';
$authUrl = google_auth_url();
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <title>Přihlášení — Samet Festival</title>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    * {
      -webkit-tap-highlight-color: transparent;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(135deg, #ea6666 0%, #992323 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }
    
    /* Animated background particles */
    .bg-particle {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      animation: float 20s infinite;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0) translateX(0); }
      25% { transform: translateY(-100px) translateX(50px); }
      50% { transform: translateY(-50px) translateX(-50px); }
      75% { transform: translateY(-150px) translateX(100px); }
    }
    
    .container {
      max-width: 500px;
      width: 100%;
      position: relative;
      z-index: 1;
    }
    
    .login-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      border-radius: 32px;
      padding: 48px 40px;
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
    
    .logo {
      width: 120px;
      height: 120px;
      margin: 0 auto 24px;
      background: linear-gradient(135deg, #ea6666 0%, #992323 100%);
      border-radius: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 12px 32px rgba(234, 102, 102, 0.4);
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    .logo svg {
      width: 70px;
      height: 70px;
      color: white;
    }
    
    h1 {
      font-size: 32px;
      font-weight: 800;
      text-align: center;
      color: #1e293b;
      margin-bottom: 12px;
      line-height: 1.2;
    }
    
    .subtitle {
      text-align: center;
      color: #64748b;
      font-size: 16px;
      margin-bottom: 32px;
      line-height: 1.5;
    }
    
    .btn-google {
      background: linear-gradient(135deg, #ea6666 0%, #992323 100%);
      color: white;
      padding: 18px 32px;
      border-radius: 16px;
      font-weight: 600;
      font-size: 17px;
      border: none;
      cursor: pointer;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 8px 24px rgba(234, 102, 102, 0.35);
    }
    
    .btn-google:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 32px rgba(234, 102, 102, 0.45);
    }
    
    .btn-google:active {
      transform: translateY(-1px);
    }
    
    .btn-google svg {
      width: 24px;
      height: 24px;
    }
    
    .info-box {
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      border-radius: 16px;
      padding: 16px;
      margin-top: 24px;
      text-align: center;
    }
    
    .info-box p {
      color: #64748b;
      font-size: 14px;
      line-height: 1.6;
    }
    
    @media (max-width: 480px) {
      .login-card {
        padding: 36px 28px;
      }
      
      h1 {
        font-size: 28px;
      }
      
      .logo {
        width: 100px;
        height: 100px;
      }
      
      .logo svg {
        width: 60px;
        height: 60px;
      }
    }
  </style>
</head>
<body>
  <!-- Background particles -->
  <div class="bg-particle" style="width: 300px; height: 300px; top: -100px; left: -100px; animation-delay: 0s;"></div>
  <div class="bg-particle" style="width: 200px; height: 200px; top: 50%; right: -50px; animation-delay: 5s;"></div>
  <div class="bg-particle" style="width: 150px; height: 150px; bottom: -50px; left: 30%; animation-delay: 10s;"></div>
  <div class="bg-particle" style="width: 250px; height: 250px; top: 20%; right: 20%; animation-delay: 7s;"></div>

  <div class="container">
    <div class="login-card">
      <div class="logo">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
      </div>
      
      <h1>Samet Festival</h1>
      <p class="subtitle">Vítejte v interaktivní soutěži!<br>Přihlaste se školním Google účtem.</p>
      
      <a href="<?=htmlspecialchars($authUrl)?>" class="btn-google">
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        <span>Přihlásit se přes Google</span>
      </a>
      
      <div class="info-box">
        <p>
          <strong>🔒 Pouze pro studenty GJKT</strong><br>
          Přihlaste se školním účtem končícím na <strong>@gjkt.eu</strong>
        </p>
      </div>
    </div>
  </div>
</body>
</html>