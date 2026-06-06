-- Migration: Add soal versioning
-- Date: 2026-06-07
-- Description: Add table for soal versioning to track changes

CREATE TABLE IF NOT EXISTS soal_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soal_id INT NOT NULL,
    version INT NOT NULL,
    pertanyaan TEXT NOT NULL,
    pilihan_a TEXT NULL,
    pilihan_b TEXT NULL,
    pilihan_c TEXT NULL,
    pilihan_d TEXT NULL,
    pilihan_e TEXT NULL,
    jawaban_benar CHAR(1) NOT NULL,
    pembahasan TEXT NULL,
    tips TEXT NULL,
    related_links TEXT NULL,
    materi TEXT NULL,
    image_url TEXT NULL,
    bobot_tkp INT NULL,
    edited_by INT NULL COMMENT 'User ID who made the edit',
    edited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_soal_id (soal_id),
    INDEX idx_version (soal_id, version),
    FOREIGN KEY (soal_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Version history for questions';
