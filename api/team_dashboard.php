<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/assignment_functions.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user'])) { 
    http_response_code(401); 
    echo json_encode(['success' => false, 'message' => 'Nejste přihlášeni']); 
    exit; 
}

$user = $_SESSION['user'];
$user_data = get_user_by_googleid($user['google_id']);

// Check time lock for regular players
if (!can_access_stations($user_data['role'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Aplikace je časově uzamčena']);
    exit;
}

// Kontrola, zda má uživatel tým
if (!$user['team_id']) {
    echo json_encode([
        'success' => true,
        'has_team' => false,
        'message' => 'Nejste členem žádného týmu'
    ]);
    exit;
}

$pdo = db();

try {
    // Získání informací o týmu
    $stmt = $pdo->prepare("
        SELECT t.*, 
           s_current.name as current_station_name,
           s_assigned.name as assigned_station_name,
           s_assigned.id as assigned_station_id,
           s_assigned.location as assigned_station_location
        FROM teams t
        LEFT JOIN stations s_current ON t.current_station_id = s_current.id
        LEFT JOIN stations s_assigned ON t.assigned_station_id = s_assigned.id
        WHERE t.id = ?");
    $stmt->execute([$user['team_id']]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$team) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tým nebyl nalezen']);
        exit;
    }
    
    // Počet dokončených stanovišť
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT station_id) as completed_count
        FROM results 
        WHERE team_id = ? AND status = 'done'
    ");
    $stmt->execute([$user['team_id']]);
    $completed = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Celkový počet aktivních stanovišť
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM stations WHERE active = 1");
    $total_stations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Kontrola, zda má tým všechna stanoviště hotová
    $all_completed = ($completed['completed_count'] >= $total_stations);
    
    // Aktuální stav týmu
    $status = 'idle'; // idle, assigned, working, completed
    $status_message = '';
    
    if ($all_completed) {
        $status = 'completed';
        $status_message = 'Gratulujeme! Máte všechna stanoviště hotová! 🎉';
    } elseif ($team['current_station_id']) {
        $status = 'working';
        $status_message = 'Pracujete na stanovišti: ' . $team['current_station_name'];
    } elseif ($team['assigned_station_id']) {
        $status = 'assigned';
        $status_message = 'Máte přiřazené stanoviště: ' . $team['assigned_station_name'];
    } else {
        $status = 'idle';
        $status_message = 'Vyberte si nové stanoviště';
    }
    
    // Seznam dostupných stanovišť (které ještě neabsolvovali)
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.description, s.location, s.reward_points, s.capacity, s.current_occupancy
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
        ORDER BY s.id
    ");
    $stmt->execute([$user['team_id'], $user['team_id']]);
    $available_stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Historie výsledků
    $stmt = $pdo->prepare("
        SELECT r.*, s.name as station_name, s.reward_points as max_points
        FROM results r
        LEFT JOIN stations s ON r.station_id = s.id
        WHERE r.team_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user['team_id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'has_team' => true,
        'team' => [
            'id' => $team['id'],
            'name' => $team['name'],
            'code' => $team['code'],
            'points' => $team['points']
        ],
        'status' => $status,
        'status_message' => $status_message,
        'current_station' => $team['current_station_id'] ? [
            'id' => $team['current_station_id'],
            'name' => $team['current_station_name']
        ] : null,
        'assigned_station' => $team['assigned_station_id'] ? [
            'id' => $team['assigned_station_id'],
            'name' => $team['assigned_station_name'],
            'location' => $team['assigned_station_location']
        ] : null,   
        'progress' => [
            'completed' => $completed['completed_count'],
            'total' => $total_stations,
            'percentage' => $total_stations > 0 ? round(($completed['completed_count'] / $total_stations) * 100) : 0
        ],
        'available_stations' => $available_stations,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Chyba při načítání dat: ' . $e->getMessage()
    ]);
}