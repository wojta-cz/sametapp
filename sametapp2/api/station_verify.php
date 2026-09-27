<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/station_functions.php';
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

// Získání dat
$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? ''; // 'qr' nebo 'pin'
$value = $data['value'] ?? '';

if (empty($type) || empty($value)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Chybí typ nebo hodnota']);
    exit;
}

$pdo = db();
$station = null;

try {
    if ($type === 'qr') {
        // Ověření QR tokenu
        $station_id = verify_station_qr_token($value);
        if (!$station_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Neplatný nebo expirovaný QR kód']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND active = 1");
        $stmt->execute([$station_id]);
        $station = $stmt->fetch();
        
    } elseif ($type === 'pin') {
        // Ověření PIN kódu
        $station = find_station_by_pin($value);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Neplatný typ ověření']);
        exit;
    }
    
    if (!$station) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Stanoviště nebylo nalezeno']);
        exit;
    }
    

    $_SESSION['station_id'] = $station['id'];

    // Úspěch
    echo json_encode([
        'success' => true,
        'message' => 'Stanoviště bylo ověřeno',
        'station' => [
            'id' => $station['id'],
            'name' => $station['name'],
            'description' => $station['description'],
            'location' => $station['location'],
            'capacity' => $station['capacity'],
            'current_occupancy' => $station['current_occupancy'] ?? 0,
            'reward_points' => $station['reward_points'],
            'max_points' => $station['max_points'],
            'min_points' => $station['min_points'] ?? 0,
            'once_only' => $station['once_only'] ?? 0,
            'type' => $station['type']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Chyba při ověřování: ' . $e->getMessage()
    ]);
}