<?php
/**
 * API: Verify OTP and reset password
 * 
 * @param string $_POST['no_hp'] - User phone number
 * @param string $_POST['otp'] - 6-digit OTP code
 * @param string $_POST['new_password'] - New password
 */
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!validateCsrfApi()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token tidak valid']);
    exit;
}

$noHp = sanitizeInput($_POST['no_hp'] ?? '');
$otp = sanitizeInput($_POST['otp'] ?? '');
$newPassword = $_POST['new_password'] ?? '';

if (!$noHp || !$otp || !$newPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'Nomor HP, OTP, dan password baru diperlukan']);
    exit;
}

// Validate password strength
$pwdValidation = validatePasswordStrength($newPassword);
if (!$pwdValidation['valid']) {
    http_response_code(400);
    echo json_encode(['error' => $pwdValidation['error']]);
    exit;
}

// Find user
$stmt = $pdo->prepare("SELECT id FROM users WHERE no_hp = ? AND role = 'user' AND status = 'active'");
$stmt->execute([$noHp]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User tidak ditemukan']);
    exit;
}

// Verify OTP
try {
    $stmt = $pdo->prepare("SELECT id, otp_code, expires_at FROM password_reset_requests WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user['id']]);
    $resetReq = $stmt->fetch();
    
    if (!$resetReq || !$resetReq['otp_code']) {
        http_response_code(400);
        echo json_encode(['error' => 'Tidak ada permintaan OTP aktif. Silakan request OTP baru.']);
        exit;
    }
    
    if ($resetReq['otp_code'] !== $otp) {
        http_response_code(400);
        echo json_encode(['error' => 'Kode OTP tidak valid']);
        exit;
    }
    
    if (strtotime($resetReq['expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['error' => 'Kode OTP telah kedaluwarsa. Silakan request OTP baru.']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}

// Update password
$hash = password_hash($newPassword, PASSWORD_BCRYPT);
try {
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, failed_attempts = 0, lockout_until = NULL WHERE id = ?");
    $stmt->execute([$hash, $user['id']]);
} catch (PDOException $e) {
    $stmt = $pdo->prepare("UPDATE users SET password = ?, failed_attempts = 0, lockout_until = NULL WHERE id = ?");
    $stmt->execute([$hash, $user['id']]);
}

// Mark OTP as used
$stmt = $pdo->prepare("UPDATE password_reset_requests SET status = 'completed' WHERE id = ?");
$stmt->execute([$resetReq['id']]);

echo json_encode(['success' => true, 'message' => 'Password berhasil diubah. Silakan login dengan password baru.']);
