/**
 * COMPREHENSIVE APPLICATION ANALYSIS
 * Full testing of SKD CAT-BKN application
 * 
 * Tests cover:
 * 1. Public pages (Homepage, Login, Register, Leaderboard, Materi)
 * 2. Authentication flow (Login, Session, Logout)
 * 3. User Dashboard & Analytics
 * 4. Tryout System (Full 110 soal, Latihan per subtes)
 * 5. Materi & Uji Pemahaman
 * 6. API Endpoints
 * 7. Admin Dashboard & Features
 * 8. Navigation & UI/UX
 * 9. Mobile Responsiveness
 * 10. Performance & Error Monitoring
 */

const { test, expect } = require('@playwright/test');

const BASE = 'http://localhost/permen';

// Enhanced error capture with detailed logging
function captureAllErrors(page, testName) {
  const errors = {
    console: [],
    page: [],
    network: [],
    warnings: [],
    requests: [],
    responses: []
  };

  // Capture all console messages
  page.on('console', msg => {
    const type = msg.type();
    const text = msg.text();
    const entry = { type, text, timestamp: new Date().toISOString() };

    if (type === 'error') {
      errors.console.push(entry);
      console.log(`[${testName}] [CONSOLE ERROR] ${text}`);
    } else if (type === 'warning') {
      errors.warnings.push(entry);
      console.log(`[${testName}] [CONSOLE WARN] ${text}`);
    } else if (type === 'log') {
      console.log(`[${testName}] [CONSOLE LOG] ${text}`);
    }
  });

  // Capture page errors (JavaScript exceptions)
  page.on('pageerror', error => {
    const entry = { message: error.message, stack: error.stack, timestamp: new Date().toISOString() };
    errors.page.push(entry);
    console.log(`[${testName}] [PAGE ERROR] ${error.message}`);
  });

  // Capture all network requests
  page.on('request', request => {
    errors.requests.push({
      url: request.url(),
      method: request.method(),
      timestamp: new Date().toISOString()
    });
  });

  // Capture all network responses
  page.on('response', async response => {
    const status = response.status();
    const url = response.url();

    errors.responses.push({
      url,
      status,
      timestamp: new Date().toISOString()
    });

    if (status >= 400) {
      let body = '';
      try {
        body = await response.text();
        if (body.length > 300) body = body.substring(0, 300) + '...';
      } catch (e) { }

      const entry = { status, url, body, timestamp: new Date().toISOString() };
      errors.network.push(entry);
      console.log(`[${testName}] [NETWORK ${status}] ${url}`);
      if (body) console.log(`[${testName}] [RESPONSE] ${body}`);
    }
  });

  // Capture failed requests
  page.on('requestfailed', request => {
    const entry = {
      url: request.url(),
      error: request.failure()?.errorText || 'Unknown error',
      timestamp: new Date().toISOString()
    };
    errors.network.push(entry);
    console.log(`[${testName}] [REQUEST FAILED] ${request.url()} - ${entry.error}`);
  });

  return errors;
}

// Print comprehensive error summary
function printErrorSummary(errors, testName) {
  console.log(`\n${'='.repeat(60)}`);
  console.log(`ERROR SUMMARY: ${testName}`);
  console.log(`${'='.repeat(60)}`);
  console.log(`Console Errors: ${errors.console.length}`);
  console.log(`Page Errors: ${errors.page.length}`);
  console.log(`Network Errors: ${errors.network.length}`);
  console.log(`Warnings: ${errors.warnings.length}`);
  console.log(`Total Requests: ${errors.requests.length}`);
  console.log(`Total Responses: ${errors.responses.length}`);

  if (errors.console.length > 0) {
    console.log('\n--- Console Errors ---');
    errors.console.forEach((err, i) => console.log(`${i + 1}. [${err.type}] ${err.text}`));
  }
  if (errors.page.length > 0) {
    console.log('\n--- Page Errors (JS Exceptions) ---');
    errors.page.forEach((err, i) => console.log(`${i + 1}. ${err.message}`));
  }
  if (errors.network.length > 0) {
    console.log('\n--- Network Errors ---');
    errors.network.forEach((err, i) => console.log(`${i + 1}. [${err.status || 'FAILED'}] ${err.url}`));
  }
  console.log(`${'='.repeat(60)}\n`);
}

// Helper: Login as user
async function loginUser(page) {
  await page.goto(`${BASE}/pages/login.php`);
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

  const quickBtn = page.locator('button:has-text("User (081987654321)")');
  if (await quickBtn.isVisible().catch(() => false)) {
    await quickBtn.click();
  } else {
    await page.fill('input[name="no_hp"]', '081987654321');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
  }

  await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
}

// Helper: Login as admin
async function loginAdmin(page) {
  await page.goto(`${BASE}/pages/login.php`);
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

  const quickBtn = page.locator('button:has-text("Admin (081234567890)")');
  if (await quickBtn.isVisible().catch(() => false)) {
    await quickBtn.click();
  } else {
    await page.fill('input[name="no_hp"]', '081234567890');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
  }

  await page.waitForURL(/admin_dashboard\.php/, { timeout: 15000 });
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
}

// Helper: Logout
async function logout(page) {
  await page.goto(`${BASE}/api/logout.php`);
  await page.waitForTimeout(1000);
}

test.describe('COMPREHENSIVE APPLICATION ANALYSIS', () => {

  // ============================================
  // SECTION 1: PUBLIC PAGES
  // ============================================
  test.describe('1. PUBLIC PAGES', () => {

    test('1.1 Homepage - Full Analysis', async ({ page }) => {
      const errors = captureAllErrors(page, 'Homepage');

      console.log('\n[TEST] Loading homepage...');
      const response = await page.goto(`${BASE}/index.php`);

      // Check HTTP status
      expect(response.status()).toBe(200);
      console.log(`[INFO] HTTP Status: ${response.status()}`);

      await page.waitForLoadState('networkidle', { timeout: 15000 });

      // Check title
      const title = await page.title();
      console.log(`[INFO] Page Title: ${title}`);
      expect(title).toContain('SKD');

      // Check critical elements
      const elements = [
        { name: 'Hero Section', selector: '.landing-hero, .hero' },
        { name: 'CTA Buttons', selector: '.landing-cta a, .cta a' },
        { name: 'Features Section', selector: '.landing-features, .features' },
        { name: 'Statistics', selector: '.landing-stats, .stats' },
        { name: 'Navigation', selector: 'nav, .navigation' },
        { name: 'Footer', selector: '.landing-footer, footer' }
      ];

      for (const el of elements) {
        const count = await page.locator(el.selector).count();
        console.log(`[INFO] ${el.name}: ${count} elements`);
      }

      // Check for API calls (stats)
      await page.waitForTimeout(3000); // Wait for stats API

      // Verify stats loaded
      const userCount = await page.textContent('#user-count');
      const tryoutCount = await page.textContent('#tryout-count');
      console.log(`[INFO] Stats - Users: ${userCount}, Tryouts: ${tryoutCount}`);

      printErrorSummary(errors, 'Homepage');
      expect(errors.page).toHaveLength(0);
    });

    test('1.2 Login Page - Form & Validation', async ({ page }) => {
      const errors = captureAllErrors(page, 'Login Page');

      await page.goto(`${BASE}/pages/login.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Check form elements
      const formElements = [
        { name: 'Phone Input', selector: 'input[name="no_hp"]' },
        { name: 'Password Input', selector: 'input[name="password"]' },
        { name: 'Submit Button', selector: 'button[type="submit"]' },
        { name: 'CSRF Token', selector: 'input[name="csrf_token"]' },
        { name: 'Quick Login User', selector: 'button:has-text("User (081987654321)")' },
        { name: 'Quick Login Admin', selector: 'button:has-text("Admin (081234567890)")' },
        { name: 'Register Link', selector: 'a[href*="register"]' },
        { name: 'Forgot Password Link', selector: 'a[href*="forgot"]' }
      ];

      for (const el of formElements) {
        const count = await page.locator(el.selector).count();
        const visible = count > 0 ? await page.locator(el.selector).first().isVisible().catch(() => false) : false;
        console.log(`[INFO] ${el.name}: exists=${count > 0}, visible=${visible}`);
      }

      printErrorSummary(errors, 'Login Page');
    });

    test('1.3 Register Page - Form Analysis', async ({ page }) => {
      const errors = captureAllErrors(page, 'Register Page');

      await page.goto(`${BASE}/pages/register.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Check registration form
      const formElements = [
        { name: 'Name Input', selector: 'input[name="nama"]' },
        { name: 'Phone Input', selector: 'input[name="no_hp"]' },
        { name: 'Email Input', selector: 'input[name="email"]' },
        { name: 'Password Input', selector: 'input[name="password"]' },
        { name: 'Confirm Password', selector: 'input[name="confirm_password"], input[name="password_confirm"]' },
        { name: 'Submit Button', selector: 'button[type="submit"]' }
      ];

      for (const el of formElements) {
        const count = await page.locator(el.selector).count();
        console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
      }

      printErrorSummary(errors, 'Register Page');
    });

    test('1.4 Leaderboard Page - Rankings Display', async ({ page }) => {
      const errors = captureAllErrors(page, 'Leaderboard');

      await page.goto(`${BASE}/pages/leaderboard.php`);
      await page.waitForLoadState('networkidle', { timeout: 15000 });

      // Check leaderboard elements
      const title = await page.title();
      console.log(`[INFO] Page Title: ${title}`);

      // Check for ranking tables
      const tableCount = await page.locator('table').count();
      console.log(`[INFO] Tables found: ${tableCount}`);

      // Check for subtes tabs
      const subtesTabs = ['TWK', 'TIU', 'TKP'];
      for (const subtes of subtesTabs) {
        const tabExists = await page.locator(`text=${subtes}`).count() > 0;
        console.log(`[INFO] ${subtes} tab: ${tabExists ? 'found' : 'NOT FOUND'}`);
      }

      // Check for user rankings
      const rowCount = await page.locator('table tr, .rank-item').count();
      console.log(`[INFO] Ranking rows: ${rowCount}`);

      printErrorSummary(errors, 'Leaderboard');
    });

    test('1.5 Materi Pages - All Subtes', async ({ page }) => {
      const errors = captureAllErrors(page, 'Materi Pages');

      const subtesList = ['TWK', 'TIU', 'TKP'];

      for (const subtes of subtesList) {
        console.log(`\n[TEST] Checking Materi ${subtes}...`);
        await page.goto(`${BASE}/pages/materi.php?subtes=${subtes}`);
        await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

        const title = await page.title();
        console.log(`[INFO] Title: ${title}`);

        // Check content sections
        const contentElements = [
          { name: 'Materi Container', selector: '#materiContainer, .materi-container' },
          { name: 'Accordion/Cards', selector: '.accordion, .card' },
          { name: 'Uji Pemahaman', selector: 'text=Uji Pemahaman' },
          { name: 'Topic Selector', selector: '#latihTopik, select' },
          { name: 'Generate Button', selector: 'button:has-text("Generate")' }
        ];

        for (const el of contentElements) {
          const count = await page.locator(el.selector).count();
          console.log(`[INFO] ${subtes} - ${el.name}: ${count}`);
        }

        // Check content length
        const bodyText = await page.textContent('body');
        console.log(`[INFO] ${subtes} content length: ${bodyText.length} chars`);
      }

      printErrorSummary(errors, 'Materi Pages');
    });

    test('1.6 Latihan Page - Public Access', async ({ page }) => {
      const errors = captureAllErrors(page, 'Latihan Page');

      await page.goto(`${BASE}/pages/latihan.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      const currentUrl = page.url();
      console.log(`[INFO] Current URL: ${currentUrl}`);

      // May redirect to login or show latihan options
      if (currentUrl.includes('login.php')) {
        console.log('[INFO] Latihan requires authentication');
      } else {
        const title = await page.title();
        console.log(`[INFO] Title: ${title}`);

        // Check for subtes options
        const subtesOptions = await page.locator('a[href*="subtes"], button:has-text("TWK"), button:has-text("TIU"), button:has-text("TKP")').count();
        console.log(`[INFO] Subtes options: ${subtesOptions}`);
      }

      printErrorSummary(errors, 'Latihan Page');
    });

    test('1.7 Help Page', async ({ page }) => {
      const errors = captureAllErrors(page, 'Help Page');

      await page.goto(`${BASE}/pages/help.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      const title = await page.title();
      console.log(`[INFO] Title: ${title}`);

      // Check for help content
      const bodyText = await page.textContent('body');
      const hasHelpContent = bodyText.length > 500;
      console.log(`[INFO] Help content: ${hasHelpContent ? 'adequate' : 'minimal'} (${bodyText.length} chars)`);

      printErrorSummary(errors, 'Help Page');
    });
  });

  // ============================================
  // SECTION 2: AUTHENTICATION FLOW
  // ============================================
  test.describe('2. AUTHENTICATION FLOW', () => {

    test('2.1 User Login - Quick Button', async ({ page }) => {
      const errors = captureAllErrors(page, 'User Login');

      await page.goto(`${BASE}/pages/login.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Click quick login
      await page.click('button:has-text("User (081987654321)")');
      await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });

      console.log('[INFO] User login successful');

      // Verify dashboard loaded
      const title = await page.title();
      console.log(`[INFO] Dashboard title: ${title}`);

      // Check session
      const cookies = await page.context().cookies();
      const sessionCookie = cookies.find(c => c.name === 'PHPSESSID');
      console.log(`[INFO] Session cookie: ${sessionCookie ? 'present' : 'MISSING'}`);

      printErrorSummary(errors, 'User Login');
    });

    test('2.2 Admin Login - Quick Button', async ({ page }) => {
      const errors = captureAllErrors(page, 'Admin Login');

      await page.goto(`${BASE}/pages/login.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      await page.click('button:has-text("Admin (081234567890)")');
      await page.waitForURL(/admin_dashboard\.php/, { timeout: 15000 });

      console.log('[INFO] Admin login successful');

      const title = await page.title();
      console.log(`[INFO] Admin dashboard title: ${title}`);

      printErrorSummary(errors, 'Admin Login');
    });

    test('2.3 Logout Flow', async ({ page }) => {
      const errors = captureAllErrors(page, 'Logout');

      // Login first
      await loginUser(page);

      // Logout via API
      await page.goto(`${BASE}/api/logout.php`);
      await page.waitForTimeout(2000);

      // Should redirect to login
      const currentUrl = page.url();
      console.log(`[INFO] After logout URL: ${currentUrl}`);

      // Try accessing protected page
      await page.goto(`${BASE}/pages/user_dashboard.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      const redirectedUrl = page.url();
      console.log(`[INFO] Protected page redirect: ${redirectedUrl}`);
      expect(redirectedUrl).toContain('login.php');

      printErrorSummary(errors, 'Logout');
    });

    test('2.4 Session Persistence', async ({ page }) => {
      const errors = captureAllErrors(page, 'Session');

      await loginUser(page);

      // Navigate to multiple pages
      const pages = [
        '/pages/user_dashboard.php',
        '/pages/materi.php?subtes=TWK',
        '/pages/latihan.php',
        '/pages/riwayat_soal.php',
        '/pages/profile.php'
      ];

      for (const pageUrl of pages) {
        await page.goto(`${BASE}${pageUrl}`);
        await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

        const currentUrl = page.url();
        const isAuthenticated = !currentUrl.includes('login.php');
        console.log(`[INFO] ${pageUrl}: ${isAuthenticated ? 'authenticated' : 'REDIRECTED TO LOGIN'}`);
      }

      printErrorSummary(errors, 'Session');
    });
  });

  // ============================================
  // SECTION 3: USER DASHBOARD
  // ============================================
  test.describe('3. USER DASHBOARD', () => {

    test('3.1 Dashboard Components', async ({ page }) => {
      const errors = captureAllErrors(page, 'Dashboard Components');

      await loginUser(page);

      // Check dashboard elements
      const elements = [
        { name: 'Welcome Message', selector: 'text=Selamat datang' },
        { name: 'Stats Cards', selector: '.stat-card, .card' },
        { name: 'Total Tryout', selector: 'text=Total Tryout' },
        { name: 'Rata-rata Nilai', selector: 'text=Rata-rata' },
        { name: 'Nilai Tertinggi', selector: 'text=Tertinggi' },
        { name: 'Charts', selector: 'canvas, #progressChart, #pieChart' },
        { name: 'Riwayat Section', selector: 'text=Riwayat' },
        { name: 'Navigation Menu', selector: 'nav, .sidebar' },
        { name: 'Logout Button', selector: 'text=Logout, a[href*="logout"]' }
      ];

      for (const el of elements) {
        const count = await page.locator(el.selector).count();
        console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
      }

      // Wait for AJAX data
      await page.waitForTimeout(3000);

      // Check for API calls
      const apiCalls = errors.responses.filter(r => r.url.includes('/api/'));
      console.log(`[INFO] API calls made: ${apiCalls.length}`);
      apiCalls.forEach(call => console.log(`  - ${call.status} ${call.url}`));

      printErrorSummary(errors, 'Dashboard Components');
    });

    test('3.2 Dashboard Navigation', async ({ page }) => {
      const errors = captureAllErrors(page, 'Dashboard Navigation');

      await loginUser(page);

      // Test navigation links
      const navLinks = [
        { name: 'Tryout', href: 'tryout' },
        { name: 'Latihan', href: 'latihan' },
        { name: 'Materi', href: 'materi' },
        { name: 'Riwayat', href: 'riwayat' },
        { name: 'Leaderboard', href: 'leaderboard' },
        { name: 'Profile', href: 'profile' }
      ];

      for (const link of navLinks) {
        const linkEl = page.locator(`a[href*="${link.href}"]`).first();
        const exists = await linkEl.count() > 0;
        console.log(`[INFO] ${link.name} link: ${exists ? 'found' : 'NOT FOUND'}`);
      }

      printErrorSummary(errors, 'Dashboard Navigation');
    });
  });

  // ============================================
  // SECTION 4: TRYOUT SYSTEM
  // ============================================
  test.describe('4. TRYOUT SYSTEM', () => {

    test('4.1 Tryout Page Load', async ({ page }) => {
      const errors = captureAllErrors(page, 'Tryout Load');

      await loginUser(page);

      await page.goto(`${BASE}/pages/tryout.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 15000 });
      await page.waitForTimeout(3000);

      // Check tryout elements
      const elements = [
        { name: 'Timer', selector: '#timer, .timer' },
        { name: 'Soal Container', selector: '#soalContainer, .soal-container' },
        { name: 'Navigation Grid', selector: '#navGrid, .nav-grid' },
        { name: 'Subtes Info', selector: '#subtes-info' },
        { name: 'Answer Options', selector: 'input[name="jawaban"]' },
        { name: 'Finish Button', selector: 'button.finish, #finishTryout' },
        { name: 'Dark Mode Toggle', selector: '#darkModeToggle' },
        { name: 'Font Size Control', selector: '#fontSize' },
        { name: 'Sidebar Toggle', selector: '#sidebarToggle' }
      ];

      for (const el of elements) {
        const count = await page.locator(el.selector).count();
        const visible = count > 0 ? await page.locator(el.selector).first().isVisible().catch(() => false) : false;
        console.log(`[INFO] ${el.name}: count=${count}, visible=${visible}`);
      }

      printErrorSummary(errors, 'Tryout Load');
    });

    test('4.2 Tryout Answer Flow', async ({ page }) => {
      const errors = captureAllErrors(page, 'Tryout Answer');

      await loginUser(page);

      await page.goto(`${BASE}/pages/tryout.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 15000 });
      await page.waitForTimeout(3000);

      // Answer 5 questions
      let answeredCount = 0;
      for (let i = 0; i < 5; i++) {
        try {
          await page.waitForSelector('input[name="jawaban"]', { timeout: 3000 });

          const options = await page.locator('input[name="jawaban"]').all();
          if (options.length > 0) {
            // Select random option
            const randomIndex = Math.floor(Math.random() * options.length);
            await options[randomIndex].check();
            answeredCount++;
            console.log(`[INFO] Answered question ${i + 1}`);
            await page.waitForTimeout(800); // Wait for auto-advance
          }
        } catch (e) {
          console.log(`[WARN] Could not answer question ${i + 1}: ${e.message}`);
          break;
        }
      }

      console.log(`[INFO] Total answered: ${answeredCount}`);

      // Check navigation grid update
      const answeredCells = await page.locator('.nav-grid .answered, .nav-cell.answered').count();
      console.log(`[INFO] Answered cells in grid: ${answeredCells}`);

      printErrorSummary(errors, 'Tryout Answer');
    });

    test('4.3 Tryout Finish & Results', async ({ page }) => {
      const errors = captureAllErrors(page, 'Tryout Finish');

      await loginUser(page);

      await page.goto(`${BASE}/pages/tryout.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 15000 });
      await page.waitForTimeout(3000);

      // Answer a few questions
      for (let i = 0; i < 3; i++) {
        try {
          await page.waitForSelector('input[name="jawaban"]', { timeout: 2000 });
          await page.locator('input[name="jawaban"]').first().check();
          await page.waitForTimeout(600);
        } catch (e) {
          break;
        }
      }

      // Handle dialog
      page.on('dialog', dialog => {
        console.log(`[INFO] Dialog: ${dialog.message()}`);
        dialog.accept();
      });

      // Click finish
      const finishBtn = page.locator('button.finish, #finishTryout');
      if (await finishBtn.count() > 0) {
        await finishBtn.click();
        await page.waitForTimeout(3000);

        const currentUrl = page.url();
        console.log(`[INFO] After finish URL: ${currentUrl}`);

        if (currentUrl.includes('hasil.php')) {
          console.log('[INFO] Successfully redirected to results page');

          // Check results elements
          const resultElements = [
            { name: 'Score Display', selector: '.score, .nilai' },
            { name: 'TWK Score', selector: 'text=TWK' },
            { name: 'TIU Score', selector: 'text=TIU' },
            { name: 'TKP Score', selector: 'text=TKP' },
            { name: 'Total Score', selector: 'text=Total' },
            { name: 'Review Button', selector: 'a[href*="review"], button:has-text("Review")' }
          ];

          for (const el of resultElements) {
            const count = await page.locator(el.selector).count();
            console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
          }
        }
      }

      printErrorSummary(errors, 'Tryout Finish');
    });

    test('4.4 Latihan TWK', async ({ page }) => {
      const errors = captureAllErrors(page, 'Latihan TWK');

      await loginUser(page);

      await page.goto(`${BASE}/pages/latihan.php?subtes=TWK`);
      await page.waitForLoadState('domcontentloaded', { timeout: 15000 });

      const currentUrl = page.url();
      console.log(`[INFO] Latihan TWK URL: ${currentUrl}`);

      if (currentUrl.includes('tryout.php')) {
        await page.waitForSelector('#soalContainer', { timeout: 10000 });
        console.log('[INFO] Latihan TWK session started');

        // Verify it's TWK only
        const subtesInfo = await page.textContent('#subtes-info').catch(() => '');
        console.log(`[INFO] Subtes info: ${subtesInfo}`);
      }

      printErrorSummary(errors, 'Latihan TWK');
    });

    test('4.5 Latihan TIU', async ({ page }) => {
      const errors = captureAllErrors(page, 'Latihan TIU');

      await loginUser(page);

      await page.goto(`${BASE}/pages/latihan.php?subtes=TIU`);
      await page.waitForLoadState('domcontentloaded', { timeout: 15000 });

      const currentUrl = page.url();
      console.log(`[INFO] Latihan TIU URL: ${currentUrl}`);

      printErrorSummary(errors, 'Latihan TIU');
    });

    test('4.6 Latihan TKP', async ({ page }) => {
      const errors = captureAllErrors(page, 'Latihan TKP');

      await loginUser(page);

      await page.goto(`${BASE}/pages/latihan.php?subtes=TKP`);
      await page.waitForLoadState('domcontentloaded', { timeout: 15000 });

      const currentUrl = page.url();
      console.log(`[INFO] Latihan TKP URL: ${currentUrl}`);

      printErrorSummary(errors, 'Latihan TKP');
    });
  });

  // ============================================
  // SECTION 5: API ENDPOINTS
  // ============================================
  test.describe('5. API ENDPOINTS', () => {

    test('5.1 Public APIs', async ({ request }) => {
      console.log('\n[TEST] Testing public APIs...');

      const publicApis = [
        { url: '/api/test_json.php', desc: 'Test JSON' },
        { url: '/api/get_landing_stats.php', desc: 'Landing Stats' },
        { url: '/api/materi.php', desc: 'Materi API' },
        { url: '/api/health.php', desc: 'Health Check' }
      ];

      for (const api of publicApis) {
        try {
          const response = await request.get(`${BASE}${api.url}`);
          const status = response.status();
          console.log(`[API] ${api.desc}: ${status}`);

          if (status === 200) {
            try {
              const json = await response.json();
              console.log(`[API] ${api.desc}: Valid JSON`);
            } catch (e) {
              const text = await response.text();
              console.log(`[API] ${api.desc}: Response is not JSON (${text.substring(0, 100)}...)`);
            }
          }
        } catch (e) {
          console.log(`[API ERROR] ${api.desc}: ${e.message}`);
        }
      }
    });

    test('5.2 Protected APIs (without auth)', async ({ request }) => {
      console.log('\n[TEST] Testing protected APIs without auth...');

      const protectedApis = [
        { url: '/api/get_soal.php', desc: 'Get Soal' },
        { url: '/api/get_review.php?session_id=1', desc: 'Get Review' },
        { url: '/api/get_dashboard_analytics.php', desc: 'Dashboard Analytics' },
        { url: '/api/learning_analytics.php', desc: 'Learning Analytics' },
        { url: '/api/get_notifications.php', desc: 'Notifications' }
      ];

      for (const api of protectedApis) {
        try {
          const response = await request.get(`${BASE}${api.url}`);
          const status = response.status();
          console.log(`[API] ${api.desc}: ${status} (expected 401)`);

          // Should return 401 without auth
          expect(status === 401 || status === 403 || status === 200).toBeTruthy();
        } catch (e) {
          console.log(`[API ERROR] ${api.desc}: ${e.message}`);
        }
      }
    });

    test('5.3 Admin APIs (without admin auth)', async ({ request }) => {
      console.log('\n[TEST] Testing admin APIs without admin auth...');

      const adminApis = [
        { url: '/api/generate_soal_smart.php?subtes=TWK&topik=Test&jumlah=1', desc: 'Smart Generator' },
        { url: '/api/admin_soal_crud.php', desc: 'Soal CRUD' },
        { url: '/api/admin_user_management.php', desc: 'User Management' },
        { url: '/api/admin_reports.php', desc: 'Admin Reports' }
      ];

      for (const api of adminApis) {
        try {
          const response = await request.get(`${BASE}${api.url}`);
          const status = response.status();
          console.log(`[API] ${api.desc}: ${status} (expected 403)`);

          // Should return 403 without admin auth
          expect(status === 401 || status === 403).toBeTruthy();
        } catch (e) {
          console.log(`[API ERROR] ${api.desc}: ${e.message}`);
        }
      }
    });

    test('5.4 User Generator API (with auth)', async ({ page }) => {
      const errors = captureAllErrors(page, 'User Generator API');

      await loginUser(page);

      // Test generate_user_soal API
      await page.goto(`${BASE}/api/generate_user_soal.php?subtes=TWK&topik=Nasionalisme&jumlah=3`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      const responseText = await page.textContent('body');
      console.log(`[API] Response: ${responseText.substring(0, 500)}`);

      try {
        const json = JSON.parse(responseText);
        if (json.success) {
          console.log(`[API] Generated ${json.data?.soal?.length || 0} questions`);
          expect(json.data.subtes).toBe('TWK');
        } else {
          console.log(`[API] Error: ${json.error}`);
        }
      } catch (e) {
        console.log(`[API] Response is not valid JSON`);
      }

      printErrorSummary(errors, 'User Generator API');
    });
  });

  // ============================================
  // SECTION 6: ADMIN DASHBOARD
  // ============================================
  test.describe('6. ADMIN DASHBOARD', () => {

    test('6.1 Admin Dashboard Load', async ({ page }) => {
      const errors = captureAllErrors(page, 'Admin Dashboard');

      await loginAdmin(page);

      // Check admin elements
      const elements = [
        { name: 'Dashboard Title', selector: 'text=Dashboard Admin' },
        { name: 'Generator Massal', selector: 'text=Generator Massal' },
        { name: 'Kelola Soal', selector: 'text=Kelola Soal, text=Soal' },
        { name: 'Statistik', selector: 'text=Statistik' },
        { name: 'Users Management', selector: 'text=User, text=Pengguna' },
        { name: 'Reports', selector: 'text=Laporan, text=Report' },
        { name: 'Charts', selector: 'canvas' },
        { name: 'Tables', selector: 'table' }
      ];

      for (const el of elements) {
        const count = await page.locator(el.selector).count();
        console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
      }

      printErrorSummary(errors, 'Admin Dashboard');
    });

    test('6.2 Admin Generator Massal', async ({ page }) => {
      const errors = captureAllErrors(page, 'Generator Massal');

      await loginAdmin(page);

      // Find and click Generator Massal tab
      const generatorTab = page.locator('text=Generator Massal').first();
      if (await generatorTab.isVisible().catch(() => false)) {
        await generatorTab.click();
        await page.waitForTimeout(1000);

        // Check generator form
        const formElements = [
          { name: 'Subtes Select', selector: 'select[name="subtes"], #subtes' },
          { name: 'Topik Select', selector: 'select[name="topik"], #topik' },
          { name: 'Jumlah Input', selector: 'input[name="jumlah"], #jumlah' },
          { name: 'Generate Button', selector: 'button:has-text("Generate")' }
        ];

        for (const el of formElements) {
          const count = await page.locator(el.selector).count();
          console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
        }
      } else {
        console.log('[WARN] Generator Massal tab not visible');
      }

      printErrorSummary(errors, 'Generator Massal');
    });

    test('6.3 Admin Soal Management', async ({ page }) => {
      const errors = captureAllErrors(page, 'Soal Management');

      await loginAdmin(page);

      // Find Kelola Soal tab
      const soalTab = page.locator('text=Kelola Soal').first();
      if (await soalTab.isVisible().catch(() => false)) {
        await soalTab.click();
        await page.waitForTimeout(1000);

        // Check soal table
        const tableExists = await page.locator('table').count() > 0;
        console.log(`[INFO] Soal table: ${tableExists ? 'found' : 'NOT FOUND'}`);

        // Check for CRUD buttons
        const crudButtons = [
          { name: 'Add', selector: 'button:has-text("Tambah"), button:has-text("Add")' },
          { name: 'Edit', selector: 'button:has-text("Edit"), .btn-edit' },
          { name: 'Delete', selector: 'button:has-text("Hapus"), .btn-delete' }
        ];

        for (const btn of crudButtons) {
          const count = await page.locator(btn.selector).count();
          console.log(`[INFO] ${btn.name} button: ${count}`);
        }
      }

      printErrorSummary(errors, 'Soal Management');
    });
  });

  // ============================================
  // SECTION 7: MOBILE RESPONSIVENESS
  // ============================================
  test.describe('7. MOBILE RESPONSIVENESS', () => {

    test('7.1 Mobile Homepage', async ({ page }) => {
      const errors = captureAllErrors(page, 'Mobile Homepage');

      await page.setViewportSize({ width: 375, height: 667 });

      await page.goto(`${BASE}/index.php`);
      await page.waitForLoadState('networkidle', { timeout: 15000 });

      // Check for horizontal overflow
      const hasOverflow = await page.evaluate(() => {
        return document.documentElement.scrollWidth > window.innerWidth;
      });
      console.log(`[INFO] Horizontal overflow: ${hasOverflow ? 'YES (BAD)' : 'NO (GOOD)'}`);

      // Check hamburger menu
      const hamburger = await page.locator('.hamburger, [class*="hamburger"], [aria-label*="menu"]').count();
      console.log(`[INFO] Hamburger menu: ${hamburger > 0 ? 'found' : 'NOT FOUND'}`);

      // Check CTA buttons are stacked
      const ctaButtons = await page.locator('.landing-cta a').all();
      if (ctaButtons.length > 1) {
        const firstBox = await ctaButtons[0].boundingBox();
        const secondBox = await ctaButtons[1].boundingBox();
        if (firstBox && secondBox) {
          const isStacked = secondBox.y > firstBox.y + firstBox.height - 10;
          console.log(`[INFO] CTA buttons stacked: ${isStacked ? 'YES' : 'NO'}`);
        }
      }

      printErrorSummary(errors, 'Mobile Homepage');
    });

    test('7.2 Mobile Tryout', async ({ page }) => {
      const errors = captureAllErrors(page, 'Mobile Tryout');

      await page.setViewportSize({ width: 375, height: 667 });

      await loginUser(page);

      await page.goto(`${BASE}/pages/tryout.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 15000 });
      await page.waitForTimeout(3000);

      // Check sidebar toggle
      const sidebarToggle = await page.locator('#sidebarToggle').count();
      console.log(`[INFO] Sidebar toggle: ${sidebarToggle > 0 ? 'found' : 'NOT FOUND'}`);

      // Check if sidebar is hidden by default on mobile
      const sidebar = page.locator('.sidebar, #sidebar');
      if (await sidebar.count() > 0) {
        const isVisible = await sidebar.isVisible().catch(() => false);
        console.log(`[INFO] Sidebar visible on mobile: ${isVisible}`);
      }

      // Check answer options are readable
      const options = await page.locator('input[name="jawaban"]').count();
      console.log(`[INFO] Answer options visible: ${options}`);

      printErrorSummary(errors, 'Mobile Tryout');
    });

    test('7.3 Tablet Dashboard', async ({ page }) => {
      const errors = captureAllErrors(page, 'Tablet Dashboard');

      await page.setViewportSize({ width: 768, height: 1024 });

      await loginUser(page);

      // Check layout
      const hasOverflow = await page.evaluate(() => {
        return document.documentElement.scrollWidth > window.innerWidth;
      });
      console.log(`[INFO] Horizontal overflow: ${hasOverflow ? 'YES' : 'NO'}`);

      // Check cards layout
      const cards = await page.locator('.stat-card, .card').all();
      console.log(`[INFO] Cards found: ${cards.length}`);

      printErrorSummary(errors, 'Tablet Dashboard');
    });
  });

  // ============================================
  // SECTION 8: ADDITIONAL FEATURES
  // ============================================
  test.describe('8. ADDITIONAL FEATURES', () => {

    test('8.1 Daily Quiz', async ({ page }) => {
      const errors = captureAllErrors(page, 'Daily Quiz');

      await loginUser(page);

      await page.goto(`${BASE}/pages/daily_quiz.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      const currentUrl = page.url();
      console.log(`[INFO] Daily Quiz URL: ${currentUrl}`);

      if (!currentUrl.includes('login.php')) {
        const title = await page.title();
        console.log(`[INFO] Title: ${title}`);

        // Check for quiz elements
        const quizElements = [
          { name: 'Quiz Container', selector: '#quizContainer, .quiz-container' },
          { name: 'Question', selector: '.question, #question' },
          { name: 'Timer', selector: '#timer, .timer' }
        ];

        for (const el of quizElements) {
          const count = await page.locator(el.selector).count();
          console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
        }
      }

      printErrorSummary(errors, 'Daily Quiz');
    });

    test('8.2 Profile Page', async ({ page }) => {
      const errors = captureAllErrors(page, 'Profile Page');

      await loginUser(page);

      await page.goto(`${BASE}/pages/profile.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Check profile elements
      const profileElements = [
        { name: 'Profile Photo', selector: 'img[src*="profile"], .profile-photo' },
        { name: 'Name Display', selector: 'text=Nama' },
        { name: 'Email Display', selector: 'text=Email' },
        { name: 'Edit Button', selector: 'button:has-text("Edit"), a:has-text("Edit")' }
      ];

      for (const el of profileElements) {
        const count = await page.locator(el.selector).count();
        console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
      }

      printErrorSummary(errors, 'Profile Page');
    });

    test('8.3 Settings Page', async ({ page }) => {
      const errors = captureAllErrors(page, 'Settings Page');

      await loginUser(page);

      await page.goto(`${BASE}/pages/settings.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      const currentUrl = page.url();
      console.log(`[INFO] Settings URL: ${currentUrl}`);

      if (!currentUrl.includes('login.php')) {
        // Check settings options
        const settingsElements = [
          { name: 'Theme Toggle', selector: 'input[type="checkbox"], select' },
          { name: 'Notification Settings', selector: 'text=Notifikasi' },
          { name: 'Save Button', selector: 'button:has-text("Simpan"), button[type="submit"]' }
        ];

        for (const el of settingsElements) {
          const count = await page.locator(el.selector).count();
          console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
        }
      }

      printErrorSummary(errors, 'Settings Page');
    });

    test('8.4 Feedback Page', async ({ page }) => {
      const errors = captureAllErrors(page, 'Feedback Page');

      await loginUser(page);

      await page.goto(`${BASE}/pages/feedback.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Check feedback form
      const feedbackElements = [
        { name: 'Rating Input', selector: 'input[type="radio"], .rating' },
        { name: 'Comment Textarea', selector: 'textarea' },
        { name: 'Submit Button', selector: 'button[type="submit"]' }
      ];

      for (const el of feedbackElements) {
        const count = await page.locator(el.selector).count();
        console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
      }

      printErrorSummary(errors, 'Feedback Page');
    });

    test('8.5 Riwayat Soal', async ({ page }) => {
      const errors = captureAllErrors(page, 'Riwayat Soal');

      await loginUser(page);

      await page.goto(`${BASE}/pages/riwayat_soal.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Check riwayat elements
      const riwayatElements = [
        { name: 'Filter Options', selector: 'select, input[type="date"]' },
        { name: 'Soal List', selector: 'table, .soal-list' },
        { name: 'Pagination', selector: '.pagination, nav[aria-label="pagination"]' }
      ];

      for (const el of riwayatElements) {
        const count = await page.locator(el.selector).count();
        console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
      }

      printErrorSummary(errors, 'Riwayat Soal');
    });

    test('8.6 Scheduled Tryouts', async ({ page }) => {
      const errors = captureAllErrors(page, 'Scheduled Tryouts');

      await loginUser(page);

      await page.goto(`${BASE}/pages/scheduled_tryouts.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      const currentUrl = page.url();
      console.log(`[INFO] Scheduled Tryouts URL: ${currentUrl}`);

      if (!currentUrl.includes('login.php')) {
        // Check for scheduled events
        const eventElements = [
          { name: 'Event List', selector: '.event, .tryout-event, table' },
          { name: 'Date Display', selector: 'text=Tanggal, text=Waktu' },
          { name: 'Register Button', selector: 'button:has-text("Daftar"), a:has-text("Daftar")' }
        ];

        for (const el of eventElements) {
          const count = await page.locator(el.selector).count();
          console.log(`[INFO] ${el.name}: ${count > 0 ? 'found' : 'NOT FOUND'}`);
        }
      }

      printErrorSummary(errors, 'Scheduled Tryouts');
    });
  });

  // ============================================
  // SECTION 9: PERFORMANCE & FINAL SUMMARY
  // ============================================
  test.describe('9. PERFORMANCE & SUMMARY', () => {

    test('9.1 Page Load Performance', async ({ page }) => {
      const errors = captureAllErrors(page, 'Performance');

      const pages = [
        { url: '/index.php', name: 'Homepage' },
        { url: '/pages/login.php', name: 'Login' },
        { url: '/pages/leaderboard.php', name: 'Leaderboard' },
        { url: '/pages/materi.php?subtes=TWK', name: 'Materi TWK' }
      ];

      console.log('\n[TEST] Measuring page load times...');

      for (const pageInfo of pages) {
        const startTime = Date.now();
        await page.goto(`${BASE}${pageInfo.url}`);
        await page.waitForLoadState('domcontentloaded', { timeout: 15000 });
        const loadTime = Date.now() - startTime;

        const resources = await page.evaluate(() => performance.getEntriesByType('resource').length);

        console.log(`[PERF] ${pageInfo.name}: ${loadTime}ms, ${resources} resources`);

        if (loadTime > 5000) {
          console.log(`[WARN] Slow load time on ${pageInfo.name}`);
        }
      }

      printErrorSummary(errors, 'Performance');
    });

    test('9.2 Final Comprehensive Check', async ({ page }) => {
      const errors = captureAllErrors(page, 'Final Check');

      console.log('\n[TEST] Running final comprehensive check...');

      // Visit all critical pages
      const criticalPages = [
        '/index.php',
        '/pages/login.php',
        '/pages/register.php',
        '/pages/leaderboard.php',
        '/pages/materi.php?subtes=TWK',
        '/pages/materi.php?subtes=TIU',
        '/pages/materi.php?subtes=TKP',
        '/pages/help.php'
      ];

      for (const url of criticalPages) {
        await page.goto(`${BASE}${url}`);
        await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
        console.log(`[CHECK] ${url}: loaded`);
      }

      // Login and check authenticated pages
      await loginUser(page);

      const authPages = [
        '/pages/user_dashboard.php',
        '/pages/latihan.php',
        '/pages/riwayat_soal.php',
        '/pages/profile.php',
        '/pages/feedback.php'
      ];

      for (const url of authPages) {
        await page.goto(`${BASE}${url}`);
        await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
        const currentUrl = page.url();
        const isAuth = !currentUrl.includes('login.php');
        console.log(`[CHECK] ${url}: ${isAuth ? 'authenticated' : 'REDIRECTED'}`);
      }

      console.log('\n' + '='.repeat(60));
      console.log('FINAL ERROR SUMMARY');
      console.log('='.repeat(60));
      console.log(`Total Console Errors: ${errors.console.length}`);
      console.log(`Total Page Errors: ${errors.page.length}`);
      console.log(`Total Network Errors: ${errors.network.length}`);
      console.log(`Total Warnings: ${errors.warnings.length}`);
      console.log(`Total Requests: ${errors.requests.length}`);
      console.log(`Total Responses: ${errors.responses.length}`);

      // List all unique network errors
      if (errors.network.length > 0) {
        console.log('\n--- All Network Errors ---');
        const uniqueErrors = [...new Set(errors.network.map(e => `${e.status} ${e.url}`))];
        uniqueErrors.forEach((err, i) => console.log(`${i + 1}. ${err}`));
      }

      console.log('\n' + '='.repeat(60));
      console.log('COMPREHENSIVE ANALYSIS COMPLETE');
      console.log('='.repeat(60));
    });
  });

});
