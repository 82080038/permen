-- Migration v1.1 — Schema Update for Production
-- Run this AFTER db.sql and seed.sql have been imported
-- Date: 2026-06-03

-- ============================================
-- 1. questions table: enrichment columns
-- ============================================
ALTER TABLE questions
    ADD COLUMN IF NOT EXISTS tips_trick TEXT NULL DEFAULT NULL AFTER pembahasan,
    ADD COLUMN IF NOT EXISTS related_links TEXT NULL DEFAULT NULL AFTER tips_trick,
    ADD COLUMN IF NOT EXISTS materi_id INT NULL DEFAULT NULL AFTER related_links,
    ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) NULL DEFAULT NULL AFTER materi_id,
    ADD COLUMN IF NOT EXISTS passage_id INT NULL DEFAULT NULL AFTER image_url,
    ADD COLUMN IF NOT EXISTS passage_order INT NOT NULL DEFAULT 0 AFTER passage_id,
    ADD COLUMN IF NOT EXISTS needs_revision TINYINT(1) NOT NULL DEFAULT 0 AFTER passage_order,
    ADD COLUMN IF NOT EXISTS revision_status VARCHAR(20) NULL DEFAULT NULL AFTER needs_revision,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER revision_status;

-- Index for admin queries
CREATE INDEX IF NOT EXISTS idx_questions_needs_revision ON questions(needs_revision);
CREATE INDEX IF NOT EXISTS idx_questions_is_active ON questions(is_active);
CREATE INDEX IF NOT EXISTS idx_questions_subtes_topik ON questions(subtes, topik);

-- ============================================
-- 2. materi table: external URL
-- ============================================
ALTER TABLE materi
    ADD COLUMN IF NOT EXISTS url VARCHAR(255) NULL DEFAULT NULL AFTER konten;

-- ============================================
-- 3. session_subtes normalization table
-- ============================================
CREATE TABLE IF NOT EXISTS session_subtes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    subtes VARCHAR(10) NOT NULL,
    durasi_menit INT NOT NULL DEFAULT 30,
    jumlah_soal INT NOT NULL DEFAULT 30,
    passing_grade INT NOT NULL DEFAULT 65,
    nilai INT NOT NULL DEFAULT 0,
    urutan INT NOT NULL DEFAULT 1,
    waktu_mulai_subtes DATETIME NULL,
    INDEX idx_session_subtes_session (session_id),
    INDEX idx_session_subtes_subtes (subtes),
    FOREIGN KEY (session_id) REFERENCES tryout_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. instansi table
-- ============================================
CREATE TABLE IF NOT EXISTS instansi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    tkp_passing INT NOT NULL DEFAULT 126,
    tiu_passing INT NOT NULL DEFAULT 80,
    twk_passing INT NOT NULL DEFAULT 65,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    urutan INT NOT NULL DEFAULT 0,
    INDEX idx_instansi_aktif (aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. rekomendasi_materi table
-- ============================================
CREATE TABLE IF NOT EXISTS rekomendasi_materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtes VARCHAR(10) NOT NULL,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    url VARCHAR(255) NULL,
    urutan INT NOT NULL DEFAULT 0,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_rekomendasi_subtes (subtes, aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. soal_ai_cache table
-- ============================================
CREATE TABLE IF NOT EXISTS soal_ai_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtes VARCHAR(10) NOT NULL,
    tipe VARCHAR(50) NULL,
    topik VARCHAR(255) NOT NULL,
    pertanyaan TEXT NOT NULL,
    created_at DATETIME DEFAULT NOW(),
    INDEX idx_cache_lookup (subtes, topik, tipe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. master_materi table
-- ============================================
CREATE TABLE IF NOT EXISTS master_materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtes VARCHAR(10) NOT NULL,
    tipe VARCHAR(50) NULL,
    topik VARCHAR(255) NOT NULL,
    kisi_kisi TEXT NULL,
    contoh TEXT NULL,
    level VARCHAR(20) DEFAULT 'sedang',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_master_subtes_topik (subtes, topik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 8. tips_tricks table
-- ============================================
CREATE TABLE IF NOT EXISTS tips_tricks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtes VARCHAR(10) NOT NULL,
    trik VARCHAR(255) NOT NULL,
    akronim VARCHAR(50) NULL,
    langkah TEXT NULL,
    contoh_soal TEXT NULL,
    penjelasan TEXT NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_tips_subtes (subtes, aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 9. subtes_config table
-- ============================================
CREATE TABLE IF NOT EXISTS subtes_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtes VARCHAR(10) NOT NULL UNIQUE,
    durasi_menit INT NOT NULL DEFAULT 30,
    jumlah_soal INT NOT NULL DEFAULT 30,
    passing_grade INT NOT NULL DEFAULT 65,
    urutan INT NOT NULL DEFAULT 1,
    aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 10. Data integrity fixes
-- ============================================
-- Ensure all questions have minimum pembahasan length
-- UPDATE questions SET pembahasan = CONCAT(pembahasan, ' Tips: pelajari konsep dasar ini dengan teliti.') WHERE LENGTH(pembahasan) < 120;

-- Mark existing questions as active and not needing revision
UPDATE questions SET is_active = 1, needs_revision = 0 WHERE is_active IS NULL;

-- Insert default subtes config
INSERT IGNORE INTO subtes_config (subtes, durasi_menit, jumlah_soal, passing_grade, urutan) VALUES
    ('TWK', 30, 30, 65, 1),
    ('TIU', 35, 35, 80, 2),
    ('TKP', 45, 45, 126, 3);
