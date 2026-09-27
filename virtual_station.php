<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/virtual_station_functions.php';
require_login();
$user = $_SESSION['user'];
$pdo = db();

$station_id = intval($_GET['id'] ?? 0);
if (!$station_id) {
    header('Location: stations');
    exit;
}

$team_id = $user['team_id'] ?? null;
if (!$team_id) {
    header('Location: stations?error=no_team');
    exit;
}

$station = get_virtual_station_data_v2($station_id, $team_id);

if (!$station || $station['type'] !== 'virtual') {
    header('Location: stations');
    exit;
}

// Zkontrolovat požadavek na fyzické QR (stejný kód jako v původním souboru)
if ($station['require_physical_qr'] && !check_physical_qr_requirement($station_id, $team_id)) {
    $colors = $station['custom_colors'];
    $primary_color = $colors['primary'] ?? '#667eea';
    $secondary_color = $colors['secondary'] ?? '#764ba2';
    ?>
    <!doctype html>
    <html lang="cs">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
      <title><?=htmlspecialchars($station['name'])?> — Vyžadován QR kód</title>
      <script src="https://cdn.tailwindcss.com"></script>
      <style>
        body {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
          background: linear-gradient(135deg, <?=$primary_color?> 0%, <?=$secondary_color?> 100%);
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 20px;
        }
        .qr-required-container {
          max-width: 500px;
          background: white;
          border-radius: 24px;
          padding: 40px 30px;
          text-align: center;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .qr-icon {
          width: 100px;
          height: 100px;
          margin: 0 auto 24px;
          background: linear-gradient(135deg, <?=$primary_color?> 0%, <?=$secondary_color?> 100%);
          border-radius: 20px;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .btn-primary {
          background: linear-gradient(135deg, <?=$primary_color?> 0%, <?=$secondary_color?> 100%);
          color: white;
          padding: 14px 32px;
          border-radius: 12px;
          font-weight: 600;
          text-decoration: none;
          display: inline-block;
          margin-top: 24px;
          transition: all 0.3s;
        }
        .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }
        .btn-secondary {
          background: white;
          color: <?=$primary_color?>;
          padding: 12px 28px;
          border-radius: 10px;
          font-weight: 600;
          text-decoration: none;
          display: inline-block;
          margin-top: 12px;
          transition: all 0.3s;
          border: 2px solid <?=$primary_color?>;
        }
        .btn-secondary:hover {
          background: #f9fafb;
        }
      </style>
    </head>
    <body>
      <div class="qr-required-container">
        <div class="qr-icon">
          <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">🔒 QR kód vyžadován</h1>
        <p class="text-lg text-gray-600 mb-6">
          Pro přístup k tomuto virtuálnímu úkolu musíte nejprve navštívit fyzické stanoviště a naskenovat QR kód.
        </p>
        <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4 mb-6">
          <p class="text-red-800 font-semibold">📍 Stanoviště: <?=htmlspecialchars($station['name'])?></p>
          <?php if ($station['location']): ?>
          <p class="text-red-600 text-sm mt-2">Umístění: <?=htmlspecialchars($station['location'])?></p>
          <?php endif; ?>
        </div>
        <a href="./scan_station_qr?station_id=<?=$station_id?>" class="btn-primary">
          📷 Naskenovat QR kód stanoviště
        </a>
        <br>
        <a href="./stations" class="btn-secondary">← Zpět na stanoviště</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Zkontrolovat, zda už nesplnili celé stanoviště
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

$session = $station['session'];
$tasks = $station['tasks'];
$tasks = array_values($tasks);
$task_answers = $station['task_answers'];
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
    
    * {
      -webkit-tap-highlight-color: transparent;
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
      padding: 16px;
      padding-bottom: 20px;
    }
    
    .station-container {
      max-width: 800px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 24px 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .station-header {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: var(--text-color);
      padding: 20px;
      border-radius: 16px;
      margin-bottom: 20px;
      text-align: center;
    }
    
    .station-header h1 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 8px;
      line-height: 1.3;
    }
    
    .timer {
      background: rgba(0, 0, 0, 0.15);
      padding: 8px 16px;
      border-radius: 8px;
      display: inline-block;
      font-weight: 600;
      margin-top: 12px;
      font-size: 16px;
    }
    
    .task-card {
      background: white;
      border: 2px solid #e5e7eb;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
      transition: all 0.3s;
    }
    
    .task-card.completed {
      border-color: #10b981;
      background: #f0fdf4;
    }
    
    .task-card.active {
      border-color: var(--primary-color);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }
    
    .task-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }
    
    .task-number {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 16px;
    }
    
    .task-number.completed {
      background: #10b981;
    }
    
    .task-points {
      background: #fef3c7;
      color: #92400e;
      padding: 6px 12px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
    }
    
    .media-container {
      margin: 16px 0;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .media-container img,
    .media-container video {
      width: 100%;
      display: block;
    }
    
    .media-container iframe {
      width: 100%;
      height: 300px;
      border: none;
    }
    
    .hint-card {
      background: #f8f9fa;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      padding: 14px;
      margin-bottom: 10px;
      transition: all 0.3s;
    }
    
    .hint-revealed {
      background: #d4edda;
      border-color: #28a745;
    }
    
    .quiz-option {
      background: white;
      border: 2px solid #dee2e6;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 12px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 16px;
      text-align: left;
    }
    
    .quiz-option:active {
      transform: scale(0.98);
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
      padding: 16px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 17px;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
      width: 100%;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }
    
    .btn-primary:active {
      transform: scale(0.98);
    }
    
    .btn-primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    
    .score-display {
      background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
      color: #333;
      padding: 14px;
      border-radius: 12px;
      text-align: center;
      font-weight: bold;
      font-size: 17px;
      margin-bottom: 16px;
      box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 14px;
      color: #1f2937;
    }
    
    .form-label {
      display: block;
      font-weight: 600;
      margin-bottom: 10px;
      font-size: 16px;
      color: #374151;
    }
    
    .form-input, .form-textarea {
      width: 100%;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      padding: 14px;
      font-size: 16px;
      transition: all 0.3s;
    }
    
    .form-input:focus, .form-textarea:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .file-input-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
      width: 100%;
    }
    
    .file-input-wrapper input[type=file] {
      position: absolute;
      left: -9999px;
    }
    
    .file-input-label {
      display: block;
      padding: 14px;
      background: #f3f4f6;
      border: 2px dashed #d1d5db;
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 15px;
      color: #6b7280;
    }
    
    .file-input-label:hover {
      border-color: var(--primary-color);
      background: #eff6ff;
      color: var(--primary-color);
    }
    
    .file-selected {
      border-color: var(--primary-color);
      background: #eff6ff;
      color: var(--primary-color);
    }
    
    .progress-bar {
      background: #e5e7eb;
      border-radius: 10px;
      height: 8px;
      overflow: hidden;
      margin-top: 12px;
    }
    
    .progress-fill {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      height: 100%;
      transition: width 0.3s;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
      animation: fadeIn 0.5s ease-out;
    }
    
    .back-link {
      display: inline-block;
      margin-top: 16px;
      color: #6b7280;
      text-decoration: none;
      font-size: 15px;
      transition: color 0.3s;
    }
    
    .back-link:hover {
      color: #374151;
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
      <img src="<?=htmlspecialchars($station['station_logo'])?>" alt="Logo" style="max-width: 100px; margin: 0 auto 12px;">
      <?php endif; ?>
      <h1><?=htmlspecialchars($station['name'])?></h1>
      <p><?=htmlspecialchars($station['description'])?></p>
      <div class="timer">
        <span id="timer">00:00</span>
      </div>
    </div>

    <?php if ($team && $station['show_score']): ?>
    <div class="score-display">
      🏆 Aktuální skóre: <?=$team['points']?> bodů
    </div>
    <?php endif; ?>

    <?php if ($completed): ?>
      <!-- Již splněno -->
      <div class="p-6 bg-green-100 border-2 border-green-500 rounded-2xl text-center">
        <svg class="w-20 h-20 mx-auto mb-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <h2 class="text-2xl font-bold text-green-800 mb-2">Stanoviště splněno!</h2>
        <p class="text-lg text-green-700">Získali jste <?=$completed['points']?> bodů.</p>
        <a href="./stations" class="btn-primary mt-6" style="display: inline-block; text-decoration: none;">Zpět na stanoviště</a>
      </div>
    <?php else: ?>
      <!-- Progress -->
      <div class="mb-5">
        <div class="flex justify-between items-center mb-2">
          <span class="text-sm font-semibold text-gray-700">Postup: <?=count($task_answers)?> / <?=count($tasks)?> úkolů</span>
          <span class="text-sm font-semibold text-gray-700"><?=count($tasks) > 0 ? round(count($task_answers) / count($tasks) * 100) : 0?>%</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" style="width: <?=count($tasks) > 0 ? round(count($task_answers) / count($tasks) * 100) : 0?>%"></div>
        </div>
      </div>

      <!-- Nápovědy -->
      <?php if (!empty($station['hints'])): ?>
      <div class="mb-5">
        <h4 class="section-title">💡 Nápovědy</h4>
        <div id="hintsContainer">
          <?php foreach ($station['hints'] as $index => $hint): ?>
          <div class="hint-card <?=in_array($index, $station['used_hints'])?'hint-revealed':''?>" data-hint-index="<?=$index?>">
            <?php if (in_array($index, $station['used_hints'])): ?>
              <p class="text-gray-800"><?=htmlspecialchars($hint['text'])?></p>
            <?php else: ?>
              <div class="flex justify-between items-center">
                <span class="font-medium">Nápověda #<?=$index + 1?></span>
                <button onclick="revealHint(<?=$index?>, <?=$hint['points_cost']?>)" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                  Odhalit (-<?=$hint['points_cost']?> bodů)
                </button>
              </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Úkoly -->
      <?php foreach ($tasks as $index => $task): 
        $is_completed = isset($task_answers[$task['id']]);
        $is_active = !$is_completed && ($index === 0 || isset($task_answers[$tasks[$index-1]['id']]));
      ?>
      <div class="task-card <?=$is_completed ? 'completed' : ($is_active ? 'active' : '')?>" id="task-<?=$task['id']?>">
        <div class="task-header">
          <div class="flex items-center gap-3">
            <div class="task-number <?=$is_completed ? 'completed' : ''?>">
              <?php if ($is_completed): ?>
                ✓
              <?php else: ?>
                <?=$index + 1?>
              <?php endif; ?>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Úkol <?=$index + 1?></h3>
          </div>
          <div class="task-points">
            🏆 <?=$task['reward_points']?> bodů
          </div>
        </div>

        <?php if ($is_completed): ?>
          <!-- Dokončeno -->
          <div class="bg-green-50 border-2 border-green-200 rounded-xl p-4">
            <p class="text-green-800 font-semibold">✓ Úkol dokončen</p>
            <p class="text-green-700 text-sm mt-1">Získáno: <?=$task_answers[$task['id']]['points_awarded']?> bodů</p>
          </div>
        <?php elseif (!$is_active): ?>
          <!-- Zamčeno -->
          <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-4 text-center">
            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
            </svg>
            <p class="text-gray-600 font-semibold">🔒 Dokončete předchozí úkol</p>
          </div>
        <?php else: ?>
          <!-- Aktivní úkol -->
          
          <!-- Média -->
          <?php if ($task['media_type'] !== 'none' && $task['media_url']): ?>
          <div class="media-container">
            <?php if ($task['media_type'] === 'image'): ?>
              <img src="<?=htmlspecialchars($task['media_url'])?>" alt="Obrázek úkolu">
            <?php elseif ($task['media_type'] === 'video'): ?>
              <video controls>
                <source src="<?=htmlspecialchars($task['media_url'])?>" type="video/mp4">
              </video>
            <?php elseif ($task['media_type'] === 'audio'): ?>
              <audio controls style="width: 100%;">
                <source src="<?=htmlspecialchars($task['media_url'])?>" type="audio/mpeg">
              </audio>
            <?php elseif ($task['media_type'] === 'youtube'): ?>
              <?php
              // Extrahovat YouTube ID z URL
              $youtube_id = '';
              if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\?\/]+)/', $task['media_url'], $matches)) {
                $youtube_id = $matches[1];
              }
              ?>
              <?php if ($youtube_id): ?>
              <iframe src="https://www.youtube.com/embed/<?=$youtube_id?>" allowfullscreen></iframe>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Otázka -->
          <div class="mb-4">
            <h4 class="section-title"><?=htmlspecialchars($task['question'])?></h4>
          </div>

          <!-- Formulář odpovědi -->
          <form class="task-form" data-task-id="<?=$task['id']?>" enctype="multipart/form-data">
            <input type="hidden" name="station_id" value="<?=$station_id?>">
            <input type="hidden" name="task_id" value="<?=$task['id']?>">

            <?php if ($task['task_type'] === 'quiz' || $task['task_type'] === 'creative'): ?>
              <!-- Kvíz -->
              <div class="mb-4">
                <label class="form-label">Vyberte odpověď:</label>
                <div class="quiz-options">
                  <?php foreach ($task['quiz_options'] as $option): ?>
                  <div class="quiz-option" onclick="selectQuizOption(this, '<?=htmlspecialchars($option, ENT_QUOTES)?>', <?=$task['id']?>)">
                    <?=htmlspecialchars($option)?>
                  </div>
                  <?php endforeach; ?>
                </div>
                <input type="hidden" name="answer" class="quiz-answer-<?=$task['id']?>">
              </div>
            <?php endif; ?>

            <?php if ($task['task_type'] === 'text' || $task['task_type'] === 'mixed'): ?>
              <!-- Textová odpověď -->
              <div class="mb-4">
                <label class="form-label">Vaše odpověď:</label>
                <textarea name="answer" rows="4" <?=$task['task_type'] === 'text' ? 'required' : ''?>
                          class="form-textarea"
                          placeholder="Napište zde svou odpověď..."></textarea>
              </div>
            <?php endif; ?>

            <?php if ($task['task_type'] === 'file_upload' || $task['task_type'] === 'mixed' || $task['task_type'] === 'creative' || $task['allow_file_upload']): ?>
            <div class="mb-4">
              <label class="form-label">
                <?php if ($task['task_type'] === 'file_upload'): ?>
                  Nahrajte fotku nebo video: *
                <?php else: ?>
                  Nahrát soubor (volitelné):
                <?php endif; ?>
              </label>
              <div class="file-input-wrapper">
                <input type="file" name="file" id="fileInput-<?=$task['id']?>" 
                       accept="image/*,video/*,audio/*,.pdf" 
                       <?php if ($task['task_type'] === 'file_upload'): ?>required<?php endif; ?>
                       onchange="updateFileLabel(this, <?=$task['id']?>)">
                <label for="fileInput-<?=$task['id']?>" class="file-input-label" id="fileLabel-<?=$task['id']?>">
                  📷 Klikněte pro výběr souboru<br>
                  <small>Obrázky, videa, audio, PDF (max 10MB)</small>
                </label>
              </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn-primary">
              🚀 Odeslat odpověď
            </button>
          </form>

          <div class="result-message-<?=$task['id']?> mt-4 hidden"></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <div class="text-center">
        <a href="./stations" class="back-link">← Zpět na stanoviště</a>
      </div>
    <?php endif; ?>
  </div>

  <script>
  // Timer - počítá od začátku session
  const sessionStartTime = <?=strtotime($session['started_at'])?>; 
  
  function updateTimer() {
    const now = Math.floor(Date.now() / 1000);
    const elapsed = now - sessionStartTime;
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;
    document.getElementById('timer').textContent = 
      String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
  }
  
  setInterval(updateTimer, 1000);
  updateTimer();

  // File label update
  function updateFileLabel(input, taskId) {
    const label = document.getElementById('fileLabel-' + taskId);
    if (input.files && input.files[0]) {
      label.innerHTML = '✓ ' + input.files[0].name;
      label.classList.add('file-selected');
    }
  }

  // Kvíz výběr
  function selectQuizOption(element, answer, taskId) {
    const container = element.closest('.quiz-options');
    container.querySelectorAll('.quiz-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    document.querySelector('.quiz-answer-' + taskId).value = answer;
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

  // Odeslání formuláře úkolu
  document.querySelectorAll('.task-form').forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(e.target);
      const taskId = formData.get('task_id');
      const submitBtn = e.target.querySelector('button[type="submit"]');
      const resultDiv = document.querySelector('.result-message-' + taskId);

      // Validace
      const taskType = '<?=$task['task_type']?>';
      const answer = formData.get('answer');
      
      if ((taskType === 'quiz' || taskType === 'creative') && (!answer || answer.trim() === '')) {
        alert('Prosím vyberte odpověď');
        return;
      }
      
      if (taskType === 'text' && (!answer || answer.trim() === '')) {
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
            resultDiv.className = 'result-message-' + taskId + ' mt-4 p-6 bg-green-100 border-2 border-green-500 rounded-2xl text-center';
            resultDiv.innerHTML = `
              <svg class="w-16 h-16 mx-auto mb-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <h3 class="text-2xl font-bold text-green-800 mb-2">Skvělé!</h3>
              <p class="text-lg text-green-700">${data.message}</p>
            `;
          } else if (data.status === 'failed') {
            resultDiv.className = 'result-message-' + taskId + ' mt-4 p-6 bg-red-100 border-2 border-red-500 rounded-2xl text-center';
            resultDiv.innerHTML = `
              <svg class="w-16 h-16 mx-auto mb-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <h3 class="text-2xl font-bold text-red-800 mb-2">Bohužel špatně</h3>
              <p class="text-lg text-red-700">${data.message}</p>
            `;
          } else {
            resultDiv.className = 'result-message-' + taskId + ' mt-4 p-6 bg-blue-100 border-2 border-blue-500 rounded-2xl text-center';
            resultDiv.innerHTML = `
              <h3 class="text-2xl font-bold text-blue-800 mb-2">Odpověď odeslána</h3>
              <p class="text-lg text-blue-700">${data.message}</p>
            `;
          }

          if (data.station_completed) {
            setTimeout(() => {
              window.location.href = './stations';
            }, 3000);
          } else {
            setTimeout(() => {
              window.location.reload();
            }, 2000);
          }

        } else {
          resultDiv.classList.remove('hidden');
          resultDiv.className = 'result-message-' + taskId + ' mt-4 p-6 bg-red-100 border-2 border-red-500 rounded-2xl';
          resultDiv.textContent = 'Chyba: ' + data.error;
          submitBtn.disabled = false;
          submitBtn.textContent = '🚀 Odeslat odpověď';
        }

      } catch (error) {
        resultDiv.classList.remove('hidden');
        resultDiv.className = 'result-message-' + taskId + ' mt-4 p-6 bg-red-100 border-2 border-red-500 rounded-2xl';
        resultDiv.textContent = 'Chyba připojení: ' + error.message;
        submitBtn.disabled = false;
        submitBtn.textContent = '🚀 Odeslat odpověď';
      }
    });
  });

  // Atmosféra audio
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