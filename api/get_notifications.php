<?php
/**
 * API: Get User Notifications
 * 
 * Retrieves notifications for the current user
 * 
 * @param bool $_GET['unread_only'] - Only return unread notifications
 * @param int $_GET['limit'] - Limit number of notifications (default: 20)
 * @return JSON { notifications: array, unread_count: int }
 */
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentikasi diperlukan']);
        exit;
    }
    
    $unreadOnly = ($_GET['unread_only'] ?? '') === 'true';
    $limit = min(50, max(5, (int)($_GET['limit'] ?? 20)));
    
    // Get unread count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $countStmt->execute([$userId]);
    $unreadCount = $countStmt->fetchColumn();
    
    // Get notifications
    $where = "user_id = ?";
    $params = [$userId];
    
    if ($unreadOnly) {
        $where .= " AND is_read = FALSE";
    }
    
    $sql = "
        SELECT id, type, title, message, link, is_read, created_at
        FROM notifications
        WHERE $where
        ORDER BY created_at DESC
        LIMIT $limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => [
        'notifications' => $notifications,
        'unread_count' => (int)$unreadCount
    ]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}
