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

## Test Files
| File | Deskripsi |
|------|-----------|
| `peserta-full-simulation.spec.js` | Full user flow: register, login, navigate pages, tryout (UI clicks + API), daily quiz, feedback, settings, logout |
| `mobile-responsive-check.spec.js` | Mobile viewport checks: touch targets, font sizes, hamburger menu, horizontal overflow |

## Environment Variables
Set `TEST_BASE_URL` to switch between environments:
```bash
# Local development
export TEST_BASE_URL=http://localhost/permen

# Production
export TEST_BASE_URL=https://bimbel.bereng.info
```

## Hasil Test
Screenshot dan trace tersimpan di `test-results/` jika ada kegagalan.
