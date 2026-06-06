-- Seed data for leaderboard testing
-- This file adds sample tryout sessions and results for leaderboard display

-- Insert sample users if not exists
INSERT INTO users (no_hp, nama, email, password, role, created_at) VALUES
('081111111111', 'Budi Santoso', 'budi@example.com', '$2y$10$placeholder', 'user', NOW()),
('081222222222', 'Siti Rahayu', 'siti@example.com', '$2y$10$placeholder', 'user', NOW()),
('081333333333', 'Ahmad Wijaya', 'ahmad@example.com', '$2y$10$placeholder', 'user', NOW()),
('081444444444', 'Dewi Lestari', 'dewi@example.com', '$2y$10$placeholder', 'user', NOW()),
('081555555555', 'Eko Prasetyo', 'eko@example.com', '$2y$10$placeholder', 'user', NOW())
ON DUPLICATE KEY UPDATE nama=VALUES(nama);

-- Get user IDs for tryout sessions
SET @user1 = (SELECT id FROM users WHERE no_hp = '081111111111' LIMIT 1);
SET @user2 = (SELECT id FROM users WHERE no_hp = '081222222222' LIMIT 1);
SET @user3 = (SELECT id FROM users WHERE no_hp = '081333333333' LIMIT 1);
SET @user4 = (SELECT id FROM users WHERE no_hp = '081444444444' LIMIT 1);
SET @user5 = (SELECT id FROM users WHERE no_hp = '081555555555' LIMIT 1);

-- Insert sample tryout sessions with completed status
INSERT INTO tryout_sessions (user_id, nama, waktu_mulai, waktu_selesai, status, nilai_twk, nilai_tiu, nilai_tkp, total_nilai) VALUES
(@user1, 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 'selesai', 75, 85, 165, 325),
(@user1, 'Try Out SKD #2', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), 'selesai', 70, 80, 160, 310),
(@user2, 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 'selesai', 80, 90, 170, 340),
(@user3, 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 'selesai', 65, 75, 155, 295),
(@user4, 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), 'selesai', 78, 88, 168, 334),
(@user5, 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 'selesai', 72, 82, 162, 316);

-- Insert session_subtes for each session
INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, nilai, urutan, waktu_mulai_subtes)
SELECT ts.id, 'TWK', 30, 30, 65, ts.nilai_twk, 1, ts.waktu_mulai
FROM tryout_sessions ts
WHERE ts.status = 'selesai'
ON DUPLICATE KEY UPDATE nilai=VALUES(nilai);

INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, nilai, urutan, waktu_mulai_subtes)
SELECT ts.id, 'TIU', 35, 35, 80, ts.nilai_tiu, 2, ts.waktu_mulai
FROM tryout_sessions ts
WHERE ts.status = 'selesai'
ON DUPLICATE KEY UPDATE nilai=VALUES(nilai);

INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, nilai, urutan, waktu_mulai_subtes)
SELECT ts.id, 'TKP', 45, 45, 126, ts.nilai_tkp, 3, ts.waktu_mulai
FROM tryout_sessions ts
WHERE ts.status = 'selesai'
ON DUPLICATE KEY UPDATE nilai=VALUES(nilai);
