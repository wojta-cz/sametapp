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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_task') {
        $question = trim($_POST['question'] ?? '');
        $correct_answer = trim($_POST['correct_answer'] ?? '');
        $task_type = $_POST['task_type'] ?? 'text';
        $media_type = $_POST['media_type'] ?? 'none';
        $media_url = trim($_POST['media_url'] ?? '');
        $quiz_options = !empty($_POST['quiz_options']) ? json_encode(array_filter(array_map('trim', explode("\n", $_POST['quiz_options'])))) : null;
        
        // NEW: Handle multiple correct answers for quiz
        $quiz_correct_answers = null;
        if ($task_type === 'quiz' || $task_type === 'creative') {
            if (!empty($_POST['quiz_correct_answers'])) {
                $correct_arr = array_filter(array_map('trim', explode("\n", $_POST['quiz_correct_answers'])));
                $quiz_correct_answers = json_encode($correct_arr);
            }
        }
        
        // NEW: Handle custom feedback messages
        $quiz_answer_messages = null;
        if ($task_type === 'quiz' || $task_type === 'creative') {
            $messages = [];
            if (!empty($_POST['quiz_message_correct'])) {
                $messages['correct'] = trim($_POST['quiz_message_correct']);
            }
            if (!empty($_POST['quiz_message_incorrect'])) {
                $messages['incorrect'] = trim($_POST['quiz_message_incorrect']);
            }
            if (!empty($messages)) {
                $quiz_answer_messages = json_encode($messages);
            }
        }
        
        $reward_points = intval($_POST['reward_points'] ?? 10);
        $max_points = !empty($_POST['max_points']) ? intval($_POST['max_points']) : null;
        $min_points = intval($_POST['min_points'] ?? 0);
        $speed_bonus = intval($_POST['speed_bonus'] ?? 0);
        $penalty_wrong = intval($_POST['penalty_wrong'] ?? 0);
        $allow_file_upload = isset($_POST['allow_file_upload']) ? 1 : 0;
        $task_order = intval($_POST['task_order'] ?? 0);
        
        $stmt = $pdo->prepare("
            INSERT INTO station_tasks 
            (station_id, question, correct_answer, task_type, media_type, media_url, quiz_options, 
             quiz_correct_answers, quiz_answer_messages, reward_points, max_points, min_points, speed_bonus, penalty_wrong, allow_file_upload, task_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $station_id, $question, $correct_answer, $task_type, $media_type, $media_url, $quiz_options,
            $quiz_correct_answers, $quiz_answer_messages, $reward_points, $max_points, $min_points, $speed_bonus, $penalty_wrong, $allow_file_upload, $task_order
        ]);
        
        header('Location: virtual_station_tasks.php?id=' . $station_id . '&success=added');
        exit;
    }
    
    if ($_POST['action'] === 'update_task') {
        $task_id = intval($_POST['task_id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $correct_answer = trim($_POST['correct_answer'] ?? '');
        $task_type = $_POST['task_type'] ?? 'text';
        $media_type = $_POST['media_type'] ?? 'none';
        $media_url = trim($_POST['media_url'] ?? '');
        $quiz_options = !empty($_POST['quiz_options']) ? json_encode(array_filter(array_map('trim', explode("\n", $_POST['quiz_options'])))) : null;
        
        // NEW: Handle multiple correct answers for quiz
        $quiz_correct_answers = null;
        if ($task_type === 'quiz' || $task_type === 'creative') {
            if (!empty($_POST['quiz_correct_answers'])) {
                $correct_arr = array_filter(array_map('trim', explode("\n", $_POST['quiz_correct_answers'])));
                $quiz_correct_answers = json_encode($correct_arr);
            }
        }
        
        // NEW: Handle custom feedback messages
        $quiz_answer_messages = null;
        if ($task_type === 'quiz' || $task_type === 'creative') {
            $messages = [];
            if (!empty($_POST['quiz_message_correct'])) {
                $messages['correct'] = trim($_POST['quiz_message_correct']);
            }
            if (!empty($_POST['quiz_message_incorrect'])) {
                $messages['incorrect'] = trim($_POST['quiz_message_incorrect']);
            }
            if (!empty($messages)) {
                $quiz_answer_messages = json_encode($messages);
            }
        }
        
        $reward_points = intval($_POST['reward_points'] ?? 10);
        $max_points = !empty($_POST['max_points']) ? intval($_POST['max_points']) : null;
        $min_points = intval($_POST['min_points'] ?? 0);
        $speed_bonus = intval($_POST['speed_bonus'] ?? 0);
        $penalty_wrong = intval($_POST['penalty_wrong'] ?? 0);
        $allow_file_upload = isset($_POST['allow_file_upload']) ? 1 : 0;
        $task_order = intval($_POST['task_order'] ?? 0);
        
        $stmt = $pdo->prepare("
            UPDATE station_tasks 
            SET question = ?, correct_answer = ?, task_type = ?, media_type = ?, media_url = ?, 
                quiz_options = ?, quiz_correct_answers = ?, quiz_answer_messages = ?, 
                reward_points = ?, max_points = ?, min_points = ?, 
                speed_bonus = ?, penalty_wrong = ?, allow_file_upload = ?, task_order = ?
            WHERE id = ? AND station_id = ?
        ");
        $stmt->execute([
            $question, $correct_answer, $task_type, $media_type, $media_url, $quiz_options,
            $quiz_correct_answers, $quiz_answer_messages,
            $reward_points, $max_points, $min_points, $speed_bonus, $penalty_wrong, $allow_file_upload, 
            $task_order, $task_id, $station_id
        ]);
        
        header('Location: virtual_station_tasks.php?id=' . $station_id . '&success=updated');
        exit;
    }
    
    if ($_POST['action'] === 'delete_task') {
        $task_id = intval($_POST['task_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM station_tasks WHERE id = ? AND station_id = ?");
        $stmt->execute([$task_id, $station_id]);
        
        header('Location: virtual_station_tasks.php?id=' . $station_id . '&success=deleted');
        exit;
    }
    
    if ($_POST['action'] === 'update_selection_mode') {
        $mode = $_POST['task_selection_mode'] ?? 'all';
        $tasks_count = !empty($_POST['tasks_count']) ? intval($_POST['tasks_count']) : null;
        $fixed_task_ids = [];
        if ($mode === 'fixed' && !empty($_POST['fixed_tasks'])) {
            $fixed_task_ids = array_map('intval', $_POST['fixed_tasks']);
        }
        $fixed_task_ids_json = !empty($fixed_task_ids) ? json_encode($fixed_task_ids) : null;
        
        $stmt = $pdo->prepare("
            UPDATE stations 
            SET task_selection_mode = ?, tasks_count = ?, fixed_task_ids = ?
            WHERE id = ?
        ");
        $stmt->execute([$mode, $tasks_count, $fixed_task_ids_json, $station_id]);
        
        header('Location: virtual_station_tasks.php?id=' . $station_id . '&success=mode_updated');
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

$page = intval($_GET['page'] ?? 1);
$per_page = 50;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM station_tasks WHERE station_id = ?");
$stmt->execute([$station_id]);
$total_tasks = $stmt->fetchColumn();
$total_pages = ceil($total_tasks / $per_page);

$stmt = $pdo->prepare("
    SELECT * FROM station_tasks 
    WHERE station_id = ? 
    ORDER BY task_order ASC, id ASC
    LIMIT ? OFFSET ?
");

// bindParam nebo bindValue s PDO::PARAM_INT
$stmt->bindValue(1, $station_id, PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);

$stmt->execute();
$tasks = $stmt->fetchAll();

$fixed_task_ids = json_decode($station['fixed_task_ids'] ?? '[]', true);
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Správa úkolů — <?= htmlspecialchars($station['name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    }
    .card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .card-title {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 20px;
      color: #1e293b;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .task-item {
      background: #f8f9fa;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 12px;
      transition: all 0.3s;
    }
    .task-item:hover {
      border-color: #667eea;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
    }
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-block;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }
    .btn-secondary {
      background: #f1f5f9;
      color: #475569;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-block;
    }
    .btn-secondary:hover {
      background: #e2e8f0;
    }
    .btn-danger {
      background: #fee2e2;
      color: #dc2626;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
    }
    .btn-danger:hover {
      background: #fecaca;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-label {
      display: block;
      font-weight: 600;
      color: #475569;
      margin-bottom: 8px;
      font-size: 14px;
    }
    .form-input, .form-select, .form-textarea {
      width: 100%;
      padding: 10px 14px;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal.active {
      display: flex;
    }
    .modal-content {
      background: white;
      border-radius: 16px;
      padding: 24px;
      max-width: 700px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
    }
    .info-box {
      background: #eff6ff;
      border: 2px solid #bfdbfe;
      border-radius: 10px;
      padding: 14px;
      margin-top: 10px;
      font-size: 13px;
      color: #1e40af;
    }
    .success-box {
      background: #d1fae5;
      border: 2px solid #6ee7b7;
      border-radius: 10px;
      padding: 14px;
      margin-bottom: 20px;
      font-size: 14px;
      color: #065f46;
    }
    .checkbox-label {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
      margin-bottom: 10px;
    }
    .checkbox-label:hover {
      border-color: #667eea;
      background: #f8f9ff;
    }
    .checkbox-label input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
    }
    .breadcrumb {
      background: white;
      padding: 12px 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
    }
    .breadcrumb a {
      color: #667eea;
      text-decoration: none;
    }
    .breadcrumb a:hover {
      text-decoration: underline;
    }
    .pagination {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-top: 20px;
    }
    .pagination a, .pagination span {
      padding: 8px 12px;
      border: 2px solid #e2e8f0;
      border-radius: 6px;
      text-decoration: none;
      color: #475569;
    }
    .pagination a:hover {
      border-color: #667eea;
      background: #f8f9ff;
    }
    .pagination .active {
      background: #667eea;
      color: white;
      border-color: #667eea;
    }
  </style>
</head>
<body>
  <nav class="bg-white shadow-sm border-b mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <a href="./stations.php" class="text-blue-600 hover:text-blue-800 mr-4">← Stanoviště</a>
          <h1 class="text-xl font-bold text-gray-900">
            Správa úkolů: <?= htmlspecialchars($station['name']) ?>
          </h1>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="./stations.php">Stanoviště</a>
      <span>→</span>
      <a href="./virtual_station_settings.php?id=<?=$station_id?>">Nastavení</a>
      <span>→</span>
      <span>Úkoly</span>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="success-box">
      <?php
      $messages = [
        'added' => '✓ Úkol byl úspěšně přidán.',
        'updated' => '✓ Úkol byl úspěšně aktualizován.',
        'deleted' => '✓ Úkol byl úspěšně smazán.',
        'mode_updated' => '✓ Režim výběru úkolů byl aktualizován.'
      ];
      echo $messages[$_GET['success']] ?? '✓ Operace byla úspěšná.';
      ?>
    </div>
    <?php endif; ?>

    <!-- Režim výběru úkolů -->
    <div class="card">
      <div class="card-title">
        ⚙️ Režim výběru úkolů
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="update_selection_mode">
        
        <div class="form-group">
          <label class="form-label">Jak se budou vybírat úkoly pro týmy?</label>
          <select name="task_selection_mode" id="selectionMode" onchange="updateSelectionFields()" class="form-select">
            <option value="all" <?=$station['task_selection_mode']==='all'?'selected':''?>>Všechny úkoly (týmy dostanou všechny úkoly)</option>
            <option value="random" <?=$station['task_selection_mode']==='random'?'selected':''?>>Náhodný výběr (každý tým dostane náhodné úkoly)</option>
            <option value="fixed" <?=$station['task_selection_mode']==='fixed'?'selected':''?>>Fixní výběr (vyberte konkrétní úkoly)</option>
          </select>
        </div>

        <div id="randomCountSection" style="display:<?=$station['task_selection_mode']==='random'?'block':'none'?>;">
          <div class="form-group">
            <label class="form-label">Počet náhodných úkolů</label>
            <input type="number" name="tasks_count" value="<?=$station['tasks_count']??''?>" min="1" class="form-input" placeholder="Např. 3">
            <div class="info-box">
              Kolik úkolů se má náhodně vybrat z celkového počtu úkolů
            </div>
          </div>
        </div>

        <div id="fixedTasksSection" style="display:<?=$station['task_selection_mode']==='fixed'?'block':'none'?>;">
          <div class="form-group">
            <label class="form-label">Vyberte fixní úkoly</label>
            <?php if ($total_tasks === 0): ?>
              <p class="text-gray-500">Nejprve vytvořte nějaké úkoly níže</p>
            <?php else: ?>
              <?php 
              // Load ALL tasks for selection (not paginated)
              $stmt = $pdo->prepare("SELECT * FROM station_tasks WHERE station_id = ? ORDER BY task_order ASC, id ASC");
              $stmt->execute([$station_id]);
              $all_tasks_for_selection = $stmt->fetchAll();
              foreach ($all_tasks_for_selection as $task): 
              ?>
              <label class="checkbox-label">
                <input type="checkbox" name="fixed_tasks[]" value="<?=$task['id']?>" 
                       <?=in_array($task['id'], $fixed_task_ids)?'checked':''?>>
                <span>
                  <strong>Úkol #<?=$task['task_order']?></strong>: <?=htmlspecialchars(substr($task['question'], 0, 60))?>...
                </span>
              </label>
              <?php endforeach; ?>
            <?php endif; ?>
            <div class="info-box">
              Týmy dostanou pouze vybrané úkoly v daném pořadí
            </div>
          </div>
        </div>

        <button type="submit" class="btn-primary">💾 Uložit režim výběru</button>
      </form>
    </div>

    <!-- Seznam úkolů -->
    <div class="card">
      <div class="card-title">
        📝 Úkoly stanoviště (<?=$total_tasks?>)
        <div style="margin-left: auto;">
          <button onclick="openAddModal()" class="btn-primary">+ Přidat úkol</button>
        </div>
      </div>

      <?php if ($total_tasks === 0): ?>
        <div class="text-center py-8 text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
          </svg>
          <p class="text-lg font-semibold mb-2">Zatím nemáte žádné úkoly</p>
          <p class="mb-4">Začněte přidáním prvního úkolu pro toto stanoviště</p>
          <button onclick="openAddModal()" class="btn-primary">+ Přidat první úkol</button>
        </div>
      <?php else: ?>
        <?php foreach ($tasks as $index => $task): 
          $quiz_options = json_decode($task['quiz_options'] ?? '[]', true);
          $quiz_correct_answers = json_decode($task['quiz_correct_answers'] ?? '[]', true);
        ?>
        <div class="task-item">
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-bold">
                  Úkol #<?=$task['task_order']?>
                </span>
                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">
                  🏆 <?=$task['reward_points']?> bodů
                </span>
                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-semibold">
                  <?php
                  $types = [
                    'text' => '📝 Text',
                    'quiz' => '✅ Kvíz',
                    'file_upload' => '📷 Soubor',
                    'mixed' => '🔀 Mix',
                    'creative' => '🎨 Kreativní'
                  ];
                  echo $types[$task['task_type']] ?? $task['task_type'];
                  ?>
                </span>
              </div>
              <h4 class="font-bold text-gray-800 mb-2"><?=htmlspecialchars($task['question'])?></h4>
              <?php if ($task['media_type'] !== 'none'): ?>
              <p class="text-sm text-gray-600 mb-1">
                🎬 Médium: <?=$task['media_type']?> 
                <?php if ($task['media_url']): ?>
                  - <a href="<?=htmlspecialchars($task['media_url'])?>" target="_blank" class="text-blue-600 hover:underline">Zobrazit</a>
                <?php endif; ?>
              </p>
              <?php endif; ?>
              <?php if ($task['correct_answer']): ?>
              <p class="text-sm text-gray-600 mb-1">✓ Správná odpověď: <code class="bg-gray-100 px-2 py-1 rounded"><?=htmlspecialchars($task['correct_answer'])?></code></p>
              <?php endif; ?>
              <?php if (!empty($quiz_options)): ?>
              <p class="text-sm text-gray-600 mb-1">📋 Možnosti: <?=implode(', ', array_map('htmlspecialchars', $quiz_options))?></p>
              <?php endif; ?>
              <?php if (!empty($quiz_correct_answers)): ?>
              <p class="text-sm text-green-600 mb-1">✅ Správné odpovědi: <?=implode(', ', array_map('htmlspecialchars', $quiz_correct_answers))?></p>
              <?php endif; ?>
              <?php if ($task['speed_bonus'] > 0): ?>
              <p class="text-sm text-green-600">⚡ Speed bonus: +<?=$task['speed_bonus']?> bodů</p>
              <?php endif; ?>
              <?php if ($task['penalty_wrong'] > 0): ?>
              <p class="text-sm text-red-600">⚠️ Penalizace: -<?=$task['penalty_wrong']?> bodů</p>
              <?php endif; ?>
            </div>
            <div class="flex gap-2">
              <button onclick='openEditModal(<?=htmlspecialchars(json_encode($task), ENT_QUOTES)?>)' class="btn-secondary">
                ✏️ Upravit
              </button>
              <form method="POST" onsubmit="return confirm('Opravdu chcete smazat tento úkol?')" style="display: inline;">
                <input type="hidden" name="action" value="delete_task">
                <input type="hidden" name="task_id" value="<?=$task['id']?>">
                <button type="submit" class="btn-danger">🗑️</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?id=<?=$station_id?>&page=<?=$page-1?>">← Předchozí</a>
          <?php endif; ?>
          
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
              <span class="active"><?=$i?></span>
            <?php else: ?>
              <a href="?id=<?=$station_id?>&page=<?=$i?>"><?=$i?></a>
            <?php endif; ?>
          <?php endfor; ?>
          
          <?php if ($page < $total_pages): ?>
            <a href="?id=<?=$station_id?>&page=<?=$page+1?>">Další →</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal pro přidání/úpravu úkolu -->
  <div id="taskModal" class="modal">
    <div class="modal-content">
      <h2 class="text-2xl font-bold mb-6" id="modalTitle">Přidat úkol</h2>
      <form method="POST" id="taskForm">
        <input type="hidden" name="action" id="formAction" value="add_task">
        <input type="hidden" name="task_id" id="taskId">

        <div class="form-group">
          <label class="form-label">Pořadí úkolu *</label>
          <input type="number" name="task_order" id="taskOrder" required class="form-input" min="0" placeholder="0">
          <div class="info-box">Určuje pořadí, ve kterém se úkoly zobrazí (0 = první)</div>
        </div>

        <div class="form-group">
          <label class="form-label">Otázka / Zadání úkolu *</label>
          <textarea name="question" id="taskQuestion" rows="3" required class="form-textarea" placeholder="Např.: Kolik písmen má slovo 'samet'?"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Typ úkolu *</label>
          <select name="task_type" id="taskType" onchange="updateTaskFields()" class="form-select">
            <option value="text">📝 Pouze textová odpověď</option>
            <option value="file_upload">📷 Pouze nahrání souboru</option>
            <option value="quiz">✅ Pouze kvíz</option>
            <option value="mixed">🔀 Text + soubor</option>
            <option value="creative">🎨 Kreativní (kvíz + soubor)</option>
          </select>
        </div>

        <div id="correctAnswerSection">
          <div class="form-group">
            <label class="form-label">Správná odpověď (pro automatické vyhodnocení)</label>
            <input type="text" name="correct_answer" id="taskCorrectAnswer" class="form-input" placeholder="Např.: 5">
          </div>
        </div>

        <div id="quizOptionsSection" style="display:none;">
          <div class="form-group">
            <label class="form-label">Možnosti kvízu (každá na nový řádek) *</label>
            <textarea name="quiz_options" id="taskQuizOptions" rows="4" class="form-textarea" placeholder="Možnost 1&#10;Možnost 2&#10;Možnost 3"></textarea>
            <div class="info-box">⚠️ Zadejte všechny možnosti odpovědí, které se zobrazí týmům</div>
          </div>
          
          <!-- NEW: Multiple correct answers -->
          <div class="form-group">
            <label class="form-label">Správné odpovědi (každá na nový řádek) *</label>
            <textarea name="quiz_correct_answers" id="taskQuizCorrectAnswers" rows="3" class="form-textarea" placeholder="Správná odpověď 1&#10;Správná odpověď 2"></textarea>
            <div class="info-box">✅ Zadejte všechny správné odpovědi. Mohou být v jakémkoliv pořadí!</div>
          </div>
          
          <!-- NEW: Custom feedback messages -->
          <div class="form-group">
            <label class="form-label">Zpráva při správné odpovědi</label>
            <textarea name="quiz_message_correct" id="taskQuizMessageCorrect" rows="2" class="form-textarea" placeholder="Např.: Výborně! Toto je správná odpověď, protože..."></textarea>
          </div>
          
          <div class="form-group">
            <label class="form-label">Zpráva při špatné odpovědi</label>
            <textarea name="quiz_message_incorrect" id="taskQuizMessageIncorrect" rows="2" class="form-textarea" placeholder="Např.: Bohužel ne. Správná odpověď je jiná, protože..."></textarea>
          </div>
        </div>

        <div id="fileUploadSection" style="display:none;">
          <label class="checkbox-label">
            <input type="checkbox" name="allow_file_upload" id="taskAllowFileUpload" value="1">
            <span>Povolit nahrávání souborů</span>
          </label>
        </div>

        <div class="form-group">
          <label class="form-label">Typ média</label>
          <select name="media_type" id="taskMediaType" class="form-select">
            <option value="none">Žádné</option>
            <option value="image">🖼️ Obrázek</option>
            <option value="video">🎥 Video</option>
            <option value="audio">🎵 Audio</option>
            <option value="youtube">📺 YouTube</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">URL média</label>
          <input type="url" name="media_url" id="taskMediaUrl" class="form-input" placeholder="https://...">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="form-group">
            <label class="form-label">Body za úkol *</label>
            <input type="number" name="reward_points" id="taskRewardPoints" required class="form-input" min="0" value="10">
          </div>
          <div class="form-group">
            <label class="form-label">Speed bonus</label>
            <input type="number" name="speed_bonus" id="taskSpeedBonus" class="form-input" min="0" value="0">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="form-group">
            <label class="form-label">Penalizace za chybu</label>
            <input type="number" name="penalty_wrong" id="taskPenaltyWrong" class="form-input" min="0" value="0">
          </div>
          <div class="form-group">
            <label class="form-label">Max body (škála)</label>
            <input type="number" name="max_points" id="taskMaxPoints" class="form-input" min="0" placeholder="Prázdné = bez škály">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Min body (škála)</label>
          <input type="number" name="min_points" id="taskMinPoints" class="form-input" min="0" value="0">
        </div>

        <div class="flex gap-3 mt-6">
          <button type="submit" class="btn-primary flex-1">💾 Uložit úkol</button>
          <button type="button" onclick="closeModal()" class="btn-secondary">Zrušit</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function updateSelectionFields() {
    const mode = document.getElementById('selectionMode').value;
    document.getElementById('randomCountSection').style.display = mode === 'random' ? 'block' : 'none';
    document.getElementById('fixedTasksSection').style.display = mode === 'fixed' ? 'block' : 'none';
  }

  function updateTaskFields() {
    const taskType = document.getElementById('taskType').value;
    const quizSection = document.getElementById('quizOptionsSection');
    const fileSection = document.getElementById('fileUploadSection');
    const correctAnswerSection = document.getElementById('correctAnswerSection');
    
    quizSection.style.display = 'none';
    fileSection.style.display = 'none';
    correctAnswerSection.style.display = 'block';
    
    if (taskType === 'quiz' || taskType === 'creative') {
      quizSection.style.display = 'block';
      correctAnswerSection.style.display = 'none';
    }
    
    if (taskType === 'mixed' || taskType === 'creative') {
      fileSection.style.display = 'block';
    }
    
    if (taskType === 'file_upload') {
      correctAnswerSection.style.display = 'none';
    }
  }

  function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Přidat úkol';
    document.getElementById('formAction').value = 'add_task';
    document.getElementById('taskForm').reset();
    document.getElementById('taskId').value = '';
    document.getElementById('taskOrder').value = <?=$total_tasks?>;
    updateTaskFields();
    document.getElementById('taskModal').classList.add('active');
  }

  function openEditModal(task) {
    document.getElementById('modalTitle').textContent = 'Upravit úkol';
    document.getElementById('formAction').value = 'update_task';
    document.getElementById('taskId').value = task.id;
    document.getElementById('taskOrder').value = task.task_order;
    document.getElementById('taskQuestion').value = task.question;
    document.getElementById('taskCorrectAnswer').value = task.correct_answer || '';
    document.getElementById('taskType').value = task.task_type;
    document.getElementById('taskMediaType').value = task.media_type;
    document.getElementById('taskMediaUrl').value = task.media_url || '';
    
    const quizOptions = task.quiz_options ? JSON.parse(task.quiz_options) : [];
    document.getElementById('taskQuizOptions').value = quizOptions.join('\n');
    
    // NEW: Load correct answers
    const quizCorrectAnswers = task.quiz_correct_answers ? JSON.parse(task.quiz_correct_answers) : [];
    document.getElementById('taskQuizCorrectAnswers').value = quizCorrectAnswers.join('\n');
    
    // NEW: Load custom messages
    const quizAnswerMessages = task.quiz_answer_messages ? JSON.parse(task.quiz_answer_messages) : {};
    document.getElementById('taskQuizMessageCorrect').value = quizAnswerMessages.correct || '';
    document.getElementById('taskQuizMessageIncorrect').value = quizAnswerMessages.incorrect || '';
    
    document.getElementById('taskRewardPoints').value = task.reward_points;
    document.getElementById('taskMaxPoints').value = task.max_points || '';
    document.getElementById('taskMinPoints').value = task.min_points || 0;
    document.getElementById('taskSpeedBonus').value = task.speed_bonus || 0;
    document.getElementById('taskPenaltyWrong').value = task.penalty_wrong || 0;
    document.getElementById('taskAllowFileUpload').checked = task.allow_file_upload == 1;
    
    updateTaskFields();
    document.getElementById('taskModal').classList.add('active');
  }

  function closeModal() {
    document.getElementById('taskModal').classList.remove('active');
  }

  // Close modal on outside click
  document.getElementById('taskModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeModal();
    }
  });

  updateSelectionFields();
  </script>
</body>
</html>