<?php
require_once __DIR__ . '/../includes/auth.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user'])) { 
    http_response_code(401); 
    echo json_encode(['success' => false, 'message' => 'Nejste přihlášeni']); 
    exit; 
}

$user = $_SESSION['user'];
$team_id = $user['team_id'] ?? null;

if (!$team_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nejste členem žádného týmu']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$station_id = intval($data['station_id'] ?? 0);
$action = $data['action'] ?? ''; // 'arrive' nebo 'leave'

if (!$station_id || !in_array($action, ['arrive', 'leave'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neplatné parametry']);
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();
    
    if ($action === 'arrive') {
        // Zvýšit obsazenost
        $stmt = $pdo->prepare("UPDATE stations SET current_occupancy = current_occupancy + 1 WHERE id = ?");
        $stmt->execute([$station_id]);
        
        // Aktualizovat aktuální stanoviště týmu
        $stmt = $pdo->prepare("UPDATE teams SET current_station_id = ?, arrival_time = NOW() WHERE id = ?");
        $stmt->execute([$station_id, $team_id]);
        
    } else if ($action === 'leave') {
        // Snížit obsazenost
        $stmt = $pdo->prepare("UPDATE stations SET current_occupancy = GREATEST(0, current_occupancy - 1) WHERE id = ?");
        $stmt->execute([$station_id]);
        
        // Vymazat aktuální stanoviště týmu
        $stmt = $pdo->prepare("UPDATE teams SET current_station_id = NULL, arrival_time = NULL WHERE id = ?");
        $stmt->execute([$team_id]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Obsazenost byla aktualizována']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Chyba při aktualizaci: ' . $e->getMessage()]);
}