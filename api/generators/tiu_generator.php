<?php
/**
 * TIU (Tes Intelegensia Umum) Question Generator
 * 
 * Generates TIU questions including:
 * - Deret Angka (Number sequences)
 * - Berhitung (Calculations)
 * - Perbandingan (Ratios/Proportions)
 * - Soal Cerita (Word problems)
 * - Passage Logika (Logic passages)
 */

require_once __DIR__ . '/helpers.php';

/**
 * Generate number sequence question (deret angka)
 * @param string $kesulitan Difficulty level (mudah, sedang, sulit)
 * @return array Question data with options and explanation
 */
function generateDeretAngka(string $kesulitan): array {
    $patterns = [
        'aritmatika' => function() {
            $a = randomInt(1, 20); $d = randomInt(2, 10);
            $seq = [$a, $a+$d, $a+2*$d, $a+3*$d, $a+4*$d];
            $next = $a+5*$d;
            return [$seq, $next, "Pola aritmatika: selisih tetap +$d."];
        },
        'geometri' => function() {
            $a = randomInt(1, 5); $r = randomInt(2, 4);
            $seq = [$a, $a*$r, $a*$r*$r, $a*$r*$r*$r, $a*$r*$r*$r*$r];
            $next = $a*pow($r, 5);
            return [$seq, $next, "Pola geometri: dikali $r setiap langkah."];
        },
        'kuadratik' => function() {
            $n = randomInt(1, 3);
            $seq = [];
            for ($i=1; $i<=5; $i++) $seq[] = $i*$i + $n;
            $next = 6*6 + $n;
            return [$seq, $next, "Pola kuadratik: n² + $n."];
        },
        'selisih_naik' => function() {
            $a = randomInt(1, 10); $d = randomInt(2, 5);
            $seq = [$a];
            $cd = $d;
            for ($i=1; $i<5; $i++) { $seq[] = $seq[$i-1] + $cd; $cd += 2; }
            $next = $seq[4] + $cd;
            return [$seq, $next, "Pola selisih naik: beda dari beda bertambah 2."];
        },
    ];
    
    if ($kesulitan === 'mudah') {
        $p = $patterns['aritmatika'];
    } elseif ($kesulitan === 'sedang') {
        $keys = ['geometri', 'kuadratik'];
        $p = $patterns[$keys[array_rand($keys)]];
    } else {
        $p = $patterns['selisih_naik'];
    }
    
    list($seq, $next, $pembahasan) = $p();
    $soal = 'Deret: ' . implode(', ', $seq) . ', ...';
    
    // Buat opsi: benar + 4 distraktor
    $opts = [$next];
    $opts[] = $next + randomInt(2, 10);
    $opts[] = $next - randomInt(2, 10);
    $opts[] = $next + randomInt(11, 20);
    $opts[] = $next * 2;
    
    $labels = ['A','B','C','D','E'];
    $map = array_combine($labels, array_slice(array_unique($opts), 0, 5));
    
    // Pastikan jawaban benar ada
    $map['A'] = $next; // force ke A
    shuffle($map); // ini tidak bisa langsung shuffle assoc, pakai cara lain
    
    // Manual shuffle
    $entries = [];
    foreach ($map as $k=>$v) $entries[] = ['label'=>$k, 'val'=>$v];
    shuffle($entries);
    $options = [];
    $correct = '';
    foreach ($entries as $i=>$e) {
        $lbl = $labels[$i];
        $options[$lbl] = $e['val'];
        if ($e['val'] == $next) $correct = $lbl;
    }
    
    return [
        'pertanyaan' => $soal,
        'pilihan_a' => (string)$options['A'],
        'pilihan_b' => (string)$options['B'],
        'pilihan_c' => (string)$options['C'],
        'pilihan_d' => (string)$options['D'],
        'pilihan_e' => (string)$options['E'],
        'jawaban_benar' => $correct,
        'pembahasan' => $pembahasan . ' Angka selanjutnya adalah ' . $next . '.'
    ];
}

/**
 * Generate calculation question (berhitung)
 * @param string $kesulitan Difficulty level
 * @return array Question data with options and explanation
 */
function generateBerhitung(string $kesulitan): array {
    $templates = [
        function() {
            $a = randomInt(10, 99); $b = randomInt(10, 99);
            $soal = "Hasil dari $a + $b × 2 adalah...";
            $ans = $a + $b * 2;
            return [$soal, $ans, "Perkalian dulu: $b × 2 = " . ($b*2) . ", lalu + $a = $ans."];
        },
        function() {
            $a = randomInt(50, 200); $pct = randomInt(10, 40);
            $soal = "$pct% dari $a adalah...";
            $ans = round($a * $pct / 100);
            return [$soal, $ans, "$pct/100 × $a = $ans."];
        },
        function() {
            $n = randomInt(2, 9);
            $soal = "Akar kuadrat dari " . ($n*$n) . " adalah...";
            return [$soal, $n, "√" . ($n*$n) . " = $n."];
        },
    ];
    
    $t = $templates[array_rand($templates)];
    list($soal, $ans, $pembahasan) = $t();
    
    $opts = [$ans, $ans+1, $ans+5, $ans-1, $ans+10];
    if ($opts[1]==$opts[0]) $opts[1] += 2;
    
    $labels = ['A','B','C','D','E'];
    $entries = [];
    foreach ($opts as $v) $entries[] = ['val'=>$v];
    shuffle($entries);
    $options = [];
    $correct = '';
    foreach ($entries as $i=>$e) {
        $lbl = $labels[$i];
        $options[$lbl] = $e['val'];
        if ($e['val'] == $ans) $correct = $lbl;
    }
    
    return [
        'pertanyaan' => $soal,
        'pilihan_a' => (string)$options['A'],
        'pilihan_b' => (string)$options['B'],
        'pilihan_c' => (string)$options['C'],
        'pilihan_d' => (string)$options['D'],
        'pilihan_e' => (string)$options['E'],
        'jawaban_benar' => $correct,
        'pembahasan' => $pembahasan
    ];
}

/**
 * Generate comparison question (perbandingan)
 * @return array Question data with options and explanation
 */
function generatePerbandingan(): array {
    $a = randomInt(2, 8); $b = randomInt(2, 8);
    $total = ($a + $b) * randomInt(10, 30);
    $soal = "Perbandingan A dan B adalah $a : $b. Jika total $total, berapa nilai A?";
    $ans = $total * $a / ($a + $b);
    $pembahasan = "A = $a/($a+$b) × $total = " . ($a/($a+$b)) . " × $total = $ans.";
    
    $opts = [$ans, $ans+5, $ans-5, $total-$ans, round($total/2)];
    
    $labels = ['A','B','C','D','E'];
    $entries = [];
    foreach ($opts as $v) $entries[] = ['val'=>$v];
    shuffle($entries);
    $options = []; $correct = '';
    foreach ($entries as $i=>$e) {
        $lbl = $labels[$i]; $options[$lbl] = $e['val'];
        if ($e['val'] == $ans) $correct = $lbl;
    }
    
    return [
        'pertanyaan' => $soal,
        'pilihan_a' => (string)$options['A'],
        'pilihan_b' => (string)$options['B'],
        'pilihan_c' => (string)$options['C'],
        'pilihan_d' => (string)$options['D'],
        'pilihan_e' => (string)$options['E'],
        'jawaban_benar' => $correct,
        'pembahasan' => $pembahasan
    ];
}

/**
 * Generate word problem question (soal cerita)
 * @return array Question data with options and explanation
 */
function generateSoalCerita(): array {
    $templates = [
        function() {
            $jarak = randomInt(120, 360); $jam = randomInt(2, 5);
            $soal = "Sebuah mobil menempuh jarak $jarak km dalam $jam jam. Kecepatan rata-rata?";
            $ans = $jarak / $jam;
            return [$soal, $ans, "V = jarak ÷ waktu = $jarak ÷ $jam = $ans km/jam."];
        },
        function() {
            $modal = randomInt(20, 50) * 1000;
            $untung = randomInt(10, 30);
            $soal = "Pedagang membeli barang Rp" . number_format($modal) . ". Jika dijual untung $untung%, berapa harga jual?";
            $ans = $modal * (100 + $untung) / 100;
            return [$soal, $ans, "Jual = modal + ($untung% × modal) = $modal + " . ($modal*$untung/100) . " = $ans."];
        },
    ];
    
    $t = $templates[array_rand($templates)];
    list($soal, $ans, $pembahasan) = $t();
    
    $opts = [$ans, $ans+10, $ans-10, $ans*2, round($ans/2)];
    
    $labels = ['A','B','C','D','E'];
    $entries = [];
    foreach ($opts as $v) $entries[] = ['val'=>$v];
    shuffle($entries);
    $options = []; $correct = '';
    foreach ($entries as $i=>$e) {
        $lbl = $labels[$i]; $options[$lbl] = $e['val'];
        if ($e['val'] == $ans) $correct = $lbl;
    }
    
    return [
        'pertanyaan' => $soal,
        'pilihan_a' => 'Rp' . number_format($options['A']),
        'pilihan_b' => 'Rp' . number_format($options['B']),
        'pilihan_c' => 'Rp' . number_format($options['C']),
        'pilihan_d' => 'Rp' . number_format($options['D']),
        'pilihan_e' => 'Rp' . number_format($options['E']),
        'jawaban_benar' => $correct,
        'pembahasan' => $pembahasan
    ];
}

/**
 * Generate TIU passage logic question (1 passage + multiple questions)
 * @return array Question data with options and explanation
 */
function generateTIU_PassageLogika(): array {
    $bacaan = 'Sebuah proyek pembangunan terdiri atas 6 proyek kecil: P, Q, R, S, T, dan U. Proyek kecil ini berkaitan satu dengan yang lainnya. Proyek P harus diselesaikan sebelum proyek Q dimulai. Proyek R dan S dapat dikerjakan secara bersamaan. Proyek T harus menunggu proyek Q dan R selesai. Proyek U adalah proyek terakhir yang dapat dikerjakan setelah seluruh proyek lainnya selesai. Jika proyek P memerlukan waktu 3 hari, Q 4 hari, R 2 hari, S 5 hari, T 3 hari, dan U 2 hari.';

    $soalList = [
        [
            'pertanyaan' => 'Proyek mana yang merupakan proyek pertama yang harus dikerjakan?',
            'pilihan_a' => 'P', 'pilihan_b' => 'Q', 'pilihan_c' => 'R', 'pilihan_d' => 'S', 'pilihan_e' => 'T',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Proyek P harus diselesaikan sebelum Q dimulai, dan tidak ada proyek lain yang menjadi prasyarat untuk P. Jadi P adalah proyek pertama.'
        ],
        [
            'pertanyaan' => 'Berapa total hari minimum yang dibutuhkan untuk menyelesaikan seluruh proyek?',
            'pilihan_a' => '14 hari', 'pilihan_b' => '16 hari', 'pilihan_c' => '17 hari', 'pilihan_d' => '19 hari', 'pilihan_e' => '21 hari',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Jalur kritis: P(3) → Q(4) → T(3) → U(2) = 12 hari. R(2) dan S(5) berjalan paralel, dimulai setelah P selesai. T menunggu Q dan R selesai. Total = 3 + max(4, 2+5) + 3 + 2 = 17 hari.'
        ],
        [
            'pertanyaan' => 'Proyek mana yang dapat dikerjakan secara bersamaan?',
            'pilihan_a' => 'P dan Q', 'pilihan_b' => 'R dan S', 'pilihan_c' => 'T dan U', 'pilihan_d' => 'Q dan R', 'pilihan_e' => 'P dan T',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Soal secara eksplisit menyebutkan bahwa proyek R dan S dapat dikerjakan secara bersamaan (paralel).'
        ],
        [
            'pertanyaan' => 'Setelah proyek P selesai, proyek apa saja yang dapat segera dimulai?',
            'pilihan_a' => 'Q dan R', 'pilihan_b' => 'Q dan S', 'pilihan_c' => 'Q, R, dan S', 'pilihan_d' => 'R dan T', 'pilihan_e' => 'Q dan U',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Setelah P selesai, Q dapat dimulai (prasyarat P selesai). R dan S juga dapat dimulai karena tidak ada prasyarat khusus selain tidak bertabrakan dengan jalur lain, dan soal menyebutkan R dan S dapat bersamaan.'
        ],
        [
            'pertanyaan' => 'Proyek U dapat dimulai setelah...',
            'pilihan_a' => 'Proyek P selesai', 'pilihan_b' => 'Proyek T selesai', 'pilihan_c' => 'Proyek Q dan R selesai', 'pilihan_d' => 'Proyek S selesai', 'pilihan_e' => 'Proyek Q selesai',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Proyek U adalah proyek terakhir yang dapat dikerjakan setelah seluruh proyek lainnya selesai. T adalah proyek sebelum U, sehingga U dapat dimulai setelah T selesai (yang artinya semua proyek sebelumnya juga sudah selesai).'
        ],
    ];
    
    return [
        'pertanyaan' => $soalList[array_rand($soalList)]['pertanyaan'],
        'pilihan_a' => $soalList[array_rand($soalList)]['pilihan_a'],
        'pilihan_b' => $soalList[array_rand($soalList)]['pilihan_b'],
        'pilihan_c' => $soalList[array_rand($soalList)]['pilihan_c'],
        'pilihan_d' => $soalList[array_rand($soalList)]['pilihan_d'],
        'pilihan_e' => $soalList[array_rand($soalList)]['pilihan_e'],
        'jawaban_benar' => $soalList[array_rand($soalList)]['jawaban_benar'],
        'pembahasan' => $soalList[array_rand($soalList)]['pembahasan']
    ];
}
