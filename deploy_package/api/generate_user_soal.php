<?php
/**
 * User Soal Generator — Generate soal latihan untuk peserta
 * Tidak menyimpan ke database, langsung return JSON untuk latihan client-side.
 * 
 * Cara pakai:
 *   GET /api/generate_user_soal.php?subtes=TIU&topik=Deret+Angka&jumlah=5
 */

require '../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

// Guard: logged-in user only (admin or peserta)
if (empty($_SESSION['user_id'])) {
    ApiResponse::unauthorized('Login diperlukan untuk generate soal latihan.');
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
        if (strpos($t, 'analogi') !== false) return 'Tips: Identifikasi hubungan pasangan pertama (sebab-akibat, fungsi, lokasi, sinonim, definisi). Cari pasangan kedua dengan hubungan identik.';
        if (strpos($t, 'silogisme') !== false) return 'Tips: Gambar diagram Venn. Perhatikan kata kunci: Semua, Beberapa, Tidak ada. Hindari kesimpulan yang melebihi premis. Ingat: p→q, konvers: q→p, invers: ~p→~q, kontraposisi: ~q→~p.';
        if (strpos($t, 'analitis') !== false) return 'Tips: Tulis ulang informasi dalam skema/garis. Susun urutan dari fakta yang pasti terlebih dahulu. Gunakan modus ponens (p→q, p, maka q) dan modus tollens (p→q, ~q, maka ~p).';
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

function genAnalogi() {
    $analogies = [
        ['q'=>'Hari : .... = Kata : ....','opts'=>['Bulan - Suku kata','Tahun - Huruf','Minggu - Frasa','Dasawarsa - Paragraf','Abad - Kalimat'],'key'=>'C','exp'=>'Jenis padanan hubungan "definisi". Sekumpulan hari membentuk minggu, sebagaimana kumpulan kata membentuk frasa.'],
        ['q'=>'Penuh : ..... = ..... : Berhenti','opts'=>['Isi - Gerakan','Kosong - Diam','Banjir - Pindah','Meluap - Melambat','Menggenang - Marah'],'key'=>'D','exp'=>'Jenis padanan hubungan "urutan". Meluap terjadi setelah Penuh, sebagaimana Berhenti terjadi setelah Melambat.'],
        ['q'=>'Serangga : Semut = ...... : ......','opts'=>['Ular : Ulat','Ikan : Paus','Ayam : Jago','Jeruk : Manis','Palem : Pinang'],'key'=>'C','exp'=>'Jenis padanan hubungan "asosiasi". Semut merupakan suatu jenis serangga, sebagaimana Jago merupakan suatu jenis Ayam.'],
        ['q'=>'Pedas : Lombok = ...... : ......','opts'=>['Manis : Gula','Lapar : Makanan','Manis : Sirup','Kecap : Sakarin','Manisan : Teh'],'key'=>'A','exp'=>'Pedas adalah rasa dari lombok sehingga manis adalah rasa dari gula.'],
        ['q'=>'Kendaraan : Mobil = .... : ....','opts'=>['Binatang : Lawan','Kapal : Perahu','Gaji : Upah','Orang : Pemuda','Laut : Danau'],'key'=>'D','exp'=>'Salah satu jenis dari kendaraan adalah mobil. Sehingga hubungan yang tepat adalah orang dan pemuda karena salah satu jenis orang adalah pemuda.'],
        ['q'=>'Mengantuk : Tidur = .... : ....','opts'=>['Makanan : Kalori','Terbang : Berjalan','Polisi : Pencuri','Pelanggaran : Hukuman','Lapar : Makanan'],'key'=>'E','exp'=>'Ketika mengantuk menghasilkan tidur. Sehingga lapar menghasilkan makanan.'],
        ['q'=>'Kosong : Hampa = .... : ....','opts'=>['Penuh : Sesak','Cair : Encer','Renggut : Sorak','Ubi : Akar','Siang : Malam'],'key'=>'B','exp'=>'Kosong memiliki sinonim Hampa, sebagaimana Cair memiliki sinonim Encer.'],
        ['q'=>'Rokok : Batuk = .... : ....','opts'=>['Sambal : Asma','Sambal : Pedas','Sambal : Diare','Sambal : Sesak','Sambal : Panas'],'key'=>'C','exp'=>'Mengonsumsi Rokok dapat menyebabkan Batuk, sebagaimana mengonsumsi Sambal dapat menyebabkan Diare.'],
    ];
    $a = $analogies[array_rand($analogies)];
    $opts = $a['opts'];
    $correctVal = $opts[0];
    shuffle($opts);
    $l = ['A','B','C','D','E'];
    $correct = '';
    foreach($opts as $i=>$v) if($v===$correctVal) { $correct=$l[$i]; break; }
    return ['pertanyaan'=>$a['q'],'pilihan_a'=>$opts[0],'pilihan_b'=>$opts[1],'pilihan_c'=>$opts[2],'pilihan_d'=>$opts[3],'pilihan_e'=>$opts[4],'jawaban_benar'=>$correct,'pembahasan'=>$a['exp']];
}

function genSilogisme() {
    $silogisms = [
        ['q'=>'Kalimat yang ekuivalen dengan pernyataan "Tidak benar bahwa Amir siswa yang rajin belajar dan pandai" adalah:','opts'=>['Amir siswa yang tidak rajin belajar dan tidak pandai','Amir siswa yang tidak rajin belajar tetapi pandai','Amir siswa yang rajin belajar dan pandai','Amir siswa yang tidak rajin belajar atau tidak pandai','Amir tidak pandai dan siswa yang tidak rajin belajar'],'key'=>'D','exp'=>'"Tidak benar bahwa Amir siswa yang rajin belajar dan pandai" adalah ingkaran dari pernyataan "Amir siswa yang rajin belajar dan pandai." Kalimat yang ekuivalen adalah: "Amir siswa yang tidak rajin belajar atau tidak pandai."'],
        ['q'=>'Konvers dari pernyataan "Jika Badu seorang pelajar SMA maka ia mempunyai kartu pelajar" adalah:','opts'=>['Jika Badu bukan seorang pelajar SMA, maka ia tidak mempunyai kartu pelajar','Jika Badu seorang pelajar SMA, maka ia tidak mempunyai kartu pelajar','Badu seorang pelajar SMA atau ia tidak mempunyai kartu pelajar','Badu seorang pelajar SMA dan ia tidak mempunyai kartu pelajar','Jika Badu mempunyai kartu pelajar maka ia seorang pelajar SMA'],'key'=>'E','exp'=>'Konvers dari pernyataan "Jika Badu mempunyai kartu pelajar, maka Badu seorang pelajar SMA."'],
        ['q'=>'Invers dari pernyataan "Jika ia rajin belajar maka ia naik kelas" adalah:','opts'=>['Jika ia tidak rajin belajar maka ia tidak naik kelas','Jika ia tidak rajin belajar maka ia naik kelas','Jika ia tidak naik kelas maka ia tidak rajin belajar','Jika ia naik kelas maka ia tidak rajin belajar','Jika ia naik kelas maka ia rajin belajar'],'key'=>'A','exp'=>'Implikasi p→q memiliki invers ∼p→∼q. Jadi, invers dari pernyataan "Jika ia rajin belajar maka ia naik kelas" adalah "Jika ia tidak rajin belajar maka ia tidak naik kelas."'],
        ['q'=>'Semua warga Desa Suket adalah nelayan. Pak Imam adalah warga Desa Suket.','opts'=>['Pak Imam pasti seorang nelayan','Pak Imam bukan seorang nelayan','Pak Imam terpaksa jadi nelayan','Pak Imam belum mau jadi nelayan','Pak Imam nelayan dari desa sebelah Desa Suket'],'key'=>'A','exp'=>'Menggunakan Modus Ponens: Premis 1: p→q, Premis 2: p. Kesimpulan: Pak Imam adalah bagian dari warga Desa Suket, sehingga Pak Imam adalah nelayan.'],
        ['q'=>'Jika ingin membantu maka harus ikut mengangkat. Jika ikut mengangkat maka perlu bergantian. Kesimpulan dari pernyataan tersebut adalah:','opts'=>['Semua yang bergantian ikut mengangkat','Semua yang bergantian tentu ingin membantu','Semua yang ingin membantu perlu bergantian','Sebagian yang ikut mengangkat ingin membantu','Sebagian yang ingin membantu perlu bergantian'],'key'=>'C','exp'=>'Menggunakan silogisme: Premis 1: Jika ingin membantu maka harus ikut mengangkat. Premis 2: Jika ikut mengangkat maka perlu bergantian. Kesimpulan: Jika ingin membantu maka perlu bergantian.'],
    ];
    $s = $silogisms[array_rand($silogisms)];
    $opts = $s['opts'];
    $correctVal = $opts[0];
    shuffle($opts);
    $l = ['A','B','C','D','E'];
    $correct = '';
    foreach($opts as $i=>$v) if($v===$correctVal) { $correct=$l[$i]; break; }
    return ['pertanyaan'=>$s['q'],'pilihan_a'=>$opts[0],'pilihan_b'=>$opts[1],'pilihan_c'=>$opts[2],'pilihan_d'=>$opts[3],'pilihan_e'=>$opts[4],'jawaban_benar'=>$correct,'pembahasan'=>$s['exp']];
}

function genAnalitis() {
    $analitis = [
        ['q'=>'Perhatikan pernyataan berikut: "Jika hujan turun, maka jalan menjadi licin. Jalan tidak licin." Kesimpulan yang tepat adalah:','opts'=>['Hujan turun','Hujan tidak turun','Jalan basah','Jalan kering','Tidak dapat disimpulkan'],'key'=>'B','exp'=>'Menggunakan modus tollens: Jika p→q dan ~q, maka ~p. Jadi, jika jalan tidak licin (~q), maka hujan tidak turun (~p).'],
        ['q'=>'"Semua dokter adalah orang yang berpendidikan tinggi. Sebagian orang yang berpendidikan tinggi adalah kaya." Kesimpulan yang benar adalah:','opts'=>['Semua dokter kaya','Sebagian dokter kaya','Tidak ada dokter yang kaya','Semua orang kaya adalah dokter','Tidak dapat disimpulkan'],'key'=>'B','exp'=>'Dari premis: Semua dokter → berpendidikan tinggi. Sebagian berpendidikan tinggi → kaya. Maka sebagian dokter mungkin kaya, tapi tidak semua.'],
        ['q'=>'"Jika seseorang rajin belajar, maka ia akan lulus. Jika seseorang lulus, maka ia akan mendapat pekerjaan." Kesimpulan yang benar adalah:','opts'=>['Jika rajin belajar, maka akan mendapat pekerjaan','Jika mendapat pekerjaan, maka rajin belajar','Jika tidak rajin belajar, maka tidak mendapat pekerjaan','Jika tidak mendapat pekerjaan, maka tidak lulus','Tidak dapat disimpulkan'],'key'=>'A','exp'=>'Silogisme: p→q, q→r, maka p→r. Jadi, jika rajin belajar (p), maka akan lulus (q), dan jika lulus, maka mendapat pekerjaan (r).'],
        ['q'=>'"Sebagian siswa suka matematika. Semua yang suka matematika adalah orang yang logis." Kesimpulan yang benar adalah:','opts'=>['Semua siswa logis','Sebagian siswa logis','Tidak ada siswa yang logis','Semua orang logis adalah siswa','Tidak dapat disimpulkan'],'key'=>'B','exp'=>'Sebagian siswa → suka matematika. Semua suka matematika → logis. Maka sebagian siswa logis.'],
        ['q'=>'"Jika tidak ada korupsi, maka dana digunakan optimal. Jika dana digunakan optimal, maka gedung sekolah diperbaiki." Kesimpulan yang benar adalah:','opts'=>['Jika ada korupsi, maka gedung sekolah diperbaiki','Jika ada korupsi, maka gedung sekolah tidak diperbaiki','Jika tidak ada korupsi, maka gedung sekolah diperbaiki','Jika tidak ada korupsi, maka gedung sekolah tidak diperbaiki','Tidak dapat disimpulkan'],'key'=>'C','exp'=>'Silogisme: p→q, q→r, maka p→r. Jadi, jika tidak ada korupsi (p), maka dana optimal (q), dan jika dana optimal, maka gedung diperbaiki (r).'],
    ];
    $a = $analitis[array_rand($analitis)];
    $opts = $a['opts'];
    $correctVal = $opts[0];
    shuffle($opts);
    $l = ['A','B','C','D','E'];
    $correct = '';
    foreach($opts as $i=>$v) if($v===$correctVal) { $correct=$l[$i]; break; }
    return ['pertanyaan'=>$a['q'],'pilihan_a'=>$opts[0],'pilihan_b'=>$opts[1],'pilihan_c'=>$opts[2],'pilihan_d'=>$opts[3],'pilihan_e'=>$opts[4],'jawaban_benar'=>$correct,'pembahasan'=>$a['exp']];
}

function genTWK($topik) {
    $facts = [];
    
    // Nasionalisme
    if (strpos(strtolower($topik), 'nasional') !== false) {
        $facts = [
            ['q'=>'Sumpah Pemuda dilafalkan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Semarang'],'key'=>'A','exp'=>'Sumpah Pemuda diikrarkan di Jakarta pada 28 Oktober 1928.'],
            ['q'=>'Hari Pahlawan diperingati tanggal...','opts'=>['10 November','17 Agustus','20 Mei','1 Juni','28 Oktober'],'key'=>'A','exp'=>'Hari Pahlawan: 10 November, mengenang Pertempuran Surabaya 1945.'],
            ['q'=>'Proklamasi Kemerdekaan dilaksanakan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Medan'],'key'=>'A','exp'=>'Proklamasi Kemerdekaan dilaksanakan di Jakarta, 17 Agustus 1945.'],
        ];
    }
    // Sejarah
    elseif (strpos(strtolower($topik), 'sejarah') !== false) {
        $facts = [
            ['q'=>'Proklamasi Kemerdekaan dilaksanakan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Medan'],'key'=>'A','exp'=>'Proklamasi Kemerdekaan dilaksanakan di Jakarta, 17 Agustus 1945.'],
            ['q'=>'Sumpah Pemuda dilafalkan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Semarang'],'key'=>'A','exp'=>'Sumpah Pemuda diikrarkan di Jakarta pada 28 Oktober 1928.'],
            ['q'=>'Konferensi Asia-Afrika tahun 1955 diselenggarakan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Medan'],'key'=>'B','exp'=>'KAA 1955 diselenggarakan di Bandung.'],
            ['q'=>'G30S/PKI terjadi pada tahun...','opts'=>['1963','1965','1967','1969','1971'],'key'=>'B','exp'=>'G30S/PKI terjadi 30 September 1965.'],
        ];
    }
    // Pancasila
    elseif (strpos(strtolower($topik), 'pancasila') !== false) {
        $facts = [
            ['q'=>'Pancasila dirumuskan oleh...','opts'=>['BPUPKI','PPKI','MPR','DPR','Presiden'],'key'=>'A','exp'=>'Pancasila dirumuskan oleh BPUPKI.'],
            ['q'=>'Sila pertama Pancasila berbunyi...','opts'=>['Ketuhanan Yang Maha Esa','Kemanusiaan','Persatuan','Kerakyatan','Keadilan'],'key'=>'A','exp'=>'Sila pertama: Ketuhanan Yang Maha Esa.'],
            ['q'=>'Lambang sila ketiga Pancasila adalah...','opts'=>['Bintang','Rantai','Pohon Beringin','Kepala Banteng','Padi dan Kapas'],'key'=>'C','exp'=>'Sila ketiga (Persatuan Indonesia) dilambangkan Pohon Beringin.'],
        ];
    }
    // Bahasa Indonesia
    elseif (strpos(strtolower($topik), 'bahasa') !== false) {
        $facts = [
            ['q'=>'KBBI adalah singkatan dari...','opts'=>['Kamus Besar Bahasa Indonesia','Kamus Bahasa Baku Indonesia','Kamus Baku Bahasa Indonesia','Kamus Besar Baku Indonesia','Kamus Bahasa Indonesia Besar'],'key'=>'A','exp'=>'KBBI = Kamus Besar Bahasa Indonesia.'],
            ['q'=>'Kata yang benar adalah...','opts'=>['sekadar','sekedar','se kedar','se kad ar','sekader'],'key'=>'A','exp'=>'Bentuk baku: sekadar.'],
            ['q'=>'Kata yang benar adalah...','opts'=>['sesaat','sebentar','se saat','sebentr','sesa at'],'key'=>'A','exp'=>'Bentuk baku: sesaat.'],
        ];
    }
    // UUD 1945
    elseif (strpos(strtolower($topik), 'uud') !== false) {
        $facts = [
            ['q'=>'UUD 1945 disahkan oleh...','opts'=>['BPUPKI','PPKI','MPR','DPR','Presiden'],'key'=>'B','exp'=>'UUD 1945 disahkan oleh PPKI pada 18 Agustus 1945.'],
            ['q'=>'UUD 1945 terdiri dari berapa bab?','opts'=>['16','18','20','22','24'],'key'=>'A','exp'=>'UUD 1945 terdiri dari 16 bab.'],
            ['q'=>'Amandemen ke-3 UUD 1945 melahirkan lembaga...','opts'=>['MK dan KY','KPK dan BPK','DPR dan DPD','MA dan KY','KPU dan Bawaslu'],'key'=>'A','exp'=>'Amandemen ke-3 melahirkan Mahkamah Konstitusi dan Komisi Yudisial.'],
        ];
    }
    // Pilar Negara
    elseif (strpos(strtolower($topik), 'pilar') !== false) {
        $facts = [
            ['q'=>'4 Pilar Negara Indonesia adalah...','opts'=>['Pancasila, UUD 1945, NKRI, Bhinneka Tunggal Ika','Pancasila, UUD 1945, NKRI, Gotong Royong','Pancasila, UUD 1945, Bhinneka Tunggal Ika, Gotong Royong','UUD 1945, NKRI, Bhinneka Tunggal Ika, Gotong Royong','Pancasila, NKRI, Bhinneka Tunggal Ika, Gotong Royong'],'key'=>'A','exp'=>'4 Pilar: Pancasila, UUD 1945, NKRI, Bhinneka Tunggal Ika.'],
            ['q'=>'Pilar pertama negara Indonesia adalah...','opts'=>['Pancasila','UUD 1945','NKRI','Bhinneka Tunggal Ika','Gotong Royong'],'key'=>'A','exp'=>'Pilar pertama: Pancasila sebagai dasar negara.'],
        ];
    }
    // Integritas
    elseif (strpos(strtolower($topik), 'integritas') !== false) {
        $facts = [
            ['q'=>'KPK singkatan dari...','opts'=>['Komisi Pemberantasan Korupsi','Komisi Pengawas Korupsi','Komisi Pencegahan Korupsi','Komisi Pemeriksa Korupsi','Komisi Penegak Korupsi'],'key'=>'A','exp'=>'KPK = Komisi Pemberantasan Korupsi.'],
            ['q'=>'Tipikor adalah singkatan dari...','opts'=>['Tindak Pidana Korupsi','Tindak Pidana Koruptor','Tindak Pidana Kriminal','Tindak Pidana Kriminalitas','Tindak Pidana Kriminal Korupsi'],'key'=>'A','exp'=>'Tipikor = Tindak Pidana Korupsi.'],
        ];
    }
    // Bela Negara
    elseif (strpos(strtolower($topik), 'bela') !== false) {
        $facts = [
            ['q'=>'Komponen utama pertahanan negara adalah...','opts'=>['TNI','Polri','Brimob','Satpol PP','Hanura'],'key'=>'A','exp'=>'Komponen utama pertahanan: TNI.'],
            ['q'=>'Sistem pertahanan Indonesia disebut...','opts'=>['SISHANKAMRATA','SISPERNAS','SISKEHAT','SISPRES','SISPROTAN'],'key'=>'A','exp'=>'Sistem Pertahanan dan Keamanan Rakyat Semesta (SISHANKAMRATA).'],
        ];
    }
    // Default (all TWK questions)
    else {
        error_log("Unmatched TWK topic: '$topik', using default facts");
        $facts = [
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
    }
    
    $f=$facts[array_rand($facts)]; $opts=$f['opts']; $correctVal=$opts[0]; shuffle($opts);
    $l=['A','B','C','D','E']; $correct='';
    foreach($opts as $i=>$v) if($v===$correctVal) { $correct=$l[$i]; break; }
    return ['pertanyaan'=>$f['q'],'pilihan_a'=>$opts[0],'pilihan_b'=>$opts[1],'pilihan_c'=>$opts[2],'pilihan_d'=>$opts[3],'pilihan_e'=>$opts[4],'jawaban_benar'=>$correct,'pembahasan'=>$f['exp']];
}

function genTKP($topik) {
    $scenarios = [];
    
    // Kepribadian
    if (strpos(strtolower($topik), 'kepribadian') !== false) {
        $scenarios = [
            ['q'=>'Saat rapat, rekan mengkritik ide Anda dengan nada tinggi. Tindakan Anda...','opts'=>['Membantah dengan nada yang sama tinggi','Mendengarkan, mencatat poin valid, dan menjawab dengan tenang','Keluar dari rapat','Menyuruhnya bicara setelah rapat','Melaporkannya ke atasan'],'key'=>'B','exp'=>'Menerima kritik dengan dewasa dan profesional adalah jejaring kerja yang konstruktif.'],
            ['q'=>'Anda memiliki kebiasaan datang terlambat. Tindakan Anda...','opts'=>['Mengabaikan karena tidak ada yang menegur','Membuat komitmen datang tepat waktu dan minta rekan mengingatkan','Mencari alasan untuk membenarkan keterlambatan','Menyalahkan transportasi','Pindah ke divisi lain'],'key'=>'B','exp'=>'Mengakuri kelemahan dan berusaha memperbaiki adalah karakter kepribadian yang kuat.'],
        ];
    }
    // Pelayanan Publik
    elseif (strpos(strtolower($topik), 'pelayanan') !== false) {
        $scenarios = [
            ['q'=>'Anda menemukan warga lansia kesulitan naik tangga kantor. Tindakan Anda...','opts'=>['Menyuruhnya menunggu di bawah','Membantunya naik dengan sabar','Mengatakan kantor tidak memiliki lift','Mengabaikannya karena sibuk','Menyuruh keluarganya membantu'],'key'=>'B','exp'=>'Membantu lansia dengan empati dan solusi konkret menunjukkan pelayanan publik yang inklusif.'],
            ['q'=>'Warga mengeluh prosedur terlalu rumit. Tindakan Anda...','opts'=>['Menyuruhnya ikuti aturan','Menjelaskan dengan sabar dan cari solusi sederhana','Mengabaikan keluhan','Melaporkan ke atasan','Menyuruhnya ke instansi lain'],'key'=>'B','exp'=>'Pelayanan publik yang baik memprioritaskan kemudahan bagi masyarakat.'],
        ];
    }
    // Jejaring Kerja
    elseif (strpos(strtolower($topik), 'jejaring') !== false) {
        $scenarios = [
            ['q'=>'Saat rapat, rekan mengkritik ide Anda dengan nada tinggi. Tindakan Anda...','opts'=>['Membantah dengan nada yang sama tinggi','Mendengarkan, mencatat poin valid, dan menjawab dengan tenang','Keluar dari rapat','Menyuruhnya bicara setelah rapat','Melaporkannya ke atasan'],'key'=>'B','exp'=>'Menerima kritik dengan dewasa dan profesional adalah jejaring kerja yang konstruktif.'],
            ['q'=>'Rekan kerja sering tidak menyelesaikan tugas tim. Tindakan Anda...','opts'=>['Mengeluh di belakang','Berbicara langsung dan cari solusi bersama','Melaporkan ke atasan','Mengerjakan tugasnya sendiri','Menghindari kerja tim'],'key'=>'B','exp'=>'Komunikasi langsung dan mencari solusi bersama membangun jejaring kerja yang sehat.'],
        ];
    }
    // Sosial Budaya
    elseif (strpos(strtolower($topik), 'sosial') !== false) {
        $scenarios = [
            ['q'=>'Anda ditugaskan ke daerah dengan budaya berbeda. Tindakan Anda...','opts'=>['Menolak karena tidak cocok','Mempelajari dan menghormati budaya setempat','Mengeluh ke rekan','Tetap bertindak sesuai budaya asal','Pindah tugasan'],'key'=>'B','exp'=>'Menghormati keberagaman budaya adalah nilai sosial yang penting.'],
            ['q'=>'Tetangga baru dari suku berbeda. Tindakan Anda...','opts'=>['Menghindari','Menyapa dan mengenal','Mengomentari kebiasaan mereka','Melaporkan ke RT','Tidak peduli'],'key'=>'B','exp'=>'Sikap terbuka dan ramah membangun harmoni sosial.'],
        ];
    }
    // Profesionalisme
    elseif (strpos(strtolower($topik), 'profesional') !== false) {
        $scenarios = [
            ['q'=>'Anda diminta mengganti tugas rekan yang sakit, padahal Anda sibuk. Tindakan Anda...','opts'=>['Menolak karena tidak sesuai job desk','Menerima, menentukan prioritas, dan komunikasikan ke atasan jika perlu bantuan','Mengerjakan asal-asalan','Mengeluh di grup kerja','Mengatakan rekan tidak bertanggung jawab'],'key'=>'B','exp'=>'Fleksibilitas dan komunikasi terbuka adalah profesionalisme tinggi.'],
            ['q'=>'Anda membuat kesalahan dalam laporan. Tindakan Anda...','opts'=>['Menyembunyikan','Mengakui dan segera perbaiki','Menyalahkan orang lain','Menghapus bukti','Mengabaikan'],'key'=>'B','exp'=>'Jujur dan bertanggung jawab adalah inti profesionalisme.'],
        ];
    }
    // Teknologi Informasi
    elseif (strpos(strtolower($topik), 'teknologi') !== false) {
        $scenarios = [
            ['q'=>'Kantor menerapkan sistem baru. Tindakan Anda...','opts'=>['Menolak karena terbiasa lama','Belajar dan beradaptasi','Mengeluh','Menunggu orang lain','Mengabaikan'],'key'=>'B','exp'=>'Bersikap terbuka terhadap teknologi baru adalah nilai positif.'],
            ['q'=>'Data penting bocor karena kelalaian. Tindakan Anda...','opts'=>['Menyembunyikan','Melaporkan dan cari solusi','Mengabaikan','Menyalahkan sistem','Menghapus jejak'],'key'=>'B','exp'=>'Keamanan data adalah tanggung jawab bersama di era teknologi.'],
        ];
    }
    // Default (all TKP scenarios)
    else {
        $scenarios = [
            ['q'=>'Anda menemukan warga lansia kesulitan naik tangga kantor. Tindakan Anda...','opts'=>['Menyuruhnya menunggu di bawah','Membantunya naik dengan sabar','Mengatakan kantor tidak memiliki lift','Mengabaikannya karena sibuk','Menyuruh keluarganya membantu'],'key'=>'B','exp'=>'Membantu lansia dengan empati dan solusi konkret menunjukkan pelayanan publik yang inklusif.'],
            ['q'=>'Saat rapat, rekan mengkritik ide Anda dengan nada tinggi. Tindakan Anda...','opts'=>['Membantah dengan nada yang sama tinggi','Mendengarkan, mencatat poin valid, dan menjawab dengan tenang','Keluar dari rapat','Menyuruhnya bicara setelah rapat','Melaporkannya ke atasan'],'key'=>'B','exp'=>'Menerima kritik dengan dewasa dan profesional adalah jejaring kerja yang konstruktif.'],
            ['q'=>'Anda diminta mengganti tugas rekan yang sakit, padahal Anda sibuk. Tindakan Anda...','opts'=>['Menolak karena tidak sesuai job desk','Menerima, menentukan prioritas, dan komunikasikan ke atasan jika perlu bantuan','Mengerjakan asal-asalan','Mengeluh di grup kerja','Mengatakan rekan tidak bertanggung jawab'],'key'=>'B','exp'=>'Fleksibilitas dan komunikasi terbuka adalah profesionalisme tinggi.'],
        ];
    }
    
    $s=$scenarios[array_rand($scenarios)]; $opts=$s['opts']; $correctVal=$opts[1]; shuffle($opts);
    $l=['A','B','C','D','E']; $correct='';
    foreach($opts as $i=>$v) if($v===$correctVal) { $correct=$l[$i]; break; }
    return ['pertanyaan'=>$s['q'],'pilihan_a'=>$opts[0],'pilihan_b'=>$opts[1],'pilihan_c'=>$opts[2],'pilihan_d'=>$opts[3],'pilihan_e'=>$opts[4],'jawaban_benar'=>$correct,'pembahasan'=>$s['exp']];
}

// ============================================================
// DISPATCHER
// ============================================================
$soalList = [];

// For TWK and TKP, generate all available soal first, then shuffle and pick unique
if ($subtes === 'TWK') {
    $allFacts = [];
    // Nasionalisme
    if (strpos(strtolower($topik), 'nasional') !== false) {
        $allFacts = [
            ['q'=>'Sumpah Pemuda dilafalkan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Semarang'],'key'=>'A','exp'=>'Sumpah Pemuda diikrarkan di Jakarta pada 28 Oktober 1928.'],
            ['q'=>'Hari Pahlawan diperingati tanggal...','opts'=>['10 November','17 Agustus','20 Mei','1 Juni','28 Oktober'],'key'=>'A','exp'=>'Hari Pahlawan: 10 November, mengenang Pertempuran Surabaya 1945.'],
            ['q'=>'Proklamasi Kemerdekaan dilaksanakan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Medan'],'key'=>'A','exp'=>'Proklamasi Kemerdekaan dilaksanakan di Jakarta, 17 Agustus 1945.'],
            ['q'=>'Tokoh perjuangan dari Aceh adalah...','opts'=>['Cut Nyak Dien','Diponegoro','Soedirman','Hasanuddin','Tuanku Imam Bonjol'],'key'=>'A','exp'=>'Cut Nyak Dien adalah pahlawan wanita dari Aceh.'],
            ['q'=>'Semangat nasionalisme muncul pada tahun...','opts'=>['1908','1920','1930','1942','1945'],'key'=>'A','exp'=>'Kebangkitan nasional dimulai tahun 1908 dengan Budi Utomo.'],
        ];
    }
    // Sejarah
    elseif (strpos(strtolower($topik), 'sejarah') !== false) {
        $allFacts = [
            ['q'=>'Proklamasi Kemerdekaan dilaksanakan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Medan'],'key'=>'A','exp'=>'Proklamasi Kemerdekaan dilaksanakan di Jakarta, 17 Agustus 1945.'],
            ['q'=>'Sumpah Pemuda dilafalkan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Semarang'],'key'=>'A','exp'=>'Sumpah Pemuda diikrarkan di Jakarta pada 28 Oktober 1928.'],
            ['q'=>'Konferensi Asia-Afrika tahun 1955 diselenggarakan di...','opts'=>['Jakarta','Bandung','Surabaya','Yogyakarta','Medan'],'key'=>'B','exp'=>'KAA 1955 diselenggarakan di Bandung.'],
            ['q'=>'G30S/PKI terjadi pada tahun...','opts'=>['1963','1965','1967','1969','1971'],'key'=>'B','exp'=>'G30S/PKI terjadi 30 September 1965.'],
            ['q'=>'Reformasi terjadi pada tahun...','opts'=>['1996','1997','1998','1999','2000'],'key'=>'C','exp'=>'Reformasi terjadi pada tahun 1998.'],
        ];
    }
    // Pancasila
    elseif (strpos(strtolower($topik), 'pancasila') !== false) {
        $allFacts = [
            ['q'=>'Pancasila dirumuskan oleh...','opts'=>['BPUPKI','PPKI','MPR','DPR','Presiden'],'key'=>'A','exp'=>'Pancasila dirumuskan oleh BPUPKI.'],
            ['q'=>'Sila pertama Pancasila berbunyi...','opts'=>['Ketuhanan Yang Maha Esa','Kemanusiaan','Persatuan','Kerakyatan','Keadilan'],'key'=>'A','exp'=>'Sila pertama: Ketuhanan Yang Maha Esa.'],
            ['q'=>'Lambang sila ketiga Pancasila adalah...','opts'=>['Bintang','Rantai','Pohon Beringin','Kepala Banteng','Padi dan Kapas'],'key'=>'C','exp'=>'Sila ketiga (Persatuan Indonesia) dilambangkan Pohon Beringin.'],
            ['q'=>'Sila kedua Pancasila berbunyi...','opts'=>['Kemanusiaan yang adil dan beradab','Kemanusiaan yang adil','Keadilan bagi seluruh rakyat Indonesia','Persatuan Indonesia','Ketuhanan Yang Maha Esa'],'key'=>'A','exp'=>'Sila kedua: Kemanusiaan yang adil dan beradab.'],
            ['q'=>'Lambang sila pertama Pancasila adalah...','opts'=>['Bintang','Rantai','Pohon Beringin','Kepala Banteng','Padi dan Kapas'],'key'=>'A','exp'=>'Sila pertama dilambangkan Bintang.'],
        ];
    }
    // Bahasa Indonesia
    elseif (strpos(strtolower($topik), 'bahasa') !== false) {
        $allFacts = [
            ['q'=>'KBBI adalah singkatan dari...','opts'=>['Kamus Besar Bahasa Indonesia','Kamus Bahasa Baku Indonesia','Kamus Baku Bahasa Indonesia','Kamus Besar Baku Indonesia','Kamus Bahasa Indonesia Besar'],'key'=>'A','exp'=>'KBBI = Kamus Besar Bahasa Indonesia.'],
            ['q'=>'Kata yang benar adalah...','opts'=>['sekadar','sekedar','se kedar','se kad ar','sekader'],'key'=>'A','exp'=>'Bentuk baku: sekadar.'],
            ['q'=>'Kata yang benar adalah...','opts'=>['sesaat','sebentar','se saat','sebentr','sesa at'],'key'=>'A','exp'=>'Bentuk baku: sesaat.'],
            ['q'=>'Kata yang benar adalah...','opts'=>['apabila','kalau','jika','bila','apakala'],'key'=>'A','exp'=>'Bentuk baku: apabila.'],
            ['q'=>'Kata yang benar adalah...','opts'=>['hendaknya','harusnya','wajibnya','mestinya','perlu'],'key'=>'A','exp'=>'Bentuk baku: hendaknya.'],
        ];
    }
    // UUD 1945
    elseif (strpos(strtolower($topik), 'uud') !== false) {
        $allFacts = [
            ['q'=>'UUD 1945 disahkan oleh...','opts'=>['BPUPKI','PPKI','MPR','DPR','Presiden'],'key'=>'B','exp'=>'UUD 1945 disahkan oleh PPKI pada 18 Agustus 1945.'],
            ['q'=>'UUD 1945 terdiri dari berapa bab?','opts'=>['16','18','20','22','24'],'key'=>'A','exp'=>'UUD 1945 terdiri dari 16 bab.'],
            ['q'=>'Amandemen ke-3 UUD 1945 melahirkan lembaga...','opts'=>['MK dan KY','KPK dan BPK','DPR dan DPD','MA dan KY','KPU dan Bawaslu'],'key'=>'A','exp'=>'Amandemen ke-3 melahirkan Mahkamah Konstitusi dan Komisi Yudisial.'],
            ['q'=>'UUD 1945 diamandemen pertama kali pada tahun...','opts'=>['1999','2000','2001','2002','2003'],'key'=>'A','exp'=>'Amandemen pertama UUD 1945 tahun 1999.'],
            ['q'=>'Pasal 1 UUD 1945 menyatakan...','opts'=>['Negara Indonesia ialah negara kesatuan','Negara Indonesia adalah republik','Negara Indonesia adalah demokrasi','Negara Indonesia adalah monarki','Negara Indonesia adalah federasi'],'key'=>'A','exp'=>'Pasal 1: Negara Indonesia ialah negara kesatuan.'],
        ];
    }
    // Pilar Negara
    elseif (strpos(strtolower($topik), 'pilar') !== false) {
        $allFacts = [
            ['q'=>'4 Pilar Negara Indonesia adalah...','opts'=>['Pancasila, UUD 1945, NKRI, Bhinneka Tunggal Ika','Pancasila, UUD 1945, NKRI, Gotong Royong','Pancasila, UUD 1945, Bhinneka Tunggal Ika, Gotong Royong','UUD 1945, NKRI, Bhinneka Tunggal Ika, Gotong Royong','Pancasila, NKRI, Bhinneka Tunggal Ika, Gotong Royong'],'key'=>'A','exp'=>'4 Pilar: Pancasila, UUD 1945, NKRI, Bhinneka Tunggal Ika.'],
            ['q'=>'Pilar pertama negara Indonesia adalah...','opts'=>['Pancasila','UUD 1945','NKRI','Bhinneka Tunggal Ika','Gotong Royong'],'key'=>'A','exp'=>'Pilar pertama: Pancasila sebagai dasar negara.'],
            ['q'=>'Pilar kedua negara Indonesia adalah...','opts'=>['UUD 1945','Pancasila','NKRI','Bhinneka Tunggal Ika','Gotong Royong'],'key'=>'A','exp'=>'Pilar kedua: UUD 1945 sebagai konstitusi.'],
            ['q'=>'Pilar ketiga negara Indonesia adalah...','opts'=>['NKRI','Pancasila','UUD 1945','Bhinneka Tunggal Ika','Gotong Royong'],'key'=>'A','exp'=>'Pilar ketiga: NKRI sebagai bentuk negara.'],
            ['q'=>'Pilar keempat negara Indonesia adalah...','opts'=>['Bhinneka Tunggal Ika','Pancasila','UUD 1945','NKRI','Gotong Royong'],'key'=>'A','exp'=>'Pilar keempat: Bhinneka Tunggal Ika sebagai semboyan.'],
        ];
    }
    // Integritas
    elseif (strpos(strtolower($topik), 'integritas') !== false) {
        $allFacts = [
            ['q'=>'KPK singkatan dari...','opts'=>['Komisi Pemberantasan Korupsi','Komisi Pengawas Korupsi','Komisi Pencegahan Korupsi','Komisi Pemeriksa Korupsi','Komisi Penegak Korupsi'],'key'=>'A','exp'=>'KPK = Komisi Pemberantasan Korupsi.'],
            ['q'=>'Tipikor adalah singkatan dari...','opts'=>['Tindak Pidana Korupsi','Tindak Pidana Koruptor','Tindak Pidana Kriminal','Tindak Pidana Kriminalitas','Tindak Pidana Kriminal Korupsi'],'key'=>'A','exp'=>'Tipikor = Tindak Pidana Korupsi.'],
            ['q'=>'Gratifikasi adalah...','opts'=>['Pemberian dalam rangka jabatan','Hadiah ulang tahun','Bonus kinerja','Tunjangan transport','Uang makan'],'key'=>'A','exp'=>'Gratifikasi adalah pemberian dalam rangka jabatan dan bertentaran dengan kewajiban.'],
            ['q'=>'Laporan korupsi dapat dilakukan ke...','opts'=>['KPK','Polri','Kejaksaan','Pengadilan','Semua benar'],'key'=>'E','exp'=>'Laporan korupsi dapat dilakukan ke KPK, Polri, Kejaksaan, atau Pengadilan.'],
            ['q'=>'Sikap anti-korupsi adalah...','opts'=>['Menolak gratifikasi','Menerima hadiah','Mengabaikan pelanggaran','Ikut serta','Netral'],'key'=>'A','exp'=>'Anti-korupsi: menolak gratifikasi dan melaporkan pelanggaran.'],
        ];
    }
    // Bela Negara
    elseif (strpos(strtolower($topik), 'bela') !== false) {
        $allFacts = [
            ['q'=>'Komponen utama pertahanan negara adalah...','opts'=>['TNI','Polri','Brimob','Satpol PP','Hanura'],'key'=>'A','exp'=>'Komponen utama pertahanan: TNI.'],
            ['q'=>'Sistem pertahanan Indonesia disebut...','opts'=>['SISHANKAMRATA','SISPERNAS','SISKEHAT','SISPRES','SISPROTAN'],'key'=>'A','exp'=>'Sistem Pertahanan dan Keamanan Rakyat Semesta (SISHANKAMRATA).'],
            ['q'=>'Wajib militer di Indonesia disebut...','opts'=>['Wamil','Wajib Militer','Compulsory Military Service','Wajib Bela Negara','Bela Negara'],'key'=>'D','exp'=>'Wajib Bela Negara adalah kewajiban pertahanan.'],
            ['q'=>'Komponen cadangan pertahanan adalah...','opts'=>['Masyarakat sipil','Polri','Brimob','Satpol PP','Hanura'],'key'=>'A','exp'=>'Komponen cadangan: masyarakat sipil yang dilatih.'],
            ['q'=>'Tugas utama TNI adalah...','opts'=>['Pertahanan negara','Keamanan dalam negeri','Penegakan hukum','Pelayanan publik','Administrasi'],'key'=>'A','exp'=>'Tugas utama TNI: upaya pertahanan negara.'],
        ];
    }
    // Default (all TWK questions)
    else {
        $allFacts = [
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
    }
    
    // Shuffle and pick unique soal
    shuffle($allFacts);
    $selectedFacts = array_slice($allFacts, 0, min($jumlah, count($allFacts)));
    
    foreach ($selectedFacts as $f) {
        $opts = $f['opts'];
        $correctVal = $opts[0];
        shuffle($opts);
        $l = ['A','B','C','D','E'];
        $correct = '';
        foreach($opts as $i=>$v) if($v===$correctVal) { $correct=$l[$i]; break; }
        
        $s = [
            'pertanyaan' => $f['q'],
            'pilihan_a' => $opts[0],
            'pilihan_b' => $opts[1],
            'pilihan_c' => $opts[2],
            'pilihan_d' => $opts[3],
            'pilihan_e' => $opts[4],
            'jawaban_benar' => $correct,
            'pembahasan' => $f['exp']
        ];
        
        // Enrich
        $s['tips_trick'] = buildTips($subtes, $topik);
        $s['related_links'] = buildLinks($subtes, $topik);
        $s['subtes'] = $subtes;
        $s['topik'] = $topik;
        $s['tipe'] = $tipe;
        $soalList[] = $s;
    }
}
// For TKP, similar approach
elseif ($subtes === 'TKP') {
    $allScenarios = [];
    
    // Pelayanan Publik
    if (strpos(strtolower($topik), 'pelayanan') !== false) {
        $allScenarios = [
            ['q'=>'Anda menemukan warga lansia kesulitan naik tangga kantor. Tindakan Anda...','opts'=>['Menyuruhnya menunggu di bawah','Membantunya naik dengan sabar','Mengatakan kantor tidak memiliki lift','Mengabaikannya karena sibuk','Menyuruh keluarganya membantu'],'key'=>'B','exp'=>'Membantu lansia dengan empati dan solusi konkret menunjukkan pelayanan publik yang inklusif.'],
            ['q'=>'Warga mengeluh prosedur terlalu rumit. Tindakan Anda...','opts'=>['Menyuruhnya ikuti aturan','Menjelaskan dengan sabar dan cari solusi sederhana','Mengabaikan keluhan','Melaporkan ke atasan','Menyuruhnya ke instansi lain'],'key'=>'B','exp'=>'Pelayanan publik yang baik memprioritaskan kemudahan bagi masyarakat.'],
            ['q'=>'Warga datang setelah jam kerja. Tindakan Anda...','opts'=>['Menolak melayani','Melayani dengan senang hati','Menyuruhnya datang besok','Mengabaikan','Marah-marah'],'key'=>'B','exp'=>'Pelayanan publik yang baik tetap melayani meskipun di luar jam kerja jika memungkinkan.'],
        ];
    }
    // Jejaring Kerja
    elseif (strpos(strtolower($topik), 'jejaring') !== false) {
        $allScenarios = [
            ['q'=>'Saat rapat, rekan mengkritik ide Anda dengan nada tinggi. Tindakan Anda...','opts'=>['Membantah dengan nada yang sama tinggi','Mendengarkan, mencatat poin valid, dan menjawab dengan tenang','Keluar dari rapat','Menyuruhnya bicara setelah rapat','Melaporkannya ke atasan'],'key'=>'B','exp'=>'Menerima kritik dengan dewasa dan profesional adalah jejaring kerja yang konstruktif.'],
            ['q'=>'Rekan kerja sering tidak menyelesaikan tugas tim. Tindakan Anda...','opts'=>['Mengeluh di belakang','Berbicara langsung dan cari solusi bersama','Melaporkan ke atasan','Mengerjakan tugasnya sendiri','Menghindari kerja tim'],'key'=>'B','exp'=>'Komunikasi langsung dan mencari solusi bersama membangun jejaring kerja yang sehat.'],
            ['q'=>'Ada konflik antar divisi. Tindakan Anda...','opts'=>['Mengambil pihak satu divisi','Mediasi dan cari solusi win-win','Mengabaikan','Melaporkan ke atasan','Mengundurkan diri'],'key'=>'B','exp'=>'Mediasi konflik dengan solusi win-win membangun jejaring kerja yang baik.'],
        ];
    }
    // Sosial Budaya
    elseif (strpos(strtolower($topik), 'sosial') !== false) {
        $allScenarios = [
            ['q'=>'Anda ditugaskan ke daerah dengan budaya berbeda. Tindakan Anda...','opts'=>['Menolak karena tidak cocok','Mempelajari dan menghormati budaya setempat','Mengeluh ke rekan','Tetap bertindak sesuai budaya asal','Pindah tugasan'],'key'=>'B','exp'=>'Menghormati keberagaman budaya adalah nilai sosial yang penting.'],
            ['q'=>'Tetangga baru dari suku berbeda. Tindakan Anda...','opts'=>['Menghindari','Menyapa dan mengenal','Mengomentari kebiasaan mereka','Melaporkan ke RT','Tidak peduli'],'key'=>'B','exp'=>'Sikap terbuka dan ramah membangun harmoni sosial.'],
            ['q'=>'Ada acara adat di lingkungan. Tindakan Anda...','opts'=>['Mengabaikan','Ikut serta dan menghormati','Mengkritik','Melaporkan','Pindah rumah'],'key'=>'B','exp'=>'Menghormati kegiatan adat setempat adalah nilai sosial yang baik.'],
        ];
    }
    // Profesionalisme
    elseif (strpos(strtolower($topik), 'profesional') !== false) {
        $allScenarios = [
            ['q'=>'Anda diminta mengganti tugas rekan yang sakit, padahal Anda sibuk. Tindakan Anda...','opts'=>['Menolak karena tidak sesuai job desk','Menerima, menentukan prioritas, dan komunikasikan ke atasan jika perlu bantuan','Mengerjakan asal-asalan','Mengeluh di grup kerja','Mengatakan rekan tidak bertanggung jawab'],'key'=>'B','exp'=>'Fleksibilitas dan komunikasi terbuka adalah profesionalisme tinggi.'],
            ['q'=>'Anda membuat kesalahan dalam laporan. Tindakan Anda...','opts'=>['Menyembunyikan','Mengakui dan segera perbaiki','Menyalahkan orang lain','Menghapus bukti','Mengabaikan'],'key'=>'B','exp'=>'Jujur dan bertanggung jawab adalah inti profesionalisme.'],
            ['q'=>'Tugas baru diberikan tanpa pelatihan. Tindakan Anda...','opts'=>['Menolak','Belajar mandiri dan minta bimbingan','Mengeluh','Mengerjakan asal-asalan','Mengundurkan diri'],'key'=>'B','exp'=>'Inisiatif belajar mandiri menunjukkan profesionalisme.'],
        ];
    }
    // Teknologi Informasi
    elseif (strpos(strtolower($topik), 'teknologi') !== false) {
        $allScenarios = [
            ['q'=>'Kantor menerapkan sistem baru. Tindakan Anda...','opts'=>['Menolak karena terbiasa lama','Belajar dan beradaptasi','Mengeluh','Menunggu orang lain','Mengabaikan'],'key'=>'B','exp'=>'Bersikap terbuka terhadap teknologi baru adalah nilai positif.'],
            ['q'=>'Data penting bocor karena kelalaian. Tindakan Anda...','opts'=>['Menyembunyikan','Melaporkan dan cari solusi','Mengabaikan','Menyalahkan sistem','Menghapus jejak'],'key'=>'B','exp'=>'Keamanan data adalah tanggung jawab bersama di era teknologi.'],
            ['q'=>'Ada pelatihan teknologi baru. Tindakan Anda...','opts'=>['Menghindari','Ikuti dengan antusias','Mengeluh','Mengikuti tapi tidak fokus','Mengundurkan diri'],'key'=>'B','exp'=>'Antusiasme belajar teknologi baru adalah nilai positif.'],
        ];
    }
    // Default (all TKP scenarios)
    else {
        error_log("Unmatched TKP topic: '$topik', using default scenarios");
        $allScenarios = [
            ['q'=>'Anda menemukan warga lansia kesulitan naik tangga kantor. Tindakan Anda...','opts'=>['Menyuruhnya menunggu di bawah','Membantunya naik dengan sabar','Mengatakan kantor tidak memiliki lift','Mengabaikannya karena sibuk','Menyuruh keluarganya membantu'],'key'=>'B','exp'=>'Membantu lansia dengan empati dan solusi konkret menunjukkan pelayanan publik yang inklusif.'],
            ['q'=>'Saat rapat, rekan mengkritik ide Anda dengan nada tinggi. Tindakan Anda...','opts'=>['Membantah dengan nada yang sama tinggi','Mendengarkan, mencatat poin valid, dan menjawab dengan tenang','Keluar dari rapat','Menyuruhnya bicara setelah rapat','Melaporkannya ke atasan'],'key'=>'B','exp'=>'Menerima kritik dengan dewasa dan profesional adalah jejaring kerja yang konstruktif.'],
            ['q'=>'Anda diminta mengganti tugas rekan yang sakit, padahal Anda sibuk. Tindakan Anda...','opts'=>['Menolak karena tidak sesuai job desk','Menerima, menentukan prioritas, dan komunikasikan ke atasan jika perlu bantuan','Mengerjakan asal-asalan','Mengeluh di grup kerja','Mengatakan rekan tidak bertanggung jawab'],'key'=>'B','exp'=>'Fleksibilitas dan komunikasi terbuka adalah profesionalisme tinggi.'],
        ];
    }
    
    // Shuffle and pick unique soal
    shuffle($allScenarios);
    $selectedScenarios = array_slice($allScenarios, 0, min($jumlah, count($allScenarios)));
    
    foreach ($selectedScenarios as $s_data) {
        $opts = $s_data['opts'];
        $correctVal = $opts[1];
        shuffle($opts);
        $l = ['A','B','C','D','E'];
        $correct = '';
        foreach($opts as $i=>$v) if($v===$correctVal) { $correct=$l[$i]; break; }
        
        $s = [
            'pertanyaan' => $s_data['q'],
            'pilihan_a' => $opts[0],
            'pilihan_b' => $opts[1],
            'pilihan_c' => $opts[2],
            'pilihan_d' => $opts[3],
            'pilihan_e' => $opts[4],
            'jawaban_benar' => $correct,
            'pembahasan' => $s_data['exp']
        ];
        
        // Enrich
        $s['tips_trick'] = buildTips($subtes, $topik);
        $s['related_links'] = buildLinks($subtes, $topik);
        $s['subtes'] = $subtes;
        $s['topik'] = $topik;
        $s['tipe'] = $tipe;
        $soalList[] = $s;
    }
}
// For TIU, generate unique soal per iteration
else {
    for ($i = 0; $i < $jumlah; $i++) {
        if ($subtes === 'TIU' && $topik === 'Deret Angka') $s = genDeretAngka($kesulitan);
        elseif ($subtes === 'TIU' && $topik === 'Berhitung') $s = genBerhitung();
        elseif ($subtes === 'TIU' && $topik === 'Perbandingan') $s = genPerbandingan();
        elseif ($subtes === 'TIU' && $topik === 'Soal Cerita') $s = genSoalCerita();
        elseif ($subtes === 'TIU' && $topik === 'Analogi') $s = genAnalogi();
        elseif ($subtes === 'TIU' && $topik === 'Silogisme') $s = genSilogisme();
        elseif ($subtes === 'TIU' && $topik === 'Analitis') $s = genAnalitis();
        else {
            // Log unmatched topic for debugging
            error_log("Unmatched TIU topic: '$topik', falling back to Deret Angka");
            $s = genDeretAngka($kesulitan);
        }

        // Enrich
        $s['tips_trick'] = buildTips($subtes, $topik);
        $s['related_links'] = buildLinks($subtes, $topik);
        $s['subtes'] = $subtes;
        $s['topik'] = $topik;
        $s['tipe'] = $tipe;
        $soalList[] = $s;
    }
}

ApiResponse::success([
    'subtes' => $subtes,
    'topik' => $topik,
    'jumlah' => count($soalList),
    'soal' => $soalList
], 'Questions generated');
