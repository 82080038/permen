-- Migration: Add account lockout columns to users table
-- Date: 2025-06-05
-- Description: Add failed_attempts and lockout_until columns for account lockout policy

ALTER TABLE users ADD COLUMN failed_attempts INT DEFAULT 0 AFTER password_hash;
ALTER TABLE users ADD COLUMN lockout_until DATETIME NULL AFTER failed_attempts;
