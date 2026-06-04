-- Migration v1.2: Ganti email dengan no_hp, tambah sekolah_asal dan tahun_tamat
-- Created: 2026-06-05
-- Description: Mengubah sistem login dari email ke nomor HP, dan menambahkan data pendidikan user

-- ============================================
-- 1. Tambah kolom baru
-- ============================================
ALTER TABLE users 
ADD COLUMN no_hp VARCHAR(20) NULL COMMENT 'Nomor HP untuk login (08xx)',
ADD COLUMN sekolah_asal VARCHAR(100) NULL COMMENT 'Nama sekolah asal (SMA/SMK/MA)',
ADD COLUMN tahun_tamat INT NULL COMMENT 'Tahun tamat sekolah';

-- ============================================
-- 2. Migrasi data email ke no_hp (jika ada)
-- ============================================
-- Catatan: Karena email dan no_hp berbeda format, kita tidak bisa migrasi otomatis
-- User perlu input no_hp baru saat login pertama setelah migration
-- Kolom email akan dijadikan UNIQUE KEY untuk backward compatibility sementara

-- ============================================
-- 3. Update UNIQUE constraint
-- ============================================
-- Hapus unique key lama jika ada
ALTER TABLE users DROP INDEX IF EXISTS email;

-- Tambah unique key untuk no_hp
ALTER TABLE users ADD UNIQUE KEY unique_no_hp (no_hp);

-- ============================================
-- 4. Update kolom email agar nullable (untuk backward compatibility)
-- ============================================
ALTER TABLE users MODIFY COLUMN email VARCHAR(100) NULL COMMENT 'Email (deprecated, gunakan no_hp)';

-- ============================================
-- 5. Update tabel password_reset_requests jika ada
-- ============================================
-- Cek apakah tabel ada, jika ya update kolom email
SET @tableExists = 0;
SELECT COUNT(*) INTO @tableExists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'password_reset_requests';

SET @sql = IF(@tableExists > 0, 
    'ALTER TABLE password_reset_requests ADD COLUMN no_hp VARCHAR(20) NULL COMMENT ''Nomor HP user''',
    'SELECT ''Table password_reset_requests tidak ada, skip'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 6. Catatan untuk developer
-- ============================================
-- Setelah migration ini:
-- - Update semua kode PHP yang menggunakan email untuk menggunakan no_hp
-- - Update form login: email -> no_hp
-- - Update form register: email -> no_hp, tambah sekolah_asal, tahun_tamat
-- - Hapus atau update forgot_password.php (karena tidak relevan untuk no_hp)
-- - Update helpers.php: isValidEmail -> isValidPhoneNumber
