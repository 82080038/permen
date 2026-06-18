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

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    
    if (!$userId) {
        ApiResponse::unauthorized('Autentikasi diperlukan');
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
    
    ApiResponse::success(['feedback' => $feedback], 'Feedback history retrieved');
} catch (Exception $e) {
    ApiResponse::serverError('Terjadi kesalahan server');
}
