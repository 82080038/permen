<?php
/**
 * Clear user exam data
 * This deletes all user answers, tryout sessions, and daily quiz data
 */

require_once __DIR__ . '/../config.php';

echo "=== Clearing User Exam Data ===\n\n";

// Delete in correct order due to foreign key constraints
$tables = [
    'daily_quiz_answers',
    'daily_quiz_questions',
    'daily_quiz_sessions',
    'session_subtes',
    'answers',
    'tryout_sessions'
];

foreach ($tables as $table) {
    $stmt = $pdo->query("DELETE FROM $table");
    $deleted = $stmt->rowCount();
    echo "Deleted $deleted rows from $table\n";
}

// Reset auto-increment for tables
echo "\nResetting auto-increment...\n";
$pdo->exec("ALTER TABLE tryout_sessions AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE answers AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE daily_quiz_sessions AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE daily_quiz_answers AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE session_subtes AUTO_INCREMENT = 1");

echo "\nUser exam data cleared successfully!\n";
