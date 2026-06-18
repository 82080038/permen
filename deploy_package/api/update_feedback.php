<?php
/**
 * API: Update Feedback Status (Admin Only)
 * 
 * Allows admin to update feedback status and add response
 * 
 * @param int $_POST['feedback_id'] - Feedback ID
 * @param string $_POST['status'] - New status: pending, dilihat, diproses, selesai, ditolak
 * @param string $_POST['response'] - Optional admin response message
 * @return JSON { success: boolean, message: string }
 */
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    // Admin check
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Akses ditolak. Hanya admin yang dapat mengupdate feedback.']);
        exit;
    }
    
    $feedbackId = (int)($_POST['feedback_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $response = trim($_POST['response'] ?? '');
    
    if (!$feedbackId) {
        http_response_code(400);
        echo json_encode(['error' => 'Feedback ID diperlukan']);
        exit;
    }
    
    $validStatuses = ['pending', 'dilihat', 'diproses', 'selesai', 'ditolak'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Status tidak valid']);
        exit;
    }
    
    // Check if feedback exists
    $check = $pdo->prepare("SELECT id FROM user_feedback WHERE id = ?");
    $check->execute([$feedbackId]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Feedback tidak ditemukan']);
        exit;
    }
    
    // Update feedback
    $stmt = $pdo->prepare("UPDATE user_feedback SET status = ?, admin_response = ? WHERE id = ?");
    $stmt->execute([$status, $response, $feedbackId]);
    
    echo json_encode(['success' => true, 'message' => 'Feedback berhasil diupdate']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}
