<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: admin only
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'approve_content') {
    $moderationId = (int)($_POST['moderation_id'] ?? 0);
    $note = sanitizeInput($_POST['note'] ?? '');
    
    if (!$moderationId) {
        echo json_encode(['error' => 'Invalid moderation ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE content_moderation 
        SET status = 'approved', moderator_id = ?, moderator_note = ?, reviewed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $note ?: null, $moderationId]);
    
    ApiResponse::success([], 'Content approved');
    
} elseif ($action === 'reject_content') {
    $moderationId = (int)($_POST['moderation_id'] ?? 0);
    $note = sanitizeInput($_POST['note'] ?? '');
    
    if (!$moderationId) {
        echo json_encode(['error' => 'Invalid moderation ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE content_moderation 
        SET status = 'rejected', moderator_id = ?, moderator_note = ?, reviewed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $note ?: null, $moderationId]);
    
    ApiResponse::success([], 'Content rejected');
    
} elseif ($action === 'delete_content') {
    $moderationId = (int)($_POST['moderation_id'] ?? 0);
    $note = sanitizeInput($_POST['note'] ?? '');
    
    if (!$moderationId) {
        ApiResponse::validationError(['moderation_id' => 'Invalid moderation ID'], 'Invalid moderation ID');
    }
    
    // Get content info first
    $stmt = $pdo->prepare("SELECT content_type, content_id FROM content_moderation WHERE id = ?");
    $stmt->execute([$moderationId]);
    $content = $stmt->fetch();
    
    if (!$content) {
        ApiResponse::notFound('Content not found');
    }
    
    // Soft delete the content
    if ($content['content_type'] === 'question') {
        $stmt = $pdo->prepare("UPDATE questions SET is_active = 0 WHERE id = ?");
        $stmt->execute([$content['content_id']]);
    } elseif ($content['content_type'] === 'materi') {
        $stmt = $pdo->prepare("UPDATE materi SET is_active = 0 WHERE id = ?");
        $stmt->execute([$content['content_id']]);
    }
    
    // Update moderation status
    $stmt = $pdo->prepare("
        UPDATE content_moderation 
        SET status = 'deleted', moderator_id = ?, moderator_note = ?, reviewed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $note ?: null, $moderationId]);
    
    echo json_encode(['success' => true, 'message' => 'Content deleted']);
    
} elseif ($_GET['action'] === 'get_moderation_queue') {
    $status = $_GET['status'] ?? 'pending';
    
    $stmt = $pdo->prepare("
        SELECT cm.*, 
               u.nama as reporter_name,
               m.nama as moderator_name,
               CASE 
                   WHEN cm.content_type = 'question' THEN (SELECT pertanyaan FROM questions WHERE id = cm.content_id)
                   WHEN cm.content_type = 'materi' THEN (SELECT judul FROM materi WHERE id = cm.content_id)
                   ELSE 'Unknown'
               END as content_preview
        FROM content_moderation cm
        LEFT JOIN users u ON cm.reporter_id = u.id
        LEFT JOIN users m ON cm.moderator_id = m.id
        WHERE cm.status = ?
        ORDER BY cm.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$status]);
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'queue' => $queue]);
    
} elseif ($_GET['action'] === 'flag_content') {
    $contentType = $_GET['content_type'] ?? '';
    $contentId = (int)($_GET['content_id'] ?? 0);
    $reason = sanitizeInput($_GET['reason'] ?? '');
    
    if (!$contentType || !$contentId) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    // Check if already flagged
    $stmt = $pdo->prepare("SELECT id FROM content_moderation WHERE content_type = ? AND content_id = ? AND status = 'pending'");
    $stmt->execute([$contentType, $contentId]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Content already flagged and pending review']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO content_moderation (content_type, content_id, reporter_id, reason)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$contentType, $contentId, $_SESSION['user_id'] ?? null, $reason ?: null]);
    
    echo json_encode(['success' => true, 'message' => 'Content flagged for review']);
    
} else {
    ApiResponse::validationError(['action' => 'Invalid action'], 'Invalid action');
}
