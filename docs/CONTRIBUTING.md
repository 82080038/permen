# Contributing Guide — SKD CAT-BKN

Panduan untuk developer yang ingin berkontribusi atau melanjutkan pengembangan aplikasi.

---

## 1. Setup Development Environment

### Prerequisites
- XAMPP / LAMP (PHP 7.4+, MySQL, Apache)
- Git
- Node.js 16+ (untuk Playwright testing)
- Browser modern (Chrome/Firefox/Edge)

### Clone & Setup
```bash
git clone https://github.com/82080038/permen.git
cd permen

cp .env.example .env
# Edit .env — isi password database

# Import database
cd sql
mysql -u root -p < IMPORT_ALL.sql
mysql -u root -p skd_cat_bkn < migration_v1.1.sql

# Install test dependencies
cd ..
npm install
```

### Verifikasi Setup
```bash
# Jalankan test dasar
npx playwright test tests/skd.spec.js

# Akses aplikasi
open http://localhost/permen/
```

---

## 2. Branch Workflow

```bash
# Selalu bikin branch baru untuk fitur/bugfix
git checkout -b feature/nama-fitur
git checkout -b fix/deskripsi-bug

# Jangan commit langsung ke main
```

### Naming Convention
| Prefix | Gunakan untuk | Contoh |
|--------|---------------|--------|
| `feature/` | Fitur baru | `feature/leaderboard-filter` |
| `fix/` | Bug fix | `fix/timer-reset-on-refresh` |
| `docs/` | Dokumentasi | `docs/api-endpoint-update` |
| `refactor/` | Refactor kode | `refactor/tryout-js-modular` |

---

## 3. Coding Standards

### PHP
- Gunakan PDO prepared statements untuk SEMUA query
- Escape output dengan `htmlspecialchars()` atau helper `e()`
- Gunakan snake_case untuk variabel dan fungsi
- Selalu deklarasikan tipe return jika memungkinkan

```php
// ✅ Good
$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$id]);

// ❌ Bad
$result = $pdo->query("SELECT * FROM questions WHERE id = $id");
```

### JavaScript
- Gunakan vanilla JS (ES6+), hindari jQuery untuk kode baru
- Gunakan `const` dan `let`, jangan `var`
- Async/await untuk fetch API, hindari callback pyramid
- Jangan lupa error handling (try/catch)

```javascript
// ✅ Good
async function fetchData() {
    try {
        const res = await fetch('../api/get_soal.php?session_id=1');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    } catch (e) {
        console.error('Failed to fetch:', e);
        return null;
    }
}

// ❌ Bad
fetch(url).then(r => r.json()).then(d => { ... }).catch(e => { ... });
```

### CSS
- Gunakan CSS variables untuk tema (dark/light mode)
- Mobile-first: base styles untuk mobile, @media untuk desktop
- Touch target minimum 44px untuk semua interactive element

```css
/* ✅ Good */
:root {
    --bg-body: #f0f2f5;
    --text-main: #222;
}
[data-theme="dark"] {
    --bg-body: #1a1a2e;
    --text-main: #f0f0f0;
}

/* ❌ Bad */
body { background: #f0f2f5; }  /* hardcoded, no dark mode support */
```

---

## 4. Database Changes

### Menambah Kolom Baru
1. Edit `sql/db.sql` untuk skema baru
2. Buat file migration baru: `sql/migration_v1.2.sql`
3. Jalankan migration di environment development
4. Update `docs/ARCHITECTURE.md`

### Contoh Migration
```sql
-- sql/migration_v1.2.sql
ALTER TABLE questions ADD COLUMN new_field VARCHAR(255) NULL;
CREATE INDEX idx_questions_new ON questions(new_field);
```

---

## 5. Testing

### Sebelum Commit
```bash
# 1. Syntax check semua PHP
for f in $(find . -name "*.php"); do php -l "$f"; done

# 2. Jalankan Playwright test
npx playwright test

# 3. Manual test fitur yang diubah
```

### Menambah Test Baru
```javascript
// tests/feature.spec.js
test('deskripsi fitur', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/fitur.php');
    await expect(page.locator('text=Expected Text')).toBeVisible();
});
```

---

## 6. Commit & Push

```bash
git add .
git commit -m "type: deskripsi singkat

Detail lebih lanjut jika perlu."
git push origin feature/nama-fitur
```

### Commit Message Format
```
feature: tambah dark mode toggle
docs: update API endpoint list
fix: perbaiki timer yang reset saat refresh
refactor: pisah tryout JS ke modular files
test: tambah test untuk generator massal
```

---

## 7. File Kritis — Baca Sebelum Edit

| File | Fungsi | Hati-hati Jika Edit |
|------|--------|---------------------|
| `config.php` | Koneksi DB + session | Jangan expose credentials |
| `helpers.php` | Fungsi reusable | Banyak file depend di sini |
| `pages/tryout.php` | Engine tryout utama | Timer, auto-advance, anti-cheating |
| `api/get_soal.php` | Generate soal session | Validasi ownership |
| `api/submit_jawaban.php` | Save jawaban | Timer check + skoring |
| `api/finish_tryout.php` | Finalisasi nilai | Hitung total & passing grade |
| `api/generate_soal_smart.php` | Smart generator | Algoritma internal |
| `.env` | Credentials | Jangan commit password asli |

---

## 8. Debugging

### PHP Error Log
```bash
tail -f logs/error.log
tail -f /var/log/apache2/error.log
```

### Browser Console
- Buka DevTools → Console untuk JavaScript error
- Network tab untuk cek API response

### Database Debug
```bash
mysql -u root -p -e "USE skd_cat_bkn; SELECT COUNT(*) FROM questions;"
```

---

## 9. Contact & Support

Jika ada pertanyaan:
1. Baca `docs/ARCHITECTURE.md` untuk alur data
2. Baca `docs/API.md` untuk endpoint documentation
3. Cek `CHANGELOG.md` untuk riwayat perubahan
4. Buka issue di GitHub jika menemukan bug

