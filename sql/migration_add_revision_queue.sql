-- Migration: Add revision queue system
-- Date: 2026-06-07
-- Description: Add table for revision queue management

CREATE TABLE IF NOT EXISTS revision_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soal_id INT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    assigned_to INT NULL COMMENT 'User ID assigned to revision',
    assigned_by INT NULL COMMENT 'Admin who assigned',
    assigned_at TIMESTAMP NULL,
    reason TEXT NULL COMMENT 'Why this soal needs revision',
    admin_notes TEXT NULL COMMENT 'Admin notes/comments',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_soal_id (soal_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_assigned_to (assigned_to),
    FOREIGN KEY (soal_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Revision queue for soal that need review';
