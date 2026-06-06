-- Migration: Add daily quiz streaks and achievements tracking
-- Date: 2026-06-07
-- Description: Add table for tracking daily quiz streaks and user achievements

CREATE TABLE IF NOT EXISTS daily_quiz_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    current_streak INT DEFAULT 0 COMMENT 'Current consecutive days',
    longest_streak INT DEFAULT 0 COMMENT 'Longest streak achieved',
    last_quiz_date DATE DEFAULT NULL COMMENT 'Last date user completed daily quiz',
    total_quizzes INT DEFAULT 0 COMMENT 'Total daily quizzes completed',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Daily quiz streak tracking';

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_type VARCHAR(50) NOT NULL COMMENT 'Type: streak_7, streak_30, streak_100, perfect_score, etc',
    achievement_name VARCHAR(100) NOT NULL,
    achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_achievement (user_id, achievement_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User achievements and badges';
