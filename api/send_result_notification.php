<?php
/**
 * API: Send Tryout Results via In-App Notification
 * 
 * Sends tryout results to user via in-app notification (replaces email)
 * 
 * @param int $_POST['session_id'] The session ID to send
 * @return JSON { success: boolean, message: string }
 */
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $sessionId = (int)($_POST['session_id'] ?? 0);
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentikasi diperlukan']);
        exit;
    }
    
    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID diperlukan']);
        exit;
    }
    
    // Get user info
    $stmt = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(400);
        echo json_encode(['error' => 'User tidak ditemukan']);
        exit;
    }
    
    // Get session data
    $stmt = $pdo->prepare("SELECT * FROM tryout_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$sessionId, $userId]);
    $session = $stmt->fetch();
    
    if (!$session) {
        http_response_code(404);
        echo json_encode(['error' => 'Sesi tidak ditemukan']);
        exit;
    }
    
    // Get subtes data
    $stmt = $pdo->prepare("SELECT subtes, nilai, passing_grade FROM session_subtes WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $subData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (empty($subData)) {
        $subData = [
            'TKP' => ['nilai'=>$session['nilai_tkp'],'passing_grade'=>$session['passing_tkp']],
            'TIU' => ['nilai'=>$session['nilai_tiu'],'passing_grade'=>$session['passing_tiu']],
            'TWK' => ['nilai'=>$session['nilai_twk'],'passing_grade'=>$session['passing_twk']],
        ];
    }
    
    $nilaiTkp = $subData['TKP']['nilai'] ?? 0;
    $nilaiTiu = $subData['TIU']['nilai'] ?? 0;
    $nilaiTwk = $subData['TWK']['nilai'] ?? 0;
    $totalNilai = $session['total_nilai'] ?? ($nilaiTkp + $nilaiTiu + $nilaiTwk);
    
    $passingTotal = $session['passing_total'] ?? 271;
    $statusTotal = $totalNilai >= $passingTotal ? 'LULUS' : 'TIDAK LULUS';
    
    // Compose notification message
    $message = "Hasil {$session['nama']}: Total {$totalNilai} ({$statusTotal}) | TKP: {$nilaiTkp} | TIU: {$nilaiTiu} | TWK: {$nilaiTwk}";
    
    // Determine notification type based on status
    $type = $statusTotal === 'LULUS' ? 'success' : 'warning';
    $title = $statusTotal === 'LULUS' ? '🎉 Selamat! Anda LULUS' : 'Hasil Tryout Anda';
    
    // Create in-app notification
    require '../api/create_notification.php';
    $result = createNotification(
        $userId,
        $type,
        $title,
        $message,
        "hasil.php?session_id={$sessionId}"
    );
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Hasil tryout berhasil dikirim ke notifikasi']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal mengirim notifikasi']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}
