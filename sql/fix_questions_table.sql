-- =====================================================
-- FIX TABLE QUESTIONS - Match dengan data existing
-- =====================================================

-- Hapus table lama
DROP TABLE IF EXISTS `questions`;

-- Buat table dengan struktur yang benar (sesuai data)
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subtes` enum('TWK','TIU','TKP') NOT NULL,
  `topik` varchar(50) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `tingkat_kesulitan` enum('mudah','sedang','sulit') DEFAULT 'sedang',
  `soal` text NOT NULL,
  `pilihan_a` varchar(255) DEFAULT NULL,
  `pilihan_b` varchar(255) DEFAULT NULL,
  `pilihan_c` varchar(255) DEFAULT NULL,
  `pilihan_d` varchar(255) DEFAULT NULL,
  `pilihan_e` varchar(255) DEFAULT NULL,
  `jawaban_benar` enum('A','B','C','D','E') DEFAULT NULL,
  `bobot_tkp` int(11) DEFAULT NULL,
  `pembahasan_singkat` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `gambar_url` varchar(255) DEFAULT NULL,
  `audio_url` varchar(255) DEFAULT NULL,
  `referensi` varchar(255) DEFAULT NULL,
  `pembahasan_lengkap` text DEFAULT NULL,
  `is_smart_generated` tinyint(1) DEFAULT 0,
  `smart_params` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ai_generated` tinyint(1) DEFAULT 0,
  `ai_cache_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','pending_review') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_subtes` (`subtes`),
  KEY `idx_topik` (`topik`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_tingkat_kesulitan` (`tingkat_kesulitan`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- SEKARANG import data_questions_hostinger.sql
-- =====================================================
