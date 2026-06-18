-- Add missing tryout-related tables to production database
-- Run this on Hostinger production database

-- Table: tryout_sessions
CREATE TABLE IF NOT EXISTS `tryout_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `status` enum('berjalan','selesai','dibatalkan','ongoing','completed') DEFAULT 'berjalan',
  `score_twk` int(11) DEFAULT 0,
  `score_tiu` int(11) DEFAULT 0,
  `score_tkp` int(11) DEFAULT 0,
  `score_total` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: session_subtes
CREATE TABLE IF NOT EXISTS `session_subtes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `durasi_menit` int(11) DEFAULT 30,
  `jumlah_soal` int(11) DEFAULT 0,
  `passing_grade` int(11) DEFAULT 0,
  `urutan` int(11) DEFAULT 1,
  `waktu_mulai_subtes` datetime DEFAULT NULL,
  `waktu_selesai_subtes` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_subtes` (`subtes`),
  CONSTRAINT `fk_session_subtes_session` FOREIGN KEY (`session_id`) REFERENCES `tryout_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: subtes_config (check if exists first)
-- Only create if it doesn't exist
CREATE TABLE IF NOT EXISTS `subtes_config_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `durasi_menit` int(11) DEFAULT 30,
  `jumlah_soal` int(11) DEFAULT 0,
  `passing_grade` int(11) DEFAULT 0,
  `urutan` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `aktif` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default subtes configuration into new table
INSERT IGNORE INTO `subtes_config_new` (`subtes`, `durasi_menit`, `jumlah_soal`, `passing_grade`, `urutan`, `is_active`, `aktif`) VALUES
('TWK', 30, 35, 65, 1, 1, 1),
('TIU', 35, 30, 70, 2, 1, 1),
('TKP', 45, 30, 166, 3, 1, 1);

-- Rename if original doesn't exist
DROP TABLE IF EXISTS `subtes_config`;
RENAME TABLE `subtes_config_new` TO `subtes_config`;

-- Table: tryout_packages
CREATE TABLE IF NOT EXISTS `tryout_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `deskripsi` text,
  `durasi_twk` int(11) DEFAULT 30,
  `durasi_tiu` int(11) DEFAULT 35,
  `durasi_tkp` int(11) DEFAULT 45,
  `jumlah_soal_twk` int(11) DEFAULT 35,
  `jumlah_soal_tiu` int(11) DEFAULT 30,
  `jumlah_soal_tkp` int(11) DEFAULT 30,
  `passing_grade_twk` int(11) DEFAULT 65,
  `passing_grade_tiu` int(11) DEFAULT 70,
  `passing_grade_tkp` int(11) DEFAULT 166,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default tryout package
INSERT IGNORE INTO `tryout_packages` (`nama`, `deskripsi`, `durasi_twk`, `durasi_tiu`, `durasi_tkp`, `jumlah_soal_twk`, `jumlah_soal_tiu`, `jumlah_soal_tkp`, `passing_grade_twk`, `passing_grade_tiu`, `passing_grade_tkp`, `aktif`) VALUES
('Try Out SKD Standar', 'Paket try out standar SKD CAT-BKN', 30, 35, 45, 35, 30, 30, 65, 70, 166, 1);

-- Table: scheduled_tryouts
CREATE TABLE IF NOT EXISTS `scheduled_tryouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `deskripsi` text,
  `waktu_mulai` datetime NOT NULL,
  `durasi_menit` int(11) DEFAULT 110,
  `kuota` int(11) DEFAULT 100,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: scheduled_tryout_registrations
CREATE TABLE IF NOT EXISTS `scheduled_tryout_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scheduled_tryout_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('registered','joined','cancelled') DEFAULT 'registered',
  `registered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scheduled_tryout_id` (`scheduled_tryout_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `scheduled_tryout_registrations_ibfk_1` FOREIGN KEY (`scheduled_tryout_id`) REFERENCES `scheduled_tryouts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: answers (for storing user answers)
CREATE TABLE IF NOT EXISTS `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `answered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_question_id` (`question_id`),
  CONSTRAINT `fk_answers_session` FOREIGN KEY (`session_id`) REFERENCES `tryout_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
