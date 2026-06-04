<?php
/**
 * API: Reset User Password (Admin Only)
 * 
 * Allows admin to reset a user's password
 * 
 * @param int $_POST['user_id'] - User ID to reset
 * @return JSON { success: boolean, new_password: string }
 */
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $adminId = (int)($_SESSION['user_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);
    
    // Check admin role
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin || $admin['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Akses ditolak. Hanya admin yang dapat reset password.']);
        exit;
    }
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID diperlukan']);
        exit;
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, nama, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User tidak ditemukan']);
        exit;
    }
    
    // Generate new random password
    $newPassword = generateRandomPassword();
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, failed_attempts = 0, lockout_until = NULL WHERE id = ?");
    $stmt->execute([$hash, $userId]);
    
    // Mark reset request as completed if exists
    $stmt = $pdo->prepare("UPDATE password_reset_requests SET status = 'completed' WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    
    // Notify user via notification
    require '../api/create_notification.php';
    createNotification(
        $userId,
        'success',
        'Password Telah Di-reset',
        "Password Anda telah di-reset oleh admin. Password baru Anda: $newPassword. Silakan login dengan password baru.",
        'login.php'
    );
    
    echo json_encode([
        'success' => true,
        'new_password' => $newPassword,
        'user_name' => $user['nama'],
        'user_email' => $user['email']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}

function generateRandomPassword(): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < 12; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}
