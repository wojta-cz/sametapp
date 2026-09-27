<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <title>Žebříček — Samet Festival</title>
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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
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
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .team-item {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 12px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid rgba(102, 126, 234, 0.1);
    }
    
    .team-item:active {
      transform: scale(0.98);
    }
    
    .rank-badge {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 20px;
      flex-shrink: 0;
    }
    
    .rank-1 {
      background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
      color: white;
      box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
    }
    
    .rank-2 {
      background: linear-gradient(135deg, #C0C0C0 0%, #A8A8A8 100%);
      color: white;
      box-shadow: 0 6px 20px rgba(192, 192, 192, 0.4);
    }
    
    .rank-3 {
      background: linear-gradient(135deg, #CD7F32 0%, #B87333 100%);
      color: white;
      box-shadow: 0 6px 20px rgba(205, 127, 50, 0.4);
    }
    
    .rank-other {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
      color: rgba(224, 127, 127, 1);
      border: 2px solid rgba(224, 127, 127, 0.2);
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
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        </a> 
      </div>
      
    </div>
  </div>

  <div class="px-5">
    <div class="card p-7 fade-in">
      <div id="loading" class="text-center py-10">
        <div class="animate-spin inline-block w-10 h-10 border-4 border-red-500 border-t-transparent rounded-full"></div>
        <p class="mt-3 text-gray-600 font-medium text-lg">Načítám žebříček...</p>
      </div>
      <div id="list" class="hidden"></div>
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
      
      <a href="./qr" class="nav-item">
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
      
      <a href="./leaderboard" class="nav-item active">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
        </svg>
        <span class="nav-label">Žebříček</span>
      </a>
    </div>
  </nav>

<script>
async function load() {
  try {
    const r = await fetch('./api/leaderboard.php');
    const j = await r.json();
    const el = document.getElementById('list');
    const loading = document.getElementById('loading');
    
    if (j.teams && j.teams.length > 0) {
      el.innerHTML = j.teams.map((t, i) => {
        const rank = i + 1;
        let rankClass = 'rank-other';
        let medal = '';
        
        if (rank === 1) {
          rankClass = 'rank-1';
          medal = '🥇';
        } else if (rank === 2) {
          rankClass = 'rank-2';
          medal = '🥈';
        } else if (rank === 3) {
          rankClass = 'rank-3';
          medal = '🥉';
        }
        
        return `
          <div class="team-item">
            <div class="flex items-center gap-5">
              <div class="rank-badge ${rankClass}">
                ${medal || rank}
              </div>
              <div class="flex-1">
                <div class="font-bold text-gray-800 text-xl">${escapeHtml(t.name)}</div>
                <div class="text-base text-gray-500 font-medium">Tým</div>
              </div>
              <div class="text-right">
                <div class="font-bold text-3xl text-red-600">
                  ${t.points}
                </div>
                <div class="text-sm text-gray-500 font-medium">bodů</div>
              </div>
            </div>
          </div>
        `;
      }).join('');
      
      loading.classList.add('hidden');
      el.classList.remove('hidden');
    } else {
      loading.innerHTML = `
        <svg class="w-24 h-24 mx-auto mb-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
        <p class="text-gray-600 text-xl">Zatím nejsou žádné týmy v žebříčku</p>
      `;
    }
  } catch (error) {
    document.getElementById('loading').innerHTML = `
      <svg class="w-24 h-24 mx-auto mb-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="text-red-600 text-xl font-bold">Chyba při načítání žebříčku</p>
    `;
  }
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

load();
setInterval(load, 10000);
</script>
</body>
</html>