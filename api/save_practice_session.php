<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: only logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!validateCsrfApi()) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$subtes = $_POST['subtes'] ?? '';
$topik = $_POST['topik'] ?? '';
$jumlahSoal = (int)($_POST['jumlah_soal'] ?? 0);
$tingkatKesulitan = $_POST['tingkat_kesulitan'] ?? 'sedang';
$benar = (int)($_POST['benar'] ?? 0);
$salah = (int)($_POST['salah'] ?? 0);
$skor = (int)($_POST['skor'] ?? 0);
$timerMode = $_POST['timer_mode'] ?? 'none';
$timerUsed = (int)($_POST['timer_used'] ?? 0);

// Validasi
$validSubtes = ['TWK', 'TIU', 'TKP'];
if (!in_array($subtes, $validSubtes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid subtes']);
    exit;
}

$validKesulitan = ['mudah', 'sedang', 'sulit'];
if (!in_array($tingkatKesulitan, $validKesulitan)) {
    echo json_encode(['success' => false, 'error' => 'Invalid difficulty level']);
    exit;
}

if ($jumlahSoal < 1 || $jumlahSoal > 50) {
    echo json_encode(['success' => false, 'error' => 'Invalid question count']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO personal_practice_sessions 
        (user_id, subtes, topik, jumlah_soal, tingkat_kesulitan, benar, salah, skor, timer_mode, timer_used_seconds, waktu_mulai, waktu_selesai)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $userId,
        $subtes,
        $topik ?: null,
        $jumlahSoal,
        $tingkatKesulitan,
        $benar,
        $salah,
        $skor,
        $timerMode,
        $timerUsed
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save practice session']);
}
