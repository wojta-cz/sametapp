<?php
/**
 * Migrace starých virtuálních stanovišť na nový systém s více úkoly
 * 
 * Tento skript převede stávající virtuální stanoviště (s jedním úkolem)
 * na nový systém s podporou více úkolů.
 * 
 * Spustit: php migrate_old_stations.php
 */

require_once __DIR__ . '/includes/db.php';

echo "=== Migrace virtuálních stanovišť ===\n\n";

$pdo = db();

try {
    $pdo->beginTransaction();
    
    // Získat všechna virtuální stanoviště
    $stmt = $pdo->query("
        SELECT * FROM stations 
        WHERE type = 'virtual' 
        AND (question IS NOT NULL OR quiz_options IS NOT NULL)
    ");
    $stations = $stmt->fetchAll();
    
    echo "Nalezeno " . count($stations) . " virtuálních stanovišť k migraci.\n\n";
    
    foreach ($stations as $station) {
        echo "Migrace stanoviště: " . $station['name'] . " (ID: " . $station['id'] . ")\n";
        
        // Zkontrolovat, zda už nemá úkoly v nové tabulce
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM station_tasks WHERE station_id = ?");
        $stmt->execute([$station['id']]);
        $existing_tasks = $stmt->fetchColumn();
        
        if ($existing_tasks > 0) {
            echo "  ⚠️  Stanoviště už má úkoly, přeskakuji...\n\n";
            continue;
        }
        
        // Vytvořit jeden úkol ze stávajících dat
        $question = $station['question'] ?? 'Vyřešte úkol';
        $correct_answer = $station['correct_answer'] ?? null;
        $task_type = $station['task_type'] ?? 'text';
        $media_type = $station['media_type'] ?? 'none';
        $media_url = $station['media_url'] ?? null;
        $quiz_options = $station['quiz_options'] ?? null;
        $reward_points = $station['reward_points'] ?? 10;
        $max_points = $station['max_points'] ?? null;
        $min_points = $station['min_points'] ?? 0;
        $speed_bonus = $station['speed_bonus'] ?? 0;
        $penalty_wrong = $station['penalty_wrong'] ?? 0;
        $allow_file_upload = $station['allow_file_upload'] ?? 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO station_tasks 
            (station_id, task_order, question, correct_answer, task_type, media_type, media_url, 
             quiz_options, reward_points, max_points, min_points, speed_bonus, penalty_wrong, 
             allow_file_upload, created_at)
            VALUES (?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $station['id'],
            $question,
            $correct_answer,
            $task_type,
            $media_type,
            $media_url,
            $quiz_options,
            $reward_points,
            $max_points,
            $min_points,
            $speed_bonus,
            $penalty_wrong,
            $allow_file_upload
        ]);
        
        $task_id = $pdo->lastInsertId();
        
        // Nastavit režim výběru na "all" (všechny úkoly)
        $stmt = $pdo->prepare("
            UPDATE stations 
            SET task_selection_mode = 'all', tasks_count = NULL, fixed_task_ids = NULL
            WHERE id = ?
        ");
        $stmt->execute([$station['id']]);
        
        echo "  ✓ Vytvořen úkol ID: " . $task_id . "\n";
        echo "  ✓ Režim výběru nastaven na 'all'\n\n";
    }
    
    $pdo->commit();
    
    echo "\n=== Migrace dokončena úspěšně! ===\n";
    echo "Migrováno " . count($stations) . " stanovišť.\n";
    echo "\nDalší kroky:\n";
    echo "1. Zkontrolujte migrovaná data v administraci\n";
    echo "2. Můžete přidat další úkoly ke každému stanovišti\n";
    echo "3. Nastavte režim výběru úkolů (all/random/fixed)\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ CHYBA při migraci: " . $e->getMessage() . "\n";
    echo "Migrace byla vrácena zpět.\n";
    exit(1);
}