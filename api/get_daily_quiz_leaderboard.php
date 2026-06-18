<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Simple response function for production compatibility
function sendJsonResponse($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

try {
    // Check if daily_quiz_streaks table exists
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM daily_quiz_streaks LIMIT 1");
        $tableExists = true;
    } catch (PDOException $e) {
        $tableExists = false;
    }

    $streakLeaderboard = [];
    $totalLeaderboard = [];
    $userRank = null;

    if ($tableExists) {
        // Get leaderboard by streak (top 10)
        $stmt = $pdo->prepare("
            SELECT u.nama, dqs.current_streak, dqs.longest_streak, dqs.total_quizzes
            FROM daily_quiz_streaks dqs
            JOIN users u ON dqs.user_id = u.id
            ORDER BY dqs.current_streak DESC, dqs.total_quizzes DESC
            LIMIT 10
        ");
        $stmt->execute();
        $streakLeaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get leaderboard by total quizzes (top 10)
        $stmt = $pdo->prepare("
            SELECT u.nama, dqs.current_streak, dqs.longest_streak, dqs.total_quizzes
            FROM daily_quiz_streaks dqs
            JOIN users u ON dqs.user_id = u.id
            ORDER BY dqs.total_quizzes DESC, dqs.current_streak DESC
            LIMIT 10
        ");
        $stmt->execute();
        $totalLeaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get current user's rank
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) + 1 as `rank`
                FROM daily_quiz_streaks dqs
                WHERE dqs.current_streak > COALESCE((SELECT current_streak FROM daily_quiz_streaks WHERE user_id = ?), 0)
            ");
            $stmt->execute([$userId]);
            $userRank = $stmt->fetch()['rank'] ?? null;
        }
    }

    sendJsonResponse(true, [
        'streak_leaderboard' => $streakLeaderboard,
        'total_leaderboard' => $totalLeaderboard,
        'user_rank' => $userRank
    ], 'Leaderboard fetched');
} catch (Exception $e) {
    sendJsonResponse(false, [], 'Failed to fetch leaderboard');
}
