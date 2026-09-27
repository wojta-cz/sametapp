<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
header('Content-Type: application/json');

// Kontrola přihlášení
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

// Získání QR tokenu z požadavku
$data = json_decode(file_get_contents('php://input'), true);
$qr_token = $data['qr_token'] ?? '';

if (empty($qr_token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'QR kód nebyl poskytnut']);
    exit;
}

$pdo = db();

try {
    // Dekódovat QR token stanoviště
    // Formát: STATION|{station_id}|{timestamp}|{hash}
    $data = base64_decode(urldecode($qr_token));;
    error_log($data);
    if (!$data) {
        throw new Exception('Neplatný QR kód!1');
    };
    
    $parts = explode('|', $data);
    if (count($parts) < 3) {
        throw new Exception('Neplatný QR kód!2');
    };
    
    $prefix = $parts[0];
    $station_id = $parts[1];
    $hmac = $parts[2];
    error_log($hmac);
    
    if ($prefix !== 'STATION') {
        throw new Exception('Neplatný QR kód!3');
    };
    
    $key = getenv('JWT_SECRET') ?: 'secret_key';
    $expected = hash_hmac('sha256', 'STATION|' . $station_id, $key);

    error_log($expected);
    
    //if (!hash_equals($expected, $hmac)) {
    //    throw new Exception('Neplatný QR kód!4' );
    //};

    // Získat informace o stanovišti
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND active = 1");
    $stmt->execute([$station_id]);
    $station = $stmt->fetch();

    if (!$station) {
        throw new Exception('Stanoviště nebylo nalezeno nebo není aktivní');
    }

    // Zkontrolovat, zda je stanoviště virtuální a vyžaduje fyzický QR
    if ($station['type'] !== 'virtual' || !$station['require_physical_qr']) {
        throw new Exception('Toto stanoviště nevyžaduje skenování QR kódu týmem');
    }

    $pdo->beginTransaction();

    // Zaznamenat scan do team_qr_scans
    $stmt = $pdo->prepare("
        INSERT INTO team_qr_scans (team_id, station_id, scanned_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$team_id, $station_id]);

    $pdo->commit();

    // Úspěšná odpověď
    echo json_encode([
        'success' => true,
        'message' => 'QR kód byl úspěšně naskenován',
        'station' => [
            'id' => $station['id'],
            'name' => $station['name'],
            'description' => $station['description']
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}