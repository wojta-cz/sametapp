<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/virtual_station_functions.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nejste přihlášeni']);
    exit;
}

$user = $_SESSION['user'];
$team_id = $user['team_id'] ?? null;

if (!$team_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nejste členem žádného týmu']);
    exit;
}

$pdo = db();

try {
    $station_id = intval($_POST['station_id'] ?? 0);
    $task_id = intval($_POST['task_id'] ?? 0);
    $answer = trim($_POST['answer'] ?? '');
    
    if (!$station_id || !$task_id) {
        throw new Exception('Neplatné ID stanoviště nebo úkolu');
    }
    
    $pdo->beginTransaction();
    
    // Získat data stanoviště a session
    $station = get_virtual_station_data_v2($station_id, $team_id);
    
    if (!$station || $station['type'] !== 'virtual') {
        throw new Exception('Stanoviště nenalezeno nebo není virtuální');
    }
    
    // Zkontrolovat požadavek na fyzické QR
    if ($station['require_physical_qr'] && !check_physical_qr_requirement($station_id, $team_id)) {
        throw new Exception('Musíte nejprve naskenovat fyzický QR kód stanoviště');
    }
    
    $session = $station['session'];
    
    // Najít úkol
    $task = null;
    foreach ($station['tasks'] as $t) {
        if ($t['id'] == $task_id) {
            $task = $t;
            break;
        }
    }
    
    if (!$task) {
        throw new Exception('Úkol nenalezen');
    }
    
    // Zkontrolovat, zda už nebyl zodpovězen
    if (isset($station['task_answers'][$task_id])) {
        throw new Exception('Tento úkol jste již zodpověděli');
    }
    
    // Zpracovat nahrání souboru
    $file_path = null;
    $file_required = ($task['task_type'] === 'file_upload');
    $file_allowed = ($task['allow_file_upload'] || in_array($task['task_type'], ['file_upload', 'mixed', 'creative']));
    
    if ($file_allowed && !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handle_task_file_upload($_FILES['file'], $team_id, $station_id, $task_id);
        if ($upload_result['success']) {
            $file_path = $upload_result['path'];
        } else {
            throw new Exception($upload_result['error']);
        }
    } elseif ($file_required && empty($_FILES['file'])) {
        throw new Exception('Tento úkol vyžaduje nahrání souboru');
    }
    
    // Validovat odpověď podle typu úkolu
    $answer_required = in_array($task['task_type'], ['text', 'quiz', 'mixed', 'creative']);
    
    if ($answer_required && empty($answer) && empty($file_path)) {
        throw new Exception('Prosím vyplňte odpověď nebo nahrajte soubor');
    }
    
    // Validovat odpověď - UPDATED to return correct answer and feedback
    $validation = validate_task_answer($task, $answer);
    $is_correct = $validation['correct'];
    $correct_answer_text = $validation['correct_answer'];
    $custom_feedback = $validation['feedback'];
    
    // Vypočítat čas od začátku session
    $stmt = $pdo->prepare("
        SELECT TIMESTAMPDIFF(SECOND, started_at, NOW()) as elapsed
        FROM team_station_sessions WHERE id = ?
    ");
    $stmt->execute([$session['id']]);
    $time_taken = $stmt->fetchColumn();
    
    // Vypočítat body
    $points = calculate_task_points($task, $is_correct, $time_taken);
    
    // Určit status
    $status = 'done';
    if ($is_correct === false) {
        $status = 'failed';
    } elseif ($is_correct === null) {
        $status = 'done'; // Automaticky přiděleno
    }
    
    // Uložit odpověď
    save_task_answer($team_id, $station_id, $task_id, $session['id'], $answer, $file_path, $points, $status);
    
    // Zkontrolovat, zda jsou všechny úkoly dokončeny
    $all_completed = check_station_completion($station_id, $team_id, $session['id']);
    
    $response = [
        'success' => true,
        'status' => $status,
        'points' => $points,
        'is_correct' => $is_correct,
        'all_completed' => $all_completed
    ];
    
    // NEW: Add correct answer and custom feedback to response
    if ($correct_answer_text) {
        $response['correct_answer'] = $correct_answer_text;
    }
    
    if ($custom_feedback) {
        $response['custom_feedback'] = $custom_feedback;
    }
    
    // Build message with correct answer display
    if ($is_correct === true) {
        $message = '✅ Správně! Získali jste ' . $points . ' bodů.';
        if ($custom_feedback) {
            $message .= "\n\n" . $custom_feedback;
        }
        $response['message'] = $message;
    } elseif ($is_correct === false) {
        $message = '❌ Bohužel špatně.';
        if ($correct_answer_text) {
            $message .= "\n\n📌 Správná odpověď: " . $correct_answer_text;
        }
        if ($custom_feedback) {
            $message .= "\n\n" . $custom_feedback;
        }
        if ($points > 0) {
            $message .= "\n\n⚠️ Penalizace: -" . $points . ' bodů.';
        }
        $response['message'] = $message;
    } else {
        $response['message'] = 'Odpověď byla odeslána. Získali jste ' . $points . ' bodů.';
    }
    
    if ($all_completed) {
        // Dokončit celé stanoviště
        $completion = complete_station_session($station_id, $team_id, $session['id']);
        if ($completion['success']) {

         $response['station_completed'] = true;
            $response['total_points'] = $completion['total_points'];
            $response['total_time'] = $completion['total_time'];
            $response['message'] = 'Gratulujeme! Dokončili jste všechny úkoly. Celkem jste získali ' . $completion['total_points'] . ' bodů.';
        }
    }
    
    $pdo->commit();
    echo json_encode($response);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}