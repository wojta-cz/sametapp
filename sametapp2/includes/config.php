<?php
// jednoduché načtení env (pokud .env existuje)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$k,$v] = array_map('trim', explode('=', $line, 2));
        if (!getenv($k)) putenv("$k=$v");
    }
}

define('DB_HOST', getenv('DB_HOST') ?: 'mysql.hostnow.cz:3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sql1890_samet');
define('DB_USER', getenv('DB_USER') ?: 'sql1890_samet');
define('DB_PASS', getenv('DB_PASS') ?: 'Vlach.2009');
define('APP_URL', getenv('APP_URL') ?: 'https://samet.ruzickyprogjkt.fun/public/');
define('ALLOWED_DOMAIN', getenv('ALLOWED_DOMAIN') ?: 'gjkt.eu');
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI'));
