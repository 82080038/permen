<?php
/**
 * Robust fix for TWK generator - handles both single-line and multi-line formats
 */

$file = 'C:\xampp\htdocs\permen\api\generators\twk_generator.php';
$content = file_get_contents($file);

// Manual specific fixes for each question
$fixes = [
    // Question 1: A -> Menggunakan produk dalam negeri dan mendukung UMKM
    [
        'search' => "'pertanyaan' => 'Semangat nasionalisme paling tepat ditunjukkan oleh...',
            'pilihan_a' => 'Menggunakan produk dalam negeri dan mendukung UMKM',
            'pilihan_b' => 'Menolak segala bentuk kerja sama dengan negara lain',
            'pilihan_c' => 'Mengkritik pemerintah tanpa memberikan solusi',
            'pilihan_d' => 'Menghindari tugas-tugas kewarganegaraan',
            'pilihan_e' => 'Menganggap budaya asing lebih superior',
            'jawaban_benar' => 'A',",
        'replace' => "'pertanyaan' => 'Semangat nasionalisme paling tepat ditunjukkan oleh...',
            'pilihan_a' => 'Menggunakan produk dalam negeri dan mendukung UMKM',
            'pilihan_b' => 'Menolak segala bentuk kerja sama dengan negara lain',
            'pilihan_c' => 'Mengkritik pemerintah tanpa memberikan solusi',
            'pilihan_d' => 'Menghindari tugas-tugas kewarganegaraan',
            'pilihan_e' => 'Menganggap budaya asing lebih superior',
            'jawaban_benar' => 'Menggunakan produk dalam negeri dan mendukung UMKM',"
    ],
    // Question 2: B -> 20 Mei
    [
        'search' => "'pertanyaan' => 'Hari Kebangkitan Nasional diperingati setiap tanggal...',
            'pilihan_a' => '17 Agustus', 'pilihan_b' => '20 Mei', 'pilihan_c' => '28 Oktober', 'pilihan_d' => '10 November', 'pilihan_e' => '1 Juni',
            'jawaban_benar' => 'Menolak segala bentuk kerja sama dengan negara lain',",
        'replace' => "'pertanyaan' => 'Hari Kebangkitan Nasional diperingati setiap tanggal...',
            'pilihan_a' => '17 Agustus', 'pilihan_b' => '20 Mei', 'pilihan_c' => '28 Oktober', 'pilihan_d' => '10 November', 'pilihan_e' => '1 Juni',
            'jawaban_benar' => '20 Mei',"
    ],
    // Question 3: C -> Kepala banteng
    [
        'search' => "'pertanyaan' => 'Lambang sila ketiga Pancasila adalah...',
            'pilihan_a' => 'Bintang emas', 'pilihan_b' => 'Pohon beringin', 'pilihan_c' => 'Kepala banteng', 'pilihan_d' => 'Padi dan kapas', 'pilihan_e' => 'Rantai',
            'jawaban_benar' => 'C',",
        'replace' => "'pertanyaan' => 'Lambang sila ketiga Pancasila adalah...',
            'pilihan_a' => 'Bintang emas', 'pilihan_b' => 'Pohon beringin', 'pilihan_c' => 'Kepala banteng', 'pilihan_d' => 'Padi dan kapas', 'pilihan_e' => 'Rantai',
            'jawaban_benar' => 'Kepala banteng',"
    ],
    // Question 5: B -> Bandung
    [
        'search' => "'pertanyaan' => 'Konferensi Asia-Afrika tahun 1955 diselenggarakan di...',
            'pilihan_a' => 'Jakarta', 'pilihan_b' => 'Bandung', 'pilihan_c' => 'Surabaya', 'pilihan_d' => 'Yogyakarta', 'pilihan_e' => 'Medan',
            'jawaban_benar' => 'B',",
        'replace' => "'pertanyaan' => 'Konferensi Asia-Afrika tahun 1955 diselenggarakan di...',
            'pilihan_a' => 'Jakarta', 'pilihan_b' => 'Bandung', 'pilihan_c' => 'Surabaya', 'pilihan_d' => 'Yogyakarta', 'pilihan_e' => 'Medan',
            'jawaban_benar' => 'Bandung',"
    ],
    // Question 6: B -> 16 Agustus 1945
    [
        'search' => "'pertanyaan' => 'Peristiwa Rengasdengklok terjadi pada tanggal...',
            'pilihan_a' => '15 Agustus 1945', 'pilihan_b' => '16 Agustus 1945', 'pilihan_c' => '17 Agustus 1945', 'pilihan_d' => '18 Agustus 1945', 'pilihan_e' => '19 Agustus 1945',
            'jawaban_benar' => '20 Mei',",
        'replace' => "'pertanyaan' => 'Peristiwa Rengasdengklok terjadi pada tanggal...',
            'pilihan_a' => '15 Agustus 1945', 'pilihan_b' => '16 Agustus 1945', 'pilihan_c' => '17 Agustus 1945', 'pilihan_d' => '18 Agustus 1945', 'pilihan_e' => '19 Agustus 1945',
            'jawaban_benar' => '16 Agustus 1945',"
    ],
    // Question 7: C -> Ernest Douwes Dekker
    [
        'search' => "'pertanyaan' => 'Tokoh yang mengusulkan nama \"Indonesia\" dalam Sumpah Pemuda adalah...',
            'pilihan_a' => 'Ir. Soekarno', 'pilihan_b' => 'Mohammad Hatta', 'pilihan_c' => 'Ernest Douwes Dekker', 'pilihan_d' => 'Sutan Sjahrir', 'pilihan_e' => 'Agus Salim',
            'jawaban_benar' => 'Kepala banteng',",
        'replace' => "'pertanyaan' => 'Tokoh yang mengusulkan nama \"Indonesia\" dalam Sumpah Pemuda adalah...',
            'pilihan_a' => 'Ir. Soekarno', 'pilihan_b' => 'Mohammad Hatta', 'pilihan_c' => 'Ernest Douwes Dekker', 'pilihan_d' => 'Sutan Sjahrir', 'pilihan_e' => 'Agus Salim',
            'jawaban_benar' => 'Ernest Douwes Dekker',"
    ],
    // Question 8: B -> Bung Tomo
    [
        'search' => "'pertanyaan' => 'Peristiwa pertempuran 10 November 1945 di Surabaya dipimpin oleh...',
            'pilihan_a' => 'Jenderal Soedirman', 'pilihan_b' => 'Bung Tomo', 'pilihan_c' => 'Ki Hajar Dewantara', 'pilihan_d' => 'Mohammad Hatta', 'pilihan_e' => 'Sutan Sjahrir',
            'jawaban_benar' => '20 Mei',",
        'replace' => "'pertanyaan' => 'Peristiwa pertempuran 10 November 1945 di Surabaya dipimpin oleh...',
            'pilihan_a' => 'Jenderal Soedirman', 'pilihan_b' => 'Bung Tomo', 'pilihan_c' => 'Ki Hajar Dewantara', 'pilihan_d' => 'Mohammad Hatta', 'pilihan_e' => 'Sutan Sjahrir',
            'jawaban_benar' => 'Bung Tomo',"
    ],
    // Question 9: B -> UUD 1945
    [
        'search' => "'pertanyaan' => 'Piagam Jakarta merupakan cikal bakal dari...',
            'pilihan_a' => 'Proklamasi Kemerdekaan', 'pilihan_b' => 'UUD 1945', 'pilihan_c' => 'Konstituante', 'pilihan_d' => 'GBHN', 'pilihan_e' => 'Tap MPR',
            'jawaban_benar' => '20 Mei',",
        'replace' => "'pertanyaan' => 'Piagam Jakarta merupakan cikal bakal dari...',
            'pilihan_a' => 'Proklamasi Kemerdekaan', 'pilihan_b' => 'UUD 1945', 'pilihan_c' => 'Konstituante', 'pilihan_d' => 'GBHN', 'pilihan_e' => 'Tap MPR',
            'jawaban_benar' => 'UUD 1945',"
    ],
    // Question 10: C -> Bebas menentukan sikap sesuai kepentingan nasional tanpa memihak blok mana pun
    [
        'search' => "'pertanyaan' => 'Politik luar negeri Indonesia yang bebas aktif berarti...',
            'pilihan_a' => 'Tidak berhubungan dengan negara lain', 'pilihan_b' => 'Memihak salah satu blok dalam perang dingin', 'pilihan_c' => 'Bebas menentukan sikap sesuai kepentingan nasional tanpa memihak blok mana pun', 'pilihan_d' => 'Menjadi anggota NATO', 'pilihan_e' => 'Menjadi anggota Pakta Warsawa',
            'jawaban_benar' => 'Kepala banteng',",
        'replace' => "'pertanyaan' => 'Politik luar negeri Indonesia yang bebas aktif berarti...',
            'pilihan_a' => 'Tidak berhubungan dengan negara lain', 'pilihan_b' => 'Memihak salah satu blok dalam perang dingin', 'pilihan_c' => 'Bebas menentukan sikap sesuai kepentingan nasional tanpa memihak blok mana pun', 'pilihan_d' => 'Menjadi anggota NATO', 'pilihan_e' => 'Menjadi anggota Pakta Warsawa',
            'jawaban_benar' => 'Bebas menentukan sikap sesuai kepentingan nasional tanpa memihak blok mana pun',"
    ],
    // Question 11: C -> KPU
    [
        'search' => "'pertanyaan' => 'Lembaga yang menyelenggarakan pemilu di Indonesia adalah...',
            'pilihan_a' => 'MPR', 'pilihan_b' => 'DPR', 'pilihan_c' => 'KPU', 'pilihan_d' => 'BPK', 'pilihan_e' => 'MK',
            'jawaban_benar' => 'Kepala banteng',",
        'replace' => "'pertanyaan' => 'Lembaga yang menyelenggarakan pemilu di Indonesia adalah...',
            'pilihan_a' => 'MPR', 'pilihan_b' => 'DPR', 'pilihan_c' => 'KPU', 'pilihan_d' => 'BPK', 'pilihan_e' => 'MK',
            'jawaban_benar' => 'KPU',"
    ],
    // Question 12: B -> 1965
    [
        'search' => "'pertanyaan' => 'Peristiwa G30S/PKI terjadi pada tahun...',
            'pilihan_a' => '1963', 'pilihan_b' => '1965', 'pilihan_c' => '1967', 'pilihan_d' => '1969', 'pilihan_e' => '1971',
            'jawaban_benar' => '20 Mei',",
        'replace' => "'pertanyaan' => 'Peristiwa G30S/PKI terjadi pada tahun...',
            'pilihan_a' => '1963', 'pilihan_b' => '1965', 'pilihan_c' => '1967', 'pilihan_d' => '1969', 'pilihan_e' => '1971',
            'jawaban_benar' => '1965',"
    ],
    // Question 13: C -> Pancasila Sila Ketiga
    [
        'search' => "'pertanyaan' => 'Wawasan Nusantara merupakan wujud dari...',
            'pilihan_a' => 'Pancasila Sila Pertama', 'pilihan_b' => 'Pancasila Sila Kedua', 'pilihan_c' => 'Pancasila Sila Ketiga', 'pilihan_d' => 'Pancasila Sila Keempat', 'pilihan_e' => 'Pancasila Sila Kelima',
            'jawaban_benar' => 'Kepala banteng',",
        'replace' => "'pertanyaan' => 'Wawasan Nusantara merupakan wujud dari...',
            'pilihan_a' => 'Pancasila Sila Pertama', 'pilihan_b' => 'Pancasila Sila Kedua', 'pilihan_c' => 'Pancasila Sila Ketiga', 'pilihan_d' => 'Pancasila Sila Keempat', 'pilihan_e' => 'Pancasila Sila Kelima',
            'jawaban_benar' => 'Pancasila Sila Ketiga',"
    ],
    // Question 14: C -> Aisyiyah
    [
        'search' => "'pertanyaan' => 'Organisasi perempuan pertama di Indonesia yang bergerak di bidang pendidikan adalah...',
            'pilihan_a' => 'Gerwani', 'pilihan_b' => 'Perwari', 'pilihan_c' => 'Aisyiyah', 'pilihan_d' => 'Dharma Wanita', 'pilihan_e' => 'PKK',
            'jawaban_benar' => 'Kepala banteng',",
        'replace' => "'pertanyaan' => 'Organisasi perempuan pertama di Indonesia yang bergerak di bidang pendidikan adalah...',
            'pilihan_a' => 'Gerwani', 'pilihan_b' => 'Perwari', 'pilihan_c' => 'Aisyiyah', 'pilihan_d' => 'Dharma Wanita', 'pilihan_e' => 'PKK',
            'jawaban_benar' => 'Aisyiyah',"
    ],
    // Question 15: D -> Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan
    [
        'search' => "'pertanyaan' => 'Sila keempat Pancasila mengandung pengertian...',
            'pilihan_a' => 'Ketuhanan Yang Maha Esa', 'pilihan_b' => 'Kemanusiaan yang adil dan beradab', 'pilihan_c' => 'Persatuan Indonesia', 'pilihan_d' => 'Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan', 'pilihan_e' => 'Keadilan sosial bagi seluruh rakyat Indonesia',
            'jawaban_benar' => 'Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan',",
        'replace' => "'pertanyaan' => 'Sila keempat Pancasila mengandung pengertian...',
            'pilihan_a' => 'Ketuhanan Yang Maha Esa', 'pilihan_b' => 'Kemanusiaan yang adil dan beradab', 'pilihan_c' => 'Persatuan Indonesia', 'pilihan_d' => 'Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan', 'pilihan_e' => 'Keadilan sosial bagi seluruh rakyat Indonesia',
            'jawaban_benar' => 'Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan',"
    ],
    // Question 16: D -> Berdaulat di bidang militer
    [
        'search' => "'pertanyaan' => 'Konsepsi Trisakti yang dikemukakan Presiden Soekarno meliputi kecuali...',
            'pilihan_a' => 'Berdaulat di bidang politik', 'pilihan_b' => 'Berdaulat di bidang ekonomi', 'pilihan_c' => 'Berdaulat di bidang kebudayaan', 'pilihan_d' => 'Berdaulat di bidang militer', 'pilihan_e' => 'Berdiri di atas kaki sendiri',
            'jawaban_benar' => 'Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan',",
        'replace' => "'pertanyaan' => 'Konsepsi Trisakti yang dikemukakan Presiden Soekarno meliputi kecuali...',
            'pilihan_a' => 'Berdaulat di bidang politik', 'pilihan_b' => 'Berdaulat di bidang ekonomi', 'pilihan_c' => 'Berdaulat di bidang kebudayaan', 'pilihan_d' => 'Berdaulat di bidang militer', 'pilihan_e' => 'Berdiri di atas kaki sendiri',
            'jawaban_benar' => 'Berdaulat di bidang militer',"
    ],
    // Question 17: C -> 1967
    [
        'search' => "'pertanyaan' => 'Pemerintahan Orde Baru dimulai pada tahun...',
            'pilihan_a' => '1965', 'pilihan_b' => '1966', 'pilihan_c' => '1967', 'pilihan_d' => '1968', 'pilihan_e' => '1969',
            'jawaban_benar' => 'Kepala banteng',",
        'replace' => "'pertanyaan' => 'Pemerintahan Orde Baru dimulai pada tahun...',
            'pilihan_a' => '1965', 'pilihan_b' => '1966', 'pilihan_c' => '1967', 'pilihan_d' => '1968', 'pilihan_e' => '1969',
            'jawaban_benar' => '1967',"
    ],
    // Question 18: B -> Meningkatkan pemerataan penduduk dan pembangunan antarwilayah
    [
        'search' => "'pertanyaan' => 'Kebijakan transmigrasi pada masa Orde Baru bertujuan untuk...',
            'pilihan_a' => 'Mengurangi jumlah penduduk di pulau Jawa', 'pilihan_b' => 'Meningkatkan pemerataan penduduk dan pembangunan antarwilayah', 'pilihan_c' => 'Mengalihkan kekayaan alam ke pulau Jawa', 'pilihan_d' => 'Memindahkan etnis tertentu ke daerah tertentu', 'pilihan_e' => 'Mengurangi konflik agraria',
            'jawaban_benar' => '20 Mei',",
        'replace' => "'pertanyaan' => 'Kebijakan transmigrasi pada masa Orde Baru bertujuan untuk...',
            'pilihan_a' => 'Mengurangi jumlah penduduk di pulau Jawa', 'pilihan_b' => 'Meningkatkan pemerataan penduduk dan pembangunan antarwilayah', 'pilihan_c' => 'Mengalihkan kekayaan alam ke pulau Jawa', 'pilihan_d' => 'Memindahkan etnis tertentu ke daerah tertentu', 'pilihan_e' => 'Mengurangi konflik agraria',
            'jawaban_benar' => 'Meningkatkan pemerataan penduduk dan pembangunan antarwilayah',"
    ],
    // Question 19: C -> 29 April 1945
    [
        'search' => "'pertanyaan' => 'BPUPKI dibentuk pada tanggal...',
            'pilihan_a' => '17 Agustus 1945', 'pilihan_b' => '10 Juli 1945', 'pilihan_c' => '29 April 1945', 'pilihan_d' => '1 Maret 1945', 'pilihan_e' => '18 Agustus 1945',
            'jawaban_benar' => 'Kepala banteng',",
        'replace' => "'pertanyaan' => 'BPUPKI dibentuk pada tanggal...',
            'pilihan_a' => '17 Agustus 1945', 'pilihan_b' => '10 Juli 1945', 'pilihan_c' => '29 April 1945', 'pilihan_d' => '1 Maret 1945', 'pilihan_e' => '18 Agustus 1945',
            'jawaban_benar' => '29 April 1945',"
    ],
    // Question 20: D -> Sayuti Melik
    [
        'search' => "'pertanyaan' => 'Naskah proklamasi kemerdekaan Indonesia ditulis oleh...',
            'pilihan_a' => 'Ir. Soekarno', 'pilihan_b' => 'Mohammad Hatta', 'pilihan_c' => 'Achmad Soebardjo', 'pilihan_d' => 'Sayuti Melik', 'pilihan_e' => 'Sukarni',
            'jawaban_benar' => 'Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan',",
        'replace' => "'pertanyaan' => 'Naskah proklamasi kemerdekaan Indonesia ditulis oleh...',
            'pilihan_a' => 'Ir. Soekarno', 'pilihan_b' => 'Mohammad Hatta', 'pilihan_c' => 'Achmad Soebardjo', 'pilihan_d' => 'Sayuti Melik', 'pilihan_e' => 'Sukarni',
            'jawaban_benar' => 'Sayuti Melik',"
    ],
    // Pancasila questions
    [
        'search' => "'pertanyaan' => 'Silah pertama Pancasila adalah...',
            'pilihan_a' => 'Ketuhanan Yang Maha Esa',
            'pilihan_b' => 'Kemanusiaan yang adil dan beradab',
            'pilihan_c' => 'Persatuan Indonesia',
            'pilihan_d' => 'Kerakyatan',
            'pilihan_e' => 'Keadilan sosial',
            'jawaban_benar' => 'Menggunakan produk dalam negeri dan mendukung UMKM',",
        'replace' => "'pertanyaan' => 'Silah pertama Pancasila adalah...',
            'pilihan_a' => 'Ketuhanan Yang Maha Esa',
            'pilihan_b' => 'Kemanusiaan yang adil dan beradab',
            'pilihan_c' => 'Persatuan Indonesia',
            'pilihan_d' => 'Kerakyatan',
            'pilihan_e' => 'Keadilan sosial',
            'jawaban_benar' => 'Ketuhanan Yang Maha Esa',"
    ],
    [
        'search' => "'pertanyaan' => 'UUD 1945 telah diamandemen sebanyak... kali.',
            'pilihan_a' => '2', 'pilihan_b' => '3', 'pilihan_c' => '4', 'pilihan_d' => '5', 'pilihan_e' => '6',
            'jawaban_benar' => 'Kepala banteng',",
        'replace' => "'pertanyaan' => 'UUD 1945 telah diamandemen sebanyak... kali.',
            'pilihan_a' => '2', 'pilihan_b' => '3', 'pilihan_c' => '4', 'pilihan_d' => '5', 'pilihan_e' => '6',
            'jawaban_benar' => '4',"
    ],
    [
        'search' => "'pertanyaan' => 'Lambang silah keempat Pancasila adalah...',
            'pilihan_a' => 'Bintang', 'pilihan_b' => 'Pohon beringin', 'pilihan_c' => 'Kepala banteng', 'pilihan_d' => 'Padi dan kapas', 'pilihan_e' => 'Rantai',
            'jawaban_benar' => '20 Mei',",
        'replace' => "'pertanyaan' => 'Lambang silah keempat Pancasila adalah...',
            'pilihan_a' => 'Bintang', 'pilihan_b' => 'Pohon beringin', 'pilihan_c' => 'Kepala banteng', 'pilihan_d' => 'Padi dan kapas', 'pilihan_e' => 'Rantai',
            'jawaban_benar' => 'Pohon beringin',"
    ],
    [
        'search' => "'pertanyaan' => 'Pasal 33 UUD 1945 mengatur tentang...',
            'pilihan_a' => 'Pendidikan', 'pilihan_b' => 'Kesehatan', 'pilihan_c' => 'Ekonomi & keuangan negara', 'pilihan_d' => 'Pertahanan', 'pilihan_e' => 'Kehutanan',
            'jawaban_benar' => 'Kepala banteng',",
        'replace' => "'pertanyaan' => 'Pasal 33 UUD 1945 mengatur tentang...',
            'pilihan_a' => 'Pendidikan', 'pilihan_b' => 'Kesehatan', 'pilihan_c' => 'Ekonomi & keuangan negara', 'pilihan_d' => 'Pertahanan', 'pilihan_e' => 'Kehutanan',
            'jawaban_benar' => 'Ekonomi & keuangan negara',"
    ],
];

foreach ($fixes as $fix) {
    $content = str_replace($fix['search'], $fix['replace'], $content);
}

file_put_contents($file, $content);
echo "TWK generator fixed with robust method!\n";
