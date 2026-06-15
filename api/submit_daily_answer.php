<?php
/**
 * API Submit Jawaban Daily Quiz
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
$questionId = (int)($data['question_id'] ?? 0);
$jawaban = strtoupper(trim($data['jawaban'] ?? ''));
$isRagu = (int)($data['is_ragu'] ?? 0);

// Validasi input
if (!$sessionId || !$questionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter tidak lengkap']);
    exit;
}

// Validasi jawaban hanya A-E atau kosong
if ($jawaban && !in_array($jawaban, ['A', 'B', 'C', 'D', 'E'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Jawaban tidak valid']);
    exit;
}

// Verifikasi session milik user
$stmt = $pdo->prepare("SELECT id FROM daily_quiz_sessions WHERE id = ? AND user_id = ? AND status = 'berjalan'");
$stmt->execute([$sessionId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Session tidak valid atau sudah selesai']);
    exit;
}

// Simpan jawaban (upsert)
$stmt = $pdo->prepare("
    INSERT INTO daily_quiz_answers (session_id, question_id, jawaban, is_ragu)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE jawaban = VALUES(jawaban), is_ragu = VALUES(is_ragu)
");
$stmt->execute([$sessionId, $questionId, $jawaban ?: null, $isRagu]);

echo json_encode(['success' => true, 'message' => 'Jawaban tersimpan']);
