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

if (!$station_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neplatné ID stanoviště']);
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();
    
    // Snížit obsazenost stanoviště
    $stmt = $pdo->prepare("UPDATE stations SET current_occupancy = GREATEST(0, current_occupancy - 1) WHERE id = ?");
    $stmt->execute([$station_id]);
    
    // Vymazat aktuální stanoviště týmu
    $stmt = $pdo->prepare("UPDATE teams SET current_station_id = NULL, arrival_time = NULL WHERE id = ?");
    $stmt->execute([$team_id]);
    
    // Zkontrolovat režim přiřazování
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'assignment_mode'");
    $stmt->execute();
    $assignment_mode = $stmt->fetchColumn();
    
    $next_station = null;
    
    // Automaticky přiřadit další stanoviště v režimu přiřazených stanovišť
    if ($assignment_mode === 'assigned') {
        $stmt = $pdo->prepare("
            SELECT s.* 
            FROM stations s
            WHERE s.active = 1
            AND s.id NOT IN (
                SELECT station_id 
                FROM results 
                WHERE team_id = ? AND status = 'done'
            )
            AND (s.once_only = 0 OR s.id NOT IN (
                SELECT station_id 
                FROM results 
                WHERE team_id = ?
            ))
            AND (s.capacity = 0 OR s.current_occupancy < s.capacity)
            ORDER BY 
                CASE WHEN s.capacity = 0 THEN 999999 ELSE (s.capacity - s.current_occupancy) END DESC,
                s.current_occupancy ASC,
                RAND()
            LIMIT 1
        ");
        $stmt->execute([$team_id, $team_id]);
        $next_station = $stmt->fetch();
        
        if ($next_station) {
            $stmt = $pdo->prepare("UPDATE teams SET assigned_station_id = ? WHERE id = ?");
            $stmt->execute([$next_station['id'], $team_id]);
        }
    }
    
    $pdo->commit();
    
    $response = [
        'success' => true,
        'message' => 'Opustili jste stanoviště',
        'assignment_mode' => $assignment_mode
    ];
    
    if ($next_station) {
        $response['next_station'] = [
            'id' => $next_station['id'],
            'name' => $next_station['name'],
            'description' => $next_station['description'],
            'location' => $next_station['location']
        ];
        $response['auto_assigned'] = true;
    } else if ($assignment_mode === 'assigned') {
        $response['all_completed'] = true;
        $response['message'] = 'Opustili jste stanoviště. Dokončili jste všechna dostupná stanoviště.';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()]);
}