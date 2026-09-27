<?php
/**
 * Vylepšené helper funkce pro virtuální stanoviště s podporou více úkolů
 */

require_once __DIR__ . '/db.php';

/**
 * Získá nebo vytvoří session pro tým na stanovišti
 */
function get_or_create_station_session($station_id, $team_id) {
    $pdo = db();
    
    // Zkontrolovat aktivní session
    $stmt = $pdo->prepare("
        SELECT * FROM team_station_sessions 
        WHERE team_id = ? AND station_id = ? AND completed_at IS NULL
        ORDER BY started_at DESC LIMIT 1
    ");
    $stmt->execute([$team_id, $station_id]);
    $session = $stmt->fetch();
    
    if ($session) {
        return $session;
    }
    
    // Vytvořit novou session
    $stmt = $pdo->prepare("
        INSERT INTO team_station_sessions (team_id, station_id, started_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$team_id, $station_id]);
    
    $session_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT * FROM team_station_sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    return $stmt->fetch();
}

/**
 * Získá úkoly pro stanoviště podle režimu výběru
 */
function get_station_tasks($station_id, $team_id = null) {
    $pdo = db();
    
    // Získat nastavení stanoviště
    $stmt = $pdo->prepare("
        SELECT task_selection_mode, tasks_count, fixed_task_ids 
        FROM stations WHERE id = ?
    ");
    $stmt->execute([$station_id]);
    $station_config = $stmt->fetch();
    
    if (!$station_config) {
        return [];
    }
    
    $mode = $station_config['task_selection_mode'] ?? 'all';
    
    // Získat všechny úkoly stanoviště
    $stmt = $pdo->prepare("
        SELECT * FROM station_tasks 
        WHERE station_id = ? 
        ORDER BY task_order ASC, id ASC
    ");
    $stmt->execute([$station_id]);
    $all_tasks = $stmt->fetchAll();
    
    if (empty($all_tasks)) {
        return [];
    }
    
    // Dekódovat JSON data
    foreach ($all_tasks as &$task) {
        $task['quiz_options'] = json_decode($task['quiz_options'] ?? '[]', true);
        $task['quiz_correct_answers'] = json_decode($task['quiz_correct_answers'] ?? '[]', true);
        $task['quiz_answer_messages'] = json_decode($task['quiz_answer_messages'] ?? '{}', true);
    }
    
    // Podle režimu vrátit úkoly
    if ($mode === 'all') {
        return $all_tasks;
    }
    
    if ($mode === 'fixed') {
        $fixed_ids = json_decode($station_config['fixed_task_ids'] ?? '[]', true);
        if (empty($fixed_ids)) {
            return $all_tasks; // Fallback na všechny
        }
        return array_filter($all_tasks, function($task) use ($fixed_ids) {
            return in_array($task['id'], $fixed_ids);
        });
    }
    
    if ($mode === 'random' && $team_id) {
        return get_random_tasks_for_team($station_id, $team_id, $all_tasks, $station_config['tasks_count']);
    }
    
    return $all_tasks;
}

/**
 * Získá nebo přiřadí náhodné úkoly pro tým
 */
function get_random_tasks_for_team($station_id, $team_id, $all_tasks, $count) {
    $pdo = db();
    
    // Zkontrolovat, zda už má tým přiřazené úkoly
    $stmt = $pdo->prepare("
        SELECT task_id FROM team_assigned_tasks 
        WHERE team_id = ? AND station_id = ?
    ");
    $stmt->execute([$team_id, $station_id]);
    $assigned_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($assigned_ids)) {
        // Vrátit už přiřazené úkoly
        return array_filter($all_tasks, function($task) use ($assigned_ids) {
            return in_array($task['id'], $assigned_ids);
        });
    }
    
    // Přiřadit náhodné úkoly
    $count = min($count ?: count($all_tasks), count($all_tasks));
    shuffle($all_tasks);
    $selected_tasks = array_slice($all_tasks, 0, $count);
    
    // Uložit přiřazení
    $stmt = $pdo->prepare("
        INSERT INTO team_assigned_tasks (team_id, station_id, task_id)
        VALUES (?, ?, ?)
    ");
    
    foreach ($selected_tasks as $task) {
        $stmt->execute([$team_id, $station_id, $task['id']]);
    }
    
    return $selected_tasks;
}

/**
 * Získá kompletní data virtuálního stanoviště s úkoly
 */
function get_virtual_station_data_v2($station_id, $team_id = null) {
    $pdo = db();
    
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND active = 1");
    $stmt->execute([$station_id]);
    $station = $stmt->fetch();
    
    if (!$station) {
        return null;
    }
    
    // Dekódovat JSON data
    $station['custom_colors'] = json_decode($station['custom_colors'] ?? '{}', true);
    $station['hints'] = json_decode($station['hints'] ?? '[]', true);
    
    // Získat úkoly
    $station['tasks'] = get_station_tasks($station_id, $team_id);
    
    // Získat session
    if ($team_id) {
        $station['session'] = get_or_create_station_session($station_id, $team_id);
        
        // Získat použité nápovědy týmu
        $stmt = $pdo->prepare("
            SELECT hint_index, used_at 
            FROM team_hints_used 
            WHERE team_id = ? AND station_id = ?
        ");
        $stmt->execute([$team_id, $station_id]);
        $station['used_hints'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Získat odpovědi týmu na úkoly
        $stmt = $pdo->prepare("
            SELECT task_id, answer, file_path, points_awarded, status, submitted_at
            FROM team_task_answers
            WHERE team_id = ? AND station_id = ? AND session_id = ?
        ");
        $stmt->execute([$team_id, $station_id, $station['session']['id']]);
        $answers = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
        $station['task_answers'] = $answers;
    } else {
        $station['used_hints'] = [];
        $station['task_answers'] = [];
    }
    
    return $station;
}

/**
 * Validuje odpověď na úkol - UPDATED to support multiple correct answers
 */
function validate_task_answer($task, $answer) {
    $result = [
        'correct' => null,
        'feedback' => '',
        'correct_answer' => null
    ];
    
    $task_type = $task['task_type'];
    
    if ($task_type === 'quiz' || $task_type === 'creative') {
        // NEW: Support multiple correct answers in any order
        $correct_answers = $task['quiz_correct_answers'] ?? [];
        
        // Fallback: if quiz_correct_answers is empty, use first option as correct
        if (empty($correct_answers) && !empty($task['quiz_options'])) {
            $correct_answers = [$task['quiz_options'][0]];
        }
        
        // Normalize answer for comparison
        $normalized_answer = mb_strtolower(trim($answer));
        $is_correct = false;
        
        foreach ($correct_answers as $correct) {
            if (mb_strtolower(trim($correct)) === $normalized_answer) {
                $is_correct = true;
                break;
            }
        }
        
        $result['correct'] = $is_correct;
        $result['correct_answer'] = implode(' / ', $correct_answers);
        
        // Get custom message for this answer
        $quiz_answer_messages = $task['quiz_answer_messages'] ?? [];
        if (!empty($quiz_answer_messages)) {
            if ($is_correct && isset($quiz_answer_messages['correct'])) {
                $result['feedback'] = $quiz_answer_messages['correct'];
            } elseif (!$is_correct && isset($quiz_answer_messages['incorrect'])) {
                $result['feedback'] = $quiz_answer_messages['incorrect'];
            }
        }
        
    } elseif ($task_type === 'text') {
        $correct_answer = trim($task['correct_answer'] ?? '');
        if ($correct_answer !== '') {
            $result['correct'] = (mb_strtolower(trim($answer)) === mb_strtolower($correct_answer));
            $result['correct_answer'] = $correct_answer;
        } else {
            $result['correct'] = null; // Vyžaduje manuální kontrolu
        }
    } else {
        // Ostatní typy - automaticky přidělit body
        $result['correct'] = null;
    }
    
    return $result;
}

/**
 * Vypočítá body za úkol
 */
function calculate_task_points($task, $is_correct, $time_taken = null) {
    $base_points = intval($task['reward_points']);
    $points = 0;
    
    if ($is_correct === true) {
        $points = $base_points;
        
        // Speed bonus
        if ($task['speed_bonus'] > 0 && $time_taken !== null && $time_taken <= 120) {
            $points += intval($task['speed_bonus']);
        }
    } elseif ($is_correct === false) {
        $points = -intval($task['penalty_wrong']);
    } else {
        // Nelze automaticky vyhodnotit - přidělit plný počet bodů
        $points = $base_points;
    }
    
    return max(0, $points);
}

/**
 * Uloží odpověď týmu na úkol
 */
function save_task_answer($team_id, $station_id, $task_id, $session_id, $answer, $file_path, $points, $status) {
    $pdo = db();
    
    $stmt = $pdo->prepare("
        INSERT INTO team_task_answers 
        (team_id, station_id, task_id, session_id, answer, file_path, points_awarded, status, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $team_id, 
        $station_id, 
        $task_id, 
        $session_id, 
        $answer, 
        $file_path, 
        $points, 
        $status
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Zkontroluje, zda tým dokončil všechny úkoly stanoviště
 */
function check_station_completion($station_id, $team_id, $session_id) {
    $pdo = db();
    
    // Získat počet úkolů, které by tým měl vyřešit
    $tasks = get_station_tasks($station_id, $team_id);
    $total_tasks = count($tasks);
    
    if ($total_tasks === 0) {
        return false;
    }
    
    // Získat počet dokončených úkolů
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM team_task_answers
        WHERE team_id = ? AND station_id = ? AND session_id = ?
    ");
    $stmt->execute([$team_id, $station_id, $session_id]);
    $completed_tasks = $stmt->fetchColumn();
    
    return $completed_tasks >= $total_tasks;
}

/**
 * Dokončí session stanoviště
 */

function complete_station_session($station_id, $team_id, $session_id) {
    $pdo = db();
    
    try {        
        // Získat celkový čas
        $stmt = $pdo->prepare("
            SELECT TIMESTAMPDIFF(SECOND, started_at, NOW()) as total_time
            FROM team_station_sessions
            WHERE id = ?
        ");
        $stmt->execute([$session_id]);
        $total_time = $stmt->fetchColumn();
        
        // Aktualizovat session
        $stmt = $pdo->prepare("
            UPDATE team_station_sessions
            SET completed_at = NOW(), total_time = ?
            WHERE id = ?
        ");
        $stmt->execute([$total_time, $session_id]);
        
        // Sečíst všechny body z úkolů
        $stmt = $pdo->prepare("
            SELECT SUM(points_awarded) as total_points
            FROM team_task_answers
            WHERE session_id = ?
        ");
        $stmt->execute([$session_id]);
        $total_points = intval($stmt->fetchColumn());
        
        // Odečíst cenu nápověd
        $stmt = $pdo->prepare("
            SELECT SUM(points_cost) as hint_cost
            FROM team_hints_used
            WHERE team_id = ? AND station_id = ?
        ");
        $stmt->execute([$team_id, $station_id]);
        $hint_cost = intval($stmt->fetchColumn());
        
        $final_points = max(0, $total_points - $hint_cost);
        
        // Uložit výsledek do results
        $stmt = $pdo->prepare("
            INSERT INTO results (team_id, station_id, status, points, completion_time, created_at)
            VALUES (?, ?, 'done', ?, ?, NOW())
        ");
        $stmt->execute([$team_id, $station_id, $final_points, $total_time]);
        
        // Aktualizovat body týmu
        $stmt = $pdo->prepare("
            UPDATE teams SET points = GREATEST(0, points + ?) WHERE id = ?
        ");
        $stmt->execute([$final_points, $team_id]);
        
        // Snížit obsazenost stanoviště
        $stmt = $pdo->prepare("
            UPDATE stations SET current_occupancy = GREATEST(0, current_occupancy - 1) WHERE id = ?
        ");
        $stmt->execute([$station_id]);
        
        // Vymazat aktuální stanoviště týmu
        $stmt = $pdo->prepare("
            UPDATE teams SET current_station_id = NULL, arrival_time = NULL, assigned_station_id = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$team_id]);
                
        return [
            'success' => true,
            'total_points' => $final_points,
            'total_time' => $total_time
        ];
        
    } catch (Exception $e) {
        throw $e; // předat chybu hlavnímu try/catch
    }
}

/**
 * Zpracuje nahrání souboru pro úkol
 */

function handle_task_file_upload($file, $team_id, $station_id, $task_id) {
    $accountId = '0038c21da9b43950000000001';
    $appKey = 'K003K+d3sdrtK3nGK6pRkSnoa6l3Jjg';
    $bucketId = '184c62315dda898b94a30915'; // ID bucketu, ne název
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'team_' . $team_id . '_station_' . $station_id . '_task_' . $task_id . '_' . time() . '.' . $extension;

    // 1️⃣ Authorize account
    $ch = curl_init('https://api.backblazeb2.com/b2api/v2/b2_authorize_account');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode("$accountId:$appKey")
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if (!$response) return ['success' => false, 'error' => $error];
    $authData = json_decode($response, true);
    $authToken = $authData['authorizationToken'];
    $apiUrl = $authData['apiUrl'];
    $downloadUrl = $authData['downloadUrl'];

    // 2️⃣ Get upload URL
    $ch = curl_init("$apiUrl/b2api/v2/b2_get_upload_url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['bucketId' => $bucketId]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $authToken",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if (!$response) return ['success' => false, 'error' => $error];
    $uploadData = json_decode($response, true);
    $uploadUrl = $uploadData['uploadUrl'];
    $uploadAuthToken = $uploadData['authorizationToken'];

    // 3️⃣ Upload file
    $fileData = file_get_contents($file['tmp_name']);
    $sha1 = sha1($fileData);

    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $uploadAuthToken",
        "X-Bz-File-Name: " . rawurlencode($filename),
        "Content-Type: " . $file['type'],
        "X-Bz-Content-Sha1: $sha1"
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if (!$response) return ['success' => false, 'error' => $error];
    $result = json_decode($response, true);

    return [
        'success' => true,
        'filename' => $filename,
        'path' => $downloadUrl . '/file/' . $bucketId . '/' . $filename
    ];
}


/**
 * Zkontroluje, zda tým splnil požadavek na fyzické QR
 */
function check_physical_qr_requirement($station_id, $team_id) {
    $pdo = db();
    
    $stmt = $pdo->prepare("SELECT require_physical_qr FROM stations WHERE id = ?");
    $stmt->execute([$station_id]);
    $require_qr = $stmt->fetchColumn();
    
    if (!$require_qr) {
        return true;
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM team_qr_scans 
        WHERE team_id = ? AND station_id = ? AND scanned_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$team_id, $station_id]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Použije nápovědu (stejné jako původní funkce)
 */
function use_hint($station_id, $team_id, $hint_index) {
    $pdo = db();
    
    try {
        $pdo->beginTransaction();
        
        $station = get_virtual_station_data_v2($station_id, $team_id);
        if (!$station || !isset($station['hints'][$hint_index])) {
            throw new Exception('Nápověda nenalezena');
        }
        
        if (in_array($hint_index, $station['used_hints'])) {
            throw new Exception('Nápověda již byla použita');
        }
        
        $hint = $station['hints'][$hint_index];
        $cost = intval($hint['points_cost']);
        
        if ($cost > 0) {
            $stmt = $pdo->prepare("UPDATE teams SET points = GREATEST(0, points - ?) WHERE id = ?");
            $stmt->execute([$cost, $team_id]);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO team_hints_used (team_id, station_id, hint_index, points_cost, used_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$team_id, $station_id, $hint_index, $cost]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'hint_text' => $hint['text'],
            'points_cost' => $cost
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}