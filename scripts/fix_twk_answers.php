<?php
/**
 * Fix jawaban_benar in TWK generator
 * Replaces letter labels (A, B, C, D, E) with actual option text
 */

$file = 'C:\xampp\htdocs\permen\api\generators\twk_generator.php';
$content = file_get_contents($file);

// Pattern to match each question block and extract options
$pattern = '/(
    \'pertanyaan\'\s*=>\s*\'[^\']+\',\s*
    \'pilihan_a\'\s*=>\s*\'([^\']+)\',\s*
    \'pilihan_b\'\s*=>\s*\'([^\']+)\',\s*
    \'pilihan_c\'\s*=>\s*\'([^\']+)\',\s*
    \'pilihan_d\'\s*=>\s*\'([^\']+)\',\s*
    \'pilihan_e\'\s*=>\s*\'([^\']+)\',\s*
    \'jawaban_benar\'\s*=>\s*\'([ABCDE])\',
)/x';

$callback = function($matches) {
    $fullBlock = $matches[0];
    $optA = $matches[1];
    $optB = $matches[2];
    $optC = $matches[3];
    $optD = $matches[4];
    $optE = $matches[5];
    $correctLetter = $matches[6];
    
    $options = [
        'A' => $optA,
        'B' => $optB,
        'C' => $optC,
        'D' => $optD,
        'E' => $optE
    ];
    
    $correctText = $options[$correctLetter] ?? '';
    
    if ($correctText) {
        $fixed = str_replace(
            "'jawaban_benar' => '$correctLetter'",
            "'jawaban_benar' => '$correctText'",
            $fullBlock
        );
        return $fixed;
    }
    
    return $fullBlock;
};

$content = preg_replace_callback($pattern, $callback, $content);

file_put_contents($file, $content);
echo "TWK generator fixed!\n";
