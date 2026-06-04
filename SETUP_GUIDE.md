# Setup Guide for SKD CAT-BKN Application

This guide helps developers set up the application on their local machine.

## Quick Start

### Prerequisites

- **XAMPP** (for Windows) or **LAMP** (for Linux/Mac)
- **Node.js** 16+ 
- **Git**
- **PHP** 7.4+
- **MySQL** 5.7+ / MariaDB 10.3+

### Installation Steps

#### 1. Clone the Repository

```bash
git clone <repository-url>
cd permen
```

#### 2. Start Web Server

**Linux (XAMPP):**
```bash
sudo /opt/lampp/lampp start
```

**Windows (XAMPP):**
- Open XAMPP Control Panel
- Start Apache and MySQL

#### 3. Database Setup

**Option A: Import from SQL Export (Recommended)**
```bash
# Linux
/opt/lampp/bin/mysql -u root -proot < database_export.sql

# Windows
C:\xampp\mysql\bin\mysql -u root -proot < database_export.sql
```

**Option B: Import from Individual SQL Files**
```bash
# Import in order
mysql -u root -proot < sql/01_create_tables.sql
mysql -u root -proot < sql/02_insert_users.sql
mysql -u root -proot < sql/03_insert_questions.sql
# ... continue with other SQL files
```

#### 4. Configure Environment

```bash
# Copy example file
cp .env.example .env

# Edit with your credentials
nano .env  # Linux
notepad .env  # Windows
```

**Required Environment Variables:**
```env
DB_HOST=localhost
DB_NAME=skd_cat_bkn
DB_USER=root
DB_PASS=your_password
DB_CHARSET=utf8mb4
APP_ENV=development
BASE_URL=http://localhost/permen
GEMINI_API_KEY=your_gemini_api_key  # Optional
```

#### 5. Install Dependencies

```bash
# Install Node.js dependencies
npm install

# Install PHP dependencies (if using composer)
composer install
```

#### 6. Verify Installation

Open browser and navigate to:
- **Application:** `http://localhost/permen`
- **Admin Dashboard:** `http://localhost/permen/pages/admin_dashboard.php?quick=admin`
- **User Dashboard:** `http://localhost/permen/pages/user_dashboard.php?quick=budi`

## Configuration Details

### Database Configuration

Edit `config.php` if you need to change database settings:
```php
$host    = $_ENV['DB_HOST']    ?? 'localhost';
$db      = $_ENV['DB_NAME']    ?? 'skd_cat_bkn';
$user    = $_ENV['DB_USER']    ?? 'root';
$pass    = $_ENV['DB_PASS']    ?? '';
```

### Session Configuration

Session settings in `config.php`:
```php
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
```

### Apache Configuration

`.htaccess` handles:
- Security headers
- Gzip compression
- Browser caching
- File protection

## Testing Setup

### Playwright E2E Tests

```bash
# Install Playwright browsers
npx playwright install

# Run tests in headed mode (with browser UI)
npx playwright test --headed

# Run tests in headless mode
npx playwright test

# View test report
npx playwright show-report
```

### PHP Unit Tests

```bash
# Run PHPUnit
./vendor/bin/phpunit

# Run specific test
./vendor/bin/phpunit tests/HelpersTest.php
```

## Development Workflow

### Running the Application

1. Start Apache and MySQL
2. Open browser to `http://localhost/permen`
3. Login using quick login for testing:
   - Admin: `?quick=admin`
   - User: `?quick=budi`

### Common Tasks

**Adding Questions:**
1. Access admin dashboard
2. Go to "Soal" tab
3. Use "Generator Massal" or add manually

**Exporting Database:**
```bash
mysqldump -u root -proot skd_cat_bkn > database_export.sql
```

**Importing Database:**
```bash
mysql -u root -proot skd_cat_bkn < database_export.sql
```

## Troubleshooting

### Database Connection Failed

**Symptoms:** "Koneksi database gagal" error

**Solutions:**
1. Check MySQL is running:
   ```bash
   sudo /opt/lampp/lampp status
   ```
2. Verify credentials in `.env`
3. Check database exists:
   ```bash
   mysql -u root -proot -e "SHOW DATABASES;"
   ```

### Session Issues

**Symptoms:** Login not persisting, frequent logouts

**Solutions:**
1. Check session directory permissions
2. Verify `session.gc_maxlifetime` in `config.php`
3. Clear browser cookies
4. Check browser privacy settings

### Test Failures

**Symptoms:** Playwright tests failing

**Solutions:**
1. Ensure web server is running
2. Check BASE_URL in `playwright.config.js`
3. Verify database has test data
4. Run in headed mode to debug: `npx playwright test --headed --debug`

### Permission Issues (Linux)

**Symptoms:** File write errors, session not saving

**Solutions:**
```bash
# Fix directory permissions
sudo chown -R www-data:www-data /opt/lampp/htdocs/permen
sudo chmod -R 755 /opt/lampp/htdocs/permen
```

## Project Structure Overview

```
permen/
├── api/              # API endpoints (get_soal, submit_jawaban, etc.)
├── assets/           # Static assets (CSS, JS, images)
├── content/          # Content management files
├── docs/             # Documentation (ARCHITECTURE, TEAM_ANALYSIS_REPORT)
├── pages/            # Page files (login, dashboard, tryout)
├── scripts/          # Utility scripts
├── sql/              # SQL migration files
├── tests/            # Test files (Playwright, PHPUnit)
├── .env              # Environment variables (DO NOT COMMIT)
├── .env.example      # Example environment file
├── .htaccess         # Apache configuration
├── config.php        # Database and session config
├── helpers.php       # Helper functions
└── index.php         # Landing page
```

## Security Notes

### Before Production Deployment

1. **Change Default Passwords**
   - Update admin passwords in database
   - Change database root password

2. **Environment Variables**
   - Never commit `.env` file
   - Use strong passwords
   - Generate secure API keys

3. **File Permissions**
   - Restrict write permissions
   - Protect sensitive files with `.htaccess`

4. **HTTPS**
   - Enable SSL/TLS in production
   - Update BASE_URL to use `https://`

5. **Session Security**
   - Review session timeout settings
   - Consider implementing 2FA for admin

## Getting Help

- **Documentation:** See `docs/` directory
- **Architecture:** `docs/ARCHITECTURE.md`
- **Team Analysis:** `docs/TEAM_ANALYSIS_REPORT.md`
- **Workflows:** `.windsurf/workflows/`

## Recent Updates (June 2026)

### Critical Fixes Implemented
1. Session expiry handling with 5-minute warning
2. Tightened timer tolerances (10s per subtest, 1min total)
3. Fixed N+1 query problem in question generation
4. Added database transactions to prevent race conditions
5. Implemented localStorage quota handling

See `docs/TEAM_ANALYSIS_REPORT.md` for complete analysis.

## Next Steps

After setup:
1. Read `docs/ARCHITECTURE.md` to understand system design
2. Review `docs/TEAM_ANALYSIS_REPORT.md` for improvement roadmap
3. Check `.windsurf/workflows/development.md` for development workflow
4. Run tests to verify installation: `npx playwright test --headed`
