// @ts-nocheck
/**
 * Comprehensive Application Audit - SKD CAT-BKN
 * Tests: Admin flows, API endpoints, edge cases, security, console/network monitoring
 */
const { test, expect, request } = require('@playwright/test');

const BASE = process.env.TEST_BASE_URL || 'http://localhost/permen';

const ADMIN = { no_hp: '081265511982', password: 'Sihaloho1982' };
const PESERTA = { no_hp: '081200001111', password: 'Simulasi2025!' };

let consoleErrors = [];
let networkErrors = [];
let allConsoleLogs = [];

function setupMonitors(page) {
  consoleErrors = [];
  networkErrors = [];
  allConsoleLogs = [];

  page.on('console', msg => {
    const text = msg.text();
    allConsoleLogs.push(`[${msg.type()}] ${text}`);
    if (msg.type() === 'error') {
      if (!text.includes('net::ERR_ABORTED') && !text.includes('favicon')) {
        consoleErrors.push(text);
      }
    }
  });
  page.on('pageerror', err => consoleErrors.push(err.message));
  page.on('response', resp => {
    const url = resp.url();
    if (resp.status() >= 400 && !url.includes('favicon') && !url.includes('learning_analytics')) {
      networkErrors.push(`[${resp.status()}] ${url}`);
    }
  });
  page.on('requestfailed', req => {
    const url = req.url();
    if (!url.includes('favicon') && !url.includes('learning_analytics')) {
      networkErrors.push(`[FAIL] ${url}`);
    }
  });
}

function reportErrors(label) {
  if (consoleErrors.length > 0) {
    console.log(`\n  ⚠️ CONSOLE ERRORS [${label}]:`);
    consoleErrors.forEach(e => console.log(`    ❌ ${e}`));
  }
  if (networkErrors.length > 0) {
    console.log(`\n  ⚠️ NETWORK ERRORS [${label}]:`);
    networkErrors.forEach(e => console.log(`    ❌ ${e}`));
  }
}

// ═══════════════════════════════════════════════════════════════════
// ADMIN FLOW TESTS
// ═══════════════════════════════════════════════════════════════════
test.describe.serial('Admin Flow Tests', () => {

  test('1. Admin login → dashboard', async ({ page }) => {
    setupMonitors(page);
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(ADMIN.no_hp);
    await page.locator('input[name="password"]').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/admin_dashboard/, { timeout: 30000 });

    const title = await page.title();
    console.log(`  ✓ Admin login OK → ${title}`);
    expect(page.url()).toContain('admin_dashboard');
    reportErrors('Admin login');
    expect(consoleErrors).toEqual([]);
  });

  test('2. Admin dashboard - content & charts', async ({ page, context }) => {
    setupMonitors(page);
    // Login first
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(ADMIN.no_hp);
    await page.locator('input[name="password"]').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/admin_dashboard/, { timeout: 15000 });

    await page.waitForTimeout(3000);

    const bodyText = await page.textContent('body');
    expect(bodyText).toContain('Admin');
    expect(bodyText.length > 100).toBeTruthy();

    // Check for stats cards
    const statsCards = await page.locator('.stat, .stats > div, [class*="stat"]').count();
    console.log(`  ✓ Stats cards found: ${statsCards}`);

    reportErrors('Admin dashboard');
    expect(consoleErrors).toEqual([]);
  });

  test('3. Admin user management page', async ({ page }) => {
    setupMonitors(page);
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(ADMIN.no_hp);
    await page.locator('input[name="password"]').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/admin_dashboard/, { timeout: 15000 });

    await page.goto(`${BASE}/pages/admin_users.php`, { waitUntil: 'domcontentloaded' });
    const title = await page.title();
    console.log(`  ✓ Admin users page: ${title}`);

    // Check user table exists
    const tableExists = await page.locator('table').count();
    expect(tableExists).toBeGreaterThan(0);

    reportErrors('Admin users');
    expect(consoleErrors).toEqual([]);
  });

  test('4. Admin scheduled tryouts page', async ({ page }) => {
    setupMonitors(page);
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(ADMIN.no_hp);
    await page.locator('input[name="password"]').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/admin_dashboard/, { timeout: 15000 });

    await page.goto(`${BASE}/pages/admin_scheduled_tryouts.php`, { waitUntil: 'domcontentloaded' });
    const title = await page.title();
    console.log(`  ✓ Admin scheduled tryouts: ${title}`);

    reportErrors('Admin scheduled tryouts');
    expect(consoleErrors).toEqual([]);
  });

  test('5. Admin navigation - all admin links work', async ({ page }) => {
    setupMonitors(page);
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(ADMIN.no_hp);
    await page.locator('input[name="password"]').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/admin_dashboard/, { timeout: 15000 });

    // Get all nav links
    const navLinks = await page.locator('#navMenu a').all();
    console.log(`  ✓ Found ${navLinks.length} admin nav links`);

    for (const link of navLinks) {
      const href = await link.getAttribute('href');
      const text = (await link.textContent()).trim();
      if (href && !href.startsWith('http') && !href.startsWith('#')) {
        const resp = await page.goto(`${BASE}/${href.replace(/^\.\.\//, '').replace(/^pages\//, 'pages/')}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
        const status = resp?.status() ?? 0;
        if (status === 200) {
          console.log(`    ✓ ${text} → ${status}`);
        } else {
          console.log(`    ✗ ${text} → ${status}`);
        }
        expect(status).toBeLessThan(400);
      }
    }
    reportErrors('Admin nav links');
  });
});

// ═══════════════════════════════════════════════════════════════════
// API ENDPOINT TESTS
// ═══════════════════════════════════════════════════════════════════
test.describe('API Endpoint Tests', () => {

  test('6. Public API - health check', async ({ request }) => {
    const resp = await request.get(`${BASE}/api/health.php`);
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.success).toBe(true);
    expect(body.data.status).toBe('healthy');
    console.log(`  ✓ Health check OK - v${body.data.version}`);
  });

  test('7. Public API - landing stats', async ({ request }) => {
    const resp = await request.get(`${BASE}/api/get_landing_stats.php`);
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.success).toBe(true);
    expect(body.data).toHaveProperty('user_count');
    console.log(`  ✓ Landing stats OK - ${body.data.user_count} users`);
  });

  test('8. Protected API without auth → 401/403', async ({ request }) => {
    const endpoints = [
      'api/get_dashboard_analytics.php',
      'api/get_notifications.php',
      'api/get_my_feedback.php',
    ];
    for (const ep of endpoints) {
      const resp = await request.get(`${BASE}/${ep}`);
      console.log(`  ${ep} → ${resp.status()}`);
      expect([401, 403]).toContain(resp.status());
    }
  });

  test('9. Admin API without admin auth → 403', async ({ request }) => {
    const endpoints = [
      'api/list_soal.php',
      'api/admin_reports.php',
      'api/monitoring.php',
    ];
    for (const ep of endpoints) {
      const resp = await request.get(`${BASE}/${ep}`);
      console.log(`  ${ep} → ${resp.status()}`);
      expect(resp.status()).toBe(403);
    }
  });
});

// ═══════════════════════════════════════════════════════════════════
// SECURITY & EDGE CASE TESTS
// ═══════════════════════════════════════════════════════════════════
test.describe('Security & Edge Case Tests', () => {

  test('10. Login with wrong password → fail', async ({ page }) => {
    setupMonitors(page);
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(ADMIN.no_hp);
    await page.locator('input[name="password"]').fill('WrongPassword123!');
    await page.locator('button[type="submit"]').click();
    await page.waitForTimeout(2000);

    // Should still be on login page
    expect(page.url()).toContain('login');
    const bodyText = await page.textContent('body');
    // Should show some error message
    console.log(`  ✓ Wrong password rejected, still on login page`);
    reportErrors('Wrong password login');
  });

  test('11. Login with non-existent user → fail', async ({ page }) => {
    setupMonitors(page);
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill('089999999999');
    await page.locator('input[name="password"]').fill('SomePassword123!');
    await page.locator('button[type="submit"]').click();
    await page.waitForTimeout(2000);

    expect(page.url()).toContain('login');
    console.log(`  ✓ Non-existent user rejected`);
  });

  test('12. Register with weak password → fail', async ({ page }) => {
    setupMonitors(page);
    await page.goto(`${BASE}/pages/register.php`);

    // Get CSRF token
    const csrf = await page.locator('input[name="csrf_token"]').inputValue();

    await page.locator('#nama').fill('Test Weak');
    await page.locator('#no_hp').fill('089999111222');
    await page.locator('#password').fill('weak');
    await page.locator('#password2').fill('weak');
    await page.locator('button[type="submit"]').click();
    await page.waitForTimeout(2000);

    // Should stay on register page
    expect(page.url()).toContain('register');
    console.log(`  ✓ Weak password rejected`);
  });

  test('13. XSS attempt in registration', async ({ page }) => {
    setupMonitors(page);
    await page.goto(`${BASE}/pages/register.php`);

    await page.locator('#nama').fill('<script>alert("xss")</script>');
    await page.locator('#no_hp').fill('089999222333');
    await page.locator('#password').fill('StrongPass123!');
    await page.locator('#password2').fill('StrongPass123!');
    await page.locator('button[type="submit"]').click();
    await page.waitForTimeout(3000);

    // Check page doesn't contain unescaped script
    const bodyHtml = await page.content();
    expect(bodyHtml).not.toContain('<script>alert("xss")</script>');
    console.log(`  ✓ XSS attempt sanitized`);
  });

  test('14. Direct admin page access without auth → redirect', async ({ page }) => {
    setupMonitors(page);
    const adminPages = [
      'pages/admin_dashboard.php',
      'pages/admin_users.php',
      'pages/admin_scheduled_tryouts.php',
    ];

    for (const adminPage of adminPages) {
      await page.goto(`${BASE}/${adminPage}`);
      await page.waitForTimeout(1000);
      const url = page.url();
      expect(url).not.toContain('admin');
      console.log(`  ✓ ${adminPage} → redirected to ${url.split('/').pop()}`);
    }
  });

  test('15. CSRF protection on login form', async ({ page }) => {
    setupMonitors(page);
    // Try to login without CSRF token via direct POST
    const resp = await page.request.post(`${BASE}/pages/login.php`, {
      form: {
        no_hp: ADMIN.no_hp,
        password: ADMIN.password,
      },
    });
    // Should not get a successful redirect to dashboard
    const url = resp.url();
    console.log(`  ✓ Login without CSRF: ${resp.status()} (expected non-200 or redirect to login)`);
    // The response should not redirect to admin_dashboard
    expect(url).not.toContain('admin_dashboard');
  });
});

// ═══════════════════════════════════════════════════════════════════
// PAGE CONSOLE ERROR MONITORING
// ═══════════════════════════════════════════════════════════════════
test.describe('Console Error Monitoring - All Pages', () => {

  test('16. Public pages - no console errors', async ({ page }) => {
    const publicPages = [
      { url: '/', name: 'Landing' },
      { url: '/pages/login.php', name: 'Login' },
      { url: '/pages/register.php', name: 'Register' },
      { url: '/pages/forgot_password.php', name: 'Forgot Password' },
      { url: '/pages/help.php', name: 'Help' },
    ];

    for (const p of publicPages) {
      setupMonitors(page);
      await page.goto(`${BASE}${p.url}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await page.waitForTimeout(2000);

      if (consoleErrors.length > 0) {
        console.log(`  ⚠️ ${p.name}: ${consoleErrors.length} console errors`);
        consoleErrors.forEach(e => console.log(`    ❌ ${e}`));
      } else {
        console.log(`  ✓ ${p.name}: no console errors`);
      }
      expect(consoleErrors, `${p.name} has console errors`).toEqual([]);
    }
  });

  test('17. User pages - no console errors', async ({ page }) => {
    setupMonitors(page);
    // Login first
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(PESERTA.no_hp);
    await page.locator('input[name="password"]').fill(PESERTA.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/user_dashboard/, { timeout: 15000 });

    const userPages = [
      { url: '/pages/user_dashboard.php', name: 'Dashboard' },
      { url: '/pages/profile.php', name: 'Profile' },
      { url: '/pages/latihan.php', name: 'Latihan' },
      { url: '/pages/daily_quiz.php', name: 'Daily Quiz' },
      { url: '/pages/leaderboard.php', name: 'Leaderboard' },
      { url: '/pages/feedback.php', name: 'Feedback' },
      { url: '/pages/settings.php', name: 'Settings' },
      { url: '/pages/riwayat_soal.php', name: 'Riwayat Soal' },
      { url: '/pages/scheduled_tryouts.php', name: 'Scheduled Tryouts' },
      { url: '/pages/materi.php?subtes=TWK', name: 'Materi TWK' },
      { url: '/pages/materi.php?subtes=TIU', name: 'Materi TIU' },
      { url: '/pages/materi.php?subtes=TKP', name: 'Materi TKP' },
    ];

    for (const p of userPages) {
      setupMonitors(page);
      await page.goto(`${BASE}${p.url}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await page.waitForTimeout(2000);

      if (consoleErrors.length > 0) {
        console.log(`  ⚠️ ${p.name}: ${consoleErrors.length} console errors`);
        consoleErrors.forEach(e => console.log(`    ❌ ${e}`));
      } else {
        console.log(`  ✓ ${p.name}: no console errors`);
      }
      expect(consoleErrors, `${p.name} has console errors`).toEqual([]);
    }
  });

  test('18. Admin pages - no console errors', async ({ page }) => {
    setupMonitors(page);
    // Login as admin
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(ADMIN.no_hp);
    await page.locator('input[name="password"]').fill(ADMIN.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/admin_dashboard/, { timeout: 30000 });

    const adminPages = [
      { url: '/pages/admin_dashboard.php', name: 'Admin Dashboard' },
      { url: '/pages/admin_users.php', name: 'Admin Users' },
      { url: '/pages/admin_scheduled_tryouts.php', name: 'Admin Scheduled Tryouts' },
    ];

    for (const p of adminPages) {
      setupMonitors(page);
      await page.goto(`${BASE}${p.url}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await page.waitForTimeout(3000);

      if (consoleErrors.length > 0) {
        console.log(`  ⚠️ ${p.name}: ${consoleErrors.length} console errors`);
        consoleErrors.forEach(e => console.log(`    ❌ ${e}`));
      } else {
        console.log(`  ✓ ${p.name}: no console errors`);
      }
      expect(consoleErrors, `${p.name} has console errors`).toEqual([]);
    }
  });
});

// ═══════════════════════════════════════════════════════════════════
// DEAD LINK / 404 CHECK
// ═══════════════════════════════════════════════════════════════════
test.describe('Dead Link Check', () => {

  test('19. All internal links on landing page return 200', async ({ page }) => {
    setupMonitors(page);
    await page.goto(`${BASE}/`, { waitUntil: 'domcontentloaded' });

    const links = await page.locator('a[href]').all();
    const internalLinks = [];

    for (const link of links) {
      const href = await link.getAttribute('href');
      if (href && !href.startsWith('http') && !href.startsWith('#') && !href.startsWith('mailto') && !href.startsWith('tel')) {
        internalLinks.push(href);
      }
    }

    console.log(`  Found ${internalLinks.length} internal links on landing page`);

    for (const href of internalLinks) {
      const fullUrl = href.startsWith('/') ? `${BASE}${href}` : `${BASE}/${href}`;
      const resp = await page.request.get(fullUrl);
      const status = resp.status();
      if (status < 400) {
        console.log(`  ✓ ${href} → ${status}`);
      } else {
        console.log(`  ✗ ${href} → ${status}`);
      }
      expect(status, `Dead link: ${href}`).toBeLessThan(400);
    }
  });

  test('20. All CSS/JS resources load on landing page', async ({ page }) => {
    setupMonitors(page);

    const failedResources = [];
    page.on('response', resp => {
      if (resp.status() >= 400 && (resp.url().includes('.css') || resp.url().includes('.js'))) {
        failedResources.push(`[${resp.status()}] ${resp.url()}`);
      }
    });

    await page.goto(`${BASE}/`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);

    if (failedResources.length > 0) {
      console.log(`  ⚠️ Failed resources:`);
      failedResources.forEach(r => console.log(`    ❌ ${r}`));
    } else {
      console.log(`  ✓ All CSS/JS resources loaded successfully`);
    }
    expect(failedResources).toEqual([]);
  });
});
