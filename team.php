<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = get_user_by_googleid($_SESSION['user']['google_id']);
$pdo = db();

$team = null;
$members = [];
if ($user['team_id']) {
  $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
  $stmt->execute([$user['team_id']]);
  $team = $stmt->fetch();

  $stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE team_id = ?");
  $stmt->execute([$user['team_id']]);
  $members = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="cs">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <title>Můj tým — Samet Festival</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    * {
      -webkit-tap-highlight-color: transparent;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
      min-height: 100vh;
      padding-bottom: 90px;
    }

    .card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
      border: 1px solid rgba(0, 0, 0, 0.04);
    }

    .gradient-primary {
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
    }

    .btn-primary {
      background: linear-gradient(135deg, #ea6666ff 0%, #992323ff 100%);
      color: white;
      padding: 16px 28px;
      border-radius: 16px;
      font-weight: 600;
      font-size: 16px;
      border: none;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    }

    .btn-primary:active {
      transform: translateY(0);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
      background: white;
      color: #e88181ff;
      border: 2px solid #e88181ff;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }

    .btn-secondary:hover {
      background: rgba(102, 126, 234, 0.05);
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

    .fade-in {
      animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(15px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .member-card {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
      border-radius: 16px;
      padding: 16px;
      border: 1px solid rgba(102, 126, 234, 0.15);
    }

    input {
      border: 2px solid #e5e7eb;
      border-radius: 16px;
      padding: 16px 20px;
      font-size: 16px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      width: 100%;
    }

    input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .header-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 0 0 32px 32px;
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
    }
  </style>
</head>

<body>
  <div class="px-5 pb-5 pt-5 pb-2 bg-gradient-to-r from-gray-50 to-slate-100">
    <div class="flex justify-between items-center">
      <div>
        <img class="h-12" src="./includes/images/logo.png">
      </div>
      <div>
        <a href="./logout" class=" hover:text-white transition-colors rounded-xl hover:bg-white/10">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
        </a>
      </div>

    </div>
  </div>

  <div class="px-5 mt-3">
    <?php if ($team): ?>
      <!-- Team Info -->
      <div class="card p-7 mb-5 fade-in">
        <div class="flex justify-between items-start mb-5">
          <div class="flex-1">
            <div class="text-sm text-gray-500 font-medium mb-1">Název týmu</div>
            <h2 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($team['name']) ?></h2>
          </div>
          <div class="text-right">
            <div class="text-sm text-gray-500 font-medium">Body</div>
            <div class="text-4xl font-bold text-red-600"><?= $team['points'] ?></div>
          </div>
        </div>

        <div class="bg-gradient-to-r from-red-50 to-pink-50 rounded-2xl p-5 mb-5 border border-red-200">
          <div class="text-sm text-gray-600 font-medium mb-2">Kód týmu</div>
          <div class="text-5xl font-mono font-bold"><?= htmlspecialchars($team['code']) ?></div>
          <p class="text-sm text-gray-500 mt-3">Sdílejte tento kód s ostatními členy týmu</p>
        </div>

        <div class="border-t pt-5">
          <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-lg">
            <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path
                d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
            </svg>
            <span>Členové týmu (<?= count($members) ?>/5)</span>
          </h3>
          <div class="space-y-3">
            <?php foreach ($members as $m): ?>
              <div class="member-card flex items-center justify-between">
                <div>
                  <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($m['name']) ?></p>
                  <p class="text-base text-gray-500 text-sm"><?= htmlspecialchars($m['email']) ?></p>
                </div>
                <?php if ($m['role'] !== 'player'): ?>
                  <span class="px-4 py-2 text-sm bg-red-100 text-red-800 rounded-full font-bold border border-red-200">
                    <?= $m['role'] === 'organizer' ? 'Pořadatel' : 'Admin' ?>
                  </span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-2 gap-4 mb-5">
        <a href="./qr" class="card p-6 text-center fade-in" style="text-decoration: none;">
          <svg class="w-14 h-14 mx-auto mb-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z"
              clip-rule="evenodd" />
            <path
              d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
          </svg>
          <div class="font-bold text-gray-800">QR kód</div>
        </a>

        <a href="./stations" class="card p-6 text-center fade-in" style="text-decoration: none;">
          <svg class="w-14 h-14 mx-auto mb-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
              clip-rule="evenodd" />
          </svg>
          <div class="font-bold text-gray-800">Stanoviště</div>
        </a>
      </div>

    <?php else: ?>
      <!-- No Team - Create or Join -->
      <div class="card p-7 mb-5 fade-in">
        <div class="text-center mb-8">
          <svg class="w-24 h-24 mx-auto mb-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
          </svg>
          <h2 class="text-3xl font-bold text-gray-800 mb-3">Zatím nejste v týmu</h2>
          <p class="text-gray-600 text-lg">Vytvořte nový tým nebo se připojte k existujícímu</p>
        </div>

        <div class="card p-5 mb-5 fade-in">
          <div class="flex items-center gap-4">
            <svg class="w-12 h-12 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
            <div class="flex-1">
              <p class="font-bold text-gray-800">Jakmile vytvoříte tým, nebo se k němu připojíte, nemůžete ho opustit!</p>
            </div>
          </div>
        </div>

        <div class="space-y-5">
          <div class="border-2 border-red-200 rounded-2xl p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
              <svg class="w-7 h-7 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path
                  d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
              </svg>
              <span>Připojit se k týmu</span>
            </h3>
            <form id="joinForm" class="space-y-4">
              <div>
                <label class="block text-base font-bold text-gray-700 mb-3">Kód týmu</label>
                <input class="p-3 text-md" type="text" id="teamCode" required placeholder="ABC123" maxlength="6"
                  style="text-transform: uppercase;">
              </div>
              <button type="submit" class="btn-primary btn-secondary">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                  <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                </svg>
                <span>Připojit se</span>
              </button>
            </form>
            <div id="joinResult" class="mt-4 hidden"></div>
          </div>

          <!-- Create Team -->
          <div class="border-2 border-red-200 rounded-2xl p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
              <svg class="w-7 h-7 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                  clip-rule="evenodd" />
              </svg>
              <span>Vytvořit nový tým</span>
            </h3>
            <form id="createForm" class="space-y-4">
              <div>
                <label class="block text-base font-bold text-gray-700 mb-3">Název týmu</label>
                <input class="p-3 text-md" type="text" id="teamName" required placeholder="např. Borci 2024">
              </div>
              <button type="submit" class="btn-primary">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd" />
                </svg>
                <span>Vytvořit tým</span>
              </button>
            </form>
            <div id="createResult" class="mt-4 hidden"></div>
          </div>

          <!-- Join Team -->
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Bottom Navigation -->
  <nav class="nav-bar">
    <div class="flex justify-around items-center">
      <a href="./index" class="nav-item">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path
            d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
        </svg>
        <span class="nav-label">Domů</span>
      </a>

      <a href="./team" class="nav-item active">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path
            d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
        </svg>
        <span class="nav-label">Tým</span>
      </a>

      <a href="./qr" class="nav-item">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z"
            clip-rule="evenodd" />
          <path
            d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
        </svg>
        <span class="nav-label">QR kód</span>
      </a>

      <a href="./stations" class="nav-item">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
            clip-rule="evenodd" />
        </svg>
        <span class="nav-label">Stanoviště</span>
      </a>

      <a href="./leaderboard" class="nav-item">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path
            d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
        </svg>
        <span class="nav-label">Žebříček</span>
      </a>
    </div>
  </nav>

  <script>
    function copyCode() {
      const code = document.getElementById('teamCodeInput');
      code.select();
      code.setSelectionRange(0, 99999);
      document.execCommand('copy');

      const btn = event.target.closest('button');
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>Zkopírováno</span>';
      setTimeout(() => {
        btn.innerHTML = originalHTML;
      }, 2000);
    }

    document.getElementById('createForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const name = document.getElementById('teamName').value;
      const result = document.getElementById('createResult');
      const btn = e.target.querySelector('button');

      btn.disabled = true;
      btn.innerHTML = '<svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Vytvářím...</span>';

      try {
        const response = await fetch('./api/team_create.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name })
        });
        const data = await response.json();

        if (data.ok) {
          result.className = 'mt-4 p-4 bg-green-100 text-green-800 rounded-2xl font-bold';
          result.textContent = `✅ Tým vytvořen! Kód: ${data.code}`;
          location.reload();
        } else if (!data.ok && data.error) {
          result.className = 'mt-4 p-4 bg-red-100 text-red-800 rounded-2xl font-bold';
          result.textContent = '❌ ' + data.error;
          btn.disabled = false;
          btn.innerHTML = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg><span>Vytvořit tým</span>';
        } else {
          result.className = 'mt-4 p-4 bg-red-100 text-red-800 rounded-2xl font-bold';
          result.textContent = '❌ Chyba při vytváření týmu';
          btn.disabled = false;
          btn.innerHTML = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg><span>Vytvořit tým</span>';
        }
        result.classList.remove('hidden');
      } catch (error) {
        result.className = 'mt-4 p-4 bg-red-100 text-red-800 rounded-2xl font-bold';
        result.textContent = '❌ Chyba připojení';
        result.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg><span>Vytvořit tým</span>';
      }
    });

    document.getElementById('joinForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const code = document.getElementById('teamCode').value.toUpperCase();
      const result = document.getElementById('joinResult');
      const btn = e.target.querySelector('button');

      btn.disabled = true;
      btn.innerHTML = '<svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Připojuji...</span>';

      try {
        const response = await fetch('./api/team_join.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code })
        });
        const data = await response.json();

        if (data.ok) {
          result.className = 'mt-4 p-4 bg-green-100 text-green-800 rounded-2xl font-bold';
          result.textContent = `✅ Připojen k týmu: ${data.team.name}`;
          location.reload();
        } else if (data.full) {
          result.className = 'mt-4 p-4 bg-red-100 text-red-800 rounded-2xl font-bold';
          result.textContent = '❌ Tento tým je již plný';
          btn.disabled = false;
          btn.innerHTML = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/></svg><span>Připojit se</span>';
        } else {
          result.className = 'mt-4 p-4 bg-red-100 text-red-800 rounded-2xl font-bold';
          result.textContent = '❌ Tým s tímto kódem nebyl nalezen';
          btn.disabled = false;
          btn.innerHTML = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/></svg><span>Připojit se</span>';
        }
        result.classList.remove('hidden');
      } catch (error) {
        result.className = 'mt-4 p-4 bg-red-100 text-red-800 rounded-2xl font-bold';
        result.textContent = '❌ Chyba připojení';
        result.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/></svg><span>Připojit se</span>';
      }
    });
  </script>
</body>

</html>