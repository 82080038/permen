-- Performance Optimization: Add Database Indexes
-- Migration: 20260616_add_performance_indexes.sql
-- Description: Add missing indexes to optimize query performance

-- Index for answers table (session_id, question_id)
CREATE INDEX IF NOT EXISTS idx_answers_session_question ON answers(session_id, question_id);

-- Index for questions table (subtes, topik)
CREATE INDEX IF NOT EXISTS idx_questions_subtes_topik ON questions(subtes, topik);

-- Index for questions table (is_active)
CREATE INDEX IF NOT EXISTS idx_questions_is_active ON questions(is_active);

-- Index for learning_analytics table (user_id, event_type)
CREATE INDEX IF NOT EXISTS idx_learning_analytics_user_event ON learning_analytics(user_id, event_type);

-- Index for api_rate_limits table (created_at)
CREATE INDEX IF NOT EXISTS idx_api_rate_limits_created ON api_rate_limits(created_at);

-- Index for tryout_sessions table (user_id, status)
CREATE INDEX IF NOT EXISTS idx_tryout_sessions_user_status ON tryout_sessions(user_id, status);

-- Index for tryout_sessions table (waktu_mulai)
CREATE INDEX IF NOT EXISTS idx_tryout_sessions_waktu_mulai ON tryout_sessions(waktu_mulai);

-- Index for passages table (id)
CREATE INDEX IF NOT EXISTS idx_passages_id ON passages(id);

-- Index for question_options table (question_id)
CREATE INDEX IF NOT EXISTS idx_question_options_question_id ON question_options(question_id);

-- Index for users table (email)
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- Index for users table (no_hp - unique index for phone number)
CREATE INDEX IF NOT EXISTS idx_users_no_hp ON users(no_hp);

-- Composite index for tryout_sessions (user_id, waktu_mulai) for dashboard queries
CREATE INDEX IF NOT EXISTS idx_tryout_sessions_user_time ON tryout_sessions(user_id, waktu_mulai);

-- Index for materi table (subtes, tipe)
CREATE INDEX IF NOT EXISTS idx_materi_subtes_tipe ON materi(subtes, tipe);

-- Index for tips table (subtes, tipe)
CREATE INDEX IF NOT EXISTS idx_tips_subtes_tipe ON tips(subtes, tipe);
