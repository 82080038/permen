<?php
/**
 * API: Get User Feedback List (Admin Only)
 * 
 * Retrieves list of user feedback for admin to review
 * 
 * @param string $_GET['status'] - Optional filter by status
 * @param string $_GET['category'] - Optional filter by category
 * @param int $_GET['limit'] - Pagination limit (default: 20)
 * @param int $_GET['offset'] - Pagination offset (default: 0)
 * @return JSON { feedback: array, total: int }
 */
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

try {
    // Admin check
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        ApiResponse::forbidden('Akses ditolak. Hanya admin yang dapat melihat feedback.');
    }
    
    $status = $_GET['status'] ?? '';
    $category = $_GET['category'] ?? '';
    $limit = min(100, max(10, (int)($_GET['limit'] ?? 20)));
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Build query
    $where = ['1=1'];
    $params = [];
    
    if (!empty($status)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    
    if (!empty($category)) {
        $where[] = 'category = ?';
        $params[] = $category;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM user_feedback WHERE $whereClause";
    $total = $pdo->prepare($countSql);
    $total->execute($params);
    $total = $total->fetchColumn();
    
    // Get feedback list
    $sql = "
        SELECT 
            f.id,
            f.user_id,
            u.nama as user_name,
            u.email as user_email,
            f.category,
            f.message,
            f.status,
            f.admin_response,
            f.created_at,
            f.updated_at
        FROM user_feedback f
        LEFT JOIN users u ON f.user_id = u.id
        WHERE $whereClause
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $feedback = $stmt->fetchAll();
    
    ApiResponse::success([
        'feedback' => $feedback,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset
    ], 'Feedback retrieved');
} catch (Exception $e) {
    ApiResponse::serverError('Terjadi kesalahan server');
}
