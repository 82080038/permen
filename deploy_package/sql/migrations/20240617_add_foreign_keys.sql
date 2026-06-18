-- Migration: Add Foreign Key Constraints
-- Date: 2026-06-16
-- Author: Cascade AI
--
-- This migration adds foreign key constraints to ensure data integrity.
-- BACKUP DATABASE BEFORE RUNNING!

-- ============================================
-- 1. Add foreign key to tryout_sessions (user_id -> users.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE tryout_sessions DROP FOREIGN KEY IF EXISTS fk_tryout_sessions_user;

-- Add foreign key constraint
ALTER TABLE tryout_sessions 
ADD CONSTRAINT fk_tryout_sessions_user 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ============================================
-- 2. Add foreign key to session_subtes (session_id -> tryout_sessions.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE session_subtes DROP FOREIGN KEY IF EXISTS fk_session_subtes_session;

-- Add foreign key constraint
ALTER TABLE session_subtes 
ADD CONSTRAINT fk_session_subtes_session 
FOREIGN KEY (session_id) REFERENCES tryout_sessions(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ============================================
-- 3. Add foreign key to answers (session_id -> tryout_sessions.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE answers DROP FOREIGN KEY IF EXISTS fk_answers_session;

-- Add foreign key constraint
ALTER TABLE answers 
ADD CONSTRAINT fk_answers_session 
FOREIGN KEY (session_id) REFERENCES tryout_sessions(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ============================================
-- 4. Add foreign key to answers (question_id -> questions.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE answers DROP FOREIGN KEY IF EXISTS fk_answers_question;

-- Add foreign key constraint
ALTER TABLE answers 
ADD CONSTRAINT fk_answers_question 
FOREIGN KEY (question_id) REFERENCES questions(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ============================================
-- 5. Add foreign key to users (target_instansi -> instansi.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE users DROP FOREIGN KEY IF EXISTS fk_users_instansi;

-- Add foreign key constraint (nullable, so ON DELETE SET NULL)
ALTER TABLE users 
ADD CONSTRAINT fk_users_instansi 
FOREIGN KEY (target_instansi) REFERENCES instansi(id) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- ============================================
-- 6. Add foreign key to rekomendasi_materi (user_id -> users.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE rekomendasi_materi DROP FOREIGN KEY IF EXISTS fk_rekomendasi_materi_user;

-- Add foreign key constraint
ALTER TABLE rekomendasi_materi 
ADD CONSTRAINT fk_rekomendasi_materi_user 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ============================================
-- 7. Add foreign key to rekomendasi_materi (materi_id -> materi.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE rekomendasi_materi DROP FOREIGN KEY IF EXISTS fk_rekomendasi_materi_materi;

-- Add foreign key constraint
ALTER TABLE rekomendasi_materi 
ADD CONSTRAINT fk_rekomendasi_materi_materi 
FOREIGN KEY (materi_id) REFERENCES materi(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ============================================
-- 8. Add foreign key to soal_ai_cache (question_id -> questions.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE soal_ai_cache DROP FOREIGN KEY IF EXISTS fk_soal_ai_cache_question;

-- Add foreign key constraint
ALTER TABLE soal_ai_cache 
ADD CONSTRAINT fk_soal_ai_cache_question 
FOREIGN KEY (question_id) REFERENCES questions(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ============================================
-- 9. Add foreign key to question_options (question_id -> questions.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE question_options DROP FOREIGN KEY IF EXISTS fk_question_options_question;

-- Add foreign key constraint
ALTER TABLE question_options 
ADD CONSTRAINT fk_question_options_question 
FOREIGN KEY (question_id) REFERENCES questions(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ============================================
-- 10. Add foreign key to passages (question_id -> questions.id)
-- ============================================

-- First, remove any existing foreign key if it exists
ALTER TABLE passages DROP FOREIGN KEY IF EXISTS fk_passages_question;

-- Add foreign key constraint
ALTER TABLE passages 
ADD CONSTRAINT fk_passages_question 
FOREIGN KEY (question_id) REFERENCES questions(id) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- ============================================
-- 11. Verify foreign keys created
-- ============================================

SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- ============================================
-- NOTES:
-- ============================================
-- - Foreign keys ensure referential integrity
-- - ON DELETE CASCADE: automatically delete child records when parent is deleted
-- - ON DELETE SET NULL: set foreign key to NULL when parent is deleted (for nullable fields)
-- - ON UPDATE CASCADE: automatically update child records when parent key is updated
-- - This helps prevent orphaned records and maintains data consistency
