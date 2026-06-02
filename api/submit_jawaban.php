<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Autentikasi diperlukan']);
    exit;
}

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
$stmt = $pdo->prepare("SELECT q.*, ts.waktu_mulai, ts.status,
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
if ($totalSeconds > 0 && $elapsedSeconds > $totalSeconds + 300) { // toleransi 5 menit total
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

$stmt = $pdo->prepare("UPDATE answers SET jawaban_user = ?, skor = ? WHERE id = ?");
$stmt->execute([$jawaban, $skor, $answerId]);

echo json_encode(['success' => true, 'skor' => $skor]);
