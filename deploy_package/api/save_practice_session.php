<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json');

// Guard: only logged in
if (empty($_SESSION['user_id'])) {
    ApiResponse::unauthorized('Not logged in');
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::validationError(['method' => 'Method not allowed'], 'Method not allowed');
}

if (!validateCsrfApi()) {
    ApiResponse::validationError(['csrf' => 'Invalid CSRF token'], 'Invalid CSRF token');
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
    ApiResponse::validationError(['subtes' => 'Invalid subtes'], 'Invalid subtes');
}

$validKesulitan = ['mudah', 'sedang', 'sulit'];
if (!in_array($tingkatKesulitan, $validKesulitan)) {
    ApiResponse::validationError(['tingkat_kesulitan' => 'Invalid difficulty level'], 'Invalid difficulty level');
}

if ($jumlahSoal < 1 || $jumlahSoal > 50) {
    ApiResponse::validationError(['jumlah_soal' => 'Invalid question count'], 'Invalid question count');
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

    ApiResponse::success([], 'Practice session saved');
} catch (Exception $e) {
    ApiResponse::serverError('Failed to save practice session');
}
