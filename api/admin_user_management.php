<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json');

// Guard: admin only
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    ApiResponse::forbidden('Unauthorized');
}

// CSRF validation
if (!validateCsrfApi()) {
    ApiResponse::forbidden('Token keamanan tidak valid. Silakan muat ulang halaman.');
}

$action = $_POST['action'] ?? '';

if ($action === 'suspend_user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    if (!$userId) {
        ApiResponse::validationError(['user_id' => 'Invalid user ID'], 'Invalid user ID');
    }
    
    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', suspended_at = NOW(), suspended_reason = ? WHERE id = ? AND role != 'admin'");
    $stmt->execute([$reason, $userId]);
    
    if ($stmt->rowCount() === 0) {
        ApiResponse::error('Tidak bisa suspend akun admin atau user tidak ditemukan.', 403);
    }

    // Log action
    $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'suspended', ?, ?)");
    $stmt->execute([$userId, "Suspended by admin. Reason: $reason", $_SERVER['REMOTE_ADDR'] ?? '']);
    
    ApiResponse::success([], 'User suspended successfully');
    
} elseif ($action === 'ban_user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    if (!$userId) {
        echo json_encode(['error' => 'Invalid user ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE users SET status = 'banned', suspended_at = NOW(), suspended_reason = ? WHERE id = ? AND role != 'admin'");
    $stmt->execute([$reason, $userId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['error' => 'Tidak bisa ban akun admin atau user tidak ditemukan.']);
        exit;
    }

    // Log action
    $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'banned', ?, ?)");
    $stmt->execute([$userId, "Banned by admin. Reason: $reason", $_SERVER['REMOTE_ADDR'] ?? '']);
    
    echo json_encode(['success' => true, 'message' => 'User banned successfully']);
    
} elseif ($action === 'activate_user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if (!$userId) {
        ApiResponse::validationError(['user_id' => 'Invalid user ID'], 'Invalid user ID');
    }
    
    $stmt = $pdo->prepare("UPDATE users SET status = 'active', suspended_at = NULL, suspended_reason = NULL WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Log action
    $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'activated', 'Activated by admin', ?)");
    $stmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    ApiResponse::success([], 'User activated successfully');
    
} elseif ($action === 'delete_user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if (!$userId) {
        ApiResponse::validationError(['user_id' => 'Invalid user ID'], 'Invalid user ID');
    }
    
    // Soft delete by setting status to banned
    $stmt = $pdo->prepare("UPDATE users SET status = 'banned', suspended_at = NOW(), suspended_reason = 'Deleted by admin' WHERE id = ? AND role != 'admin'");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() === 0) {
        ApiResponse::error('Tidak bisa menghapus akun admin atau user tidak ditemukan.', 403);
    }
    
    // Log action
    $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'deleted', 'Deleted by admin', ?)");
    $stmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    ApiResponse::success([], 'User deleted successfully');
    
} elseif ($action === 'reset_password') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    
    if (!$userId || !$newPassword) {
        ApiResponse::validationError(['user_id' => 'Invalid parameters', 'new_password' => 'Invalid parameters'], 'Invalid parameters');
    }
    
    $pwdValidation = validatePasswordStrength($newPassword);
    if (!$pwdValidation['valid']) {
        ApiResponse::validationError(['password' => $pwdValidation['error']], $pwdValidation['error']);
    }
    
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $userId]);
    
    // Log action
    $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'password_reset', 'Password reset by admin', ?)");
    $stmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    ApiResponse::success([], 'Password reset successfully');
    
} elseif ($action === 'edit_user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $nama = sanitizeInput($_POST['nama'] ?? '');
    $noHp = sanitizeInput($_POST['no_hp'] ?? '');
    $sekolahAsal = sanitizeInput($_POST['sekolah_asal'] ?? '');
    $tahunTamat = (int)($_POST['tahun_tamat'] ?? 0);
    $instansiId = (int)($_POST['instansi_id'] ?? 0);
    
    if (!$userId || !$nama || !$noHp) {
        echo json_encode(['error' => 'Nama dan No HP wajib diisi']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE users SET nama = ?, no_hp = ?, sekolah_asal = ?, tahun_tamat = ?, instansi_id = ? WHERE id = ?");
    $stmt->execute([$nama, $noHp, $sekolahAsal ?: null, $tahunTamat ?: null, $instansiId ?: null, $userId]);
    
    // Log action
    $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'profile_updated', 'Profile updated by admin', ?)");
    $stmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    
} elseif ($action === 'get_user_activity') {
    $userId = (int)($_GET['user_id'] ?? 0);
    
    if (!$userId) {
        ApiResponse::validationError(['user_id' => 'Invalid user ID'], 'Invalid user ID');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM user_activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$userId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success(['activities' => $activities], 'User activity retrieved');
    
} else {
    ApiResponse::validationError(['action' => 'Invalid action'], 'Invalid action');
}
