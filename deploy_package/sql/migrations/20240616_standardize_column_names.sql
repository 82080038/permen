-- Migration: Standardize Column Names
-- Date: 2026-06-16
-- Author: Cascade AI
--
-- This migration standardizes column naming conventions across all tables.
-- Convention: snake_case for all column names, consistent prefixes where applicable.
-- BACKUP DATABASE BEFORE RUNNING!

-- ============================================
-- 1. Standardize timestamp columns
-- ============================================

-- Rename created_at to created_at (already standard)
-- Rename updated_at to updated_at (already standard)
-- Rename deleted_at to deleted_at (for soft delete, to be added later)

-- ============================================
-- 2. Standardize user-related columns
-- ============================================

-- users table - already has standard names
-- No changes needed for: id, nama, no_hp, email, password, role, target_instansi, created_at

-- ============================================
-- 3. Standardize question-related columns
-- ============================================

-- questions table - already has standard names
-- No changes needed for: id, subtes, tipe, topik, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, jawaban_benar, pembahasan, image_url, is_active, created_at

-- ============================================
-- 4. Standardize session-related columns
-- ============================================

-- tryout_sessions table - already has standard names
-- No changes needed for: id, user_id, package_id, status, waktu_mulai, waktu_selesai, total_nilai, nilai_tkp, nilai_tiu, nilai_twk, created_at

-- session_subtes table - already has standard names
-- No changes needed for: id, session_id, subtes, durasi_menit, jumlah_soal, passing_grade, nilai, waktu_mulai_subtes

-- ============================================
-- 5. Standardize answer-related columns
-- ============================================

-- answers table - already has standard names
-- No changes needed for: id, session_id, question_id, jawaban, skor, is_ragu, waktu_jawab

-- ============================================
-- 6. Standardize material-related columns
-- ============================================

-- materi table - already has standard names
-- No changes needed for: id, subtes, judul, konten, created_at

-- master_materi table - already has standard names
-- No changes needed for: id, subtes, topik, judul, konten, created_at

-- ============================================
-- 7. Standardize tip-related columns
-- ============================================

-- tips_tricks table - already has standard names
-- No changes needed for: id, subtes, judul, konten, created_at

-- ============================================
-- 8. Add comments to document naming convention
-- ============================================

ALTER TABLE users COMMENT = 'Users table - snake_case naming convention';
ALTER TABLE questions COMMENT = 'Questions table - snake_case naming convention';
ALTER TABLE tryout_sessions COMMENT = 'Tryout sessions table - snake_case naming convention';
ALTER TABLE session_subtes COMMENT = 'Session subtests table - snake_case naming convention';
ALTER TABLE answers COMMENT = 'Answers table - snake_case naming convention';
ALTER TABLE materi COMMENT = 'Materials table - snake_case naming convention';
ALTER TABLE master_materi COMMENT = 'Master materials table - snake_case naming convention';
ALTER TABLE tips_tricks COMMENT = 'Tips and tricks table - snake_case naming convention';

-- ============================================
-- 9. Verify column naming
-- ============================================

-- Run this to verify all columns follow snake_case convention
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- ============================================
-- NOTES:
-- ============================================
-- - All tables already follow snake_case naming convention
-- - No column renaming required
-- - This migration serves as documentation and verification
-- - Future tables should follow the same convention
