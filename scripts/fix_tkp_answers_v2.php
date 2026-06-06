<?php
/**
 * Fix jawaban_benar in TKP generator - Version 2
 * Replaces letter labels (A, B, C, D, E) with actual option text
 */

$file = 'C:\xampp\htdocs\permen\api\generators\tkp_generator.php';
$content = file_get_contents($file);

// Split into lines
$lines = explode("\n", $content);
$fixedLines = [];

$i = 0;
while ($i < count($lines)) {
    $line = $lines[$i];
    
    // Check if this line starts a question block
    if (preg_match("/'pertanyaan'\s*=>/", $line)) {
        // Collect the next lines to get options
        $block = [$line];
        $options = [];
        
        // Look ahead for options
        for ($j = $i + 1; $j < min($i + 10, count($lines)); $j++) {
            $block[] = $lines[$j];
            
            // Extract options
            if (preg_match("/'pilihan_([a-e])'\s*=>\s*'([^']+)'/", $lines[$j], $matches)) {
                $options[strtoupper($matches[1])] = $matches[2];
            }
            
            // Check for jawaban_benar
            if (preg_match("/'jawaban_benar'\s*=>\s*'([ABCDE])'/", $lines[$j], $matches)) {
                $correctLetter = $matches[1];
                $correctText = $options[$correctLetter] ?? '';
                
                if ($correctText) {
                    // Replace with actual text
                    $lines[$j] = str_replace(
                        "'jawaban_benar' => '$correctLetter'",
                        "'jawaban_benar' => '$correctText'",
                        $lines[$j]
                    );
                }
                break;
            }
        }
        
        $i += count($block) - 1;
    }
    
    $fixedLines[] = $lines[$i];
    $i++;
}

file_put_contents($file, implode("\n", $fixedLines));
echo "TKP generator fixed!\n";
