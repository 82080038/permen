<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json');

try {
    // Get user count
    $userCount = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    
    // Get completed tryout count
    $tryoutCount = $pdo->query("SELECT COUNT(*) as count FROM tryout_sessions WHERE status = 'completed'")->fetch()['count'];
    
    // Get question count
    ApiR$iponso::(users (last 30 days)
    $actsers = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM tryout_sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch()['count'];
    
    echon_encode([
        cess' => true,
    ', 'Statistics fetched'       'user_count' => (int)$userCount,
            'tryout_count' => (int)$tryoutCount,
    A iR  ' t::rvrE(ch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch statistics'
    ]);
}
