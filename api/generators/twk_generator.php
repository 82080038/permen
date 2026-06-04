<?php
/**
 * TWK (Tes Wawasan Kebangsaan) Question Generator
 * 
 * Generates TWK questions including:
 * - Nasionalisme (Nationalism)
 * - Pancasila / Pilar Negara (Pancasila / State Pillars)
 */

require_once __DIR__ . '/helpers.php';

/**
 * Generate TWK nationalism question
 * @return array Question data with options and explanation
 */
function generateTWK_Nasionalisme(): array {
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

/**
 * Generate TWK Pancasila/pilar negara question
 * @return array Question data with options and explanation
 */
function generateTWK(): array {
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
