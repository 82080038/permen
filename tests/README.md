# Playwright E2E Tests — SKD CAT-BKN

## Setup
Sudah terinstall otomatis via npm:
```bash
npm install
npx playwright install chromium
npx playwright install-deps chromium
```

## Menjalankan Test

### Headed (dengan browser terlihat)
```bash
# Linux dengan display virtual (xvfb)
xvfb-run --auto-servernum --server-args="-screen 0 1280x720x24" npx playwright test --headed

# Atau gunakan script shorthand
npm run test:headed
```

### Headless (CI/server)
```bash
npx playwright test
# atau
npm test
```

### Debug Mode
```bash
npx playwright test --debug
```

### UI Mode (interactive)
```bash
npx playwright test --ui
```

## Test Cases
| Test | Deskripsi |
|------|-----------|
| halaman utama | Cek title, CTA, navigasi |
| materi TWK | Cek accordion materi |
| latihan per subtes | Cek 3 pilihan subtes |
| flow latihan TIU | End-to-end: mulai -> jawab -> selesai -> hasil |
| smart generator | Cek API generate soal internal |
| API security | Cek endpoint menolak tanpa autentikasi |

## Hasil Test
Screenshot dan trace tersimpan di `test-results/` jika ada kegagalan.
