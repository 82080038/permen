<?php
/**
 * Generator Helpers - Shared Functions for Question Generation
 * 
 * Common helper functions used across different subtes generators.
 */

/**
 * Generate random integer between min and max
 * @param int $min Minimum value
 * @param int $max Maximum value
 * @return int Random integer
 */
function randomInt(int $min, int $max): int {
    return random_int($min, $max);
}

/**
 * Shuffle associative array while preserving keys
 * @param array $array Array to shuffle
 * @return array Shuffled array
 */
function shuffleAssoc(array $array): array {
    $keys = array_keys($array);
    shuffle($keys);
    $new = [];
    foreach ($keys as $k) $new[$k] = $array[$k];
    return $new;
}

/**
 * Build tips string based on subtes and topic
 * @param string $subtes Subtes code (TIU, TWK, TKP)
 * @param string $topik Topic name
 * @return string Tips text
 */
function buildTips(string $subtes, string $topik): string {
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

/**
 * Build related learning links based on subtes and topic
 * @param string $subtes Subtes code (TIU, TWK, TKP)
 * @param string $topik Topic name
 * @return string JSON encoded array of links
 */
function buildLinks(string $subtes, string $topik): string {
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

/**
 * Expand explanation with context prefix
 * @param string $subtes Subtes code
 * @param string $topik Topic name
 * @param string $pertanyaan Question text
 * @param string $jawaban Correct answer
 * @param string $pembahasan Original explanation
 * @return string Expanded explanation
 */
function expandPembahasan(string $subtes, string $topik, string $pertanyaan, string $jawaban, string $pembahasan): string {
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
