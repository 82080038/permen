<?php
/**
 * API Finish Daily Quiz - Hitung hasil dan simpan
 */

require '../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

// Guard: user harus login
if (empty($_SESSION['user_id'])) {
    ApiResponse::unauthorized('Silakan login terlebih dahulu');
}

$userId = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$sessionId = (int)($data['session_id'] ?? 0);

if (!$sessionId) {
    ApiResponse::validationError(['session_id' => 'Session ID diperlukan'], 'Session ID diperlukan');
}

// Verifikasi session milik user
$stmt = $pdo->prepare("SELECT * FROM daily_quiz_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$sessionId, $userId]);
$session = $stmt->fetch();

if (!$session) {
    ApiResponse::notFound('Session tidak ditemukan');
}

if ($session['status'] === 'selesai') {
    ApiResponse::success([], 'Quiz sudah selesai sebelumnya');
}

// Hitung hasil - fetch individual answers for proper TKP weighted scoring
$stmt = $pdo->prepare("
    SELECT 
        q.subtes, q.jawaban_benar, q.bobot_tkp, q.bobot_a, q.bobot_b, q.bobot_c, q.bobot_d, q.bobot_e,
        dqa.jawaban_user
    FROM daily_quiz_questions dq
    JOIN questions q ON dq.question_id = q.id
    LEFT JOIN daily_quiz_answers dqa ON dqa.session_id = dq.session_id AND dqa.question_id = dq.question_id
    WHERE dq.session_id = ?
");
$stmt->execute([$sessionId]);
$allAnswers = $stmt->fetchAll();

$totalSoal = count($allAnswers);
$answered = 0;
$benar = 0;
$salah = 0;
$nilaiTkp = 0;
$nilaiTiu = 0;
$nilaiTwk = 0;

foreach ($allAnswers as $row) {
    $jawaban = $row['jawaban_user'];
    if (empty($jawaban)) continue;
    $answered++;
    
    if ($row['subtes'] === 'TKP') {
        // TKP: weighted scoring (setiap jawaban dapat skor 1-5)
        $bobotKey = 'bobot_' . strtolower($jawaban);
        if (!empty($row[$bobotKey])) {
            $nilaiTkp += (int)$row[$bobotKey];
        } else {
            // Fallback: distance-based scoring
            $map = ['A'=>1,'B'=>2,'C'=>3,'D'=>4,'E'=>5];
            $skorJawaban = $map[$jawaban] ?? 1;
            $skorBenar = $map[$row['jawaban_benar']] ?? 3;
            $diff = abs($skorJawaban - $skorBenar);
            $bobot = (int)($row['bobot_tkp'] ?? 5);
            if ($diff == 0) $nilaiTkp += $bobot;
            elseif ($diff == 1) $nilaiTkp += max(1, $bobot - 1);
            elseif ($diff == 2) $nilaiTkp += max(1, $bobot - 2);
            elseif ($diff == 3) $nilaiTkp += max(1, $bobot - 3);
            else $nilaiTkp += 1;
        }
        // TKP: benar jika jawaban = jawaban_benar (untuk statistik)
        if ($jawaban === $row['jawaban_benar']) $benar++;
        else $salah++;
    } else {
        // TIU & TWK: binary scoring (benar = 5, salah = 0)
        if ($jawaban === $row['jawaban_benar']) {
            $benar++;
            if ($row['subtes'] === 'TIU') $nilaiTiu += 5;
            else $nilaiTwk += 5;
        } else {
            $salah++;
        }
    }
}

$kosong = $totalSoal - $answered;
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

ApiResponse::success([
    'total_soal' => $totalSoal,
    'dijawab' => $answered,
    'benar' => $benar,
    'salah' => $salah,
    'kosong' => $kosong,
    'skor_twk' => $nilaiTwk,
    'skor_tiu' => $nilaiTiu,
    'skor_tkp' => $nilaiTkp,
    'nilai_total' => $nilaiTotal
], 'Daily quiz finished');

function checkAndAwardAchievements($userId, $streak, $benar, $totalSoal) {
    $pdo = app('pdo');
    
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

