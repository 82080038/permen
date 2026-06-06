<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: logged in user required
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'subscribe') {
    $endpoint = $_POST['endpoint'] ?? '';
    $p256dh = $_POST['p256dh'] ?? '';
    $auth = $_POST['auth'] ?? '';
    
    if (!$endpoint || !$p256dh || !$auth) {
        echo json_encode(['error' => 'Invalid subscription data']);
        exit;
    }
    
    // Check if subscription already exists
    $stmt = $pdo->prepare("SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
    $stmt->execute([$_SESSION['user_id'], $endpoint]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing subscription
        $stmt = $pdo->prepare("
            UPDATE push_subscriptions 
            SET p256dh = ?, auth = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$p256dh, $auth, $existing['id']]);
    } else {
        // Insert new subscription
        $stmt = $pdo->prepare("
            INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $endpoint, $p256dh, $auth]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Subscription saved']);
    
} elseif ($action === 'unsubscribe') {
    $endpoint = $_POST['endpoint'] ?? '';
    
    if (!$endpoint) {
        echo json_encode(['error' => 'Invalid endpoint']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
    $stmt->execute([$_SESSION['user_id'], $endpoint]);
    
    echo json_encode(['success' => true, 'message' => 'Subscription removed']);
    
} elseif ($action === 'update_preferences') {
    $dailyQuiz = isset($_POST['daily_quiz_reminder']) ? 1 : 0;
    $liveClass = isset($_POST['live_class_starting']) ? 1 : 0;
    $newMateri = isset($_POST['new_materi_available']) ? 1 : 0;
    $tryoutResult = isset($_POST['tryout_result_ready']) ? 1 : 0;
    
    $stmt = $pdo->prepare("
        INSERT INTO notification_preferences (user_id, daily_quiz_reminder, live_class_starting, new_materi_available, tryout_result_ready)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            daily_quiz_reminder = ?, live_class_starting = ?, new_materi_available = ?, tryout_result_ready = ?, updated_at = NOW()
    ");
    $stmt->execute([$_SESSION['user_id'], $dailyQuiz, $liveClass, $newMateri, $tryoutResult, $dailyQuiz, $liveClass, $newMateri, $tryoutResult]);
    
    echo json_encode(['success' => true, 'message' => 'Preferences updated']);
    
} elseif ($_GET['action'] === 'get_preferences') {
    $stmt = $pdo->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prefs) {
        // Return default preferences
        $prefs = [
            'daily_quiz_reminder' => 1,
            'live_class_starting' => 1,
            'new_materi_available' => 1,
            'tryout_result_ready' => 1
        ];
    }
    
    echo json_encode(['success' => true, 'preferences' => $prefs]);
    
} elseif ($_GET['action'] === 'check_subscription') {
    $stmt = $pdo->prepare("SELECT endpoint FROM push_subscriptions WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $subscription = $stmt->fetch();
    
    echo json_encode(['success' => true, 'subscribed' => !!$subscription]);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}
