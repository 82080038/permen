<?php
/**
 * Test API get_soal with new question pool
 * Tests the question fetching and rolling mechanism
 */

require_once __DIR__ . '/../config.php';

echo "=== Testing API get_soal with New Question Pool ===\n\n";

// Create or find test user
$phone = '081999999999';
$stmt = $pdo->prepare("SELECT id FROM users WHERE no_hp = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch();

if (!$user) {
    $stmt = $pdo->prepare("INSERT INTO users (no_hp, password_hash, nama, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$phone, password_hash('test123', PASSWORD_DEFAULT), 'Test User API', 'user']);
    $userId = $pdo->lastInsertId();
    echo "Created test user (ID: $userId)\n";
} else {
    $userId = $user['id'];
    echo "Using existing test user (ID: $userId)\n";
}

// Create a tryout session
$sessionName = 'API Test Session ' . date('Y-m-d H:i:s');
$stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai) VALUES (?, ?, NOW())");
$stmt->execute([$userId, $sessionName]);
$sessionId = $pdo->lastInsertId();
echo "Created tryout session (ID: $sessionId)\n\n";

// Test fetching questions for each subtest
$subtests = ['TWK', 'TIU', 'TKP'];
$totalQuestions = 0;

foreach ($subtests as $subtest) {
    echo "--- Fetching $subtest questions ---\n";
    
    // Get previously answered questions
    $stmt = $pdo->prepare("SELECT DISTINCT question_id FROM answers a INNER JOIN tryout_sessions ts ON a.session_id = ts.id WHERE ts.user_id = ? AND a.question_id IN (SELECT id FROM questions WHERE subtes = ?)");
    $stmt->execute([$userId, $subtest]);
    $excludedIds = array_column($stmt->fetchAll(), 'question_id');
    
    echo "  Excluded questions: " . count($excludedIds) . "\n";
    
    // Fetch available questions
    $excludedSql = count($excludedIds) > 0 ? "AND id NOT IN (" . implode(',', $excludedIds) . ")" : "";
    $stmt = $pdo->prepare("SELECT id, pertanyaan, topik FROM questions WHERE subtes = ? AND is_active = 1 $excludedSql ORDER BY RAND() LIMIT 5");
    $stmt->execute([$subtest]);
    $questions = $stmt->fetchAll();
    
    echo "  Fetched questions: " . count($questions) . "\n";
    
    if (count($questions) > 0) {
        echo "  Sample question: \"" . substr($questions[0]['pertanyaan'], 0, 50) . "...\"\n";
        echo "  Topics: " . implode(', ', array_unique(array_column($questions, 'topik'))) . "\n";
        
        // Simulate assigning to session (using answers table)
        foreach ($questions as $q) {
            $stmtIns = $pdo->prepare("INSERT INTO answers (session_id, question_id) VALUES (?, ?)");
            $stmtIns->execute([$sessionId, $q['id']]);
        }
        
        $totalQuestions += count($questions);
    } else {
        echo "  ⚠ No questions available (pool exhausted or excluded)\n";
    }
    
    echo "\n";
}

// Mark session as completed
$stmt = $pdo->prepare("UPDATE tryout_sessions SET status = 'selesai', waktu_selesai = NOW() WHERE id = ?");
$stmt->execute([$sessionId]);

echo "=== Test Results ===\n";
echo "Total questions assigned: $totalQuestions\n";
echo "Session ID: $sessionId\n";
echo "User ID: $userId\n";

// Check question variety
$stmt = $pdo->prepare("SELECT q.subtes, COUNT(DISTINCT q.topik) as topic_count, COUNT(*) as total FROM answers a JOIN questions q ON a.question_id = q.id WHERE a.session_id = ? GROUP BY q.subtes");
$stmt->execute([$sessionId]);
echo "\nTopic variety in session:\n";
while ($row = $stmt->fetch()) {
    echo "  {$row['subtes']}: {$row['total']} questions from {$row['topic_count']} topics\n";
}

echo "\n=== API Test Complete ===\n";
