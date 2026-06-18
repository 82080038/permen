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

// Skip rate limiting in development
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    // Skip rate limiting for testing
} else {
    // API rate limiting
    $identifier = "user_$userId";
    if (!checkAPIRateLimit($identifier, 'finish_tryout', 10, 60)) {
        ApiResponse::rateLimit('Rate limit exceeded. Please try again later.');
    }
    logAPIRequest($identifier, 'finish_tryout');
}

$data = json_decode(file_get_contents('php://input'), true);
$sessionId = (int)($data['session_id'] ?? 0);

if (!$sessionId) {
    ApiResponse::validationError(['session_id' => 'Session ID diperlukan'], 'Session ID diperlukan');
}

// Validasi kepemilikan session
$stmt = $pdo->prepare("SELECT id, UNIX_TIMESTAMP(waktu_mulai) as start_ts FROM tryout_sessions WHERE id = ? AND user_id = ? AND status = 'berjalan'");
$stmt->execute([$sessionId, $userId]);
$session = $stmt->fetch();
if (!$session) {
    ApiResponse::forbidden('Session tidak ditemukan, sudah selesai, atau bukan milik Anda');
}

// Anti-cheat: validasi waktu per subtes
$subtesStmt = $pdo->prepare("SELECT subtes, durasi_menit, waktu_mulai_subtes FROM session_subtes WHERE session_id = ?");
$subtesStmt->execute([$sessionId]);
$subtesList = $subtesStmt->fetchAll();

$totalElapsed = 0;
foreach ($subtesList as $sub) {
    if ($sub['waktu_mulai_subtes']) {
        $elapsed = time() - strtotime($sub['waktu_mulai_subtes']);
        $maxSeconds = (int)$sub['durasi_menit'] * 60;
        $totalElapsed += min($elapsed, $maxSeconds);
    }
}

// Skip anti-cheat time validation in development
if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
    // Minimum total waktu: jumlah durasi minimum (1 menit per subtes)
    $minSeconds = count($subtesList) * 60;
    if ($totalElapsed < $minSeconds) {
        ApiResponse::error('Waktu terlalu singkat. Mohon selesaikan tryout dengan wajar.', 429);
    }

    // Maximum total waktu: sum durasi + toleransi 5 menit
    $maxTotalSeconds = array_sum(array_column($subtesList, 'durasi_menit')) * 60 + 300;
    if ($totalElapsed > $maxTotalSeconds) {
        ApiResponse::error('Waktu melebihi batas. Hasil tidak valid.', 429);
    }
}

// Hitung nilai per subtes
$stmt = $pdo->prepare("SELECT q.subtes, SUM(a.skor) as total, COUNT(a.id) as jumlah FROM answers a JOIN questions q ON a.question_id = q.id WHERE a.session_id = ? GROUP BY q.subtes");
$stmt->execute([$sessionId]);
$results = $stmt->fetchAll();

$nilai = ['TKP'=>0,'TIU'=>0,'TWK'=>0];
foreach ($results as $r) {
    $nilai[$r['subtes']] = (int)$r['total'];
}
$total = $nilai['TKP'] + $nilai['TIU'] + $nilai['TWK'];

// Update tabel normalisasi session_subtes
$updateSub = $pdo->prepare("UPDATE session_subtes SET nilai = ? WHERE session_id = ? AND subtes = ?");
foreach ($nilai as $sub => $val) {
    $updateSub->execute([$val, $sessionId, $sub]);
}

// Fallback: update juga kolom flat untuk backward compatibility
$stmt = $pdo->prepare("UPDATE tryout_sessions SET nilai_tkp=?, nilai_tiu=?, nilai_twk=?, total_nilai=?, status='selesai', waktu_selesai=NOW() WHERE id=?");
$stmt->execute([$nilai['TKP'], $nilai['TIU'], $nilai['TWK'], $total, $sessionId]);

ApiResponse::success(['nilai' => $nilai, 'total' => $total], 'Tryout selesai berhasil');
