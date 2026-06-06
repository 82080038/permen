-- Tambah kolom difficulty ke tabel questions
-- Jalankan di phpMyAdmin atau via command line

ALTER TABLE questions ADD COLUMN difficulty ENUM('mudah', 'sedang', 'sulit') DEFAULT 'sedang' AFTER topik;

-- Verifikasi kolom ditambahkan
DESCRIBE questions;
