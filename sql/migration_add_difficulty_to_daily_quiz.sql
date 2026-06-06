-- Migration: Add difficulty progression to daily quiz
-- Date: 2026-06-07
-- Description: Add difficulty tracking and user difficulty level

ALTER TABLE daily_quiz_sessions 
ADD COLUMN difficulty ENUM('mudah', 'sedang', 'sulit') DEFAULT 'sedang' COMMENT 'Difficulty level of the quiz';

CREATE TABLE IF NOT EXISTS user_quiz_difficulty (
    user_id INT PRIMARY KEY,
    current_difficulty ENUM('mudah', 'sedang', 'sulit') DEFAULT 'sedang',
    consecutive_high_scores INT DEFAULT 0 COMMENT 'Count of consecutive high scores (>=80%)',
    consecutive_low_scores INT DEFAULT 0 COMMENT 'Count of consecutive low scores (<50%)',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User quiz difficulty progression tracking';
