<?php
require_once __DIR__ . '/../config.php';

echo "=== Checking jawaban_benar field ===\n\n";

$stmt = $pdo->query("SELECT pertanyaan, jawaban_benar FROM questions LIMIT 10");
$questions = $stmt->fetchAll();

foreach ($questions as $row) {
    echo "Q: " . substr($row['pertanyaan'], 0, 50) . "...\n";
    echo "A: " . $row['jawaban_benar'] . "\n";
    echo "\n";
}
