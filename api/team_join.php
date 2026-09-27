<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
header('Content-Type: application/json');

// 1️⃣ Kontrola přihlášení
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauth']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$code = strtoupper(trim($data['code'] ?? ''));

if (!$code) {
    http_response_code(400);
    echo json_encode(['error' => 'code_required']);
    exit;
}

$pdo = db();

// 2️⃣ Najdi tým podle kódu
$stmt = $pdo->prepare("SELECT * FROM teams WHERE code = ?");
$stmt->execute([$code]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$team) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}

// 3️⃣ Zjisti počet členů týmu
$maxMembers = 7; // maximální počet lidí v týmu
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE team_id = ?");
$stmt->execute([$team['id']]);
$currentCount = $stmt->fetchColumn();

if ($currentCount >= $maxMembers) {
    echo json_encode(['ok' => false, 'team' => $team, 'full' => true]);
    exit;
}

// 4️⃣ Ulož přidělení týmu
$userId = $_SESSION['user']['id'];
$stmt = $pdo->prepare(query: "UPDATE users SET team_id = ? WHERE id = ?");
$stmt->execute([$team['id'], $userId]);

// 5️⃣ Aktualizuj session
$_SESSION['user']['team_id'] = $team['id'];

// 6️⃣ Log akce
$log = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
$log->execute([$userId, "joined_team: {$team['name']} ({$team['code']})"]);

echo json_encode(['ok' => true, 'team' => $team]);
