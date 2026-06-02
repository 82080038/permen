USE skd_cat_bkn;

-- ============================================================
-- TIPS & TRIK (Reusable, singkat, padat, dengan contoh penerapan)
-- ============================================================

INSERT INTO tips_tricks (subtes, tipe, topik, judul, tips, contoh_soal, contoh_penerapan, contoh_jawaban, contoh_pembahasan, urutan) VALUES

-- === TIU NUMERIK ===
('TIU', 'numerik', 'Deret Angka', 'TRIK SLEDING: Selisih → Lihat → Eksplorasi → Diagram → Identifikasi → Next → Gap', 
'1. Hitung SELISIH antar angka.\n2. LIHAT apakah selisih tetap, naik, atau membentuk pola sendiri.\n3. EKSPLORASI operasi lain (kali, bagi, pangkat).\n4. DIAGRAM dengan garis bantu jika bingung.\n5. IDENTIFIKASI apakah deret gabungan (2 deret dalam 1).\n6. NEXT: tulis 2-3 angka selanjutnya untuk cek kecocokan.\n7. GAP: jika stuck, perhatikan selisih kedua (beda dari beda).',
'Deret: 3, 8, 15, 24, 35, ...',
'Selisih: +5,+7,+9,+11 → selisih naik +2. Selanjutnya +13. 35+13=48.',
'48',
'Pola selisih ganjil bertambah 2. Deret kuadratik (n²+2n).',
1),

('TIU', 'numerik', 'Soal Cerita', 'RUMUS DJK: Diketahui → Jawab yang dicari → Kerjakan dengan rumus → Kontrol satuan',
'1. Tulis semua yang DIKETAHUI.\n2. Tentukan JAWAB yang dicari.\n3. KERJAKAN dengan rumus yang sesuai.\n4. KONTROL satuan (km/jam, m/menit, persen vs desimal).',
'Bus berangkat 07.00, tiba 10.30. Jarak 210 km. Kecepatan rata-rata?',
'Diketahui: waktu=3,5 jam, jarak=210 km. Jawab: kecepatan. Rumus: v=s/t=210/3,5=60.',
'60 km/jam',
'Kecepatan = jarak ÷ waktu. Perhatikan satuan jam, bukan menit.',
2),

('TIU', 'numerik', 'Berhitung', 'TRIK PERKALIAN CEPAT: 25×N = N×100÷4, 5×N = N×10÷2, 11×N = jumlahkan digit',
'25 × N = (N × 100) ÷ 4.\n5 × N = (N × 10) ÷ 2.\n11 × N = digit pertama, (jumlah digit), digit terakhir.\nKuadrat berakhiran 5: n × (n+1), lalu tambah 25.',
'36 × 25 = ?',
'36 × 100 = 3600. 3600 ÷ 4 = 900.',
'900',
'Perkalian 25 ekuivalen dengan ×100 lalu ÷4 karena 25 = 100/4.',
3),

('TIU', 'numerik', 'Perbandingan', 'TRIK PROPORSI: Sebanding = silang, Berbalik = kali silang',
'Sebanding (sama arah): a/b = c/d → a×d = b×c.\nBerbalik nilai (lawan arah): a×b = c×d.\nContoh berbalik: pekerja ↑ → waktu ↓.',
'3 pekerja selesai dalam 6 hari. Berapa hari untuk 9 pekerja?',
'Berbalik: 3×6 = 9×h → h = 18/9 = 2.',
'2 hari',
'Pekerja naik 3x, waktu turun 3x. 6÷3 = 2.',
4),

-- === TIU VERBAL ===
('TIU', 'verbal', 'Analogi', 'TRIK HUBUNGAN 5W1H: Tanya "hubungan A dan B itu apa?" lalu cocokkan ke C',
'1. Identifikasi hubungan pasangan pertama (A:B).\n2. Tanya: apakah sebab-akibat, fungsi, sinonim, antonim, habitat, profesi?\n3. Cari pasangan kedua (C:D) dengan hubungan PERSIS SAMA.\n4. Hindari jawaban yang "hampir mirip" — harus presisi.',
'BURUNG : UDARA = ... : ...',
'Burung hidup di udara (habitat). Ikan hidup di air. Hubungan: habitat.',
'A. Ikan : Air',
'Analogi habitat: setiap makhluk memiliki tempat hidup utama.',
1),

('TIU', 'verbal', 'Silogisme', 'TRIK DIAGRAM VENN: Gambar lingkaran untuk setiap premis',
'1. Premis 1: gambar lingkaran A di dalam B (jika "semua A adalah B").\n2. Premis 2: tempatkan elemen baru.\n3. Hati-hati: "sebagian" ≠ "semua", "tidak ada" = himpunan terpisah.\n4. Jika premis berisi 2×"sebagian" → kemungkinan besar "tidak dapat disimpulkan".',
'Semua penari adalah atlet. Sebagian atlet adalah vegetarian.',
'Gambar: Penari ⊂ Atlet. Vegetarian ∩ Atlet = sebagian. Tidak tahu apakah Penari masuk ke Vegetarian atau tidak.',
'E. Tidak dapat disimpulkan',
'Dua premis dengan "sebagian" tidak bisa menarik kesimpulan tentang irisan spesifik.',
2),

('TIU', 'verbal', 'Analitis', 'TRIK GARIS WAKTU: Buat garis/urutan dari setiap pernyataan',
'1. Tulis setiap pernyataan sebagai fakta terpisah.\n2. Buat garis atau tabel untuk urutan/posisi.\n3. Gabungkan fakta secara bertahap.\n4. Jika ada yang tidak diketahui → jawaban "tidak dapat ditentukan".',
'Andi > Budi. Budi > Candra. Dedi > Andi. Siapa paling tinggi?',
'Garis: Dedi — Andi — Budi — Candra. Paling tinggi = ujung kiri.',
'D. Dedi',
'Gabungkan premis secara berurutan untuk mendapatkan urutan lengkap.',
3),

-- === TIU FIGURAL ===
('TIU', 'figural', 'Analogi Figural', 'TRIK TRANSFORMASI: Ubah → Bandingkan → Terapkan',
'1. Perhatikan perubahan dari gambar A ke B (ukuran, rotasi, pencerminan, pengisian).\n2. BANDINGKAN perubahan tersebut.\n3. TERAPKAN perubahan yang sama ke gambar C untuk mendapatkan D.',
'Segitiga → Segitiga sama sisi. Persegi → ?',
'Transformasi: bentuk dasar → bentuk sempurna/reguler. Persegi sempurna = persegi dengan semua sisi sama.',
'B. Persegi dengan semua sisi sama',
'Analogi memperkuat bentuk dasar menjadi bentuk ideal/seragam.',
1),

('TIU', 'figural', 'Ketidaksamaan', 'TRIK CARI ANOMALI: Cek simetri, jumlah elemen, arah, bentuk dasar',
'1. Cek apakah semua gambar simetris?\n2. Cek jumlah elemen (garis, sudut, lingkaran kecil).\n3. Cek arah putaran atau orientasi.\n4. Cek bentuk dasar: 4 poligon + 1 lingkaran = lingkaran yang aneh.',
'5 gambar: segitiga, persegi, lingkaran, persegi panjang, jajaran genjang.',
'Lingkaran adalah satu-satunya yang tidak memiliki sudut/garis lurus.',
'C. Lingkaran',
'Gunakan prinsip pembedaan atribut geometris dasar.',
2),

('TIU', 'figural', 'Serial', 'TRIK SIKLUS: Kosong → Setengah → Penuh → Setengah lain → Kosong',
'1. Perhatikan urutan pengisian (kosong, arsir, penuh).\n2. Perhatikan rotasi bertahap (0°, 45°, 90°, ...).\n3. Perhatikan penambahan elemen (+1 garis, +1 titik).\n4. Jika stuck, tulis 1-2 gambar berikutnya untuk verifikasi.',
'○ → ◐ → ● → ?',
'Pola pengisian: kosong → setengah kanan → penuh → setengah kiri → kosong.',
'D. ◑',
'Deret pengisian membentuk siklus 4 langkah. Setelah penuh, kembali ke arah berlawanan.',
3),

-- === TWK ===
('TWK', NULL, 'Pancasila', 'AKRONIM BERTA: Bhinneka, Etika, Rakyat, Tuhan, Adil (5 silah)',
'Silah 1: Ketuhanan (BERTA-T)\nSilah 2: Kemanusiaan (BERTA-E)\nSilah 3: Persatuan (BERTA-R-B)\nSilah 4: Kerakyatan (BERTA-R)\nSilah 5: Keadilan (BERTA-A)',
'Silah ketiga Pancasila menekankan...',
'Ingat BERTA: R = Rakyat (ke-4), jadi R yang kedua = Persatuan.',
'Persatuan Indonesia',
'BERTA = urutan silah dari 1 sampai 5. R kedua = silah 3.',
1),

('TWK', NULL, 'UUD 1945', 'TRIK PEMBUKAAN 4N: Nasionalisme, Politik, Hukum, Nasionalisme ( filosofis = Pancasila )',
'Pembukaan UUD 1945 punya 4 nilai:\n1. Historis = perjuangan bangsa (Nasionalisme)\n2. Politis = cita-cita negara\n3. Hukum = dasar konstitusi\n4. Filosofis = Pancasila',
'Pembukaan UUD 1945 mengandung nilai...',
'Jawab yang mencakup keempat aspek: historis, politis, hukum, filosofis.',
'B. Historis, politis, hukum, dan filosofis',
'4N = empat nilai fundamental yang menjadi dasar berdirinya negara.',
2),

('TWK', NULL, 'Bahasa Indonesia', 'TRIK EYD KAT: Kapital, Awal kalimat, Tanda baca, Kata utuh',
'K = Kapital untuk nama diri, awal kalimat, gelar.\nA = Awal kalimat selalu huruf besar.\nT = Tanda baca (titik, koma, titik koma) jangan lupa.\nKata = jangan disingkat sembarangan (tidak → tdk ❌).',
'Mana penulisan yang benar?',
'Cek setiap pilihan dengan KAT: Kapital, Awal, Tanda baca, Kata utuh.',
'Penulisan sesuai EYD',
'EYD memastikan konsistensi dan kejelasan dalam komunikasi tertulis.',
3),

('TWK', NULL, 'Sejarah', 'TRIK TANGGAL KUNCI: 20-05 (Kebangkitan), 28-10 (Sumpah Pemuda), 10-11 (Pahlawan), 17-08 (Kemerdekaan)',
'20 Mei = Hari Kebangkitan Nasional (Budi Utomo)\n28 Okt = Sumpah Pemuda\n10 Nov = Hari Pahlawan (Pertempuran Surabaya)\n17 Agt = Proklamasi Kemerdekaan',
'Hari Kebangkitan Nasional diperingati tanggal...',
'Ingat urutan: 20 Mei → 28 Okt → 10 Nov → 17 Agt.',
'20 Mei',
'Hari Kebangkitan = 20 Mei 1908, awal pergerakan nasional Indonesia.',
4),

-- === TKP ===
('TKP', NULL, 'Pelayanan Publik', 'TRIK SOLID: Speed, Ownership, Loyalty, Integrity, Discipline',
'S = Speed: cepat tanggap.\nO = Ownership: tanggung jawab penuh.\nL = Loyalty: setia pada tugas.\nI = Integrity: jujur & transparan.\nD = Discipline: disiplin & konsisten.\nPilih jawaban yang paling SOLID.',
'Warga datang dengan emosi karena pengurusan tertunda. Sikap terbaik?',
'Pilih yang SOLID: S (cepat), O (tanggung jawab), I (jujur minta maaf).',
'B. Meminta maaf dan menjanjikan penyelesaian',
'Jawaban SOLID menunjukkan empati dan tanggung jawab aktif.',
1),

('TKP', NULL, 'Profesionalisme', 'TRIK AKHLAK ASN: Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif',
'A = Amanah: dapat dipercaya.\nK = Kompeten: mampu dan belajar.\nH = Harmonis: rukun kerja sama.\nL = Loyal: setia pada NKRI.\nA = Adaptif: inovatif & fleksibel.\nK = Kolaboratif: sinergi lintas sektor.',
'Seseorang menawarkan uang agar pengurusan dipercepat. Tindakan?',
'Amanah = jujur, tidak korupsi. Kompeten = tahu prosedur. Jawaban: tolak tegas & laporkan.',
'B. Menolak dengan tegas dan melaporkan',
'AKHLAK menuntut integritas tinggi dalam menghadapi gratifikasi.',
2),

('TKP', NULL, 'Teknologi Informasi', 'TRIK LIDS: Literasi, Integritas, Digital aman, Sceptical',
'L = Literasi: bedakan hoaks & fakta.\nI = Integritas: tidak sebar data rahasia.\nD = Digital aman: jaga password, akun.\nS = Sceptical: curigai sebelum teruskan info.',
'Menemukan hoaks di grup kerja. Tindakan?',
'LIDS: S (sceptical, klarifikasi), L (literasi, edukasi).',
'B. Menyampaikan klarifikasi dan menghimbau tidak menyebarkan',
'Literasi digital ASN = bertanggung jawab atas informasi yang diterima dan disebarkan.',
3),

('TKP', NULL, 'Jejaring Kerja', 'TRIK KOMUNIKASI 3B: Dengar, Berdiskusi, Bangun solusi',
'B1 = Dengar (listen) aktif kedua pihak.\nB2 = Berdiskusi dengan sopan & terbuka.\nB3 = Bangun solusi win-win, bukan win-lose.',
'Rekan mengalami kesulitan tugas yang berdampak deadline. Tindakan?',
'3B: Dengar → Berdiskusi → Bangun solusi. Tawarkan bantuan setelah tugas sendiri selesai.',
'B. Menawarkan bantuan setelah menyelesaikan tugas sendiri',
'Menawarkan bantuan sesuai kapasitas = jejaring kerja konstruktif.',
4),

('TKP', NULL, 'Sosial Budaya', 'TRIK TOLERANSI: Terima, Hormati, Adaptasi, Bantu, Integrasi',
'T = Terima keberagaman.\nH = Hormati adat & budaya lain.\nA = Adaptasi dengan lingkungan baru.\nB = Bantu yang dizolimi/dibully.\nI = Integrasi tanpa menghilangkan identitas.',
'Bekerja di daerah dengan adat berbeda. Sikap?',
'T+H+A = Terima, Hormati, Adaptasi. Jawaban: pelajari & hormati adat setempat.',
'B. Mempelajari dan menghormati adat setempat',
'Kesadaran sosial budaya = menghargai keberagaman sebagai kekuatan bangsa.',
5);
