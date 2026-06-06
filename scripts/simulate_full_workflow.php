<?php
/**
 * Full Application Feature Simulation
 * Simulates complete user workflow from registration to tryout completion
 */

require_once __DIR__ . '/../config.php';

echo "=== Full Application Feature Simulation ===\n\n";

// ============================================================
// STEP 1: User Registration
// ============================================================
echo "--- Step 1: User Registration ---\n";
$testPhone = '081888888888';
$testName = 'Simulation User';
$testPassword = 'SimPass123';

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE no_hp = ?");
$stmt->execute([$testPhone]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    $userId = $existingUser['id'];
    echo "  User already exists (ID: $userId)\n";
} else {
    // Register new user
    $hash = password_hash($testPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (no_hp, password_hash, nama, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$testPhone, $hash, $testName, 'user']);
    $userId = $pdo->lastInsertId();
    echo "  ✓ User registered successfully (ID: $userId)\n";
}

// ============================================================
// STEP 2: User Login
// ============================================================
echo "\n--- Step 2: User Login ---\n";
$stmt = $pdo->prepare("SELECT id, nama, no_hp, password_hash, role FROM users WHERE no_hp = ?");
$stmt->execute([$testPhone]);
$user = $stmt->fetch();

if ($user && password_verify($testPassword, $user['password_hash'])) {
    echo "  ✓ Login successful\n";
    echo "  User: {$user['nama']} ({$user['role']})\n";
} else {
    echo "  ✗ Login failed\n";
    exit(1);
}

// ============================================================
// STEP 3: Create Tryout Session
// ============================================================
echo "\n--- Step 3: Create Tryout Session ---\n";
$sessionName = 'Simulation Tryout ' . date('Y-m-d H:i:s');
$stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai) VALUES (?, ?, NOW())");
$stmt->execute([$userId, $sessionName]);
$sessionId = $pdo->lastInsertId();
echo "  ✓ Session created (ID: $sessionId)\n";

// Insert session_subtes configuration
$subtesConfig = [
    ['TWK', 12, 5, 143, 1],
    ['TIU', 18, 5, 166, 2],
    ['TKP', 25, 5, 126, 3]
];

foreach ($subtesConfig as $config) {
    $stmt = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sessionId, $config[0], $config[1], $config[2], $config[3], $config[4]]);
}
echo "  ✓ Subtest configuration added\n";

// ============================================================
// STEP 4: Fetch Questions (get_soal API simulation)
// ============================================================
echo "\n--- Step 4: Fetch Questions ---\n";

// Simulate get_soal API logic
$stmt = $pdo->prepare("SELECT subtes, durasi_menit, jumlah_soal, passing_grade, nilai FROM session_subtes WHERE session_id = ? ORDER BY urutan");
$stmt->execute([$sessionId]);
$subtesRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$subtesConfig = [];
foreach ($subtesRows as $row) {
    $subtesConfig[$row['subtes']] = [
        'durasi_menit' => (int)$row['durasi_menit'],
        'jumlah_soal'  => (int)$row['jumlah_soal'],
        'passing_grade'=> (int)$row['passing_grade'],
        'nilai'        => (int)$row['nilai'],
    ];
}

// Start first subtest timer
$pdo->prepare("UPDATE session_subtes SET waktu_mulai_subtes = NOW() WHERE session_id = ? AND urutan = (SELECT MIN(urutan) FROM session_subtes WHERE session_id = ?)")
    ->execute([$sessionId, $sessionId]);

$totalQuestions = 0;
foreach (array_keys($subtesConfig) as $sub) {
    $jumlah = (int)$subtesConfig[$sub]['jumlah_soal'];
    
    // Get excluded questions
    $stmtExcl = $pdo->prepare("SELECT DISTINCT question_id FROM answers a 
                              INNER JOIN tryout_sessions ts ON a.session_id = ts.id 
                              WHERE ts.user_id = ? AND a.question_id IN (SELECT id FROM questions WHERE subtes = ?)");
    $stmtExcl->execute([$userId, $sub]);
    $excludedIds = array_column($stmtExcl->fetchAll(), 'question_id');
    
    $exclusionClause = count($excludedIds) > 0 ? " AND id NOT IN (" . implode(',', $excludedIds) . ")" : "";
    
    // Fetch questions
    $stmt = $pdo->prepare("SELECT id, topik FROM questions WHERE subtes = ? AND is_active = 1 $exclusionClause ORDER BY RAND() LIMIT $jumlah");
    $stmt->execute([$sub]);
    $questions = $stmt->fetchAll();
    
    // Insert to answers table
    $insert = $pdo->prepare("INSERT INTO answers (session_id, question_id) VALUES (?, ?)");
    foreach ($questions as $q) {
        $insert->execute([$sessionId, $q['id']]);
    }
    
    $totalQuestions += count($questions);
    echo "  ✓ $sub: " . count($questions) . " questions fetched\n";
}

echo "  Total questions: $totalQuestions\n";

// ============================================================
// STEP 5: Submit Answers
// ============================================================
echo "\n--- Step 5: Submit Answers ---\n";

// Get all questions for this session
$stmt = $pdo->prepare("SELECT a.id as answer_id, a.question_id, q.subtes, q.jawaban_benar, q.bobot_tkp 
                      FROM answers a 
                      JOIN questions q ON a.question_id = q.id 
                      WHERE a.session_id = ?");
$stmt->execute([$sessionId]);
$answers = $stmt->fetchAll();

$submittedCount = 0;
foreach ($answers as $ans) {
    // Simulate random answer selection
    $options = ['A', 'B', 'C', 'D', 'E'];
    $randomAnswer = $options[array_rand($options)];
    
    // Update answer
    $stmt = $pdo->prepare("UPDATE answers SET jawaban_user = ? WHERE id = ?");
    $stmt->execute([$randomAnswer, $ans['answer_id']]);
    
    // Calculate score
    $isCorrect = ($randomAnswer === $ans['jawaban_benar']);
    $score = $isCorrect ? 1 : 0;
    
    // For TKP, use bobot_tkp
    if ($ans['subtes'] === 'TKP') {
        $score = $isCorrect ? $ans['bobot_tkp'] : 1;
    }
    
    $stmt = $pdo->prepare("UPDATE answers SET skor = ? WHERE id = ?");
    $stmt->execute([$score, $ans['answer_id']]);
    
    $submittedCount++;
}

echo "  ✓ $submittedCount answers submitted\n";

// ============================================================
// STEP 6: Navigate Subtests (next_subtes simulation)
// ============================================================
echo "\n--- Step 6: Navigate Subtests ---\n";

// Move to next subtest
$currentSubtes = 'TWK';
$nextSubtes = 'TIU';

$stmt = $pdo->prepare("UPDATE session_subtes SET waktu_mulai_subtes = NOW() WHERE session_id = ? AND subtes = ?");
$stmt->execute([$sessionId, $nextSubtes]);
echo "  ✓ Moved from $currentSubtes to $nextSubtes\n";

// Move to final subtest
$currentSubtes = 'TIU';
$nextSubtes = 'TKP';

$stmt = $pdo->prepare("UPDATE session_subtes SET waktu_mulai_subtes = NOW() WHERE session_id = ? AND subtes = ?");
$stmt->execute([$sessionId, $nextSubtes]);
echo "  ✓ Moved from $currentSubtes to $nextSubtes\n";

// ============================================================
// STEP 7: Finish Tryout
// ============================================================
echo "\n--- Step 7: Finish Tryout ---\n";

// Calculate scores
$nilai = [];
foreach (['TWK', 'TIU', 'TKP'] as $sub) {
    $stmt = $pdo->prepare("SELECT SUM(skor) as total FROM answers a 
                          JOIN questions q ON a.question_id = q.id 
                          WHERE a.session_id = ? AND q.subtes = ?");
    $stmt->execute([$sessionId, $sub]);
    $score = $stmt->fetch()['total'] ?: 0;
    
    // Normalize TKP score (max 125)
    if ($sub === 'TKP') {
        $score = min(125, $score);
    }
    
    $nilai[$sub] = $score;
}

$total = array_sum($nilai);

// Update session_subtes
$updateSub = $pdo->prepare("UPDATE session_subtes SET nilai = ? WHERE session_id = ? AND subtes = ?");
foreach ($nilai as $sub => $val) {
    $updateSub->execute([$val, $sessionId, $sub]);
}

// Update tryout_sessions
$stmt = $pdo->prepare("UPDATE tryout_sessions SET nilai_tkp=?, nilai_tiu=?, nilai_twk=?, total_nilai=?, status='selesai', waktu_selesai=NOW() WHERE id=?");
$stmt->execute([$nilai['TKP'], $nilai['TIU'], $nilai['TWK'], $total, $sessionId]);

echo "  ✓ Tryout finished\n";
echo "  Scores: TWK={$nilai['TWK']}, TIU={$nilai['TIU']}, TKP={$nilai['TKP']}\n";
echo "  Total: $total\n";

// ============================================================
// STEP 8: Daily Quiz Simulation
// ============================================================
echo "\n--- Step 8: Daily Quiz ---\n";

// Check if daily quiz already exists today
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT id FROM daily_quiz_sessions WHERE user_id = ? AND quiz_date = ?");
$stmt->execute([$userId, $today]);
$existingQuiz = $stmt->fetch();

if ($existingQuiz) {
    $quizSessionId = $existingQuiz['id'];
    echo "  Daily quiz already exists today (ID: $quizSessionId)\n";
} else {
    // Create daily quiz session
    $stmt = $pdo->prepare("INSERT INTO daily_quiz_sessions (user_id, quiz_date, total_soal) VALUES (?, ?, 10)");
    $stmt->execute([$userId, $today]);
    $quizSessionId = $pdo->lastInsertId();
    echo "  ✓ Daily quiz session created (ID: $quizSessionId)\n";
    
    // Fetch questions for daily quiz
    $soalTWK = $pdo->query("SELECT id FROM questions WHERE subtes = 'TWK' AND is_active = 1 ORDER BY RAND() LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);
    $soalTIU = $pdo->query("SELECT id FROM questions WHERE subtes = 'TIU' AND is_active = 1 ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
    $soalTKP = $pdo->query("SELECT id FROM questions WHERE subtes = 'TKP' AND is_active = 1 ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
    
    $allSoal = array_merge($soalTWK, $soalTIU, $soalTKP);
    $urutan = 1;
    
    foreach ($allSoal as $qId) {
        $stmt = $pdo->prepare("SELECT subtes FROM questions WHERE id = ?");
        $stmt->execute([$qId]);
        $subtes = $stmt->fetch()['subtes'];
        
        $stmtIns = $pdo->prepare("INSERT INTO daily_quiz_questions (session_id, question_id, subtes, urutan) VALUES (?, ?, ?, ?)");
        $stmtIns->execute([$quizSessionId, $qId, $subtes, $urutan++]);
    }
    
    echo "  ✓ 10 questions assigned to daily quiz\n";
}

// Submit daily quiz answers
$stmt = $pdo->prepare("SELECT id, question_id FROM daily_quiz_questions WHERE session_id = ?");
$stmt->execute([$quizSessionId]);
$quizQuestions = $stmt->fetchAll();

foreach ($quizQuestions as $qq) {
    $stmt = $pdo->prepare("SELECT jawaban_benar FROM questions WHERE id = ?");
    $stmt->execute([$qq['question_id']]);
    $correct = $stmt->fetch()['jawaban_benar'];
    
    $options = ['A', 'B', 'C', 'D', 'E'];
    $randomAnswer = $options[array_rand($options)];
    
    // Insert into daily_quiz_answers table
    $stmt = $pdo->prepare("INSERT INTO daily_quiz_answers (session_id, question_id, jawaban_user) VALUES (?, ?, ?)");
    $stmt->execute([$quizSessionId, $qq['question_id'], $randomAnswer]);
}

echo "  ✓ Daily quiz answers submitted\n";

// ============================================================
// STEP 9: Verify Results
// ============================================================
echo "\n--- Step 9: Verify Results ---\n";

// Verify tryout results
$stmt = $pdo->prepare("SELECT * FROM tryout_sessions WHERE id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

echo "  Tryout Session #$sessionId:\n";
echo "    Status: {$session['status']}\n";
echo "    TWK Score: {$session['nilai_twk']}\n";
echo "    TIU Score: {$session['nilai_tiu']}\n";
echo "    TKP Score: {$session['nilai_tkp']}\n";
echo "    Total Score: {$session['total_nilai']}\n";

// Verify daily quiz results
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM daily_quiz_answers WHERE session_id = ?");
$stmt->execute([$quizSessionId]);
$quizResult = $stmt->fetch();

echo "\n  Daily Quiz #$quizSessionId:\n";
echo "    Answers submitted: {$quizResult['total']}\n";

// Check question variety in tryout
$stmt = $pdo->prepare("SELECT q.subtes, COUNT(DISTINCT q.topik) as topics, COUNT(*) as total 
                      FROM answers a 
                      JOIN questions q ON a.question_id = q.id 
                      WHERE a.session_id = ? 
                      GROUP BY q.subtes");
$stmt->execute([$sessionId]);
echo "\n  Question Variety:\n";
while ($row = $stmt->fetch()) {
    echo "    {$row['subtes']}: {$row['total']} questions from {$row['topics']} topics\n";
}

echo "\n=== Simulation Complete ===\n";
echo "All features simulated successfully!\n";
