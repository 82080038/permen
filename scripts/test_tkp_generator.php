<?php
require_once __DIR__ . '/../api/generators/tkp_generator.php';

echo "Testing TKP generator functions...\n\n";

$topics = ['Pelayanan Publik', 'Jejaring Kerja', 'Sosial Budaya', 'Teknologi Informasi', 'Profesionalisme'];

foreach ($topics as $topic) {
    try {
        echo "Testing $topic...\n";
        $result = generateTKP($topic);
        echo "  Success! Keys: " . implode(', ', array_keys($result)) . "\n";
        echo "  Jawaban benar: " . $result['jawaban_benar'] . "\n\n";
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n\n";
    }
}
