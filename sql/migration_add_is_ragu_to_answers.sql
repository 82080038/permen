-- Migration: Add is_ragu column to answers table
-- Description: Add doubt flag support for tryout answers
-- Date: 2026-06-06

ALTER TABLE answers ADD COLUMN is_ragu TINYINT DEFAULT 0 COMMENT 'Doubt flag: 1 = ragu-ragu, 0 = tidak ragu';
