-- Migration: Enhance materi table for full CRUD
-- Date: 2026-06-07
-- Description: Add columns for materi management

ALTER TABLE materi 
ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT 'Active status for materi' AFTER urutan,
ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
ADD COLUMN created_by INT NULL COMMENT 'User who created materi' AFTER updated_at,
ADD COLUMN updated_by INT NULL COMMENT 'User who last updated materi' AFTER created_by,
ADD INDEX idx_is_active (is_active),
ADD INDEX idx_subtes (subtes);
