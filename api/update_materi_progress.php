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

$materiId = (int)($_POST['materi_id'] ?? 0);
$progressPercent = (int)($_POST['progress_percent'] ?? 0);
$lastPosition = (int)($_POST['last_position'] ?? 0);

if (!$materiId) {
    echo json_encode(['success' => false, 'error' => 'Invalid materi ID']);
    exit;
}

// Validate progress percentage
if ($progressPercent < 0 || $progressPercent > 100) {
    echo json_encode(['success' => false, 'error' => 'Progress percentage must be between 0 and 100']);
    exit;
}

try {
    // Check if materi exists
    $stmt = $pdo->prepare("SELECT id FROM materi WHERE id = ?");
    $stmt->execute([$materiId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Material not found']);
        exit;
    }

    // Check if progress record exists
    $stmt = $pdo->prepare("SELECT id FROM materi_progress WHERE user_id = ? AND materi_id = ?");
    $stmt->execute([$userId, $materiId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update progress
        $stmt = $pdo->prepare("UPDATE materi_progress SET progress_percent = ?, last_position = ?, last_read_at = NOW() WHERE user_id = ? AND materi_id = ?");
        $stmt->execute([$progressPercent, $lastPosition, $userId, $materiId]);
    } else {
        // Insert new progress
        $stmt = $pdo->prepare("INSERT INTO materi_progress (user_id, materi_id, progress_percent, last_position) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $materiId, $progressPercent, $lastPosition]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update progress']);
}
