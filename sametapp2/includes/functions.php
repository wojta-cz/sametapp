<?php
require_once __DIR__ . '/db.php';

function random_code($len = 6)
{
    return strtoupper(substr(bin2hex(random_bytes($len)), 0, $len));
}

// uživatelské a týmové helpery
function get_user_by_googleid($google_id)
{
    $stmt = db()->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->execute([$google_id]);
    return $stmt->fetch();
}

function create_user($google_id, $name, $email, $role = 'player')
{
    $stmt = db()->prepare("INSERT INTO users (google_id,name,email,role) VALUES (?,?,?,?)");
    $stmt->execute([$google_id, $name, $email, $role]);
    return db()->lastInsertId();
}

function require_login($rank_required = null)
{
    session_start();
    if (empty($_SESSION['user'])) {
        header('Location: /login');
        exit;
    }
    if ($rank_required && !in_array($_SESSION['user']['role'], $rank_required)) {
        header('Location: /');
        exit;
    }
}

function qr_token_for_team($team_id)
{
    $payload = $team_id . '|' . time();
    $key = getenv('JWT_SECRET') ?: 'secret_key';
    $hmac = hash_hmac('sha256', $payload, $key);
    return base64_encode($payload . '|' . $hmac);
}

function verify_qr_token($token, $max_age = 300)
{
    $data = base64_decode($token);
    if (!$data)
        return false;
    $parts = explode('|', $data);
    if (count($parts) < 3)
        return false;
    $team_id = $parts[0];
    $ts = intval($parts[1]);
    $hmac = $parts[2];
    $key = getenv('JWT_SECRET') ?: 'secret_key';
    $expected = hash_hmac('sha256', $team_id . '|' . $ts, $key);
    if (!hash_equals($expected, $hmac))
        return false;
    if (time() - $ts > $max_age)
        return false;
    return intval($team_id);
}

function checkPerspective($teamName, $apiKey)
{
    $url = "https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze?key=" . $apiKey;

    // Data pro API
    $data = [
        "comment" => ["text" => $teamName],
        "languages" => ["cs", "en"],
        "requestedAttributes" => [
            "TOXICITY" => new stdClass(),
            "INSULT" => new stdClass(),
            "PROFANITY" => new stdClass()
        ]
    ];

    // Převod do JSON
    $jsonData = json_encode($data);

    // cURL request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    // Odpověď API
    $response = curl_exec($ch);
    curl_close($ch);


    if ($response === FALSE) {
        return ['error' => true, 'message' => 'Chyba při komunikaci s Perspective API'];
    }

    return json_decode($response, true);
}

function checkLocalWordlist($teamName, $pathToFile)
{
    if (!file_exists($pathToFile)) {
        return false;
    }

    $teamNameLower = mb_strtolower($teamName);
    $handle = fopen($pathToFile, "r");

    if (!$handle) {
        return false;
    }

    while (($line = fgets($handle)) !== false) {
        $badWord = trim(mb_strtolower($line));

        if ($badWord === "")
            continue;

        if (mb_strpos($teamNameLower, $badWord) !== false) {
            fclose($handle);
            return $badWord;
        }
    }

    fclose($handle);
    return false;
}

function validateTeamName($teamName)
{
    // 1) Lokální kontrola
    $perspectiveKey = getenv("PERSPECTIVE_API");
    $badwordFile = __DIR__ . "/includes/badwords.txt";
    $localCheck = checkLocalWordlist($teamName, $badwordFile);
    if ($localCheck !== false) {
        return [
            "valid" => false,
            "reason" => "Název týmu obsahuje zakázané slovo: " . $localCheck
        ];
    }

    // 2) Google Perspective kontrola
    $perspective = checkPerspective($teamName, $perspectiveKey);

    if (isset($perspective['error'])) {
        return [
            "valid" => false,
            "reason" => "Nepodařilo se ověřit přes Perspective API. " . print_r($perspective)
        ];
    }

    $scores = [];
    foreach ($perspective['attributeScores'] as $attr => $val) {
        $scores[$attr] = $val['summaryScore']['value'];
    }

    // nastav si vlastní prahy
    if ($scores["TOXICITY"] > 0.4 || $scores["INSULT"] > 0.4 || $scores["PROFANITY"] > 0.4) {
        return [
            "valid" => false,
            "reason" => "Název týmu je nevhodný podle AI",
            "scores" => $scores
        ];
    }

    return [
        "valid" => true,
        "scores" => $scores
    ];
}

function is_time_lock_active() {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'time_lock_enabled'");
    $stmt->execute();
    $value = $stmt->fetchColumn();
    return $value === '1';
}

function can_access_stations($user_role) {
    // Admins and organizers can always access stations
    if ($user_role === 'admin' || $user_role === 'organizer') {
        return true;
    }
    
    // Regular players can only access if time lock is not active
    return !is_time_lock_active();
}