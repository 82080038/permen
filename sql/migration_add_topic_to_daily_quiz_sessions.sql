-- Migration: Add topic columns to daily quiz sessions
-- Date: 2026-06-07
-- Description: Add columns to track scheduled subtes and topic for daily quiz

ALTER TABLE daily_quiz_sessions 
ADD COLUMN scheduled_subtes VARCHAR(10) DEFAULT NULL COMMENT 'Scheduled subtes for the day',
ADD COLUMN scheduled_topik VARCHAR(100) DEFAULT NULL COMMENT 'Scheduled topic for the day';
