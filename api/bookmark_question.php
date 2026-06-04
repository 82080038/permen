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
header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $questionId = (int)($_POST['question_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentikasi diperlukan']);
        exit;
    }

    // CSRF validation
    if (!validateCsrfApi()) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token tidak valid']);
        exit;
    }
    
    if (!$questionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Question ID diperlukan']);
        exit;
    }
    
    if (!in_array($action, ['add', 'remove'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Action tidak valid']);
        exit;
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
    
    echo json_encode(['success' => true, 'bookmarked' => $action === 'add']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}
