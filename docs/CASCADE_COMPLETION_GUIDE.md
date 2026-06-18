# Cascade Completion Guide - SKD CAT-BKN Application

**Purpose:** Comprehensive guide for Cascade AI to complete the SKD CAT-BKN application to production-ready state.

**Current Status:** Production Ready (82/100) with minor improvements needed
**Last Updated:** 16 June 2026
**Application Location:** `/opt/lampp/htdocs/permen`
**Repository:** https://github.com/82080038/permen.git

---

## Executive Summary

The SKD CAT-BKN (Seleksi Kompetensi Dasar - Computer Assisted Test) application is a PHP-based exam preparation platform for Indonesian civil service entrance exams. The application is currently **production-ready** with a score of 82/100, but requires specific improvements to reach optimal state.

### Current Strengths
- ✅ Security: CSRF protection, rate limiting, prepared statements, session security
- ✅ Testing: 97.1% coverage with Playwright E2E tests
- ✅ Code Quality: PSR-4 autoloading, error handling, API standardization
- ✅ Features: Full tryout system (110 questions), user management, admin dashboard
- ✅ Deployment: Environment configuration, security headers, file protection

### Critical Issues to Address
1. **Security:** Remove inline JavaScript for CSP compliance
2. **Architecture:** Unify legacy/modern hybrid approach
3. **Performance:** Implement caching layer and optimize queries
4. **Scalability:** Migrate to Redis for session storage
5. **Maintainability:** Standardize API responses and database migrations

---

## Phase 1: Critical Security & Architecture Improvements (Priority: HIGH)

### 1.1 Remove Inline JavaScript for CSP Compliance

**Problem:** Content Security Policy uses `unsafe-inline` which is a security risk.

**Files to Modify:**
- `pages/tryout.php` (~500 lines of inline JS)
- `pages/user_dashboard.php` (inline chart JS)
- `pages/admin_dashboard.php` (inline analytics JS)
- All other pages with inline scripts

**Solution:**
```bash
# Create new directory structure
mkdir -p assets/js/src
mkdir -p assets/js/dist

# Extract inline JavaScript to separate files
# Example for tryout.php:
# assets/js/src/tryout.js
class TryoutManager {
    constructor(sessionId, subtesTimers, strictMode) {
        this.sessionId = sessionId;
        this.subtesTimers = subtesTimers;
        this.strictMode = strictMode;
        this.currentIndex = 0;
        this.soalList = [];
        this.init();
    }
    
    async init() {
        await this.loadSoal();
        this.startTimer();
        this.bindEvents();
    }
    
    async loadSoal() {
        const response = await fetch(`/permen/api/get_soal.php?session_id=${this.sessionId}`);
        const data = await response.json();
        if (data.success) {
            this.soalList = data.data.soal;
            this.renderSoal();
        }
    }
    
    // ... rest of methods
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    const sessionId = document.querySelector('[data-session-id]')?.dataset.sessionId;
    if (sessionId) {
        window.tryoutManager = new TryoutManager(sessionId, window.subtesTimers, window.strictMode);
    }
});
```

**Implementation Steps:**
1. Extract all inline JS to `assets/js/src/` directory
2. Use esbuild for bundling: `npm install --save-dev esbuild`
3. Add build script to `package.json`:
```json
{
  "scripts": {
    "build:js": "esbuild assets/js/src/*.js --bundle --outdir=assets/js/dist --minify"
  }
}
```
4. Update CSP in `.htaccess` to remove `unsafe-inline`
5. Load scripts in pages: `<script src="/permen/assets/js/dist/tryout.js" defer></script>`

### 1.2 Unify Architecture (Remove Hybrid Legacy/Modern)

**Problem:** Application uses both legacy procedural and modern OOP approaches.

**Files to Modify:**
- `config.php` (remove fallback to legacy)
- All API endpoints (use modern approach consistently)

**Solution:**
```php
// config.php - Remove legacy fallback
require __DIR__ . '/env_loader.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use App\Core\App;
use App\Database\Database;

// Always use modern approach
$app = App::getInstance();
$pdo = $app->database()->getPdo();

// Dependency injection container
$container = [
    'pdo' => $pdo,
    'app' => $app,
    'security' => $app->security()
];

// Helper function for container access
function app(string $key = null) {
    global $container;
    return $key ? ($container[$key] ?? null) : $container;
}
```

**Implementation Steps:**
1. Remove legacy PDO connection code from `config.php`
2. Update all API endpoints to use `app('pdo')` instead of `$GLOBALS['pdo']`
3. Remove duplicate database connections in API files
4. Test all endpoints after changes

### 1.3 Standardize API Response Format

**Problem:** Inconsistent API response formats across endpoints.

**Solution:**
```php
// src/Http/ApiResponse.php (already exists, ensure all endpoints use it)
namespace App\Http;

class ApiResponse
{
    public static function success(array $data = [], string $message = ''): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    public static function error(string $message, int $code = 400, array $errors = []): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c')
        ]);
        exit;
    }
}
```

**Implementation Steps:**
1. Audit all 58 API endpoints for response format
2. Update endpoints to use `ApiResponse::success()` and `ApiResponse::error()`
3. Update frontend JavaScript to handle standardized format
4. Test all API calls

---

## Phase 2: Performance & Scalability Improvements (Priority: HIGH)

### 2.1 Implement Caching Layer

**Problem:** No caching layer for frequently accessed data.

**Solution:**
```php
// src/Cache/Cache.php
namespace App\Cache;

class Cache
{
    private static ?\Redis $redis = null;
    
    public static function init(): void
    {
        if (self::$redis === null) {
            self::$redis = new \Redis();
            self::$redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', $_ENV['REDIS_PORT'] ?? 6379);
        }
    }
    
    public static function get(string $key): ?string
    {
        self::init();
        return self::$redis->get($key);
    }
    
    public static function set(string $key, string $value, int $ttl = 3600): bool
    {
        self::init();
        return self::$redis->setex($key, $ttl, $value);
    }
    
    public static function delete(string $key): bool
    {
        self::init();
        return self::$redis->del($key) > 0;
    }
}
```

**Implementation Steps:**
1. Install Redis: `sudo apt-get install redis-server`
2. Add Redis to `.env.example`:
```
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```
3. Cache frequently accessed data (questions, materi, leaderboard)
4. Implement cache invalidation on data updates
5. Test cache hit/miss rates

### 2.2 Optimize Database Queries

**Problem:** N+1 queries and missing indexes.

**Solution:**
```sql
-- Add missing indexes
CREATE INDEX idx_answers_session_question ON answers(session_id, question_id);
CREATE INDEX idx_questions_subtes_topik ON questions(subtes, topik);
CREATE INDEX idx_questions_is_active ON questions(is_active);
CREATE INDEX idx_learning_analytics_user_event ON learning_analytics(user_id, event_type);
CREATE INDEX idx_api_rate_limits_created ON api_rate_limits(created_at);

-- Fix N+1 query in admin_dashboard.php
-- Before: Multiple separate queries
-- After: Single query with subqueries
SELECT 
    (SELECT COUNT(*) FROM questions) as total_soal,
    (SELECT COUNT(*) FROM users WHERE role='user') as total_users,
    (SELECT COUNT(*) FROM tryout_sessions) as total_tryout,
    (SELECT COUNT(*) FROM tryout_sessions WHERE status='selesai') as tryout_selesai;
```

**Implementation Steps:**
1. Run index creation SQL
2. Audit all queries for N+1 patterns
3. Optimize heavy queries (get_soal.php)
4. Add query logging for monitoring
5. Benchmark before/after performance

### 2.3 Migrate Session Storage to Redis

**Problem:** File-based sessions don't scale for multi-server deployment.

**Solution:**
```php
// config.php - Update session configuration
use App\Cache\Cache;

// Redis session handler
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://' . ($_ENV['REDIS_HOST'] ?? '127.0.0.1') . ':' . ($_ENV['REDIS_PORT'] ?? 6379));
```

**Implementation Steps:**
1. Install php-redis extension: `sudo apt-get install php-redis`
2. Update session configuration in `config.php`
3. Test session persistence
4. Monitor Redis memory usage

---

## Phase 3: Database & Migration System (Priority: MEDIUM)

### 3.1 Implement Database Migration System

**Problem:** No version control for database changes.

**Solution:**
```php
// scripts/migrate.php
<?php
require __DIR__ . '/../config.php';

$migrationsDir = __DIR__ . '/../sql/migrations';
$migrationsTable = 'migrations';

// Create migrations table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS $migrationsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Get executed migrations
$executed = $pdo->query("SELECT migration FROM $migrationsTable")->fetchAll(PDO::FETCH_COLUMN);

// Get pending migrations
$files = glob("$migrationsDir/*.sql");
sort($files);

foreach ($files as $file) {
    $migration = basename($file);
    if (!in_array($migration, $executed)) {
        echo "Running: $migration\n";
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        $pdo->prepare("INSERT INTO $migrationsTable (migration) VALUES (?)")->execute([$migration]);
        echo "Done: $migration\n";
    }
}

echo "All migrations completed.\n";
```

**Implementation Steps:**
1. Create `sql/migrations/` directory
2. Move existing SQL files to migrations with version prefixes
3. Create migration runner script
4. Add to `package.json`: `"migrate": "php scripts/migrate.php"`
5. Document migration process

### 3.2 Standardize Column Names

**Problem:** Inconsistent naming (snake_case vs camelCase).

**Solution:**
```sql
-- sql/migrations/20260616_standardize_column_names.sql
-- Standardize to snake_case
ALTER TABLE questions CHANGE COLUMN jawabanBenar jawaban_benar VARCHAR(1);
ALTER TABLE questions CHANGE COLUMN createdAt created_at TIMESTAMP;
ALTER TABLE questions CHANGE COLUMN updatedAt updated_at TIMESTAMP;
-- ... continue for all tables
```

**Implementation Steps:**
1. Audit all tables for naming inconsistencies
2. Create migration script
3. Update PHP code to use standardized names
4. Test all queries after migration

### 3.3 Add Foreign Key Constraints

**Problem:** Missing referential integrity.

**Solution:**
```sql
-- sql/migrations/20260616_add_foreign_keys.sql
ALTER TABLE answers 
    ADD CONSTRAINT fk_answers_session 
    FOREIGN KEY (session_id) REFERENCES tryout_sessions(id) ON DELETE CASCADE;

ALTER TABLE answers 
    ADD CONSTRAINT fk_answers_question 
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE RESTRICT;

ALTER TABLE session_subtes 
    ADD CONSTRAINT fk_session_subtes_session 
    FOREIGN KEY (session_id) REFERENCES tryout_sessions(id) ON DELETE CASCADE;
```

**Implementation Steps:**
1. Identify all relationships
2. Add foreign key constraints
3. Test cascade operations
4. Document ERD

### 3.4 Implement Soft Delete

**Problem:** Permanent deletion without audit trail.

**Solution:**
```sql
-- sql/migrations/20260616_add_soft_delete.sql
ALTER TABLE questions ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE materi ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Create views for active data
CREATE VIEW active_questions AS 
SELECT * FROM questions WHERE deleted_at IS NULL;

CREATE VIEW active_users AS 
SELECT * FROM users WHERE deleted_at IS NULL;
```

```php
// helpers.php
function softDelete(PDO $pdo, string $table, int $id): bool
{
    $stmt = $pdo->prepare("UPDATE $table SET deleted_at = NOW() WHERE id = ?");
    return $stmt->execute([$id]);
}

function restore(PDO $pdo, string $table, int $id): bool
{
    $stmt = $pdo->prepare("UPDATE $table SET deleted_at = NULL WHERE id = ?");
    return $stmt->execute([$id]);
}
```

**Implementation Steps:**
1. Add `deleted_at` columns
2. Create views for active data
3. Update queries to use views
4. Implement soft delete functions
5. Update admin interfaces

---

## Phase 4: Code Quality & Maintainability (Priority: MEDIUM)

### 4.1 Extract Inline CSS

**Problem:** CSS embedded in every page causes duplication.

**Solution:**
```bash
# Create CSS structure
mkdir -p assets/css
mkdir -p assets/css/pages
mkdir -p assets/css/themes

# assets/css/base.css
:root {
    --color-primary: #1a5276;
    --color-secondary: #2980b9;
    --color-success: #27ae60;
    --color-danger: #e74c3c;
    --color-warning: #f39c12;
    
    --bg-body: #f5f7fa;
    --bg-card: #ffffff;
    --text-main: #222222;
    --text-muted: #555555;
    
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
    
    --border-radius: 8px;
    --shadow-sm: 0 2px 6px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
}

[data-theme="dark"] {
    --bg-body: #1a1a2e;
    --bg-card: #16213e;
    --text-main: #e8e8e8;
    --text-muted: #d0d0d0;
}
```

**Implementation Steps:**
1. Extract common CSS to `assets/css/base.css`
2. Create page-specific CSS files
3. Use CSS variables for theming
4. Minify CSS with build process
5. Load CSS in all pages

### 4.2 Implement Router Pattern

**Problem:** No centralized routing for API endpoints.

**Solution:**
```php
// src/Http/Router.php
namespace App\Http;

class Router
{
    private array $routes = [];
    
    public function get(string $path, callable $handler): self
    {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }
    
    public function post(string $path, callable $handler): self
    {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }
    
    public function dispatch(string $method, string $uri): mixed
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = str_replace('/permen/api/', '', $path);
        
        if (isset($this->routes[$method][$path])) {
            return call_user_func($this->routes[$method][$path]);
        }
        
        http_response_code(404);
        return ['error' => 'Not Found'];
    }
}

// api/index.php - New entry point
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Http/Router.php';

use App\Http\Router;

$router = new Router();

// Register routes
$router->get('get_soal', function() {
    // Handle get_soal logic
});

$router->post('submit_jawaban', function() {
    // Handle submit_jawaban logic
});

// Dispatch
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$result = $router->dispatch($method, $uri);
```

**Implementation Steps:**
1. Create Router class
2. Migrate API endpoints to router
3. Update API calls to use new structure
4. Test all routes

### 4.3 Add Type Hints

**Problem:** Missing type declarations reduce code safety.

**Solution:**
```php
// Before
function validateCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// After
declare(strict_types=1);

function validateCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
```

**Implementation Steps:**
1. Add `declare(strict_types=1);` to all PHP files
2. Add type hints to function parameters
3. Add return type declarations
4. Run PHPStan for static analysis
5. Fix type errors

---

## Phase 5: Feature Completion (Priority: MEDIUM)

### 5.1 Complete Admin Panel CMS (M3)

**Remaining Tasks:**
- CRUD soal with WYSIWYG editor
- CRUD materi & tips with HTML editor
- Upload gambar untuk soal figural
- Manajemen tryout per-event
- Export hasil ke Excel/CSV

**Implementation:**
```php
// api/admin_soal_crud.php - Enhance with full CRUD
// Add TinyMCE or Quill for WYSIWYG
// Add image upload with validation
// Add Excel export with PHPSpreadsheet
```

### 5.2 Enhance Tryout Features (M4)

**Remaining Tasks:**
- Navigasi soal lanjut (ragu-ragu, review)
- Mode ketat (tidak bisa kembali)
- Paket tryout harian/mingguan
- Bank soal 500+ soal
- Soal gambar (figural)

**Implementation:**
```javascript
// Add ragu-ragu checkbox
// Add review mode
// Add strict mode toggle
// Add scheduling system
// Add figural question support
```

### 5.3 Complete Ranking & Community (M5)

**Remaining Tasks:**
- Share hasil ke media sosial
- Forum diskusi per topik
- Grup belajar

**Implementation:**
```php
// Add social sharing card generator
// Add forum CRUD
// Add group management
```

---

## Phase 6: Deployment & DevOps (Priority: HIGH)

### 6.1 Automated Backups

**Solution:**
```bash
# scripts/backup.sh
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/permen"
DB_NAME="skd_cat_bkn"

# Backup database
mysqldump -u root -proot $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /opt/lampp/htdocs/permen

# Keep last 7 days
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

**Implementation Steps:**
1. Create backup script
2. Add to cron: `0 2 * * * /path/to/backup.sh`
3. Test restore process
4. Monitor backup storage

### 6.2 Monitoring & Alerting

**Solution:**
```php
// api/health.php - Enhance with detailed metrics
return [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'metrics' => [
        'database' => checkDatabase(),
        'redis' => checkRedis(),
        'disk' => checkDisk(),
        'memory' => checkMemory()
    ]
];
```

**Implementation Steps:**
1. Add detailed health checks
2. Integrate with monitoring service (Sentry, New Relic)
3. Set up alerts for critical failures
4. Create monitoring dashboard

### 6.3 CI/CD Pipeline

**Solution:**
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests
        run: npx playwright test
      - name: Deploy
        run: |
          # Deploy commands
          ssh user@server 'cd /var/www/permen && git pull && composer install && php scripts/migrate.php'
```

**Implementation Steps:**
1. Create GitHub Actions workflow
2. Add automated testing
3. Add automated deployment
4. Add rollback mechanism

---

## Testing Strategy

### Unit Tests
```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Run tests
vendor/bin/phpunit tests/unit
```

### Integration Tests
```bash
# Run API integration tests
vendor/bin/phpunit tests/integration
```

### E2E Tests (Already Implemented)
```bash
# Run Playwright tests
npx playwright test

# Run specific test
npx playwright test tests/exam-simulation.spec.js
```

### Load Testing
```bash
# Install k6
# Create load test script
k6 run tests/load/exam-load.js
```

---

## Performance Benchmarks

### Current Performance
- Homepage: 1026ms
- Login: 558ms
- Leaderboard: 373ms
- Materi TWK: 161ms
- Latihan: 1384ms

### Target Performance
- Homepage: <500ms
- Login: <300ms
- Leaderboard: <200ms
- Materi TWK: <100ms
- Latihan: <500ms

### Optimization Techniques
1. Enable gzip compression
2. Implement lazy loading for images
3. Add CDN for static assets
4. Optimize database queries
5. Implement caching

---

## Security Checklist

### Before Production
- [ ] Remove all inline JavaScript
- [ ] Remove `unsafe-inline` from CSP
- [ ] Enable HTTPS with valid certificate
- [ ] Set `APP_ENV=production`
- [ ] Disable error display
- [ ] Enable rate limiting
- [ ] Review all file permissions
- [ ] Audit all API endpoints
- [ ] Test CSRF protection
- [ ] Test session security
- [ ] Review dependencies for vulnerabilities
- [ ] Implement 2FA for admin (optional)

### Ongoing Security
- [ ] Regular dependency updates
- [ ] Security audit logs
- [ ] Penetration testing
- [ ] Code review process
- [ ] Security training

---

## Deployment Checklist

### Pre-Deployment
- [ ] All tests passing
- [ ] Database migrations run
- [ ] Environment variables configured
- [ ] Backup current version
- [ ] Document deployment process
- [ ] Notify stakeholders

### Deployment
- [ ] Deploy to staging first
- [ ] Run smoke tests
- [ ] Monitor error logs
- [ ] Check performance metrics
- [ ] Verify all features
- [ ] Deploy to production

### Post-Deployment
- [ ] Monitor for 24 hours
- [ ] Check error rates
- [ ] Verify user feedback
- [ ] Update documentation
- [ ] Create post-mortem if issues

---

## Success Criteria

The application will be considered complete when:

1. **Security Score:** 95/100 (remove inline JS, add 2FA)
2. **Code Quality Score:** 90/100 (add type hints, standardize responses)
3. **Architecture Score:** 85/100 (unify architecture, add router)
4. **Performance Score:** 90/100 (add caching, optimize queries)
5. **Testing Coverage:** 100% (add unit tests, integration tests)
6. **Deployment Score:** 95/100 (automated backups, CI/CD)

**Overall Target Score:** 91/100 (Production Optimized)

---

## Priority Order

Execute phases in this order:

1. **Phase 1** (Critical Security & Architecture) - 2 weeks
2. **Phase 2** (Performance & Scalability) - 2 weeks
3. **Phase 6** (Deployment & DevOps) - 1 week
4. **Phase 3** (Database & Migration) - 1 week
5. **Phase 4** (Code Quality) - 2 weeks
6. **Phase 5** (Feature Completion) - 3 weeks

**Total Estimated Time:** 11 weeks

---

## Resources

### Documentation
- `/opt/lampp/htdocs/permen/docs/` - All documentation
- `/opt/lampp/htdocs/permen/docs/PRODUCTION_READINESS_ASSESSMENT.md` - Current assessment
- `/opt/lampp/htdocs/permen/docs/ROADMAP.md` - Feature roadmap
- `/opt/lampp/htdocs/permen/docs/SARAN_PERBAIKAN_APLIKASI.md` - Detailed improvement suggestions

### Code References
- `/opt/lampp/htdocs/permen/config.php` - Configuration
- `/opt/lampp/htdocs/permen/helpers.php` - Helper functions
- `/opt/lampp/htdocs/permen/src/` - Modern PSR-4 classes
- `/opt/lampp/htdocs/permen/api/` - API endpoints (58 files)
- `/opt/lampp/htdocs/permen/pages/` - Frontend pages (19 files)

### Testing
- `/opt/lampp/htdocs/permen/tests/` - Playwright E2E tests
- Run: `npx playwright test`
- Coverage: 97.1%

### Database
- `/opt/lampp/htdocs/permen/sql/` - SQL files and migrations
- Host: localhost
- Database: skd_cat_bkn
- User: root
- Password: root

---

## Notes for Cascade AI

1. **Always test changes** before committing - use existing Playwright tests
2. **Maintain backward compatibility** when possible
3. **Document all changes** in relevant markdown files
4. **Follow PSR standards** for PHP code
5. **Use type hints** for all new code
6. **Standardize API responses** using ApiResponse class
7. **Cache frequently accessed data** after implementing Redis
8. **Monitor performance** after each optimization
9. **Security first** - never compromise on security
10. **Communicate progress** regularly with user

---

**End of Guide**
