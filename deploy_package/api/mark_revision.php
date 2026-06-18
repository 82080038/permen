<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

// Guard: logged-in user
if (empty($_SESSION['user_id'])) {
    ApiResponse::unauthorized('Login diperlukan');
}

// CSRF validation
if (!validateCsrfApi()) {
    ApiResponse::forbidden('CSRF token tidak valid');
}

$input = json_decode(file_get_contents('php://input'), true);
$questionId = (int)($input['question_id'] ?? 0);
$needsRevision = (int)($input['needs_revision'] ?? 0);

if (!$questionId) {
    ApiResponse::validationError(['question_id' => 'question_id diperlukan'], 'question_id diperlukan');
}

$upd = $pdo->prepare("UPDATE questions SET needs_revision = ? WHERE id = ?");
$upd->execute([$needsRevision, $questionId]);

ApiResponse::success(['question_id' => $questionId, 'needs_revision' => $needsRevision], 'Revision marked');
