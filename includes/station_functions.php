<?php
require_once __DIR__ . '/db.php';

/**
 * Vygeneruje QR token pro stanoviště
 */
function generate_station_qr_token($station_id) {
    $payload = 'STATION|' . $station_id . '|' . time();
    $key = getenv('JWT_SECRET') ?: 'secret_key';
    $hmac = hash_hmac('sha256', $payload, $key);
    return base64_encode($payload . '|' . $hmac);
}

function generate_virtual_station_qr_token($station_id) {
    $payload = 'STATION|' . $station_id;
    $key = getenv('JWT_SECRET') ?: 'secret_key';
    $hmac = hash_hmac('sha256', $payload, $key);
    return urlencode(base64_encode($payload . '|' . $hmac));
}

/**
 * Ověří QR token stanoviště
 */
function verify_station_qr_token($token, $max_age = 86400) { // 24 hodin platnost
    $data = base64_decode($token);
    if (!$data) return false;
    
    $parts = explode('|', $data);
    if (count($parts) < 4) return false;
    
    $prefix = $parts[0];
    $station_id = $parts[1];
    $ts = intval($parts[2]);
    $hmac = $parts[3];
    
    if ($prefix !== 'STATION') return false;
    
    $key = getenv('JWT_SECRET') ?: 'secret_key';
    $expected = hash_hmac('sha256', 'STATION|' . $station_id . '|' . $ts, $key);
    
    if (!hash_equals($expected, $hmac)) return false;
    if (time() - $ts > $max_age) return false;
    
    return intval($station_id);
}

/**
 * Vygeneruje PIN kód pro stanoviště
 */
function generate_station_pin() {
    return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Najde stanoviště podle PIN kódu
 */
function find_station_by_pin($pin_code) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE pin_code = ? AND active = 1");
    $stmt->execute([$pin_code]);
    return $stmt->fetch();
}

/**
 * Aktualizuje QR token a PIN kód stanoviště
 */
function update_station_codes($station_id) {
    $pdo = db();
    $qr_token = generate_station_qr_token($station_id);
    $pin_code = generate_station_pin();
    
    $stmt = $pdo->prepare("UPDATE stations SET qr_token = ?, pin_code = ? WHERE id = ?");
    $stmt->execute([$qr_token, $pin_code, $station_id]);
    
    return ['qr_token' => $qr_token, 'pin_code' => $pin_code];
}

function update_virtual_station_codes($station_id) {
    $qr_token = generate_virtual_station_qr_token($station_id);
        
    return ['qr_token' => $qr_token];
}

/**
 * Získá detailní informace o týmu včetně členů a historie
 */
function get_team_details($team_id) {
    $pdo = db();
    
    // Základní info o týmu
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch();
    
    if (!$team) return null;
    
    // Členové týmu
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE team_id = ?");
    $stmt->execute([$team_id]);
    $team['members'] = $stmt->fetchAll();
    
    // Historie výsledků
    $stmt = $pdo->prepare("
        SELECT r.*, s.name as station_name, s.reward_points as max_points, 
               u.name as organizer_name, r.created_at
        FROM results r
        LEFT JOIN stations s ON r.station_id = s.id
        LEFT JOIN users u ON r.organizer_id = u.id
        WHERE r.team_id = ?
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$team_id]);
    $team['results'] = $stmt->fetchAll();
    
    // Počet dokončených stanic
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM results WHERE team_id = ? AND status = 'done'");
    $stmt->execute([$team_id]);
    $team['completed_stations'] = $stmt->fetch()['completed'];
    
    return $team;
}
