<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = $_SESSION['user'];

if ($user['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$pdo = db();

// Get station ID
$station_id = intval($_GET['id'] ?? 0);
if (!$station_id) {
    header('Location: stations.php');
    exit;
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_virtual_settings') {
        $task_type = $_POST['task_type'] ?? 'text';
        $media_type = $_POST['media_type'] ?? 'none';
        $media_url = trim($_POST['media_url'] ?? '');
        $quiz_options = !empty($_POST['quiz_options']) ? json_encode(array_filter(array_map('trim', explode("\n", $_POST['quiz_options'])))) : null;
        $hints = [];
        if (!empty($_POST['hint_text'])) {
            foreach ($_POST['hint_text'] as $i => $text) {
                if (trim($text)) {
                    $hints[] = [
                        'text' => trim($text),
                        'points_cost' => intval($_POST['hint_cost'][$i] ?? 0)
                    ];
                }
            }
        }
        $hints_json = !empty($hints) ? json_encode($hints) : null;
        
        $speed_bonus = intval($_POST['speed_bonus'] ?? 0);
        $penalty_wrong = intval($_POST['penalty_wrong'] ?? 0);
        $auto_evaluate = isset($_POST['auto_evaluate']) ? 1 : 0;
        $allow_file_upload = isset($_POST['allow_file_upload']) ? 1 : 0;
        $allow_voting = isset($_POST['allow_voting']) ? 1 : 0;
        $custom_background = trim($_POST['custom_background'] ?? '');
        $custom_colors = json_encode([
            'primary' => trim($_POST['color_primary'] ?? '#667eea'),
            'secondary' => trim($_POST['color_secondary'] ?? '#764ba2'),
            'text' => trim($_POST['color_text'] ?? '#ffffff')
        ]);
        $station_logo = trim($_POST['station_logo'] ?? '');
        $audio_atmosphere = trim($_POST['audio_atmosphere'] ?? '');
        $show_score = isset($_POST['show_score']) ? 1 : 0;
        $random_questions = isset($_POST['random_questions']) ? 1 : 0;
        $question_pool = !empty($_POST['question_pool']) ? json_encode(array_filter(array_map('trim', explode("\n", $_POST['question_pool'])))) : null;
        $require_physical_qr = isset($_POST['require_physical_qr']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE stations 
            SET task_type = ?, media_type = ?, media_url = ?, quiz_options = ?, hints = ?,
                speed_bonus = ?, penalty_wrong = ?, auto_evaluate = ?, allow_file_upload = ?,
                allow_voting = ?, custom_background = ?, custom_colors = ?, station_logo = ?,
                audio_atmosphere = ?, show_score = ?, random_questions = ?, question_pool = ?,
                require_physical_qr = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $task_type, $media_type, $media_url, $quiz_options, $hints_json,
            $speed_bonus, $penalty_wrong, $auto_evaluate, $allow_file_upload,
            $allow_voting, $custom_background, $custom_colors, $station_logo,
            $audio_atmosphere, $show_score, $random_questions, $question_pool,
            $require_physical_qr, $station_id
        ]);
        
        header('Location: virtual_station_settings.php?id=' . $station_id . '&success=1');
        exit;
    }
}

// Get station details
$stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
$stmt->execute([$station_id]);
$station = $stmt->fetch();

if (!$station || $station['type'] !== 'virtual') {
    header('Location: stations.php');
    exit;
}

$custom_colors = json_decode($station['custom_colors'] ?? '{}', true);
$hints = json_decode($station['hints'] ?? '[]', true);
$quiz_options = json_decode($station['quiz_options'] ?? '[]', true);
$question_pool = json_decode($station['question_pool'] ?? '[]', true);
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Virtuální stanoviště — <?= htmlspecialchars($station['name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .section-card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .section-title {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 16px;
      color: #1f2937;
      display: flex;
      align-items: center;
      gap: 8px;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <nav class="bg-white shadow-sm border-b mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <a href="./station_settings.php?id=<?=$station_id?>" class="text-blue-600 hover:text-blue-800 mr-4">← Základní nastavení</a>
          <h1 class="text-xl font-bold text-gray-900">
            Virtuální stanoviště: <?= htmlspecialchars($station['name']) ?>
          </h1>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      Nastavení bylo úspěšně uloženo.
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-0">
      <input type="hidden" name="action" value="update_virtual_settings">
      
      <!-- Typ úkolu -->
      <div class="section-card">
        <div class="section-title">
          📝 Typ úkolu
        </div>
        <select name="task_type" id="taskType" onchange="updateTaskFields()" class="w-full border-gray-300 rounded-md">
          <option value="text" <?=$station['task_type']==='text'?'selected':''?>>Textová otázka</option>
          <option value="quiz" <?=$station['task_type']==='quiz'?'selected':''?>>Kvíz (výběr z možností)</option>
          <option value="file_upload" <?=$station['task_type']==='file_upload'?'selected':''?>>Nahrání souboru</option>
          <option value="creative" <?=$station['task_type']==='creative'?'selected':''?>>Kreativní úkol</option>
          <option value="mixed" <?=$station['task_type']==='mixed'?'selected':''?>>Kombinovaný</option>
        </select>
      </div>

      <!-- Média jako podklad -->
      <div class="section-card">
        <div class="section-title">
          🎬 Média jako podklad
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Typ média</label>
            <select name="media_type" class="w-full border-gray-300 rounded-md">
              <option value="none" <?=$station['media_type']==='none'?'selected':''?>>Žádné</option>
              <option value="image" <?=$station['media_type']==='image'?'selected':''?>>Obrázek</option>
              <option value="video" <?=$station['media_type']==='video'?'selected':''?>>Video</option>
              <option value="audio" <?=$station['media_type']==='audio'?'selected':''?>>Audio</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">URL média</label>
            <input type="url" name="media_url" value="<?=htmlspecialchars($station['media_url']??'')?>" class="w-full border-gray-300 rounded-md" placeholder="https://...">
          </div>
        </div>
      </div>

      <!-- Možnosti kvízu -->
      <div class="section-card" id="quizOptionsSection" style="display:none;">
        <div class="section-title">
          ✅ Možnosti kvízu
        </div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Možnosti odpovědí (každá na nový řádek)</label>
        <textarea name="quiz_options" rows="5" class="w-full border-gray-300 rounded-md" placeholder="Možnost A&#10;Možnost B&#10;Možnost C"><?=implode("\n", $quiz_options)?></textarea>
        <p class="text-sm text-gray-500 mt-2">První možnost bude považována za správnou odpověď.</p>
      </div>

      <!-- Nápovědy -->
      <div class="section-card">
        <div class="section-title">
          💡 Nápovědy
        </div>
        <div id="hintsContainer" class="space-y-3">
          <?php if (empty($hints)): ?>
          <div class="hint-row grid grid-cols-12 gap-3">
            <div class="col-span-9">
              <input type="text" name="hint_text[]" placeholder="Text nápovědy" class="w-full border-gray-300 rounded-md">
            </div>
            <div class="col-span-3">
              <input type="number" name="hint_cost[]" placeholder="Cena v bodech" min="0" class="w-full border-gray-300 rounded-md">
            </div>
          </div>
          <?php else: ?>
          <?php foreach ($hints as $hint): ?>
          <div class="hint-row grid grid-cols-12 gap-3">
            <div class="col-span-9">
              <input type="text" name="hint_text[]" value="<?=htmlspecialchars($hint['text'])?>" placeholder="Text nápovědy" class="w-full border-gray-300 rounded-md">
            </div>
            <div class="col-span-3">
              <input type="number" name="hint_cost[]" value="<?=$hint['points_cost']?>" placeholder="Cena v bodech" min="0" class="w-full border-gray-300 rounded-md">
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <button type="button" onclick="addHint()" class="mt-3 px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-sm font-medium">
          + Přidat nápovědu
        </button>
      </div>

      <!-- Bodování a vyhodnocení -->
      <div class="section-card">
        <div class="section-title">
          ⭐ Bodování a vyhodnocení
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Speed bonus (body)</label>
            <input type="number" name="speed_bonus" value="<?=$station['speed_bonus']??0?>" min="0" class="w-full border-gray-300 rounded-md">
            <p class="text-xs text-gray-500 mt-1">Bonus za první správnou odpověď</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Penalizace za chybu (body)</label>
            <input type="number" name="penalty_wrong" value="<?=$station['penalty_wrong']??0?>" min="0" class="w-full border-gray-300 rounded-md">
            <p class="text-xs text-gray-500 mt-1">Odečtené body za špatnou odpověď</p>
          </div>
        </div>
        <div class="space-y-3">
          <label class="flex items-center">
            <input type="checkbox" name="auto_evaluate" value="1" <?=$station['auto_evaluate']?'checked':''?> class="rounded border-gray-300 text-blue-600">
            <span class="ml-2 text-sm font-medium text-gray-700">Automatické vyhodnocení</span>
          </label>
          <label class="flex items-center">
            <input type="checkbox" name="show_score" value="1" <?=$station['show_score']?'checked':''?> class="rounded border-gray-300 text-blue-600">
            <span class="ml-2 text-sm font-medium text-gray-700">Zobrazit skóre po dokončení</span>
          </label>
        </div>
      </div>

      <!-- Interaktivita -->
      <div class="section-card">
        <div class="section-title">
          🎯 Interaktivita
        </div>
        <div class="space-y-3">
          <label class="flex items-center">
            <input type="checkbox" name="allow_file_upload" value="1" <?=$station['allow_file_upload']?'checked':''?> class="rounded border-gray-300 text-blue-600">
            <span class="ml-2 text-sm font-medium text-gray-700">Povolit nahrávání souborů (foto, video, dokumenty)</span>
          </label>
          <label class="flex items-center">
            <input type="checkbox" name="allow_voting" value="1" <?=$station['allow_voting']?'checked':''?> class="rounded border-gray-300 text-blue-600">
            <span class="ml-2 text-sm font-medium text-gray-700">Povolit hlasování mezi týmy</span>
          </label>
        </div>
      </div>

      <!-- Vizuální úpravy -->
      <div class="section-card">
        <div class="section-title">
          🎨 Vizuální úpravy
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Vlastní pozadí (URL)</label>
            <input type="url" name="custom_background" value="<?=htmlspecialchars($station['custom_background']??'')?>" class="w-full border-gray-300 rounded-md" placeholder="https://...">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Logo stanoviště (URL)</label>
            <input type="url" name="station_logo" value="<?=htmlspecialchars($station['station_logo']??'')?>" class="w-full border-gray-300 rounded-md" placeholder="https://...">
          </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Primární barva</label>
            <input type="color" name="color_primary" value="<?=$custom_colors['primary']??'#667eea'?>" class="w-full h-10 border-gray-300 rounded-md">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sekundární barva</label>
            <input type="color" name="color_secondary" value="<?=$custom_colors['secondary']??'#764ba2'?>" class="w-full h-10 border-gray-300 rounded-md">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Barva textu</label>
            <input type="color" name="color_text" value="<?=$custom_colors['text']??'#ffffff'?>" class="w-full h-10 border-gray-300 rounded-md">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Zvuková atmosféra (URL)</label>
          <input type="url" name="audio_atmosphere" value="<?=htmlspecialchars($station['audio_atmosphere']??'')?>" class="w-full border-gray-300 rounded-md" placeholder="https://...">
        </div>
      </div>

      <!-- Speciální funkce -->
      <div class="section-card">
        <div class="section-title">
          🔧 Speciální funkce
        </div>
        <div class="space-y-4">
          <label class="flex items-start">
            <input type="checkbox" name="require_physical_qr" value="1" <?=$station['require_physical_qr']?'checked':''?> class="rounded border-gray-300 text-blue-600 mt-1">
            <span class="ml-2">
              <span class="text-sm font-medium text-gray-700 block">Vyžadovat načtení fyzického QR kódu</span>
              <span class="text-xs text-gray-500">Virtuální úkol bude přístupný až po naskenování fyzického QR kódu stanoviště</span>
            </span>
          </label>
          
          <label class="flex items-start">
            <input type="checkbox" name="random_questions" value="1" <?=$station['random_questions']?'checked':''?> class="rounded border-gray-300 text-blue-600 mt-1">
            <span class="ml-2">
              <span class="text-sm font-medium text-gray-700 block">Náhodný výběr otázek</span>
              <span class="text-xs text-gray-500">Každý tým dostane náhodnou otázku z poolu níže</span>
            </span>
          </label>
          
          <div id="questionPoolSection" style="display:<?=$station['random_questions']?'block':'none'?>;">
            <label class="block text-sm font-medium text-gray-700 mb-2">Pool otázek (každá na nový řádek)</label>
            <textarea name="question_pool" rows="6" class="w-full border-gray-300 rounded-md" placeholder="Otázka 1?&#10;Otázka 2?&#10;Otázka 3?"><?=implode("\n", $question_pool)?></textarea>
          </div>
        </div>
      </div>

      <div class="section-card">
        <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold text-lg">
          💾 Uložit nastavení virtuálního stanoviště
        </button>
      </div>
    </form>
  </div>

  <script>
  function updateTaskFields() {
    const taskType = document.getElementById('taskType').value;
    const quizSection = document.getElementById('quizOptionsSection');
    
    if (taskType === 'quiz') {
      quizSection.style.display = 'block';
    } else {
      quizSection.style.display = 'none';
    }
  }

  function addHint() {
    const container = document.getElementById('hintsContainer');
    const row = document.createElement('div');
    row.className = 'hint-row grid grid-cols-12 gap-3';
    row.innerHTML = `
      <div class="col-span-9">
        <input type="text" name="hint_text[]" placeholder="Text nápovědy" class="w-full border-gray-300 rounded-md">
      </div>
      <div class="col-span-3">
        <input type="number" name="hint_cost[]" placeholder="Cena v bodech" min="0" class="w-full border-gray-300 rounded-md">
      </div>
    `;
    container.appendChild(row);
  }

  document.querySelector('input[name="random_questions"]').addEventListener('change', function() {
    document.getElementById('questionPoolSection').style.display = this.checked ? 'block' : 'none';
  });

  updateTaskFields();
  </script>
</body>
</html>