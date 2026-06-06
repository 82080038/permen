<?php
require_once __DIR__ . '/../config.php';

echo "=== Verifying Simulation Results ===\n\n";

// Check tryout session
$stmt = $pdo->query("SELECT * FROM tryout_sessions ORDER BY id DESC LIMIT 1");
$session = $stmt->fetch();

if ($session) {
    echo "Tryout Session #{$session['id']}:\n";
    echo "  Status: {$session['status']}\n";
    echo "  User ID: {$session['user_id']}\n";
    echo "  Total Score: " . ($session['total_skor'] ?? 'N/A') . "\n\n";
    
    // Check answers with jawaban_benar
    $stmt = $pdo->prepare("
        SELECT a.id, q.subtes, q.pertanyaan, a.jawaban_user, q.jawaban_benar, a.skor
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        WHERE a.session_id = ?
        ORDER BY q.subtes
    ");
    $stmt->execute([$session['id']]);
    $answers = $stmt->fetchAll();
    
    echo "Answers Detail:\n";
    foreach ($answers as $ans) {
        $match = ($ans['jawaban_user'] === $ans['jawaban_benar']) ? '✓' : '✗';
        echo "  [{$ans['subtes']}] {$match} User: " . substr($ans['jawaban_user'], 0, 30) . "... | Correct: " . substr($ans['jawaban_benar'], 0, 30) . "... | Score: {$ans['skor']}\n";
    }
}

// Check daily quiz
$stmt = $pdo->query("SELECT * FROM daily_quiz_sessions ORDER BY id DESC LIMIT 1");
$quiz = $stmt->fetch();

if ($quiz) {
    echo "\nDaily Quiz Session #{$quiz['id']}:\n";
    echo "  User ID: {$quiz['user_id']}\n";
    echo "  Quiz Date: {$quiz['quiz_date']}\n";
    echo "  Total Soal: {$quiz['total_soal']}\n\n";
    
    // Check daily quiz answers
    $stmt = $pdo->prepare("
        SELECT dqa.id, q.subtes, q.pertanyaan, dqa.jawaban_user, q.jawaban_benar
        FROM daily_quiz_answers dqa
        JOIN questions q ON dqa.question_id = q.id
        WHERE dqa.session_id = ?
        ORDER BY q.subtes
    ");
    $stmt->execute([$quiz['id']]);
    $quizAnswers = $stmt->fetchAll();
    
    echo "Daily Quiz Answers:\n";
    foreach ($quizAnswers as $ans) {
        $match = ($ans['jawaban_user'] === $ans['jawaban_benar']) ? '✓' : '✗';
        echo "  [{$ans['subtes']}] {$match} User: " . substr($ans['jawaban_user'], 0, 30) . "... | Correct: " . substr($ans['jawaban_benar'], 0, 30) . "...\n";
    }
}

echo "\n=== Verification Complete ===\n";
