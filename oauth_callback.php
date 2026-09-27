<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_GET['code'])) {
    echo "Chyba: chybí kód.";
    exit;
}

$code = $_GET['code'];
$ui = google_exchange_code($code);
if (!$ui || empty($ui['email'])) {
    echo "Nepodařilo se ověřit účet.";
    exit;
}
// kontrola domény
$domain = substr(strrchr($ui['email'], "@"), 1);
if ($domain !== ALLOWED_DOMAIN) {
    echo "Přístup povolen pouze pro doménu " . ALLOWED_DOMAIN;
    exit;
}

$google_id = $ui['sub'] ?? $ui['id'] ?? null;
$name = $ui['name'] ?? $ui['email'];
$email = $ui['email'];

$user = get_user_by_googleid($google_id);
if (!$user) {
    $id = create_user($google_id, $name, $email, 'player');
    $user = get_user_by_googleid($google_id);
}

session_start();
$_SESSION['user'] = $user;
header('Location: ./index');
exit;
