-- ============================================
-- MIGRASI: Tabel Instansi & Passing Grade
-- ============================================

DROP TABLE IF EXISTS instansi;
CREATE TABLE instansi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL COMMENT 'Singkatan: STAN, STIS, IPDN, dll',
    nama VARCHAR(100) NOT NULL COMMENT 'Nama lengkap instansi',
    passing_twk INT NOT NULL DEFAULT 65,
    passing_tiu INT NOT NULL DEFAULT 80,
    passing_tkp INT NOT NULL DEFAULT 126,
    passing_total INT NOT NULL DEFAULT 271,
    deskripsi TEXT DEFAULT NULL COMMENT 'Info singkat instansi',
    aktif BOOLEAN DEFAULT TRUE,
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data instansi sekolah kedinasan populer
-- Passing grade SKD adalah standar nasional, bedanya di seleksi kompetensi bidang
-- Data di bawah adalah ambang batas SKD umum + target nilai kompetitif
INSERT INTO instansi (kode, nama, passing_twk, passing_tiu, passing_tkp, passing_total, deskripsi, urutan) VALUES
('STAN', 'Sekolah Tinggi Akuntansi Negara', 65, 80, 126, 271, 'Dibawah naungan Kementerian Keuangan. Lulusan menjadi CPNS di DJP, DJPK, BPK, dll.', 1),
('STIS', 'Sekolah Tinggi Ilmu Statistik', 65, 80, 126, 271, 'Dibawah naungan BPS. Fokus statistik, data science, dan big data.', 2),
('IPDN', 'Institut Pemerintahan Dalam Negeri', 65, 80, 126, 271, 'Dibawah naungan Kemendagri. Menyediakan aparatur sipil negara daerah.', 3),
('STMKG', 'Sekolah Tinggi Meteorologi Klimatologi dan Geofisika', 65, 80, 126, 271, 'Dibawah naungan BMKG. Fokus cuaca, iklim, dan mitigasi bencana.', 4),
('POLTEK_SSN', 'Politeknik Siber dan Sandi Negara', 65, 80, 126, 271, 'Dibawah naungan BSSN. Fokus keamanan siber dan kriptografi.', 5),
('PTIK', 'Politeknik Ilmu Pemasyarakatan', 65, 80, 126, 271, 'Dibawah naungan Kemenkumham. Fokus pemasyarakatan dan pengawasan.', 6),
('POLTEK_IMIGRASI', 'Politeknik Imigrasi', 65, 80, 126, 271, 'Dibawah naungan Kemenkumham. Fokus keimigrasian dan pengawasan perbatasan.', 7),
('STIN', 'Sekolah Tinggi Intelijen Negara', 65, 80, 126, 271, 'Dibawah naungan BIN. Fokus intelijen dan analisis ancaman.', 8),
('AKPOL', 'Akademi Kepolisian', 65, 80, 126, 271, 'Dibawah naungan Polri. Fokus penegakan hukum dan keamanan.', 9),
('AKMIL', 'Akademi Militer', 65, 80, 126, 271, 'Dibawah naungan TNI AD. Fokus pertahanan dan kepemimpinan militer.', 10),
('AAU', 'Akademi Angkatan Udara', 65, 80, 126, 271, 'Dibawah naungan TNI AU. Fokus penerbangan dan pertahanan udara.', 11),
('AAL', 'Akademi Angkatan Laut', 65, 80, 126, 271, 'Dibawah naungan TNI AL. Fokus kelautan dan pertahanan laut.', 12);

-- Update users yang punya instansi_pilihan → pindahkan ke instansi_id
-- Pertama tambah kolom instansi_id ke users jika belum ada
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'instansi_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN instansi_id INT DEFAULT NULL AFTER role, ADD FOREIGN KEY (instansi_id) REFERENCES instansi(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Tabel rekomendasi_materi: mapping kelemahan → materi
-- ============================================
DROP TABLE IF EXISTS rekomendasi_materi;
CREATE TABLE rekomendasi_materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtes ENUM('TKP','TIU','TWK') NOT NULL,
    topik VARCHAR(100) NOT NULL,
    materi_url VARCHAR(200) NOT NULL COMMENT 'Link ke materi.php',
    pesan VARCHAR(255) NOT NULL COMMENT 'Pesan motivasi/rekomendasi',
    aktif BOOLEAN DEFAULT TRUE,
    urutan INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rekomendasi_materi (subtes, topik, materi_url, pesan, urutan) VALUES
-- TWK
('TWK', 'Nasionalisme', 'pages/materi.php?subtes=TWK&topik=Nasionalisme', 'Tingkatkan rasa cinta tanah air dengan memahami sejarah perjuangan bangsa.', 1),
('TWK', 'Integritas', 'pages/materi.php?subtes=TWK&topik=Integritas', 'Integritas adalah kunci sukses. Belajar etika dan anti korupsi.', 2),
('TWK', 'Bela Negara', 'pages/materi.php?subtes=TWK&topik=Bela+Negara', 'Pahami konsep bela negara dalam konteks modern.', 3),
('TWK', 'Pilar Negara', 'pages/materi.php?subtes=TWK&topik=Pilar+Negara', 'Kuasai Pancasila, UUD 1945, NKRI, dan Bhinneka Tunggal Ika.', 4),
('TWK', 'Bahasa Indonesia', 'pages/materi.php?subtes=TWK&topik=Bahasa+Indonesia', 'Perbanyak latihan EYD, peribahasa, dan kosakata baku.', 5),
-- TIU
('TIU', 'Analogi Verbal', 'pages/materi.php?subtes=TIU&tipe=verbal&topik=Analogi', 'Latih kemampuan asosiasi kata dengan latihan rutin.', 6),
('TIU', 'Silogisme', 'pages/materi.php?subtes=TIU&tipe=verbal&topik=Silogisme', 'Pahami struktur premis-premis untuk menyimpulkan logis.', 7),
('TIU', 'Analitis', 'pages/materi.php?subtes=TIU&tipe=verbal&topik=Analitis', 'Kerjakan soal TPA analitis dengan membuat tabel/diagram.', 8),
('TIU', 'Berhitung Cepat', 'pages/materi.php?subtes=TIU&tipe=numerik&topik=Berhitung', 'Kuasai trik perhitungan cepat dan operasi dasar.', 9),
('TIU', 'Deret Angka', 'pages/materi.php?subtes=TIU&tipe=numerik&topik=Deret', 'Identifikasi pola aritmatika, geometri, dan kombinasi.', 10),
('TIU', 'Perbandingan', 'pages/materi.php?subtes=TIU&tipe=numerik&topik=Perbandingan', 'Pahami rasio, skala, dan proporsi dengan latihan soal cerita.', 11),
('TIU', 'Soal Cerita', 'pages/materi.php?subtes=TIU&tipe=numerik&topik=Cerita', 'Pelajari teknik menerjemahkan soal cerita ke persamaan matematika.', 12),
('TIU', 'Figural Analogi', 'pages/materi.php?subtes=TIU&tipe=figural&topik=Analogi', 'Latih pengenalan pola visual dan transformasi gambar.', 13),
('TIU', 'Figural Ketidaksamaan', 'pages/materi.php?subtes=TIU&tipe=figural&topik=Ketidaksamaan', 'Cari perbedaan detail dalam gambar dengan fokus dan teliti.', 14),
('TIU', 'Figural Serial', 'pages/materi.php?subtes=TIU&tipe=figural&topik=Serial', 'Amati urutan pola visual dan prediksi gambar selanjutnya.', 15),
-- TKP
('TKP', 'Pelayanan Publik', 'pages/materi.php?subtes=TKP&topik=Pelayanan+Publik', 'Pelajari prinsip pelayanan prima dan penanganan keluhan.', 16),
('TKP', 'Jejaring Kerja', 'pages/materi.php?subtes=TKP&topik=Jejaring+Kerja', 'Pahami kerja sama tim, koordinasi, dan komunikasi efektif.', 17),
('TKP', 'Sosial Budaya', 'pages/materi.php?subtes=TKP&topik=Sosial+Budaya', 'Latih empati dan penghormatan terhadap keberagaman.', 18),
('TKP', 'Teknologi Informasi', 'pages/materi.php?subtes=TKP&topik=TI', 'Pahami etika digital dan pemanfaatan teknologi untuk pelayanan.', 19),
('TKP', 'Profesionalisme', 'pages/materi.php?subtes=TKP&topik=Profesionalisme', 'Kembangkan sikap profesional: integritas, komitmen, dan tanggung jawab.', 20);
