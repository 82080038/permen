<?php
/**
 * API Finish Daily Quiz - Hitung hasil dan simpan
 */

require '../config.php';
header('Content-Type: application/json; charset=utf-8');

// Guard: user harus login
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$sessionId = (int)($data['session_id'] ?? 0);

if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Session ID diperlukan']);
    exit;
}

// Verifikasi session milik user
$stmt = $pdo->prepare("SELECT * FROM daily_quiz_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$sessionId, $userId]);
$session = $stmt->fetch();

if (!$session) {
    http_response_code(403);
    echo json_encode(['error' => 'Session tidak ditemukan']);
    exit;
}

if ($session['status'] === 'selesai') {
    echo json_encode(['success' => true, 'message' => 'Quiz sudah selesai sebelumnya']);
    exit;
}

// Hitung hasil
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN dqa.jawaban_user IS NOT NULL THEN 1 ELSE 0 END) as answered,
        SUM(CASE WHEN dqa.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar,
        SUM(CASE WHEN dqa.jawaban_user IS NOT NULL AND dqa.jawaban_user != q.jawaban_benar THEN 1 ELSE 0 END) as salah,
        SUM(CASE WHEN q.subtes = 'TKP' AND dqa.jawaban_user = q.jawaban_benar THEN q.bobot_tkp ELSE 0 END) as nilai_tkp,
        SUM(CASE WHEN q.subtes = 'TIU' AND dqa.jawaban_user = q.jawaban_benar THEN 5 ELSE 0 END) as nilai_tiu,
        SUM(CASE WHEN q.subtes = 'TWK' AND dqa.jawaban_user = q.jawaban_benar THEN 5 ELSE 0 END) as nilai_twk
    FROM daily_quiz_questions dq
    JOIN questions q ON dq.question_id = q.id
    LEFT JOIN daily_quiz_answers dqa ON dqa.session_id = dq.session_id AND dqa.question_id = dq.question_id
    WHERE dq.session_id = ?
");
$stmt->execute([$sessionId]);
$hasil = $stmt->fetch();

$totalSoal = (int)$hasil['total'];
$answered = (int)$hasil['answered'];
$benar = (int)$hasil['benar'];
$salah = (int)$hasil['salah'];
$kosong = $totalSoal - $answered;
$nilaiTkp = (int)($hasil['nilai_tkp'] ?? 0);
$nilaiTiu = (int)($hasil['nilai_tiu'] ?? 0);
$nilaiTwk = (int)($hasil['nilai_twk'] ?? 0);
$nilaiTotal = $nilaiTkp + $nilaiTiu + $nilaiTwk;

// Update session
$stmt = $pdo->prepare("
    UPDATE daily_quiz_sessions 
    SET status = 'selesai', waktu_selesai = NOW(),
        benar = ?, salah = ?, kosong = ?, nilai_total = ?
    WHERE id = ?
");
$stmt->execute([$benar, $salah, $kosong, $nilaiTotal, $sessionId]);

echo json_encode([
    'success' => true,
    'hasil' => [
        'total_soal' => $totalSoal,
        'dijawab' => $answered,
        'benar' => $benar,
        'salah' => $salah,
        'kosong' => $kosong,
        'nilai_twk' => $nilaiTwk,
        'nilai_tiu' => $nilaiTiu,
        'nilai_tkp' => $nilaiTkp,
        'nilai_total' => $nilaiTotal
    ]
]);
