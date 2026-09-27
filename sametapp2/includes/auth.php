<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
session_start();

// helper: build Google OAuth URL
function google_auth_url() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'hd' => ALLOWED_DOMAIN,
        'prompt' => 'select_account'
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

// exchange code for tokens + fetch userinfo
function google_exchange_code($code) {
    $token_url = 'https://oauth2.googleapis.com/token';
    $post = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    // use curl (shared hosting)
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
    if (empty($data['access_token'])) return null;
    // get userinfo
    $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $data['access_token']]);
    $ui = curl_exec($ch);
    curl_close($ch);
    $userinfo = json_decode($ui, true);
    return $userinfo;
}
