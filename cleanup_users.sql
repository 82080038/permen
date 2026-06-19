SET FOREIGN_KEY_CHECKS=0;

-- Delete answers via session_id (answers.session_id -> tryout_sessions)
DELETE FROM answers WHERE session_id IN (SELECT id FROM tryout_sessions WHERE user_id IN (SELECT id FROM users WHERE role != 'admin'));
DELETE FROM session_subtes WHERE session_id IN (SELECT id FROM tryout_sessions WHERE user_id IN (SELECT id FROM users WHERE role != 'admin'));
DELETE FROM tryout_sessions WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');

-- Daily quiz
DELETE FROM daily_quiz_answers WHERE session_id IN (SELECT id FROM daily_quiz_sessions WHERE user_id IN (SELECT id FROM users WHERE role != 'admin'));
DELETE FROM daily_quiz_sessions WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM daily_quiz_streaks WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');

-- Learning & materi
DELETE FROM learning_analytics WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM learning_insights WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM materi_progress WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM materi_bookmarks WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');

-- Notifications
DELETE FROM notifications WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM push_subscriptions WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');

-- Practice & registration
DELETE FROM personal_practice_sessions WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM scheduled_tryout_registrations WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM tryout_event_registrations WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');

-- User data
DELETE FROM user_achievements WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM user_activity_log WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM user_audit_logs WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM user_feedback WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM user_quiz_difficulty WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');
DELETE FROM password_reset_requests WHERE user_id IN (SELECT id FROM users WHERE role != 'admin');

-- Delete the users themselves
DELETE FROM users WHERE role != 'admin';

SET FOREIGN_KEY_CHECKS=1;

-- Verify
SELECT id, nama, role FROM users;
