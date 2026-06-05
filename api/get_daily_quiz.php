<?php
/**
 * API Daily Quiz - Ambil soal harian
 * 
 * Setiap user mendapat 10 soal random per hari:
 * - 4 soal TWK
 * - 3 soal TIU  
 * - 3 soal TKP
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
$today = date('Y-m-d');

// Cek apakah sudah ada sesi hari ini
$stmt = $pdo->prepare("SELECT * FROM daily_quiz_sessions WHERE user_id = ? AND quiz_date = ?");
$stmt->execute([$userId, $today]);
$session = $stmt->fetch();

if (!$session) {
    // Buat sesi baru
    $stmt = $pdo->prepare("INSERT INTO daily_quiz_sessions (user_id, quiz_date, total_soal) VALUES (?, ?, 10)");
    $stmt->execute([$userId, $today]);
    $sessionId = $pdo->lastInsertId();
    
    // Ambil soal random: 4 TWK, 3 TIU, 3 TKP
    $soalTWK = $pdo->query("SELECT id FROM questions WHERE subtes = 'TWK' AND is_active = 1 ORDER BY RAND() LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);
    $soalTIU = $pdo->query("SELECT id FROM questions WHERE subtes = 'TIU' AND is_active = 1 ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
    $soalTKP = $pdo->query("SELECT id FROM questions WHERE subtes = 'TKP' AND is_active = 1 ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
    
    $allSoal = array_merge($soalTWK, $soalTIU, $soalTKP);
    
    // Insert ke daily_quiz_questions
    $stmtInsert = $pdo->prepare("INSERT INTO daily_quiz_questions (session_id, question_id, subtes, urutan) VALUES (?, ?, ?, ?)");
    foreach ($allSoal as $i => $qid) {
        $subtes = ($i < 4) ? 'TWK' : (($i < 7) ? 'TIU' : 'TKP');
        $stmtInsert->execute([$sessionId, $qid, $subtes, $i + 1]);
    }
    
    $session = [
        'id' => $sessionId,
        'quiz_date' => $today,
        'status' => 'berjalan',
        'total_soal' => 10
    ];
} else {
    $sessionId = $session['id'];
}

// Ambil semua soal dengan detail lengkap
$stmt = $pdo->prepare("
    SELECT dq.id as dq_id, dq.question_id, dq.subtes, dq.urutan,
           q.pertanyaan, q.pilihan_a, q.pilihan_b, q.pilihan_c, q.pilihan_d, q.pilihan_e,
           q.jawaban_benar, q.bobot_tkp, q.pembahasan, q.image_url,
           dqa.jawaban_user, dqa.is_ragu
    FROM daily_quiz_questions dq
    JOIN questions q ON dq.question_id = q.id
    LEFT JOIN daily_quiz_answers dqa ON dqa.session_id = dq.session_id AND dqa.question_id = dq.question_id
    WHERE dq.session_id = ?
    ORDER BY dq.urutan
");
$stmt->execute([$sessionId]);
$soal = $stmt->fetchAll();

// Hitung progress
$answered = count(array_filter($soal, fn($s) => $s['jawaban_user'] !== null));
$marked = count(array_filter($soal, fn($s) => $s['is_ragu']));

echo json_encode([
    'success' => true,
    'session' => [
        'id' => $sessionId,
        'date' => $today,
        'status' => $session['status'],
        'total_soal' => (int)$session['total_soal'],
        'dijawab' => $answered,
        'ragu_ragu' => $marked
    ],
    'soal' => array_map(fn($s) => [
        'dq_id' => (int)$s['dq_id'],
        'question_id' => (int)$s['question_id'],
        'urutan' => (int)$s['urutan'],
        'subtes' => $s['subtes'],
        'pertanyaan' => $s['pertanyaan'],
        'pilihan_a' => $s['pilihan_a'],
        'pilihan_b' => $s['pilihan_b'],
        'pilihan_c' => $s['pilihan_c'],
        'pilihan_d' => $s['pilihan_d'],
        'pilihan_e' => $s['pilihan_e'],
        'image_url' => $s['image_url'],
        'jawaban_user' => $s['jawaban_user'],
        'is_ragu' => (bool)$s['is_ragu']
    ], $soal)
]);
