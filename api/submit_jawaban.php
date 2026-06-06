<?php
/**
 * API: Submit Answer for Tryout Question
 * 
 * Submits a user's answer for a specific question in a tryout session.
 * Enforces server-side timer validation for both total time and per-subtes time.
 * Calculates score based on question type (TKP uses weighted scoring, TIU/TWK use binary scoring).
 * 
 * @param JSON body {
 *   answer_id: int The answer record ID,
 *   jawaban: string The selected answer (A, B, C, D, E)
 * }
 * @return JSON {
 *   success: bool,
 *   skor: int Calculated score (0-5 for TIU/TWK, 1-5 for TKP)
 * }
 * 
 * HTTP Status Codes:
 * - 401: User not authenticated
 * - 400: Invalid data or answer
 * - 403: Question not found, not owned by user, session finished, or time expired
 * - 200: Success
 */
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Autentikasi diperlukan']);
    exit;
}

// CSRF validation for API endpoints
if (!validateCsrfApi()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token tidak valid']);
    exit;
}

// API rate limiting
$identifier = "user_$userId";
if (!checkAPIRateLimit($identifier, 'submit_jawaban', 200, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
    exit;
}
logAPIRequest($identifier, 'submit_jawaban');

$data = json_decode(file_get_contents('php://input'), true);
$answerId = (int)($data['answer_id'] ?? 0);
$jawaban = $data['jawaban'] ?? '';
$jawaban = strtoupper(substr(trim($jawaban), 0, 1));

if (!$answerId || !in_array($jawaban, ['A','B','C','D','E'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap atau jawaban tidak valid']);
    exit;
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
    http_response_code(403);
    echo json_encode(['error' => 'Soal tidak ditemukan atau bukan milik Anda']);
    exit;
}

// Enforce timer server-side per subtes
if ($soal['status'] !== 'berjalan') {
    http_response_code(403);
    echo json_encode(['error' => 'Session sudah selesai. Jawaban tidak dapat diubah.']);
    exit;
}

// Validate per-subtes time
$subStmt = $pdo->prepare("SELECT waktu_mulai_subtes, durasi_menit FROM session_subtes WHERE session_id = ? AND subtes = ?");
$subStmt->execute([$soal['session_id'], $soal['subtes']]);
$subData = $subStmt->fetch();

if ($subData && $subData['waktu_mulai_subtes']) {
    $elapsedSub = time() - strtotime($subData['waktu_mulai_subtes']);
    $maxSubSeconds = (int)$subData['durasi_menit'] * 60 + 60; // toleransi 60 detik
    if ($elapsedSub > $maxSubSeconds) {
        http_response_code(403);
        echo json_encode(['error' => 'Waktu subtes ' . $soal['subtes'] . ' sudah habis.']);
        exit;
    }
}

// Also validate total time
$elapsedSeconds = time() - strtotime($soal['waktu_mulai']);
$totalSeconds = (int)$soal['total_durasi_menit'] * 60;
if ($totalSeconds > 0 && $elapsedSeconds > $totalSeconds + 60) { // toleransi 1 menit total (tightened from 5)
    http_response_code(403);
    echo json_encode(['error' => 'Waktu tryout sudah habis.']);
    exit;
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
    $stmt = $pdo->prepare("UPDATE answers SET jawaban_user = ?, skor = ? WHERE id = ?");
    $stmt->execute([$jawaban, $skor, $answerId]);
    
    // Log answer submission for audit trail
    logUserAction($userId, 'submit_answer', "answer_id=$answerId, session_id={$soal['session_id']}, jawaban=$jawaban, skor=$skor");
    
    $pdo->commit();
    echo json_encode(['success' => true, 'skor' => $skor]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyimpan jawaban. Silakan coba lagi.']);
    exit;
}
