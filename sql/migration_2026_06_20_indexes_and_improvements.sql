-- Migration: Performance indexes and materi progress tracking
-- Date: 2026-06-20
-- Description: Add database indexes for performance, materi_progress table, migration tracking

-- ============================================================
-- 1. Performance indexes
-- ============================================================

-- Index for answers lookup by session and question (review queries)
CREATE INDEX IF NOT EXISTS idx_answers_session_question ON answers(session_id, question_id);

-- Index for answers by jawaban_user (accuracy per topic queries)
CREATE INDEX IF NOT EXISTS idx_answers_jawaban_user ON answers(jawaban_user);

-- Index for tryout_sessions by user, status, created_at (dashboard analytics)
CREATE INDEX IF NOT EXISTS idx_tryout_sessions_user_status ON tryout_sessions(user_id, status, created_at);

-- Index for tryout_sessions by status (admin dashboard stats)
CREATE INDEX IF NOT EXISTS idx_tryout_sessions_status ON tryout_sessions(status);

-- ============================================================
-- 2. Materi progress tracking table (if not exists)
-- ============================================================

CREATE TABLE IF NOT EXISTS materi_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    materi_id VARCHAR(255) NOT NULL,
    subtes VARCHAR(10) NOT NULL,
    progress_percent INT DEFAULT 0,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_materi (user_id, materi_id),
    INDEX idx_materi_progress_user (user_id),
    INDEX idx_materi_progress_subtes (subtes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. Rate limits table (if not exists - for login rate limiting)
-- ============================================================

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    count INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rate_limits_ip (ip),
    INDEX idx_rate_limits_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. Migration versioning table
-- ============================================================

CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Record this migration
INSERT IGNORE INTO schema_migrations (migration_name) VALUES ('migration_2026_06_20_indexes_and_improvements');
