<?php
/**
 * Database Integrity Check Script
 * Verifies the state of questions after batch generation
 */

require_once __DIR__ . '/../config.php';

echo "=== Database Integrity Check ===\n\n";

// Check total questions
$stmt = $pdo->query('SELECT COUNT(*) as total FROM questions WHERE is_active = 1');
echo "Total active questions: " . $stmt->fetch()['total'] . "\n\n";

// Check by subtest
echo "Questions by subtest:\n";
$stmt = $pdo->query('SELECT subtes, COUNT(*) as total FROM questions WHERE is_active = 1 GROUP BY subtes');
while ($row = $stmt->fetch()) {
    echo "  {$row['subtes']}: {$row['total']}\n";
}

echo "\nQuestions by topic (TKP):\n";
$stmt = $pdo->query('SELECT topik, COUNT(*) as total FROM questions WHERE subtes = "TKP" AND is_active = 1 GROUP BY topik ORDER BY total DESC');
while ($row = $stmt->fetch()) {
    echo "  {$row['topik']}: {$row['total']}\n";
}

echo "\nQuestions by topic (TWK):\n";
$stmt = $pdo->query('SELECT topik, COUNT(*) as total FROM questions WHERE subtes = "TWK" AND is_active = 1 GROUP BY topik ORDER BY total DESC');
while ($row = $stmt->fetch()) {
    echo "  {$row['topik']}: {$row['total']}\n";
}

echo "\nQuestions by topic (TIU):\n";
$stmt = $pdo->query('SELECT topik, COUNT(*) as total FROM questions WHERE subtes = "TIU" AND is_active = 1 GROUP BY topik ORDER BY total DESC');
while ($row = $stmt->fetch()) {
    echo "  {$row['topik']}: {$row['total']}\n";
}

// Check for duplicates
echo "\n=== Duplicate Check ===\n";
$stmt = $pdo->query('SELECT pertanyaan, COUNT(*) as count FROM questions WHERE is_active = 1 GROUP BY pertanyaan HAVING COUNT(*) > 1 LIMIT 10');
$duplicates = $stmt->fetchAll();
if (count($duplicates) > 0) {
    echo "Found " . count($duplicates) . " duplicate questions (showing first 10):\n";
    foreach ($duplicates as $dup) {
        echo "  \"" . substr($dup['pertanyaan'], 0, 50) . "...\" appears {$dup['count']} times\n";
    }
} else {
    echo "No duplicate questions found.\n";
}

// Check questions with (Var suffix
echo "\n=== Variation Questions ===\n";
$stmt = $pdo->query('SELECT COUNT(*) as total FROM questions WHERE pertanyaan LIKE "%(Var%" AND is_active = 1');
echo "Questions with variation suffix: " . $stmt->fetch()['total'] . "\n";

// Check for null values
echo "\n=== Null Value Check ===\n";
$stmt = $pdo->query('SELECT COUNT(*) as total FROM questions WHERE is_active = 1 AND (pertanyaan IS NULL OR jawaban_benar IS NULL)');
$nulls = $stmt->fetch()['total'];
echo "Questions with null critical fields: " . $nulls . "\n";

echo "\n=== Integrity Check Complete ===\n";
