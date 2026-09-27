<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/virtual_station_functions.php';
require_login();
$user = $_SESSION['user'];
$pdo = db();

$station_id = intval($_GET['id'] ?? 0);
if (!$station_id) {
    header('Location: stations.php');
    exit;
}

$team_id = $user['team_id'] ?? null;
if (!$team_id) {
    header('Location: stations.php?error=no_team');
    exit;
}

$station = get_virtual_station_data($station_id, $team_id);

if (!$station || $station['type'] !== 'virtual') {
    header('Location: stations.php');
    exit;
}

// Zkontrolovat, zda už nesplnili
$stmt = $pdo->prepare("SELECT * FROM results WHERE team_id = ? AND station_id = ? AND status = 'done'");
$stmt->execute([$team_id, $station_id]);
$completed = $stmt->fetch();

// Získat tým pro zobrazení bodů
$stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch();

$colors = $station['custom_colors'];
$primary_color = $colors['primary'] ?? '#667eea';
$secondary_color = $colors['secondary'] ?? '#764ba2';
$text_color = $colors['text'] ?? '#ffffff';
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <title><?=htmlspecialchars($station['name'])?> — Virtuální stanoviště</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --primary-color: <?=$primary_color?>;
      --secondary-color: <?=$secondary_color?>;
      --text-color: <?=$text_color?>;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      <?php if ($station['custom_background']): ?>
      background-image: url('<?=htmlspecialchars($station['custom_background'])?>');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      <?php else: ?>
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      <?php endif; ?>
      min-height: 100vh;
      padding: 20px;
    }
    
    .station-container {
      max-width: 800px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      padding: 32px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .station-header {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: var(--text-color);
      padding: 24px;
      border-radius: 16px;
      margin-bottom: 24px;
      text-align: center;
    }
    
    .media-container {
      margin: 24px 0;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }
    
    .media-container img,
    .media-container video {
      width: 100%;
      display: block;
    }
    
    .hint-card {
      background: #f8f9fa;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 12px;
      transition: all 0.3s;
    }
    
    .hint-card:hover {
      border-color: var(--primary-color);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .hint-revealed {
      background: #d4edda;
      border-color: #28a745;
    }
    
    .quiz-option {
      background: white;
      border: 2px solid #dee2e6;
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 12px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 16px;
    }
    
    .quiz-option:hover {
      border-color: var(--primary-color);
      background: #f8f9ff;
    }
    
    .quiz-option.selected {
      border-color: var(--primary-color);
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      font-weight: 600;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      padding: 16px 32px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 16px;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
      width: 100%;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }
    
    .btn-primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }
    
    .score-display {
      background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
      color: #333;
      padding: 16px;
      border-radius: 12px;
      text-align: center;
      font-weight: bold;
      font-size: 18px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
    }
    
    .timer {
      background: rgba(0, 0, 0, 0.1);
      padding: 8px 16px;
      border-radius: 8px;
      display: inline-block;
      font-weight: 600;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
      animation: fadeIn 0.5s ease-out;
    }
  </style>
</head>
<body>
  <?php if ($station['audio_atmosphere']): ?>
  <audio id="atmosphereAudio" loop autoplay>
    <source src="<?=htmlspecialchars($station['audio_atmosphere'])?>" type="audio/mpeg">
  </audio>
  <?php endif; ?>

  <div class="station-container fade-in">
    <div class="station-header">
      <?php if ($station['station_logo']): ?>
      <img src="<?=htmlspecialchars($station['station_logo'])?>" alt="Logo" style="max-width: 120px; margin: 0 auto 16px;">
      <?php endif; ?>
      <h1 class="text-3xl font-bold mb-2"><?=htmlspecialchars($station['name'])?></h1>
      <p class="text-lg opacity-90"><?=htmlspecialchars($station['description'])?></p>
      <div class="timer mt-4">
        <span id="timer">00:00</span>
      </div>
    </div>

    <?php if ($team && $station['show_score']): ?>
    <div class="score-display">
      🏆 Aktuální skóre týmu: <?=$team['points']?> bodů
    </div>
    <?php endif; ?>

    <?php if ($completed): ?>
      <!-- Již splněno -->
      <div class="p-8 bg-green-100 border-2 border-green-500 rounded-2xl text-center">
        <svg class="w-20 h-20 mx-auto mb-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <h2 class="text-2xl font-bold text-green-800 mb-2">Stanoviště splněno!</h2>
        <p class="text-lg text-green-700">Získali jste <?=$completed['points']?> bodů.</p>
        <a href="./stations.php" class="btn-primary mt-6">Zpět na stanoviště</a>
      </div>
    <?php else: ?>
      <!-- Média -->
      <?php if ($station['media_type'] !== 'none' && $station['media_url']): ?>
      <div class="media-container">
        <?php if ($station['media_type'] === 'image'): ?>
          <img src="<?=htmlspecialchars($station['media_url'])?>" alt="Obrázek úkolu">
        <?php elseif ($station['media_type'] === 'video'): ?>
          <video controls>
            <source src="<?=htmlspecialchars($station['media_url'])?>" type="video/mp4">
          </video>
        <?php elseif ($station['media_type'] === 'audio'): ?>
          <audio controls style="width: 100%;">
            <source src="<?=htmlspecialchars($station['media_url'])?>" type="audio/mpeg">
          </audio>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Otázka -->
      <div class="mb-6">
        <h3 class="text-xl font-bold mb-4">
          <?php if ($station['random_questions'] && isset($station['selected_question'])): ?>
            <?=htmlspecialchars($station['selected_question'])?>
          <?php else: ?>
            <?=htmlspecialchars($station['question'] ?? 'Vyřešte úkol')?>
          <?php endif; ?>
        </h3>
      </div>

      <!-- Nápovědy -->
      <?php if (!empty($station['hints'])): ?>
      <div class="mb-6">
        <h4 class="text-lg font-bold mb-3">💡 Nápovědy</h4>
        <div id="hintsContainer">
          <?php foreach ($station['hints'] as $index => $hint): ?>
          <div class="hint-card <?=in_array($index, $station['used_hints'])?'hint-revealed':''?>" data-hint-index="<?=$index?>">
            <?php if (in_array($index, $station['used_hints'])): ?>
              <p class="text-gray-800"><?=htmlspecialchars($hint['text'])?></p>
            <?php else: ?>
              <div class="flex justify-between items-center">
                <span class="font-medium">Nápověda #<?=$index + 1?></span>
                <button onclick="revealHint(<?=$index?>, <?=$hint['points_cost']?>)" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                  Odhalit (-<?=$hint['points_cost']?> bodů)
                </button>
              </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Formulář odpovědi -->
      <form id="answerForm" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="station_id" value="<?=$station_id?>">
        <input type="hidden" name="start_time" id="startTime" value="<?=time()?>">

        <?php if ($station['task_type'] === 'quiz' && !empty($station['quiz_options'])): ?>
          <!-- Kvíz -->
          <div id="quizOptions">
            <?php foreach ($station['quiz_options'] as $index => $option): ?>
            <div class="quiz-option" onclick="selectQuizOption(this, '<?=htmlspecialchars($option, ENT_QUOTES)?>')">
              <?=htmlspecialchars($option)?>
            </div>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="answer" id="quizAnswer">
        <?php else: ?>
          <!-- Textová odpověď -->
          <div>
            <label class="block text-lg font-bold mb-2">Vaše odpověď:</label>
            <textarea name="answer" id="textAnswer" rows="5" required 
                      class="w-full border-2 border-gray-300 rounded-lg p-4 focus:border-blue-500 focus:outline-none"
                      placeholder="Napište zde svou odpověď..."></textarea>
          </div>
        <?php endif; ?>

        <?php if ($station['allow_file_upload']): ?>
        <div>
          <label class="block text-lg font-bold mb-2">Nahrát soubor (volitelné):</label>
          <input type="file" name="file" accept="image/*,video/*,.pdf" 
                 class="w-full border-2 border-gray-300 rounded-lg p-3">
          <p class="text-sm text-gray-600 mt-1">Podporované formáty: obrázky, videa, PDF (max 10MB)</p>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn-primary" id="submitBtn">
          🚀 Odeslat odpověď
        </button>
      </form>

      <div id="resultMessage" class="mt-6 hidden"></div>

      <div class="mt-6 text-center">
        <a href="./stations.php" class="text-gray-600 hover:text-gray-800 underline">← Zpět na stanoviště</a>
      </div>
    <?php endif; ?>
  </div>

  <script>
  // Timer
  let startTime = <?=time()?>;
  setInterval(() => {
    const elapsed = Math.floor(Date.now() / 1000) - startTime;
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;
    document.getElementById('timer').textContent = 
      String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
  }, 1000);

  // Kvíz výběr
  function selectQuizOption(element, answer) {
    document.querySelectorAll('.quiz-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('quizAnswer').value = answer;
  }

  // Odhalení nápovědy
  async function revealHint(hintIndex, cost) {
    if (!confirm(`Opravdu chcete odhalit tuto nápovědu? Bude vám odečteno ${cost} bodů.`)) {
      return;
    }

    try {
      const response = await fetch('./api/virtual_hints.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          station_id: <?=$station_id?>,
          hint_index: hintIndex
        })
      });

      const data = await response.json();

      if (data.success) {
        const hintCard = document.querySelector(`[data-hint-index="${hintIndex}"]`);
        hintCard.classList.add('hint-revealed');
        hintCard.innerHTML = `<p class="text-gray-800">${data.hint_text}</p>`;
        alert(data.message);
      } else {
        alert('Chyba: ' + data.error);
      }
    } catch (error) {
      alert('Chyba při načítání nápovědy: ' + error.message);
    }
  }

  // Odeslání formuláře
  document.getElementById('answerForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    const submitBtn = document.getElementById('submitBtn');
    const resultDiv = document.getElementById('resultMessage');

    // Validace
    const answer = formData.get('answer');
    if (!answer || answer.trim() === '') {
      alert('Prosím vyplňte odpověď');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Odesílám...';

    try {
      const response = await fetch('./api/virtual_submit.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        resultDiv.classList.remove('hidden');
        
        if (data.status === 'done') {
          resultDiv.className = 'mt-6 p-6 bg-green-100 border-2 border-green-500 rounded-2xl text-center';
          resultDiv.innerHTML = `
            <svg class="w-16 h-16 mx-auto mb-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <h3 class="text-2xl font-bold text-green-800 mb-2">Správně!</h3>
            <p class="text-lg text-green-700">${data.message}</p>
          `;
        } else if (data.status === 'failed') {
          resultDiv.className = 'mt-6 p-6 bg-red-100 border-2 border-red-500 rounded-2xl text-center';
          resultDiv.innerHTML = `
            <svg class="w-16 h-16 mx-auto mb-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <h3 class="text-2xl font-bold text-red-800 mb-2">Bohužel špatně</h3>
            <p class="text-lg text-red-700">${data.message}</p>
          `;
        } else {
          resultDiv.className = 'mt-6 p-6 bg-blue-100 border-2 border-blue-500 rounded-2xl text-center';
          resultDiv.innerHTML = `
            <svg class="w-16 h-16 mx-auto mb-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <h3 class="text-2xl font-bold text-blue-800 mb-2">Odpověď odeslána</h3>
            <p class="text-lg text-blue-700">${data.message}</p>
          `;
        }

        setTimeout(() => {
          window.location.href = './stations.php';
        }, 3000);

      } else {
        resultDiv.classList.remove('hidden');
        resultDiv.className = 'mt-6 p-6 bg-red-100 border-2 border-red-500 rounded-2xl';
        resultDiv.textContent = 'Chyba: ' + data.error;
        submitBtn.disabled = false;
        submitBtn.textContent = '🚀 Odeslat odpověď';
      }

    } catch (error) {
      resultDiv.classList.remove('hidden');
      resultDiv.className = 'mt-6 p-6 bg-red-100 border-2 border-red-500 rounded-2xl';
      resultDiv.textContent = 'Chyba připojení: ' + error.message;
      submitBtn.disabled = false;
      submitBtn.textContent = '🚀 Odeslat odpověď';
    }
  });

  // Atmosféra audio - umožnit uživateli spustit
  <?php if ($station['audio_atmosphere']): ?>
  document.addEventListener('click', function() {
    const audio = document.getElementById('atmosphereAudio');
    if (audio && audio.paused) {
      audio.play().catch(e => console.log('Audio autoplay prevented'));
    }
  }, { once: true });
  <?php endif; ?>
  </script>
</body>
</html>