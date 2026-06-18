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

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    
    if (!$userId) {
        ApiResponse::unauthorized('Autentikasi diperlukan');
    }
    
    if (!$notificationId) {
        ApiResponse::validationError(['notification_id' => 'Notification ID diperlukan'], 'Notification ID diperlukan');
    }
    
    // Verify ownership
    $check = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    $check->execute([$notificationId, $userId]);
    if (!$check->fetch()) {
        ApiResponse::notFound('Notification tidak ditemukan');
    }
    
    // Mark as read
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
    $stmt->execute([$notificationId]);
    
    ApiResponse::success([], 'Notification marked as read');
} catch (Exception $e) {
    ApiResponse::serverError('Terjadi kesalahan server');
}
