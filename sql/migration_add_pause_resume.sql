-- Migration: Add pause/resume support to tryout_sessions
-- Description: Add pause/resume tryout feature (max 3 pauses, max 10 minutes total)
-- Date: 2026-06-06

ALTER TABLE tryout_sessions ADD COLUMN pause_count INT DEFAULT 0 COMMENT 'Number of times paused';
ALTER TABLE tryout_sessions ADD COLUMN total_pause_seconds INT DEFAULT 0 COMMENT 'Total paused time in seconds';
ALTER TABLE tryout_sessions ADD COLUMN is_paused TINYINT DEFAULT 0 COMMENT 'Currently paused: 1 = yes, 0 = no';
ALTER TABLE tryout_sessions ADD COLUMN paused_at TIMESTAMP NULL COMMENT 'Timestamp when pause started';
