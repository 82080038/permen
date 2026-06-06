<?php
/**
 * API Finish Daily Quiz - Hitung hasil dan simpan
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

if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Session ID diperlukan']);
    exit;
}

// Verifikasi session milik user
$stmt = $pdo->prepare("SELECT * FROM daily_quiz_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$sessionId, $userId]);
$session = $stmt->fetch();

if (!$session) {
    http_response_code(403);
    echo json_encode(['error' => 'Session tidak ditemukan']);
    exit;
}

if ($session['status'] === 'selesai') {
    echo json_encode(['success' => true, 'message' => 'Quiz sudah selesai sebelumnya']);
    exit;
}

// Hitung hasil
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN dqa.jawaban_user IS NOT NULL THEN 1 ELSE 0 END) as answered,
        SUM(CASE WHEN dqa.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar,
        SUM(CASE WHEN dqa.jawaban_user IS NOT NULL AND dqa.jawaban_user != q.jawaban_benar THEN 1 ELSE 0 END) as salah,
        SUM(CASE WHEN q.subtes = 'TKP' AND dqa.jawaban_user = q.jawaban_benar THEN q.bobot_tkp ELSE 0 END) as nilai_tkp,
        SUM(CASE WHEN q.subtes = 'TIU' AND dqa.jawaban_user = q.jawaban_benar THEN 5 ELSE 0 END) as nilai_tiu,
        SUM(CASE WHEN q.subtes = 'TWK' AND dqa.jawaban_user = q.jawaban_benar THEN 5 ELSE 0 END) as nilai_twk
    FROM daily_quiz_questions dq
    JOIN questions q ON dq.question_id = q.id
    LEFT JOIN daily_quiz_answers dqa ON dqa.session_id = dq.session_id AND dqa.question_id = dq.question_id
    WHERE dq.session_id = ?
");
$stmt->execute([$sessionId]);
$hasil = $stmt->fetch();

$totalSoal = (int)$hasil['total'];
$answered = (int)$hasil['answered'];
$benar = (int)$hasil['benar'];
$salah = (int)$hasil['salah'];
$kosong = $totalSoal - $answered;
$nilaiTkp = (int)($hasil['nilai_tkp'] ?? 0);
$nilaiTiu = (int)($hasil['nilai_tiu'] ?? 0);
$nilaiTwk = (int)($hasil['nilai_twk'] ?? 0);
$nilaiTotal = $nilaiTkp + $nilaiTiu + $nilaiTwk;

// Update session
$stmt = $pdo->prepare("
    UPDATE daily_quiz_sessions 
    SET status = 'selesai', waktu_selesai = NOW(),
        benar = ?, salah = ?, kosong = ?, nilai_total = ?
    WHERE id = ?
");
$stmt->execute([$benar, $salah, $kosong, $nilaiTotal, $sessionId]);

// Update difficulty based on performance
$scorePercentage = ($totalSoal > 0) ? ($benar / $totalSoal) * 100 : 0;
$stmt = $pdo->prepare("SELECT * FROM user_quiz_difficulty WHERE user_id = ?");
$stmt->execute([$userId]);
$userDifficulty = $stmt->fetch();

if ($userDifficulty) {
    $currentDiff = $userDifficulty['current_difficulty'];
    $consecutiveHigh = $userDifficulty['consecutive_high_scores'];
    $consecutiveLow = $userDifficulty['consecutive_low_scores'];
    
    if ($scorePercentage >= 80) {
        // High score - increase consecutive high
        $consecutiveHigh++;
        $consecutiveLow = 0;
        
        // Increase difficulty after 3 consecutive high scores
        if ($consecutiveHigh >= 3 && $currentDiff !== 'sulit') {
            $newDiff = ($currentDiff === 'mudah') ? 'sedang' : 'sulit';
            $consecutiveHigh = 0;
            $stmt = $pdo->prepare("UPDATE user_quiz_difficulty SET current_difficulty = ?, consecutive_high_scores = 0, consecutive_low_scores = 0 WHERE user_id = ?");
            $stmt->execute([$newDiff, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE user_quiz_difficulty SET consecutive_high_scores = ?, consecutive_low_scores = 0 WHERE user_id = ?");
            $stmt->execute([$consecutiveHigh, $userId]);
        }
    } elseif ($scorePercentage < 50) {
        // Low score - increase consecutive low
        $consecutiveLow++;
        $consecutiveHigh = 0;
        
        // Decrease difficulty after 3 consecutive low scores
        if ($consecutiveLow >= 3 && $currentDiff !== 'mudah') {
            $newDiff = ($currentDiff === 'sulit') ? 'sedang' : 'mudah';
            $consecutiveLow = 0;
            $stmt = $pdo->prepare("UPDATE user_quiz_difficulty SET current_difficulty = ?, consecutive_high_scores = 0, consecutive_low_scores = 0 WHERE user_id = ?");
            $stmt->execute([$newDiff, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE user_quiz_difficulty SET consecutive_high_scores = 0, consecutive_low_scores = ? WHERE user_id = ?");
            $stmt->execute([$consecutiveLow, $userId]);
        }
    } else {
        // Average score - reset counters
        $stmt = $pdo->prepare("UPDATE user_quiz_difficulty SET consecutive_high_scores = 0, consecutive_low_scores = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
}

// Update streak
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM daily_quiz_streaks WHERE user_id = ?");
$stmt->execute([$userId]);
$streak = $stmt->fetch();

if ($streak) {
    $lastDate = $streak['last_quiz_date'];
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($lastDate === $today) {
        // Already completed today, no change
    } elseif ($lastDate === $yesterday) {
        // Consecutive day, increment streak
        $newStreak = $streak['current_streak'] + 1;
        $newLongest = max($streak['longest_streak'], $newStreak);
        $stmt = $pdo->prepare("UPDATE daily_quiz_streaks SET current_streak = ?, longest_streak = ?, last_quiz_date = ?, total_quizzes = total_quizzes + 1 WHERE user_id = ?");
        $stmt->execute([$newStreak, $newLongest, $today, $userId]);
        
        // Check for achievements
        checkAndAwardAchievements($userId, $newStreak, $benar, $totalSoal);
    } else {
        // Streak broken, reset to 1
        $stmt = $pdo->prepare("UPDATE daily_quiz_streaks SET current_streak = 1, last_quiz_date = ?, total_quizzes = total_quizzes + 1 WHERE user_id = ?");
        $stmt->execute([$today, $userId]);
        
        checkAndAwardAchievements($userId, 1, $benar, $totalSoal);
    }
} else {
    // First quiz
    $stmt = $pdo->prepare("INSERT INTO daily_quiz_streaks (user_id, current_streak, longest_streak, last_quiz_date, total_quizzes) VALUES (?, 1, 1, ?, 1)");
    $stmt->execute([$userId, $today]);
    
    checkAndAwardAchievements($userId, 1, $benar, $totalSoal);
}

echo json_encode([
    'success' => true,
    'hasil' => [
        'total_soal' => $totalSoal,
        'dijawab' => $answered,
        'benar' => $benar,
        'salah' => $salah,
        'kosong' => $kosong,
        'nilai_twk' => $nilaiTwk,
        'nilai_tiu' => $nilaiTiu,
        'nilai_tkp' => $nilaiTkp,
        'nilai_total' => $nilaiTotal
    ]
]);

function checkAndAwardAchievements($userId, $streak, $benar, $totalSoal) {
    global $pdo;
    
    $achievements = [];
    
    // Streak achievements
    if ($streak >= 7) $achievements[] = ['streak_7', '🔥 Streak 7 Hari'];
    if ($streak >= 30) $achievements[] = ['streak_30', '🔥 Streak 30 Hari'];
    if ($streak >= 100) $achievements[] = ['streak_100', '🔥 Streak 100 Hari'];
    
    // Perfect score achievement
    if ($benar === $totalSoal && $totalSoal > 0) {
        $achievements[] = ['perfect_score', '💯 Skor Sempurna'];
    }
    
    // Award achievements
    foreach ($achievements as $ach) {
        $stmt = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_type = ?");
        $stmt->execute([$userId, $ach[0]]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_type, achievement_name) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $ach[0], $ach[1]]);
        }
    }
}
