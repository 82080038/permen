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

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    
    if (!$userId) {
        ApiResponse::unauthorized('Autentikasi diperlukan');
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
    
    ApiResponse::success([
        'notifications' => $notifications,
        'unread_count' => (int)$unreadCount
    ], 'Notifications retrieved');
} catch (Exception $e) {
    ApiResponse::serverError('Terjadi kesalahan server');
}
