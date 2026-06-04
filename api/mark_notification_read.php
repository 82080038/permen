<?php
/**
 * API: Mark Notification as Read
 * 
 * Marks a notification as read for the current user
 * 
 * @param int $_POST['notification_id'] - Notification ID
 * @return JSON { success: boolean }
 */
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentikasi diperlukan']);
        exit;
    }
    
    if (!$notificationId) {
        http_response_code(400);
        echo json_encode(['error' => 'Notification ID diperlukan']);
        exit;
    }
    
    // Verify ownership
    $check = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    $check->execute([$notificationId, $userId]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Notification tidak ditemukan']);
        exit;
    }
    
    // Mark as read
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
    $stmt->execute([$notificationId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}
