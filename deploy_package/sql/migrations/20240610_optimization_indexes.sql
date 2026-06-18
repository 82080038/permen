-- Migration: Database Optimization - Indexes and Cleanup
-- Date: 2026-06-10
-- Author: Cascade AI

-- ============================================
-- 1. Add Missing Indexes for Performance
-- ============================================

-- Index for questions queries by subtes and topik (commonly used in get_soal.php)
CREATE INDEX IF NOT EXISTS idx_questions_subtes_topik ON questions(subtes, topik);
CREATE INDEX IF NOT EXISTS idx_questions_is_active ON questions(is_active);
CREATE INDEX IF NOT EXISTS idx_questions_created ON questions(created_at);

-- Index for answers table
CREATE INDEX IF NOT EXISTS idx_answers_session ON answers(session_id);
CREATE INDEX IF NOT EXISTS idx_answers_question ON answers(question_id);

-- Index for learning analytics
CREATE INDEX IF NOT EXISTS idx_learning_analytics_user ON learning_analytics(user_id);
CREATE INDEX IF NOT EXISTS idx_learning_analytics_event ON learning_analytics(event_type);
CREATE INDEX IF NOT EXISTS idx_learning_analytics_created ON learning_analytics(created_at);

-- Index for rate limiting cleanup
CREATE INDEX IF NOT EXISTS idx_api_rate_limits_created ON api_rate_limits(created_at);
CREATE INDEX IF NOT EXISTS idx_rate_limits_ip ON rate_limits(ip);

-- Index for user audit logs
CREATE INDEX IF NOT EXISTS idx_user_audit_logs_user ON user_audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_user_audit_logs_action ON user_audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_user_audit_logs_created ON user_audit_logs(created_at);

-- Index for notifications
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read);

-- Index for tryout sessions
CREATE INDEX IF NOT EXISTS idx_tryout_sessions_user ON tryout_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_tryout_sessions_status ON tryout_sessions(status);

-- ============================================
-- 2. Cleanup: Drop Unused Table 'tips'
-- ============================================
-- Table 'tips' is empty and duplicated by 'tips_tricks'
DROP TABLE IF EXISTS `tips`;

-- ============================================
-- 3. Cleanup: Remove Deprecated Column
-- ============================================
-- Column 'email' in users table is deprecated (using no_hp instead)
-- Note: Only run this after confirming no code uses email column
-- ALTER TABLE users DROP COLUMN email;

-- ============================================
-- 4. Add Composite Index for Common Queries
-- ============================================

-- For get_soal.php - questions by subtes with active status
CREATE INDEX IF NOT EXISTS idx_questions_subtes_active ON questions(subtes, is_active, created_at);

-- For admin queries - questions needing revision
CREATE INDEX IF NOT EXISTS idx_questions_revision ON questions(needs_revision, revision_status);

-- ============================================
-- 5. Verify Indexes Created
-- ============================================
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    CARDINALITY
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND INDEX_NAME LIKE 'idx_%'
ORDER BY TABLE_NAME, INDEX_NAME;
