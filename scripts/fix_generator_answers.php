<?php
/**
 * Fix jawaban_benar in generator files
 * Replaces letter labels (A, B, C, D, E) with actual option text
 */

$generators = [
    'C:\xampp\htdocs\permen\api\generators\tkp_generator.php',
    'C:\xampp\htdocs\permen\api\generators\twk_generator.php'
];

foreach ($generators as $file) {
    echo "Processing $file...\n";
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Pattern to match question blocks and fix jawaban_benar
    // This regex matches from 'pertanyaan' to 'pembahasan' and captures the options
    $pattern = '/(
        \'pertanyaan\'\s*=>\s*\'[^\']+\',
        (?:\'pilihan_[a-e]\'\s*=>\s*\'[^\']+\',\s*){5}
        \'jawaban_benar\'\s*=>\s*\'([ABCDE])\',
    )/x';
    
    $callback = function($matches) {
        $block = $matches[1];
        $correctLetter = $matches[2];
        
        // Extract options
        preg_match_all('/\'pilihan_([a-e])\'\s*=>\s*\'([^\']+)\'/', $block, $optionMatches, PREG_SET_ORDER);
        
        $options = [];
        foreach ($optionMatches as $opt) {
            $options[$opt[1]] = $opt[2];
        }
        
        // Get the correct option text
        $correctText = $options[strtolower($correctLetter)] ?? '';
        
        if ($correctText) {
            // Replace the letter with the actual text
            $fixed = str_replace(
                "'jawaban_benar' => '$correctLetter'",
                "'jawaban_benar' => '$correctText'",
                $block
            );
            return $fixed;
        }
        
        return $block;
    };
    
    $content = preg_replace_callback($pattern, $callback, $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "  ✓ Fixed\n";
    } else {
        echo "  No changes needed\n";
    }
}

echo "\nDone!\n";
