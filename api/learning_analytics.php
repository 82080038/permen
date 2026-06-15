<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: logged in user required
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    echo json_encode(['error' => 'Action parameter required']);
    exit;
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
        echo json_encode(['error' => 'Invalid event type']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO learning_analytics (user_id, event_type, subtes, topik, question_id, session_id, waktu_menit, is_benar)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'], $eventType, $subtes, $topik, $questionId, $sessionId, $waktuMenit, $isBenar
    ]);
    
    echo json_encode(['success' => true]);
    
} elseif ($action === 'get_learning_insights') {
    // Return empty insights (table may not exist)
    echo json_encode(['success' => true, 'insights' => []]);

} elseif ($action === 'mark_insight_read') {
    // No-op if table doesn't exist
    echo json_encode(['success' => true]);

} elseif ($action === 'get_learning_stats') {
    $userId = $_SESSION['user_id'];
    
    // Get stats by subtes
    $stmt = $pdo->prepare("
        SELECT 
            subtes,
            COUNT(DISTINCT question_id) as soal_viewed,
            SUM(waktu_menit) as total_time_spent,
            SUM(CASE WHEN is_benar = 1 THEN 1 ELSE 0 END) as total_benar
        FROM learning_analytics
        WHERE user_id = ? AND subtes IS NOT NULL
        GROUP BY subtes
    ");
    $stmt->execute([$userId]);
    $subtesStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get most repeated soal
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
    
    echo json_encode([
        'success' => true,
        'subtes_stats' => $subtesStats,
        'top_soal' => $topSoal
    ]);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}
