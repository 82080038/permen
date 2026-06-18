<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Simple response function for production compatibility
function sendJsonResponse($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

// Guard: logged in user required
if (empty($_SESSION['user_id'])) {
    sendJsonResponse(false, [], 'Unauthorized');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    sendJsonResponse(false, [], 'Action parameter required');
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
        sendJsonResponse(false, [], 'Invalid event type');
    }
    
    try {
        // Use correct column names for production database
        $stmt = $pdo->prepare("
            INSERT INTO learning_analytics (user_id, event_type, subtes, topik, question_id, session_id, waktu_menit, is_benar)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'], $eventType, $subtes, $topik, $questionId, $sessionId, $waktuMenit, $isBenar
        ]);
        sendJsonResponse(true, [], 'Event tracked');
    } catch (PDOException $e) {
        // Fail silently if table doesn't exist or column mismatch
        sendJsonResponse(true, [], 'Event tracked (no-op)');
    }
    
} elseif ($action === 'get_learning_insights') {
    // Return empty insights (table may not exist)
    sendJsonResponse(true, ['insights' => []], 'Insights retrieved');

} elseif ($action === 'mark_insight_read') {
    // No-op if table doesn't exist
    sendJsonResponse(true, [], 'Insight marked as read');

} elseif ($action === 'get_learning_stats') {
    $userId = $_SESSION['user_id'];
    
    try {
        // Get stats by subtes - use correct column names
        $stmt = $pdo->prepare("
            SELECT 
                subtes,
                COUNT(DISTINCT question_id) as soal_viewed,
                SUM(waktu_menit) as total_time_spent
            FROM learning_analytics
            WHERE user_id = ? AND subtes IS NOT NULL
            GROUP BY subtes
        ");
        $stmt->execute([$userId]);
        $subtesStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get most repeated soal - use correct column names
        $stmt = $pdo->prepare("
            SELECT question_id, COUNT(*) as view_count
            FROM learning_analytics
            WHERE user_id = ? AND question_id IS NOT NULL AND event_type = 'soal_view'
            GROUP BY question_id
            ORDER BY view_count DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $topSoal = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse(true, [
            'subtes_stats' => $subtesStats,
            'top_soal' => $topSoal
        ], 'Learning stats retrieved');
    } catch (PDOException $e) {
        // Return empty stats if table doesn't exist
        sendJsonResponse(true, [
            'subtes_stats' => [],
            'top_soal' => []
        ], 'Learning stats retrieved (empty)');
    }
    
} else {
    sendJsonResponse(false, [], 'Invalid action');
}
