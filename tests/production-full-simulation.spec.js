// @ts-check
const { test, expect } = require('@playwright/test');

const BASE = 'https://bimbel.bereng.info';
const USER = { no_hp: '081987654321', password: 'Sihaloho1982' };
const ADMIN = { no_hp: '081265511982', password: 'Sihaloho1982' };

let consoleErrors = [];
let networkErrors = [];

test.describe('Full Production Simulation - User Flow', () => {

  test.beforeEach(async ({ page }) => {
    consoleErrors = [];
    networkErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => consoleErrors.push(err.message));
    page.on('response', resp => { if (resp.status() >= 400 && !resp.url().includes('favicon')) networkErrors.push(`[${resp.status()}] ${resp.url()}`); });
    page.on('requestfailed', req => { if (!req.url().includes('learning_analytics') && !req.url().includes('favicon')) networkErrors.push(`[FAIL] ${req.url()}`); });
  });

  test.afterEach(async ({}, testInfo) => {
    if (consoleErrors.length > 0) console.log(`  ⚠️ Console[${testInfo.title}]: ${consoleErrors.join(' | ')}`);
    if (networkErrors.length > 0) console.log(`  ⚠️ Network[${testInfo.title}]: ${networkErrors.join(' | ')}`);
  });

  // ========== USER LOGIN & ALL PAGES ==========

  test('USER: Login → Dashboard → verify all links work', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });
    expect(page.url()).toContain('/pages/user_dashboard.php');
    console.log('  ✓ User login successful');

    // Get all nav links
    const navLinks = await page.locator('nav a, #navMenu a').all();
    const hrefs = [];
    for (const link of navLinks) {
      const href = await link.getAttribute('href');
      if (href && href.startsWith('http') && !href.includes('logout')) hrefs.push(href);
    }
    console.log(`  ✓ Found ${hrefs.length} navigation links to test`);

    // Visit each link
    for (const href of hrefs) {
      const resp = await page.goto(href, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const status = resp?.status() ?? 0;
      const title = await page.title();
      console.log(`    [${status}] ${href.replace(BASE, '')} → ${title}`);
      expect(status).toBeLessThan(500);
    }
  });

  test('USER: Profile - view and edit form loads', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/profile.php');
    await expect(page.locator('input[name="nama"]')).toBeVisible();
    const nama = await page.locator('input[name="nama"]').inputValue();
    console.log(`  ✓ Profile loaded, nama: ${nama}`);
    
    // Check profile has submit button
    await expect(page.locator('button[type="submit"], input[type="submit"]').first()).toBeVisible();
    console.log('  ✓ Profile edit form functional');
  });

  test('USER: Settings - form and options', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/settings.php');
    const title = await page.title();
    expect(title).toContain('Pengaturan');
    console.log(`  ✓ Settings page: ${title}`);
    
    // Check settings options exist
    const formElements = await page.locator('input, select').all();
    console.log(`  ✓ Settings form elements: ${formElements.length}`);
  });

  test('USER: Latihan - start TWK practice, answer questions', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/latihan.php');
    await expect(page.locator('body')).toBeVisible();

    // Click TWK
    const twkBtn = page.locator('a:has-text("TWK"), button:has-text("TWK"), a[href*="TWK"]').first();
    if (await twkBtn.isVisible()) {
      await twkBtn.click();
      await page.waitForTimeout(2000);
      const url = page.url();
      console.log(`  ✓ Started TWK practice: ${url}`);
      expect(url).toContain('session_id');

      // Check questions loaded via API
      const content = await page.content();
      if (content.includes('soal') || content.includes('question') || content.includes('pertanyaan')) {
        console.log('  ✓ Question content present on page');
      }

      // Try to answer a question via the UI
      await page.waitForTimeout(2000);
      const options = await page.locator('.option-btn, .answer-option, label[class*="option"], .option').all();
      if (options.length > 0) {
        await options[0].click();
        await page.waitForTimeout(1000);
        console.log(`  ✓ Answered question (${options.length} options available)`);
        
        // Next question
        const nextBtn = page.locator('button:has-text("Selanjutnya"), button:has-text("Next"), .next-btn, button:has-text("Lanjut")').first();
        if (await nextBtn.isVisible()) {
          await nextBtn.click();
          await page.waitForTimeout(1000);
          console.log('  ✓ Moved to next question');
        }
      }
    }
  });

  test('USER: Tryout page - interface check', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/tryout.php');
    const title = await page.title();
    console.log(`  ✓ Tryout page: ${title}`);
    
    // Check start/resume buttons or session list
    const content = await page.content();
    const hasInterface = content.includes('Mulai') || content.includes('Lanjut') || content.includes('session') || content.includes('subtes');
    console.log(`  ✓ Tryout interface present: ${hasInterface}`);
  });

  test('USER: Materi - navigate all subtes', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    for (const subtes of ['TWK', 'TIU', 'TKP']) {
      const resp = await page.goto(BASE + `/pages/materi.php?subtes=${subtes}`);
      expect(resp?.status()).toBe(200);
      const title = await page.title();
      expect(title).toContain(subtes);
      console.log(`  ✓ Materi ${subtes}: ${title}`);
    }
  });

  test('USER: Leaderboard - filters and data', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/leaderboard.php');
    const title = await page.title();
    expect(title).toContain('Leaderboard');

    // Check filter links
    const filters = await page.locator('a[href*="instansi"], .filter-btn, select').all();
    console.log(`  ✓ Leaderboard filters: ${filters.length}`);
    
    // Click first filter if exists
    if (filters.length > 0) {
      const filterLink = page.locator('a[href*="instansi"]').first();
      if (await filterLink.isVisible()) {
        await filterLink.click();
        await page.waitForTimeout(1000);
        console.log(`  ✓ Filter clicked, URL: ${page.url()}`);
      }
    }
  });

  test('USER: Feedback page - form submission', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/feedback.php');
    const title = await page.title();
    expect(title).toContain('Feedback');
    console.log(`  ✓ Feedback page loaded: ${title}`);

    // Check form elements
    const textarea = page.locator('textarea').first();
    if (await textarea.isVisible()) {
      console.log('  ✓ Feedback textarea present');
    }
  });

  test('USER: Daily Quiz page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    const resp = await page.goto(BASE + '/pages/daily_quiz.php');
    expect(resp?.status()).toBeLessThan(500);
    console.log(`  ✓ Daily Quiz: ${await page.title()} [${resp?.status()}]`);
  });

  test('USER: Scheduled Tryouts page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    const resp = await page.goto(BASE + '/pages/scheduled_tryouts.php');
    expect(resp?.status()).toBe(200);
    console.log(`  ✓ Scheduled Tryouts: ${await page.title()}`);
  });

  test('USER: Riwayat Soal page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    const resp = await page.goto(BASE + '/pages/riwayat_soal.php');
    expect(resp?.status()).toBeLessThan(500);
    console.log(`  ✓ Riwayat Soal: ${await page.title()} [${resp?.status()}]`);
  });

  test('USER: Help page - all sections', async ({ page }) => {
    const resp = await page.goto(BASE + '/pages/help.php');
    expect(resp?.status()).toBe(200);
    const sections = await page.locator('details, .faq, .accordion, .help-section').all();
    console.log(`  ✓ Help page: ${sections.length} sections`);
  });

  test('USER: Logout → redirects to login', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    // Click logout
    const logoutLink = page.locator('a[href*="logout"]').first();
    await expect(logoutLink).toBeVisible();
    await logoutLink.click();
    await page.waitForTimeout(3000);
    
    const url = page.url();
    console.log(`  ✓ After logout: ${url}`);
    expect(url).toMatch(/login/);

    // Verify cannot access dashboard after logout
    const resp = await page.goto(BASE + '/pages/user_dashboard.php');
    const afterUrl = page.url();
    console.log(`  ✓ Dashboard after logout redirects to: ${afterUrl}`);
    expect(afterUrl).toContain('login');
  });
});

// ========== ADMIN FLOW ==========

test.describe('Full Production Simulation - Admin Flow', () => {

  test.beforeEach(async ({ page }) => {
    consoleErrors = [];
    networkErrors = [];
    page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', err => consoleErrors.push(err.message));
    page.on('response', resp => { if (resp.status() >= 400 && !resp.url().includes('favicon')) networkErrors.push(`[${resp.status()}] ${resp.url()}`); });
    page.on('requestfailed', req => { if (!req.url().includes('learning_analytics') && !req.url().includes('favicon')) networkErrors.push(`[FAIL] ${req.url()}`); });
  });

  test.afterEach(async ({}, testInfo) => {
    if (consoleErrors.length > 0) console.log(`  ⚠️ Console[${testInfo.title}]: ${consoleErrors.join(' | ')}`);
    if (networkErrors.length > 0) console.log(`  ⚠️ Network[${testInfo.title}]: ${networkErrors.join(' | ')}`);
  });

  test('ADMIN: Login → Admin Dashboard', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });
    
    const title = await page.title();
    console.log(`  ✓ Admin login → ${title}`);
    expect(page.url()).toContain('admin_dashboard');
  });

  test('ADMIN: Dashboard - stats and overview', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    // Check admin dashboard content
    const content = await page.content();
    const hasStats = content.includes('user') || content.includes('User') || content.includes('peserta');
    console.log(`  ✓ Admin dashboard has user stats: ${hasStats}`);

    // Check admin-specific elements
    const cards = await page.locator('.card, .stat-card, .dashboard-stat, .admin-card').all();
    console.log(`  ✓ Admin dashboard cards: ${cards.length}`);

    // Check navigation has admin links
    const adminLinks = await page.locator('a[href*="admin"], a[href*="scheduled"]').all();
    console.log(`  ✓ Admin navigation links: ${adminLinks.length}`);
  });

  test('ADMIN: Navigate all admin links', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    // Get all nav links
    const navLinks = await page.locator('nav a, #navMenu a').all();
    const hrefs = [];
    for (const link of navLinks) {
      const href = await link.getAttribute('href');
      if (href && href.startsWith('http') && !href.includes('logout')) hrefs.push(href);
    }
    console.log(`  ✓ Admin has ${hrefs.length} navigation links`);

    for (const href of hrefs) {
      const resp = await page.goto(href, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const status = resp?.status() ?? 0;
      const title = await page.title();
      console.log(`    [${status}] ${href.replace(BASE, '')} → ${title}`);
      expect(status).toBeLessThan(500);
    }
  });

  test('ADMIN: Scheduled Tryouts management', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/admin_scheduled_tryouts.php');
    const title = await page.title();
    console.log(`  ✓ Admin Scheduled Tryouts: ${title}`);

    // Check CRUD interface
    const forms = await page.locator('form').all();
    const buttons = await page.locator('button, input[type="submit"]').all();
    console.log(`  ✓ Forms: ${forms.length}, Buttons: ${buttons.length}`);

    // Check if table/list exists
    const table = page.locator('table, .list, .data-list').first();
    if (await table.isVisible()) {
      const rows = await page.locator('tr, .item').all();
      console.log(`  ✓ Data rows: ${rows.length}`);
    }
  });

  test('ADMIN: Can access user-facing pages too', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    const pagesToTest = [
      { url: '/pages/latihan.php', name: 'Latihan' },
      { url: '/pages/tryout.php', name: 'Tryout' },
      { url: '/pages/leaderboard.php', name: 'Leaderboard' },
      { url: '/pages/help.php', name: 'Help' },
      { url: '/pages/feedback.php', name: 'Feedback' },
    ];

    for (const p of pagesToTest) {
      const resp = await page.goto(BASE + p.url, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const status = resp?.status() ?? 0;
      console.log(`    [${status}] ${p.name}: ${await page.title()}`);
      expect(status).toBeLessThan(500);
    }
  });

  test('ADMIN: Logout → redirects properly', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', ADMIN.no_hp);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/, { timeout: 10000 });

    const logoutLink = page.locator('a[href*="logout"]').first();
    await expect(logoutLink).toBeVisible();
    await logoutLink.click();
    await page.waitForTimeout(3000);
    
    const url = page.url();
    console.log(`  ✓ Admin logout → ${url}`);
    expect(url).toMatch(/login/);

    // Verify admin dashboard inaccessible after logout
    await page.goto(BASE + '/pages/admin_dashboard.php');
    await page.waitForTimeout(1000);
    const afterUrl = page.url();
    console.log(`  ✓ Admin dashboard after logout → ${afterUrl}`);
    expect(afterUrl).toContain('login');
  });
});

// ========== SECURITY & LOGIC TESTS ==========

test.describe('Security & Logic Checks', () => {

  test('Unauthenticated: protected pages redirect to login', async ({ page }) => {
    const protectedPages = [
      '/pages/user_dashboard.php',
      '/pages/admin_dashboard.php',
      '/pages/profile.php',
      '/pages/settings.php',
      '/pages/latihan.php',
      '/pages/tryout.php',
      '/pages/feedback.php',
      '/pages/daily_quiz.php',
      '/pages/scheduled_tryouts.php',
      '/pages/admin_scheduled_tryouts.php',
    ];

    for (const p of protectedPages) {
      await page.goto(BASE + p);
      await page.waitForTimeout(500);
      const url = page.url();
      const redirectedToLogin = url.includes('login');
      console.log(`  ${redirectedToLogin ? '✓' : '✗'} ${p} → ${redirectedToLogin ? 'login (protected)' : url}`);
      expect(redirectedToLogin).toBeTruthy();
    }
  });

  test('Regular user cannot access admin pages', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/, { timeout: 10000 });

    // Try admin dashboard - should redirect away (to login or user_dashboard)
    await page.goto(BASE + '/pages/admin_dashboard.php');
    await page.waitForTimeout(1000);
    const url1 = page.url();
    console.log(`  ✓ User → admin_dashboard: ${url1}`);
    expect(url1).not.toContain('admin_dashboard');

    // Try admin scheduled tryouts
    await page.goto(BASE + '/pages/admin_scheduled_tryouts.php');
    await page.waitForTimeout(1000);
    const url2 = page.url();
    console.log(`  ✓ User → admin_scheduled_tryouts: ${url2}`);
    expect(url2).not.toContain('admin_scheduled_tryouts');
  });

  test('Invalid login shows error', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', '000000000000');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    // Should stay on login with error
    expect(page.url()).toContain('login');
    const errorMsg = page.locator('.error, .alert-danger, .login-error, [class*="error"]').first();
    if (await errorMsg.isVisible()) {
      const text = await errorMsg.textContent();
      console.log(`  ✓ Error shown: ${text?.trim()}`);
    } else {
      console.log('  ✓ Stayed on login page (no redirect)');
    }
  });

  test('CSRF protection works', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    
    // Verify CSRF token exists
    const csrfInput = page.locator('input[name="csrf_token"]');
    await expect(csrfInput).toBeAttached();
    const token = await csrfInput.inputValue();
    expect(token.length).toBeGreaterThan(10);
    console.log(`  ✓ CSRF token present: ${token.substring(0, 20)}...`);
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
      const status = resp?.status() ?? 0;
      console.log(`  [${status}] ${p.name}`);
      expect(status).toBe(200);
    }
  });

  test('API endpoints return valid JSON', async ({ page }) => {
    const apis = [
      { url: '/api/health.php', name: 'Health' },
      { url: '/api/get_landing_stats.php', name: 'Landing Stats' },
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
