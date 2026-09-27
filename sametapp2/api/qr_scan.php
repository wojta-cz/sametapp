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
    echo json_encode(['success' => false, 'message' => 'Nemáte oprávnění pro skenování QR kódů']); 
    exit;
}

// Získání tokenu z požadavku
$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';
$assignedbypass = $data['bypassassigned'] ?? false;
$station_id = intval($data['station_id'] ?? 0);

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token nebyl poskytnut']);
    exit;
}

if (!$station_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID stanoviště nebylo poskytnuto']);
    exit;
}

// Ověření QR tokenu (platnost 5 minut)
$team_id = verify_qr_token($token, 300);

if (!$team_id) { 
    http_response_code(400); 
    echo json_encode(['success' => false, 'message' => 'Neplatný nebo expirovaný QR kód']); 
    exit; 
}

$pdo = db();

try {
    $pdo->beginTransaction();
    
    // Získání informací o týmu včetně přiřazeného stanoviště
    $stmt = $pdo->prepare("
        SELECT t.*, s_assigned.name as assigned_station_name
        FROM teams t
        LEFT JOIN stations s_assigned ON t.assigned_station_id = s_assigned.id
        WHERE t.id = ?
    ");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch();
    
    if (!$team) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tým nebyl nalezen']);
        exit;
    }
    
    // Získání informací o stanovišti včetně poznámek pro organizátory
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND active = 1");
    $stmt->execute([$station_id]);
    $station = $stmt->fetch();
    
    if (!$station) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Stanoviště nebylo nalezeno nebo není aktivní']);
        exit;
    }
    
    // KONTROLA: Zda má tým přiřazené jiné stanoviště
    $wrong_station_warning = null;
    if ($team['assigned_station_id'] && $team['assigned_station_id'] != $station_id && !$assignedbypass) {
        echo json_encode([
            'success' => 'warning',
            'warning' => [
                'has_warning' => true,
                'message' => 'UPOZORNĚNÍ: Tento tým má přiřazené jiné stanoviště!',
                'assigned_station' => $team['assigned_station_name'],
                'current_station' => $station['name']
            ]
        ]);
        exit;
    }
    
    // Kontrola, zda už tým není na tomto stanovišti (pending status)
    $stmt = $pdo->prepare("
        SELECT id, status FROM results 
        WHERE team_id = ? AND station_id = ? AND status = 'pending'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$team_id, $station_id]);
    $existing_pending = $stmt->fetch();
    
    if ($existing_pending) {
        // Zaznamenat QR scan i když už je pending (pro virtuální stanoviště)
        $stmt = $pdo->prepare("
            INSERT INTO team_qr_scans (team_id, station_id, scanned_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$team_id, $station_id]);
        
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Tým je již přítomen na tomto stanovišti',
            'team' => $team['name'],
            'team_id' => $team_id,
            'team_code' => $team['code'],
            'team_points' => $team['points'],
            'result_id' => $existing_pending['id'],
            'already_present' => true,
            'wrong_station_warning' => $wrong_station_warning,
            'station' => [
                'id' => $station['id'],
                'name' => $station['name'],
                'max_points' => $station['max_points'],
                'min_points' => $station['min_points'] ?? 0,
                'reward_points' => $station['reward_points'],
                'once_only' => $station['once_only'] ?? 0,
                'organizer_notes' => $station['organizer_notes'] ?? null
            ]
        ]);
        exit;
    }
    
    // Kontrola, zda stanoviště umožňuje pouze jednu účast
    if ($station['once_only']) {
        $stmt = $pdo->prepare("
            SELECT id FROM results 
            WHERE team_id = ? AND station_id = ? AND status IN ('done', 'failed')
            LIMIT 1
        ");
        $stmt->execute([$team_id, $station_id]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Tým již na tomto stanovišti byl. Stanoviště umožňuje pouze jednu účast.'
            ]);
            exit;
        }
    }
    
    // Vytvořit nový záznam pending -> tým je nyní přítomen na stanovišti
    $stmt = $pdo->prepare("
        INSERT INTO results (team_id, station_id, organizer_id, status, created_at) 
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$team_id, $station_id, $user['id']]);
    $result_id = $pdo->lastInsertId();
    
    // Zaznamenat QR scan do team_qr_scans (důležité pro virtuální stanoviště)
    $stmt = $pdo->prepare("
        INSERT INTO team_qr_scans (team_id, station_id, scanned_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$team_id, $station_id]);
    
    // Aktualizovat current_station_id týmu
    $stmt = $pdo->prepare("UPDATE teams SET current_station_id = ? WHERE id = ?");
    $stmt->execute([$station_id, $team_id]);
    
    // Zvýšit obsazenost stanoviště
    $stmt = $pdo->prepare("UPDATE stations SET current_occupancy = current_occupancy + 1 WHERE id = ?");
    $stmt->execute([$station_id]);
    
    $pdo->commit();
    
    // Úspěšná odpověď s informacemi o týmu a stanovišti
    echo json_encode([
        'success' => true,
        'message' => 'Tým byl úspěšně zaregistrován na stanovišti',
        'team' => $team['name'],
        'team_id' => $team_id,
        'team_code' => $team['code'],
        'team_points' => $team['points'],
        'result_id' => $result_id,
        'wrong_station_warning' => $wrong_station_warning,
        'station' => [
            'id' => $station['id'],
            'name' => $station['name'],
            'max_points' => $station['max_points'],
            'min_points' => $station['min_points'] ?? 0,
            'reward_points' => $station['reward_points'],
            'once_only' => $station['once_only'] ?? 0,
            'organizer_notes' => $station['organizer_notes'] ?? null
        ]
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Chyba při ukládání záznamu: ' . $e->getMessage()
    ]);
}