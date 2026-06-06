-- Migration: Add strict_mode column to tryout_sessions table
-- Description: Add strict mode support for tryout (no back navigation)
-- Date: 2026-06-06

ALTER TABLE tryout_sessions ADD COLUMN strict_mode TINYINT DEFAULT 0 COMMENT 'Strict mode: 1 = no back navigation, 0 = normal mode';
