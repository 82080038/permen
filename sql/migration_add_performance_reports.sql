-- Migration: Add performance reports table
-- Date: 2026-06-07
-- Description: Add table for storing user performance reports

CREATE TABLE IF NOT EXISTS performance_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_type ENUM('weekly', 'monthly') NOT NULL,
    report_date DATE NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_tryouts INT DEFAULT 0,
    avg_tryout_score DECIMAL(5,2) DEFAULT 0,
    total_daily_quizzes INT DEFAULT 0,
    avg_daily_quiz_score DECIMAL(5,2) DEFAULT 0,
    current_streak INT DEFAULT 0,
    total_practice_sessions INT DEFAULT 0,
    recommendations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_period (user_id, report_type, report_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User performance reports';
