-- ============================================================
-- IMPORT SEMUA BATCH — SKD CAT-BKN
-- Jalankan urutan ini di phpMyAdmin atau terminal MySQL
-- ============================================================

-- 1. Skema database & tabel baru
SOURCE skd_cat_bkn.sql;

-- 2. Migrations & additional tables
SOURCE create_api_rate_limits_table.sql;
SOURCE create_rate_limits_table.sql;
SOURCE create_audit_logs_table.sql;
SOURCE migration_add_rate_limits.sql;
SOURCE migration_add_user_audit_logs.sql;
SOURCE migration_add_account_lockout.sql;

-- 3. Email-free system tables (notifications, password reset requests)
SOURCE create_notifications_table.sql;
SOURCE create_password_reset_requests_table.sql;

-- 4. Data soal awal (60+ soal)
SOURCE seed.sql;

-- 5. Master materi (kisi-kisi untuk AI)
SOURCE batch_master_materi.sql;

-- 6. Tips & trik (reusable, dengan contoh penerapan)
SOURCE batch_tips.sql;

-- 7. Batch soal tambahan (90 soal baru: 30 TKP + 30 TIU + 30 TWK)
SOURCE batch_soal_1_tkp.sql;
SOURCE batch_soal_1_tiu.sql;
SOURCE batch_soal_1_twk.sql;

-- ============================================================
-- Total soal setelah import: ~150 soal
-- Jalankan api/generate_soal_ai.php untuk generate ribuan soal via AI
-- ============================================================
