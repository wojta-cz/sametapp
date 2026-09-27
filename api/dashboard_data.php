<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/assignment_functions.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user'])) { 
    http_response_code(401); 
    echo json_encode(['success' => false, 'message' => 'Nejste přihlášeni']); 
    exit; 
}

$user = $_SESSION['user'];

if ($user['role'] !== 'admin' && $user['role'] !== 'organizer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nemáte oprávnění']);
    exit;
}

$pdo = db();

try {
    // Statistiky
    $stats = [
        'total_teams' => $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn(),
        'active_teams' => $pdo->query("SELECT COUNT(*) FROM teams WHERE current_station_id IS NOT NULL")->fetchColumn(),
        'waiting_teams' => $pdo->query("SELECT COUNT(*) FROM teams WHERE assigned_station_id IS NULL")->fetchColumn(),
        'completed_tasks' => $pdo->query("SELECT COUNT(*) FROM results WHERE status = 'done'")->fetchColumn()
    ];
    
    // Stanoviště s týmy
    $stmt = $pdo->query("
        SELECT 
            s.*,
            GROUP_CONCAT(
                CONCAT(t.id, ':', t.name, ':', t.points)
                SEPARATOR '||'
            ) as teams_data
        FROM stations s
        LEFT JOIN teams t ON t.current_station_id = s.id
        WHERE s.active = 1
        GROUP BY s.id
        ORDER BY s.id
    ");
    
    $stations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $teams = [];
        if ($row['teams_data']) {
            foreach (explode('||', $row['teams_data']) as $team_data) {
                if ($team_data) {
                    list($id, $name, $points) = explode(':', $team_data);
                    $teams[] = [
                        'id' => intval($id),
                        'name' => $name,
                        'points' => intval($points)
                    ];
                }
            }
        }
        
        $stations[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'description' => $row['description'],
            'type' => $row['type'],
            'location' => $row['location'],
            'capacity' => intval($row['capacity']),
            'current_occupancy' => intval($row['current_occupancy']),
            'reward_points' => intval($row['reward_points']),
            'teams' => $teams
        ];
    }
    
    // Týmy bez přiřazeného stanoviště
    $stmt = $pdo->query("
        SELECT 
            t.*,
            COUNT(DISTINCT r.station_id) as completed_stations
        FROM teams t
        LEFT JOIN results r ON t.id = r.team_id AND r.status = 'done'
        WHERE t.assigned_station_id IS NULL
        GROUP BY t.id
        ORDER BY t.points DESC
    ");
    
    $unassigned_teams = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unassigned_teams[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'code' => $row['code'],
            'points' => intval($row['points']),
            'completed_stations' => intval($row['completed_stations'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'statistics' => $stats,
        'stations' => $stations,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Chyba při načítání dat: ' . $e->getMessage()
    ]);
}