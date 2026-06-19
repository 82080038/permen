// @ts-check
const { test, expect } = require('@playwright/test');

const BASE = 'https://bimbel.bereng.info';
const USER = { no_hp: '081987654321', password: 'Sihaloho1982' };

// Collect all errors during tests
let consoleErrors = [];
let networkErrors = [];
let cssErrors = [];

test.describe('Production Full User Simulation', () => {

  test.beforeEach(async ({ page }) => {
    consoleErrors = [];
    networkErrors = [];
    cssErrors = [];

    // Monitor console
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(`[CONSOLE] ${msg.text()}`);
      }
    });

    // Monitor page errors
    page.on('pageerror', err => {
      consoleErrors.push(`[PAGE_ERROR] ${err.message}`);
    });

    // Monitor network
    page.on('response', response => {
      const status = response.status();
      const url = response.url();
      if (status >= 400) {
        networkErrors.push(`[${status}] ${url}`);
      }
      // Track CSS files
      if (url.endsWith('.css') || url.includes('.css?')) {
        if (status >= 400) {
          cssErrors.push(`[CSS ${status}] ${url}`);
        }
      }
    });

    // Monitor request failures
    page.on('requestfailed', request => {
      networkErrors.push(`[FAILED] ${request.url()} - ${request.failure()?.errorText}`);
    });
  });

  test.afterEach(async ({}, testInfo) => {
    if (consoleErrors.length > 0) {
      console.log(`\n⚠️  Console Errors (${testInfo.title}):`);
      consoleErrors.forEach(e => console.log(`  ${e}`));
    }
    if (networkErrors.length > 0) {
      console.log(`\n⚠️  Network Errors (${testInfo.title}):`);
      networkErrors.forEach(e => console.log(`  ${e}`));
    }
    if (cssErrors.length > 0) {
      console.log(`\n⚠️  CSS Errors (${testInfo.title}):`);
      cssErrors.forEach(e => console.log(`  ${e}`));
    }
  });

  // ===== PUBLIC PAGES =====

  test('1. Landing page - check all assets load', async ({ page }) => {
    await page.goto(BASE);
    await expect(page).toHaveTitle(/SKD CAT-BKN/);

    // Check CSS loaded
    const styles = await page.locator('link[rel="stylesheet"]').all();
    expect(styles.length).toBeGreaterThan(0);
    console.log(`  ✓ ${styles.length} CSS files loaded`);

    // Check images
    const images = await page.locator('img').all();
    for (const img of images) {
      const src = await img.getAttribute('src');
      if (src) console.log(`  Image: ${src}`);
    }

    // Check JS
    const scripts = await page.locator('script[src]').all();
    console.log(`  ✓ ${scripts.length} JS files loaded`);

    // Verify no broken layout
    await expect(page.locator('body')).toBeVisible();
    expect(networkErrors.filter(e => e.includes('.css'))).toHaveLength(0);
  });

  test('2. Login page - verify form and CSS', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await expect(page.locator('input[name="no_hp"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"], input[type="submit"]')).toBeVisible();

    // Check CSS applied (form should have styling)
    const card = page.locator('.card, .login-card, .login-container, form').first();
    await expect(card).toBeVisible();
    console.log(`  ✓ Login form renders correctly`);
  });

  // ===== LOGIN =====

  test('3. Login as user', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');

    // Get CSRF token
    const csrfInput = page.locator('input[name="csrf_token"]');
    await expect(csrfInput).toBeAttached();

    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');

    // Should redirect to dashboard
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });
    console.log(`  ✓ Logged in, redirected to: ${page.url()}`);
  });

  // ===== USER DASHBOARD =====

  test('4. User Dashboard - full inspection', async ({ page }) => {
    // Login first
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    // Check dashboard content
    await expect(page.locator('body')).toBeVisible();
    const title = await page.title();
    console.log(`  Page title: ${title}`);

    // Check navigation
    const nav = page.locator('nav, .navbar, .nav-menu, #navMenu');
    if (await nav.count() > 0) {
      const navLinks = await nav.locator('a').all();
      console.log(`  ✓ Navigation: ${navLinks.length} links`);
      for (const link of navLinks.slice(0, 10)) {
        const href = await link.getAttribute('href');
        const text = await link.textContent();
        console.log(`    - ${text?.trim()}: ${href}`);
      }
    }

    // Check stats/cards
    const cards = await page.locator('.card, .stat-card, .dashboard-card').all();
    console.log(`  ✓ Dashboard cards: ${cards.length}`);

    // Check all CSS stylesheets
    const cssLinks = await page.locator('link[rel="stylesheet"]').all();
    console.log(`  ✓ CSS files: ${cssLinks.length}`);
    for (const css of cssLinks) {
      const href = await css.getAttribute('href');
      console.log(`    CSS: ${href}`);
    }
  });

  // ===== PROFILE PAGE =====

  test('5. Profile page - check form and data', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/profile.php');
    await expect(page.locator('body')).toBeVisible();

    const title = await page.title();
    console.log(`  Page: ${title}`);

    // Check profile form elements
    const inputs = await page.locator('input, select, textarea').all();
    console.log(`  ✓ Form elements: ${inputs.length}`);
    for (const input of inputs.slice(0, 8)) {
      const name = await input.getAttribute('name');
      const type = await input.getAttribute('type');
      if (name) console.log(`    Input: ${name} (${type || 'text'})`);
    }
  });

  // ===== MATERI PAGE =====

  test('6. Materi TWK page - content and navigation', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/materi.php?subtes=TWK');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);

    // Check materi content
    const sections = await page.locator('.materi-section, .card, article, section').all();
    console.log(`  ✓ Content sections: ${sections.length}`);

    // Check tabs/subtes navigation
    const tabs = await page.locator('a[href*="subtes"], .tab, .subtes-tab').all();
    console.log(`  ✓ Subtes tabs: ${tabs.length}`);
  });

  test('7. Materi TIU page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/materi.php?subtes=TIU');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);
    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('8. Materi TKP page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/materi.php?subtes=TKP');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);
    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  // ===== LATIHAN =====

  test('9. Latihan page - start practice session', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/latihan.php');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);

    // Check subtes options
    const options = await page.locator('a[href*="subtes"], button, .subtes-option, select option').all();
    console.log(`  ✓ Options available: ${options.length}`);

    // Try starting TWK practice
    const twkLink = page.locator('a:has-text("TWK"), button:has-text("TWK"), a[href*="TWK"]').first();
    if (await twkLink.isVisible()) {
      await twkLink.click();
      await page.waitForTimeout(2000);
      console.log(`  ✓ Started TWK practice, URL: ${page.url()}`);
    }
  });

  // ===== TRYOUT =====

  test('10. Tryout page - check available tryouts', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/tryout.php');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);

    // Check tryout interface
    const content = await page.content();
    const hasTryout = content.includes('tryout') || content.includes('Tryout') || content.includes('mulai');
    console.log(`  ✓ Tryout content present: ${hasTryout}`);
  });

  test('11. Start actual tryout and answer questions', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    // Navigate to tryout
    await page.goto(BASE + '/pages/tryout.php');
    await page.waitForTimeout(1000);

    // Try to start a new tryout session
    const startBtn = page.locator('button:has-text("Mulai"), a:has-text("Mulai"), button:has-text("Start"), form button[type="submit"]').first();
    if (await startBtn.isVisible()) {
      await startBtn.click();
      await page.waitForTimeout(3000);
      console.log(`  ✓ Tryout started, URL: ${page.url()}`);

      // Check if questions loaded
      const questionText = page.locator('.question, .soal, .question-text, [class*="question"]').first();
      if (await questionText.isVisible()) {
        const text = await questionText.textContent();
        console.log(`  ✓ Question loaded: ${text?.substring(0, 80)}...`);

        // Try answering first question
        const options = await page.locator('.option, .answer-option, input[type="radio"], label[class*="option"]').all();
        console.log(`  ✓ Answer options: ${options.length}`);
        if (options.length > 0) {
          await options[0].click();
          console.log(`  ✓ Selected first option`);
          await page.waitForTimeout(1000);
        }

        // Try next question
        const nextBtn = page.locator('button:has-text("Selanjutnya"), button:has-text("Next"), button:has-text("Lanjut"), .next-btn').first();
        if (await nextBtn.isVisible()) {
          await nextBtn.click();
          await page.waitForTimeout(1000);
          console.log(`  ✓ Moved to next question`);
        }
      }
    } else {
      console.log(`  ℹ No start button visible - may need scheduled tryout`);
    }
  });

  // ===== LEADERBOARD =====

  test('12. Leaderboard page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/leaderboard.php');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);

    // Check leaderboard table/list
    const rows = await page.locator('tr, .leaderboard-item, .rank-item').all();
    console.log(`  ✓ Leaderboard entries: ${rows.length}`);

    // Check filter options
    const filters = await page.locator('a[href*="instansi"], select, .filter').all();
    console.log(`  ✓ Filter options: ${filters.length}`);
  });

  // ===== HELP PAGE =====

  test('13. Help page - FAQ and content', async ({ page }) => {
    await page.goto(BASE + '/pages/help.php');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);

    const sections = await page.locator('.faq, .help-section, details, .accordion').all();
    console.log(`  ✓ Help sections: ${sections.length}`);
  });

  // ===== SETTINGS =====

  test('14. Settings page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/settings.php');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);

    const forms = await page.locator('form').all();
    console.log(`  ✓ Settings forms: ${forms.length}`);
  });

  // ===== FEEDBACK =====

  test('15. Feedback page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/feedback.php');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);
  });

  // ===== SCHEDULED TRYOUTS =====

  test('16. Scheduled Tryouts page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/scheduled_tryouts.php');
    await expect(page.locator('body')).toBeVisible();
    console.log(`  Page: ${await page.title()}`);

    const events = await page.locator('.tryout-event, .scheduled-item, .event-card, tr').all();
    console.log(`  ✓ Scheduled events: ${events.length}`);
  });

  // ===== RIWAYAT =====

  test('17. Riwayat Soal page', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    await page.goto(BASE + '/pages/riwayat_soal.php');
    const status = await page.evaluate(() => document.readyState);
    console.log(`  Page ready state: ${status}`);
    console.log(`  URL: ${page.url()}`);
  });

  // ===== API ENDPOINTS =====

  test('18. API endpoints check', async ({ page }) => {
    // Health
    const health = await page.goto(BASE + '/api/health.php');
    expect(health?.status()).toBe(200);
    const healthJson = await health?.json();
    console.log(`  ✓ Health: ${healthJson?.data?.status}`);

    // Landing stats
    const stats = await page.goto(BASE + '/api/get_landing_stats.php');
    expect(stats?.status()).toBe(200);
    const statsJson = await stats?.json();
    console.log(`  ✓ Stats: ${statsJson?.data?.question_count} questions, ${statsJson?.data?.user_count} users`);
  });

  // ===== CSS FILE CHECK =====

  test('19. Verify all CSS files accessible', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    // Visit multiple pages and collect CSS
    const pagesToCheck = [
      '/pages/user_dashboard.php',
      '/pages/profile.php',
      '/pages/materi.php?subtes=TWK',
      '/pages/latihan.php',
      '/pages/tryout.php',
      '/pages/leaderboard.php',
      '/pages/help.php',
    ];

    const allCssFiles = new Set();
    for (const p of pagesToCheck) {
      await page.goto(BASE + p);
      const links = await page.locator('link[rel="stylesheet"]').all();
      for (const link of links) {
        const href = await link.getAttribute('href');
        if (href) allCssFiles.add(href);
      }
    }

    console.log(`  ✓ Total unique CSS files found: ${allCssFiles.size}`);
    for (const css of allCssFiles) {
      console.log(`    ${css}`);
    }

    // Verify each CSS is accessible
    let cssOk = 0;
    let cssFail = 0;
    for (const css of allCssFiles) {
      let url = css;
      if (css.startsWith('/')) url = BASE + css;
      else if (!css.startsWith('http')) url = BASE + '/' + css;

      const resp = await page.goto(url);
      if (resp && resp.status() === 200) {
        cssOk++;
      } else {
        cssFail++;
        console.log(`    ✗ FAILED: ${css} (${resp?.status()})`);
      }
    }
    console.log(`  ✓ CSS OK: ${cssOk}, Failed: ${cssFail}`);
    expect(cssFail).toBe(0);
  });

  // ===== LOGOUT =====

  test('20. Logout', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('input[name="no_hp"]', USER.no_hp);
    await page.fill('input[name="password"]', USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL(/user_dashboard|dashboard/, { timeout: 10000 });

    // Find and click logout
    const logoutLink = page.locator('a[href*="logout"], button:has-text("Logout"), a:has-text("Logout"), a:has-text("Keluar")').first();
    await expect(logoutLink).toBeVisible();
    await logoutLink.click();
    await page.waitForTimeout(2000);

    // Should redirect to login or landing
    const url = page.url();
    console.log(`  ✓ After logout URL: ${url}`);
    expect(url).toMatch(/login|index|\/$/);
  });

  // ===== FINAL ERROR SUMMARY =====

  test('21. Final error summary across all pages', async ({ page }) => {
    const allErrors = { console: [], network: [], css: [] };

    const pagesToVisit = [
      '/',
      '/pages/login.php',
      '/pages/register.php',
      '/pages/help.php',
      '/pages/leaderboard.php',
      '/pages/forgot_password.php',
    ];

    for (const p of pagesToVisit) {
      const localConsoleErrors = [];
      const localNetworkErrors = [];

      page.on('console', msg => {
        if (msg.type() === 'error') localConsoleErrors.push(`${p}: ${msg.text()}`);
      });

      const resp = await page.goto(BASE + p, { waitUntil: 'networkidle' });
      if (resp && resp.status() >= 400) {
        localNetworkErrors.push(`${p}: HTTP ${resp.status()}`);
      }

      allErrors.console.push(...localConsoleErrors);
      allErrors.network.push(...localNetworkErrors);
    }

    console.log(`\n========== ERROR SUMMARY ==========`);
    console.log(`Console Errors: ${allErrors.console.length}`);
    allErrors.console.forEach(e => console.log(`  ${e}`));
    console.log(`Network Errors: ${allErrors.network.length}`);
    allErrors.network.forEach(e => console.log(`  ${e}`));
    console.log(`===================================\n`);

    // Allow favicon 404 but no other errors
    const realErrors = allErrors.network.filter(e => !e.includes('favicon'));
    expect(realErrors).toHaveLength(0);
  });
});
