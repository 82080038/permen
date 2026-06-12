# Panduan Setup bereng.info dengan InfinityFree

## Informasi Domain
- **Domain:** bereng.info
- **Hosting:** InfinityFree (Gratis)
- **Tujuan:** Ganti bimbel.freehosting.dev → bereng.info

---

## LANGKAH 1: Update Nameserver di Registrar Domain

### A. Login ke Tempat Beli Domain
Domain .info biasanya dibeli di:
- Namecheap
- GoDaddy
- Domain.com
- Reseller lokal Indonesia

**Login ke control panel domain Anda.**

### B. Ganti Nameserver
Cari menu **"Nameserver"** atau **"DNS Management"**, lalu ganti menjadi:

```
Nameserver 1: ns1.epizy.com
Nameserver 2: ns2.epizy.com
```

**Contoh di Namecheap:**
1. Login → Domain List
2. Klik "Manage" di bereng.info
3. Tab "Nameservers"
4. Pilih "Custom DNS"
5. Masukkan:
   - ns1.epizy.com
   - ns2.epizy.com
6. Save Changes

**Contoh di GoDaddy:**
1. Login → My Products
2. DNS di bereng.info
3. Scroll ke "Nameservers"
4. Klik "Change"
5. Pilih "Enter my own nameservers"
6. Masukkan:
   - ns1.epizy.com
   - ns2.epizy.com
7. Save

### C. Tunggu Propagasi DNS
⏱️ **Waktu tunggu:** 24-48 jam (kadang lebih cepat 2-6 jam)

**Cek status:** https://dnschecker.org
- Masukkan: bereng.info
- Pilih: NS (Nameserver)
- Klik Search
- Tunggu sampai semua server menunjukkan ns1.epizy.com & ns2.epizy.com

---

## LANGKAH 2: Add Domain di InfinityFree

### A. Login InfinityFree
```
URL: https://app.infinityfree.net/login
Username: if0_42138385
Password: Sihaloho1982
```

### B. Create New Hosting Account
1. Klik **"Create New Account"** (atau "New Hosting")
2. Pilih **"Custom Domain"**
3. Masukkan: **bereng.info**
4. Pilih **"I will use my existing domain and update my nameservers"**
5. Klik **"Continue"** atau **"Create Account"**

### C. Pilih Server Location
- Pilih: **"United States"** (default, terdekat)
- Atau: **"Singapore"** (jika tersedia, lebih dekat Indonesia)

### D. Tunggu Setup
⏱️ **Waktu tunggu:** 5-15 menit

Status akan berubah dari "Pending" → "Active"

---

## LANGKAH 3: Setup Database

### A. Buka Control Panel
1. Di daftar hosting, klik **"bereng.info"**
2. Klik **"Control Panel"** (atau "Go to CPanel")

### B. Create Database
1. Cari **"MySQL Databases"** atau **"Database"**
2. Klik **"Create Database"**
3. Isi:
   - Database Name: `skd_cat_bkn`
4. Klik **"Create"**

### C. Create Database User
1. Di halaman yang sama, scroll ke **"MySQL Users"**
2. Create User:
   - Username: (bebas, misal: admin_skd)
   - Password: (buat password kuat)
3. Klik **"Create User"**

### D. Add User to Database
1. Scroll ke **"Add User To Database"**
2. Pilih User yang baru dibuat
3. Pilih Database: skd_cat_bkn
4. Klik **"Add"**
5. Centang semua privileges (ALL PRIVILEGES)
6. Klik **"Make Changes"**

### E. Catat Informasi Database
**Simpan informasi ini:**
```
DB Host: sqlXXX.epizy.com (lihat di Control Panel)
DB Name: if0_42138385_skd_cat_bkn (atau similar)
DB User: if0_42138385_XXXXX
DB Pass: (password yang Anda buat)
```

---

## LANGKAH 4: Import Database

### A. Buka phpMyAdmin
1. Di Control Panel, cari **"phpMyAdmin"**
2. Klik untuk membuka phpMyAdmin
3. Login dengan:
   - Username: (DB User dari Langkah 3)
   - Password: (DB Pass dari Langkah 3)

### B. Import SQL File
1. Di phpMyAdmin, pilih database `skd_cat_bkn`
2. Klik tab **"Import"**
3. Klik **"Choose File"**
4. Upload file: `deploy_freehosting.sql` (dari folder /permen/sql/)
5. Klik **"Go"** atau **"Import"**
6. Tunggu proses import (bisa 2-5 menit untuk 2.678 soal)

**Jika import gagal (file terlalu besar):**
1. Split file SQL menjadi beberapa bagian
2. Atau gunakan BigDump: https://www.ozerov.de/bigdump/

---

## LANGKAH 5: Upload Aplikasi

### A. Buka File Manager
1. Di Control Panel, cari **"File Manager"**
2. Klik untuk membuka

### B. Upload File
1. Masuk ke folder `htdocs/` atau `public_html/`
2. **Hapus file default** (index.html, default.php, dll)
3. Upload semua file aplikasi `/permen`:

**Cara 1: Via File Manager (Web)**
1. Klik "Upload"
2. Select semua file di folder /permen/
3. Upload (bisa jadi beberapa batch)

**Cara 2: Via FTP (Lebih Cepat)**
1. Buka FileZilla atau FTP client
2. Connect:
   - Host: ftpupload.net
   - User: if0_42138385
   - Pass: Sihaloho1982
   - Port: 21
3. Upload ke folder `/htdocs/`

**Cara 3: ZIP Upload**
1. ZIP seluruh folder `/permen` di komputer
2. Upload ZIP via File Manager
3. Extract di server

### C. Verifikasi Struktur Folder
Pastikan struktur di `htdocs/` seperti ini:
```
htdocs/
├── index.php
├── config.php
├── .env
├── pages/
├── api/
├── assets/
├── src/
└── sql/ (optional)
```

---

## LANGKAH 6: Konfigurasi .env

### A. Edit File .env
1. Di File Manager, cari file `.env` (atau `.env.freehosting`)
2. Klik "Edit"
3. Update dengan informasi database:

```env
# Konfigurasi Database
DB_HOST=sqlXXX.epizy.com          ← GANTI dengan DB Host Anda
DB_NAME=if0_42138385_skd_cat_bkn  ← GANTI dengan DB Name Anda
DB_USER=if0_42138385_XXXXX        ← GANTI dengan DB User Anda
DB_PASS=password_anda             ← GANTI dengan DB Password
DB_CHARSET=utf8mb4

# Environment
APP_ENV=production

# Base URL
BASE_URL=https://bereng.info      ← SUDAH BENAR
```

### B. Save File
Klik **"Save"** atau **"Save Changes"**

---

## LANGKAH 7: Fix Database Connection (Jika Perlu)

### Jika Error Koneksi Database:

**Edit file:** `src/Database/Database.php`

**Cari baris:**
```php
$socket = $_ENV['DB_SOCKET'] ?? '/opt/lampp/var/mysql/mysql.sock';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset;unix_socket=$socket";
```

**Ganti menjadi:**
```php
// Hapus socket untuk free hosting
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
```

**Save file.**

---

## LANGKAH 8: Testing

### A. Cek Website
Buka di browser:
```
https://bereng.info
```

**Harusnya tampil:** Halaman utama aplikasi SKD CAT-BKN

### B. Test Login
```
URL: https://bereng.info/pages/login.php
```

**Test User:**
- No HP: `081987654321`
- Password: `password`

### C. Test Fitur
- [ ] Login berhasil
- [ ] Dashboard tampil
- [ ] Daily Quiz bisa diakses
- [ ] Tryout page load (test 1-2 soal)
- [ ] Materi pembelajaran tampil
- [ ] Logout berfungsi

---

## LANGKAH 9: SSL/HTTPS (Gratis)

InfinityFree otomatis provide SSL gratis via Let's Encrypt.

**Cek SSL:**
1. Buka: https://bereng.info
2. Harusnya ada 🔒 di browser (secure)

**Jika tidak ada SSL:**
1. Di Control Panel, cari **"SSL/TLS"**
2. Atau: **"Free SSL Certificate"**
3. Generate SSL untuk bereng.info
4. Tunggu 15-30 menit

---

## TROUBLESHOOTING

### Error: "This site can't be reached"
**Penyebab:** DNS belum propagasi
**Solusi:** Tunggu 24-48 jam, atau cek di https://dnschecker.org

### Error: "Connection failed" Database
**Penyebab:** DB_HOST salah
**Solusi:** Pastikan DB_HOST = sqlXXX.epizy.com (bukan localhost)

### Error: "404 Not Found"
**Penyebab:** File tidak di folder htdocs
**Solusi:** Pastikan index.php di `/htdocs/`

### Error: "500 Internal Server Error"
**Penyebab:** PHP error atau .env salah
**Solusi:**
1. Cek error log: Control Panel → Error Logs
2. Pastikan .env format benar (tidak ada spasi aneh)
3. Pastikan folder vendor/ ada (jika pakai composer)

### Rate Limiting (429 Error)
**Penyebab:** Terlalu banyak request
**Solusi:** Normal di free hosting, tunggu 1-2 menit, coba lagi

---

## CHECKLIST SETUP

### Pre-Setup:
- [ ] Nameserver diganti ke ns1.epizy.com & ns2.epizy.com
- [ ] Tunggu propagasi DNS (24-48 jam)

### InfinityFree Setup:
- [ ] Create hosting account dengan domain bereng.info
- [ ] Database created
- [ ] Database user created & added to database
- [ ] Database imported via phpMyAdmin

### File Upload:
- [ ] All files uploaded to htdocs/
- [ ] .env configured correctly
- [ ] Database.php edited (hapus socket jika perlu)

### Testing:
- [ ] https://bereng.info accessible
- [ ] Login works (081987654321 / password)
- [ ] Dashboard displays
- [ ] Tryout page loads
- [ ] SSL active (🔒 in browser)

---

## SETELAH BERHASIL

**Domain Anda:** https://bereng.info

**Siap digunakan untuk:**
- Demo aplikasi
- Showcase ke calon user
- Testing fitur
- Portfolio project

**Upgrade nanti jika perlu:**
- Pindah ke Niagahoster/VPS tanpa ganti domain
- Cukup update nameserver baru

---

**Selamat setup! 🚀**

Domain bereng.info akan online dalam 24-48 jam setelah nameserver diganti.
