-- ============================================================
-- IMPORT SEMUA BATCH — SKD CAT-BKN
-- Jalankan urutan ini di phpMyAdmin atau terminal MySQL
-- ============================================================

-- 1. Skema database & tabel baru
SOURCE db.sql;

-- 2. Data soal awal (60+ soal)
SOURCE seed.sql;

-- 3. Master materi (kisi-kisi untuk AI)
SOURCE batch_master_materi.sql;

-- 4. Tips & trik (reusable, dengan contoh penerapan)
SOURCE batch_tips.sql;

-- 5. Batch soal tambahan (90 soal baru: 30 TKP + 30 TIU + 30 TWK)
SOURCE batch_soal_1_tkp.sql;
SOURCE batch_soal_1_tiu.sql;
SOURCE batch_soal_1_twk.sql;

-- ============================================================
-- Total soal setelah import: ~150 soal
-- Jalankan api/generate_soal_ai.php untuk generate ribuan soal via AI
-- ============================================================
