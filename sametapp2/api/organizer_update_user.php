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
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$role = $data['role'] ?? 'player';

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID uživatele nebylo poskytnuto']);
    exit;
}

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Jméno uživatele nesmí být prázdné']);
    exit;
}

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email nesmí být prázdný']);
    exit;
}

if (!in_array($role, ['player', 'organizer', 'admin'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neplatná role']);
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();
    
    // Aktualizovat uživatele
    $stmt = $pdo->prepare("
        UPDATE users 
        SET name = ?, email = ?, role = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $email, $role, $user_id]);
    
    // Zalogovat akci
    $stmt = $pdo->prepare("
        INSERT INTO logs (user_id, action, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([
        $user['id'],
        "Upravil uživatele ID $user_id: jméno='$name', email='$email', role='$role'"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Uživatel byl úspěšně aktualizován'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Chyba při aktualizaci uživatele: ' . $e->getMessage()
    ]);
}