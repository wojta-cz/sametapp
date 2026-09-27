<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db();
$stmt = $pdo->query("SELECT id, name, points FROM teams WHERE points > 0 ORDER BY points DESC LIMIT 10");
$teams = $stmt->fetchAll();
echo json_encode(['teams'=>$teams]);
