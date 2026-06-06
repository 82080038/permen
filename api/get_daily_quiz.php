<?php
/**
 * API Daily Quiz - Ambil soal harian
 * 
 * Setiap user mendapat 10 soal random per hari:
 * - 4 soal TWK
 * - 3 soal TIU  
 * - 3 soal TKP
 */

require '../config.php';
header('Content-Type: application/json; charset=utf-8');

function getDifficultyFilter($difficulty) {
    switch ($difficulty) {
        case 'mudah':
            return "AND kesulitan IN ('mudah', 'sedang')";
        case 'sulit':
            return "AND kesulitan IN ('sedang', 'sulit')";
        default:
            return ""; // sedang: all questions
    }
}

// Guard: user harus login
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$today = date('Y-m-d');

// Cek apakah sudah ada sesi hari ini
$stmt = $pdo->prepare("SELECT * FROM daily_quiz_sessions WHERE user_id = ? AND quiz_date = ?");
$stmt->execute([$userId, $today]);
$session = $stmt->fetch();

if (!$session) {
    // Get user's current difficulty
    $stmt = $pdo->prepare("SELECT * FROM user_quiz_difficulty WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userDifficulty = $stmt->fetch();
    
    if (!$userDifficulty) {
        // Create default difficulty entry
        $stmt = $pdo->prepare("INSERT INTO user_quiz_difficulty (user_id, current_difficulty) VALUES (?, 'sedang')");
        $stmt->execute([$userId]);
        $currentDifficulty = 'sedang';
    } else {
        $currentDifficulty = $userDifficulty['current_difficulty'];
    }
    
    // Get today's scheduled topic
    $dayOfWeek = (int)date('w'); // 0=Sunday, 1=Monday, ..., 6=Saturday
    $stmt = $pdo->prepare("SELECT * FROM daily_quiz_topic_schedule WHERE day_of_week = ?");
    $stmt->execute([$dayOfWeek]);
    $schedule = $stmt->fetch();
    
    // Buat sesi baru
    $stmt = $pdo->prepare("INSERT INTO daily_quiz_sessions (user_id, quiz_date, total_soal, scheduled_subtes, scheduled_topik, difficulty) VALUES (?, ?, 10, ?, ?, ?)");
    $scheduledSubtes = $schedule['subtes'] ?? 'Mixed';
    $scheduledTopik = $schedule['topik'] ?? 'Campuran';
    $stmt->execute([$userId, $today, $scheduledSubtes, $scheduledTopik, $currentDifficulty]);
    $sessionId = $pdo->lastInsertId();
    
    // Ambil soal berdasarkan schedule dan difficulty
    if ($schedule && $schedule['subtes'] !== 'Mixed') {
        // Fokus pada subtes dan topik tertentu dengan difficulty filter
        $topikFilter = $schedule['topik'] ? "AND topik = " . $pdo->quote($schedule['topik']) : "";
        $difficultyFilter = getDifficultyFilter($currentDifficulty);
        $soal = $pdo->query("SELECT id, subtes FROM questions WHERE subtes = '{$schedule['subtes']}' AND is_active = 1 $topikFilter $difficultyFilter ORDER BY RAND() LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        
        // Jika tidak cukup soal dengan filter, ambil tanpa difficulty filter
        if (count($soal) < 10) {
            $soal = $pdo->query("SELECT id, subtes FROM questions WHERE subtes = '{$schedule['subtes']}' AND is_active = 1 $topikFilter ORDER BY RAND() LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        }
        // Masih kurang, ambil dari subtes saja
        if (count($soal) < 10) {
            $soal = $pdo->query("SELECT id, subtes FROM questions WHERE subtes = '{$schedule['subtes']}' AND is_active = 1 ORDER BY RAND() LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        // Mixed mode: 4 TWK, 3 TIU, 3 TKP with difficulty filter
        $difficultyFilter = getDifficultyFilter($currentDifficulty);
        $soalTWK = $pdo->query("SELECT id, 'TWK' as subtes FROM questions WHERE subtes = 'TWK' AND is_active = 1 $difficultyFilter ORDER BY RAND() LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
        $soalTIU = $pdo->query("SELECT id, 'TIU' as subtes FROM questions WHERE subtes = 'TIU' AND is_active = 1 $difficultyFilter ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        $soalTKP = $pdo->query("SELECT id, 'TKP' as subtes FROM questions WHERE subtes = 'TKP' AND is_active = 1 $difficultyFilter ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        $soal = array_merge($soalTWK, $soalTIU, $soalTKP);
        
        // Fallback if not enough questions with difficulty filter
        if (count($soal) < 10) {
            $soalTWK = $pdo->query("SELECT id, 'TWK' as subtes FROM questions WHERE subtes = 'TWK' AND is_active = 1 ORDER BY RAND() LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
            $soalTIU = $pdo->query("SELECT id, 'TIU' as subtes FROM questions WHERE subtes = 'TIU' AND is_active = 1 ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            $soalTKP = $pdo->query("SELECT id, 'TKP' as subtes FROM questions WHERE subtes = 'TKP' AND is_active = 1 ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            $soal = array_merge($soalTWK, $soalTIU, $soalTKP);
        }
    }
    
    // Insert ke daily_quiz_questions
    $stmtInsert = $pdo->prepare("INSERT INTO daily_quiz_questions (session_id, question_id, subtes, urutan) VALUES (?, ?, ?, ?)");
    foreach ($soal as $i => $q) {
        $stmtInsert->execute([$sessionId, $q['id'], $q['subtes'], $i + 1]);
    }
    
    $session = [
        'id' => $sessionId,
        'quiz_date' => $today,
        'status' => 'berjalan',
        'total_soal' => 10,
        'scheduled_subtes' => $scheduledSubtes,
        'scheduled_topik' => $scheduledTopik,
        'difficulty' => $currentDifficulty
    ];
} else {
    $sessionId = $session['id'];
}

// Ambil semua soal dengan detail lengkap
$stmt = $pdo->prepare("
    SELECT dq.id as dq_id, dq.question_id, dq.subtes, dq.urutan,
           q.pertanyaan, q.pilihan_a, q.pilihan_b, q.pilihan_c, q.pilihan_d, q.pilihan_e,
           q.jawaban_benar, q.bobot_tkp, q.pembahasan, q.image_url,
           dqa.jawaban_user, dqa.is_ragu
    FROM daily_quiz_questions dq
    JOIN questions q ON dq.question_id = q.id
    LEFT JOIN daily_quiz_answers dqa ON dqa.session_id = dq.session_id AND dqa.question_id = dq.question_id
    WHERE dq.session_id = ?
    ORDER BY dq.urutan
");
$stmt->execute([$sessionId]);
$soal = $stmt->fetchAll();

// Hitung progress
$answered = count(array_filter($soal, fn($s) => $s['jawaban_user'] !== null));
$marked = count(array_filter($soal, fn($s) => $s['is_ragu']));

echo json_encode([
    'success' => true,
    'session' => [
        'id' => $sessionId,
        'date' => $today,
        'status' => $session['status'],
        'total_soal' => (int)$session['total_soal'],
        'dijawab' => $answered,
        'ragu_ragu' => $marked
    ],
    'soal' => array_map(fn($s) => [
        'dq_id' => (int)$s['dq_id'],
        'question_id' => (int)$s['question_id'],
        'urutan' => (int)$s['urutan'],
        'subtes' => $s['subtes'],
        'pertanyaan' => $s['pertanyaan'],
        'pilihan_a' => $s['pilihan_a'],
        'pilihan_b' => $s['pilihan_b'],
        'pilihan_c' => $s['pilihan_c'],
        'pilihan_d' => $s['pilihan_d'],
        'pilihan_e' => $s['pilihan_e'],
        'image_url' => $s['image_url'],
        'jawaban_user' => $s['jawaban_user'],
        'is_ragu' => (bool)$s['is_ragu']
    ], $soal)
]);
