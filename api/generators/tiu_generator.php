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

/**
 * Generate verbal analogy question
 * @return array Question data with options and explanation
 */
function generateAnalogi(): array {
    $analogies = [
        [
            'soal' => 'DOKTER : OBAT = GURU : ...',
            'pilihan' => ['Sekolah', 'Murid', 'Buku', 'Ilmu', 'Kelas'],
            'benar' => 'Ilmu',
            'pembahasan' => 'Dokter menggunakan obat untuk menyembuhkan. Guru menggunakan ilmu untuk mengajar. Hubungan: profesi - alat kerja.'
        ],
        [
            'soal' => 'MATAHARI : CAHAYA = LAMPU : ...',
            'pilihan' => ['Listrik', 'Cahaya', 'Bohlam', 'Gelap', 'Siang'],
            'benar' => 'Cahaya',
            'pembahasan' => 'Matahari menghasilkan cahaya. Lampu menghasilkan cahaya. Hubungan: sumber - output.'
        ],
        [
            'soal' => 'BUKU : PENULIS = LAGU : ...',
            'pilihan' => ['Penyanyi', 'Komposer', 'Musik', 'Album', 'Radio'],
            'benar' => 'Komposer',
            'pembahasan' => 'Buku ditulis oleh penulis. Lagu diciptakan oleh komposer. Hubungan: karya - pencipta.'
        ],
        [
            'soal' => 'KULIT : JERUK = KULIT : ...',
            'pilihan' => ['Apel', 'Air', 'Daging', 'Biji', 'Batang'],
            'benar' => 'Apel',
            'pembahasan' => 'Kulit jeruk adalah bagian luar buah. Kulit apel juga bagian luar buah. Hubungan: bagian - keseluruhan.'
        ],
        [
            'soal' => 'KUNCI : PINTU = PASSWORD : ...',
            'pilihan' => ['Komputer', 'Akun', 'Email', 'Internet', 'Data'],
            'benar' => 'Akun',
            'pembahasan' => 'Kunci membuka pintu. Password membuka akses akun. Hubungan: alat akses - objek.'
        ],
        [
            'soal' => 'RODA : MOBIL = SAYAP : ...',
            'pilihan' => ['Burung', 'Pesawat', 'Ikan', 'Kapal', 'Sepeda'],
            'benar' => 'Pesawat',
            'pembahasan' => 'Roda adalah komponen mobil. Sayap adalah komponen pesawat. Hubungan: komponen - kendaraan.'
        ],
        [
            'soal' => 'AIR : MINUM = MAKANAN : ...',
            'pilihan' => ['Lapar', 'Makan', 'Minum', 'Dahaga', 'Sehat'],
            'benar' => 'Makan',
            'pembahasan' => 'Air diminum untuk menghilangkan dahaga. Makanan dimakan untuk menghilangkan lapar. Hubungan: substansi - aksi.'
        ],
        [
            'soal' => 'MATA : MELIHAT = TELINGA : ...',
            'pilihan' => ['Mencium', 'Merasa', 'Mendengar', 'Meraba', 'Bicara'],
            'benar' => 'Mendengar',
            'pembahasan' => 'Mata berfungsi untuk melihat. Telinga berfungsi untuk mendengar. Hubungan: organ - fungsi.'
        ],
        [
            'soal' => 'GARAM : LAUT = PASIR : ...',
            'pilihan' => ['Air', 'Gurun', 'Gunung', 'Hutan', 'Sungai'],
            'benar' => 'Gurun',
            'pembahasan' => 'Garam ditemukan di laut. Pasir ditemukan di gurun. Hubungan: substansi - lokasi.'
        ],
        [
            'soal' => 'PEN : TINTA = KUAS : ...',
            'pilihan' => ['Kertas', 'Cat', 'Gambar', 'Warna', 'Kanvas'],
            'benar' => 'Cat',
            'pembahasan' => 'Pen menggunakan tinta untuk menulis. Kuas menggunakan cat untuk melukis. Hubungan: alat - bahan.'
        ]
    ];
    
    $a = $analogies[array_rand($analogies)];
    $labels = ['A','B','C','D','E'];
    $entries = [];
    foreach ($a['pilihan'] as $v) $entries[] = ['val'=>$v];
    shuffle($entries);
    $options = [];
    $correct = '';
    foreach ($entries as $i=>$e) {
        $lbl = $labels[$i];
        $options[$lbl] = $e['val'];
        if ($e['val'] == $a['benar']) $correct = $lbl;
    }
    
    return [
        'pertanyaan' => $a['soal'],
        'pilihan_a' => $options['A'],
        'pilihan_b' => $options['B'],
        'pilihan_c' => $options['C'],
        'pilihan_d' => $options['D'],
        'pilihan_e' => $options['E'],
        'jawaban_benar' => $correct,
        'pembahasan' => $a['pembahasan']
    ];
}

/**
 * Generate syllogism question
 * @return array Question data with options and explanation
 */
function generateSilogisme(): array {
    $syllogisms = [
        [
            'premis1' => 'Semua mahasiswa Sekolah Kedinasan adalah calon PNS.',
            'premis2' => 'Budi adalah mahasiswa Sekolah Kedinasan.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Budi belum tentu calon PNS', 'Budi adalah calon PNS', 'Budi bukan calon PNS', 'Tidak dapat disimpulkan', 'Budi adalah PNS'],
            'benar' => 'Budi adalah calon PNS',
            'pembahasan' => 'Dari premis: Semua A adalah B. C adalah A. Maka C adalah B. Budi adalah calon PNS.'
        ],
        [
            'premis1' => 'Semua penari adalah atlet.',
            'premis2' => 'Sebagian atlet adalah vegetarian.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Semua penari vegetarian', 'Sebagian penari vegetarian', 'Tidak ada penari vegetarian', 'Tidak dapat disimpulkan', 'Semua atlet vegetarian'],
            'benar' => 'Tidak dapat disimpulkan',
            'pembahasan' => 'Dari premis: Semua A adalah B. Sebagian B adalah C. Tidak dapat disimpulkan hubungan A dan C.'
        ],
        [
            'premis1' => 'Tidak ada A yang B.',
            'premis2' => 'Semua C adalah B.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Semua C adalah A', 'Sebagian C adalah A', 'Tidak ada C yang A', 'Semua A adalah C', 'Tidak dapat disimpulkan'],
            'benar' => 'Tidak ada C yang A',
            'pembahasan' => 'Dari premis: Tidak ada A yang B. Semua C adalah B. Maka tidak ada C yang A (himpunan terpisah).'
        ],
        [
            'premis1' => 'Semua kucing adalah hewan.',
            'premis2' => 'Sebagian hewan adalah mamalia.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Semua kucing mamalia', 'Sebagian kucing mamalia', 'Tidak ada kucing mamalia', 'Tidak dapat disimpulkan', 'Semua mamalia kucing'],
            'benar' => 'Tidak dapat disimpulkan',
            'pembahasan' => 'Dari premis: Semua A adalah B. Sebagian B adalah C. Tidak dapat disimpulkan hubungan A dan C.'
        ],
        [
            'premis1' => 'Semua siswa yang rajin lulus ujian.',
            'premis2' => 'Andi tidak lulus ujian.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Andi rajin', 'Andi tidak rajin', 'Andi tidak siswa', 'Tidak dapat disimpulkan', 'Semua siswa tidak lulus'],
            'benar' => 'Andi tidak rajin',
            'pembahasan' => 'Dari premis: Semua A (rajin) adalah B (lulus). C (Andi) bukan B. Maka C bukan A. Andi tidak rajin.'
        ],
        [
            'premis1' => 'Sebagian pegawai negeri adalah korup.',
            'premis2' => 'Budi adalah pegawai negeri.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Budi korup', 'Budi tidak korup', 'Budi pasti korup', 'Tidak dapat disimpulkan', 'Semua pegawai korup'],
            'benar' => 'Tidak dapat disimpulkan',
            'pembahasan' => 'Dari premis: Sebagian A adalah B. C adalah A. Tidak dapat disimpulkan apakah C termasuk sebagian yang B.'
        ],
        [
            'premis1' => 'Semua dokter memiliki gelar sarjana.',
            'premis2' => 'Citra memiliki gelar sarjana.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Citra dokter', 'Citra bukan dokter', 'Semua sarjana dokter', 'Tidak dapat disimpulkan', 'Citra tidak sarjana'],
            'benar' => 'Tidak dapat disimpulkan',
            'pembahasan' => 'Dari premis: Semua A adalah B. C adalah B. Tidak dapat disimpulkan C adalah A (bisa bukan dokter tapi sarjana).'
        ],
        [
            'premis1' => 'Tidak ada politikus yang jujur.',
            'premis2' => 'Joko jujur.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Joko politikus', 'Joko bukan politikus', 'Semua jujur politikus', 'Tidak dapat disimpulkan', 'Joko tidak jujur'],
            'benar' => 'Joko bukan politikus',
            'pembahasan' => 'Dari premis: Tidak ada A yang B. C adalah B. Maka C bukan A. Joko bukan politikus.'
        ],
        [
            'premis1' => 'Semua buah yang manis enak.',
            'premis2' => 'Apel ini enak.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Apel ini manis', 'Apel ini tidak manis', 'Semua enak manis', 'Tidak dapat disimpulkan', 'Apel bukan buah'],
            'benar' => 'Tidak dapat disimpulkan',
            'pembahasan' => 'Dari premis: Semua A adalah B. C adalah B. Tidak dapat disimpulkan C adalah A (bisa enak tapi tidak manis).'
        ],
        [
            'premis1' => 'Sebagian mahasiswa bekerja part-time.',
            'premis2' => 'Semua yang bekerja part-time dapat uang.',
            'soal' => 'Kesimpulan yang benar adalah...',
            'pilihan' => ['Semua mahasiswa dapat uang', 'Sebagian mahasiswa dapat uang', 'Tidak ada mahasiswa dapat uang', 'Tidak dapat disimpulkan', 'Semua part-time mahasiswa'],
            'benar' => 'Sebagian mahasiswa dapat uang',
            'pembahasan' => 'Dari premis: Sebagian A adalah B. Semua B adalah C. Maka sebagian A adalah C. Sebagian mahasiswa dapat uang.'
        ]
    ];
    
    $s = $syllogisms[array_rand($syllogisms)];
    $labels = ['A','B','C','D','E'];
    $entries = [];
    foreach ($s['pilihan'] as $v) $entries[] = ['val'=>$v];
    shuffle($entries);
    $options = [];
    $correct = '';
    foreach ($entries as $i=>$e) {
        $lbl = $labels[$i];
        $options[$lbl] = $e['val'];
        if ($e['val'] == $s['benar']) $correct = $lbl;
    }
    
    return [
        'pertanyaan' => $s['premis1'] . ' ' . $s['premis2'] . ' ' . $s['soal'],
        'pilihan_a' => $options['A'],
        'pilihan_b' => $options['B'],
        'pilihan_c' => $options['C'],
        'pilihan_d' => $options['D'],
        'pilihan_e' => $options['E'],
        'jawaban_benar' => $correct,
        'pembahasan' => $s['pembahasan']
    ];
}

/**
 * Generate figural inequality question
 * @return array Question data with options and explanation
 */
function generateKetidaksamaan(): array {
    $scenarios = [
        [
            'soal' => 'Dari 5 gambar berikut, yang berbeda adalah...',
            'pilihan' => ['4 segi empat, 1 segitiga', 'Semua sama', '4 lingkaran, 1 persegi', '3 segitiga, 2 persegi', 'Tidak ada yang berbeda'],
            'benar' => '4 segi empat, 1 segitiga',
            'pembahasan' => 'Gambar yang berbeda adalah segitiga karena satu-satunya yang tidak memiliki 4 sisi.'
        ],
        [
            'soal' => 'Cari gambar yang polanya berbeda dari yang lain...',
            'pilihan' => ['4 simetris, 1 tidak simetris', 'Semua simetris', '3 arsir, 2 kosong', 'Semua sama', '2 besar, 3 kecil'],
            'benar' => '4 simetris, 1 tidak simetris',
            'pembahasan' => 'Gambar yang berbeda adalah yang tidak simetris karena yang lain semuanya simetris.'
        ],
        [
            'soal' => 'Dari 5 bentuk, yang berbeda adalah...',
            'pilihan' => ['4 memiliki sudut, 1 melengkung', 'Semua sama', '3 tertutup, 2 terbuka', 'Semua berbeda', '2 warna sama, 3 beda'],
            'benar' => '4 memiliki sudut, 1 melengkung',
            'pembahasan' => 'Bentuk yang berbeda adalah yang melengkung karena yang lain memiliki sudut.'
        ],
        [
            'soal' => 'Identifikasi gambar yang tidak mengikuti pola...',
            'pilihan' => ['1 putus-putus, 4 kontinu', 'Semua kontinu', '2 garis tebal, 3 tipis', 'Semua sama', '3 vertikal, 2 horizontal'],
            'benar' => '1 putus-putus, 4 kontinu',
            'pembahasan' => 'Gambar yang berbeda adalah yang putus-putus karena yang lain kontinu.'
        ],
        [
            'soal' => 'Dari 5 pola, yang berbeda adalah...',
            'pilihan' => ['4 berulang, 1 unik', 'Semua unik', '3 geometris, 2 abstrak', 'Semua sama', '2 kompleks, 3 sederhana'],
            'benar' => '4 berulang, 1 unik',
            'pembahasan' => 'Pola yang berbeda adalah yang unik karena yang lain berulang.'
        ],
        [
            'soal' => 'Cari gambar dengan orientasi berbeda...',
            'pilihan' => ['4 horizontal, 1 vertikal', 'Semua horizontal', '3 kanan, 2 kiri', 'Semua sama', '2 atas, 3 bawah'],
            'benar' => '4 horizontal, 1 vertikal',
            'pembahasan' => 'Gambar yang berbeda adalah yang vertikal karena yang lain horizontal.'
        ],
        [
            'soal' => 'Dari 5 gambar, yang berbeda adalah...',
            'pilihan' => ['4 memiliki bayangan, 1 tidak', 'Semua memiliki bayangan', '3 gelap, 2 terang', 'Semua sama', '2 berwarna, 3 hitam putih'],
            'benar' => '4 memiliki bayangan, 1 tidak',
            'pembahasan' => 'Gambar yang berbeda adalah yang tidak memiliki bayangan.'
        ],
        [
            'soal' => 'Identifikasi gambar dengan jumlah elemen berbeda...',
            'pilihan' => ['4 memiliki 3 elemen, 1 memiliki 5', 'Semua sama jumlah', '3 genap, 2 ganjil', 'Semua berbeda', '2 tunggal, 3 ganda'],
            'benar' => '4 memiliki 3 elemen, 1 memiliki 5',
            'pembahasan' => 'Gambar yang berbeda adalah yang memiliki 5 elemen karena yang lain 3 elemen.'
        ],
        [
            'soal' => 'Dari 5 bentuk, yang berbeda adalah...',
            'pilihan' => ['4 solid, 1 transparan', 'Semua solid', '3 cembung, 2 cekung', 'Semua sama', '2 reguler, 3 irregular'],
            'benar' => '4 solid, 1 transparan',
            'pembahasan' => 'Bentuk yang berbeda adalah yang transparan karena yang lain solid.'
        ],
        [
            'soal' => 'Cari gambar dengan tekstur berbeda...',
            'pilihan' => ['4 halus, 1 kasar', 'Semua halus', '3 bergaris, 2 polos', 'Semua sama', '2 matte, 3 glossy'],
            'benar' => '4 halus, 1 kasar',
            'pembahasan' => 'Gambar yang berbeda adalah yang kasar karena yang lain halus.'
        ]
    ];
    
    $s = $scenarios[array_rand($scenarios)];
    $labels = ['A','B','C','D','E'];
    $entries = [];
    foreach ($s['pilihan'] as $v) $entries[] = ['val'=>$v];
    shuffle($entries);
    $options = [];
    $correct = '';
    foreach ($entries as $i=>$e) {
        $lbl = $labels[$i];
        $options[$lbl] = $e['val'];
        if ($e['val'] == $s['benar']) $correct = $lbl;
    }
    
    return [
        'pertanyaan' => $s['soal'],
        'pilihan_a' => $options['A'],
        'pilihan_b' => $options['B'],
        'pilihan_c' => $options['C'],
        'pilihan_d' => $options['D'],
        'pilihan_e' => $options['E'],
        'jawaban_benar' => $correct,
        'pembahasan' => $s['pembahasan']
    ];
}

/**
 * Generate figural serial question
 * @return array Question data with options and explanation
 */
function generateSerial(): array {
    $serials = [
        [
            'soal' => 'Urutan: ○ → ◐ → ● → ?',
            'pilihan' => ['○', '◐', '●', '◑', '◒'],
            'benar' => '◑',
            'pembahasan' => 'Pola: kosong → setengah kanan → penuh → setengah kiri → kosong. Jawaban: ◑'
        ],
        [
            'soal' => 'Urutan: 1 titik → 2 titik → 3 titik → ?',
            'pilihan' => ['1 titik', '2 titik', '3 titik', '4 titik', '5 titik'],
            'benar' => '4 titik',
            'pembahasan' => 'Pola: jumlah titik bertambah 1 setiap langkah. Jawaban: 4 titik'
        ],
        [
            'soal' => 'Urutan: kecil → sedang → besar → ?',
            'pilihan' => ['kecil', 'sedang', 'besar', 'lebih besar', 'sama'],
            'benar' => 'lebih besar',
            'pembahasan' => 'Pola: ukuran bertambah bertahap. Jawaban: lebih besar'
        ],
        [
            'soal' => 'Urutan: △ → □ → ○ → ?',
            'pilihan' => ['△', '□', '○', '☆', '◇'],
            'benar' => '☆',
            'pembahasan' => 'Pola: segitiga → persegi → lingkaran → bintang. Jawaban: ☆'
        ],
        [
            'soal' => 'Urutan: 2 → 4 → 8 → ?',
            'pilihan' => ['10', '12', '14', '16', '20'],
            'benar' => '16',
            'pembahasan' => 'Pola: dikali 2 setiap langkah. 2×2=4, 4×2=8, 8×2=16. Jawaban: 16'
        ],
        [
            'soal' => 'Urutan: A → C → E → ?',
            'pilihan' => ['F', 'G', 'H', 'I', 'J'],
            'benar' => 'G',
            'pembahasan' => 'Pola: loncat 1 huruf. A(+2)=C, C(+2)=E, E(+2)=G. Jawaban: G'
        ],
        [
            'soal' => 'Urutan: ↑ → → → ↓ → ?',
            'pilihan' => ['↑', '→', '↓', '←', '↗'],
            'benar' => '←',
            'pembahasan' => 'Pola: putar 90° searah jarum jam. Atas → Kanan → Bawah → Kiri. Jawaban: ←'
        ],
        [
            'soal' => 'Urutan: 1 → 1 → 2 → 3 → 5 → ?',
            'pilihan' => ['6', '7', '8', '9', '10'],
            'benar' => '8',
            'pembahasan' => 'Pola: Fibonacci. 1+1=2, 1+2=3, 2+3=5, 3+5=8. Jawaban: 8'
        ],
        [
            'soal' => 'Urutan: merah → kuning → hijau → ?',
            'pilihan' => ['biru', 'ungu', 'coklat', 'hitam', 'putih'],
            'benar' => 'biru',
            'pembahasan' => 'Pola: warna pelangi. Merah → Kuning → Hijau → Biru. Jawaban: biru'
        ],
        [
            'soal' => 'Urutan: 1 → 4 → 9 → ?',
            'pilihan' => ['12', '14', '16', '18', '25'],
            'benar' => '16',
            'pembahasan' => 'Pola: kuadrat. 1²=1, 2²=4, 3²=9, 4²=16. Jawaban: 16'
        ]
    ];
    
    $s = $serials[array_rand($serials)];
    $labels = ['A','B','C','D','E'];
    $entries = [];
    foreach ($s['pilihan'] as $v) $entries[] = ['val'=>$v];
    shuffle($entries);
    $options = [];
    $correct = '';
    foreach ($entries as $i=>$e) {
        $lbl = $labels[$i];
        $options[$lbl] = $e['val'];
        if ($e['val'] == $s['benar']) $correct = $lbl;
    }
    
    return [
        'pertanyaan' => $s['soal'],
        'pilihan_a' => $options['A'],
        'pilihan_b' => $options['B'],
        'pilihan_c' => $options['C'],
        'pilihan_d' => $options['D'],
        'pilihan_e' => $options['E'],
        'jawaban_benar' => $correct,
        'pembahasan' => $s['pembahasan']
    ];
}

/**
 * Generate verbal analytical question
 * @return array Question data with options and explanation
 */
function generateAnalitis(): array {
    $analytics = [
        [
            'soal' => 'Andi > Budi. Budi > Candra. Dedi > Andi. Siapa yang paling tinggi?',
            'pilihan' => ['Andi', 'Budi', 'Candra', 'Dedi', 'Tidak dapat ditentukan'],
            'benar' => 'Dedi',
            'pembahasan' => 'Urutan: Dedi > Andi > Budi > Candra. Paling tinggi: Dedi.'
        ],
        [
            'soal' => 'Andi di kiri Budi, Candra di kanan Budi. Andi paling kiri. Dedi di kanan Candra. Siapa di tengah?',
            'pilihan' => ['Andi', 'Budi', 'Candra', 'Dedi', 'Tidak dapat ditentukan'],
            'benar' => 'Budi',
            'pembahasan' => 'Posisi: Andi - Budi - Candra - Dedi. Tengah: Budi.'
        ],
        [
            'soal' => 'Jika hari Senin tidak A, maka Selasa B. Hari Senin tidak A. Hari apa?',
            'pilihan' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Tidak dapat ditentukan'],
            'benar' => 'Selasa',
            'pembahasan' => 'Dari premis: Senin tidak A → Selasa B. Faktanya Senin tidak A. Maka Selasa B.'
        ],
        [
            'soal' => 'A lebih tua dari B. C lebih muda dari B. D lebih tua dari A. Siapa paling tua?',
            'pilihan' => ['A', 'B', 'C', 'D', 'Tidak dapat ditentukan'],
            'benar' => 'D',
            'pembahasan' => 'Urutan: D > A > B > C. Paling tua: D.'
        ],
        [
            'soal' => 'Jika hujan, maka jalan licin. Jalan tidak licin. Kesimpulan?',
            'pilihan' => ['Hujan', 'Tidak hujan', 'Mungkin hujan', 'Tidak dapat disimpulkan', 'Jalan basah'],
            'benar' => 'Tidak hujan',
            'pembahasan' => 'Dari premis: A → B. Tidak B → Tidak A (kontraposisi). Jalan tidak licin → Tidak hujan.'
        ],
        [
            'soal' => 'Semua kucing suka ikan. Tom suka ikan. Kesimpulan?',
            'pilihan' => ['Tom kucing', 'Tom bukan kucing', 'Semua suka ikan kucing', 'Tidak dapat disimpulkan', 'Tom tidak suka ikan'],
            'benar' => 'Tidak dapat disimpulkan',
            'pembahasan' => 'Dari premis: Semua A adalah B. C adalah B. Tidak dapat disimpulkan C adalah A.'
        ],
        [
            'soal' => 'Buku A lebih tebal dari B. B lebih tebal dari C. D lebih tipis dari C. Urutan dari paling tebal?',
            'pilihan' => ['A-B-C-D', 'A-B-D-C', 'D-C-B-A', 'A-C-B-D', 'Tidak dapat ditentukan'],
            'benar' => 'A-B-C-D',
            'pembahasan' => 'Urutan: A > B > C > D. Paling tebal: A.'
        ],
        [
            'soal' => 'Jika belajar, maka lulus. Tidak lulus. Kesimpulan?',
            'pilihan' => ['Belajar', 'Tidak belajar', 'Mungkin belajar', 'Tidak dapat disimpulkan', 'Pasti lulus'],
            'benar' => 'Tidak belajar',
            'pembahasan' => 'Dari premis: A → B. Tidak B → Tidak A. Tidak lulus → Tidak belajar.'
        ],
        [
            'soal' => 'X lebih berat dari Y. Z lebih ringan dari Y. W lebih berat dari X. Urutan dari paling ringan?',
            'pilihan' => ['Z-Y-X-W', 'W-X-Y-Z', 'X-Y-Z-W', 'Z-W-X-Y', 'Tidak dapat ditentukan'],
            'benar' => 'Z-Y-X-W',
            'pembahasan' => 'Urutan: W > X > Y > Z. Paling ringan: Z.'
        ],
        [
            'soal' => 'Semua yang rajin sukses. Budi sukses. Kesimpulan?',
            'pilihan' => ['Budi rajin', 'Budi tidak rajin', 'Semua sukses rajin', 'Tidak dapat disimpulkan', 'Budi tidak sukses'],
            'benar' => 'Tidak dapat disimpulkan',
            'pembahasan' => 'Dari premis: Semua A adalah B. C adalah B. Tidak dapat disimpulkan C adalah A.'
        ]
    ];
    
    $a = $analytics[array_rand($analytics)];
    $labels = ['A','B','C','D','E'];
    $entries = [];
    foreach ($a['pilihan'] as $v) $entries[] = ['val'=>$v];
    shuffle($entries);
    $options = [];
    $correct = '';
    foreach ($entries as $i=>$e) {
        $lbl = $labels[$i];
        $options[$lbl] = $e['val'];
        if ($e['val'] == $a['benar']) $correct = $lbl;
    }
    
    return [
        'pertanyaan' => $a['soal'],
        'pilihan_a' => $options['A'],
        'pilihan_b' => $options['B'],
        'pilihan_c' => $options['C'],
        'pilihan_d' => $options['D'],
        'pilihan_e' => $options['E'],
        'jawaban_benar' => $correct,
        'pembahasan' => $a['pembahasan']
    ];
}
