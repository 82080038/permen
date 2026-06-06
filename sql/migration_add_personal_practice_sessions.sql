-- Migration: Add personal practice sessions table
-- Date: 2026-06-07
-- Description: Add table for tracking personal practice sessions with topic, count, and difficulty

CREATE TABLE IF NOT EXISTS personal_practice_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subtes VARCHAR(10) NOT NULL,
    topik VARCHAR(100) DEFAULT NULL,
    jumlah_soal INT NOT NULL,
    tingkat_kesulitan ENUM('mudah', 'sedang', 'sulit') DEFAULT 'sedang',
    benar INT DEFAULT 0,
    salah INT DEFAULT 0,
    skor INT DEFAULT 0,
    waktu_mulai TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waktu_selesai TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Personal practice session tracking';
