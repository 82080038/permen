-- ========================================================
-- SKD CAT-BKN Complete Database Export
-- Date: 2026-06-09
-- Version: 1.1.0 (Post-Implementation)
-- Description: Full database schema with all optimizations
-- ========================================================

-- ========================================================
-- 1. DATABASE SETUP
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ========================================================
-- 2. DROP EXISTING TABLES (CLEAN INSTALL)
-- ========================================================

DROP TABLE IF EXISTS `answers`, `daily_quiz_sessions`, `daily_quiz_answers`, `learning_analytics`;
DROP TABLE IF EXISTS `materi`, `materi_progress`, `notifications`, `password_reset_requests`;
DROP TABLE IF EXISTS `personal_practice_sessions`, `practice_answers`, `questions`;
DROP TABLE IF EXISTS `session_subtes`, `subtes_config`, `tips_tricks`, `tryout_packages`;
DROP TABLE IF EXISTS `tryout_package_questions`, `tryout_sessions`, `user_audit_logs`;
DROP TABLE IF EXISTS `user_badges`, `user_bookmarks`, `user_feedback`, `user_settings`;
DROP TABLE IF EXISTS `users`, `api_rate_limits`, `rate_limits`, `content_moderation_queue`;
DROP TABLE IF EXISTS `question_versions`, `soal_tags`, `tagging`, `media_library`;

-- Note: 'tips' table has been deprecated and dropped
-- Note: Email column removed from users (using no_hp only)

-- ========================================================
-- 3. CORE TABLES
-- ========================================================

-- Users Table (Email column removed - using no_hp)
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `no_hp` VARCHAR(20) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('user','admin') DEFAULT 'user',
    `sekolah_asal` VARCHAR(100),
    `tahun_tamat` YEAR,
    `instansi` VARCHAR(100),
    `status` ENUM('active','inactive','banned') DEFAULT 'active',
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME NULL,
    `reset_token` VARCHAR(64) NULL,
    `reset_expires` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`),
    INDEX `idx_users_no_hp` (`no_hp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Questions Table
CREATE TABLE `questions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subtes` ENUM('TWK','TIU','TKP') NOT NULL,
    `topik` VARCHAR(50),
    `pertanyaan` TEXT NOT NULL,
    `jawaban_a` TEXT,
    `jawaban_b` TEXT,
    `jawaban_c` TEXT,
    `jawaban_d` TEXT,
    `jawaban_e` TEXT,
    `jawaban_benar` ENUM('A','B','C','D','E') NOT NULL,
    `bobot_tkp` TINYINT DEFAULT 0,
    `bobot_a` TINYINT DEFAULT 0,
    `bobot_b` TINYINT DEFAULT 0,
    `bobot_c` TINYINT DEFAULT 0,
    `bobot_d` TINYINT DEFAULT 0,
    `bobot_e` TINYINT DEFAULT 0,
    `gambar_url` VARCHAR(255),
    `pembahasan` TEXT,
    `is_active` BOOLEAN DEFAULT TRUE,
    `needs_revision` BOOLEAN DEFAULT FALSE,
    `revision_status` ENUM('pending','approved','rejected') DEFAULT NULL,
    `difficulty` ENUM('easy','medium','hard') DEFAULT 'medium',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_questions_subtes_topik` (`subtes`, `topik`),
    INDEX `idx_questions_is_active` (`is_active`),
    INDEX `idx_questions_subtes_active` (`subtes`, `is_active`, `created_at`),
    INDEX `idx_questions_revision` (`needs_revision`, `revision_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tryout Sessions Table
CREATE TABLE `tryout_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `nama` VARCHAR(100),
    `status` ENUM('berjalan','selesai','paused') DEFAULT 'berjalan',
    `waktu_mulai` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `waktu_selesai` DATETIME,
    `total_nilai` INT DEFAULT 0,
    `nilai_twk` INT DEFAULT 0,
    `nilai_tiu` INT DEFAULT 0,
    `nilai_tkp` INT DEFAULT 0,
    `passing_grade_twk` INT DEFAULT 65,
    `passing_grade_tiu` INT DEFAULT 80,
    `passing_grade_tkp` INT DEFAULT 166,
    `total_durasi_menit` INT DEFAULT 0,
    `durasi_twk` INT DEFAULT 30,
    `durasi_tiu` INT DEFAULT 35,
    `durasi_tkp` INT DEFAULT 25,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_tryout_sessions_user` (`user_id`),
    INDEX `idx_tryout_sessions_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Answers Table
CREATE TABLE `answers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT UNSIGNED NOT NULL,
    `question_id` INT UNSIGNED NOT NULL,
    `jawaban_user` ENUM('A','B','C','D','E'),
    `skor` INT DEFAULT 0,
    `is_ragu` BOOLEAN DEFAULT FALSE,
    `waktu_jawab` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`session_id`) REFERENCES `tryout_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    INDEX `idx_answers_session` (`session_id`),
    INDEX `idx_answers_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session Subtes Table
CREATE TABLE `session_subtes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT UNSIGNED NOT NULL,
    `subtes` ENUM('TWK','TIU','TKP') NOT NULL,
    `waktu_mulai_subtes` DATETIME,
    `waktu_selesai_subtes` DATETIME,
    `durasi_menit` INT,
    `status` ENUM('berjalan','selesai') DEFAULT 'berjalan',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`session_id`) REFERENCES `tryout_sessions`(`id`) ON DELETE CASCADE,
    INDEX `idx_session_subtes_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subtes Config Table
CREATE TABLE `subtes_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subtes` ENUM('TWK','TIU','TKP') NOT NULL UNIQUE,
    `durasi_menit` INT DEFAULT 30,
    `jumlah_soal` INT DEFAULT 35,
    `passing_grade` INT DEFAULT 65,
    `urutan` TINYINT DEFAULT 1,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 4. FEATURE TABLES
-- ========================================================

-- Daily Quiz Sessions
CREATE TABLE `daily_quiz_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `quiz_date` DATE NOT NULL,
    `status` ENUM('berjalan','selesai') DEFAULT 'berjalan',
    `waktu_mulai` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `waktu_selesai` DATETIME,
    `total_nilai` INT DEFAULT 0,
    `streak` INT DEFAULT 0,
    `topic` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_daily_quiz_user` (`user_id`),
    INDEX `idx_daily_quiz_date` (`quiz_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily Quiz Answers
CREATE TABLE `daily_quiz_answers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT UNSIGNED NOT NULL,
    `question_id` INT UNSIGNED NOT NULL,
    `jawaban_user` ENUM('A','B','C','D','E'),
    `skor` INT DEFAULT 0,
    `waktu_jawab` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`session_id`) REFERENCES `daily_quiz_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Learning Analytics
CREATE TABLE `learning_analytics` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `event_data` JSON,
    `session_id` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_learning_analytics_user` (`user_id`),
    INDEX `idx_learning_analytics_event` (`event_type`),
    INDEX `idx_learning_analytics_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50),
    `title` VARCHAR(100),
    `message` TEXT,
    `is_read` BOOLEAN DEFAULT FALSE,
    `link` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notifications_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Bookmarks
CREATE TABLE `user_bookmarks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `question_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_bookmark` (`user_id`, `question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Feedback
CREATE TABLE `user_feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `category` ENUM('saran','kritik','bug','fitur','lainnya') DEFAULT 'lainnya',
    `message` TEXT NOT NULL,
    `status` ENUM('pending','dilihat','diproses','selesai','ditolak') DEFAULT 'pending',
    `admin_response` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Audit Logs
CREATE TABLE `user_audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `target_type` VARCHAR(50),
    `target_id` INT,
    `details` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_audit_logs_user` (`user_id`),
    INDEX `idx_user_audit_logs_action` (`action`),
    INDEX `idx_user_audit_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Rate Limits
CREATE TABLE `api_rate_limits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(100) NOT NULL,
    `endpoint` VARCHAR(100),
    `request_count` INT DEFAULT 0,
    `window_start` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_api_rate_limits_identifier` (`identifier`),
    INDEX `idx_api_rate_limits_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate Limits (Legacy - for backward compatibility)
CREATE TABLE `rate_limits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) NOT NULL,
    `action` VARCHAR(50),
    `attempts` INT DEFAULT 0,
    `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `blocked_until` TIMESTAMP NULL,
    INDEX `idx_rate_limits_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 5. MATERI TABLES
-- ========================================================

-- Materi (Learning Materials)
CREATE TABLE `materi` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subtes` ENUM('TWK','TIU','TKP') NOT NULL,
    `judul` VARCHAR(200) NOT NULL,
    `konten` LONGTEXT,
    `urutan` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_materi_subtes` (`subtes`, `urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Materi Progress
CREATE TABLE `materi_progress` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `materi_id` INT UNSIGNED NOT NULL,
    `is_completed` BOOLEAN DEFAULT FALSE,
    `last_read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`materi_id`) REFERENCES `materi`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_progress` (`user_id`, `materi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tips & Tricks
CREATE TABLE `tips_tricks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kategori` ENUM('TWK','TIU','TKP','umum') DEFAULT 'umum',
    `judul` VARCHAR(200),
    `konten` TEXT,
    `aktif` BOOLEAN DEFAULT TRUE,
    `urutan` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 6. INSERT DEFAULT DATA
-- ========================================================

-- Default Subtes Config
INSERT INTO `subtes_config` (`subtes`, `durasi_menit`, `jumlah_soal`, `passing_grade`, `urutan`) VALUES
('TWK', 30, 30, 65, 1),
('TIU', 35, 35, 80, 2),
('TKP', 25, 35, 166, 3);

-- ========================================================
-- 7. VIEWS (for backward compatibility)
-- ========================================================

-- View for total soal per subtes
CREATE OR REPLACE VIEW `view_soal_per_subtes` AS
SELECT subtes, COUNT(*) as jumlah 
FROM questions 
WHERE is_active = TRUE 
GROUP BY subtes;

-- View for user performance summary
CREATE OR REPLACE VIEW `view_user_performance` AS
SELECT 
    u.id as user_id,
    u.nama,
    COUNT(DISTINCT ts.id) as total_tryout,
    AVG(ts.total_nilai) as avg_score,
    MAX(ts.total_nilai) as max_score
FROM users u
LEFT JOIN tryout_sessions ts ON u.id = ts.user_id AND ts.status = 'selesai'
WHERE u.role = 'user'
GROUP BY u.id;

-- ========================================================
-- 8. POST-MIGRATION NOTES
-- ========================================================

/*
POST-IMPLEMENTATION NOTES:
========================

1. INDEXES ADDED (Performance Optimization):
   - idx_questions_subtes_topik: For get_soal queries
   - idx_questions_is_active: For filtering active questions
   - idx_answers_session: For session answer queries
   - idx_learning_analytics_user: For analytics queries
   - idx_notifications_user_read: For notification queries
   - idx_tryout_sessions_user: For user session queries

2. SECURITY IMPROVEMENTS:
   - Email column removed from users (using no_hp)
   - 'tips' table dropped (replaced by tips_tricks)
   - Rate limiting tables added
   - Audit logging enabled

3. DEPRECATED:
   - Table 'tips' has been removed (replaced by tips_tricks)
   - Column 'email' removed from users table

4. RUN THIS AFTER IMPORT:
   
   -- Verify indexes
   SHOW INDEX FROM questions;
   SHOW INDEX FROM answers;
   SHOW INDEX FROM tryout_sessions;
   
   -- Verify tables
   SHOW TABLES;
   
   -- Check view
   SELECT * FROM view_soal_per_subtes;
*/

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- END OF EXPORT
-- ========================================================
