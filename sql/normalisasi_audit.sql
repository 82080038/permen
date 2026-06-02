-- ============================================
-- AUDIT DATABASE SKD CAT-BKN UNTUK NORMALISASI
-- ============================================

-- 1. LIHAT SEMUA TABEL
SHOW TABLES;

-- 2. DESCRIBE SETIAP TABEL
DESCRIBE users;
DESCRIBE questions;
DESCRIBE answers;
DESCRIBE tryout_sessions;
DESCRIBE master_materi;
DESCRIBE soal_ai_cache;

-- 3. CEK REDUNDANSI DI users (instansi_pilihan vs instansi)
SELECT id, nama, instansi_pilihan, instansi, role FROM users ORDER BY id LIMIT 10;

-- 4. CEK JUMLAH KOLOM SUBTES DI tryout_sessions (ini masalah normalisasi!)
-- ada 3 grup: durasi_*, jumlah_*, passing_*, nilai_* — total 12+ kolom untuk 3 subtes
DESCRIBE tryout_sessions;

-- 5. CEK answers: apakah ada session_id + question_id yang duplikat?
SELECT session_id, question_id, COUNT(*) as cnt FROM answers 
GROUP BY session_id, question_id HAVING cnt > 1;

-- 6. CEK questions: apakah tipe & topik berulang?
SELECT subtes, tipe, topik, COUNT(*) as jumlah FROM questions 
GROUP BY subtes, tipe, topik ORDER BY subtes, tipe, topik;

-- 7. CEK master_materi: bagaimana struktur referensi?
DESCRIBE master_materi;
SELECT subtes, tipe, topik FROM master_materi ORDER BY subtes, tipe, topik;

-- 8. CEK soal_ai_cache: apakah ada redundant data?
DESCRIBE soal_ai_cache;
SELECT subtes, tipe, topik, COUNT(*) as jumlah FROM soal_ai_cache 
GROUP BY subtes, tipe, topik ORDER BY jumlah DESC LIMIT 10;
