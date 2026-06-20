<?php
/**
 * API: Request OTP for password reset via WhatsApp
 * Generates 6-digit OTP, stores in DB, sends via WhatsApp
 * 
 * @param string $_POST['no_hp'] - User phone number
 */
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRF validation
if (!validateCsrfApi()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token tidak valid']);
    exit;
}

$noHp = sanitizeInput($_POST['no_hp'] ?? '');

if (!$noHp) {
    http_response_code(400);
    echo json_encode(['error' => 'Nomor HP diperlukan']);
    exit;
}

// Validate phone format
$phoneValidation = validatePhoneNumber($noHp);
if (!$phoneValidation['valid']) {
    http_response_code(400);
    echo json_encode(['error' => $phoneValidation['error']]);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id, nama, no_hp FROM users WHERE no_hp = ? AND role = 'user' AND status = 'active'");
$stmt->execute([$noHp]);
$user = $stmt->fetch();

// Always return success (security: don't reveal if user exists)
if (!$user) {
    echo json_encode(['success' => true, 'message' => 'Jika nomor terdaftar, OTP telah dikirim via WhatsApp.']);
    exit;
}

// Check if WhatsApp API is configured
if (empty($_ENV['FONNTE_API_KEY'])) {
    // Fallback: no WhatsApp, return OTP for admin to communicate
    error_log('[OTP] FONNTE_API_KEY not set, OTP generated but not sent');
    echo json_encode(['success' => true, 'message' => 'WhatsApp API belum dikonfigurasi. Hubungi admin untuk reset password.']);
    exit;
}

require_once __DIR__ . '/../helpers_whatsapp.php';

// Generate 6-digit OTP
$otp = sprintf('%06d', random_int(0, 999999));
$expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes

// Store OTP in password_reset_requests table
try {
    // Invalidate previous OTPs
    $stmt = $pdo->prepare("UPDATE password_reset_requests SET status = 'expired' WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user['id']]);
    
    // Insert new OTP
    $stmt = $pdo->prepare("INSERT INTO password_reset_requests (user_id, otp_code, expires_at, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$user['id'], $otp, $expiresAt]);
} catch (PDOException $e) {
    // Fallback: use a simpler table or log
    error_log('[OTP] DB error: ' . $e->getMessage());
    
    // Try without otp_code column (older schema)
    try {
        $stmt = $pdo->prepare("INSERT INTO password_reset_requests (user_id, status, created_at) VALUES (?, 'pending', NOW())");
        $stmt->execute([$user['id']]);
    } catch (PDOException $e2) {
        error_log('[OTP] Fallback insert failed: ' . $e2->getMessage());
    }
}

// Send OTP via WhatsApp
$result = sendOtpWhatsApp($user['no_hp'], $otp);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'OTP telah dikirim via WhatsApp ke nomor Anda.']);
} else {
    error_log('[OTP] WhatsApp send failed: ' . ($result['error'] ?? 'unknown'));
    echo json_encode(['success' => true, 'message' => 'Jika nomor terdaftar, OTP telah dikirim via WhatsApp.']);
}
