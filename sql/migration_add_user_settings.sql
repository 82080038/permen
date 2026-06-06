-- Migration: Add user settings columns
-- Date: 2026-06-07
-- Description: Add columns for user preferences (notifications, language, theme, font size)

-- Add new columns to users table
ALTER TABLE users 
ADD COLUMN notification_preference ENUM('push', 'browser', 'both', 'none') DEFAULT 'browser' COMMENT 'Preferensi notifikasi',
ADD COLUMN language VARCHAR(5) DEFAULT 'id' COMMENT 'Bahasa: id=en, en=english',
ADD COLUMN theme VARCHAR(10) DEFAULT 'light' COMMENT 'Tema: light/dark',
ADD COLUMN font_size VARCHAR(10) DEFAULT 'medium' COMMENT 'Ukuran font: small/medium/large';
