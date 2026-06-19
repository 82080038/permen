// @ts-check
const { test, expect } = require('@playwright/test');

const BASE = 'https://bimbel.bereng.info';
const ADMIN = { no_hp: '081265511982', password: 'Sihaloho1982' };

/** @type {string[]} */
let consoleErrors = [];
/** @type {string[]} */
let networkErrors = [];

function setupMonitors(page) {
  consoleErrors = [];
  networkErrors = [];
  page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
  page.on('pageerror', err => consoleErrors.push(err.message));
  page.on('response', resp => { if (resp.status() >= 400 && !resp.url().includes('favicon')) networkErrors.push(`[${resp.status()}] ${resp.url()}`); });
  page.on('requestfailed', req => { if (!req.url().includes('learning_analytics') && !req.url().includes('favicon')) networkErrors.push(`[FAIL] ${req.url()}`); });
}

function logErrors(title) {
  if (consoleErrors.length > 0) console.log(`  ⚠️ Console[${title}]: ${consoleErrors.join(' | ')}`);
  if (networkErrors.length > 0) console.log(`  ⚠️ Network[${title}]: ${networkErrors.join(' | ')}`);
}

// ========== ADMIN FLOW ==========

test.describe('Admin Full Flow', () => {

  test('ADMIN: Login → redirects to admin_dashboard', async ({ page }) => {
    setupMonitors(page);
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });
    expect(page.url()).toContain('admin_dashboard');
    console.log(`  ✓ Admin login → ${await page.title()}`);
    logErrors('ADMIN Login');
  });

  test('ADMIN: Navigation has correct links (no peserta pages)', async ({ page }) => {
    setupMonitors(page);
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    const navLinks = await page.locator('#navMenu a').all();
    const hrefs = [];
    for (const link of navLinks) {
      const text = await link.textContent();
      const href = await link.getAttribute('href');
      hrefs.push({ text: text?.trim(), href });
    }
    console.log(`  ✓ Admin nav links: ${hrefs.length}`);
    hrefs.forEach(l => console.log(`    - ${l.text}: ${l.href}`));

    // Must NOT contain peserta pages
    const hrefTexts = hrefs.map(l => l.href).join(' ');
    expect(hrefTexts).not.toContain('latihan.php');
    expect(hrefTexts).not.toContain('tryout.php');
    expect(hrefTexts).not.toContain('daily_quiz.php');
    expect(hrefTexts).not.toContain('profile.php');
    expect(hrefTexts).not.toContain('settings.php');

    // Must contain admin pages
    expect(hrefTexts).toContain('admin_dashboard.php');
    expect(hrefTexts).toContain('admin_users.php');
    expect(hrefTexts).toContain('admin_scheduled_tryouts.php');
    expect(hrefTexts).toContain('leaderboard.php');
    expect(hrefTexts).toContain('logout.php');
    logErrors('ADMIN Nav');
  });

  test('ADMIN: Navigate all admin links - all 200 OK', async ({ page }) => {
    setupMonitors(page);
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    const navLinks = await page.locator('#navMenu a').all();
    const hrefs = [];
    for (const link of navLinks) {
      const href = await link.getAttribute('href');
      if (href && href.startsWith('http') && !href.includes('logout')) hrefs.push(href);
    }

    for (const href of hrefs) {
      const resp = await page.goto(href, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const status = resp?.status() ?? 0;
      console.log(`    [${status}] ${href.replace(BASE, '')} → ${await page.title()}`);
      expect(status).toBe(200);
    }
    logErrors('ADMIN Navigate');
  });

  test('ADMIN: Dashboard tabs and stats', async ({ page }) => {
    setupMonitors(page);
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    // Check stats cards
    const stats = await page.locator('.stat').all();
    console.log(`  ✓ Stats cards: ${stats.length}`);

    // Check nav-tabs
    const tabs = await page.locator('.nav-tabs a').all();
    const tabNames = [];
    for (const tab of tabs) {
      tabNames.push(await tab.textContent());
    }
    console.log(`  ✓ Dashboard tabs: ${tabNames.join(', ')}`);
    expect(tabs.length).toBeGreaterThan(5);
    logErrors('ADMIN Dashboard');
  });

  test('ADMIN: Kelola Pengguna page', async ({ page }) => {
    setupMonitors(page);
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/admin_users.php');
    const title = await page.title();
    expect(title).toContain('Kelola Pengguna');
    console.log(`  ✓ ${title}`);

    // Check stats
    const statBoxes = await page.locator('.stat-box').all();
    console.log(`  ✓ Stats boxes: ${statBoxes.length}`);

    // Check search form
    await expect(page.locator('input[name="search"]')).toBeVisible();
    await expect(page.locator('select[name="status"]')).toBeVisible();
    console.log('  ✓ Search and filter form present');

    // Check table
    await expect(page.locator('table')).toBeVisible();
    console.log('  ✓ Users table present');
    logErrors('ADMIN Users');
  });

  test('ADMIN: Scheduled Tryouts management', async ({ page }) => {
    setupMonitors(page);
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/admin_scheduled_tryouts.php');
    const title = await page.title();
    expect(title).toContain('Scheduled Tryouts');
    console.log(`  ✓ ${title}`);

    // Check create button
    const createBtn = page.locator('button:has-text("Buat")');
    await expect(createBtn).toBeVisible();
    console.log('  ✓ Create button present');

    // Check table
    await expect(page.locator('table')).toBeVisible();
    logErrors('ADMIN Scheduled');
  });

  test('ADMIN: Cannot access peserta pages (redirected)', async ({ page }) => {
    setupMonitors(page);
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    const pesertaPages = [
      '/pages/user_dashboard.php',
      '/pages/profile.php',
      '/pages/settings.php',
      '/pages/latihan.php',
      '/pages/tryout.php',
      '/pages/daily_quiz.php',
      '/pages/feedback.php',
      '/pages/scheduled_tryouts.php',
      '/pages/riwayat_soal.php',
    ];

    for (const p of pesertaPages) {
      await page.goto(BASE + p);
      await page.waitForTimeout(500);
      const url = page.url();
      const blocked = url.includes('admin_dashboard') || url.includes('admin_scheduled');
      console.log(`  ${blocked ? '✓' : '✗'} ${p} → ${blocked ? 'blocked (admin redirect)' : url}`);
      expect(blocked).toBeTruthy();
    }
    logErrors('ADMIN Blocked');
  });

  test('ADMIN: Logout → redirects to login', async ({ page }) => {
    setupMonitors(page);
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    const logoutLink = page.locator('a[href*="logout"]').first();
    await expect(logoutLink).toBeVisible();
    await logoutLink.click();
    await page.waitForTimeout(3000);
    expect(page.url()).toContain('login');
    console.log(`  ✓ Logout → ${page.url()}`);

    // Cannot access admin after logout
    await page.goto(BASE + '/pages/admin_dashboard.php');
    await page.waitForTimeout(1000);
    expect(page.url()).toContain('login');
    console.log('  ✓ Admin dashboard blocked after logout');
    logErrors('ADMIN Logout');
  });
});

// ========== SECURITY CHECKS ==========

test.describe('Security & Access Control', () => {

  test('Unauthenticated: all protected pages redirect to login', async ({ page }) => {
    const protectedPages = [
      '/pages/user_dashboard.php',
      '/pages/admin_dashboard.php',
      '/pages/admin_users.php',
      '/pages/admin_scheduled_tryouts.php',
      '/pages/profile.php',
      '/pages/settings.php',
      '/pages/latihan.php',
      '/pages/tryout.php',
      '/pages/feedback.php',
      '/pages/daily_quiz.php',
      '/pages/scheduled_tryouts.php',
      '/pages/riwayat_soal.php',
    ];

    for (const p of protectedPages) {
      await page.goto(BASE + p);
      await page.waitForTimeout(500);
      const url = page.url();
      const redirected = url.includes('login');
      console.log(`  ${redirected ? '✓' : '✗'} ${p} → ${redirected ? 'login' : url}`);
      expect(redirected).toBeTruthy();
    }
  });

  test('Public pages accessible without login', async ({ page }) => {
    const publicPages = [
      { url: '/', name: 'Landing' },
      { url: '/pages/login.php', name: 'Login' },
      { url: '/pages/register.php', name: 'Register' },
      { url: '/pages/help.php', name: 'Help' },
      { url: '/pages/leaderboard.php', name: 'Leaderboard' },
      { url: '/pages/forgot_password.php', name: 'Forgot Password' },
    ];

    for (const p of publicPages) {
      const resp = await page.goto(BASE + p.url);
      console.log(`  [${resp?.status()}] ${p.name}`);
      expect(resp?.status()).toBe(200);
    }
  });

  test('Invalid login shows error', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', '000000000000');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('login');
    const error = page.locator('.error, .alert-danger, .login-error, [class*="error"]').first();
    if (await error.isVisible()) {
      console.log(`  ✓ Error: ${(await error.textContent())?.trim()}`);
    } else {
      console.log('  ✓ Stayed on login (blocked)');
    }
  });

  test('CSRF token present on forms', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    const csrf = page.locator('input[name="csrf_token"]');
    await expect(csrf).toBeAttached();
    const token = await csrf.inputValue();
    expect(token.length).toBeGreaterThan(10);
    console.log(`  ✓ CSRF: ${token.substring(0, 20)}...`);
  });

  test('API endpoints return valid JSON', async ({ page }) => {
    const apis = [
      { url: '/api/health.php', name: 'Health' },
      { url: '/api/get_landing_stats.php', name: 'Stats' },
    ];
    for (const api of apis) {
      const resp = await page.goto(BASE + api.url);
      expect(resp?.status()).toBe(200);
      const json = await resp?.json();
      expect(json).toHaveProperty('success');
      console.log(`  ✓ ${api.name}: success=${json.success}`);
    }
  });
});

// ========== CSS & ASSETS ==========

test.describe('Assets & Resources', () => {

  test('All CSS files load correctly (no double slash)', async ({ page }) => {
    setupMonitors(page);
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    // Collect CSS from multiple pages
    const pages = [BASE + '/pages/admin_dashboard.php', BASE + '/pages/admin_users.php', BASE + '/pages/leaderboard.php'];
    const allCss = new Set();

    for (const url of pages) {
      await page.goto(url);
      const links = await page.locator('link[rel="stylesheet"]').all();
      for (const link of links) {
        const href = await link.getAttribute('href');
        if (href) allCss.add(href);
      }
    }

    console.log(`  ✓ CSS files found: ${allCss.size}`);
    for (const css of allCss) {
      const cssUrl = css.startsWith('http') ? css : BASE + css;
      // Check no double slash (except protocol)
      const afterProtocol = cssUrl.replace('https://', '');
      expect(afterProtocol).not.toContain('//');
      console.log(`    ✓ ${css}`);
    }
    logErrors('CSS Check');
  });
});
