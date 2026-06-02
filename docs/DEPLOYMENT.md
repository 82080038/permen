# Deployment Guide — SKD CAT-BKN

Cara deploy aplikasi ke server production (shared hosting, VPS, atau local XAMPP).

---

## 1. Persyaratan Server

| Komponen | Versi Minimum |
|----------|---------------|
| PHP | 7.4+ (mysqli, PDO, gd extensions) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Apache | 2.4+ (mod_rewrite) |
| Storage | 50MB aplikasi + DB |

---

## 2. Clone / Upload

### Dari GitHub
```bash
git clone https://github.com/82080038/permen.git
```

### Manual (ZIP)
1. Download ZIP dari GitHub
2. Extract ke folder web server: `htdocs/permen/` (XAMPP) atau `public_html/` (shared hosting)

---

## 3. Konfigurasi Environment

```bash
cp .env.example .env
```

Edit `.env`:
```
DB_HOST=localhost
DB_NAME=skd_cat_bkn
DB_USER=root
DB_PASSWORD=your_password

GEMINI_API_KEY=your_gemini_key_here  # opsional
```

> **Penting**: Ganti password database setelah clone. Jangan gunakan password dari `.env` yang dipush.

---

## 4. Setup Database

### Opsi A: Import Semua Sekaligus
```bash
cd sql
mysql -u root -p < IMPORT_ALL.sql
```

### Opsi B: Import Manual
1. Buat database: `CREATE DATABASE skd_cat_bkn CHARACTER SET utf8mb4;`
2. Import schema: `mysql -u root -p skd_cat_bkn < sql/db.sql`
3. Import seed: `mysql -u root -p skd_cat_bkn < sql/seed.sql`
4. Jalankan migration: `mysql -u root -p skd_cat_bkn < sql/migration_v1.1.sql`

### Opsi C: phpMyAdmin
- Buka `http://localhost/phpmyadmin`
- Buat database `skd_cat_bkn`
- Import tab: pilih `sql/IMPORT_ALL.sql`

---

## 5. Konfigurasi Apache

Pastikan `.htaccess` sudah ada di root. Jika Apache tidak mengizinkan `.htaccess`:

```apache
# Tambahkan ke httpd.conf atau virtual host
<Directory "/var/www/html/permen">
    AllowOverride All
    Require all granted
</Directory>
```

Restart Apache:
```bash
sudo systemctl restart apache2   # Ubuntu/Debian
sudo systemctl restart httpd     # CentOS/RHEL
```

---

## 6. Permissions

```bash
# Folder uploads harus writable
chmod 755 assets/soal/
chmod 755 logs/
chmod 644 config.php
```

---

## 7. Verifikasi

Buka browser dan akses:
- Homepage: `http://localhost/permen/`
- Login: `http://localhost/permen/pages/login.php`
- Demo user: `budi@skd.test` / `password`

Cek error log jika ada masalah:
```bash
tail -f logs/error.log
```

---

## 8. Production Checklist

| Item | Status |
|------|--------|
| `.env` password diganti | ☐ |
| `GEMINI_API_KEY` diisi (jika pakai AI generator) | ☐ |
| Database migration v1.1 sudah dijalankan | ☐ |
| Folder `assets/soal/` writable | ☐ |
| `.htaccess` aktif (AllowOverride All) | ☐ |
| HTTPS enabled (SSL certificate) | ☐ |
| PHP `display_errors = Off` | ☐ |
| PHP `upload_max_filesize` ≥ 2MB | ☐ |
| Backup schedule aktif | ☐ |

---

## 9. Shared Hosting (cPanel)

1. Upload via File Manager atau FTP ke `public_html/permen/`
2. Buat database MySQL di cPanel → MySQL Databases
3. Import `sql/IMPORT_ALL.sql` via phpMyAdmin
4. Edit `.env` dengan credentials database baru
5. Pastikan `assets/soal/` permission 755

---

## 10. Docker (Opsional)

```dockerfile
# Dockerfile (skeleton)
FROM php:7.4-apache
RUN docker-php-ext-install pdo pdo_mysql mysqli gd
RUN a2enmod rewrite
COPY . /var/www/html/
RUN chmod 755 /var/www/html/assets/soal
EXPOSE 80
```

```yaml
# docker-compose.yml (skeleton)
version: '3.8'
services:
  web:
    build: .
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: skd_cat_bkn
    volumes:
      - ./sql:/docker-entrypoint-initdb.d
```

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| 500 Internal Server Error | Cek `logs/error.log`, pastikan PDO extension aktif |
| 403 Forbidden | Cek `.htaccess`, pastikan `AllowOverride All` |
| Database connection failed | Cek `.env` credentials, pastikan MySQL running |
| Gambar tidak upload | Cek `assets/soal/` permission 755, PHP `upload_max_filesize` |
| Timer tidak jalan | Cek JavaScript console, pastikan `api/get_soal.php` return JSON |

