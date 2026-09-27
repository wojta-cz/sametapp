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

// Získání team_id nebo team_code z požadavku
$team_id = intval($_GET['team_id'] ?? 0);
$team_code = trim($_GET['team_code'] ?? '');

if (!$team_id && !$team_code) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID týmu nebo kód týmu nebyl poskytnut']);
    exit;
}

$pdo = db();

try {
    // Získat informace o týmu podle ID nebo kódu
    if ($team_id) {
        $stmt = $pdo->prepare("
            SELECT t.*, s.name as current_station_name, s_assigned.name as assigned_station_name
            FROM teams t
            LEFT JOIN stations s ON t.current_station_id = s.id
            LEFT JOIN stations s_assigned ON t.assigned_station_id = s_assigned.id
            WHERE t.id = ?
        ");
        $stmt->execute([$team_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT t.*, s.name as current_station_name, s_assigned.name as assigned_station_name
            FROM teams t
            LEFT JOIN stations s ON t.current_station_id = s.id
            LEFT JOIN stations s_assigned ON t.assigned_station_id = s_assigned.id
            WHERE t.code = ?
        ");
        $stmt->execute([$team_code]);
    }
    
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$team) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tým nebyl nalezen']);
        exit;
    }
    
    // Získat členy týmu
    $stmt = $pdo->prepare("
        SELECT id, name, email, role, created_at
        FROM users
        WHERE team_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$team['id']]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Získat historii výsledků
    $stmt = $pdo->prepare("
        SELECT r.*, s.name as station_name, u.name as organizer_name
        FROM results r
        LEFT JOIN stations s ON r.station_id = s.id
        LEFT JOIN users u ON r.organizer_id = u.id
        WHERE r.team_id = ?
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$team['id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'team' => $team,
        'members' => $members,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Chyba při načítání dat: ' . $e->getMessage()
    ]);
}