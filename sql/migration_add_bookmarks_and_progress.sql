-- Migration: Add bookmarks and material progress tracking
-- Date: 2026-06-07
-- Description: Add tables for bookmarking materials and tracking reading progress

-- Create bookmarks table
CREATE TABLE IF NOT EXISTS materi_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    materi_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_materi (user_id, materi_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (materi_id) REFERENCES materi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User bookmarks for learning materials';

-- Create material progress tracking table
CREATE TABLE IF NOT EXISTS materi_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    materi_id INT NOT NULL,
    progress_percent INT DEFAULT 0 COMMENT 'Reading progress percentage (0-100)',
    last_position INT DEFAULT 0 COMMENT 'Last scroll position in pixels',
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_materi (user_id, materi_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (materi_id) REFERENCES materi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User reading progress for materials';
