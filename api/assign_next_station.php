<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/station_functions.php';
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

// Získat team_id buď z uživatele nebo z POST dat (pro admin)
$data = json_decode(file_get_contents('php://input'), true);
$team_id = $data['team_id'] ?? $user['team_id'] ?? null;

if (!$team_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nejste členem žádného týmu']);
    exit;
}

$pdo = db();

// Zkontrolovat režim přiřazování
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'assignment_mode'");
$stmt->execute();
$mode = $stmt->fetchColumn();

if ($mode !== 'assigned') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Automatické přiřazování není aktivní']);
    exit;
}

// Získat aktuální tým
$stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch();

if (!$team) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Tým nebyl nalezen']);
    exit;
}

// Najít stanoviště, která tým ještě neabsolvoval
// Najít stanoviště, která tým ještě neabsolvoval, prioritně fyzická
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM stations s
    WHERE s.active = 1
      AND s.id NOT IN (
          SELECT station_id 
          FROM results 
          WHERE team_id = ? 
            AND status = 'done'
            AND status != 'failed'
      )
      AND (s.once_only = 0 OR s.id NOT IN (
          SELECT station_id 
          FROM results 
          WHERE team_id = ?
            AND status != 'failed'
      ))
      AND (s.capacity = 0 OR s.current_occupancy < s.capacity)
    ORDER BY 
        CASE WHEN s.type = 'physical' THEN 0 ELSE 1 END,  -- fyzická stanoviště mají přednost
        CASE WHEN s.capacity = 0 THEN 999999 ELSE (s.capacity - s.current_occupancy) END DESC,
        s.current_occupancy ASC,
        RAND()
    LIMIT 1;
");

$stmt->execute([$team_id, $team_id]);
$next_station = $stmt->fetch();

if (!$next_station) {
    echo json_encode([
        'success' => false,
        'message' => 'Žádné dostupné stanoviště',
        'all_completed' => true
    ]);
    exit;
}

// Aktualizovat přiřazené stanoviště týmu
$stmt = $pdo->prepare("UPDATE teams SET assigned_station_id = ? WHERE id = ?");
$stmt->execute([$next_station['id'], $team_id]);

echo json_encode([
    'success' => true,
    'message' => 'Stanoviště bylo úspěšně přiřazeno',
    'station' => [
        'id' => $next_station['id'],
        'name' => $next_station['name'],
        'description' => $next_station['description'],
        'location' => $next_station['location'],
        'type' => $next_station['type'],
        'reward_points' => $next_station['reward_points']
    ]
]);