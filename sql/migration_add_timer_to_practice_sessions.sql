-- Migration: Add timer columns to personal practice sessions
-- Date: 2026-06-07
-- Description: Add timer mode and timer used tracking for timed practice

ALTER TABLE personal_practice_sessions 
ADD COLUMN timer_mode VARCHAR(20) DEFAULT NULL COMMENT 'Timer mode: none, per-question, or total',
ADD COLUMN timer_used_seconds INT DEFAULT NULL COMMENT 'Total time used in seconds';
