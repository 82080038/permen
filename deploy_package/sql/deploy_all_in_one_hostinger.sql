-- =====================================================
-- SQL ALL-IN-ONE UNTUK HOSTINGER
-- Include: Schema Exact + Data 2.678 Soal
-- Database: u950781813_skd_cat_bkn
-- Website: bimbel.bereng.info
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

-- =====================================================
-- PART 1: DROP & CREATE ALL TABLES (EXACT STRUCTURE)
-- =====================================================

-- -----------------------------------------------------
-- USERS
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_hp` (`no_hp`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- SUBTES CONFIG
-- -----------------------------------------------------
DROP TABLE IF EXISTS `subtes_config`;
CREATE TABLE `subtes_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `durasi_menit` int(11) NOT NULL,
  `jumlah_soal` int(11) NOT NULL,
  `passing_grade` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- QUESTIONS (EXACT STRUCTURE FROM LOCAL DATABASE)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subtes` enum('TKP','TIU','TWK') NOT NULL,
  `tipe` varchar(20) DEFAULT NULL,
  `topik` varchar(100) NOT NULL,
  `difficulty` enum('mudah','sedang','sulit') DEFAULT 'sedang',
  `pertanyaan` text NOT NULL,
  `pilihan_a` text NOT NULL,
  `pilihan_b` text NOT NULL,
  `pilihan_c` text NOT NULL,
  `pilihan_d` text NOT NULL,
  `pilihan_e` text NOT NULL,
  `jawaban_benar` varchar(255) DEFAULT NULL,
  `bobot_tkp` int(11) DEFAULT NULL,
  `bobot_a` int(11) DEFAULT NULL,
  `bobot_b` int(11) DEFAULT NULL,
  `bobot_c` int(11) DEFAULT NULL,
  `bobot_d` int(11) DEFAULT NULL,
  `bobot_e` int(11) DEFAULT NULL,
  `pembahasan` text NOT NULL,
  `tips_trick` text DEFAULT NULL,
  `related_links` longtext DEFAULT NULL,
  `materi_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `passage_id` int(11) DEFAULT NULL,
  `passage_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `needs_revision` tinyint(1) NOT NULL DEFAULT 0,
  `revision_status` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pertanyaan` (`pertanyaan`(255)),
  KEY `idx_subtes` (`subtes`),
  KEY `idx_passage_id` (`passage_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_needs_revision` (`needs_revision`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- MATERI
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`),
  KEY `idx_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- TRYOUT SESSIONS
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_waktu_mulai` (`waktu_mulai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- SESSION SUBTES
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
-- ANSWERS
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session_question` (`session_id`,`question_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- TIPS TRICKS
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- LEARNING ANALYTICS
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- DAILY QUIZ
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_date` (`user_id`,`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- PASSAGES
-- -----------------------------------------------------
DROP TABLE IF EXISTS `passages`;
CREATE TABLE `passages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) DEFAULT NULL,
  `konten` text NOT NULL,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- MASTER MATERI
-- -----------------------------------------------------
DROP TABLE IF EXISTS `master_materi`;
CREATE TABLE `master_materi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- INSTANSI
-- -----------------------------------------------------
DROP TABLE IF EXISTS `instansi`;
CREATE TABLE `instansi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- PART 2: DATA MINIMAL (Users & Config)
-- =====================================================

INSERT INTO `users` (`no_hp`, `nama`, `password`, `role`, `status`, `created_at`) VALUES 
('081987654321', 'User Test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', NOW()),
('081234567890', 'Admin Test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NOW());

INSERT INTO `subtes_config` (`subtes`, `durasi_menit`, `jumlah_soal`, `passing_grade`) VALUES
('TWK', 30, 30, 50.00),
('TIU', 35, 35, 45.00),
('TKP', 45, 45, 55.00);

-- =====================================================
-- PART 3: DATA SOAL (Import terpisah atau gunakan BigDump)
-- =====================================================
-- Data soal 2.678 soal di-import menggunakan:
-- 1. File data_questions_hostinger.sql (upload terpisah)
-- 2. Atau gunakan BigDump PHP untuk import bertahap
-- =====================================================

-- =====================================================
-- SELESAI - Database Ready!
-- =====================================================
-- Test Login:
-- User: 081987654321 / Password: Sihaloho1982
-- Admin: 081234567890 / Password: Sihaloho1982
-- =====================================================
