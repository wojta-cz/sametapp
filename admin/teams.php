<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = $_SESSION['user'];

if ($user['role'] !== 'admin') {
    header('Location: ../dashboard');
    exit;
}

$pdo = db();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_points') {
        $team_id = intval($_POST['team_id']);
        $points = intval($_POST['points']);
        $stmt = $pdo->prepare("UPDATE teams SET points = ? WHERE id = ?");
        $stmt->execute([$points, $team_id]);
        header('Location: teams?success=1');
        exit;
    } elseif ($_POST['action'] === 'delete_team') {
        $team_id = intval($_POST['team_id']);
        $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
        $stmt->execute([$team_id]);
        header('Location: teams?deleted=1');
        exit;
    }
}

// Get all teams with member count
$stmt = $pdo->query("
    SELECT t.*, COUNT(u.id) as member_count 
    FROM teams t 
    LEFT JOIN users u ON t.id = u.team_id 
    GROUP BY t.id 
    ORDER BY t.points DESC
");
$teams = $stmt->fetchAll();
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Správa týmů — Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <nav class="bg-white shadow-sm border-b mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <a href="./index" class="text-blue-600 hover:text-blue-800 mr-4">← Admin</a>
          <h1 class="text-xl font-bold text-gray-900">Správa týmů</h1>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      Změny byly úspěšně uloženy.
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      Tým byl smazán.
    </div>
    <?php endif; ?>

    <div class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Název týmu</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kód</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Členové</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Body</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vytvořeno</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akce</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php foreach ($teams as $t): ?>
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              <?=htmlspecialchars($t['name'])?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <code class="bg-gray-100 px-2 py-1 rounded"><?=htmlspecialchars($t['code'])?></code>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <?=$t['member_count']?> členů
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <form method="POST" class="flex items-center space-x-2">
                <input type="hidden" name="action" value="update_points">
                <input type="hidden" name="team_id" value="<?=$t['id']?>">
                <input type="number" name="points" value="<?=$t['points']?>" class="w-20 text-sm border-gray-300 rounded-md">
                <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs">Uložit</button>
              </form>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <?=date('d.m.Y', strtotime($t['created_at']))?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
              <a href="team_detail?id=<?=$t['id']?>" class="text-blue-600 hover:text-blue-800">Detail</a>
              <form method="POST" class="inline" onsubmit="return confirm('Opravdu smazat tým?')">
                <input type="hidden" name="action" value="delete_team">
                <input type="hidden" name="team_id" value="<?=$t['id']?>">
                <button type="submit" class="text-red-600 hover:text-red-800">Smazat</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>