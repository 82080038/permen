-- Migration: Add soal tagging system
-- Date: 2026-06-07
-- Description: Add tables for soal tags

CREATE TABLE IF NOT EXISTS soal_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(50) NOT NULL UNIQUE,
    tag_color VARCHAR(20) DEFAULT '#2980b9',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Available tags for questions';

CREATE TABLE IF NOT EXISTS soal_tag_relations (
    soal_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (soal_id, tag_id),
    FOREIGN KEY (soal_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES soal_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tag relations for questions';

-- Insert default tags
INSERT INTO soal_tags (tag_name, tag_color) VALUES 
('sulit', '#e74c3c'),
('populer', '#f39c12'),
('baru', '#27ae60'),
('figural', '#9b59b6'),
('verbal', '#3498db'),
('numerik', '#1abc9c'),
('sering_ditanya', '#e67e22');
