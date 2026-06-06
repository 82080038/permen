<?php
/**
 * Proper fix for TWK generator - replace jawaban_benar letters with actual option text
 */

$file = 'C:\xampp\htdocs\permen\api\generators\twk_generator.php';
$content = file_get_contents($file);

// Split into lines and process each question block
$lines = explode("\n", $content);
$fixedLines = [];
$i = 0;

while ($i < count($lines)) {
    $line = $lines[$i];
    
    // Check if this line starts a question block
    if (preg_match("/'pertanyaan'\s*=>/", $line)) {
        $options = [];
        $correctLetter = '';
        $correctLineIndex = -1;
        
        // Look ahead for options and jawaban_benar
        for ($j = $i; $j < min($i + 15, count($lines)); $j++) {
            // Extract options
            if (preg_match("/'pilihan_([a-e])'\s*=>\s*'([^']+)'/", $lines[$j], $matches)) {
                $options[strtoupper($matches[1])] = $matches[2];
            }
            
            // Check for jawaban_benar
            if (preg_match("/'jawaban_benar'\s*=>\s*'([ABCDE])'/", $lines[$j], $matches)) {
                $correctLetter = $matches[1];
                $correctLineIndex = $j;
            }
        }
        
        // Replace with actual text if found
        if ($correctLineIndex >= 0 && isset($options[$correctLetter])) {
            $correctText = $options[$correctLetter];
            $lines[$correctLineIndex] = str_replace(
                "'jawaban_benar' => '$correctLetter'",
                "'jawaban_benar' => '$correctText'",
                $lines[$correctLineIndex]
            );
        }
    }
    
    $fixedLines[] = $lines[$i];
    $i++;
}

file_put_contents($file, implode("\n", $fixedLines));
echo "TWK generator fixed properly!\n";
