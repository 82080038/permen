<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json');

try {
    // Get user count
    $userCount = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch()['count'];
    
    // Get completed tryout count
    $tryoutCount = $pdo->query("SELECT COUNT(*) as count FROM tryout_sessions WHERE status = 'selesai'")->fetch()['count'];
    
    // Get question count
    $questionCount = $pdo->query("SELECT COUNT(*) as count FROM questions")->fetch()['count'];
    
    // Get active users (last 30 days)
    $activeUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM tryout_sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Statistics fetched successfully',
        'data' => [
            'user_count' => (int)$userCount,
            'tryout_count' => (int)$tryoutCount,
            'question_count' => (int)$questionCount,
            'active_users' => (int)$activeUsers
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch statistics'
    ]);
}
