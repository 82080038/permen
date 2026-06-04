<?php
/**
 * API: Create Notification (Internal Use)
 * 
 * Creates a notification for a user
 * This is typically called by other APIs to notify users
 * 
 * @param int $userId User ID
 * @param string $type Notification type (info, success, warning, error)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string|null $link Optional link to related page
 * @return bool Success status
 */
require '../config.php';
require '../helpers.php';

function createNotification(int $userId, string $type, string $title, string $message, ?string $link = null): bool {
    global $pdo;
    
    $validTypes = ['info', 'success', 'warning', 'error'];
    if (!in_array($type, $validTypes)) {
        $type = 'info';
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $title, $message, $link]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}
