<?php
declare(strict_types=1);

/**
 * API: Submit Answer for Tryout Question
 * 
 * Submits a user's answer for a specific question in a tryout session.
 * Enforces server-side timer validation for both total time and per-subtes time.
 * Calculates score based on question type (TKP uses weighted scoring, TIU/TWK use binary scoring).
 * 
 * @param JSON body {
 *   answer_id: int The answer record ID,
 *   jawaban: string The selected answer (A, B, C, D, E),
 *   is_ragu: bool Optional flag for doubtful answer
 * }
 * @return JSON {
 *   success: bool,
 *   message: string,
 *   data: {
 *     skor: int,
 *     is_ragu: bool
 *   }
 * }
 * 
 * HTTP Status Codes:
 * - 401: User not authenticated
 * - 400: Invalid data or answer
 * - 403: Question not found, not owned by user, session finished, or time expired
 * - 429: Rate limit exceeded
 * - 200: Success
 */
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

// Start timing for performance monitoring
$startTime = microtime(true);

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    ApiResponse::unauthorized('Silakan login terlebih dahulu.');
}

// CSRF validation for API endpoints
if (!validateCsrfApi()) {
    ApiResponse::forbidden('Token keamanan tidak valid. Silakan muat ulang halaman.');
}

// API rate limiting
$identifier = "user_$userId";
if (!checkAPIRateLimit($identifier, 'submit_jawaban', 200, 60)) {
    ApiResponse::rateLimit('Terlalu banyak jawaban yang dikirim. Silakan tunggu sebentar.');
}
logAPIRequest($identifier, 'submit_jawaban');

$data = json_decode(file_get_contents('php://input'), true);
$answerId = (int)($data['answer_id'] ?? 0);
$jawaban = $data['jawaban'] ?? '';
$jawaban = strtoupper(substr(trim($jawaban), 0, 1));
$isRagu = (int)($data['is_ragu'] ?? 0);

if (!$answerId || !in_array($jawaban, ['A','B','C','D','E'])) {
    ApiResponse::validationError([
        'answer_id' => !$answerId ? 'ID jawaban diperlukan' : null,
        'jawaban' => !in_array($jawaban, ['A','B','C','D','E']) ? 'Jawaban harus A, B, C, D, atau E' : null
    ], 'Data tidak lengkap atau tidak valid.');
}

// Ambil soal + validasi kepemilikan session
$stmt = $pdo->prepare("SELECT q.id, q.subtes, q.jawaban_benar, q.bobot_tkp, q.bobot_a, q.bobot_b, q.bobot_c, q.bobot_d, q.bobot_e, 
    ts.waktu_mulai, ts.status, ts.id as session_id,
    (SELECT SUM(durasi_menit) FROM session_subtes WHERE session_id = ts.id) as total_durasi_menit
    FROM answers a JOIN questions q ON a.question_id = q.id
    JOIN tryout_sessions ts ON a.session_id = ts.id
    WHERE a.id = ? AND ts.user_id = ?");
$stmt->execute([$answerId, $userId]);
$soal = $stmt->fetch();

if (!$soal) {
    ApiResponse::forbidden('Soal tidak ditemukan atau bukan milik Anda.');
}

// Enforce timer server-side per subtes
if ($soal['status'] !== 'berjalan') {
    ApiResponse::forbidden('Sesi tryout sudah selesai. Jawaban tidak dapat diubah.');
}

// Validate per-subtes time
$subStmt = $pdo->prepare("SELECT waktu_mulai_subtes, durasi_menit FROM session_subtes WHERE session_id = ? AND subtes = ?");
$subStmt->execute([$soal['session_id'], $soal['subtes']]);
$subData = $subStmt->fetch();

if ($subData && $subData['waktu_mulai_subtes']) {
    $elapsedSub = time() - strtotime($subData['waktu_mulai_subtes']);
    $maxSubSeconds = (int)$subData['durasi_menit'] * 60 + 60; // toleransi 60 detik
    if ($elapsedSub > $maxSubSeconds) {
        ApiResponse::forbidden('Waktu subtes ' . $soal['subtes'] . ' sudah habis.');
    }
}

// Also validate total time
$elapsedSeconds = time() - strtotime($soal['waktu_mulai']);
$totalSeconds = (int)$soal['total_durasi_menit'] * 60;
if ($totalSeconds > 0 && $elapsedSeconds > $totalSeconds + 60) {
    ApiResponse::forbidden('Waktu tryout sudah habis.');
}

$skor = 0;
if ($soal['subtes'] === 'TKP') {
    // CAT-BKN TKP: setiap opsi (A-E) memiliki bobot 1-5.
    // Gunakan bobot per pilihan jika tersedia, fallback ke simulasi jarak.
    $bobotKey = 'bobot_' . strtolower($jawaban);
    if (!empty($soal[$bobotKey])) {
        $skor = (int)$soal[$bobotKey];
    } else {
        // Fallback: simulasi jarak dari jawaban benar
        $map = ['A'=>1,'B'=>2,'C'=>3,'D'=>4,'E'=>5];
        $skorJawaban = $map[$jawaban] ?? 1;
        $skorBenar = $map[$soal['jawaban_benar']] ?? 3;
        $diff = abs($skorJawaban - $skorBenar);
        if ($diff == 0) $skor = $soal['bobot_tkp'] ?? 5;
        elseif ($diff == 1) $skor = max(1, ($soal['bobot_tkp'] ?? 5) - 1);
        elseif ($diff == 2) $skor = max(1, ($soal['bobot_tkp'] ?? 5) - 2);
        elseif ($diff == 3) $skor = max(1, ($soal['bobot_tkp'] ?? 5) - 3);
        else $skor = 1;
    }
} else {
    // TIU & TWK: benar/salah (skor maksimal 5)
    $skor = ($jawaban === $soal['jawaban_benar']) ? 5 : 0;
}

// Use database transaction to prevent race conditions
try {
    $pdo->beginTransaction();
    // Try production column name first (jawaban_user), fallback to local (jawaban)
    try {
        $stmt = $pdo->prepare("UPDATE answers SET jawaban_user = ?, skor = ?, is_ragu = ? WHERE id = ?");
        $stmt->execute([$jawaban, $skor, $isRagu, $answerId]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("UPDATE answers SET jawaban = ?, skor = ?, is_ragu = ? WHERE id = ?");
        $stmt->execute([$jawaban, $skor, $isRagu, $answerId]);
    }
    
    // Log answer submission for audit trail
    logUserAction($userId, 'submit_answer', "answer_id=$answerId, session_id={$soal['session_id']}, jawaban=$jawaban, skor=$skor, is_ragu=$isRagu");
    
    $pdo->commit();

    // Calculate response time and log performance
    $responseTimeMs = round((microtime(true) - $startTime) * 1000);
    logApiPerformance('/api/submit_jawaban.php', $responseTimeMs, 200);
    
    ApiResponse::success([
        'skor' => $skor,
        'is_ragu' => $isRagu
    ], 'Jawaban berhasil disimpan.');
    
} catch (Exception $e) {
    $pdo->rollBack();

    // Calculate response time and log performance for error
    $responseTimeMs = round((microtime(true) - $startTime) * 1000);
    logApiPerformance('/api/submit_jawaban.php', $responseTimeMs, 500);
    
    error_log('Submit jawaban error: ' . $e->getMessage());
    ApiResponse::serverError('Gagal menyimpan jawaban. Silakan coba lagi nanti.');
}
