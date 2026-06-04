<?php
/**
 * API: Submit User Feedback
 * 
 * Allows users to submit feedback/suggestions/criticism
 * 
 * @param int $_POST['user_id'] - User ID (from session)
 * @param string $_POST['category'] - Category: saran, kritik, bug, fitur, lainnya
 * @param string $_POST['message'] - Feedback message
 * @return JSON { success: boolean, message: string }
 */
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $category = $_POST['category'] ?? 'lainnya';
    $message = trim($_POST['message'] ?? '');
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentikasi diperlukan']);
        exit;
    }
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Pesan feedback diperlukan']);
        exit;
    }
    
    if (strlen($message) < 10) {
        http_response_code(400);
        echo json_encode(['error' => 'Pesan feedback minimal 10 karakter']);
        exit;
    }
    
    if (strlen($message) > 1000) {
        http_response_code(400);
        echo json_encode(['error' => 'Pesan feedback maksimal 1000 karakter']);
        exit;
    }
    
    $validCategories = ['saran', 'kritik', 'bug', 'fitur', 'lainnya'];
    if (!in_array($category, $validCategories)) {
        $category = 'lainnya';
    }
    
    $stmt = $pdo->prepare("INSERT INTO user_feedback (user_id, category, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $category, $message]);
    
    echo json_encode(['success' => true, 'message' => 'Feedback berhasil dikirim. Terima kasih atas masukan Anda!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}
