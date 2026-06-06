<?php
/**
 * TKP (Tes Karakteristik Pribadi) Question Generator
 * 
 * Generates TKP questions including:
 * - Pelayanan Publik
 * - Jejaring Kerja
 * - Sosial Budaya
 * - Teknologi Informasi
 * - Profesionalisme
 * - Kepribadian
 */

require_once __DIR__ . '/helpers.php';

/**
 * Generate TKP question for Pelayanan Publik
 * @return array Question data with options and scores
 */
function generateTKP_PelayananPublik(): array {
    $scenarios = [
        [
            'pertanyaan' => 'Seorang warga datang ke kantor Anda dengan dokumen tidak lengkap. Tindakan yang paling tepat adalah...',
            'pilihan_a' => 'Menolak melayani dan menyuruh pulang',
            'pilihan_b' => 'Melayani seadanya dengan kesal',
            'pilihan_c' => 'Menjelaskan kekurangan dokumen dan membantu melengkapi',
            'pilihan_d' => 'Menyuruh datang besok saja',
            'pilihan_e' => 'Mengabaikan dan melayani orang lain',
            'jawaban_benar' => 'Menjelaskan kekurangan dokumen dan membantu melengkapi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan sikap pelayanan prima: menjelaskan dengan sabar dan membantu warga melengkapi dokumen. Ini sesuai prinsip pelayanan publik yang ramah dan membantu.'
        ],
        [
            'pertanyaan' => 'Ada lansia yang kesulitan mengisi formulir digital. Tindakan Anda adalah...',
            'pilihan_a' => 'Menyuruhnya pulang dan minta bantuan anak',
            'pilihan_b' => 'Mengisi sendiri tanpa penjelasan',
            'pilihan_c' => 'Membimbing mengisi dengan sabar dan langkah demi langkah',
            'pilihan_d' => 'Menyuruh antre di belakang',
            'pilihan_e' => 'Mengatakan sistem sedang error',
            'jawaban_benar' => 'Membimbing mengisi dengan sabar dan langkah demi langkah',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan empati dan kesabaran dalam melayani kelompok rentan (lansia). Ini nilai tertinggi dalam pelayanan publik.'
        ],
        [
            'pertanyaan' => 'Warga mengeluh pelayanan lambat. Respon Anda adalah...',
            'pilihan_a' => 'Marah dan membalas',
            'pilihan_b' => 'Diam saja',
            'pilihan_c' => 'Meminta maaf dan menjelaskan penyebab keterlambatan',
            'pilihan_d' => 'Menyuruhnya ke kantor lain',
            'pilihan_e' => 'Mengabaikan keluhan',
            'jawaban_benar' => 'Meminta maaf dan menjelaskan penyebab keterlambatan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan sikap menerima masukan dan transparansi. Ini nilai tinggi dalam pelayanan publik.'
        ],
        [
            'pertanyaan' => 'Warga difabel membutuhkan akses khusus. Tindakan Anda adalah...',
            'pilihan_a' => 'Menyuruhnya datang dengan pendamping',
            'pilihan_b' => 'Melayani seadanya',
            'pilihan_c' => 'Menyediakan fasilitas akses dan membantu prosedur',
            'pilihan_d' => 'Menunda pelayanan',
            'pilihan_e' => 'Mengarahkan ke kantor lain',
            'jawaban_benar' => 'Menyediakan fasilitas akses dan membantu prosedur',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan inklusivitas dan perhatian terhadap kelompok difabel. Ini nilai tertinggi dalam pelayanan publik.'
        ],
        [
            'pertanyaan' => 'Ada warga yang marah karena permohonannya ditolak. Respon Anda adalah...',
            'pilihan_a' => 'Memanggil satpam',
            'pilihan_b' => 'Menolak melayani lebih lanjut',
            'pilihan_c' => 'Menjelaskan alasan penolakan dengan tenang dan sopan',
            'pilihan_d' => 'Menghindar',
            'pilihan_e' => 'Menyuruhnya mengadu ke atasan',
            'jawaban_benar' => 'Menjelaskan alasan penolakan dengan tenang dan sopan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan profesionalisme dan kemampuan komunikasi dalam situasi sulit. Ini nilai tertinggi dalam pelayanan publik.'
        ],
        [
            'pertanyaan' => 'Warga meminta bantuan di luar jam kerja. Sikap Anda adalah...',
            'pilihan_a' => 'Menolak tegas',
            'pilihan_b' => 'Mengabaikan',
            'pilihan_c' => 'Menjelaskan jam kerja dan memberikan alternatif',
            'pilihan_d' => 'Melayani tapi dengan wajah kesal',
            'pilihan_e' => 'Membalas dengan sinis',
            'jawaban_benar' => 'Menjelaskan jam kerja dan memberikan alternatif',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan sikap profesional dan solutif. Ini nilai tertinggi dalam pelayanan publik.'
        ],
        [
            'pertanyaan' => 'Ada warga yang tidak bisa bahasa Indonesia. Tindakan Anda adalah...',
            'pilihan_a' => 'Menyuruhnya bawa penerjemah',
            'pilihan_b' => 'Menolak melayani',
            'pilihan_c' => 'Mencari cara komunikasi alternatif (isyarat, tulisan, bantuan)',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Menyuruh ke kantor lain',
            'jawaban_benar' => 'Mencari cara komunikasi alternatif (isyarat, tulisan, bantuan)',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan adaptabilitas dan komitmen pelayanan. Ini nilai tertinggi dalam pelayanan publik.'
        ],
        [
            'pertanyaan' => 'Warga mengeluh biaya layanan terlalu mahal. Respon Anda adalah...',
            'pilihan_a' => 'Menyuruhnya tidak usah pakai',
            'pilihan_b' => 'Mengatakan itu aturan',
            'pilihan_c' => 'Menjelaskan rincian biaya dan program subsidi jika ada',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Menyuruh komplain ke atasan',
            'jawaban_benar' => 'Menjelaskan rincian biaya dan program subsidi jika ada',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan transparansi dan edukasi. Ini nilai tertinggi dalam pelayanan publik.'
        ],
        [
            'pertanyaan' => 'Ada warga yang butuh layanan mendesak di luar loket. Tindakan Anda adalah...',
            'pilihan_a' => 'Menyuruh antre seperti biasa',
            'pilihan_b' => 'Menolak',
            'pilihan_c' => 'Menilai urgensi dan memberikan prioritas jika memang darurat',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Menyuruh ke rumah sakit',
            'jawaban_benar' => 'Menilai urgensi dan memberikan prioritas jika memang darurat',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan penilaian situasi dan prioritas yang tepat. Ini nilai tertinggi dalam pelayanan publik.'
        ],
        [
            'pertanyaan' => 'Warga meminta pengurusan yang tidak sesuai prosedur. Sikap Anda adalah...',
            'pilihan_a' => 'Mengikuti permintaan',
            'pilihan_b' => 'Menolak kasar',
            'pilihan_c' => 'Menjelaskan prosedur yang benar dan membantu sesuai aturan',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Menerima suap',
            'jawaban_benar' => 'Menjelaskan prosedur yang benar dan membantu sesuai aturan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan integritas dan komitmen pada prosedur. Ini nilai tertinggi dalam pelayanan publik.'
        ]
    ];
    
    return $scenarios[array_rand($scenarios)];
}

/**
 * Generate TKP question for Jejaring Kerja
 * @return array Question data with options and scores
 */
function generateTKP_JejaringKerja(): array {
    $scenarios = [
        [
            'pertanyaan' => 'Rekan kerja tidak menyelesaikan tugas tepat waktu. Tindakan Anda adalah...',
            'pilihan_a' => 'Melaporkan ke atasan tanpa konfirmasi',
            'pilihan_b' => 'Membantu menyelesaikan tanpa bicara',
            'pilihan_c' => 'Menghubungi dan menawarkan bantuan, tanyakan kendala',
            'pilihan_d' => 'Mengeluh di grup kerja',
            'pilihan_e' => 'Mengabaikan dan fokus tugas sendiri',
            'jawaban_benar' => 'Menghubungi dan menawarkan bantuan, tanyakan kendala',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan komunikasi terbuka dan kerja sama konstruktif. Ini nilai tertinggi dalam jejaring kerja.'
        ],
        [
            'pertanyaan' => 'Ada konflik antar divisi dalam proyek. Peran Anda adalah...',
            'pilihan_a' => 'Memihak divisi sendiri',
            'pilihan_b' => 'Diam dan tidak ikut campur',
            'pilihan_c' => 'Memfasilitasi diskusi untuk cari solusi bersama',
            'pilihan_d' => 'Menyalahkan divisi lain',
            'pilihan_e' => 'Mengundurkan diri dari proyek',
            'jawaban_benar' => 'Memfasilitasi diskusi untuk cari solusi bersama',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan kemampuan resolusi konflik dan kolaborasi. Ini nilai tertinggi dalam jejaring kerja.'
        ],
        [
            'pertanyaan' => 'Tim Anda mendapat tugas baru di luar job desk. Sikap Anda adalah...',
            'pilihan_a' => 'Menolak tegas',
            'pilihan_b' => 'Mengeluh tapi tetap mengerjakan',
            'pilihan_c' => 'Menerima dengan antusias dan berkoordinasi dengan tim',
            'pilihan_d' => 'Mencari alasan untuk tidak mengerjakan',
            'pilihan_e' => 'Minta gaji dinaikkan dulu',
            'jawaban_benar' => 'Menerima dengan antusias dan berkoordinasi dengan tim',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan fleksibilitas dan sikap positif. Ini nilai tertinggi dalam profesionalisme dan jejaring kerja.'
        ],
        [
            'pertanyaan' => 'Rekan kerja baru kesulitan beradaptasi. Tindakan Anda adalah...',
            'pilihan_a' => 'Mengabaikan',
            'pilihan_b' => 'Menertawakan kesalahan',
            'pilihan_c' => 'Membantu dan membimbing dengan sabar',
            'pilihan_d' => 'Melaporkan ke atasan',
            'pilihan_e' => 'Menjauh',
            'jawaban_benar' => 'Membantu dan membimbing dengan sabar',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan sikap mentoring dan kolaborasi. Ini nilai tertinggi dalam jejaring kerja.'
        ],
        [
            'pertanyaan' => 'Ada rekan yang sering datang terlambat. Sikap Anda adalah...',
            'pilihan_a' => 'Melaporkan langsung',
            'pilihan_b' => 'Mengeluh di belakang',
            'pilihan_c' => 'Menanyakan masalah dan menawarkan solusi',
            'pilihan_d' => 'Meniru perilakunya',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Menanyakan masalah dan menawarkan solusi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan empati dan pendekatan konstruktif. Ini nilai tertinggi dalam jejaring kerja.'
        ],
        [
            'pertanyaan' => 'Tim Anda harus bekerja lembur untuk deadline. Sikap Anda adalah...',
            'pilihan_a' => 'Menolak lembur',
            'pilihan_b' => 'Lembur tapi mengeluh',
            'pilihan_c' => 'Bekerja sama dan berkoordinasi pembagian tugas',
            'pilihan_d' => 'Pulang duluan',
            'pilihan_e' => 'Mencari alasan pulang',
            'jawaban_benar' => 'Bekerja sama dan berkoordinasi pembagian tugas',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan komitmen tim dan kerja sama. Ini nilai tertinggi dalam jejaring kerja.'
        ],
        [
            'pertanyaan' => 'Rekan kerja mengambil kredit ide Anda. Tindakan Anda adalah...',
            'pilihan_a' => 'Marah dan menyerang',
            'pilihan_b' => 'Diam dan tersinggung',
            'pilihan_c' => 'Mengklarifikasi dengan profesional dan mencari solusi',
            'pilihan_d' => 'Membalas dengan cara sama',
            'pilihan_e' => 'Mengeluh ke semua orang',
            'jawaban_benar' => 'Mengklarifikasi dengan profesional dan mencari solusi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan profesionalisme dan komunikasi asertif. Ini nilai tertinggi dalam jejaring kerja.'
        ],
        [
            'pertanyaan' => 'Ada proyek kolaborasi dengan instansi lain. Peran Anda adalah...',
            'pilihan_a' => 'Menolak kerja sama',
            'pilihan_b' => 'Pasif dalam kolaborasi',
            'pilihan_c' => 'Aktif berkontribusi dan membangun relasi',
            'pilihan_d' => 'Mengkritik instansi lain',
            'pilihan_e' => 'Menghindari pertemuan',
            'jawaban_benar' => 'Aktif berkontribusi dan membangun relasi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan kemampuan networking dan kolaborasi. Ini nilai tertinggi dalam jejaring kerja.'
        ],
        [
            'pertanyaan' => 'Rekan kerja mengalami masalah pribadi yang mempengaruhi kinerja. Sikap Anda adalah...',
            'pilihan_a' => 'Melaporkan ke atasan',
            'pilihan_b' => 'Mengabaikan',
            'pilihan_c' => 'Menawarkan dukungan dan fleksibilitas jika mungkin',
            'pilihan_d' => 'Mengeluh',
            'pilihan_e' => 'Menjauh',
            'jawaban_benar' => 'Menawarkan dukungan dan fleksibilitas jika mungkin',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan empati dan dukungan tim. Ini nilai tertinggi dalam jejaring kerja.'
        ],
        [
            'pertanyaan' => 'Ada perubahan struktur organisasi. Sikap Anda adalah...',
            'pilihan_a' => 'Menolak perubahan',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Beradaptasi dan membantu tim beradaptasi',
            'pilihan_d' => 'Mencari alasan pindah',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Beradaptasi dan membantu tim beradaptasi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan adaptabilitas dan kepemimpinan. Ini nilai tertinggi dalam jejaring kerja.'
        ]
    ];
    
    return $scenarios[array_rand($scenarios)];
}

/**
 * Generate TKP question for Sosial Budaya
 * @return array Question data with options and scores
 */
function generateTKP_SosialBudaya(): array {
    $scenarios = [
        [
            'pertanyaan' => 'Anda ditugaskan ke daerah dengan budaya berbeda. Sikap Anda adalah...',
            'pilihan_a' => 'Mengkritik kebiasaan lokal',
            'pilihan_b' => 'Menolak tinggal di rumah warga',
            'pilihan_c' => 'Mempelajari dan menghargai budaya setempat',
            'pilihan_d' => 'Minta pindah ke daerah lain',
            'pilihan_e' => 'Menertawakan logat bahasa',
            'jawaban_benar' => 'Mempelajari dan menghargai budaya setempat',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan adaptabilitas dan penghargaan keberagaman. Ini nilai tertinggi dalam sosial budaya.'
        ],
        [
            'pertanyaan' => 'Rekan kerja dari suku berbeda diperlakukan tidak adil. Tindakan Anda adalah...',
            'pilihan_a' => 'Ikut menertawakan',
            'pilihan_b' => 'Diam saja',
            'pilihan_c' => 'Membela dengan cara konstruktif dan melaporkan jika perlu',
            'pilihan_d' => 'Menyuruhnya sabar saja',
            'pilihan_e' => 'Menjauh dari rekan tersebut',
            'jawaban_benar' => 'Membela dengan cara konstruktif dan melaporkan jika perlu',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan sikap anti-diskriminasi dan keberanian membela kebenaran. Ini nilai tertinggi dalam sosial budaya.'
        ],
        [
            'pertanyaan' => 'Ada acara keagamaan rekan kerja yang berbeda dengan agama Anda. Sikap Anda adalah...',
            'pilihan_a' => 'Menolak diundang',
            'pilihan_b' => 'Datang tapi mengkritik ritual',
            'pilihan_c' => 'Menghargai dan mengucapkan selamat',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Menyebarkan keberatan di grup',
            'jawaban_benar' => 'Menghargai dan mengucapkan selamat',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan toleransi dan penghargaan perbedaan. Ini nilai tertinggi dalam sosial budaya.'
        ],
        [
            'pertanyaan' => 'Rekan kerja dari daerah lain memiliki logat berbeda. Sikap Anda adalah...',
            'pilihan_a' => 'Menertawakan',
            'pilihan_b' => 'Menyuruh belajar bahasa baku',
            'pilihan_c' => 'Menghargai dan beradaptasi dengan komunikasi',
            'pilihan_d' => 'Mengeluh',
            'pilihan_e' => 'Menyebarkan keberatan',
            'jawaban_benar' => 'Menghargai dan beradaptasi dengan komunikasi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan toleransi dan adaptabilitas. Ini nilai tertinggi dalam sosial budaya.'
        ],
        [
            'pertanyaan' => 'Ada kebijakan yang dianggap diskriminatif oleh sebagian warga. Sikap Anda adalah...',
            'pilihan_a' => 'Mendukung kebijakan tanpa pertanyaan',
            'pilihan_b' => 'Menolak kebijakan secara terbuka',
            'pilihan_c' => 'Mencari informasi dan dialog untuk solusi',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Menyebarkan keberatan',
            'jawaban_benar' => 'Mencari informasi dan dialog untuk solusi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan toleransi dan integritas. Ini nilai tertinggi dalam sosial budaya.'
        ],
        [
            'pertanyaan' => 'Rekan kerja baru dari minoritas merasa tidak diterima. Tindakan Anda adalah...',
            'pilihan_a' => 'Menyuruh beradaptasi sendiri',
            'pilihan_b' => 'Melaporkan',
            'pilihan_c' => 'Membantu integrasi dan memfasilitasi perkenalan',
            'pilihan_d' => 'Mengeluh',
            'pilihan_e' => 'Menjauh',
            'jawaban_benar' => 'Membantu integrasi dan memfasilitasi perkenalan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan inklusivitas dan dukungan. Ini nilai tertinggi dalam sosial budaya.'
        ],
        [
            'pertanyaan' => 'Ada perbedaan pendapat tentang praktik budaya. Sikap Anda adalah...',
            'pilihan_a' => 'Memaksakan pandangan sendiri',
            'pilihan_b' => 'Menghindari diskusi',
            'pilihan_c' => 'Mendengarkan dan menghargai perspektif berbeda',
            'pilihan_d' => 'Mengkritik',
            'pilihan_e' => 'Menolak',
            'jawaban_benar' => 'Mendengarkan dan menghargai perspektif berbeda',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan toleransi dan kemampuan dialog. Ini nilai tertinggi dalam sosial budaya.'
        ],
        [
            'pertanyaan' => 'Anda diminta mengadakan acara yang melibatkan berbagai etnis. Tindakan Anda adalah...',
            'pilihan_a' => 'Fokus pada etnis mayoritas saja',
            'pilihan_b' => 'Mengabaikan',
            'pilihan_c' => 'Menyertakan semua etnis dan menghargai keberagaman',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Menolak',
            'jawaban_benar' => 'Menyertakan semua etnis dan menghargai keberagaman',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan keinginan belajar dan penghargaan budaya. Ini nilai tertinggi dalam sosial budaya.'
        ],
        [
            'pertanyaan' => 'Rekan kerja mengirim meme yang mengandung stereotip. Sikap Anda adalah...',
            'pilihan_a' => 'Tertawa dan forward',
            'pilihan_b' => 'Mengabaikan',
            'pilihan_c' => 'Menyampaikan keberatan dengan sopan',
            'pilihan_d' => 'Mengkritik',
            'pilihan_e' => 'Menolak',
            'jawaban_benar' => 'Menyampaikan keberatan dengan sopan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan sikap anti-stereotip dan edukasi. Ini nilai tertinggi dalam sosial budaya.'
        ],
        [
            'pertanyaan' => 'Ada tradisi lokal yang bertentangan dengan aturan kerja. Sikap Anda adalah...',
            'pilihan_a' => 'Mengikuti tradisi',
            'pilihan_b' => 'Melarang tradisi',
            'pilihan_c' => 'Mencari kompromi yang menghargai tradisi dan aturan',
            'pilihan_d' => 'Mengkritik',
            'pilihan_e' => 'Menolak',
            'jawaban_benar' => 'Mencari kompromi yang menghargai tradisi dan aturan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan penghargaan budaya dan adaptabilitas. Ini nilai tertinggi dalam sosial budaya.'
        ]
    ];
    
    return $scenarios[array_rand($scenarios)];
}

/**
 * Generate TKP question for Teknologi Informasi
 * @return array Question data with options and scores
 */
function generateTKP_TeknologiInformasi(): array {
    $scenarios = [
        [
            'pertanyaan' => 'Sistem baru diterapkan di kantor. Sikap Anda adalah...',
            'pilihan_a' => 'Menolak dan tetap pakai sistem lama',
            'pilihan_b' => 'Mengeluh aplikasi rumit',
            'pilihan_c' => 'Belajar dan beradaptasi dengan sistem baru',
            'pilihan_d' => 'Ikut menanggapi emosional',
            'pilihan_e' => 'Memblokir pengirim',
            'jawaban_benar' => 'Belajar dan beradaptasi dengan sistem baru',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan adaptabilitas teknologi dan inisiatif belajar. Ini nilai tertinggi dalam TIK.'
        ],
        [
            'pertanyaan' => 'Ada email mencurigakan dengan lampiran. Tindakan Anda adalah...',
            'pilihan_a' => 'Membuka lampiran',
            'pilihan_b' => 'Forward ke rekan',
            'pilihan_c' => 'Verifikasi pengirim dan tidak membuka jika mencurigakan',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Menerima imbalan',
            'jawaban_benar' => 'Verifikasi pengirim dan tidak membuka jika mencurigakan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan literasi digital dan kehati-hatian. Ini nilai tertinggi dalam TIK.'
        ],
        [
            'pertanyaan' => 'Server down saat jam sibuk. Tindakan Anda adalah...',
            'pilihan_a' => 'Pulang karena tidak bisa kerja',
            'pilihan_b' => 'Menyalahkan pihak IT',
            'pilihan_c' => 'Mencari alternatif kerja manual sambil menunggu',
            'pilihan_d' => 'Mengeluh waktu terbuang',
            'pilihan_e' => 'Mencari alasan absen',
            'jawaban_benar' => 'Mencari alternatif kerja manual sambil menunggu',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan problem-solving dan sikap konstruktif. Ini nilai tertinggi dalam TIK.'
        ],
        [
            'pertanyaan' => 'Ada training software baru. Sikap Anda adalah...',
            'pilihan_a' => 'Mengeluh waktu terbuang',
            'pilihan_b' => 'Mencari alasan absen',
            'pilihan_c' => 'Mengikuti training dengan antusias',
            'pilihan_d' => 'Menyuruh tanya IT',
            'pilihan_e' => 'Mengeluh',
            'jawaban_benar' => 'Mengikuti training dengan antusias',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan inisiatif pembelajaran dan adaptabilitas. Ini nilai tertinggi dalam TIK.'
        ],
        [
            'pertanyaan' => 'Rekan kesulitan menggunakan software. Tindakan Anda adalah...',
            'pilihan_a' => 'Menyuruh tanya IT',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Membantu dan berbagi pengetahuan',
            'pilihan_d' => 'Share via media sosial',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Membantu dan berbagi pengetahuan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan kolaborasi dan berbagi pengetahuan. Ini nilai tertinggi dalam TIK.'
        ],
        [
            'pertanyaan' => 'Ada data sensitif perlu dikirim. Tindakan Anda adalah...',
            'pilihan_a' => 'Kirim via email biasa',
            'pilihan_b' => 'Share via media sosial',
            'pilihan_c' => 'Gunakan metode aman sesuai SOP',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Menerima imbalan',
            'jawaban_benar' => 'Gunakan metode aman sesuai SOP',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan keamanan data dan kepatuhan SOP. Ini nilai tertinggi dalam TIK.'
        ],
        [
            'pertanyaan' => 'Password policy diperketat. Sikap Anda adalah...',
            'pilihan_a' => 'Mengeluh',
            'pilihan_b' => 'Menerima imbalan',
            'pilihan_c' => 'Mengikuti dan mengamankan akun',
            'pilihan_d' => 'Mencari cara bypass',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Mengikuti dan mengamankan akun',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan integritas dan kepatuhan aturan. Ini nilai tertinggi dalam TIK.'
        ],
        [
            'pertanyaan' => 'Ada phishing terdeteksi. Tindakan Anda adalah...',
            'pilihan_a' => 'Mengabaikan',
            'pilihan_b' => 'Forward ke rekan',
            'pilihan_c' => 'Lapor ke IT dan beri peringatan',
            'pilihan_d' => 'Mengeluh',
            'pilihan_e' => 'Mencari alasan',
            'jawaban_benar' => 'Lapor ke IT dan beri peringatan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan kesadaran keamanan dan tindakan tepat. Ini nilai tertinggi dalam TIK.'
        ],
        [
            'pertanyaan' => 'Digitalisasi dokumen diminta. Sikap Anda adalah...',
            'pilihan_a' => 'Mengeluh',
            'pilihan_b' => 'Mencari alasan',
            'pilihan_c' => 'Mendukung dan membantu proses digitalisasi',
            'pilihan_d' => 'Menolak',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Mendukung dan membantu proses digitalisasi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan dukungan inovasi dan kolaborasi. Ini nilai tertinggi dalam TIK.'
        ],
        [
            'pertanyaan' => 'Ada update sistem yang mengubah workflow. Sikap Anda adalah...',
            'pilihan_a' => 'Menolak update',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Belajar dan beradaptasi dengan workflow baru',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Belajar dan beradaptasi dengan workflow baru',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan adaptabilitas dan sikap positif. Ini nilai tertinggi dalam TIK.'
        ]
    ];
    
    return $scenarios[array_rand($scenarios)];
}

/**
 * Generate TKP question for Profesionalisme
 * @return array Question data with options and scores
 */
function generateTKP_Profesionalisme(): array {
    $scenarios = [
        [
            'pertanyaan' => 'Atasan memberi kritik keras atas kinerja Anda. Respon Anda adalah...',
            'pilihan_a' => 'Membela diri dengan marah',
            'pilihan_b' => 'Diam dan tersinggung',
            'pilihan_c' => 'Menerima masukan dan bertanya cara perbaikan',
            'pilihan_d' => 'Mengeluh ke rekan',
            'pilihan_e' => 'Mencari alasan',
            'jawaban_benar' => 'Menerima masukan dan bertanya cara perbaikan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan sikap menerima masukan dan komitmen perbaikan. Ini nilai tertinggi dalam profesionalisme.'
        ],
        [
            'pertanyaan' => 'Ada peluang kenaikan gaji tapi dengan cara tidak etis. Sikap Anda adalah...',
            'pilihan_a' => 'Mengambil peluang',
            'pilihan_b' => 'Minta nominal lebih tinggi',
            'pilihan_c' => 'Menolak dan mengikuti prosedur yang benar',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Mencari alasan',
            'jawaban_benar' => 'Menolak dan mengikuti prosedur yang benar',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan integritas dan keberanian melawan korupsi. Ini nilai tertinggi dalam profesionalisme.'
        ],
        [
            'pertanyaan' => 'Ada tugas mendesak dan tugas rutin. Prioritas Anda adalah...',
            'pilihan_a' => 'Mengerjakan yang mudah dulu',
            'pilihan_b' => 'Menolak tugas mendesak',
            'pilihan_c' => 'Mengelola waktu dan komunikasi dengan atasan',
            'pilihan_d' => 'Mengerjakan asal-asalan',
            'pilihan_e' => 'Mencari alasan',
            'jawaban_benar' => 'Mengelola waktu dan komunikasi dengan atasan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan kemampuan manajemen waktu dan komunikasi. Ini nilai tertinggi dalam profesionalisme.'
        ],
        [
            'pertanyaan' => 'Ada kesalahan dalam pekerjaan Anda. Tindakan Anda adalah...',
            'pilihan_a' => 'Menyembunyikan kesalahan',
            'pilihan_b' => 'Menyalahkan orang lain',
            'pilihan_c' => 'Mengakui dan memperbaiki dengan segera',
            'pilihan_d' => 'Mengabaikan',
            'pilihan_e' => 'Mencari alasan',
            'jawaban_benar' => 'Mengakui dan memperbaiki dengan segera',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan integritas dan tanggung jawab. Ini nilai tertinggi dalam profesionalisme.'
        ],
        [
            'pertanyaan' => 'Rekan melakukan pelanggaran etika. Tindakan Anda adalah...',
            'pilihan_a' => 'Ikut melakukan',
            'pilihan_b' => 'Diam saja',
            'pilihan_c' => 'Menegur dan melaporkan jika perlu',
            'pilihan_d' => 'Melaporkan tanpa konfirmasi',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Menegur dan melaporkan jika perlu',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan integritas dan kepemimpinan. Ini nilai tertinggi dalam profesionalisme.'
        ],
        [
            'pertanyaan' => 'Ada deadline ketat tapi kualitas harus terjaga. Sikap Anda adalah...',
            'pilihan_a' => 'Mengorbankan kualitas',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Bekerja efisien dan meminta bantuan jika perlu',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Bekerja efisien dan meminta bantuan jika perlu',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan integritas dan komitmen kinerja. Ini nilai tertinggi dalam profesionalisme.'
        ],
        [
            'pertanyaan' => 'Ada tugas di luar kompetensi. Sikap Anda adalah...',
            'pilihan_a' => 'Menolak',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Belajar dan minta bimbingan',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Belajar dan minta bimbingan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan sikap pembelajaran dan adaptabilitas. Ini nilai tertinggi dalam profesionalisme.'
        ],
        [
            'pertanyaan' => 'Ada konflik kepentingan antara pekerjaan dan pribadi. Sikap Anda adalah...',
            'pilihan_a' => 'Mengutamakan kepentingan pribadi',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Mengutamakan kepentingan pekerjaan dengan transparansi',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Mengutamakan kepentingan pekerjaan dengan transparansi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan integritas dan profesionalisme. Ini nilai tertinggi dalam profesionalisme.'
        ],
        [
            'pertanyaan' => 'Ada rekan yang tidak profesional. Sikap Anda adalah...',
            'pilihan_a' => 'Meniru perilakunya',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Menjaga profesionalisme dan memberi contoh',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Menjaga profesionalisme dan memberi contoh',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan integritas dan kepemimpinan. Ini nilai tertinggi dalam profesionalisme.'
        ],
        [
            'pertanyaan' => 'Ada peluang promosi tapi bersaing dengan rekan. Sikap Anda adalah...',
            'pilihan_a' => 'Menjatuhkan rekan',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Fokus pada kinerja dan fair competition',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Fokus pada kinerja dan fair competition',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan integritas dan profesionalisme. Ini nilai tertinggi dalam profesionalisme.'
        ]
    ];
    
    return $scenarios[array_rand($scenarios)];
}

/**
 * Generate TKP question for Kepribadian
 * @return array Question data with options and scores
 */
function generateTKP_Kepribadian(): array {
    $scenarios = [
        [
            'pertanyaan' => 'Dalam situasi tekanan, Anda cenderung...',
            'pilihan_a' => 'Panik dan tidak bisa berpikir',
            'pilihan_b' => 'Menghindar',
            'pilihan_c' => 'Tenang dan fokus mencari solusi',
            'pilihan_d' => 'Marah',
            'pilihan_e' => 'Menyalahkan orang lain',
            'jawaban_benar' => 'Tenang dan fokus mencari solusi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan stabilitas emosi dan kemampuan problem-solving. Ini nilai tertinggi dalam kepribadian.'
        ],
        [
            'pertanyaan' => 'Ada perubahan mendadak dalam rencana. Sikap Anda adalah...',
            'pilihan_a' => 'Marah dan menolak',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Fleksibel dan beradaptasi',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Fleksibel dan beradaptasi',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan adaptabilitas dan sikap positif. Ini nilai tertinggi dalam kepribadian.'
        ],
        [
            'pertanyaan' => 'Anda diberi tanggung jawab baru. Sikap Anda adalah...',
            'pilihan_a' => 'Menolak',
            'pilihan_b' => 'Mengeluh',
            'pilihan_c' => 'Menerima dengan antusias dan bertanggung jawab',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Menerima dengan antusias dan bertanggung jawab',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan inisiatif dan tanggung jawab. Ini nilai tertinggi dalam kepribadian.'
        ],
        [
            'pertanyaan' => 'Ada kritik dari rekan. Sikap Anda adalah...',
            'pilihan_a' => 'Marah',
            'pilihan_b' => 'Diam dan tersinggung',
            'pilihan_c' => 'Mendengarkan dan mengevaluasi untuk perbaikan',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Mendengarkan dan mengevaluasi untuk perbaikan',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan keterbukaan dan sikap pembelajaran. Ini nilai tertinggi dalam kepribadian.'
        ],
        [
            'pertanyaan' => 'Dalam kerja tim, peran Anda cenderung...',
            'pilihan_a' => 'Pasif',
            'pilihan_b' => 'Dominan',
            'pilihan_c' => 'Kolaboratif dan konstruktif',
            'pilihan_d' => 'Mencari alasan',
            'pilihan_e' => 'Mengabaikan',
            'jawaban_benar' => 'Kolaboratif dan konstruktif',
            'bobot_tkp' => 5,
            'pembahasan' => 'Jawaban C menunjukkan kemampuan kerja tim dan kepemimpinan. Ini nilai tertinggi dalam kepribadian.'
        ]
    ];
    
    return $scenarios[array_rand($scenarios)];
}

/**
 * Main TKP generator function
 * Routes to specific topic generators
 * @param string $topic The specific TKP topic
 * @return array Question data
 */
function generateTKP(string $topic = ''): array {
    $generators = [
        'Pelayanan Publik' => 'generateTKP_PelayananPublik',
        'Jejaring Kerja' => 'generateTKP_JejaringKerja',
        'Sosial Budaya' => 'generateTKP_SosialBudaya',
        'Teknologi Informasi' => 'generateTKP_TeknologiInformasi',
        'Profesionalisme' => 'generateTKP_Profesionalisme',
        'Kepribadian' => 'generateTKP_Kepribadian'
    ];
    
    if ($topic && isset($generators[$topic])) {
        return call_user_func($generators[$topic]);
    }
    
    // Random topic if not specified
    $randomTopic = array_rand($generators);
    return call_user_func($generators[$randomTopic]);
}
