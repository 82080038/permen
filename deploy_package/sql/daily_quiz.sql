-- Tabel Daily Quiz
-- Sistem soal harian untuk drilling latihan

-- Tabel untuk menyimpan sesi daily quiz per user per hari
CREATE TABLE IF NOT EXISTS daily_quiz_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_date DATE NOT NULL,
    waktu_mulai DATETIME DEFAULT CURRENT_TIMESTAMP,
    waktu_selesai DATETIME NULL,
    status ENUM('berjalan', 'selesai') DEFAULT 'berjalan',
    total_soal INT DEFAULT 10,
    benar INT DEFAULT 0,
    salah INT DEFAULT 0,
    kosong INT DEFAULT 0,
    nilai_total INT DEFAULT 0,
    UNIQUE KEY unique_user_daily (user_id, quiz_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan soal-soal daily quiz
CREATE TABLE IF NOT EXISTS daily_quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    question_id INT NOT NULL,
    subtes VARCHAR(10) NOT NULL,
    urutan INT NOT NULL,
    FOREIGN KEY (session_id) REFERENCES daily_quiz_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan jawaban user
CREATE TABLE IF NOT EXISTS daily_quiz_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    question_id INT NOT NULL,
    jawaban_user CHAR(1) NULL,
    is_ragu TINYINT DEFAULT 0,
    waktu_jawab TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES daily_quiz_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_question (session_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
