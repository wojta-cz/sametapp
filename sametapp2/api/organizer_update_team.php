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
$name = trim($data['name'] ?? '');
$points = intval($data['points'] ?? 0);

if (!$team_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID týmu nebylo poskytnuto']);
    exit;
}

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Název týmu nesmí být prázdný']);
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();
    
    // Aktualizovat tým
    $stmt = $pdo->prepare("
        UPDATE teams 
        SET name = ?, points = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $points, $team_id]);
    
    // Zalogovat akci
    $stmt = $pdo->prepare("
        INSERT INTO logs (user_id, action, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([
        $user['id'],
        "Upravil tým ID $team_id: název='$name', body=$points"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tým byl úspěšně aktualizován'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Chyba při aktualizaci týmu: ' . $e->getMessage()
    ]);
}