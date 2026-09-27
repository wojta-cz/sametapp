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

// Kontrola oprávnění
if ($user['role'] !== 'organizer' && $user['role'] !== 'admin') {
    http_response_code(403); 
    echo json_encode(['success' => false, 'message' => 'Nemáte oprávnění']); 
    exit;
}

$station_id = intval($_GET['station_id'] ?? 0);

if (!$station_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Chybí ID stanoviště']);
    exit;
}

$pdo = db();

try {
    // Získání týmů, které jsou aktuálně na stanovišti (status = pending)
    $stmt = $pdo->prepare("
        SELECT 
            r.id as result_id,
            r.created_at,
            t.id as team_id,
            t.name as team_name,
            t.code as team_code,
            t.points as team_points,
            TIMESTAMPDIFF(MINUTE, r.created_at, NOW()) as minutes_present
        FROM results r
        JOIN teams t ON r.team_id = t.id
        WHERE r.station_id = ? AND r.status = 'pending'
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([$station_id]);
    $current_teams = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'teams' => $current_teams,
        'count' => count($current_teams)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Chyba při načítání: ' . $e->getMessage()
    ]);
}