-- Migration: Add tryout_packages table
-- Description: Add tryout packages system (Paket A: mudah, Paket B: sedang, Paket C: sulit)
-- Date: 2026-06-06

CREATE TABLE IF NOT EXISTS tryout_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    tingkat_kesulitan ENUM('mudah', 'sedang', 'sulit') NOT NULL,
    passing_grade_twk INT DEFAULT 65,
    passing_grade_tiu INT DEFAULT 80,
    passing_grade_tkp INT DEFAULT 126,
    durasi_twk INT DEFAULT 30,
    durasi_tiu INT DEFAULT 35,
    durasi_tkp INT DEFAULT 45,
    jumlah_soal_twk INT DEFAULT 30,
    jumlah_soal_tiu INT DEFAULT 30,
    jumlah_soal_tkp INT DEFAULT 35,
    aktif TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default packages
INSERT INTO tryout_packages (nama, deskripsi, tingkat_kesulitan, passing_grade_twk, passing_grade_tiu, passing_grade_tkp, durasi_twk, durasi_tiu, durasi_tkp, jumlah_soal_twk, jumlah_soal_tiu, jumlah_soal_tkp) VALUES
('Paket A (Mudah)', 'Paket soal dengan tingkat kesulitan mudah untuk pemula', 'mudah', 55, 70, 110, 25, 30, 40, 20, 20, 25),
('Paket B (Sedang)', 'Paket soal dengan tingkat kesulitan sedang', 'sedang', 65, 80, 126, 30, 35, 45, 30, 30, 35),
('Paket C (Sulit)', 'Paket soal dengan tingkat kesulitan sulit untuk persiapan maksimal', 'sulit', 75, 90, 140, 35, 40, 50, 35, 35, 40);

-- Add package_id column to tryout_sessions
ALTER TABLE tryout_sessions ADD COLUMN package_id INT NULL COMMENT 'Reference to tryout_packages table';
ALTER TABLE tryout_sessions ADD FOREIGN KEY (package_id) REFERENCES tryout_packages(id) ON DELETE SET NULL;
