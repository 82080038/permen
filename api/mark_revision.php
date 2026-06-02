<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

// Guard: logged-in user
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Login diperlukan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$questionId = (int)($input['question_id'] ?? 0);
$needsRevision = (int)($input['needs_revision'] ?? 0);

if (!$questionId) {
    echo json_encode(['error' => 'question_id diperlukan']);
    exit;
}

$upd = $pdo->prepare("UPDATE questions SET needs_revision = ? WHERE id = ?");
$upd->execute([$needsRevision, $questionId]);

echo json_encode(['success' => true, 'question_id' => $questionId, 'needs_revision' => $needsRevision]);
