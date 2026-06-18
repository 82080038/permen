<?php
/**
 * API: Resume Tryout Session
 * 
 * Resumes a paused tryout session with server-side timer validation.
 * Calculates elapsed pause time and updates total pause seconds.
 * 
 * @param JSON body {
 *   session_id: int The tryout session ID
 * }
 * @return JSON {
 *   success: bool,
 *   pause_duration: int (seconds paused),
 *   total_pause_seconds: int
 * }
 * 
 * HTTP Status Codes:
 * - 401: User not authenticated
 * - 400: Invalid data
 * - 403: Session not found, not owned, not paused, or limits exceeded
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
$stmt = $pdo->prepare("SELECT id, user_id, status, total_pause_seconds, is_paused, paused_at FROM tryout_sessions WHERE id = ?");
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

if (!$session['is_paused']) {
    http_response_code(403);
    echo json_encode(['error' => 'Session tidak sedang di-pause']);
    exit;
}

// Calculate pause duration
$pausedAt = strtotime($session['paused_at']);
$now = time();
$pauseDuration = $now - $pausedAt;

// Check if within 10 minute limit
$maxPauseSeconds = 600;
$newTotalPauseSeconds = $session['total_pause_seconds'] + $pauseDuration;

if ($newTotalPauseSeconds > $maxPauseSeconds) {
    http_response_code(403);
    echo json_encode(['error' => 'Batas maksimal waktu pause tercapai (' . ($maxPauseSeconds / 60) . ' menit)']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Update session to resume state
    $stmt = $pdo->prepare("UPDATE tryout_sessions SET is_paused = 0, paused_at = NULL, total_pause_seconds = ? WHERE id = ?");
    $stmt->execute([$newTotalPauseSeconds, $sessionId]);
    
    // Extend waktu_mulai by pause duration to adjust timer
    $stmt = $pdo->prepare("UPDATE tryout_sessions SET waktu_mulai = DATE_ADD(waktu_mulai, INTERVAL ? SECOND) WHERE id = ?");
    $stmt->execute([$pauseDuration, $sessionId]);
    
    // Log action
    logUserAction($userId, 'resume_tryout', "session_id=$sessionId, pause_duration=$pauseDuration, total_pause_seconds=$newTotalPauseSeconds");
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'pause_duration' => $pauseDuration,
        'total_pause_seconds' => $newTotalPauseSeconds
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Gagal resume tryout: ' . $e->getMessage()]);
    exit;
}
