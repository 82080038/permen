<?php
require_once __DIR__ . '/../config.php';

echo "=== Altering jawaban_benar column ===\n\n";

try {
    $pdo->exec("ALTER TABLE questions MODIFY COLUMN jawaban_benar VARCHAR(255)");
    echo "✓ Column altered successfully\n";
    
    // Verify
    $stmt = $pdo->query("SHOW COLUMNS FROM questions LIKE 'jawaban_benar'");
    $column = $stmt->fetch();
    echo "\nNew column type: " . $column['Type'] . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
