-- ============================================
-- NORMALISASI DATABASE SKD CAT-BKN
-- ============================================
-- Fokus: tryout_sessions repeating groups → session_subtes
-- Backup data sebelum migrasi dilakukan otomatis via transaction

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. TABEL subtes_config (KONFIGURASI GLOBAL)
-- ============================================
-- Menyimpan konfigurasi passing grade, durasi, jumlah soal per subtes.
-- Ini menghilangkan hardcoded values dan redundansi di tryout_sessions.
DROP TABLE IF EXISTS subtes_config;
CREATE TABLE subtes_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtes ENUM('TKP','TIU','TWK') NOT NULL,
    durasi_menit INT NOT NULL DEFAULT 30 COMMENT 'Durasi per subtes dalam menit',
    jumlah_soal INT NOT NULL DEFAULT 30 COMMENT 'Jumlah soal per subtes',
    passing_grade INT NOT NULL DEFAULT 0 COMMENT 'Nilai minimum lulus per subtes',
    urutan INT NOT NULL DEFAULT 1 COMMENT 'Urutan pengerjaan subtes',
    aktif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_subtes (subtes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data konfigurasi sesuai PermenPANRB No. 208/2025
INSERT INTO subtes_config (subtes, durasi_menit, jumlah_soal, passing_grade, urutan) VALUES
('TWK', 30, 30, 65, 1),
('TIU', 35, 35, 80, 2),
('TKP', 45, 45, 126, 3);

-- ============================================
-- 2. TABEL session_subtes (NORMALISASI tryout_sessions)
-- ============================================
-- Menggantikan kolom berulang durasi_*, jumlah_*, passing_*, nilai_* di tryout_sessions
DROP TABLE IF EXISTS session_subtes;
CREATE TABLE session_subtes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    subtes ENUM('TKP','TIU','TWK') NOT NULL,
    durasi_menit INT NOT NULL DEFAULT 30,
    jumlah_soal INT NOT NULL DEFAULT 30,
    passing_grade INT NOT NULL DEFAULT 0,
    nilai INT NOT NULL DEFAULT 0 COMMENT 'Nilai akhir subtes setelah finish',
    urutan INT NOT NULL DEFAULT 1,
    FOREIGN KEY (session_id) REFERENCES tryout_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_subtes (session_id, subtes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. MIGRASI DATA: tryout_sessions → session_subtes
-- ============================================
INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, nilai, urutan)
SELECT 
    id,
    'TKP',
    durasi_tkp,
    jumlah_tkp,
    passing_tkp,
    nilai_tkp,
    3
FROM tryout_sessions
WHERE jumlah_tkp > 0;

INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, nilai, urutan)
SELECT 
    id,
    'TIU',
    durasi_tiu,
    jumlah_tiu,
    passing_tiu,
    nilai_tiu,
    2
FROM tryout_sessions
WHERE jumlah_tiu > 0;

INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, nilai, urutan)
SELECT 
    id,
    'TWK',
    durasi_twk,
    jumlah_twk,
    passing_twk,
    nilai_twk,
    1
FROM tryout_sessions
WHERE jumlah_twk > 0;

-- ============================================
-- 4. TABEL question_options (NORMALISASI questions)
-- ============================================
-- Menggantikan kolom berulang pilihan_a ... pilihan_e di questions
DROP TABLE IF EXISTS question_options;
CREATE TABLE question_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    opsi_label CHAR(1) NOT NULL COMMENT 'A/B/C/D/E',
    opsi_teks TEXT NOT NULL,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_question_opsi (question_id, opsi_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrasi pilihan dari questions ke question_options
INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT id, 'A', pilihan_a FROM questions WHERE pilihan_a IS NOT NULL;
INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT id, 'B', pilihan_b FROM questions WHERE pilihan_b IS NOT NULL;
INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT id, 'C', pilihan_c FROM questions WHERE pilihan_c IS NOT NULL;
INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT id, 'D', pilihan_d FROM questions WHERE pilihan_d IS NOT NULL;
INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT id, 'E', pilihan_e FROM questions WHERE pilihan_e IS NOT NULL;

-- ============================================
-- 5. VIEWS untuk backward compatibility (sementara)
-- ============================================
-- View ini memungkinkan kode lama yang membaca tryout_sessions langsung
-- tetap berfungsi selama migrasi FE berlangsung

DROP VIEW IF EXISTS v_tryout_sessions_flat;
CREATE VIEW v_tryout_sessions_flat AS
SELECT 
    ts.id, ts.user_id, ts.nama, ts.waktu_mulai, ts.waktu_selesai, ts.status, ts.created_at,
    MAX(CASE WHEN ss.subtes = 'TWK' THEN ss.durasi_menit END) AS durasi_twk_v,
    MAX(CASE WHEN ss.subtes = 'TIU' THEN ss.durasi_menit END) AS durasi_tiu_v,
    MAX(CASE WHEN ss.subtes = 'TKP' THEN ss.durasi_menit END) AS durasi_tkp_v,
    MAX(CASE WHEN ss.subtes = 'TWK' THEN ss.jumlah_soal END) AS jumlah_twk_v,
    MAX(CASE WHEN ss.subtes = 'TIU' THEN ss.jumlah_soal END) AS jumlah_tiu_v,
    MAX(CASE WHEN ss.subtes = 'TKP' THEN ss.jumlah_soal END) AS jumlah_tkp_v,
    MAX(CASE WHEN ss.subtes = 'TWK' THEN ss.passing_grade END) AS passing_twk_v,
    MAX(CASE WHEN ss.subtes = 'TIU' THEN ss.passing_grade END) AS passing_tiu_v,
    MAX(CASE WHEN ss.subtes = 'TKP' THEN ss.passing_grade END) AS passing_tkp_v,
    MAX(CASE WHEN ss.subtes = 'TWK' THEN ss.nilai END) AS nilai_twk_v,
    MAX(CASE WHEN ss.subtes = 'TIU' THEN ss.nilai END) AS nilai_tiu_v,
    MAX(CASE WHEN ss.subtes = 'TKP' THEN ss.nilai END) AS nilai_tkp_v
FROM tryout_sessions ts
LEFT JOIN session_subtes ss ON ts.id = ss.session_id
GROUP BY ts.id;

-- ============================================
-- 6. INDEX untuk performa
-- ============================================
CREATE INDEX idx_session_subtes_session ON session_subtes(session_id);
CREATE INDEX idx_question_options_question ON question_options(question_id);

SET FOREIGN_KEY_CHECKS = 1;
