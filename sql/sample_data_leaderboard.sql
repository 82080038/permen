-- Sample Data for Leaderboard Testing
-- Run this to populate sample tryout data for leaderboard demo
-- 
-- Usage: /opt/lampp/bin/mysql -u root -p skd_cat_bkn < sql/sample_data_leaderboard.sql

-- Insert sample users (if not exists)
INSERT INTO users (nama, no_hp, email, password_hash, role, sekolah_asal, instansi, created_at) VALUES
('Budi Santoso', '081234567891', 'budi.santoso@email.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'SMA Negeri 1 Jakarta', 'STAN', NOW()),
('Ani Wulandari', '081234567892', 'ani.wulandari@email.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'SMA Negeri 2 Surabaya', 'IPDN', NOW()),
('Dedi Kurniawan', '081234567893', 'dedi.kurniawan@email.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'SMA Negeri 3 Bandung', 'STIS', NOW()),
('Eka Putri', '081234567894', 'eka.putri@email.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'SMA Negeri 4 Yogyakarta', 'STAN', NOW()),
('Fajar Nugraha', '081234567895', 'fajar.nugraha@email.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'SMA Negeri 5 Semarang', 'IPDN', NOW())
ON DUPLICATE KEY UPDATE nama=VALUES(nama);

-- Insert sample tryout sessions (completed)
INSERT INTO tryout_sessions (user_id, nama, waktu_mulai, status, total_nilai, nilai_twk, nilai_tiu, nilai_tkp) VALUES
((SELECT id FROM users WHERE no_hp='081234567891'), 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 2 DAY), 'selesai', 285, 75, 85, 125),
((SELECT id FROM users WHERE no_hp='081234567891'), 'Try Out SKD #2', DATE_SUB(NOW(), INTERVAL 1 DAY), 'selesai', 292, 78, 88, 126),
((SELECT id FROM users WHERE no_hp='081234567892'), 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 3 DAY), 'selesai', 310, 80, 90, 140),
((SELECT id FROM users WHERE no_hp='081234567892'), 'Try Out SKD #2', DATE_SUB(NOW(), INTERVAL 1 DAY), 'selesai', 315, 82, 92, 141),
((SELECT id FROM users WHERE no_hp='081234567893'), 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 5 DAY), 'selesai', 275, 72, 82, 121),
((SELECT id FROM users WHERE no_hp='081234567893'), 'Try Out SKD #2', DATE_SUB(NOW(), INTERVAL 2 DAY), 'selesai', 280, 74, 84, 122),
((SELECT id FROM users WHERE no_hp='081234567894'), 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 4 DAY), 'selesai', 305, 79, 89, 137),
((SELECT id FROM users WHERE no_hp='081234567895'), 'Try Out SKD #1', DATE_SUB(NOW(), INTERVAL 6 DAY), 'selesai', 265, 70, 80, 115),
((SELECT id FROM users WHERE no_hp='081234567895'), 'Try Out SKD #2', DATE_SUB(NOW(), INTERVAL 3 DAY), 'selesai', 270, 71, 81, 118);

-- Insert session_subtes for sample data (ignore duplicates)
INSERT IGNORE INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan, waktu_mulai_subtes)
SELECT 
    ts.id as session_id,
    'TWK' as subtes,
    30 as durasi_menit,
    30 as jumlah_soal,
    65 as passing_grade,
    1 as urutan,
    ts.waktu_mulai as waktu_mulai_subtes
FROM tryout_sessions ts
WHERE ts.status = 'selesai';

INSERT IGNORE INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan, waktu_mulai_subtes)
SELECT 
    ts.id as session_id,
    'TIU' as subtes,
    35 as durasi_menit,
    35 as jumlah_soal,
    80 as passing_grade,
    2 as urutan,
    DATE_ADD(ts.waktu_mulai, INTERVAL 30 MINUTE) as waktu_mulai_subtes
FROM tryout_sessions ts
WHERE ts.status = 'selesai';

INSERT IGNORE INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan, waktu_mulai_subtes)
SELECT 
    ts.id as session_id,
    'TKP' as subtes,
    45 as durasi_menit,
    45 as jumlah_soal,
    126 as passing_grade,
    3 as urutan,
    DATE_ADD(ts.waktu_mulai, INTERVAL 65 MINUTE) as waktu_mulai_subtes
FROM tryout_sessions ts
WHERE ts.status = 'selesai';

-- Update sample users with instansi for better leaderboard display
UPDATE users SET instansi = 'STAN' WHERE no_hp = '081234567891';
UPDATE users SET instansi = 'IPDN' WHERE no_hp = '081234567892';
UPDATE users SET instansi = 'STIS' WHERE no_hp = '081234567893';
UPDATE users SET instansi = 'STAN' WHERE no_hp = '081234567894';
UPDATE users SET instansi = 'IPDN' WHERE no_hp = '081234567895';

-- Summary
SELECT 'Sample data inserted successfully!' as message;
SELECT CONCAT((SELECT COUNT(*) FROM tryout_sessions WHERE status='selesai'), ' completed tryouts') as stats;
SELECT CONCAT((SELECT COUNT(*) FROM users WHERE role='user'), ' total users') as stats;
