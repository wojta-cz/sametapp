<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/virtual_station_functions.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nejste přihlášeni']);
    exit;
}

$user = $_SESSION['user'];
$team_id = $user['team_id'] ?? null;

if (!$team_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nejste členem žádného týmu']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$station_id = intval($data['station_id'] ?? 0);
$hint_index = intval($data['hint_index'] ?? -1);

if (!$station_id || $hint_index < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Neplatné parametry']);
    exit;
}

try {
    $result = use_hint($station_id, $team_id, $hint_index);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'hint_text' => $result['hint_text'],
            'points_deducted' => $result['points_cost'],
            'message' => 'Nápověda odhalena! Odečteno ' . $result['points_cost'] . ' bodů.'
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Chyba serveru: ' . $e->getMessage()
    ]);
}