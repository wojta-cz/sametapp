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
$result_id = intval($data['result_id'] ?? 0);
$status = $data['status'] ?? 'pending'; // 'done', 'failed'
$points = intval($data['points'] ?? 0);
$note = trim($data['note'] ?? '');

if (!$result_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Chybí ID výsledku']);
    exit;
}

// Validace statusu
if (!in_array($status, ['done', 'failed'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neplatný status']);
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();
    
    // Získání existujícího záznamu
    $stmt = $pdo->prepare("
        SELECT r.*, s.max_points, s.min_points, s.reward_points, t.points as team_points
        FROM results r
        JOIN stations s ON r.station_id = s.id
        JOIN teams t ON r.team_id = t.id
        WHERE r.id = ?
    ");
    $stmt->execute([$result_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Výsledek nebyl nalezen']);
        exit;
    }
    
    // Kontrola, zda už není dokončeno
    if ($result['status'] !== 'pending') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tento výsledek již byl vyhodnocen']);
        exit;
    }
    
    // Validace bodů podle škály stanoviště
    if ($status === 'done') {
        $max_points = $result['max_points'] ?? $result['reward_points'];
        $min_points = $result['min_points'] ?? 0;
        
        if ($points < $min_points || $points > $max_points) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => "Body musí být v rozmezí $min_points - $max_points"
            ]);
            exit;
        }
    } else {
        // failed = 0 bodů
        $points = 0;
    }
    
    // Aktualizace výsledku
    $stmt = $pdo->prepare("
        UPDATE results 
        SET status = ?, points = ?, note = ?, organizer_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$status, $points, $note, $user['id'], $result_id]);
    
    // Přičtení bodů týmu (pokud done)
    if ($status === 'done' && $points > 0) {
        $stmt = $pdo->prepare("UPDATE teams SET points = points + ? WHERE id = ?");
        $stmt->execute([$points, $result['team_id']]);
    }
    
    // Vymazání current_station_id týmu
    $stmt = $pdo->prepare("UPDATE teams SET current_station_id = NULL, assigned_station_id = NULL WHERE id = ?");
    $stmt->execute([$result['team_id']]);
    
    // Snížení obsazenosti stanoviště
    $stmt = $pdo->prepare("
        UPDATE stations 
        SET current_occupancy = GREATEST(0, current_occupancy - 1) 
        WHERE id = ?
    ");
    $stmt->execute([$result['station_id']]);
    
    $pdo->commit();
    
    // Získání aktualizovaných informací o týmu
    $team_details = get_team_details($result['team_id']);

    if ($status == "failed") {
        $stmt = $pdo->prepare("DELETE FROM results WHERE id = ?");
        $stmt->execute([$result_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Hodnocení bylo úspěšně uloženo',
        'result_id' => $result_id,
        'points_awarded' => $points,
        'team' => $team_details
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Chyba při ukládání: ' . $e->getMessage()
    ]);
}