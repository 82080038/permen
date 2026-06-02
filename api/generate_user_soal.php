<?php
/**
 * User Soal Generator — Generate soal latihan untuk peserta
 * Tidak menyimpan ke database, langsung return JSON untuk latihan client-side.
 * 
 * Cara pakai:
 *   GET /api/generate_user_soal.php?subtes=TIU&topik=Deret+Angka&jumlah=5
 */

require '../config.php';
header('Content-Type: application/json; charset=utf-8');

// Guard: logged-in user only (admin or peserta)
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Login diperlukan untuk generate soal latihan.']);
    exit;
}

$subtes    = $_GET['subtes']   ?? 'TIU';
$topik     = $_GET['topik']    ?? 'Deret Angka';
$jumlah    = min((int)($_GET['jumlah'] ?? 5), 20); // max 20 per request
$kesulitan = $_GET['kesulitan'] ?? 'sedang';

// Resolve tipe dari topik
$tipeMap = [
    'Deret Angka' => 'numerik', 'Berhitung' => 'numerik', 'Perbandingan' => 'numerik',
    'Soal Cerita' => 'numerik', 'Analogi' => 'verbal', 'Silogisme' => 'verbal',
    'Analitis' => 'verbal', 'Serial' => 'figural', 'Ketidaksamaan' => 'figural',
];
$tipe = $tipeMap[$topik] ?? '';

// ============================================================
// HELPERS (mirrored from generate_soal_smart.php)
// ============================================================

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
    return $links;
}

function ri($min, $max) { return random_int($min, $max); }

// ============================================================
// GENERATORS
// ============================================================

function genDeretAngka($k) {
    $p = ri(1,5);
    if ($p==1) { $a=ri(1,20); $d=ri(2,10); $seq=[$a,$a+$d,$a+2*$d,$a+3*$d,$a+4*$d]; $next=$a+5*$d; $exp="Pola aritmatika selisih +$d."; }
    elseif ($p==2) { $a=ri(2,5); $r=ri(2,4); $seq=[$a,$a*$r,$a*$r*$r,$a*$r*$r*$r,$a*$r*$r*$r*$r]; $next=$a*pow($r,5); $exp="Pola geometri dikali $r."; }
    elseif ($p==3) { $n=ri(1,3); $seq=[]; for($i=1;$i<=5;$i++) $seq[]=$i*$i+$n; $next=6*6+$n; $exp="Pola kuadratik n\u00b2+$n."; }
    elseif ($p==4) { $a=ri(1,10); $d=ri(2,5); $seq=[$a]; $cd=$d; for($i=1;$i<5;$i++) { $seq[]=$seq[$i-1]+$cd; $cd+=2; } $next=$seq[4]+$cd; $exp="Pola selisih naik."; }
    else { $a=ri(1,20); $d=ri(3,15); $seq=[$a,$a+$d,$a+2*$d,$a+3*$d,$a+4*$d]; $next=$a+5*$d; $exp="Pola aritmatika selisih +$d."; }
    $opts=[$next,$next+ri(2,10),$next-ri(2,10),$next+ri(11,20),$next*2];
    $l=['A','B','C','D','E']; $entries=[]; foreach($opts as $v) $entries[]=['val'=>$v]; shuffle($entries);
    $options=[]; $correct=''; foreach($entries as $i=>$e){ $lbl=$l[$i]; $options[$lbl]=(string)$e['val']; if($e['val']==$next) $correct=$lbl; }
    return ['pertanyaan'=>'Deret: '.implode(', ',$seq).', ...','pilihan_a'=>$options['A'],'pilihan_b'=>$options['B'],'pilihan_c'=>$options['C'],'pilihan_d'=>$options['D'],'pilihan_e'=>$options['E'],'jawaban_benar'=>$correct,'pembahasan'=>$exp.' Angka selanjutnya adalah '.$next.'.'];
}

function genBerhitung() {
    $t = ri(1,3);
    if ($t==1) { $a=ri(10,99); $b=ri(10,99); $q="Hasil dari $a + $b \u00d7 2 adalah..."; $ans=$a+$b*2; $exp="Perkalian dulu: $b\u00d72=".($b*2).", lalu +$a = $ans."; }
    elseif ($t==2) { $a=ri(50,200); $pct=ri(10,40); $q="$pct% dari $a adalah..."; $ans=round($a*$pct/100); $exp="$pct/100 \u00d7 $a = $ans."; }
    else { $n=ri(2,9); $q="Akar kuadrat dari ".($n*$n)." adalah..."; $ans=$n; $exp="\u221a".($n*$n)." = $n."; }
    $opts=[$ans,$ans+1,$ans+5,$ans-1,$ans+10]; $l=['A','B','C','D','E']; $entries=[]; foreach($opts as $v) $entries[]=['val'=>$v]; shuffle($entries);
    $options=[]; $correct=''; foreach($entries as $i=>$e){ $lbl=$l[$i]; $options[$lbl]=(string)$e['val']; if($e['val']==$ans) $correct=$lbl; }
    return ['pertanyaan'=>$q,'pilihan_a'=>$options['A'],'pilihan_b'=>$options['B'],'pilihan_c'=>$options['C'],'pilihan_d'=>$options['D'],'pilihan_e'=>$options['E'],'jawaban_benar'=>$correct,'pembahasan'=>$exp];
}

function genPerbandingan() {
    $a=ri(2,8); $b=ri(2,8); $total=($a+$b)*ri(10,30); $q="Perbandingan A dan B adalah $a : $b. Jika total $total, berapa nilai A?";
    $ans=$total*$a/($a+$b); $exp="A = $a/($a+$b) \u00d7 $total = $ans.";
    $opts=[$ans,$ans+5,$ans-5,$total-$ans,round($total/2)]; $l=['A','B','C','D','E']; $entries=[]; foreach($opts as $v) $entries[]=['val'=>$v]; shuffle($entries);
    $options=[]; $correct=''; foreach($entries as $i=>$e){ $lbl=$l[$i]; $options[$lbl]=(string)$e['val']; if($e['val']==$ans) $correct=$lbl; }
    return ['pertanyaan'=>$q,'pilihan_a'=>$options['A'],'pilihan_b'=>$options['B'],'pilihan_c'=>$options['C'],'pilihan_d'=>$options['D'],'pilihan_e'=>$options['E'],'jawaban_benar'=>$correct,'pembahasan'=>$exp];
}

function genSoalCerita() {
    $t = ri(1,2);
    if ($t==1) { $jarak=ri(120,360); $jam=ri(2,5); $q="Sebuah mobil menempuh jarak $jarak km dalam $jam jam. Kecepatan rata-rata?"; $ans=$jarak/$jam; $exp="V = jarak \u00f7 waktu = $jarak \u00f7 $jam = $ans km/jam."; }
    else { $modal=ri(20,50)*1000; $untung=ri(10,30); $q="Pedagang membeli barang Rp".number_format($modal).". Jika dijual untung $untung%, berapa harga jual?"; $ans=$modal*(100+$untung)/100; $exp="Jual = modal + ($untung% \u00d7 modal) = $ans."; }
    $opts=[$ans,$ans+10,$ans-10,$ans*2,round($ans/2)]; $l=['A','B','C','D','E']; $entries=[]; foreach($opts as $v) $entries[]=['val'=>$v]; shuffle($entries);
    $options=[]; $correct=''; foreach($entries as $i=>$e){ $lbl=$l[$i]; $options[$lbl]=(string)$e['val']; if($e['val']==$ans) $correct=$lbl; }
    return ['pertanyaan'=>$q,'pilihan_a'=>$options['A'],'pilihan_b'=>$options['B'],'pilihan_c'=>$options['C'],'pilihan_d'=>$options['D'],'pilihan_e'=>$options['E'],'jawaban_benar'=>$correct,'pembahasan'=>$exp];
}

function genTWK() {
    $facts=[
        ['q'=>'Proklamasi Kemerdekaan dilaksanakan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Medan'],'key'=>'A','exp'=>'Proklamasi Kemerdekaan dilaksanakan di Jakarta, 17 Agustus 1945.'],
        ['q'=>'Sumpah Pemuda dilafalkan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Semarang'],'key'=>'A','exp'=>'Sumpah Pemuda diikrarkan di Jakarta pada 28 Oktober 1928.'],
        ['q'=>'Konferensi Asia-Afrika tahun 1955 diselenggarakan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Medan'],'key'=>'B','exp'=>'KAA 1955 diselenggarakan di Bandung.'],
        ['q'=>'Pancasila dirumuskan oleh...','opts'=>['BPUPKI','PPKI','MPR','DPR','Presiden'],'key'=>'A','exp'=>'Pancasila dirumuskan oleh BPUPKI.'],
        ['q'=>'UUD 1945 disahkan oleh...','opts'=>['BPUPKI','PPKI','MPR','DPR','Presiden'],'key'=>'B','exp'=>'UUD 1945 disahkan oleh PPKI pada 18 Agustus 1945.'],
        ['q'=>'Sila pertama Pancasila berbunyi...','opts'=>['Ketuhanan Yang Maha Esa','Kemanusiaan','Persatuan','Kerakyatan','Keadilan'],'key'=>'A','exp'=>'Sila pertama: Ketuhanan Yang Maha Esa.'],
        ['q'=>'Lambang sila ketiga Pancasila adalah...','opts'=>['Bintang','Rantai','Pohon Beringin','Kepala Banteng','Padi dan Kapas'],'key'=>'C','exp'=>'Sila ketiga (Persatuan Indonesia) dilambangkan Pohon Beringin.'],
        ['q'=>'Hari Pahlawan diperingati tanggal...','opts'=>['10 November','17 Agustus','20 Mei','1 Juni','28 Oktober'],'key'=>'A','exp'=>'Hari Pahlawan: 10 November, mengenang Pertempuran Surabaya 1945.'],
        ['q'=>'G30S/PKI terjadi pada tahun...','opts'=>['1963','1965','1967','1969','1971'],'key'=>'B','exp'=>'G30S/PKI terjadi 30 September 1965.'],
        ['q'=>'KPK singkatan dari...','opts'=>['Komisi Pemberantasan Korupsi','Komisi Pengawas Korupsi','Komisi Pencegahan Korupsi','Komisi Pemeriksa Korupsi','Komisi Penegak Korupsi'],'key'=>'A','exp'=>'KPK = Komisi Pemberantasan Korupsi.'],
    ];
    $f=$facts[array_rand($facts)]; $opts=$f['opts']; $correctVal=$opts[0]; shuffle($opts);
    $l=['A','B','C','D','E']; $correct='';
    foreach($opts as $i=>$v) if($v===$correctVal) { $correct=$l[$i]; break; }
    return ['pertanyaan'=>$f['q'],'pilihan_a'=>$opts[0],'pilihan_b'=>$opts[1],'pilihan_c'=>$opts[2],'pilihan_d'=>$opts[3],'pilihan_e'=>$opts[4],'jawaban_benar'=>$correct,'pembahasan'=>$f['exp']];
}

function genTKP() {
    $scenarios=[
        ['q'=>'Anda menemukan warga lansia kesulitan naik tangga kantor. Tindakan Anda...','opts'=>['Menyuruhnya menunggu di bawah','Membantunya naik dengan sabar','Mengatakan kantor tidak memiliki lift','Mengabaikannya karena sibuk','Menyuruh keluarganya membantu'],'key'=>'B','exp'=>'Membantu lansia dengan empati dan solusi konkret menunjukkan pelayanan publik yang inklusif.'],
        ['q'=>'Saat rapat, rekan mengkritik ide Anda dengan nada tinggi. Tindakan Anda...','opts'=>['Membantah dengan nada yang sama tinggi','Mendengarkan, mencatat poin valid, dan menjawab dengan tenang','Keluar dari rapat','Menyuruhnya bicara setelah rapat','Melaporkannya ke atasan'],'key'=>'B','exp'=>'Menerima kritik dengan dewasa dan profesional adalah jejaring kerja yang konstruktif.'],
        ['q'=>'Anda diminta mengganti tugas rekan yang sakit, padahal Anda sibuk. Tindakan Anda...','opts'=>['Menolak karena tidak sesuai job desk','Menerima, menentukan prioritas, dan komunikasikan ke atasan jika perlu bantuan','Mengerjakan asal-asalan','Mengeluh di grup kerja','Mengatakan rekan tidak bertanggung jawab'],'key'=>'B','exp'=>'Fleksibilitas dan komunikasi terbuka adalah profesionalisme tinggi.'],
    ];
    $s=$scenarios[array_rand($scenarios)]; $opts=$s['opts']; $correctVal=$opts[1]; shuffle($opts);
    $l=['A','B','C','D','E']; $correct='';
    foreach($opts as $i=>$v) if($v===$correctVal) { $correct=$l[$i]; break; }
    return ['pertanyaan'=>$s['q'],'pilihan_a'=>$opts[0],'pilihan_b'=>$opts[1],'pilihan_c'=>$opts[2],'pilihan_d'=>$opts[3],'pilihan_e'=>$opts[4],'jawaban_benar'=>$correct,'pembahasan'=>$s['exp']];
}

// ============================================================
// DISPATCHER
// ============================================================
$soalList = [];
for ($i = 0; $i < $jumlah; $i++) {
    if ($subtes === 'TIU' && $topik === 'Deret Angka') $s = genDeretAngka($kesulitan);
    elseif ($subtes === 'TIU' && $topik === 'Berhitung') $s = genBerhitung();
    elseif ($subtes === 'TIU' && $topik === 'Perbandingan') $s = genPerbandingan();
    elseif ($subtes === 'TIU' && $topik === 'Soal Cerita') $s = genSoalCerita();
    elseif ($subtes === 'TWK') $s = genTWK();
    elseif ($subtes === 'TKP') $s = genTKP();
    else $s = genDeretAngka($kesulitan);

    // Enrich
    $s['tips_trick'] = buildTips($subtes, $topik);
    $s['related_links'] = buildLinks($subtes, $topik);
    $s['subtes'] = $subtes;
    $s['topik'] = $topik;
    $s['tipe'] = $tipe;
    $soalList[] = $s;
}

echo json_encode([
    'success' => true,
    'subtes' => $subtes,
    'topik' => $topik,
    'jumlah' => count($soalList),
    'soal' => $soalList
]);
