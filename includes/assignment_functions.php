<?php
require_once __DIR__ . '/db.php';

/**
 * Získá režim přiřazování stanovišť
 */
function get_assignment_mode() {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'assignment_mode'");
    $stmt->execute();
    return $stmt->fetchColumn() ?: 'free';
}

/**
 * Najde nejlepší dostupné stanoviště pro tým
 * UPDATED: Prioritize physical stations over virtual ones
 */
function find_best_available_station($team_id) {
    $pdo = db();
    
    // Najít stanoviště s nejnižší obsazeností, která tým ještě neabsolvoval
    // NEW: Prioritize physical stations (type = 'physical') over virtual ones
    // NEW: Filter out stations that haven't started yet (start_time)
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM stations s
        WHERE s.active = 1
        AND (s.start_time IS NULL OR s.start_time <= NOW())
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
        AND (s.capacity = 0 OR s.current_occupancy < s.capacity)
        ORDER BY 
            CASE WHEN s.type = 'physical' THEN 0 ELSE 1 END,
            CASE WHEN s.capacity = 0 THEN 999999 ELSE (s.capacity - s.current_occupancy) END DESC,
            s.current_occupancy ASC,
            RAND()
        LIMIT 1
    ");
    $stmt->execute([$team_id, $team_id]);
    return $stmt->fetch();
}

/**
 * Přiřadí stanoviště týmu
 */
function assign_station_to_team($team_id, $station_id) {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE teams SET assigned_station_id = ? WHERE id = ?");
    return $stmt->execute([$station_id, $team_id]);
}

/**
 * Aktualizuje obsazenost stanoviště
 */
function update_station_occupancy($station_id, $increment = true) {
    $pdo = db();
    if ($increment) {
        $stmt = $pdo->prepare("UPDATE stations SET current_occupancy = current_occupancy + 1 WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE stations SET current_occupancy = GREATEST(0, current_occupancy - 1) WHERE id = ?");
    }
    return $stmt->execute([$station_id]);
}

/**
 * Zkontroluje, zda má tým přístup ke stanovišti
 * UPDATED: Check if station has started (start_time)
 */
function can_team_access_station($team_id, $station_id) {
    $pdo = db();
    
    // Získat stanoviště
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND active = 1");
    $stmt->execute([$station_id]);
    $station = $stmt->fetch();
    
    if (!$station) {
        return ['allowed' => false, 'reason' => 'Stanoviště neexistuje nebo není aktivní'];
    }
    
    // NEW: Check if station has started
    if ($station['start_time'] && strtotime($station['start_time']) > time()) {
        $start_formatted = date('d.m.Y H:i', strtotime($station['start_time']));
        return ['allowed' => false, 'reason' => 'Stanoviště bude dostupné od ' . $start_formatted];
    }
    
    // Zkontrolovat kapacitu
    if ($station['capacity'] > 0 && $station['current_occupancy'] >= $station['capacity']) {
        return ['allowed' => false, 'reason' => 'Stanoviště je plné'];
    }
    
    // Zkontrolovat, zda tým už absolvoval (pokud je once_only)
    if ($station['once_only']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM results WHERE team_id = ? AND station_id = ?");
        $stmt->execute([$team_id, $station_id]);
        if ($stmt->fetchColumn() > 0) {
            return ['allowed' => false, 'reason' => 'Toto stanoviště lze absolvovat pouze jednou'];
        }
    }
    
    // Zkontrolovat režim přiřazování
    $assignment_mode = get_assignment_mode();
    if ($assignment_mode === 'assigned') {
        $stmt = $pdo->prepare("SELECT assigned_station_id FROM teams WHERE id = ?");
        $stmt->execute([$team_id]);
        $assigned = $stmt->fetchColumn();
        
        if ($assigned != $station_id) {
            return ['allowed' => false, 'reason' => 'Toto stanoviště vám není přiřazeno'];
        }
    }
    
    return ['allowed' => true];
}

/**
 * Získá statistiky přiřazování
 */
function get_assignment_statistics() {
    $pdo = db();
    
    // Počet týmů na jednotlivých stanovištích
    $stmt = $pdo->query("
        SELECT 
            s.id,
            s.name,
            s.capacity,
            s.current_occupancy,
            COUNT(DISTINCT t.id) as teams_count
        FROM stations s
        LEFT JOIN teams t ON t.current_station_id = s.id
        WHERE s.active = 1
        GROUP BY s.id
        ORDER BY s.current_occupancy DESC
    ");
    
    return $stmt->fetchAll();
}

/**
 * Přerozdělí týmy rovnoměrně mezi stanoviště
 */
function rebalance_teams() {
    $pdo = db();
    
    try {
        $pdo->beginTransaction();
        
        // Získat všechny týmy bez přiřazeného stanoviště
        $stmt = $pdo->query("
            SELECT id FROM teams 
            WHERE assigned_station_id IS NULL 
            OR assigned_station_id NOT IN (SELECT id FROM stations WHERE active = 1)
        ");
        $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $assigned_count = 0;
        foreach ($teams as $team_id) {
            $station = find_best_available_station($team_id);
            if ($station) {
                assign_station_to_team($team_id, $station['id']);
                $assigned_count++;
            }
        }
        
        $pdo->commit();
        return ['success' => true, 'assigned' => $assigned_count];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}