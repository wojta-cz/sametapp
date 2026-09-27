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

if (!$user['team_id']) {
    echo json_encode([
        'success' => true,
        'has_team' => false
    ]);
    exit;
}

$pdo = db();

try {
    // Rychlá kontrola stavu týmu
    $stmt = $pdo->prepare("
        SELECT 
            t.current_station_id,
            t.assigned_station_id,
            (SELECT COUNT(DISTINCT station_id) FROM results WHERE team_id = t.id AND status = 'done') as completed_count,
            (SELECT COUNT(*) FROM stations WHERE active = 1) as total_stations
        FROM teams t
        WHERE t.id = ?
    ");
    $stmt->execute([$user['team_id']]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $all_completed = ($status['completed_count'] >= $status['total_stations']);
    
    echo json_encode([
        'success' => true,
        'has_team' => true,
        'is_working' => !empty($status['current_station_id']),
        'has_assigned' => !empty($status['assigned_station_id']),
        'all_completed' => $all_completed,
        'progress' => [
            'completed' => $status['completed_count'],
            'total' => $status['total_stations']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Chyba: ' . $e->getMessage()
    ]);
}