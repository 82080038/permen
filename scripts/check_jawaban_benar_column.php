<?php
require_once __DIR__ . '/../config.php';

echo "=== Checking jawaban_benar column structure ===\n\n";

$stmt = $pdo->query("SHOW COLUMNS FROM questions LIKE 'jawaban_benar'");
$column = $stmt->fetch();

echo "Column info:\n";
print_r($column);

echo "\n\nSample jawaban_benar lengths:\n";
$stmt = $pdo->query("SELECT LENGTH(jawaban_benar) as len, jawaban_benar FROM questions WHERE jawaban_benar IS NOT NULL LIMIT 5");
while ($row = $stmt->fetch()) {
    echo "Length: {$row['len']}, Text: " . substr($row['jawaban_benar'], 0, 50) . "...\n";
}
