-- Migration: Add daily quiz topic schedule
-- Date: 2026-06-07
-- Description: Add table for scheduling daily quiz topics by day of week

CREATE TABLE IF NOT EXISTS daily_quiz_topic_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
    subtes VARCHAR(10) NOT NULL,
    topik VARCHAR(100) NOT NULL,
    description TEXT,
    UNIQUE KEY unique_day (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Daily quiz topic schedule by day';

-- Insert weekly schedule
INSERT INTO daily_quiz_topic_schedule (day_of_week, subtes, topik, description) VALUES
(1, 'TWK', 'Nasionalisme', 'Senin: Fokus pada nilai-nilai nasionalisme dan cinta tanah air'),
(2, 'TIU', 'Verbal', 'Selasa: Fokus pada kemampuan verbal (analogi, silogisme, analitis)'),
(3, 'TKP', 'Pelayanan Publik', 'Rabu: Fokus pada pelayanan publik dan profesionalisme'),
(4, 'TWK', 'Pancasila', 'Kamis: Fokus pada Pancasila dan ideologi negara'),
(5, 'TIU', 'Numerik', 'Jumat: Fokus pada kemampuan numerik (berhitung, deret, perbandingan)'),
(6, 'TKP', 'Sosial Budaya', 'Sabtu: Fokus pada sosial budaya dan jejaring kerja'),
(0, 'Mixed', 'Campuran', 'Minggu: Campuran semua subtes untuk review mingguan');
