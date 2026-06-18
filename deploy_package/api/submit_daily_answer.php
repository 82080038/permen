<?php
/**
 * API Submit Jawaban Daily Quiz
 */

require '../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

// Guard: user harus login
if (empty($_SESSION['user_id'])) {
    ApiResponse::unauthorized('Silakan login terlebih dahulu');
}

$userId = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$sessionId = (int)($data['session_id'] ?? 0);
$questionId = (int)($data['question_id'] ?? 0);
$jawaban = strtoupper(trim($data['jawaban'] ?? ''));
$isRagu = (int)($data['is_ragu'] ?? 0);

// Validasi input
if (!$sessionId || !$questionId) {
    ApiResponse::validationError(['session_id' => 'Parameter tidak lengkap', 'question_id' => 'Parameter tidak lengkap'], 'Parameter tidak lengkap');
}

// Validasi jawaban hanya A-E atau kosong
if ($jawaban && !in_array($jawaban, ['A', 'B', 'C', 'D', 'E'])) {
    ApiResponse::validationError(['jawaban' => 'Jawaban tidak valid'], 'Jawaban tidak valid');
}

// Verifikasi session milik user
$stmt = $pdo->prepare("SELECT id FROM daily_quiz_sessions WHERE id = ? AND user_id = ? AND status = 'berjalan'");
$stmt->execute([$sessionId, $userId]);
if (!$stmt->fetch()) {
    ApiResponse::forbidden('Session tidak valid atau sudah selesai');
}

// Simpan jawaban (upsert)
$stmt = $pdo->prepare("
    INSERT INTO daily_quiz_answers (session_id, question_id, jawaban, is_ragu)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE jawaban = VALUES(jawaban), is_ragu = VALUES(is_ragu)
");
$stmt->execute([$sessionId, $questionId, $jawaban ?: null, $isRagu]);

ApiResponse::success([], 'Jawaban tersimpan');