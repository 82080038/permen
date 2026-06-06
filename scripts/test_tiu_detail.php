<?php
/**
 * Detailed test for TIU generators that are failing
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api/generators/helpers.php';
require_once __DIR__ . '/../api/generators/tiu_generator.php';

echo "=== Detailed TIU Generator Test ===\n\n";

$functions = ['generateAnalogi', 'generateSilogisme', 'generateKetidaksamaan', 'generateSerial', 'generateAnalitis'];

foreach ($functions as $funcName) {
    echo "Testing $funcName:\n";
    $result = $funcName();
    
    echo "  pertanyaan: " . (isset($result['pertanyaan']) ? "SET" : "NOT SET") . "\n";
    echo "  jawaban_benar: " . (isset($result['jawaban_benar']) ? "'{$result['jawaban_benar']}'" : "NOT SET") . "\n";
    echo "  pembahasan: " . (isset($result['pembahasan']) ? "SET" : "NOT SET") . "\n";
    
    if (isset($result['jawaban_benar'])) {
        echo "  Options:\n";
        echo "    A: {$result['pilihan_a']}\n";
        echo "    B: {$result['pilihan_b']}\n";
        echo "    C: {$result['pilihan_c']}\n";
        echo "    D: {$result['pilihan_d']}\n";
        echo "    E: {$result['pilihan_e']}\n";
    }
    
    echo "\n";
}
