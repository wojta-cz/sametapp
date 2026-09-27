<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
header('Content-Type: application/json');

// Kontrola přihlášení
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nejste přihlášeni']);
    exit;
}

$user = $_SESSION['user'];

// Kontrola oprávnění (pouze organizer a admin)
if ($user['role'] !== 'organizer' && $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nemáte oprávnění']);
    exit;
}

// Získání dat z požadavku
$data = json_decode(file_get_contents('php://input'), true);
$team_id = intval($data['team_id'] ?? 0);

if (!$team_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID týmu nebylo poskytnuto']);
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();
    
    // Získat informace o týmu před smazáním
    $stmt = $pdo->prepare("SELECT name, code FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$team) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tým nebyl nalezen']);
        exit;
    }
    
    // Odstranit všechny uživatele z týmu (nastavit team_id na NULL)
    $stmt = $pdo->prepare("UPDATE users SET team_id = NULL WHERE team_id = ?");
    $stmt->execute([$team_id]);
    
    // Smazat tým (CASCADE smaže i související záznamy)
    $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    
    // Zalogovat akci
    $stmt = $pdo->prepare("
        INSERT INTO logs (user_id, action, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([
        $user['id'],
        "Smazal tým ID $team_id ('{$team['name']}', kód: '{$team['code']}')"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tým byl úspěšně smazán'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Chyba při mazání týmu: ' . $e->getMessage()
    ]);
}