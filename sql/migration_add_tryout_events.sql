-- Migration: Add tryout event management
-- Date: 2026-06-07
-- Description: Add table for tryout events with user registration

CREATE TABLE IF NOT EXISTS tryout_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    tanggal_mulai DATETIME NOT NULL,
    tanggal_selesai DATETIME NOT NULL,
    paket_soal_id INT NULL,
    passing_grade_custom INT NULL COMMENT 'Custom passing grade for this event',
    max_participants INT NULL COMMENT 'Maximum participants (null = unlimited)',
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paket_soal_id) REFERENCES tryout_packages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tryout events for scheduled competitions';

CREATE TABLE IF NOT EXISTS tryout_event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_event (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES tryout_events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User registrations for tryout events';
