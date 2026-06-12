-- Database sederhana untuk free hosting
-- Dibuat: 2025-06-13
-- Compatibility: MySQL 5.6+

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- Table: users
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id int(11) NOT NULL AUTO_INCREMENT,
  no_hp varchar(20) NOT NULL,
  nama varchar(100) NOT NULL,
  password varchar(255) NOT NULL,
  role enum('user','admin') DEFAULT 'user',
  target_instansi varchar(100) DEFAULT NULL,
  status enum('active','inactive') DEFAULT 'active',
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY no_hp (no_hp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: questions
DROP TABLE IF EXISTS questions;
CREATE TABLE questions (
  id int(11) NOT NULL AUTO_INCREMENT,
  soal text NOT NULL,
  pilihan_a varchar(255) DEFAULT NULL,
  pilihan_b varchar(255) DEFAULT NULL,
  pilihan_c varchar(255) DEFAULT NULL,
  pilihan_d varchar(255) DEFAULT NULL,
  pilihan_e varchar(255) DEFAULT NULL,
  jawaban_benar enum('A','B','C','D','E') DEFAULT NULL,
  subtes enum('TWK','TIU','TKP') NOT NULL,
  topik varchar(50) DEFAULT NULL,
  pembahasan text DEFAULT NULL,
  is_smart_generated tinyint(1) DEFAULT 0,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_subtes (subtes),
  KEY idx_topik (topik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: materi
DROP TABLE IF EXISTS materi;
CREATE TABLE materi (
  id int(11) NOT NULL AUTO_INCREMENT,
  judul varchar(255) NOT NULL,
  konten text NOT NULL,
  subtes enum('TWK','TIU','TKP') NOT NULL,
  kategori varchar(50) DEFAULT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_subtes (subtes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: tryout_sessions
DROP TABLE IF EXISTS tryout_sessions;
CREATE TABLE tryout_sessions (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  nama varchar(100) NOT NULL,
  waktu_mulai datetime NOT NULL,
  waktu_selesai datetime DEFAULT NULL,
  status enum('ongoing','completed','abandoned') DEFAULT 'ongoing',
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_id (user_id),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: session_subtes
DROP TABLE IF EXISTS session_subtes;
CREATE TABLE session_subtes (
  id int(11) NOT NULL AUTO_INCREMENT,
  session_id int(11) NOT NULL,
  subtes enum('TWK','TIU','TKP') NOT NULL,
  waktu_mulai datetime NOT NULL,
  waktu_selesai datetime DEFAULT NULL,
  durasi_menit int(11) DEFAULT 0,
  jumlah_soal int(11) DEFAULT 0,
  passing_grade decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (id),
  KEY idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: answers
DROP TABLE IF EXISTS answers;
CREATE TABLE answers (
  id int(11) NOT NULL AUTO_INCREMENT,
  session_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  question_id int(11) NOT NULL,
  jawaban enum('A','B','C','D','E') DEFAULT NULL,
  is_ragu tinyint(1) DEFAULT 0,
  waktu_dijawab datetime DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_session_question (session_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert test users
INSERT INTO users (no_hp, nama, password, role) VALUES
('081987654321', 'User Test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('081234567890', 'Admin Test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

SET FOREIGN_KEY_CHECKS=1;
