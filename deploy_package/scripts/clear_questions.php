<?php
/**
 * Clear all generated questions from database
 * This is needed to regenerate questions with fixed jawaban_benar field
 */

require_once __DIR__ . '/../config.php';

echo "=== Clearing Questions Database ===\n\n";

// Delete all questions
$stmt = $pdo->query("DELETE FROM questions");
$deleted = $stmt->rowCount();

echo "Deleted $deleted questions from database.\n";

// Reset auto-increment
$stmt = $pdo->query("ALTER TABLE questions AUTO_INCREMENT = 1");
echo "Reset auto-increment.\n";

echo "\nDatabase cleared successfully!\n";
