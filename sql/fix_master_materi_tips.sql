-- ============================================================
-- FIX: Insert master_materi & tips_tricks untuk topik yang belum ada
-- Dijalankan setelah seed.sql
-- ============================================================

USE skd_cat_bkn;

-- ============================================================
-- MASTER MATERI (kisi_kisi untuk AI / Smart Generator)
-- ============================================================

INSERT INTO master_materi (subtes, tipe, topik, kisi_kisi, level, aktif) VALUES
('TKP', NULL, 'Kepribadian',
 'Kepribadian dan karakteristik individu, sikap dalam menghadapi tantangan, kemampuan beradaptasi, ketahanan di bawah tekanan, empati, kejujuran, dan kerja sama dalam tim.',
 'sedang', 1),

('TIU', NULL, 'Logika Matematika',
 'Penalaran logis dalam matematika, hubungan antar variabel, pola berpikir deduktif dan induktif, simbol matematika, dan pemecahan masalah non-rutin.',
 'sedang', 1),

('TIU', 'figural', 'Analogi',
 'Hubungan visual antar bentuk geometri: kesamaan, perbedaan, transformasi, dan korespondensi antar elemen gambar. Identifikasi pasangan bentuk yang memiliki pola relasi sama.',
 'sedang', 1),

('TIU', 'figural', 'Serial',
 'Pola urutan gambar: perubahan ukuran, rotasi, refleksi, penambahan/pengurangan elemen, dan transisi bentuk dalam deret visual.',
 'sedang', 1),

('TWK', NULL, 'Pancasila',
 'Lima sila Pancasila, nilai-nilai luhur, makna filosofis dan praktis, implementasi dalam kehidupan berbangsa dan bernegara, serta peran Pancasila sebagai dasar negara.',
 'sedang', 1),

('TWK', NULL, 'Sejarah',
 'Peristiwa penting perjuangan bangsa Indonesia, tokoh nasional, proklamasi kemerdekaan, pergerakan nasional, dan warisan sejarah dalam pembentukan identitas bangsa.',
 'sedang', 1),

('TWK', NULL, 'UUD 1945',
 'Struktur dan isi UUD 1945, fungsi lembaga negara (DPR, DPD, MPR, BPK, MK), hak dan kewajiban warga negara, serta perubahan UUD 1945.',
 'sedang', 1)

ON DUPLICATE KEY UPDATE kisi_kisi = VALUES(kisi_kisi), aktif = 1;


-- ============================================================
-- TIPS & TRICKS (tips singkat + contoh soal)
-- ============================================================

INSERT INTO tips_tricks (subtes, tipe, topik, judul, tips, contoh_soal, contoh_penerapan, contoh_jawaban, contoh_pembahasan, urutan) VALUES
('TKP', NULL, 'Kepribadian',
 'Memahami Diri Sendiri',
 'Pilih jawaban yang mencerminkan sikap positif, jujur, dan bertanggung jawab. Hindari jawaban ekstrem (terlalu pasif atau terlalu agresif).',
 'Anda sedang mengalami kesulitan dalam pekerjaan. Tindakan terbaik adalah...',
 'Dalam situasi sulit, evaluasi diri dan minta bantuan jika perlu menunjukkan kepribadian yang dewasa dan terbuka.',
 'B',
 'Jawaban yang menunjukkan evaluasi diri dan keterbukaan untuk berkembang adalah pilihan terbaik dalam soal kepribadian.',
 1),

('TIU', NULL, 'Logika Matematika',
 'Sistematis dalam Logika',
 'Gunakan metode eliminasi. Tuliskan variabel yang diketahui dan yang dicari. Periksa konsistensi pernyataan sebelum menyimpulkan.',
 'Jika A > B dan B > C, maka...',
 'Hubungan transitif: A > B > C berarti A > C. Pola dasar logika matematika.',
 'A',
 'Dari A > B dan B > C, dengan sifat transitif dapat disimpulkan A > C.',
 1),

('TIU', 'figural', 'Analogi',
 'Cari Pola Dasar Gambar',
 'Perhatikan hubungan antar elemen visual: ukuran, bentuk, posisi, dan jumlah. Analogi figural selalu memiliki pasangan yang memenuhi aturan yang sama.',
 'Segitiga : Segiempat = Lingkaran : ...',
 'Segiempat memiliki sisi lebih banyak dari segitiga; bentuk berikutnya dengan sisi lebih banyak dari lingkaran adalah elips tidak terbatas, namun dalam konteks soal umumnya lingkaran berpasangan dengan elips atau oval.',
 'B',
 'Analogi figural mencari hubungan korespondensi visual; pilih bentuk yang memiliki relasi geometris serupa.',
 1),

('TWK', NULL, 'Bela Negara',
 'Pahami UU No. 3/2002',
 'Bela negara bukan hanya tugas TNI/Polri, melainkan kewajiban setiap warga negara. Fokus pada sikap aktif membela kedaulatan dan keutuhan NKRI.',
 'Bela negara menurut UU No. 3/2002 merupakan...',
 'Kewajiban setiap warga negara untuk membela negara, bukan hanya TNI/Polri.',
 'B',
 'Pasal UU No. 3/2002 menegaskan bela negara sebagai kewajiban seluruh rakyat Indonesia.',
 1),

('TWK', NULL, 'Integritas',
 'Keselarasan Pikiran, Ucapan, dan Perbuatan',
 'Integritas = konsistensi antara nilai moral dan tindakan. Pilih jawaban yang menunjukkan keberanian mempertahankan kebenaran meski ada tekanan.',
 'Integritas seorang pegawai ditunjukkan oleh...',
 'Menolak suap meski ada kesempatan menunjukkan integritas tinggi.',
 'B',
 'Integritas adalah keselarasan antara prinsip moral dan perilaku nyata dalam situasi apapun.',
 1),

('TWK', NULL, 'Nasionalisme',
 'Cinta Tanah Air secara Konkret',
 'Nasionalisme bukan chauvinisme. Pilih jawaban yang menunjukkan tindakan nyata: gunakan produk dalam negeri, lestarikan budaya, dan hormati keberagaman.',
 'Semangat nasionalisme paling tepat ditunjukkan oleh...',
 'Menggunakan produk dalam negeri dan mendukung UMKM menunjukkan nasionalisme positif.',
 'B',
 'Nasionalisme konstruktif diwujudkan melalui tindakan konkret yang memajukan bangsa tanpa merendahkan bangsa lain.',
 1),

('TWK', NULL, 'Pilar Negara',
 'Pancasila sebagai Pilar',
 'Empat pilar negara: Pancasila, UUD 1945, NKRI, Bhinneka Tunggal Ika. Pancasila adalah dasar falsafah; pahami makna setiap sila dan implementasinya.',
 'Salah satu pilar negara menurut Pembukaan UUD 1945 adalah...',
 'Pancasila sebagai pilar negara mencakup sila Ketuhanan Yang Maha Esa, kemanusiaan, persatuan, kerakyatan, dan keadilan sosial.',
 'B',
 'Pancasila dengan lima silanya menjadi dasar pilar negara Republik Indonesia.',
 1)

ON DUPLICATE KEY UPDATE tips = VALUES(tips), contoh_soal = VALUES(contoh_soal), contoh_pembahasan = VALUES(contoh_pembahasan);
