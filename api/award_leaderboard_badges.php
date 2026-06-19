<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: admin only
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$period = $_GET['period'] ?? 'weekly'; // weekly or monthly

// Calculate period dates
if ($period === 'weekly') {
    $periodStart = date('Y-m-d', strtotime('monday this week'));
    $periodEnd = date('Y-m-d', strtotime('sunday this week'));
    $badgeType = 'top_1_weekly';
    $badgeName = 'Top 1 Minggu Ini';
} else {
    $periodStart = date('Y-m-01');
    $periodEnd = date('Y-m-t');
    $badgeType = 'top_1_monthly';
    $badgeName = 'Top 1 Bulan Ini';
}

// Award Top 1 badge
$stmt = $pdo->prepare("
    SELECT user_id, AVG(total_nilai) as avg_score
    FROM tryout_sessions
    WHERE status = 'selesai' AND created_at BETWEEN ? AND ?
    GROUP BY user_id
    ORDER BY avg_score DESC
    LIMIT 1
");
$stmt->execute([$periodStart, $periodEnd]);
$topUser = $stmt->fetch();

if ($topUser) {
    // Check if badge already awarded
    $stmt = $pdo->prepare("SELECT id FROM leaderboard_badges WHERE user_id = ? AND badge_type = ? AND period_start = ?");
    $stmt->execute([$topUser['user_id'], $badgeType, $periodStart]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO leaderboard_badges (user_id, badge_type, badge_name, badge_icon, badge_color, period_start, period_end)
            VALUES (?, ?, ?, '🥇', '#f1c40f', ?, ?)
        ");
        $stmt->execute([$topUser['user_id'], $badgeType, $badgeName, $periodStart, $periodEnd]);
    }
}

// Award Most Improved badge
$stmt = $pdo->prepare("
    SELECT 
        user_id,
        (MAX(total_nilai) - MIN(total_nilai)) as improvement
    FROM tryout_sessions
    WHERE status = 'selesai' AND created_at BETWEEN ? AND ?
    GROUP BY user_id
    HAVING COUNT(*) >= 2
    ORDER BY improvement DESC
    LIMIT 1
");
$stmt->execute([$periodStart, $periodEnd]);
$mostImproved = $stmt->fetch();

if ($mostImproved && $mostImproved['improvement'] > 0) {
    $stmt = $pdo->prepare("SELECT id FROM leaderboard_badges WHERE user_id = ? AND badge_type = 'most_improved' AND period_start = ?");
    $stmt->execute([$mostImproved['user_id'], $periodStart]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO leaderboard_badges (user_id, badge_type, badge_name, badge_icon, badge_color, period_start, period_end)
            VALUES (?, 'most_improved', 'Most Improved', '📈', '#27ae60', ?, ?)
        ");
        $stmt->execute([$mostImproved['user_id'], $periodStart, $periodEnd]);
    }
}

// Award Highest Streak badge (all-time)
$stmt = $pdo->prepare("
    SELECT user_id, longest_streak
    FROM daily_quiz_streaks
    ORDER BY longest_streak DESC
    LIMIT 1
");
$stmt->execute();
$highestStreak = $stmt->fetch();

if ($highestStreak && $highestStreak['longest_streak'] >= 7) {
    $stmt = $pdo->prepare("SELECT id FROM leaderboard_badges WHERE user_id = ? AND badge_type = 'highest_streak'");
    $stmt->execute([$highestStreak['user_id']]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO leaderboard_badges (user_id, badge_type, badge_name, badge_icon, badge_color)
            VALUES (?, 'highest_streak', 'Highest Streak ({$highestStreak['longest_streak']} hari)', '🔥', '#e74c3c')
        ");
        $stmt->execute([$highestStreak['user_id']]);
    }
}

ApiResponse::success([
    'top_user' => $topUser,
    'most_improved' => $mostImproved,
    'highest_streak' => $highestStreak
], 'Badges awarded successfully');
