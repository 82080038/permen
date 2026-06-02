<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$questionId = (int)($input['question_id'] ?? 0);
$action = $input['action'] ?? ''; // 'mark_revised' atau 'toggle_active'

if (!$questionId || !in_array($action, ['mark_revised', 'toggle_active'])) {
    echo json_encode(['error' => 'Parameter tidak valid']);
    exit;
}

if ($action === 'mark_revised') {
    $upd = $pdo->prepare("UPDATE questions SET needs_revision = 0, revision_status = 'revised' WHERE id = ?");
    $upd->execute([$questionId]);
    echo json_encode(['success' => true, 'message' => 'Soal ditandai sudah direvisi']);
} elseif ($action === 'toggle_active') {
    $upd = $pdo->prepare("UPDATE questions SET is_active = NOT is_active WHERE id = ?");
    $upd->execute([$questionId]);
    $newStatus = $pdo->query("SELECT is_active FROM questions WHERE id = $questionId")->fetchColumn();
    echo json_encode(['success' => true, 'is_active' => (int)$newStatus]);
}
