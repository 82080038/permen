-- Migration: Add profile enhancement fields
-- Date: 2026-06-07
-- Description: Add photo upload, additional profile fields, and privacy settings

-- Add new columns to users table
ALTER TABLE users 
ADD COLUMN foto_profil VARCHAR(255) DEFAULT NULL COMMENT 'Path to profile photo',
ADD COLUMN tanggal_lahir DATE DEFAULT NULL COMMENT 'Tanggal lahir user',
ADD COLUMN jenis_kelamin ENUM('L', 'P') DEFAULT NULL COMMENT 'Jenis kelamin: L=Laki-laki, P=Perempuan',
ADD COLUMN alamat TEXT DEFAULT NULL COMMENT 'Alamat lengkap user',
ADD COLUMN show_leaderboard TINYINT(1) DEFAULT 1 COMMENT 'Show profile in leaderboard: 1=show, 0=hide';

-- Create uploads directory for profile photos if not exists
-- Note: This needs to be done manually or via application code
