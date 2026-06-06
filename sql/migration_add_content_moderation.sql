-- Migration: Add content moderation
-- Date: 2026-06-07
-- Description: Add table for content moderation queue

CREATE TABLE IF NOT EXISTS content_moderation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('question', 'materi', 'comment') NOT NULL,
    content_id INT NOT NULL,
    reporter_id INT NULL COMMENT 'User who reported the content (null if auto-flagged)',
    reason TEXT NULL COMMENT 'Reason for flagging',
    status ENUM('pending', 'approved', 'rejected', 'deleted') DEFAULT 'pending',
    moderator_id INT NULL COMMENT 'Admin who reviewed',
    moderator_note TEXT NULL COMMENT 'Note from moderator',
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_content (content_type, content_id),
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Content moderation queue for flagged content';
