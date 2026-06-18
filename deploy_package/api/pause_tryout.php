<?php
/**
 * API: Pause Tryout Session
 * 
 * Pauses a tryout session with server-side timer validation.
 * Max 3 pauses allowed, max 10 minutes total pause time.
 * 
 * @param JSON body {
 *   session_id: int The tryout session ID
 * }
 * @return JSON {
 *   success: bool,
 *   pause_count: int,
 *   remaining_pause_seconds: int
 * }
 * 
 * HTTP Status Codes:
 * - 401: User not authenticated
 * - 400: Invalid data
 * - 403: Session not found, not owned, already paused, or limits exceeded
 * - 200: Success
 */
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Autentikasi diperlukan']);
    exit;
}

// CSRF validation
if (!validateCsrfApi()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token tidak valid']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$sessionId = (int)($data['session_id'] ?? 0);

if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Session ID diperlukan']);
    exit;
}

// Get session data
$stmt = $pdo->prepare("SELECT id, user_id, status, pause_count, total_pause_seconds, is_paused, paused_at FROM tryout_sessions WHERE id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session || $session['user_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Session tidak ditemukan atau bukan milik Anda']);
    exit;
}

if ($session['status'] !== 'berjalan') {
    http_response_code(403);
    echo json_encode(['error' => 'Session tidak sedang berjalan']);
    exit;
}

if ($session['is_paused']) {
    http_response_code(403);
    echo json_encode(['error' => 'Session sudah di-pause']);
    exit;
}

// Check limits
$maxPauses = 3;
$maxPauseSeconds = 600; // 10 minutes

if ($session['pause_count'] >= $maxPauses) {
    http_response_code(403);
    echo json_encode(['error' => 'Batas maksimal pause tercapai (' . $maxPauses . ' kali)']);
    exit;
}

if ($session['total_pause_seconds'] >= $maxPauseSeconds) {
    http_response_code(403);
    echo json_encode(['error' => 'Batas maksimal waktu pause tercapai (' . ($maxPauseSeconds / 60) . ' menit)']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Update session to paused state
    $stmt = $pdo->prepare("UPDATE tryout_sessions SET is_paused = 1, paused_at = NOW(), pause_count = pause_count + 1 WHERE id = ?");
    $stmt->execute([$sessionId]);
    
    // Log action
    logUserAction($userId, 'pause_tryout', "session_id=$sessionId, pause_count=" . ($session['pause_count'] + 1));
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'pause_count' => $session['pause_count'] + 1,
        'remaining_pause_seconds' => $maxPauseSeconds - $session['total_pause_seconds']
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Gagal pause tryout: ' . $e->getMessage()]);
    exit;
}
