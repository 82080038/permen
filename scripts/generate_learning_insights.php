<?php
require '../config.php';
require '../helpers.php';

/**
 * Generate learning insights for users based on their analytics data
 * This script should be run periodically (e.g., daily via cron)
 */

echo "Generating learning insights...\n";

// Get all active users
$stmt = $pdo->query("SELECT id FROM users WHERE aktif = 1");
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($users as $userId) {
    echo "Processing user {$userId}...\n";
    
    // Get user's learning stats
    $stmt = $pdo->prepare("
        SELECT 
            subtes,
            COUNT(DISTINCT soal_id) as soal_viewed,
            COUNT(DISTINCT CASE WHEN event_type = 'soal_answer' THEN soal_id END) as soal_answered,
            SUM(time_spent_seconds) as total_time_spent
        FROM learning_analytics
        WHERE user_id = ? AND subtes IS NOT NULL
        GROUP BY subtes
    ");
    $stmt->execute([$userId]);
    $subtesStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get answer accuracy by subtes
    $stmt = $pdo->prepare("
        SELECT 
            q.subtes,
            COUNT(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 END) as correct,
            COUNT(*) as total
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        JOIN tryout_sessions ts ON a.session_id = ts.id
        WHERE ts.user_id = ?
        GROUP BY q.subtes
    ");
    $stmt->execute([$userId]);
    $accuracyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine stats
    $statsBySubtes = [];
    foreach ($subtesStats as $stat) {
        $statsBySubtes[$stat['subtes']] = $stat;
    }
    foreach ($accuracyStats as $stat) {
        if (!isset($statsBySubtes[$stat['subtes']])) {
            $statsBySubtes[$stat['subtes']] = [];
        }
        $statsBySubtes[$stat['subtes']]['accuracy'] = $stat['total'] > 0 ? ($stat['correct'] / $stat['total']) * 100 : 0;
    }
    
    // Generate insights
    $insights = [];
    
    // Strength: High accuracy subtes
    foreach ($statsBySubtes as $subtes => $stat) {
        if (isset($stat['accuracy']) && $stat['accuracy'] >= 80 && $stat['soal_answered'] >= 10) {
            $insights[] = [
                'type' => 'strength',
                'title' => "Kuasai {$subtes}",
                'description' => "Anda memiliki akurasi tinggi di subtes {$subtes} (" . round($stat['accuracy']) . "%). Pertahankan performa ini!",
                'data' => json_encode(['subtes' => $subtes, 'accuracy' => $stat['accuracy']])
            ];
        }
    }
    
    // Weakness: Low accuracy subtes
    foreach ($statsBySubtes as $subtes => $stat) {
        if (isset($stat['accuracy']) && $stat['accuracy'] < 60 && $stat['soal_answered'] >= 10) {
            $insights[] = [
                'type' => 'weakness',
                'title' => "Perlu Latihan {$subtes}",
                'description' => "Akurasi Anda di subtes {$subtes} masih rendah (" . round($stat['accuracy']) . "%). Fokus latihan di area ini.",
                'data' => json_encode(['subtes' => $subtes, 'accuracy' => $stat['accuracy']])
            ];
        }
    }
    
    // Recommendation: Low activity subtes
    foreach ($statsBySubtes as $subtes => $stat) {
        if ($stat['soal_viewed'] < 5) {
            $insights[] = [
                'type' => 'recommendation',
                'title' => "Belajar {$subtes}",
                'description' => "Anda belum banyak belajar subtes {$subtes}. Mulai latihan soal di area ini.",
                'data' => json_encode(['subtes' => $subtes, 'soal_viewed' => $stat['soal_viewed']])
            ];
        }
    }
    
    // Progress: Streak
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT DATE(created_at)) as active_days
        FROM learning_analytics
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId]);
    $activeDays = $stmt->fetch()['active_days'];
    
    if ($activeDays >= 5) {
        $insights[] = [
            'type' => 'progress',
            'title' => "Streak Belajar!",
            'description' => "Anda aktif belajar {$activeDays} hari dalam 7 hari terakhir. Pertahankan konsistensi!",
            'data' => json_encode(['active_days' => $activeDays])
        ];
    }
    
    // Insert insights
    foreach ($insights as $insight) {
        // Check if similar insight exists in last 7 days
        $stmt = $pdo->prepare("
            SELECT id FROM learning_insights
            WHERE user_id = ? AND insight_type = ? AND title = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$userId, $insight['type'], $insight['title']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO learning_insights (user_id, insight_type, title, description, data)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $insight['type'],
                $insight['title'],
                $insight['description'],
                $insight['data']
            ]);
            echo "  - Added insight: {$insight['title']}\n";
        }
    }
}

echo "Learning insights generation complete.\n";
