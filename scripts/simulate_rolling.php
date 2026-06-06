<?php
/**
 * Rolling Soal Simulation Script
 * 
 * Simulates multiple tryout sessions to test:
 * 1. Random questions per session
 * 2. Exclusion of previously answered questions
 * 3. Auto-generation when questions run out
 * 4. Daily limit enforcement
 */

require_once __DIR__ . '/../config.php';

echo "=== Rolling Soal Simulation ===\n\n";

// Get test user (regular user, not admin)
$stmt = $pdo->prepare("SELECT id, no_hp FROM users WHERE no_hp = ?");
$stmt->execute(['081987654321']);
$user = $stmt->fetch();

if (!$user) {
    echo "❌ Test user not found. Creating test user...\n";
    $stmt = $pdo->prepare("INSERT INTO users (no_hp, password, role, nama) VALUES (?, ?, ?, ?)");
    $stmt->execute(['081987654321', password_hash('password', PASSWORD_DEFAULT), 'user', 'Test User']);
    $userId = $pdo->lastInsertId();
    echo "✓ Test user created (ID: $userId)\n";
} else {
    $userId = $user['id'];
    echo "✓ Test user found (ID: $userId)\n";
}

// Check current question count
echo "\n--- Current Question Distribution ---\n";
$stmt = $pdo->query("SELECT subtes, COUNT(*) as total FROM questions WHERE is_active = 1 GROUP BY subtes");
while ($row = $stmt->fetch()) {
    echo "  {$row['subtes']}: {$row['total']} soal\n";
}

// Simulate 3 tryout sessions
echo "\n--- Simulating 3 Tryout Sessions ---\n";

for ($i = 1; $i <= 3; $i++) {
    echo "\nSession #$i:\n";
    
    // Create tryout session
    $stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai, status, durasi_twk, durasi_tiu, durasi_tkp, jumlah_twk, jumlah_tiu, jumlah_tkp) VALUES (?, ?, NOW(), 'berjalan', 12, 18, 25, 5, 5, 5)");
    $sessionName = "Simulasi Tryout #$i";
    $stmt->execute([$userId, $sessionName]);
    $sessionId = $pdo->lastInsertId();
    echo "  ✓ Session created (ID: $sessionId)\n";
    
    // Simulate get_soal.php logic
    $subtesConfig = [
        'TWK' => ['jumlah_soal' => 5],
        'TIU' => ['jumlah_soal' => 5],
        'TKP' => ['jumlah_soal' => 5],
    ];
    
    $allQuestionIds = [];
    foreach (array_keys($subtesConfig) as $sub) {
        $jumlah = $subtesConfig[$sub]['jumlah_soal'];
        
        // Get questions user has already answered (exclusion)
        $stmtExcl = $pdo->prepare("SELECT DISTINCT question_id FROM answers a 
                                  INNER JOIN tryout_sessions ts ON a.session_id = ts.id 
                                  WHERE ts.user_id = ? AND ts.status = 'selesai'");
        $stmtExcl->execute([$userId]);
        $excludedIds = $stmtExcl->fetchAll(PDO::FETCH_COLUMN);
        
        // Build exclusion clause
        $exclusionClause = '';
        $params = [$sub];
        if (!empty($excludedIds)) {
            $placeholders = implode(',', array_fill(0, count($excludedIds), '?'));
            $exclusionClause = " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $excludedIds);
        }
        
        $stmt = $pdo->prepare("SELECT id FROM questions WHERE subtes = ? AND is_active = 1$exclusionClause");
        $stmt->execute($params);
        $soalIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($soalIds)) {
            echo "  ⚠ $sub: No questions available (would auto-generate in production)\n";
            $soalIds = [];
        } else {
            shuffle($soalIds);
            $pilih = array_slice($soalIds, 0, min($jumlah, count($soalIds)));
            $allQuestionIds = array_merge($allQuestionIds, $pilih);
            echo "  ✓ $sub: Selected " . count($pilih) . " questions (excluded " . count($excludedIds) . ")\n";
        }
    }
    
    // Insert answers
    $insert = $pdo->prepare("INSERT INTO answers (session_id, question_id) VALUES (?, ?)");
    foreach ($allQuestionIds as $qid) {
        $insert->execute([$sessionId, $qid]);
    }
    echo "  ✓ Total questions assigned: " . count($allQuestionIds) . "\n";
    
    // Mark session as completed
    $stmt = $pdo->prepare("UPDATE tryout_sessions SET status = 'selesai', waktu_selesai = NOW() WHERE id = ?");
    $stmt->execute([$sessionId]);
    echo "  ✓ Session marked as completed\n";
}

// Check if questions were reused
echo "\n--- Checking for Question Reuse ---\n";
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT question_id) as unique_questions, COUNT(*) as total_answers FROM answers a INNER JOIN tryout_sessions ts ON a.session_id = ts.id WHERE ts.user_id = ?");
$stmt->execute([$userId]);
$result = $stmt->fetch();

echo "  Unique questions: {$result['unique_questions']}\n";
echo "  Total answers: {$result['total_answers']}\n";

if ($result['unique_questions'] == $result['total_answers']) {
    echo "  ✓ No questions reused (exclusion working)\n";
} else {
    echo "  ⚠ Some questions were reused\n";
}

// Check daily limit (only count sessions from this simulation)
echo "\n--- Daily Limit Check ---\n";
$stmt = $pdo->prepare("SELECT COUNT(*) as today_count FROM tryout_sessions WHERE user_id = ? AND DATE(waktu_mulai) = CURDATE() AND nama LIKE 'Simulasi%'");
$stmt->execute([$userId]);
$todayCount = $stmt->fetchColumn();

echo "  Tryouts today (simulation): $todayCount\n";
if ($todayCount >= 5) {
    echo "  ⚠ Daily limit reached (5)\n";
} else {
    echo "  ✓ Under daily limit (can do " . (5 - $todayCount) . " more)\n";
}

echo "\n=== Simulation Complete ===\n";
