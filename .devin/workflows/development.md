---
description: Development workflow for SKD CAT-BKN application
---

# Development Workflow

## Prerequisites

1. **XAMPP/LAMP Stack**
   - Apache web server
   - MySQL 5.7+ / MariaDB 10.3+
   - PHP 7.4+

2. **Node.js & npm**
   - Node.js 16+ recommended
   - npm for package management

3. **Git**
   - Git for version control

## Initial Setup

### 1. Clone Repository
```bash
git clone <repository-url>
cd permen
```

### 2. Database Setup

#### Option A: Import from SQL Export
```bash
# Start MySQL
sudo /opt/lampp/lampp startmysql

# Import database
/opt/lampp/bin/mysql -u root -proot < database_export.sql
```

#### Option B: Import from SQL Files
```bash
# Import main schema
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/skd_cat_bkn.sql

# Import additional data
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/batch_master_materi.sql
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/batch_tips.sql
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/batch_soal_1_twk.sql
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/batch_soal_1_tiu.sql
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/batch_soal_1_tkp.sql
```

### 3. Environment Configuration
```bash
# Copy example env file
cp .env.example .env

# Edit .env with your credentials
nano .env
```

Required environment variables:
```
DB_HOST=localhost
DB_NAME=skd_cat_bkn
DB_USER=root
DB_PASS=root
DB_CHARSET=utf8mb4
APP_ENV=development
BASE_URL=http://localhost/permen
```

### 4. Install Dependencies
```bash
# Install Node.js dependencies
npm install

# Install PHP dependencies (if using composer)
composer install
```

### 5. Start Web Server
```bash
# Start Apache and MySQL
sudo /opt/lampp/lampp start

# Or start individually
sudo /opt/lampp/lampp startapache
sudo /opt/lampp/lampp startmysql
```

### 6. Access Application
- Open browser: `http://localhost/permen`
- Default admin: Use quick login `?quick=admin`
- Default user: Use quick login `?quick=budi`

## Development Workflow

### Running Tests

#### Playwright E2E Tests
```bash
# Run tests in headed mode (with browser UI)
npx playwright test --headed

# Run tests in headless mode
npx playwright test

# Run specific test file
npx playwright test comprehensive.spec.js

# Run with debug mode
npx playwright test --debug

# View test report
npx playwright show-report
```

#### PHP Unit Tests
```bash
# Run PHPUnit tests
composer test

# Run specific test
./vendor/bin/phpunit tests/HelpersTest.php
```

### Code Quality

#### PHP CS Fixer
```bash
# Check code style
composer cs-check

# Fix code style automatically
composer cs-fix
```

#### PHPStan (Static Analysis)
```bash
# Run static analysis
composer phpstan
```

### Database Management

#### Export Database
```bash
# Export current database state
/opt/lampp/bin/mysqldump -u root -proot skd_cat_bkn > database_export.sql
```

#### Import Database
```bash
# Import database from file
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < database_export.sql
```

### Common Development Tasks

#### Adding New Questions
1. Access admin dashboard: `http://localhost/permen/pages/admin_dashboard.php`
2. Go to "Soal" tab
3. Click "Tambah Soal" or use "Generator Massal"
4. Fill in question details
5. Save

#### Modifying Configuration
- Edit `.env` for environment variables
- Edit `config.php` for database and session settings
- Edit `.htaccess` for Apache configuration

#### Adding New API Endpoints
1. Create new file in `api/` directory
2. Include `config.php` and `helpers.php`
3. Implement authentication and rate limiting
4. Return JSON responses with proper HTTP status codes

## Project Structure

```
permen/
├── api/                    # API endpoints
│   ├── get_soal.php
│   ├── submit_jawaban.php
│   └── ...
├── assets/                 # Static assets (CSS, JS, images)
├── content/                # Content management
├── docs/                   # Documentation
│   ├── ARCHITECTURE.md
│   └── TEAM_ANALYSIS_REPORT.md
├── pages/                  # Page files
│   ├── login.php
│   ├── user_dashboard.php
│   ├── admin_dashboard.php
│   └── tryout.php
├── scripts/                # Utility scripts
├── sql/                    # SQL migration files
├── tests/                  # Test files
│   ├── comprehensive.spec.js
│   └── ...
├── .env                    # Environment variables (not in git)
├── .env.example            # Example environment file
├── .gitignore              # Git ignore rules
├── .htaccess               # Apache configuration
├── config.php              # Database and session config
├── helpers.php             # Helper functions
├── index.php               # Landing page
├── package.json            # Node.js dependencies
├── playwright.config.js    # Playwright configuration
└── README.md               # Project documentation
```

## Key Files to Understand

### Core Configuration
- `config.php` - Database connection, session management
- `helpers.php` - Reusable helper functions (CSRF, rate limiting, etc.)
- `.htaccess` - Apache security headers and routing

### Main Application Logic
- `pages/tryout.php` - Tryout interface with timer and anti-cheating
- `api/get_soal.php` - Question retrieval API
- `api/submit_jawaban.php` - Answer submission API
- `pages/user_dashboard.php` - User dashboard with analytics
- `pages/admin_dashboard.php` - Admin panel for management

### Testing
- `tests/comprehensive.spec.js` - Comprehensive E2E tests
- `playwright.config.js` - Playwright test configuration

## Recent Critical Fixes (June 2026)

1. **Session Expiry Handling** - Added 5-minute warning with auto-save
2. **Timer Tolerances** - Tightened from 60s/5min to 10s/1min
3. **N+1 Query Problem** - Optimized question generation in get_soal.php
4. **Database Transactions** - Added transactions to prevent race conditions
5. **localStorage Quota** - Added quota exceeded handling with auto-cleanup

See `docs/TEAM_ANALYSIS_REPORT.md` for full analysis and recommendations.

## Troubleshooting

### Database Connection Issues
- Check MySQL is running: `sudo /opt/lampp/lampp status`
- Verify credentials in `.env`
- Check database exists: `/opt/lampp/bin/mysql -u root -proot -e "SHOW DATABASES;"`

### Session Issues
- Check session directory permissions
- Verify `session.gc_maxlifetime` in `config.php`
- Clear browser cookies

### Test Failures
- Ensure web server is running
- Check BASE_URL in `playwright.config.js`
- Verify database has test data
- Run tests in headed mode to see browser actions

## Deployment

See `/deployment.md` for deployment instructions.
