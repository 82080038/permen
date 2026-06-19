---
description: Run comprehensive Playwright E2E tests with console and network monitoring
---

# Testing Workflow — SKD CAT-BKN

## Prerequisites

1. XAMPP running (Apache + MySQL)
2. `.env` configured for local development
3. Dependencies installed: `npm install`

## Quick Run

// turbo
### Headless Mode (default, fast)
```powershell
npx playwright test tests/comprehensive-test.spec.js --reporter=list
```

### Headed Mode (visible browser)
```powershell
npx playwright test tests/comprehensive-test.spec.js --headed --reporter=list
```

### Debug Mode
```powershell
npx playwright test --debug
```

### UI Mode (interactive)
```powershell
npx playwright test --ui
```

## Test Files

| File | Coverage |
|------|----------|
| `tests/comprehensive-test.spec.js` | Full E2E: public pages, auth, dashboard, tryout, API, registration |

## Test Coverage (24 tests)

### Public Pages (7 tests)
- Landing page, login, register, help, leaderboard
- API health check, landing stats

### Authentication (3 tests)
- User login → redirect to user_dashboard
- Invalid login → error message
- Admin login → redirect to admin_dashboard

### User Dashboard (7 tests)
- Dashboard loads, navigation, profile, latihan, tryout, materi, logout

### Admin Dashboard (2 tests)
- Loads without errors, user management visible

### Tryout Flow (2 tests)
- Start tryout session
- Start latihan per subtes (TWK)

### API (1 test)
- Get soal API returns valid response

### Registration (1 test)
- Register new user successfully

### Console Monitoring (1 test)
- No JS errors on key pages

## Test Users

| Role  | No HP        | Password     |
|-------|--------------|--------------|
| Admin | 081265511982 | Sihaloho1982 |
| User  | 081987654321 | Sihaloho1982 |

## Troubleshooting

- **Tests timeout**: Ensure XAMPP Apache + MySQL running
- **Login fails**: Verify user password hash in DB
- **DB errors**: Check `.env` matches local database
- **Port conflict**: Check `http://localhost/permen` accessible in browser
