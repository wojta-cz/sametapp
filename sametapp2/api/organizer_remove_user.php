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
$user_id = intval($data['user_id'] ?? 0);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID uživatele nebylo poskytnuto']);
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();
    
    // Získat informace o uživateli před odstraněním
    $stmt = $pdo->prepare("SELECT name, email, team_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Uživatel nebyl nalezen']);
        exit;
    }
    
    // Odstranit uživatele z týmu (nastavit team_id na NULL)
    $stmt = $pdo->prepare("UPDATE users SET team_id = NULL WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Zalogovat akci
    $stmt = $pdo->prepare("
        INSERT INTO logs (user_id, action, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([
        $user['id'],
        "Odstranil uživatele ID $user_id ('{$target_user['name']}') z týmu ID {$target_user['team_id']}"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Uživatel byl úspěšně odstraněn z týmu'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Chyba při odstraňování uživatele: ' . $e->getMessage()
    ]);
}