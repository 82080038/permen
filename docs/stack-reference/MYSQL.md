# MySQL — Referensi & Query Patterns

Aplikasi ini menggunakan **MySQL 5.7+ / MariaDB 10.3+**. Dokumen ini adalah acuan penulisan query dan desain skema.

---

## 1. Tipe Data Umum

| Tipe | Gunakan untuk | Contoh |
|------|---------------|--------|
| `INT` | ID, counter, skor | `id INT AUTO_INCREMENT` |
| `BIGINT` | ID sangat besar, timestamp | `created_at BIGINT` |
| `VARCHAR(n)` | String pendek | `nama VARCHAR(100)` |
| `TEXT` | String panjang | `pertanyaan TEXT`, `pembahasan TEXT` |
| `ENUM` | Pilihan terbatas | `subtes ENUM('TKP','TIU','TWK')` |
| `DECIMAL(p,s)` | Uang / nilai presisi | `harga DECIMAL(10,2)` |
| `DATETIME` | Tanggal+waktu | `waktu_mulai DATETIME` |
| `TIMESTAMP` | Auto update waktu | `updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |
| `BOOLEAN` / `TINYINT(1)` | True/False | `is_active BOOLEAN DEFAULT true` |
| `JSON` | Data semi-struktur | `metadata JSON` (MySQL 5.7+) |

---

## 2. DDL Patterns

### Buat Tabel
```sql
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtes ENUM('TKP','TIU','TWK') NOT NULL,
    tipe VARCHAR(20) DEFAULT NULL,
    topik VARCHAR(100) NOT NULL,
    pertanyaan TEXT NOT NULL,
    pilihan_a TEXT NOT NULL,
    pilihan_b TEXT NOT NULL,
    pilihan_c TEXT NOT NULL,
    pilihan_d TEXT NOT NULL,
    pilihan_e TEXT NOT NULL,
    jawaban_benar CHAR(1) NOT NULL,
    bobot_tkp INT DEFAULT NULL,
    pembahasan TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_subtes (subtes),
    INDEX idx_topik (topik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Alter Tabel
```sql
-- Tambah kolom
ALTER TABLE users ADD COLUMN no_hp VARCHAR(15) AFTER email;

-- Ubah tipe kolom
ALTER TABLE tryout_sessions MODIFY COLUMN durasi_tkp INT DEFAULT 25;

-- Tambah index
ALTER TABLE answers ADD INDEX idx_session (session_id);

-- Tambah foreign key
ALTER TABLE answers
ADD CONSTRAINT fk_answers_session
FOREIGN KEY (session_id) REFERENCES tryout_sessions(id) ON DELETE CASCADE;

-- Hapus kolom
ALTER TABLE users DROP COLUMN no_hp;
```

---

## 3. DML Patterns

### INSERT
```sql
-- Single row
INSERT INTO users (nama, email) VALUES ('Budi', 'budi@test.com');

-- Multiple rows
INSERT INTO questions (subtes, topik, pertanyaan, ...)
VALUES
('TWK', 'Pancasila', 'Dasar negara adalah...', ...),
('TWK', 'UUD 1945', 'Presiden dipilih oleh...', ...);

-- Insert dengan select
INSERT INTO answers (session_id, question_id)
SELECT 1, id FROM questions WHERE subtes = 'TKP' ORDER BY RAND() LIMIT 35;
```

### SELECT
```sql
-- Basic
SELECT * FROM questions WHERE subtes = 'TKP';

-- Specific columns
SELECT id, pertanyaan, jawaban_benar FROM questions;

-- ORDER BY + LIMIT
SELECT * FROM questions WHERE subtes = 'TIU' ORDER BY RAND() LIMIT 35;

-- JOIN
SELECT a.id, a.jawaban_user, q.pertanyaan, q.jawaban_benar
FROM answers a
JOIN questions q ON a.question_id = q.id
WHERE a.session_id = 1;

-- GROUP BY + aggregate
SELECT q.subtes, SUM(a.skor) as total_skor, COUNT(a.id) as jumlah
FROM answers a
JOIN questions q ON a.question_id = q.id
WHERE a.session_id = 1
GROUP BY q.subtes;

-- Subquery
SELECT * FROM questions
WHERE id NOT IN (SELECT question_id FROM answers WHERE session_id = 1);

-- CASE (conditional)
SELECT 
    id,
    CASE 
        WHEN skor >= 126 THEN 'LULUS'
        ELSE 'TIDAK LULUS'
    END as status
FROM tryout_sessions;
```

### UPDATE
```sql
-- Single row
UPDATE answers SET jawaban_user = 'B', skor = 5 WHERE id = 1;

-- Multiple conditions
UPDATE tryout_sessions 
SET status = 'selesai', waktu_selesai = NOW() 
WHERE id = 1 AND status = 'berjalan';
```

### DELETE
```sql
-- Hati-hati! Selalu pakai WHERE
DELETE FROM answers WHERE session_id = 1;

-- Truncate (hapus semua data, reset auto increment)
TRUNCATE TABLE answers;
```

---

## 4. Functions & Expressions

### String
```sql
SELECT CONCAT(nama, ' - ', email) as info FROM users;
SELECT SUBSTRING(pertanyaan, 1, 50) as preview FROM questions;
SELECT REPLACE(pertanyaan, 'kantor', 'instansi') FROM questions;
SELECT LENGTH(pertanyaan) FROM questions;
```

### Date/Time
```sql
SELECT NOW(); -- 2026-06-02 18:30:00
SELECT CURDATE(); -- 2026-06-02
SELECT DATE(waktu_mulai) FROM tryout_sessions;
SELECT TIMESTAMPDIFF(MINUTE, waktu_mulai, waktu_selesai) as durasi_menit;
SELECT DATE_ADD(waktu_mulai, INTERVAL 80 MINUTE) as waktu_akhir;
SELECT TIME_TO_SEC(TIMEDIFF(NOW(), waktu_mulai)) as detik_berjalan;
```

### Aggregate
```sql
SELECT 
    COUNT(*) as total_soal,
    SUM(skor) as total_nilai,
    AVG(skor) as rata_rata,
    MAX(skor) as nilai_tertinggi,
    MIN(skor) as nilai_terendah
FROM answers WHERE session_id = 1;
```

### Math
```sql
SELECT ROUND(AVG(skor), 2) FROM answers;
SELECT CEIL(4.2); -- 5
SELECT FLOOR(4.8); -- 4
SELECT RAND(); -- 0.0 - 1.0
```

---

## 5. Indexing & Optimization

### Jenis Index
```sql
-- Single column index
CREATE INDEX idx_subtes ON questions(subtes);

-- Composite index (urutan penting!)
CREATE INDEX idx_subtes_topik ON questions(subtes, topik);

-- Unique index
CREATE UNIQUE INDEX idx_email ON users(email);
```

### Explain Query
```sql
EXPLAIN SELECT * FROM answers WHERE session_id = 1;
-- Cek: type, possible_keys, key, rows
-- Ideal: type = ref/range, key = idx_..., rows = kecil
```

### Tips Optimasi
- Index kolom yang sering di-`WHERE`, `JOIN`, `ORDER BY`.
- Hindari `SELECT *` jika hanya butuh beberapa kolom.
- Untuk teks panjang yang dicari, pertimbangkan `FULLTEXT` index.
- `LIMIT` + `ORDER BY` dengan index lebih cepat.

---

## 6. Transactions

```sql
START TRANSACTION;

INSERT INTO tryout_sessions (user_id, nama, waktu_mulai) VALUES (1, 'TO', NOW());
SET @session_id = LAST_INSERT_ID();

INSERT INTO answers (session_id, question_id)
SELECT @session_id, id FROM questions WHERE subtes = 'TKP' ORDER BY RAND() LIMIT 35;

COMMIT;
-- atau ROLLBACK jika ada error
```

Dalam PHP:
```php
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO ...");
    $stmt->execute([...]);
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

---

## 7. Backup & Restore

```bash
# Export
mysqldump -u root -p skd_cat_bkn > backup_2026.sql

# Import
mysql -u root -p skd_cat_bkn < backup_2026.sql
```

---

## 8. Common Errors

| Error | Penyebab | Solusi |
|-------|----------|--------|
| `Duplicate entry` | UNIQUE constraint violated | Cek data sebelum INSERT, atau gunakan `INSERT IGNORE` / `ON DUPLICATE KEY UPDATE` |
| `Cannot add foreign key` | Tipe data tidak cocok | Pastikan kolom FK sama persis tipe & ukurannya dengan PK |
| `Lock wait timeout` | Transaction terlalu lama | Commit lebih cepat atau kurangi scope transaction |
| `Too many connections` | Koneksi tidak ditutup | Di PHP, PDO auto-close. Pastikan tidak ada leak di kode. |

---

## 9. Referensi

- [MySQL 8.0 Reference Manual](https://dev.mysql.com/doc/refman/8.0/en/)
- [MariaDB Knowledge Base](https://mariadb.com/kb/en/)
- [Use the Index, Luke](https://use-the-index-luke.com/)
