<?php
/**
 * Cron Job: Generate Performance Reports
 * Run this script weekly/monthly to generate performance reports for all users
 * 
 * Usage: 
 * php scripts/generate_performance_reports_cron.php?type=weekly
 * php scripts/generate_performance_reports_cron.php?type=monthly
 */

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

$type = $argv[1] ?? 'weekly';

if (!in_array($type, ['weekly', 'monthly'])) {
    echo "Usage: php generate_performance_reports_cron.php [weekly|monthly]\n";
    exit(1);
}

// Get all active users
$stmt = $pdo->query("SELECT id FROM users WHERE role = 'user'");
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

$reportDate = date('Y-m-d');
if ($type === 'weekly') {
    $periodStart = date('Y-m-d', strtotime('monday this week'));
    $periodEnd = date('Y-m-d', strtotime('sunday this week'));
} else {
    $periodStart = date('Y-m-01');
    $periodEnd = date('Y-m-t');
}

$count = 0;
foreach ($users as $userId) {
    // Check if report already exists
    $stmt = $pdo->prepare("SELECT id FROM performance_reports WHERE user_id = ? AND report_type = ? AND report_date = ?");
    $stmt->execute([$userId, $type, $reportDate]);
    if ($stmt->fetch()) {
        continue; // Skip if already exists
    }

    // Calculate statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, AVG(total_nilai) as avg_score
        FROM tryout_sessions
        WHERE user_id = ? AND status = 'selesai' AND created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$userId, $periodStart, $periodEnd]);
    $tryoutStats = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, AVG(nilai_total) as avg_score
        FROM daily_quiz_sessions
        WHERE user_id = ? AND status = 'selesai' AND quiz_date BETWEEN ? AND ?
    ");
    $stmt->execute([$userId, $periodStart, $periodEnd]);
    $dailyQuizStats = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT current_streak FROM daily_quiz_streaks WHERE user_id = ?");
    $stmt->execute([$userId]);
    $streak = $stmt->fetch();

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
    if ($dailyQuizStats['total'] < 3 && $type === 'weekly') {
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
        $type,
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

    $count++;
}

echo "Generated $count $type performance reports for $reportDate\n";
