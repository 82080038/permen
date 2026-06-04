<?php
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);
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

$data = json_decode(file_get_contents('php://input'), true);
$sessionId = (int)($data['session_id'] ?? 0);
$currentSubtes = $data['current_subtes'] ?? '';
$nextSubtes = $data['next_subtes'] ?? '';

if (!$sessionId || !$currentSubtes || !$nextSubtes) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
}

// Validasi kepemilikan
$stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE id = ? AND user_id = ? AND status = 'berjalan'");
$stmt->execute([$sessionId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Session tidak valid']);
    exit;
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

echo json_encode(['success' => true, 'next_subtes' => $nextSubtes]);
