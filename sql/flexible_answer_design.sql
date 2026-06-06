-- Flexible Answer Design for Future Support
-- This design supports text, images, and other media types

-- Option 1: Add answer_type column to questions table
ALTER TABLE questions ADD COLUMN answer_type ENUM('text', 'image', 'audio', 'video', 'mixed') DEFAULT 'text';
ALTER TABLE questions ADD COLUMN answer_media_path VARCHAR(255) NULL COMMENT 'Path to media file if answer is not text';

-- Option 2: Create separate answer storage table (recommended for complex scenarios)
CREATE TABLE question_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_type ENUM('text', 'image', 'audio', 'video', 'mixed') NOT NULL DEFAULT 'text',
    answer_text VARCHAR(255) NULL COMMENT 'Text answer for text type',
    answer_media_path VARCHAR(255) NULL COMMENT 'Path to media file',
    answer_data JSON NULL COMMENT 'Additional data for complex answers',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_question_id (question_id)
);

-- Option 3: Use JSON column for flexible storage (modern approach)
ALTER TABLE questions ADD COLUMN answer_data JSON NULL COMMENT 'Flexible answer storage: {type: "text", value: "..."} or {type: "image", path: "..."}';

-- Example JSON structure for answer_data:
-- For text: {"type": "text", "value": "Jawaban yang benar"}
-- For image: {"type": "image", "path": "/uploads/answers/q123.png", "alt": "Description"}
-- For audio: {"type": "audio", "path": "/uploads/answers/q123.mp3", "duration": 5}
-- For mixed: {"type": "mixed", "items": [{"type": "text", "value": "A"}, {"type": "image", "path": "..."}]}

-- User answers also need to support flexible types
ALTER TABLE answers ADD COLUMN answer_type ENUM('text', 'image', 'audio', 'video', 'mixed') DEFAULT 'text';
ALTER TABLE answers ADD COLUMN answer_media_path VARCHAR(255) NULL;
ALTER TABLE answers ADD COLUMN answer_data JSON NULL;

ALTER TABLE daily_quiz_answers ADD COLUMN answer_type ENUM('text', 'image', 'audio', 'video', 'mixed') DEFAULT 'text';
ALTER TABLE daily_quiz_answers ADD COLUMN answer_media_path VARCHAR(255) NULL;
ALTER TABLE daily_quiz_answers ADD COLUMN answer_data JSON NULL;
