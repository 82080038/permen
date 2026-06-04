<?php
/**
 * API: Get Current User's Feedback History
 * 
 * Retrieves feedback submitted by the current user
 * 
 * @return JSON { feedback: array }
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
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            category,
            message,
            status,
            admin_response,
            created_at,
            updated_at
        FROM user_feedback
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    $feedback = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'feedback' => $feedback]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}
