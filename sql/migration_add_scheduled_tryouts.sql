-- Migration: Add scheduled_tryouts table
-- Description: Add scheduled tryout system for admin-scheduled tryouts
-- Date: 2026-06-06

CREATE TABLE IF NOT EXISTS scheduled_tryouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    waktu_mulai DATETIME NOT NULL,
    durasi_menit INT DEFAULT 90,
    kuota INT DEFAULT 100,
    status ENUM('draft', 'published', 'started', 'completed', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scheduled_tryout_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheduled_tryout_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('registered', 'joined', 'completed', 'absent') DEFAULT 'registered',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_scheduled (scheduled_tryout_id, user_id),
    FOREIGN KEY (scheduled_tryout_id) REFERENCES scheduled_tryouts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
