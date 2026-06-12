# Import Data Lengkap (2.678 Soal) ke Free Hosting

## 📊 Situasi:
- Total soal: 2.678 soal
- Size file SQL: ~2.1 MB
- Batasan free hosting: phpMyAdmin limit ~50MB upload, tapi timeout 30 detik

## ✅ SOLUSI YANG TERSEDIA:

---

## 🥇 OPSI A: BigDump PHP (DIREKOMENDASIKAN)

**File:** `sql/import_bigdump.php`

### Keuntungan:
- ✅ Import file 2.1MB dalam chunk kecil
- ✅ Auto-skip error (VIEW, CHECK constraint)
- ✅ Progress bar
- ✅ Auto-continue (tidak perlu klik terus)
- ✅ Tidak timeout

### Langkah:

**1. Upload File ke Hosting:**
```
Upload ke folder: htdocs/sql/
- deploy_final.sql (2.2 MB)
- import_bigdump.php
```

**2. Edit Konfigurasi:**
Edit file `import_bigdump.php`:
```php
$db_server   = 'sqlXXX.epizy.com';      // GANTI
$db_name     = 'if0_42138385_skd_cat_bkn';  // GANTI
$db_username = 'if0_42138385_XXXXX';    // GANTI
$db_password = 'password_anda';         // GANTI
```

**3. Jalankan Import:**
```
Buka browser: https://bereng.info/sql/import_bigdump.php
Klik: "🚀 MULAI IMPORT"
```

**4. Tunggu Proses:**
- Script akan import dalam chunk 300 baris
- Auto-refresh sampai 100%
- Biasanya butuh 5-10 menit untuk 2.1MB

---

## 🥈 OPSI B: Split File Manual

**File tersedia:**
| File | Size | Isi |
|------|------|-----|
| data_questions_500.sql | 220KB | Soal 1-500 |
| data_questions_501_1000.sql | 223KB | Soal 501-1000 |
| data_questions_1001_1500.sql | 151KB | Soal 1001-1500 |
| data_questions_1501_2000.sql | 164KB | Soal 1501-2000 |
| data_questions_2001_2678.sql | ? | Soal 2001-2678 |

### Langkah:

**1. Import per Batch:**
```
phpMyAdmin → Import → Upload file pertama (data_questions_500.sql)
Klik Go

Ulangi untuk file berikutnya satu per satu
```

**2. Jumlah Import:** 5 kali import

**3. Total waktu:** 5-10 menit

---

## 🥉 OPSI C: MySQL CLI (Jika Ada SSH Access)

```bash
mysql -h sqlXXX.epizy.com -u if0_42138385_XXXXX -p if0_42138385_skd_cat_bkn < deploy_final.sql
```

**Catatan:** InfinityFree biasanya tidak berikan SSH untuk free account.

---

## ⚠️ Catatan Penting:

### Data yang Akan Di-import:
✅ **2.678 soal** (questions table)
✅ **Users** (sudah ada di deploy_minimal.sql)
✅ **Materi** (jika ada)
✅ **Config subtes**

❌ **TIDAK di-import (tidak support free hosting):**
- VIEW v_tryout_sessions_flat
- Table admin_reports (ada CHECK constraint)
- Table dengan json_valid()

### Jika Error Saat Import:

**Error: "MySQL server has gone away"**
- Solusi: Gunakan BigDump (sudah handle auto-reconnect)

**Error: "Timeout"**
- Solusi: BigDump dengan delay antar chunk

**Error: "Data too long"**
- Solusi: Skip soal tersebut, lanjutkan manual insert

---

## 🎯 Rekomendasi Urutan Import:

### 1. Schema Dasar (Sudah Done)
```
✅ deploy_minimal.sql ( struktur table )
✅ Test user (081987654321 / password)
```

### 2. Data Soal
```
Pilih salah satu:
A. BigDump: import_bigdump.php + deploy_final.sql
B. Manual: data_questions_500.sql → 501_1000.sql → dll
```

### 3. Verifikasi
```
Login phpMyAdmin
Cek table questions: SELECT COUNT(*) FROM questions;
Harusnya: 2678 rows
```

---

## 🔧 Alternatif Jika Semua Gagal:

### Gunakan Smart Generator
Aplikasi punya fitur generate soal otomatis:
1. Login admin
2. Menu: Smart Generator
3. Generate soal TWK/TIU/TKP per topik
4. Tidak perlu import SQL

**Keuntungan:**
- ✅ Tidak perlu import SQL besar
- ✅ Soal selalu fresh
- ✅ Bisa generate sesuai kebutuhan

---

## 📋 Checklist Import:

- [ ] Upload schema: deploy_minimal.sql ✅
- [ ] Pilih metode import data (BigDump/Manual)
- [ ] Upload file data SQL
- [ ] Jalankan import
- [ ] Verifikasi: SELECT COUNT(*) FROM questions; → 2678
- [ ] Test login aplikasi
- [ ] Test tryout dengan soal

---

## 💡 Tips:

1. **Jika BigDump stuck:** Refresh browser, klik "LANJUTKAN"
2. **Jika error memory:** Kurangi chunk_size di BigDump (ganti 300 → 200)
3. **Backup dulu:** Export database sebelum import (jaga-jaga)
4. **Waktu terbaik:** Import saat traffic low (pagi/subuh)

---

## 🆘 Troubleshooting:

| Masalah | Solusi |
|---------|--------|
| Import stuck di 50% | Refresh, klik Continue |
| Error "max_allowed_packet" | Split file lebih kecil |
| Error "time out" | Gunakan BigDump dengan delay |
| Data corrupt | Import ulang dari awal |

---

**Siap import? Pilih Opsi A (BigDump) untuk kemudahan! 🚀**
