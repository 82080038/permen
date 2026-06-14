<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: user must be logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$reportType = $_GET['type'] ?? 'weekly'; // weekly or monthly

// Calculate period
$reportDate = date('Y-m-d');
if ($reportType === 'weekly') {
    $periodStart = date('Y-m-d', strtotime('monday this week'));
    $periodEnd = date('Y-m-d', strtotime('sunday this week'));
} else {
    $periodStart = date('Y-m-01'); // First day of current month
    $periodEnd = date('Y-m-t'); // Last day of current month
}

// Check if report already exists
$stmt = $pdo->prepare("SELECT * FROM performance_reports WHERE user_id = ? AND report_type = ? AND report_date = ?");
$stmt->execute([$userId, $reportType, $reportDate]);
$existingReport = $stmt->fetch();

if ($existingReport) {
    echo json_encode(['success' => true, 'data' => $existingReport]);
    exit;
}

// Calculate statistics
// Tryout stats
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total, AVG(skor_total) as avg_score
    FROM tryout_sessions
    WHERE user_id = ? AND status = 'completed' AND created_at BETWEEN ? AND ?
");
$stmt->execute([$userId, $periodStart, $periodEnd]);
$tryoutStats = $stmt->fetch();

// Daily quiz stats
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total, AVG(nilai_total) as avg_score
    FROM daily_quiz_sessions
    WHERE user_id = ? AND status = 'completed' AND quiz_date BETWEEN ? AND ?
");
$stmt->execute([$userId, $periodStart, $periodEnd]);
$dailyQuizStats = $stmt->fetch();

// Current streak
$stmt = $pdo->prepare("SELECT current_streak FROM daily_quiz_streaks WHERE user_id = ?");
$stmt->execute([$userId]);
$streak = $stmt->fetch();

// Practice sessions count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM personal_practice_sessions
    WHERE user_id = ? AND waktu_mulai BETWEEN ? AND ?
");
$stmt->execute([$userId, $periodStart, $periodEnd]);
$practiceStats = $stmt->fetch();

// Generate recommendations
$recommendations = [];
if ($tryoutStats['total'] > 0 && $tryoutStats['avg_score'] < 250) {
    $recommendations[] = "Skor tryout rata-rata di bawah 250. Perlu latihan lebih intensif.";
}
if ($dailyQuizStats['total'] < 3 && $reportType === 'weekly') {
    $recommendations[] = "Daily quiz kurang dari 3 kali minggu ini. Pertahankan streak!";
}
if ($streak && $streak['current_streak'] < 3) {
    $recommendations[] = "Streak daily quiz rendah. Coba kerjakan setiap hari.";
}
if ($practiceStats['total'] < 5) {
    $recommendations[] = "Latihan personal kurang. Fokus pada topik yang lemah.";
}

if (empty($recommendations)) {
    $recommendations[] = "Performa Anda bagus! Pertahankan konsistensi.";
}

// Save report
$stmt = $pdo->prepare("
    INSERT INTO performance_reports 
    (user_id, report_type, report_date, period_start, period_end, total_tryouts, avg_tryout_score, 
     total_daily_quizzes, avg_daily_quiz_score, current_streak, total_practice_sessions, recommendations)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $userId,
    $reportType,
    $reportDate,
    $periodStart,
    $periodEnd,
    $tryoutStats['total'] ?? 0,
    $tryoutStats['avg_score'] ?? 0,
    $dailyQuizStats['total'] ?? 0,
    $dailyQuizStats['avg_score'] ?? 0,
    $streak['current_streak'] ?? 0,
    $practiceStats['total'] ?? 0,
    implode('; ', $recommendations)
]);

// Fetch the created report
$stmt = $pdo->prepare("SELECT * FROM performance_reports WHERE id = ?");
$stmt->execute([$pdo->lastInsertId()]);
$report = $stmt->fetch();

echo json_encode(['success' => true, 'data' => $report]);
