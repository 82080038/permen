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

if (!$materiId) {
    echo json_encode(['success' => false, 'error' => 'Invalid materi ID']);
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

    // Check if already bookmarked
    $stmt = $pdo->prepare("SELECT id FROM materi_bookmarks WHERE user_id = ? AND materi_id = ?");
    $stmt->execute([$userId, $materiId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove bookmark
        $stmt = $pdo->prepare("DELETE FROM materi_bookmarks WHERE user_id = ? AND materi_id = ?");
        $stmt->execute([$userId, $materiId]);
        echo json_encode(['success' => true, 'bookmarked' => false]);
    } else {
        // Add bookmark
        $stmt = $pdo->prepare("INSERT INTO materi_bookmarks (user_id, materi_id) VALUES (?, ?)");
        $stmt->execute([$userId, $materiId]);
        echo json_encode(['success' => true, 'bookmarked' => true]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to toggle bookmark']);
}
