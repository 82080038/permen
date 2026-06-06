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

$action = $_POST['action'] ?? '';

if ($action === 'track_event') {
    $eventType = sanitizeInput($_POST['event_type'] ?? '');
    $pageUrl = $_POST['page_url'] ?? null;
    $materiId = (int)($_POST['materi_id'] ?? 0) ?: null;
    $soalId = (int)($_POST['soal_id'] ?? 0) ?: null;
    $subtes = sanitizeInput($_POST['subtes'] ?? null);
    $topik = sanitizeInput($_POST['topik'] ?? null);
    $timeSpent = (int)($_POST['time_spent_seconds'] ?? 0) ?: null;
    $sessionId = $_POST['session_id'] ?? session_id();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $validEventTypes = ['page_view', 'materi_access', 'soal_view', 'soal_answer', 'quiz_start', 'quiz_complete', 'tryout_start', 'tryout_complete'];
    if (!in_array($eventType, $validEventTypes)) {
        echo json_encode(['error' => 'Invalid event type']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO learning_analytics (user_id, event_type, page_url, materi_id, soal_id, subtes, topik, time_spent_seconds, session_id, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'], $eventType, $pageUrl, $materiId, $soalId, $subtes, $topik, $timeSpent, $sessionId, $userAgent
    ]);
    
    echo json_encode(['success' => true]);
    
} elseif ($_GET['action'] === 'get_learning_insights') {
    $stmt = $pdo->prepare("
        SELECT * FROM learning_insights 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $insights = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'insights' => $insights]);
    
} elseif ($_GET['action'] === 'mark_insight_read') {
    $insightId = (int)($_GET['insight_id'] ?? 0);
    
    if (!$insightId) {
        echo json_encode(['error' => 'Invalid insight ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE learning_insights 
        SET is_read = 1 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$insightId, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
    
} elseif ($_GET['action'] === 'get_learning_stats') {
    $userId = $_SESSION['user_id'];
    
    // Get stats by subtes
    $stmt = $pdo->prepare("
        SELECT 
            subtes,
            COUNT(DISTINCT soal_id) as soal_viewed,
            COUNT(DISTINCT materi_id) as materi_accessed,
            SUM(time_spent_seconds) as total_time_spent
        FROM learning_analytics
        WHERE user_id = ? AND subtes IS NOT NULL
        GROUP BY subtes
    ");
    $stmt->execute([$userId]);
    $subtesStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get most viewed materi
    $stmt = $pdo->prepare("
        SELECT materi_id, COUNT(*) as view_count
        FROM learning_analytics
        WHERE user_id = ? AND materi_id IS NOT NULL
        GROUP BY materi_id
        ORDER BY view_count DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $topMateri = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    
    echo json_encode([
        'success' => true,
        'subtes_stats' => $subtesStats,
        'top_materi' => $topMateri,
        'top_soal' => $topSoal
    ]);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}
