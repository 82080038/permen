# Panduan Deploy ke Free Hosting (bimbel.freehosting.dev)

## Informasi Login Free Hosting
- **Username:** if0_42138385
- **Password:** Sihaloho1982
- **Domain:** bimbel.freehosting.dev
- **Panel:** InfinityFree (cPanel-like)

---

## Langkah 1: Login ke Control Panel

1. Buka: https://app.infinityfree.net/login
2. Login dengan username dan password di atas
3. Pilih domain: bimbel.freehosting.dev
4. Klik "Control Panel"

---

## Langkah 2: Buat Database MySQL

1. Di Control Panel, cari **"MySQL Databases"**
2. Klik "Create Database"
3. Isi:
   - Database Name: `skd_cat_bkn` (atau nama lain, catat!)
4. Klik "Create"
5. **Catat informasi database:**
   - DB Host: `sqlXXX.epizy.com` (bukan localhost!)
   - DB Name: `epiz_XXXXX_skd_cat_bkn`
   - DB User: `epiz_XXXXX`
   - DB Pass: (password yang Anda buat)

---

## Langkah 3: Import Database

### Opsi A: Via phpMyAdmin (Lebih Mudah)
1. Di Control Panel, klik **"phpMyAdmin"**
2. Login dengan user database yang baru dibuat
3. Pilih database
4. Klik tab "Import"
5. Upload file: `sql/skd_cat_bkn_latest_YYYYMMDD.sql`
6. Klik "Go"

### Opsi B: Via FTP + SSH (Jika tersedia)
```bash
# Upload file SQL ke folder, lalu import via phpMyAdmin CLI
```

---

## Langkah 4: Upload File Aplikasi

### Via File Manager (Control Panel)
1. Di Control Panel, klik **"File Manager"**
2. Masuk ke folder `htdocs/` (public_html)
3. **Upload semua file aplikasi:**
   - Klik "Upload"
   - Upload ZIP file yang berisi seluruh folder `/permen`
   - Extract ZIP di server

### Via FTP (Alternatif)
1. Download FTP client (FileZilla)
2. Connect ke server:
   - Host: `ftpupload.net`
   - User: `if0_42138385`
   - Pass: `Sihaloho1982`
   - Port: `21`
3. Upload ke folder `htdocs/`

---

## Langkah 5: Konfigurasi .env

1. Rename file `.env.freehosting` menjadi `.env`
2. Edit dengan informasi database dari Langkah 2:
   ```env
   DB_HOST=sqlXXX.epizy.com
   DB_NAME=epiz_XXXXX_skd_cat_bkn
   DB_USER=epiz_XXXXX
   DB_PASS=password_anda
   DB_CHARSET=utf8mb4
   
   APP_ENV=production
   BASE_URL=https://bimbel.freehosting.dev
   ```

---

## Langkah 6: Setup di File Manager

### Struktur Folder yang Benar:
```
htdocs/
├── index.php
├── pages/
├── api/
├── assets/
├── src/
├── config.php
├── .env
└── ... (file lainnya)
```

### Checklist Upload:
- [ ] index.php
- [ ] config.php
- [ ] .env (sudah di-edit)
- [ ] pages/ (folder)
- [ ] api/ (folder)
- [ ] assets/ (folder)
- [ ] src/ (folder)
- [ ] sql/ (folder - optional)
- [ ] vendor/ (jika ada)

---

## Langkah 7: Perbaiki Config Database (Jika Perlu)

Jika koneksi database gagal, edit file `src/Database/Database.php`:

```php
// Hapus atau komentari baris unix_socket
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
// Hapus: ;unix_socket=$socket
```

---

## Langkah 8: Testing

1. Buka: https://bimbel.freehosting.dev
2. Cek halaman utama (index.php)
3. Cek login: https://bimbel.freehosting.dev/pages/login.php
4. Test dengan test user:
   - No HP: `081987654321`
   - Password: `password`
5. Test tryout: https://bimbel.freehosting.dev/pages/tryout.php

---

## Troubleshooting

### Error: "Connection failed"
- Cek DB_HOST di .env (harus sqlXXX.epizy.com, bukan localhost)
- Pastikan database sudah dibuat dan user punya akses

### Error: "500 Internal Server Error"
- Cek error log di Control Panel > Error Logs
- Pastikan file .env ada dan format benar
- Cek permission file (harus 644)

### Error: "404 Not Found"
- Pastikan file di folder htdocs/ (bukan root)
- Cek .htaccess (jika ada)

### Rate Limiting (429 Error)
- Normal di free hosting
- Hindari terlalu banyak request AJAX
- Demo dengan 1-2 user saja

---

## Batasan Free Hosting (InfinityFree)

| Aspek | Batasan |
|-------|---------|
| Disk Space | 5 GB |
| Bandwidth | Unlimited (fair use) |
| Database | 400 MB max |
| PHP Execution | 20 detik max |
| Concurrent | Limited |
| Daily Hits | ~50,000/day |

**Tips:**
- Optimize gambar soal sebelum upload
- Demo dengan <5 user simultan
- Hindari tryout 110 soal full (bisa timeout)
- Backup database secara berkala

---

## Contact Support InfinityFree

Jika ada masalah teknis:
- Forum: https://forum.infinityfree.net
- Knowledge Base: https://infinityfree.net/support/

---

## Setelah Deploy Berhasil

1. **Test fitur utama:**
   - [ ] Login/Register
   - [ ] Daily Quiz
   - [ ] Tryout (5-10 soal saja untuk demo)
   - [ ] Materi pembelajaran
   - [ ] Leaderboard

2. **Share link demo:**
   - https://bimbel.freehosting.dev
   - Test user: 081987654321 / password

3. **Monitoring:**
   - Cek error log berkala
   - Monitor bandwidth usage
   - Backup database mingguan

---

**Selamat mencoba! 🚀**
