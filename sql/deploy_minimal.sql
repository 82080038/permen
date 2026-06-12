-- =====================================================
-- SQL MINIMAL UNTUK FREE HOSTING (InfinityFree)
-- Tanpa VIEW, CHECK constraint, json_valid, FK complex
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- -----------------------------------------------------
-- 1. USERS (Wajib pertama untuk FK references)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_hp` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `target_instansi` varchar(100) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_hp` (`no_hp`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES 
(1,'081987654321','User Test',NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user',NULL,NULL,'active',NULL,'2025-06-13 00:00:00','2025-06-13 00:00:00'),
(2,'081234567890','Admin Test',NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',NULL,NULL,'active',NULL,'2025-06-13 00:00:00','2025-06-13 00:00:00');

-- -----------------------------------------------------
-- 2. QUESTIONS (Soal-soal)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `soal` text NOT NULL,
  `pilihan_a` varchar(255) DEFAULT NULL,
  `pilihan_b` varchar(255) DEFAULT NULL,
  `pilihan_c` varchar(255) DEFAULT NULL,
  `pilihan_d` varchar(255) DEFAULT NULL,
  `pilihan_e` varchar(255) DEFAULT NULL,
  `jawaban_benar` enum('A','B','C','D','E') DEFAULT NULL,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `topik` varchar(50) DEFAULT NULL,
  `tingkat_kesulitan` enum('mudah','sedang','sulit') DEFAULT 'sedang',
  `pembahasan` text DEFAULT NULL,
  `referensi` varchar(255) DEFAULT NULL,
  `is_smart_generated` tinyint(1) DEFAULT 0,
  `ai_cache_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','pending_review') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`),
  KEY `idx_topik` (`topik`),
  KEY `idx_status` (`status`),
  KEY `idx_difficulty` (`tingkat_kesulitan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 3. MATERI
-- -----------------------------------------------------
DROP TABLE IF EXISTS `materi`;
CREATE TABLE `materi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `konten` text NOT NULL,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`),
  KEY `idx_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 4. TRYOUT SESSIONS
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tryout_sessions`;
CREATE TABLE `tryout_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `waktu_mulai` datetime NOT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `durasi_total_menit` int(11) DEFAULT 0,
  `total_soal` int(11) DEFAULT 0,
  `total_benar` int(11) DEFAULT 0,
  `total_salah` int(11) DEFAULT 0,
  `total_tidak_jawab` int(11) DEFAULT 0,
  `skor_twk` decimal(5,2) DEFAULT 0.00,
  `skor_tiu` decimal(5,2) DEFAULT 0.00,
  `skor_tkp` decimal(5,2) DEFAULT 0.00,
  `skor_total` decimal(5,2) DEFAULT 0.00,
  `status` enum('ongoing','completed','abandoned','paused') DEFAULT 'ongoing',
  `catatan` text DEFAULT NULL,
  `is_smart_generated` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_waktu_mulai` (`waktu_mulai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 5. SESSION SUBTES
-- -----------------------------------------------------
DROP TABLE IF EXISTS `session_subtes`;
CREATE TABLE `session_subtes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `waktu_mulai` datetime NOT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `durasi_menit` int(11) DEFAULT 0,
  `jumlah_soal` int(11) DEFAULT 0,
  `jumlah_benar` int(11) DEFAULT 0,
  `jumlah_salah` int(11) DEFAULT 0,
  `jumlah_tidak_jawab` int(11) DEFAULT 0,
  `passing_grade` decimal(5,2) DEFAULT 0.00,
  `skor` decimal(5,2) DEFAULT 0.00,
  `status` enum('ongoing','completed','abandoned') DEFAULT 'ongoing',
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 6. ANSWERS
-- -----------------------------------------------------
DROP TABLE IF EXISTS `answers`;
CREATE TABLE `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `jawaban` enum('A','B','C','D','E') DEFAULT NULL,
  `is_ragu` tinyint(1) DEFAULT 0,
  `waktu_dijawab` datetime DEFAULT NULL,
  `durasi_menit` int(11) DEFAULT 0,
  `is_benar` tinyint(1) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session_question` (`session_id`,`question_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 7. TIPS TRICKS
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tips_tricks`;
CREATE TABLE `tips_tricks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `konten` text NOT NULL,
  `subtes` enum('TWK','TIU','TKP') DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 8. SUBTES CONFIG
-- -----------------------------------------------------
DROP TABLE IF EXISTS `subtes_config`;
CREATE TABLE `subtes_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `durasi_menit` int(11) NOT NULL,
  `jumlah_soal` int(11) NOT NULL,
  `passing_grade` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `subtes_config` (`subtes`, `durasi_menit`, `jumlah_soal`, `passing_grade`) VALUES
('TWK', 30, 30, 50.00),
('TIU', 35, 35, 45.00),
('TKP', 45, 45, 55.00);

-- -----------------------------------------------------
-- 9. LEARNING ANALYTICS (Simplified)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `learning_analytics`;
CREATE TABLE `learning_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `subtes` enum('TWK','TIU','TKP') DEFAULT NULL,
  `topik` varchar(50) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `waktu_menit` int(11) DEFAULT 0,
  `is_benar` tinyint(1) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 10. DAILY QUIZ (Simplified)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `daily_quiz`;
CREATE TABLE `daily_quiz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `question_ids` text DEFAULT NULL,
  `jawaban_user` text DEFAULT NULL,
  `skor` int(11) DEFAULT 0,
  `status` enum('ongoing','completed') DEFAULT 'ongoing',
  `waktu_mulai` datetime DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_date` (`user_id`,`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 11. PASSAGES (For reading comprehension)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `passages`;
CREATE TABLE `passages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) DEFAULT NULL,
  `konten` text NOT NULL,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 12. MASTER MATERI
-- -----------------------------------------------------
DROP TABLE IF EXISTS `master_materi`;
CREATE TABLE `master_materi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- CATATAN:
-- =====================================================
-- File ini adalah schema dasar tanpa:
-- - VIEWs (tidak support di free hosting)
-- - CHECK constraints (tidak support)
-- - json_valid() function (tidak support)
-- - Complex FOREIGN KEY (simpel saja)
--
-- Data soal (2.678 soal) perlu di-import terpisah
-- atau generate via aplikasi setelah setup.
-- =====================================================
