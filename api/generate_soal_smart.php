<?php
/**
 * Smart Soal Generator — Tanpa API Eksternal
 * 
 * Menggunakan algoritma PHP + template dari master_materi untuk generate soal.
 * Tidak memerlukan API key, internet, atau AI pihak ketiga.
 * 
 * Cara pakai:
 *   php api/generate_soal_smart.php?subtes=TIU&tipe=numerik&topik=Deret+Angka&jumlah=10
 * 
 * Atau via browser:
 *   http://localhost/permen/api/generate_soal_smart.php?subtes=TIU&tipe=numerik&topik=Deret+Angka&jumlah=10
 */

require '../config.php';
header('Content-Type: application/json; charset=utf-8');

// Guard: admin only
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak. Hanya admin yang bisa generate soal.']);
    exit;
}

$subtes    = $_GET['subtes']   ?? 'TIU';
$tipe      = $_GET['tipe']     ?? 'numerik';
$topik     = $_GET['topik']    ?? 'Deret Angka';
$jumlah    = (int)($_GET['jumlah'] ?? 5);
$kesulitan = $_GET['kesulitan'] ?? 'sedang';
$imageUrl  = $_GET['image_url'] ?? null; // path gambar soal, e.g. assets/soal/xxx.svg

// Ambil master materi
$stmt = $pdo->prepare("SELECT id, kisi_kisi FROM master_materi WHERE subtes = ? AND tipe = ? AND topik = ? LIMIT 1");
$stmt->execute([$subtes, $tipe, $topik]);
$master = $stmt->fetch();

if (!$master) {
    echo json_encode(['error' => 'Master materi tidak ditemukan: ' . $subtes . '/' . $tipe . '/' . $topik]);
    exit;
}

$masterId = $master['id'];
$soalGenerated = [];

// ============================================================
// HELPERS: tips, links, materi mapping
// ============================================================

// Build materi lookup map
$materiMap = [];
$stmtM = $pdo->query("SELECT id, subtes, judul FROM materi");
foreach ($stmtM as $rm) {
    $key = strtolower($rm['subtes'] . '_' . $rm['judul']);
    $materiMap[$key] = $rm['id'];
}

$topicToMateri = [
    'logika matematika' => $materiMap['tiu_logika matematika: aritmatika & geometri'] ?? null,
    'berhitung' => $materiMap['tiu_berhitung cepat'] ?? null,
    'deret angka' => $materiMap['tiu_deret angka: aritmatika & geometri'] ?? null,
    'perbandingan' => $materiMap['tiu_perbandingan & proporsi'] ?? null,
    'ketidaksamaan' => $materiMap['tiu_figural: serial & ketidaksamaan'] ?? null,
    'analogi' => $materiMap['tiu_analogi verbal'] ?? null,
    'serial' => $materiMap['tiu_figural: serial & ketidaksamaan'] ?? null,
    'analitis' => $materiMap['tiu_analitis & penalaran'] ?? null,
    'silogisme' => $materiMap['tiu_silogisme'] ?? null,
    'soal cerita' => $materiMap['tiu_soal cerita matematika'] ?? null,
    'nasionalisme' => $materiMap['twk_nasionalisme & pahlawan'] ?? null,
    'sejarah' => $materiMap['twk_sejarah kemerdekaan indonesia'] ?? null,
    'pancasila' => $materiMap['twk_pancasila: sila & lambang'] ?? null,
    'bahasa indonesia' => $materiMap['twk_bahasa indonesia baku'] ?? null,
    'uud 1945' => $materiMap['twk_uud 1945 & amandemen'] ?? null,
    'pilar negara' => $materiMap['twk_pilar negara & bhinneka tunggal ika'] ?? null,
    'integritas' => $materiMap['twk_integritas & anti korupsi'] ?? null,
    'bela negara' => $materiMap['twk_bela negara'] ?? null,
    'kepribadian' => $materiMap['tkp_kepribadian & kecerdasan emosional'] ?? null,
    'pelayanan publik' => $materiMap['tkp_pelayanan publik prima'] ?? null,
    'jejaring kerja' => $materiMap['tkp_jejaring kerja & kerja sama'] ?? null,
    'sosial budaya' => $materiMap['tkp_sosial budaya & keberagaman'] ?? null,
    'profesionalisme' => $materiMap['tkp_profesionalisme & integritas'] ?? null,
    'teknologi informasi' => $materiMap['tkp_teknologi informasi & adaptasi'] ?? null,
];

function buildTips($subtes, $topik) {
    $t = strtolower($topik);
    if ($subtes == 'TIU') {
        if (strpos($t, 'deret') !== false) return 'Tips: Selisihkan angka, periksa pola beda tetap (aritmatika) atau rasio tetap (geometri). Perhatikan juga bilangan prima, kuadrat, dan kubik.';
        if (strpos($t, 'berhitung') !== false) return 'Tips: Pecah perhitungan jadi langkah kecil. Gunakan trik perkalian kelipatan 10/100 untuk mempercepat.';
        if (strpos($t, 'perbandingan') !== false) return 'Tips: Samakan satuan. Gunakan rumus P1xH1 = P2xH2 untuk soal pekerja. Untuk kecepatan, v=s/t.';
        if (strpos($t, 'analogi') !== false) return 'Tips: Identifikasi hubungan pasangan pertama (sebab-akibat, fungsi, lokasi, sinonim). Cari pasangan kedua dengan hubungan identik.';
        if (strpos($t, 'silogisme') !== false) return 'Tips: Gambar diagram Venn. Perhatikan kata kunci: Semua, Beberapa, Tidak ada. Hindari kesimpulan yang melebihi premis.';
        if (strpos($t, 'analitis') !== false) return 'Tips: Tulis ulang informasi dalam skema/garis. Susun urutan dari fakta yang pasti terlebih dahulu.';
        if (strpos($t, 'cerita') !== false) return 'Tips: Identifikasi variabel, tulis rumus, periksa satuan, hitung step-by-step.';
        return 'Tips: Baca soal dengan teliti. Identifikasi variabel dan rumus yang sesuai. Periksa kembali jawaban sebelum memilih.';
    } elseif ($subtes == 'TWK') {
        if (strpos($t, 'sejarah') !== false) return 'Tips: Hafalkan timeline: 1908 (Budi Utomo), 1928 (Sumpah Pemuda), 1945 (Proklamasi), 1955 (KAA), 1965 (G30S), 1998 (Reformasi).';
        if (strpos($t, 'pancasila') !== false) return 'Tips: Hafalkan 5 sila beserta lambangnya. Ingat: Bintang(1), Rantai(2), Beringin(3), Banteng(4), Padi Kapas(5).';
        if (strpos($t, 'uud') !== false) return 'Tips: UUD 1945 terdiri dari 16 bab, 4 amandemen. Mahkamah Konstitusi & Komisi Yudisial hasil amandemen ke-3.';
        if (strpos($t, 'bahasa') !== false) return 'Tips: Gunakan KBBI sebagai acuan baku. Perhatikan perbedaan: sekadar vs sekedar, sesaat vs sebentar, memiliki vs mempunyai.';
        if (strpos($t, 'nasionalisme') !== false) return 'Tips: Hafalkan pahlawan per daerah. Aceh: Cut Nyak Dien, Jawa: Soedirman, Kalimantan: Antasari, Sulawesi: Hasanuddin.';
        if (strpos($t, 'integritas') !== false) return 'Tips: KPK = Komisi Pemberantasan Korupsi. Tipikor = Tindak Pidana Korupsi. Tolak gratifikasi dan laporkan.';
        if (strpos($t, 'bela') !== false) return 'Tips: Komponen utama pertahanan = TNI. Sistem = SISHANKAMRATA. Kewajiban = wajib militer/sipil.';
        if (strpos($t, 'pilar') !== false) return 'Tips: 4 Pilar: Pancasila, UUD 1945, NKRI, Bhinneka Tunggal Ika.';
        return 'Tips: Hafalkan fakta dan angka penting. Baca berulang dan buat catatan sendiri.';
    } else {
        if (strpos($t, 'pelayanan') !== false) return 'Tips: Prioritaskan kepentingan masyarakat. Sabar, ramah, dan beri solusi konkret.';
        if (strpos($t, 'jejaring') !== false) return 'Tips: Komunikasikan secara jujur dan konstruktif. Cari solusi win-win dalam konflik.';
        if (strpos($t, 'sosial') !== false) return 'Tips: Hormati adat dan keberagaman. Adaptasi dengan lingkungan baru.';
        if (strpos($t, 'profesional') !== false) return 'Tips: Netral, jujur, bertanggung jawab, dan disiplin. Akui kesalahan dan segera perbaiki.';
        if (strpos($t, 'teknologi') !== false) return 'Tips: Bersikap terbuka belajar teknologi baru. Jaga keamanan data dan privasi.';
        if (strpos($t, 'kepribadian') !== false) return 'Tips: Kelola emosi dengan baik. Tunjukkan empati dan bangun relasi positif.';
        return 'Tips: Pilih jawaban yang menunjukkan integritas, profesionalisme, dan empati tertinggi.';
    }
}

function buildLinks($subtes, $topik) {
    $links = [];
    $t = strtolower($topik);
    if ($subtes == 'TIU') {
        if (strpos($t, 'deret') !== false || strpos($t, 'berhitung') !== false) $links[] = ['label'=>'Pola Bilangan','url'=>'https://id.wikipedia.org/wiki/Deret_aritmatika'];
        if (strpos($t, 'perbandingan') !== false) $links[] = ['label'=>'Perbandingan','url'=>'https://id.wikipedia.org/wiki/Perbandingan'];
        if (strpos($t, 'silogisme') !== false) $links[] = ['label'=>'Silogisme','url'=>'https://id.wikipedia.org/wiki/Silogisme'];
        if (strpos($t, 'analogi') !== false) $links[] = ['label'=>'Analogi','url'=>'https://id.wikipedia.org/wiki/Analogi'];
        if (strpos($t, 'analitis') !== false) $links[] = ['label'=>'Penalaran','url'=>'https://www.zenius.net/cpns/tiu-penalaran'];
        $links[] = ['label'=>'Soal TIU','url'=>'https://www.zenius.net/cpns/tiu'];
    } elseif ($subtes == 'TWK') {
        if (strpos($t, 'sejarah') !== false) $links[] = ['label'=>'Sejarah Indonesia','url'=>'https://id.wikipedia.org/wiki/Sejarah_Indonesia'];
        if (strpos($t, 'pancasila') !== false) $links[] = ['label'=>'Pancasila','url'=>'https://id.wikipedia.org/wiki/Pancasila'];
        if (strpos($t, 'uud') !== false) $links[] = ['label'=>'UUD 1945','url'=>'https://id.wikipedia.org/wiki/UUD_1945'];
        if (strpos($t, 'bahasa') !== false) $links[] = ['label'=>'KBBI','url'=>'https://kbbi.kemdikbud.go.id/'];
        if (strpos($t, 'integritas') !== false) $links[] = ['label'=>'KPK','url'=>'https://www.kpk.go.id/'];
        if (strpos($t, 'bela') !== false) $links[] = ['label'=>'Bela Negara','url'=>'https://id.wikipedia.org/wiki/Bela_negara'];
        $links[] = ['label'=>'TWK CPNS','url'=>'https://www.zenius.net/cpns/twk'];
    } else {
        $links[] = ['label'=>'TKP CPNS','url'=>'https://www.zenius.net/cpns/tkp'];
        $links[] = ['label'=>'ASN Profesional','url'=>'https://www.menpan.go.id/'];
    }
    return json_encode($links, JSON_UNESCAPED_UNICODE);
}

function expandPembahasan($subtes, $topik, $pertanyaan, $jawaban, $pembahasan) {
    $p = $pembahasan;
    if (strlen($p) >= 120) return $p;
    $t = strtolower($topik);
    $prefix = '';
    if ($subtes == 'TIU') {
        if (strpos($t, 'deret') !== false) $prefix = "Untuk menyelesaikan deret ini, perhatikan pola perubahan antar angka. ";
        elseif (strpos($t, 'berhitung') !== false) $prefix = "Perhitungan dilakukan secara bertahap. ";
        elseif (strpos($t, 'perbandingan') !== false) $prefix = "Gunakan konsep perbandingan senilai atau berbalik nilai. ";
        elseif (strpos($t, 'analogi') !== false) $prefix = "Hubungan antar kata pertama harus identik dengan hubungan pasangan kedua. ";
        elseif (strpos($t, 'silogisme') !== false) $prefix = "Analisis premis dengan diagram Venn untuk menemukan kesimpulan valid. ";
        elseif (strpos($t, 'analitis') !== false) $prefix = "Susun fakta secara sistematis untuk menemukan urutan atau relasi. ";
        elseif (strpos($t, 'cerita') !== false) $prefix = "Identifikasi variabel, pilih rumus yang sesuai, lalu hitung secara bertahap. ";
        else $prefix = "Baca soal dengan teliti dan identifikasi rumus yang sesuai. ";
        $prefix .= "Jawaban yang benar adalah $jawaban. ";
    } elseif ($subtes == 'TWK') {
        $prefix = "Fakta ini merupakan bagian dari pemahaman $topik dalam konteks Wawasan Kebangsaan. ";
        $prefix .= "Jawaban yang benar adalah $jawaban. ";
    } else {
        $prefix = "Dalam situasi ini, pilihan terbaik adalah yang menunjukkan integritas, empati, dan profesionalisme tinggi. ";
        $prefix .= "Jawaban yang benar adalah $jawaban. ";
    }
    return trim($prefix . " " . $p);
}

$materiId = $topicToMateri[strtolower($topik)] ?? null;

// ============================================================
// GENERATOR PER TIPE
// ============================================================

function randomInt($min, $max) {
    return random_int($min, $max);
}

function shuffleAssoc($array) {
    $keys = array_keys($array);
    shuffle($keys);
    $new = [];
    foreach ($keys as $k) $new[$k] = $array[$k];
    return $new;
}

// --- TIU NUMERIK: DERET ANGKA ---
function generateDeretAngka($kesulitan) {
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

// --- TIU NUMERIK: BERHITUNG ---
function generateBerhitung($kesulitan) {
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

// --- TIU NUMERIK: PERBANDINGAN ---
function generatePerbandingan() {
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

// --- TIU NUMERIK: SOAL CERITA ---
function generateSoalCerita() {
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

// --- TWK: NASIONALISME ---
function generateTWK_Nasionalisme() {
    $templates = [
        [
            'pertanyaan' => 'Semangat nasionalisme paling tepat ditunjukkan oleh...',
            'pilihan_a' => 'Menggunakan produk dalam negeri dan mendukung UMKM',
            'pilihan_b' => 'Menolak segala bentuk kerja sama dengan negara lain',
            'pilihan_c' => 'Mengkritik pemerintah tanpa memberikan solusi',
            'pilihan_d' => 'Menghindari tugas-tugas kewarganegaraan',
            'pilihan_e' => 'Menganggap budaya asing lebih superior',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Nasionalisme konstruktif diwujudkan melalui tindakan nyata yang memajukan bangsa, seperti menggunakan produk dalam negeri dan mendukung UMKM, tanpa merendahkan bangsa lain.'
        ],
        [
            'pertanyaan' => 'Hari Kebangkitan Nasional diperingati setiap tanggal...',
            'pilihan_a' => '17 Agustus', 'pilihan_b' => '20 Mei', 'pilihan_c' => '28 Oktober', 'pilihan_d' => '10 November', 'pilihan_e' => '1 Juni',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Hari Kebangkitan Nasional diperingati pada tanggal 20 Mei, merujak pada berdirinya organisasi Budi Utomo pada tahun 1908 sebagai tonggak kebangkitan nasional.'
        ],
        [
            'pertanyaan' => 'Lambang sila ketiga Pancasila adalah...',
            'pilihan_a' => 'Bintang emas', 'pilihan_b' => 'Pohon beringin', 'pilihan_c' => 'Kepala banteng', 'pilihan_d' => 'Padi dan kapas', 'pilihan_e' => 'Rantai',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Sila ketiga Pancasila (Persatuan Indonesia) dilambangkan dengan kepala banteng yang menggambarkan kekuatan dan ketahanan bangsa.'
        ],
        [
            'pertanyaan' => 'Sumpah Pemuda 1928 diikrarkan di kota...',
            'pilihan_a' => 'Jakarta', 'pilihan_b' => 'Surabaya', 'pilihan_c' => 'Bandung', 'pilihan_d' => 'Yogyakarta', 'pilihan_e' => 'Solo',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Sumpah Pemuda diikrarkan di Jakarta pada tanggal 28 Oktober 1928, menegaskan satu tanah air, satu bangsa, dan satu bahasa Indonesia.'
        ],
        [
            'pertanyaan' => 'Konferensi Asia-Afrika tahun 1955 diselenggarakan di...',
            'pilihan_a' => 'Jakarta', 'pilihan_b' => 'Bandung', 'pilihan_c' => 'Surabaya', 'pilihan_d' => 'Yogyakarta', 'pilihan_e' => 'Medan',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Konferensi Asia-Afrika (KAA) 1955 diselenggarakan di Bandung, menjadi tonggak diplomasi bebas aktif Indonesia dan solidaritas negara-negara Asia-Afrika.'
        ],
        [
            'pertanyaan' => 'Peristiwa Rengasdengklok terjadi pada tanggal...',
            'pilihan_a' => '15 Agustus 1945', 'pilihan_b' => '16 Agustus 1945', 'pilihan_c' => '17 Agustus 1945', 'pilihan_d' => '18 Agustus 1945', 'pilihan_e' => '19 Agustus 1945',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Peristiwa Rengasdengklok terjadi pada 16 Agustus 1945, ketika golongan muda menculik Soekarno-Hatta ke Rengasdengklok untuk mempercepat proklamasi kemerdekaan.'
        ],
        [
            'pertanyaan' => 'Tokoh yang mengusulkan nama "Indonesia" dalam Sumpah Pemuda adalah...',
            'pilihan_a' => 'Ir. Soekarno', 'pilihan_b' => 'Mohammad Hatta', 'pilihan_c' => 'Ernest Douwes Dekker', 'pilihan_d' => 'Sutan Sjahrir', 'pilihan_e' => 'Agus Salim',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Ernest Douwes Dekker (Setiabudi) yang mengusulkan penggunaan nama "Indonesia" sebagai pengganti "Hindia", "Nusantara", atau "Tanah Melayu" dalam Sumpah Pemuda.'
        ],
        [
            'pertanyaan' => 'Peristiwa pertempuran 10 November 1945 di Surabaya dipimpin oleh...',
            'pilihan_a' => 'Jenderal Soedirman', 'pilihan_b' => 'Bung Tomo', 'pilihan_c' => 'Ki Hajar Dewantara', 'pilihan_d' => 'Mohammad Hatta', 'pilihan_e' => 'Sutan Sjahrir',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Bung Tomo (Sutomo) memimpin perlawanan rakyat Surabaya dalam Pertempuran 10 November 1945 melawan pasukan Sekutu yang dipimpin Brigadir Jenderal Mallaby.'
        ],
        [
            'pertanyaan' => 'Piagam Jakarta merupakan cikal bakal dari...',
            'pilihan_a' => 'Proklamasi Kemerdekaan', 'pilihan_b' => 'UUD 1945', 'pilihan_c' => 'Konstituante', 'pilihan_d' => 'GBHN', 'pilihan_e' => 'Tap MPR',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Piagam Jakarta (22 Juni 1945) disusun oleh Panitia Sembilan dan menjadi cikal bakal Pembukaan UUD 1945, meskipun tujuh kata "dengan kewajiban menjalankan syariat Islam bagi pemeluk-pemeluknya" dihapus dalam UUD final.'
        ],
        [
            'pertanyaan' => 'Politik luar negeri Indonesia yang bebas aktif berarti...',
            'pilihan_a' => 'Tidak berhubungan dengan negara lain', 'pilihan_b' => 'Memihak salah satu blok dalam perang dingin', 'pilihan_c' => 'Bebas menentukan sikap sesuai kepentingan nasional tanpa memihak blok mana pun', 'pilihan_d' => 'Menjadi anggota NATO', 'pilihan_e' => 'Menjadi anggota Pakta Warsawa',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Politik bebas aktif yang digagas Bung Hatta berarti Indonesia bebas menentukan sikap dalam setiap persoalan internasional berdasarkan kepentingan nasional, tanpa terikat pada blok Barat atau blok Timur.'
        ],
        [
            'pertanyaan' => 'Lembaga yang menyelenggarakan pemilu di Indonesia adalah...',
            'pilihan_a' => 'MPR', 'pilihan_b' => 'DPR', 'pilihan_c' => 'KPU', 'pilihan_d' => 'BPK', 'pilihan_e' => 'MK',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Komisi Pemilihan Umum (KPU) adalah lembaga penyelenggara pemilu yang bersifat nasional, tetap, dan mandiri sesuai Pasal 22E dan Pasal 22H UUD 1945.'
        ],
        [
            'pertanyaan' => 'Peristiwa G30S/PKI terjadi pada tahun...',
            'pilihan_a' => '1963', 'pilihan_b' => '1965', 'pilihan_c' => '1967', 'pilihan_d' => '1969', 'pilihan_e' => '1971',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Peristiwa G30S/PKI terjadi pada 30 September 1965, ditandai dengan penculikan dan pembunuhan enam jenderal serta satu perwira di Jakarta.'
        ],
        [
            'pertanyaan' => 'Wawasan Nusantara merupakan wujud dari...',
            'pilihan_a' => 'Pancasila Sila Pertama', 'pilihan_b' => 'Pancasila Sila Kedua', 'pilihan_c' => 'Pancasila Sila Ketiga', 'pilihan_d' => 'Pancasila Sila Keempat', 'pilihan_e' => 'Pancasila Sila Kelima',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Wawasan Nusantara merupakan penjabaran dan wujud pengamalan sila ketiga Pancasila: Persatuan Indonesia, yang menegaskan kesatuan wilayah, bangsa, dan bahasa Indonesia.'
        ],
        [
            'pertanyaan' => 'Organisasi perempuan pertama di Indonesia yang bergerak di bidang pendidikan adalah...',
            'pilihan_a' => 'Gerwani', 'pilihan_b' => 'Perwari', 'pilihan_c' => 'Aisyiyah', 'pilihan_d' => 'Dharma Wanita', 'pilihan_e' => 'PKK',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Aisyiyah (lahir 1917) adalah organisasi perempuan Islam pertama di Indonesia yang bergerak di bidang pendidikan, kesehatan, dan sosial, didirikan di Yogyakarta.'
        ],
        [
            'pertanyaan' => 'Sila keempat Pancasila mengandung pengertian...',
            'pilihan_a' => 'Ketuhanan Yang Maha Esa', 'pilihan_b' => 'Kemanusiaan yang adil dan beradab', 'pilihan_c' => 'Persatuan Indonesia', 'pilihan_d' => 'Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan', 'pilihan_e' => 'Keadilan sosial bagi seluruh rakyat Indonesia',
            'jawaban_benar' => 'D',
            'pembahasan' => 'Sila keempat Pancasila berbunyi: Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan, dengan lambang pohon beringin.'
        ],
        [
            'pertanyaan' => 'Konsepsi Trisakti yang dikemukakan Presiden Soekarno meliputi kecuali...',
            'pilihan_a' => 'Berdaulat di bidang politik', 'pilihan_b' => 'Berdaulat di bidang ekonomi', 'pilihan_c' => 'Berdaulat di bidang kebudayaan', 'pilihan_d' => 'Berdaulat di bidang militer', 'pilihan_e' => 'Berdiri di atas kaki sendiri',
            'jawaban_benar' => 'D',
            'pembahasan' => 'Trisakti Soekarno terdiri dari: berdaulat di bidang politik, berdaulat di bidang ekonomi, berdaulat di bidang kebudayaan, serta berdiri di atas kaki sendiri (berdikari). Tidak mencakup kedaulatan militer sebagai pilar tersendiri.'
        ],
        [
            'pertanyaan' => 'Pemerintahan Orde Baru dimulai pada tahun...',
            'pilihan_a' => '1965', 'pilihan_b' => '1966', 'pilihan_c' => '1967', 'pilihan_d' => '1968', 'pilihan_e' => '1969',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Orde Baru dimulai pada tahun 1967, ketika Soeharto dilantik sebagai Presiden RI menggantikan Soekarno melalui Sidang Istimewa MPRS pada Maret 1967.'
        ],
        [
            'pertanyaan' => 'Kebijakan transmigrasi pada masa Orde Baru bertujuan untuk...',
            'pilihan_a' => 'Mengurangi jumlah penduduk di pulau Jawa', 'pilihan_b' => 'Meningkatkan pemerataan penduduk dan pembangunan antarwilayah', 'pilihan_c' => 'Mengalihkan kekayaan alam ke pulau Jawa', 'pilihan_d' => 'Memindahkan etnis tertentu ke daerah tertentu', 'pilihan_e' => 'Mengurangi konflik agraria',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Program transmigrasi bertujuan meningkatkan pemerataan penduduk dan pembangunan antarwilayah, meskipun dalam praktiknya menimbulkan berbagai persoalan sosial dan agraria di daerah tujuan.'
        ],
        [
            'pertanyaan' => 'BPUPKI dibentuk pada tanggal...',
            'pilihan_a' => '17 Agustus 1945', 'pilihan_b' => '10 Juli 1945', 'pilihan_c' => '29 April 1945', 'pilihan_d' => '1 Maret 1945', 'pilihan_e' => '18 Agustus 1945',
            'jawaban_benar' => 'C',
            'pembahasan' => 'BPUPKI (Badan Penyelidik Usaha-usaha Persiapan Kemerdekaan Indonesia) dibentuk pada 29 April 1945 oleh Pemerintah Pendudukan Jepang, dipimpin oleh Dr. Radjiman Wedyodiningrat.'
        ],
        [
            'pertanyaan' => 'Naskah proklamasi kemerdekaan Indonesia ditulis oleh...',
            'pilihan_a' => 'Ir. Soekarno', 'pilihan_b' => 'Mohammad Hatta', 'pilihan_c' => 'Achmad Soebardjo', 'pilihan_d' => 'Sayuti Melik', 'pilihan_e' => 'Sukarni',
            'jawaban_benar' => 'D',
            'pembahasan' => 'Naskah proklamasi ditulis oleh Sayuti Melik berdasarkan konsep yang disusun oleh Soekarno, Hatta, dan Achmad Soebardjo pada dini hari 17 Agustus 1945 di Jalan Pegangsaan Timur 56.'
        ],
    ];
    
    return $templates[array_rand($templates)];
}

// --- TWK: PANCASILA / PILAR NEGARA ---
function generateTWK() {
    $templates = [
        [
            'pertanyaan' => 'Silah pertama Pancasila adalah...',
            'pilihan_a' => 'Ketuhanan Yang Maha Esa',
            'pilihan_b' => 'Kemanusiaan yang adil dan beradab',
            'pilihan_c' => 'Persatuan Indonesia',
            'pilihan_d' => 'Kerakyatan',
            'pilihan_e' => 'Keadilan sosial',
            'jawaban_benar' => 'A',
            'pembahasan' => 'Silah 1: Ketuhanan Yang Maha Esa.'
        ],
        [
            'pertanyaan' => 'UUD 1945 telah diamandemen sebanyak... kali.',
            'pilihan_a' => '2', 'pilihan_b' => '3', 'pilihan_c' => '4', 'pilihan_d' => '5', 'pilihan_e' => '6',
            'jawaban_benar' => 'C',
            'pembahasan' => 'UUD 1945 diamandemen 4 kali (1999-2002).'
        ],
        [
            'pertanyaan' => 'Lambang silah keempat Pancasila adalah...',
            'pilihan_a' => 'Bintang', 'pilihan_b' => 'Pohon beringin', 'pilihan_c' => 'Kepala banteng', 'pilihan_d' => 'Padi dan kapas', 'pilihan_e' => 'Rantai',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Silah 4 (Kerakyatan) dilambangkan pohon beringin.'
        ],
        [
            'pertanyaan' => 'Pasal 33 UUD 1945 mengatur tentang...',
            'pilihan_a' => 'Pendidikan', 'pilihan_b' => 'Kesehatan', 'pilihan_c' => 'Ekonomi & keuangan negara', 'pilihan_d' => 'Pertahanan', 'pilihan_e' => 'Kehutanan',
            'jawaban_benar' => 'C',
            'pembahasan' => 'Pasal 33 mengatur perekonomian nasional dan keuangan negara.'
        ],
    ];
    
    return $templates[array_rand($templates)];
}

// --- TIU: PASSAGE LOGIKA (1 bacaan + multiple soal) ---
function generateTIU_PassageLogika() {
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
        'passage' => [
            'judul' => 'Proyek Pembangunan',
            'bacaan' => $bacaan
        ],
        'soal' => $soalList
    ];
}

// --- TKP: SKENARIO ---
function generateTKP() {
    $templates = [
        [
            'pertanyaan' => 'Anda menemukan warga lansia kesulitan naik tangga kantor. Tindakan Anda...',
            'pilihan_a' => 'Menyuruhnya menunggu di bawah',
            'pilihan_b' => 'Membantunya naik dengan sabar dan menemukan jalur akses yang memadai',
            'pilihan_c' => 'Mengatakan kantor tidak memiliki lift',
            'pilihan_d' => 'Mengabaikannya karena sibuk',
            'pilihan_e' => 'Menyuruh keluarganya membantu',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Membantu lansia dengan empati dan solusi konkret menunjukkan pelayanan publik yang inklusif.',
            'bobot_tkp' => 5
        ],
        [
            'pertanyaan' => 'Saat rapat, rekan mengkritik ide Anda dengan nada tinggi. Tindakan Anda...',
            'pilihan_a' => 'Membantah dengan nada yang sama tinggi',
            'pilihan_b' => 'Mendengarkan, mencatat poin valid, dan menjawab dengan tenang',
            'pilihan_c' => 'Keluar dari rapat',
            'pilihan_d' => 'Menyuruhnya bicara setelah rapat',
            'pilihan_e' => 'Melaporkannya ke atasan',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Menerima kritik dengan dewasa dan profesional adalah jejaring kerja yang konstruktif.',
            'bobot_tkp' => 5
        ],
        [
            'pertanyaan' => 'Anda diminta mengganti tugas rekan yang sakit, padahal Anda sibuk. Tindakan Anda...',
            'pilihan_a' => 'Menolak karena tidak sesuai job desk',
            'pilihan_b' => 'Menerima, menentukan prioritas, dan komunikasikan ke atasan jika perlu bantuan',
            'pilihan_c' => 'Mengerjakan asal-asalan',
            'pilihan_d' => 'Mengeluh di grup kerja',
            'pilihan_e' => 'Mengatakan rekan tidak bertanggung jawab',
            'jawaban_benar' => 'B',
            'pembahasan' => 'Fleksibilitas dan komunikasi terbuka adalah profesionalisme tinggi.',
            'bobot_tkp' => 5
        ],
    ];
    
    return $templates[array_rand($templates)];
}

// ============================================================
// DISPATCHER
// ============================================================
$mode = $_GET['mode'] ?? 'single'; // 'single' atau 'passage'
$passageData = null;

for ($i = 0; $i < $jumlah; $i++) {
    if ($mode === 'passage' && $subtes === 'TIU' && $topik === 'Logika Matematika') {
        // Mode passage: generate sekali, ambil soal-nya
        if (!$passageData) {
            $passageData = generateTIU_PassageLogika();
        }
        $s = $passageData['soal'][$i] ?? $passageData['soal'][array_rand($passageData['soal'])];
    } elseif ($subtes === 'TIU' && $tipe === 'numerik' && $topik === 'Deret Angka') {
        $s = generateDeretAngka($kesulitan);
    } elseif ($subtes === 'TIU' && $tipe === 'numerik' && $topik === 'Berhitung') {
        $s = generateBerhitung($kesulitan);
    } elseif ($subtes === 'TIU' && $tipe === 'numerik' && $topik === 'Perbandingan') {
        $s = generatePerbandingan();
    } elseif ($subtes === 'TIU' && $tipe === 'numerik' && $topik === 'Soal Cerita') {
        $s = generateSoalCerita();
    } elseif ($subtes === 'TWK' && $topik === 'Nasionalisme') {
        $s = generateTWK_Nasionalisme();
    } elseif ($subtes === 'TWK') {
        $s = generateTWK();
    } elseif ($subtes === 'TKP') {
        $s = generateTKP();
    } else {
        // Default fallback: deret angka
        $s = generateDeretAngka($kesulitan);
    }

    $soalGenerated[] = $s;
}

// ============================================================
// SIMPAN KE DATABASE
// ============================================================
$inserted = 0;
$skippedDup = 0;
$passageId = null;

$tips = buildTips($subtes, $topik);
$links = buildLinks($subtes, $topik);

$stmtCache = $pdo->prepare("INSERT INTO soal_ai_cache (master_materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, bobot_tkp, pembahasan, image_url, tingkat_kesulitan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtQ = $pdo->prepare("INSERT INTO questions (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, bobot_tkp, pembahasan, tips_trick, related_links, materi_id, image_url, passage_id, passage_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtCheckDup = $pdo->prepare("SELECT id FROM questions WHERE pertanyaan = ? LIMIT 1");

// Insert passage terlebih dahulu jika mode passage
if ($mode === 'passage' && $passageData) {
    $stmtPassage = $pdo->prepare("INSERT INTO passages (subtes, tipe, topik, judul, bacaan) VALUES (?, ?, ?, ?, ?)");
    $stmtPassage->execute([$subtes, $tipe, $topik, $passageData['passage']['judul'], $passageData['passage']['bacaan']]);
    $passageId = $pdo->lastInsertId();
}

foreach ($soalGenerated as $order => $s) {
    $bobot = $s['bobot_tkp'] ?? NULL;
    $pemb = expandPembahasan($subtes, $topik, $s['pertanyaan'], $s['jawaban_benar'], $s['pembahasan']);

    // Deduplicate: skip jika pertanyaan sudah ada
    $stmtCheckDup->execute([$s['pertanyaan']]);
    if ($stmtCheckDup->fetch()) {
        $skippedDup++;
        continue;
    }

    try {
        $stmtCache->execute([
            $masterId, $s['pertanyaan'], $s['pilihan_a'], $s['pilihan_b'], $s['pilihan_c'],
            $s['pilihan_d'], $s['pilihan_e'], $s['jawaban_benar'], $bobot, $pemb, $imageUrl, $kesulitan
        ]);

        $stmtQ->execute([
            $subtes, $tipe, $topik, $s['pertanyaan'], $s['pilihan_a'], $s['pilihan_b'],
            $s['pilihan_c'], $s['pilihan_d'], $s['pilihan_e'], $s['jawaban_benar'], $bobot, $pemb,
            $tips, $links, $materiId, $imageUrl, $passageId, ($passageId ? $order + 1 : 0)
        ]);

        $inserted++;
    } catch (Exception $e) {
        // Skip jika gagal (misalnya race condition duplikat)
    }
}

echo json_encode([
    'success' => true,
    'generator' => 'smart_internal',
    'no_api_required' => true,
    'mode' => $mode,
    'generated' => count($soalGenerated),
    'inserted' => $inserted,
    'skipped_duplicate' => $skippedDup,
    'passage_id' => $passageId,
    'subtes' => $subtes,
    'tipe' => $tipe,
    'topik' => $topik,
    'kesulitan' => $kesulitan,
    'soal' => $soalGenerated
]);
