-- Migration: Add leaderboard badges
-- Date: 2026-06-07
-- Description: Add table for leaderboard badges/achievements

CREATE TABLE IF NOT EXISTS leaderboard_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_type VARCHAR(50) NOT NULL,
    badge_name VARCHAR(100) NOT NULL,
    badge_icon VARCHAR(50) DEFAULT '🏆',
    badge_color VARCHAR(20) DEFAULT '#f1c40f',
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    period_start DATE DEFAULT NULL COMMENT 'For time-based badges',
    period_end DATE DEFAULT NULL COMMENT 'For time-based badges',
    UNIQUE KEY unique_user_badge_period (user_id, badge_type, period_start),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Leaderboard badges and achievements';

-- Badge types:
-- top_1_weekly: Top 1 on weekly leaderboard
-- top_1_monthly: Top 1 on monthly leaderboard
-- most_improved: Most improved score in a period
-- highest_streak: Highest daily quiz streak
-- perfect_score: Perfect score on tryout
-- consistency: Completed daily quiz for 30 days straight
