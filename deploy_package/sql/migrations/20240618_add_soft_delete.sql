-- Migration: Add Soft Delete Support
-- Date: 2026-06-17
-- Author: Cascade AI
--
-- This migration adds soft delete support to major tables.
-- Soft delete allows records to be marked as deleted without actually removing them.
-- BACKUP DATABASE BEFORE RUNNING!

-- ============================================
-- 1. Add deleted_at column to users table
-- ============================================

ALTER TABLE users 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

-- Add index for soft delete queries
CREATE INDEX idx_users_deleted_at ON users(deleted_at);

-- ============================================
-- 2. Add deleted_at column to questions table
-- ============================================

ALTER TABLE questions 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

-- Add index for soft delete queries
CREATE INDEX idx_questions_deleted_at ON questions(deleted_at);

-- ============================================
-- 3. Add deleted_at column to materi table
-- ============================================

ALTER TABLE materi 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

-- Add index for soft delete queries
CREATE INDEX idx_materi_deleted_at ON materi(deleted_at);

-- ============================================
-- 4. Add deleted_at column to master_materi table
-- ============================================

ALTER TABLE master_materi 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

-- Add index for soft delete queries
CREATE INDEX idx_master_materi_deleted_at ON master_materi(deleted_at);

-- ============================================
-- 5. Add deleted_at column to tips_tricks table
-- ============================================

ALTER TABLE tips_tricks 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

-- Add index for soft delete queries
CREATE INDEX idx_tips_tricks_deleted_at ON tips_tricks(deleted_at);

-- ============================================
-- 6. Add deleted_at column to instansi table
-- ============================================

ALTER TABLE instansi 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

-- Add index for soft delete queries
CREATE INDEX idx_instansi_deleted_at ON instansi(deleted_at);

-- ============================================
-- 7. Update foreign key constraints to respect soft delete
-- ============================================

-- Note: We keep ON DELETE CASCADE for tryout_sessions and answers
-- because these are session data that should be hard-deleted when the session is deleted
-- Soft delete is mainly for content (users, questions, materials, tips)

-- ============================================
-- 8. Verify soft delete columns added
-- ============================================

SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND COLUMN_NAME = 'deleted_at'
ORDER BY TABLE_NAME;

-- ============================================
-- NOTES:
-- ============================================
-- - Soft delete allows recovery of accidentally deleted records
-- - Records with deleted_at IS NULL are considered active
-- - Records with deleted_at IS NOT NULL are considered deleted
-- - All queries should include WHERE deleted_at IS NULL to filter out soft-deleted records
-- - Hard delete can still be performed by actually deleting the record
