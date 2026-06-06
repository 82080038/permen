<?php
/**
 * Test Individual Generator Functions
 * Tests each generator function to ensure they return valid question data
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api/generators/helpers.php';
require_once __DIR__ . '/../api/generators/tiu_generator.php';
require_once __DIR__ . '/../api/generators/twk_generator.php';
require_once __DIR__ . '/../api/generators/tkp_generator.php';

echo "=== Testing Individual Generator Functions ===\n\n";

$testCount = 0;
$passCount = 0;
$failCount = 0;

function testGenerator($name, $func) {
    global $testCount, $passCount, $failCount;
    $testCount++;
    echo "Testing $name... ";
    
    try {
        $result = $func();
        
        // Validate structure
        $required = ['pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'pilihan_e', 'jawaban_benar', 'pembahasan'];
        $missing = [];
        foreach ($required as $field) {
            if (!isset($result[$field]) || empty($result[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            echo "FAIL - Missing fields: " . implode(', ', $missing) . "\n";
            $failCount++;
            return false;
        }
        
        // Validate answer is valid option
        $validAnswers = ['A', 'B', 'C', 'D', 'E'];
        if (!in_array($result['jawaban_benar'], $validAnswers)) {
            echo "FAIL - Invalid answer: {$result['jawaban_benar']}\n";
            $failCount++;
            return false;
        }
        
        echo "PASS\n";
        $passCount++;
        return true;
    } catch (Exception $e) {
        echo "FAIL - Exception: " . $e->getMessage() . "\n";
        $failCount++;
        return false;
    }
}

// Test TKP generators
echo "--- TKP Generators ---\n";
testGenerator('generateTKP_PelayananPublik', 'generateTKP_PelayananPublik');
testGenerator('generateTKP_JejaringKerja', 'generateTKP_JejaringKerja');
testGenerator('generateTKP_SosialBudaya', 'generateTKP_SosialBudaya');
testGenerator('generateTKP_TeknologiInformasi', 'generateTKP_TeknologiInformasi');
testGenerator('generateTKP_Profesionalisme', 'generateTKP_Profesionalisme');
testGenerator('generateTKP_Kepribadian', 'generateTKP_Kepribadian');

// Test TWK generators
echo "\n--- TWK Generators ---\n";
testGenerator('generateTWK_Nasionalisme', 'generateTWK_Nasionalisme');
testGenerator('generateTWK_Sejarah', 'generateTWK_Sejarah');
testGenerator('generateTWK_Pancasila', 'generateTWK_Pancasila');
testGenerator('generateTWK_BahasaIndonesia', 'generateTWK_BahasaIndonesia');
testGenerator('generateTWK_UUD1945', 'generateTWK_UUD1945');
testGenerator('generateTWK_PilarNegara', 'generateTWK_PilarNegara');
testGenerator('generateTWK_Integritas', 'generateTWK_Integritas');
testGenerator('generateTWK_BelaNegara', 'generateTWK_BelaNegara');

// Test TIU generators
echo "\n--- TIU Generators ---\n";
testGenerator('generateDeretAngka', function() { return generateDeretAngka('sedang'); });
testGenerator('generateBerhitung', function() { return generateBerhitung('sedang'); });
testGenerator('generatePerbandingan', 'generatePerbandingan');
testGenerator('generateSoalCerita', 'generateSoalCerita');
testGenerator('generateAnalogi', 'generateAnalogi');
testGenerator('generateSilogisme', 'generateSilogisme');
testGenerator('generateKetidaksamaan', 'generateKetidaksamaan');
testGenerator('generateSerial', 'generateSerial');
testGenerator('generateAnalitis', 'generateAnalitis');

// Summary
echo "\n=== Test Summary ===\n";
echo "Total tests: $testCount\n";
echo "Passed: $passCount\n";
echo "Failed: $failCount\n";

if ($failCount === 0) {
    echo "\nAll generator functions are working correctly!\n";
} else {
    echo "\nSome generator functions failed. Please review the errors above.\n";
}
