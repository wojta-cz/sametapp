<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error'=>'unauth']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$name = trim($data['name'] ?? '');
if ($name === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error'=>'name_required']);
    exit;
}
if (!validateTeamName($name)["valid"]) {
    echo json_encode(['ok' => false, 'error' => 'Toto jméno není vhodné']);
    exit;
}

$code = random_code(6);
$pdo = db();
$stmt = $pdo->prepare("INSERT INTO teams (name, code) VALUES (?,?)");
$stmt->execute([$name,$code]);
$team_id = $pdo->lastInsertId();
// přiřadit uživatele do týmu
$stmt = $pdo->prepare("UPDATE users SET team_id = ? WHERE id = ?");
$stmt->execute([$team_id, $_SESSION['user']['id']]);
$_SESSION['user']['team_id'] = $team_id;
echo json_encode(['ok'=>true,'team_id'=>$team_id,'code'=>$code]);
