<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: only logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    // Analyze user performance per topic
    $stmt = $pdo->prepare("
        SELECT 
            q.subtes,
            q.topik,
            COUNT(*) as total_attempts,
            SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as correct,
            ROUND(SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as accuracy
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        JOIN tryout_sessions ts ON a.session_id = ts.id
        WHERE ts.user_id = ? AND a.jawaban_user IS NOT NULL AND a.jawaban_user != ''
        GROUP BY q.subtes, q.topik
        HAVING COUNT(*) >= 3
        ORDER BY accuracy ASC, total_attempts DESC
    ");
    $stmt->execute([$userId]);
    $topicPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Identify weak topics (accuracy < 60%)
    $weakTopics = array_filter($topicPerformance, fn($t) => $t['accuracy'] < 60);
    
    // Get strong topics (accuracy >= 80%) for comparison
    $strongTopics = array_filter($topicPerformance, fn($t) => $t['accuracy'] >= 80);

    // If no data yet, recommend based on subtes balance
    if (empty($topicPerformance)) {
        // Check which subtes has been attempted
        $stmt = $pdo->prepare("
            SELECT q.subtes, COUNT(*) as attempts
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            JOIN tryout_sessions ts ON a.session_id = ts.id
            WHERE ts.user_id = ? AND a.jawaban_user IS NOT NULL
            GROUP BY q.subtes
        ");
        $stmt->execute([$userId]);
        $subtesAttempts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Recommend subtes with least attempts
        $recommendedSubtes = [];
        foreach (['TWK', 'TIU', 'TKP'] as $subtes) {
            $attempts = $subtesAttempts[$subtes] ?? 0;
            $recommendedSubtes[] = [
                'subtes' => $subtes,
                'attempts' => $attempts,
                'reason' => 'least_practiced'
            ];
        }
        usort($recommendedSubtes, fn($a, $b) => $a['attempts'] <=> $b['attempts']);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'has_data' => false,
                'weak_topics' => [],
                'strong_topics' => [],
                'recommendations' => array_slice($recommendedSubtes, 0, 3),
                'message' => 'Mulai latihan untuk mendapatkan rekomendasi personal berdasarkan performa Anda.'
            ]
        ]);
        exit;
    }

    // Generate recommendations based on weak topics
    $recommendations = [];
    foreach ($weakTopics as $topic) {
        $recommendations[] = [
            'subtes' => $topic['subtes'],
            'topik' => $topic['topik'],
            'accuracy' => $topic['accuracy'],
            'attempts' => $topic['total_attempts'],
            'priority' => 'high',
            'reason' => 'weak_performance'
        ];
    }

    // If no weak topics, recommend balanced practice
    if (empty($recommendations)) {
        // Find subtes with least practice
        $subtesPractice = [];
        foreach ($topicPerformance as $t) {
            $subtes = $t['subtes'];
            if (!isset($subtesPractice[$subtes])) {
                $subtesPractice[$subtes] = 0;
            }
            $subtesPractice[$subtes] += $t['total_attempts'];
        }
        
        foreach (['TWK', 'TIU', 'TKP'] as $subtes) {
            $attempts = $subtesPractice[$subtes] ?? 0;
            $recommendations[] = [
                'subtes' => $subtes,
                'topik' => null,
                'accuracy' => null,
                'attempts' => $attempts,
                'priority' => 'medium',
                'reason' => 'balanced_practice'
            ];
        }
        
        usort($recommendations, fn($a, $b) => $a['attempts'] <=> $b['attempts']);
        $recommendations = array_slice($recommendations, 0, 3);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'has_data' => true,
            'weak_topics' => array_values($weakTopics),
            'strong_topics' => array_values($strongTopics),
            'recommendations' => array_slice($recommendations, 0, 5),
            'message' => empty($weakTopics) ? 'Performa Anda bagus! Lanjutkan latihan seimbang untuk semua subtes.' : 'Fokus latihan pada topik yang perlu diperbaiki.'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to analyze performance']);
}
