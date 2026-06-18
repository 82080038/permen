<?php
declare(strict_types=1);

/**
 * API: Submit User Feedback
 * 
 * Allows users to submit feedback/suggestions/criticism
 * 
 * @param int $_POST['user_id'] - User ID (from session)
 * @param string $_POST['category'] - Category: saran, kritik, bug, fitur, lainnya
 * @param string $_POST['message'] - Feedback message
 * @return JSON { success: boolean, message: string, data: null }
 */
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $category = $_POST['category'] ?? 'lainnya';
    $message = trim($_POST['message'] ?? '');
    
    if (!$userId) {
        ApiResponse::unauthorized('Silakan login terlebih dahulu untuk mengirim feedback.');
    }
    
    // Validation
    $errors = [];
    if (empty($message)) {
        $errors['message'] = 'Pesan feedback diperlukan';
    } elseif (strlen($message) < 10) {
        $errors['message'] = 'Pesan feedback minimal 10 karakter';
    } elseif (strlen($message) > 1000) {
        $errors['message'] = 'Pesan feedback maksimal 1000 karakter';
    }
    
    if (!empty($errors)) {
        ApiResponse::validationError($errors, 'Silakan periksa input Anda.');
    }
    
    $validCategories = ['saran', 'kritik', 'bug', 'fitur', 'lainnya'];
    if (!in_array($category, $validCategories)) {
        $category = 'lainnya';
    }
    
    $stmt = $pdo->prepare("INSERT INTO user_feedback (user_id, category, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $category, $message]);
    
    ApiResponse::success(null, 'Feedback berhasil dikirim. Terima kasih atas masukan Anda!');
    
} catch (Exception $e) {
    error_log('Feedback submission error: ' . $e->getMessage());
    ApiResponse::serverError('Gagal mengirim feedback. Silakan coba lagi nanti.');
}
