<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    ApiResponse::unauthorized('Autentikasi diperlukan');
}

// CSRF validation
if (!validateCsrfApi()) {
    ApiResponse::forbidden('CSRF token tidak valid');
}

$data = json_decode(file_get_contents('php://input'), true);
$sessionId = (int)($data['session_id'] ?? 0);
$currentSubtes = $data['current_subtes'] ?? '';
$nextSubtes = $data['next_subtes'] ?? '';

if (!$sessionId || !$currentSubtes || !$nextSubtes) {
    ApiResponse::error('Data tidak lengkap', 400);
}

// Validasi kepemilikan
$stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE id = ? AND user_id = ? AND status = 'berjalan'");
$stmt->execute([$sessionId, $userId]);
if (!$stmt->fetch()) {
    ApiResponse::forbidden('Session tidak valid');
}

// Validasi waktu subtes saat ini
$stmt = $pdo->prepare("SELECT durasi_menit, waktu_mulai_subtes FROM session_subtes WHERE session_id = ? AND subtes = ?");
$stmt->execute([$sessionId, $currentSubtes]);
$current = $stmt->fetch();

if ($current && $current['waktu_mulai_subtes']) {
    $elapsed = time() - strtotime($current['waktu_mulai_subtes']);
    $maxSeconds = (int)$current['durasi_menit'] * 60;
    if ($maxSeconds > 0 && $elapsed > $maxSeconds + 60) { // toleransi 60 detik
        // Auto-submit: waktu subtes habis
        // Tidak reject, tapi catat bahwa waktu habis
    }
}

// Set waktu mulai subtes berikutnya
$stmt = $pdo->prepare("UPDATE session_subtes SET waktu_mulai_subtes = NOW() WHERE session_id = ? AND subtes = ? AND waktu_mulai_subtes IS NULL");
$stmt->execute([$sessionId, $nextSubtes]);

ApiResponse::success(['next_subtes' => $nextSubtes], 'Subtes berikutnya dimulai');
