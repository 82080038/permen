---
description: Run comprehensive Playwright E2E tests with console and network monitoring
---

# Testing Workflow - SKD CAT-BKN

This workflow runs comprehensive end-to-end tests using Playwright with console and network error monitoring.

## Prerequisites

1. XAMPP services must be running:
   ```bash
   sudo /opt/lampp/lampp start
   ```

2. Database must be imported:
   ```bash
   /opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/skd_cat_bkn.sql
   ```

3. Dependencies must be installed:
   ```bash
   composer install
   npm install
   npx playwright install chromium
   ```

## Running Tests

### Headed Mode (with browser visible)
```bash
# Linux requires xvfb for headed mode
xvfb-run --auto-servernum --server-args="-screen 0 1280x720x24" npm run test:headed
```

Note: Playwright config is set to `headless: false` by default for monitoring

### Headless Mode (default)
```bash
npm test
```

### Debug Mode
```bash
npx playwright test --debug
```

### UI Mode
```bash
npm run test:ui
```

## Test Files

- `tests/skd.spec.js` - Basic functionality tests
- `tests/comprehensive.spec.js` - Comprehensive E2E tests with error monitoring
- `tests/exploratory.spec.js` - Exploratory testing

## Test Coverage

### Public Pages
- Homepage loading
- Login page
- Leaderboard
- Materi pages (TWK, TIU, TKP)
- Latihan page

### Authenticated Flows
- User login and dashboard
- Tryout functionality
- Materi with Uji Pemahaman
- Logout

### API Endpoints
- Smart generator (admin-only)
- User generator
- Get soal (auth required)
- Submit jawaban (auth required)

### Admin Features
- Admin dashboard
- Generator massal
- Soal management

## Error Monitoring

Tests automatically capture:
- Console errors
- Page errors
- Network errors (4xx, 5xx)

All errors are logged during test execution and reported in test results.

## Troubleshooting

### 500 Error on user_dashboard.php
Check that the `answers` table query includes proper JOIN with `tryout_sessions`:
```sql
JOIN tryout_sessions ts ON a.session_id = ts.id
WHERE ts.user_id = ?
```

### Authentication Failures
Ensure test user exists in database:
```sql
SELECT * FROM users WHERE email = 'budi@skd.test';
```

### Database Connection Issues
Verify `.env` configuration:
```
DB_HOST=localhost
DB_NAME=skd_cat_bkn
DB_USER=root
DB_PASS=root
```

## Viewing Results

HTML report is automatically generated at:
```
test-results/index.html
```

View with:
```bash
npx playwright show-report
```
