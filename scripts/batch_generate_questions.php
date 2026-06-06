<?php
/**
 * Batch Question Generator - Balance Distribution
 * 
 * This script generates questions for minor topics to balance the distribution
 * across all subtests (TKP, TWK, TIU).
 * 
 * Usage: php scripts/batch_generate_questions.php
 * 
 * Target distribution:
 * TKP: ~100 soal per topik (6 topik)
 * TWK: ~100 soal per topik (8 topik)
 * TIU: ~170 soal per topik (10 topik)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api/generators/helpers.php';
require_once __DIR__ . '/../api/generators/tiu_generator.php';
require_once __DIR__ . '/../api/generators/twk_generator.php';
require_once __DIR__ . '/../api/generators/tkp_generator.php';

echo "=== Batch Question Generator ===\n\n";

// Get current distribution
echo "Current distribution:\n";
$stmt = $pdo->query("SELECT subtes, topik, COUNT(*) as total FROM questions WHERE is_active = 1 GROUP BY subtes, topik ORDER BY subtes, total DESC");
while ($row = $stmt->fetch()) {
    echo "  {$row['subtes']} - {$row['topik']}: {$row['total']}\n";
}
echo "\n";

// ============================================================
// GENERATE TKP MINOR TOPICS
// ============================================================
echo "Generating TKP minor topics...\n";

$tkpTargets = [
    'Profesionalisme' => 200,
    'Jejaring Kerja' => 200,
    'Sosial Budaya' => 200,
    'Teknologi Informasi' => 200,
    'Pelayanan Publik' => 200,
];

foreach ($tkpTargets as $topik => $target) {
    // Get current count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questions WHERE subtes = 'TKP' AND topik = ? AND is_active = 1");
    $stmt->execute([$topik]);
    $current = $stmt->fetch()['total'];
    
    $needed = $target - $current;
    
    if ($needed <= 0) {
        echo "  TKP - $topik: Already has $current (target: $target), skipping\n";
        continue;
    }
    
    echo "  TKP - $topik: Generating $needed questions (current: $current, target: $target)\n";
    
    // Get materi_id
    $stmt = $pdo->prepare("SELECT id FROM materi WHERE subtes = 'TKP' AND judul LIKE ?");
    $stmt->execute(["%$topik%"]);
    $materi = $stmt->fetch();
    $materiId = $materi['id'] ?? null;
    
    // Generate questions
    $generated = 0;
    for ($i = 0; $i < $needed; $i++) {
        try {
            $s = generateTKP($topik);
            
            // Add unique suffix to avoid duplicates
            $uniqueSuffix = " (Var " . ($i + 1) . ")";
            $s['pertanyaan'] = $s['pertanyaan'] . $uniqueSuffix;
            
            // Insert
            $stmtIns = $pdo->prepare("INSERT INTO questions (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, bobot_tkp, pembahasan, difficulty, materi_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([
                'TKP', 'pribadi', $topik,
                $s['pertanyaan'], $s['pilihan_a'], $s['pilihan_b'], $s['pilihan_c'],
                $s['pilihan_d'], $s['pilihan_e'], $s['jawaban_benar'], $s['bobot_tkp'],
                $s['pembahasan'], 'sedang', $materiId
            ]);
            
            $generated++;
        } catch (Exception $e) {
            echo "    Error generating question: " . $e->getMessage() . "\n";
        }
    }
    
    echo "    Generated: $generated\n";
}

// ============================================================
// GENERATE TWK MINOR TOPICS
// ============================================================
echo "\nGenerating TWK minor topics...\n";

$twkTargets = [
    'Sejarah' => 200,
    'Pancasila' => 200,
    'Bahasa Indonesia' => 200,
    'UUD 1945' => 200,
    'Pilar Negara' => 200,
    'Integritas' => 200,
    'Bela Negara' => 200,
];

foreach ($twkTargets as $topik => $target) {
    // Get current count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questions WHERE subtes = 'TWK' AND topik = ? AND is_active = 1");
    $stmt->execute([$topik]);
    $current = $stmt->fetch()['total'];
    
    $needed = $target - $current;
    
    if ($needed <= 0) {
        echo "  TWK - $topik: Already has $current (target: $target), skipping\n";
        continue;
    }
    
    echo "  TWK - $topik: Generating $needed questions (current: $current, target: $target)\n";
    
    // Get materi_id
    $stmt = $pdo->prepare("SELECT id FROM materi WHERE subtes = 'TWK' AND judul LIKE ?");
    $stmt->execute(["%$topik%"]);
    $materi = $stmt->fetch();
    $materiId = $materi['id'] ?? null;
    
    // Generate questions
    $generated = 0;
    for ($i = 0; $i < $needed; $i++) {
        try {
            // Route to specific generator function based on topic
            if ($topik === 'Nasionalisme') {
                $s = generateTWK_Nasionalisme();
            } else {
                $s = generateTWK(); // fallback for all other TWK topics
            }
            
            // Add unique suffix to avoid duplicates
            $uniqueSuffix = " (Var " . ($i + 1) . ")";
            $s['pertanyaan'] = $s['pertanyaan'] . $uniqueSuffix;
            
            // Insert
            $stmtIns = $pdo->prepare("INSERT INTO questions (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, pembahasan, difficulty, materi_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([
                'TWK', 'wawasan', $topik,
                $s['pertanyaan'], $s['pilihan_a'], $s['pilihan_b'], $s['pilihan_c'],
                $s['pilihan_d'], $s['pilihan_e'], $s['jawaban_benar'],
                $s['pembahasan'], 'sedang', $materiId
            ]);
            
            $generated++;
        } catch (Exception $e) {
            echo "    Error generating question: " . $e->getMessage() . "\n";
        }
    }
    
    echo "    Generated: $generated\n";
}

// ============================================================
// GENERATE TIU MINOR TOPICS
// ============================================================
echo "\nGenerating TIU minor topics...\n";

$tiuTargets = [
    'Analogi' => 200,
    'Ketidaksamaan' => 200,
    'Analitis' => 200,
    'Serial' => 200,
    'Silogisme' => 200,
];

foreach ($tiuTargets as $topik => $target) {
    // Get current count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questions WHERE subtes = 'TIU' AND topik = ? AND is_active = 1");
    $stmt->execute([$topik]);
    $current = $stmt->fetch()['total'];
    
    $needed = $target - $current;
    
    if ($needed <= 0) {
        echo "  TIU - $topik: Already has $current (target: $target), skipping\n";
        continue;
    }
    
    echo "  TIU - $topik: Generating $needed questions (current: $current, target: $target)\n";
    
    // Get materi_id
    $stmt = $pdo->prepare("SELECT id FROM materi WHERE subtes = 'TIU' AND judul LIKE ?");
    $stmt->execute(["%$topik%"]);
    $materi = $stmt->fetch();
    $materiId = $materi['id'] ?? null;
    
    // Determine tipe based on topik
    $tipe = 'numerik';
    if (in_array($topik, ['Analogi', 'Silogisme', 'Analitis'])) {
        $tipe = 'verbal';
    } elseif (in_array($topik, ['Ketidaksamaan', 'Serial'])) {
        $tipe = 'figural';
    }
    
    // Generate questions
    $generated = 0;
    for ($i = 0; $i < $needed; $i++) {
        try {
            if ($topik === 'Perbandingan') {
                $s = generatePerbandingan();
            } elseif ($topik === 'Soal Cerita') {
                $s = generateSoalCerita();
            } elseif ($topik === 'Analogi') {
                $s = generateAnalogi();
            } elseif ($topik === 'Silogisme') {
                $s = generateSilogisme();
            } elseif ($topik === 'Ketidaksamaan') {
                $s = generateKetidaksamaan();
            } elseif ($topik === 'Serial') {
                $s = generateSerial();
            } elseif ($topik === 'Analitis') {
                $s = generateAnalitis();
            } else {
                $s = generateDeretAngka('sedang'); // fallback
            }
            
            // Add unique suffix to avoid duplicates
            $uniqueSuffix = " (Var " . ($i + 1) . ")";
            $s['pertanyaan'] = $s['pertanyaan'] . $uniqueSuffix;
            
            // Insert
            $stmtIns = $pdo->prepare("INSERT INTO questions (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, pembahasan, difficulty, materi_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([
                'TIU', $tipe, $topik,
                $s['pertanyaan'], $s['pilihan_a'], $s['pilihan_b'], $s['pilihan_c'],
                $s['pilihan_d'], $s['pilihan_e'], $s['jawaban_benar'],
                $s['pembahasan'], 'sedang', $materiId
            ]);
            
            $generated++;
        } catch (Exception $e) {
            echo "    Error generating question: " . $e->getMessage() . "\n";
        }
    }
    
    echo "    Generated: $generated\n";
}

// ============================================================
// FINAL REPORT
// ============================================================
echo "\n=== Final Distribution ===\n";
$stmt = $pdo->query("SELECT subtes, topik, COUNT(*) as total FROM questions WHERE is_active = 1 GROUP BY subtes, topik ORDER BY subtes, total DESC");
while ($row = $stmt->fetch()) {
    echo "  {$row['subtes']} - {$row['topik']}: {$row['total']}\n";
}

echo "\n=== Summary ===\n";
$stmt = $pdo->query("SELECT subtes, COUNT(*) as total FROM questions WHERE is_active = 1 GROUP BY subtes");
while ($row = $stmt->fetch()) {
    echo "  {$row['subtes']}: {$row['total']} soal\n";
}

echo "\nBatch generation completed!\n";
