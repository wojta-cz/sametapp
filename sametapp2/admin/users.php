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

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_role') {
        $user_id = intval($_POST['user_id']);
        $new_role = $_POST['role'];
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
        header('Location: users?success=1');
        exit;
    }
}

// Get all users
$stmt = $pdo->query("SELECT u.*, t.name as team_name FROM users u LEFT JOIN teams t ON u.team_id = t.id ORDER BY u.created_at DESC");
$users = $stmt->fetchAll();
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Správa uživatelů — Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <nav class="bg-white shadow-sm border-b mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <a href="./index" class="text-blue-600 hover:text-blue-800 mr-4">← Admin</a>
          <h1 class="text-xl font-bold text-gray-900">Správa uživatelů</h1>
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

    <div class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jméno</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tým</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registrace</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akce</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              <?=htmlspecialchars($u['name'])?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <?=htmlspecialchars($u['email'])?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <form method="POST" class="inline">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="user_id" value="<?=$u['id']?>">
                <select name="role" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md">
                  <option value="player" <?=$u['role']==='player'?'selected':''?>>Hráč</option>
                  <option value="organizer" <?=$u['role']==='organizer'?'selected':''?>>Pořadatel</option>
                  <option value="admin" <?=$u['role']==='admin'?'selected':''?>>Admin</option>
                </select>
              </form>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <?=$u['team_name'] ? htmlspecialchars($u['team_name']) : '-'?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <?=date('d.m.Y H:i', strtotime($u['created_at']))?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <a href="?view=<?=$u['id']?>" class="text-blue-600 hover:text-blue-800">Detail</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>