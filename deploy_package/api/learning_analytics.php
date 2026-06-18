<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json');

// Guard: logged in user required
if (empty($_SESSION['user_id'])) {
    ApiResponse::unauthorized('Unauthorized');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    ApiResponse::validationError(['action' => 'Action parameter required'], 'Action parameter required');
}

if ($action === 'track_event') {
    $eventType = sanitizeInput($_POST['event_type'] ?? '');
    $subtes = !empty($_POST['subtes']) ? sanitizeInput($_POST['subtes']) : null;
    $topik = !empty($_POST['topik']) ? sanitizeInput($_POST['topik']) : null;
    $questionId = (int)($_POST['question_id'] ?? $_POST['soal_id'] ?? 0) ?: null;
    $sessionId = (int)($_POST['session_id'] ?? 0) ?: null;
    $waktuMenit = (int)($_POST['waktu_menit'] ?? $_POST['time_spent_seconds'] ?? 0) ?: null;
    $isBenar = isset($_POST['is_benar']) ? (int)$_POST['is_benar'] : null;
    
    $validEventTypes = ['page_view', 'materi_access', 'soal_view', 'soal_answer', 'quiz_start', 'quiz_complete', 'tryout_start', 'tryout_complete'];
    if (!in_array($eventType, $validEventTypes)) {
        ApiResponse::validationError(['event_type' => 'Invalid event type'], 'Invalid event type');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO learning_analytics (user_id, event_type, subtes, topik, soal_id, session_id, time_spent_seconds)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'], $eventType, $subtes, $topik, $questionId, $sessionId, $waktuMenit
    ]);
    
    ApiResponse::success([], 'Event tracked');
    
} elseif ($action === 'get_learning_insights') {
    // Return empty insights (table may not exist)
    ApiResponse::success(['insights' => []], 'Insights retrieved');

} elseif ($action === 'mark_insight_read') {
    // No-op if table doesn't exist
    ApiResponse::success([], 'Insight marked as read');

} elseif ($action === 'get_learning_stats') {
    $userId = $_SESSION['user_id'];
    
    // Get stats by subtes
    $stmt = $pdo->prepare("
        SELECT 
            subtes,
            COUNT(DISTINCT soal_id) as soal_viewed,
            SUM(time_spent_seconds) as total_time_spent
        FROM learning_analytics
        WHERE user_id = ? AND subtes IS NOT NULL
        GROUP BY subtes
    ");
    $stmt->execute([$userId]);
    $subtesStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get most repeated soal
    $stmt = $pdo->prepare("
        SELECT soal_id, COUNT(*) as view_count
        FROM learning_analytics
        WHERE user_id = ? AND soal_id IS NOT NULL AND event_type = 'soal_view'
        GROUP BY soal_id
        ORDER BY view_count DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $topSoal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success([
        'subtes_stats' => $subtesStats,
        'top_soal' => $topSoal
    ], 'Learning stats retrieved');
    
} else {
    ApiResponse::validationError(['action' => 'Invalid action'], 'Invalid action');
}
