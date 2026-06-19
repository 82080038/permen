---
description: Development workflow for SKD CAT-BKN application
---

# Development Workflow — SKD CAT-BKN (permen)

## Environment

- **OS**: Windows 10/11
- **Stack**: XAMPP (Apache + MariaDB + PHP 8.x)
- **Path**: `C:\xampp\htdocs\permen`
- **Node.js**: 18+ with npm
- **PHP**: `C:\xampp\php\php.exe`
- **MySQL**: `C:\xampp\mysql\bin\mysql.exe`
- **URL**: `http://localhost/permen`

## Quick Start

// turbo
### 1. Start XAMPP
Start Apache and MySQL from XAMPP Control Panel.

### 2. Setup Environment
```powershell
# .env harus berisi:
# DB_HOST=localhost
# DB_NAME=skd_cat_bkn
# DB_USER=root
# DB_PASS=root
# DB_CHARSET=utf8mb4
# APP_ENV=development
# BASE_URL=/permen
```

// turbo
### 3. Verify Database
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -proot -e "USE skd_cat_bkn; SELECT COUNT(*) FROM questions;"
```

// turbo
### 4. Install Dependencies
```powershell
npm install
```

// turbo
### 5. Run Tests
```powershell
npx playwright test tests/comprehensive-test.spec.js --reporter=list
```

## Test Users

| Role  | No HP        | Password     |
|-------|--------------|--------------|
| Admin | 081265511982 | Sihaloho1982 |
| User  | 081987654321 | Sihaloho1982 |

## Running Tests

```powershell
# Headless (CI mode)
npx playwright test tests/comprehensive-test.spec.js

# Headed (visible browser)
npx playwright test tests/comprehensive-test.spec.js --headed

# Debug mode
npx playwright test --debug

# Specific test
npx playwright test -g "Login"
```

## Database Management

```powershell
# Export database
C:\xampp\mysql\bin\mysqldump.exe -u root -proot skd_cat_bkn > sql\skd_cat_bkn_current.sql

# Import database
C:\xampp\mysql\bin\mysql.exe -u root -proot skd_cat_bkn < sql\skd_cat_bkn_current.sql
```

## Project Structure

```
permen/
├── api/           # REST API endpoints
├── assets/        # CSS, JS, images
├── pages/         # PHP pages (login, dashboard, tryout, etc.)
├── includes/      # Shared components (navigation.php)
├── src/           # PSR-4 classes (App\*)
├── sql/           # Database SQL files
├── tests/         # Playwright E2E tests
├── .devin/        # Windsurf/Devin workflows
├── config.php     # DB + session config
├── helpers.php    # Helper functions
├── env_loader.php # .env parser
├── index.php      # Landing page
└── .env           # Environment vars (gitignored)
```

## Key Configuration

- **Database**: `config.php` + `env_loader.php` + `.env`
- **Session**: `config.php` (1 hour lifetime, file-based)
- **Security**: CSRF tokens, rate limiting, bcrypt passwords
- **Production**: `bimbel.bereng.info` (Hostinger)

## Common Tasks

### Add questions
Admin dashboard → Soal tab → Tambah/Generator Massal

### Deploy to production
```powershell
git add -A && git commit -m "message" && git push origin main
# Then update files on Hostinger via File Manager or Git
```

## Troubleshooting

- **DB connection fails**: Check `.env` credentials, ensure MySQL running
- **500 errors**: Check `C:\xampp\apache\logs\error.log`
- **Session issues**: Clear cookies, check `session.gc_maxlifetime`
- **Test fails**: Ensure XAMPP running, check `http://localhost/permen`
