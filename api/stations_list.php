<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nejste přihlášeni']);
    exit;
}

$user = get_user_by_googleid($_SESSION['user']['google_id']);

// Check time lock for regular players
if (!can_access_stations($user['role'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Aplikace je časově uzamčena']);
    exit;
}

$pdo = db();

try {
    $page = intval($_GET['page'] ?? 0);
    $limit = intval($_GET['limit'] ?? 10);
    $offset = $page * $limit;
    
    // Get stations with pagination
    // NEW: Filter stations by start_time - only show stations that have started
    // NEW: Prioritize physical stations over virtual ones
    $stmt = $pdo->prepare("
        SELECT * FROM stations
        WHERE active=1
        AND (start_time IS NULL OR start_time <= NOW())
        ORDER BY 
            CASE WHEN type = 'physical' THEN 0 ELSE 1 END,
            id
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if there are more stations
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM stations 
        WHERE active=1
        AND (start_time IS NULL OR start_time <= NOW())
    ");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    $has_more = ($offset + $limit) < $total;
    
    echo json_encode([
        'success' => true,
        'stations' => $stations,
        'has_more' => $has_more,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}