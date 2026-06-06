<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    // Get score progress per subtest (last 10 tryouts)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(waktu_mulai) as date,
            nilai_tkp,
            nilai_tiu,
            nilai_twk,
            total_nilai
        FROM tryout_sessions 
        WHERE user_id = ? AND status = 'selesai'
        ORDER BY waktu_mulai ASC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $scoreProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get learning activity data (last 30 days)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(waktu_mulai) as activity_date,
            COUNT(*) as session_count,
            SUM(TIMESTAMPDIFF(MINUTE, waktu_mulai, COALESCE(waktu_selesai, NOW()))) as total_minutes
        FROM tryout_sessions 
        WHERE user_id = ? AND waktu_mulai >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(waktu_mulai)
        ORDER BY activity_date ASC
    ");
    $stmt->execute([$userId]);
    $learningActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get national average (simulated - in production this would come from actual data)
    $nationalAverage = [
        'tkp' => 143,
        'tiu' => 75,
        'twk' => 62,
        'total' => 280
    ];
    
    // Get user's average scores
    $stmt = $pdo->prepare("
        SELECT 
            AVG(nilai_tkp) as avg_tkp,
            AVG(nilai_tiu) as avg_tiu,
            AVG(nilai_twk) as avg_twk,
            AVG(total_nilai) as avg_total
        FROM tryout_sessions 
        WHERE user_id = ? AND status = 'selesai'
    ");
    $stmt->execute([$userId]);
    $userAverage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get weak topics for recommendations
    $stmt = $pdo->prepare("
        SELECT 
            q.subtes,
            q.topik,
            COUNT(*) as total,
            SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        JOIN tryout_sessions ts ON a.session_id = ts.id
        WHERE ts.user_id = ? AND a.jawaban_user IS NOT NULL AND a.jawaban_user != ''
        GROUP BY q.subtes, q.topik
        HAVING COUNT(*) >= 3
        ORDER BY subtes, (benar * 100.0 / total) ASC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $weakTopics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'score_progress' => $scoreProgress,
            'learning_activity' => $learningActivity,
            'national_average' => $nationalAverage,
            'user_average' => [
                'tkp' => round($userAverage['avg_tkp'] ?? 0),
                'tiu' => round($userAverage['avg_tiu'] ?? 0),
                'twk' => round($userAverage['avg_twk'] ?? 0),
                'total' => round($userAverage['avg_total'] ?? 0)
            ],
            'weak_topics' => array_map(function($topic) {
                $accuracy = round(($topic['benar'] / $topic['total']) * 100);
                return [
                    'subtes' => $topic['subtes'],
                    'topik' => $topic['topik'],
                    'accuracy' => $accuracy,
                    'total' => $topic['total'],
                    'correct' => $topic['benar']
                ];
            }, $weakTopics)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch analytics data'
    ]);
}
