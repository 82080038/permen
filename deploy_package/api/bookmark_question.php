<?php
/**
 * API: Bookmark/Unbookmark Question
 * 
 * Adds or removes a question from user's bookmarks
 * 
 * @param int $_POST['question_id'] The question ID to bookmark/unbookmark
 * @param string $_POST['action'] - 'add' or 'remove'
 * @return JSON { success: boolean, bookmarked: boolean }
 */
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $questionId = (int)($_POST['question_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!$userId) {
        ApiResponse::unauthorized('Autentikasi diperlukan');
    }

    // CSRF validation
    if (!validateCsrfApi()) {
        ApiResponse::forbidden('CSRF token tidak valid');
    }
    
    if (!$questionId) {
        ApiResponse::validationError(['question_id' => 'Question ID diperlukan'], 'Question ID diperlukan');
    }
    
    if (!in_array($action, ['add', 'remove'])) {
        ApiResponse::validationError(['action' => 'Action tidak valid'], 'Action tidak valid');
    }
    
    if ($action === 'add') {
        // Add bookmark (ignore if already exists)
        $stmt = $pdo->prepare("INSERT IGNORE INTO question_bookmarks (user_id, question_id) VALUES (?, ?)");
        $stmt->execute([$userId, $questionId]);
        $bookmarked = $stmt->rowCount() > 0;
    } else {
        // Remove bookmark
        $stmt = $pdo->prepare("DELETE FROM question_bookmarks WHERE user_id = ? AND question_id = ?");
        $stmt->execute([$userId, $questionId]);
        $bookmarked = false;
    }
    
    ApiResponse::success(['bookmarked' => $action === 'add'], 'Bookmark berhasil diperbarui');
} catch (Exception $e) {
    ApiResponse::serverError('Terjadi kesalahan server');
}
