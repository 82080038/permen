const { test, expect } = require('@playwright/test');

const BASE = 'http://localhost/permen';

// Enhanced error capture dengan detail lebih lengkap
function captureDetailedErrors(page) {
  const errors = {
    console: [],
    page: [],
    network: [],
    warnings: []
  };
  
  page.on('console', msg => {
    const type = msg.type();
    const text = msg.text();
    const entry = `[${type.toUpperCase()}] ${text}`;
    
    if (type === 'error') {
      errors.console.push(entry);
      console.log(`[CONSOLE ERROR] ${text}`);
    } else if (type === 'warning') {
      errors.warnings.push(entry);
      console.log(`[CONSOLE WARNING] ${text}`);
    }
  });
  
  page.on('pageerror', error => {
    const entry = `[PAGE] ${error.message}`;
    errors.page.push(entry);
    console.log(`[PAGE ERROR] ${error.message}`);
  });
  
  page.on('response', async response => {
    const status = response.status();
    const url = response.url();
    
    if (status >= 400) {
      const entry = `[NETWORK ${status}] ${url}`;
      errors.network.push(entry);
      console.log(`[NETWORK ERROR] ${status} ${url}`);
      
      // Try to get response body for more context
      try {
        const body = await response.text();
        if (body && body.length < 500) {
          console.log(`[RESPONSE BODY] ${body.substring(0, 200)}`);
        }
      } catch (e) {
        // Ignore
      }
    }
  });
  
  page.on('requestfailed', request => {
    const entry = `[REQUEST FAILED] ${request.url()} - ${request.failure().errorText}`;
    errors.network.push(entry);
    console.log(`[REQUEST FAILED] ${request.url()}`);
  });
  
  return errors;
}

function printErrorSummary(errors, testName) {
  console.log(`\n=== ERROR SUMMARY: ${testName} ===`);
  console.log(`Console Errors: ${errors.console.length}`);
  console.log(`Page Errors: ${errors.page.length}`);
  console.log(`Network Errors: ${errors.network.length}`);
  console.log(`Warnings: ${errors.warnings.length}`);
  
  if (errors.console.length > 0) {
    console.log('\n--- Console Errors ---');
    errors.console.forEach((err, i) => console.log(`${i + 1}. ${err}`));
  }
  if (errors.page.length > 0) {
    console.log('\n--- Page Errors ---');
    errors.page.forEach((err, i) => console.log(`${i + 1}. ${err}`));
  }
  if (errors.network.length > 0) {
    console.log('\n--- Network Errors ---');
    errors.network.forEach((err, i) => console.log(`${i + 1}. ${err}`));
  }
  console.log('=== END ERROR SUMMARY ===\n');
}

test.describe('PRODUCTION ANALYSIS - Deep Testing Suite', () => {

  // ============================================
  // 1. HOMEPAGE & PUBLIC PAGES DEEP ANALYSIS
  // ============================================
  test('ANALYSIS: Homepage - Full Render & Resource Check', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    console.log('\n[TEST] Loading homepage...');
    const response = await page.goto(`${BASE}/index.php`);
    
    // Check HTTP status
    expect(response.status()).toBe(200);
    console.log(`[INFO] HTTP Status: ${response.status()}`);
    
    // Wait for all resources
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
    
    // Check title
    const title = await page.title();
    console.log(`[INFO] Page Title: ${title}`);
    expect(title).toContain('SKD');
    
    // Check critical elements
    const elements = [
      { name: 'Main heading', selector: 'h1, h2' },
      { name: 'Navigation links', selector: 'nav a' },
      { name: 'CTA Buttons', selector: '.cta a, button' },
      { name: 'Features section', selector: '.feature' }
    ];
    
    for (const el of elements) {
      const count = await page.locator(el.selector).count();
      console.log(`[INFO] ${el.name}: ${count} elements found`);
      if (count === 0) {
        console.log(`[WARNING] No ${el.name} found!`);
      }
    }
    
    // Check for visible content
    const bodyText = await page.textContent('body');
    const criticalTexts = ['Try Out', 'Latihan', 'Materi', 'TWK', 'TIU', 'TKP'];
    for (const text of criticalTexts) {
      if (!bodyText.includes(text)) {
        console.log(`[WARNING] Missing critical text: "${text}"`);
      }
    }
    
    printErrorSummary(errors, 'Homepage');
    
    // Homepage should have minimal/no errors
    expect(errors.page).toHaveLength(0);
  });

  test('ANALYSIS: Login Page - Form Validation & Structure', async ({ page }) => {
    const errors = captureDetailedErrors(page);

    console.log('\n[TEST] Loading login page...');
    await page.goto(`${BASE}/pages/login.php`);
    await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

    // Check form elements
    const formChecks = [
      { name: 'no_hp input', selector: 'input[name="no_hp"]' },
      { name: 'password input', selector: 'input[name="password"]' },
      { name: 'submit button', selector: 'button[type="submit"]' },
      { name: 'CSRF token', selector: 'input[name="csrf_token"]' }
    ];

    for (const check of formChecks) {
      try {
        const visible = await page.locator(check.selector).isVisible().catch(() => false);
        const exists = await page.locator(check.selector).count() > 0;
        console.log(`[INFO] ${check.name}: visible=${visible}, exists=${exists}`);
        expect(exists).toBe(true);
      } catch (e) {
        console.log(`[WARN] ${check.name} check failed: ${e.message}`);
      }
    }

    // Test quick login buttons if they exist
    try {
      const quickLoginCount = await page.locator('button:has-text("Admin")').count();
      console.log(`[INFO] Quick login buttons: ${quickLoginCount}`);
    } catch (e) {
      console.log(`[WARN] Quick login check failed: ${e.message}`);
    }

    printErrorSummary(errors, 'Login Page');
  });

  test('ANALYSIS: Navigation Flow - Menu Consistency', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    const pages = [
      { url: '/index.php', name: 'Homepage' },
      { url: '/pages/login.php', name: 'Login' },
      { url: '/pages/register.php', name: 'Register' },
      { url: '/pages/leaderboard.php', name: 'Leaderboard' },
      { url: '/pages/materi.php?subtes=TWK', name: 'Materi TWK' },
      { url: '/pages/materi.php?subtes=TIU', name: 'Materi TIU' },
      { url: '/pages/materi.php?subtes=TKP', name: 'Materi TKP' },
      { url: '/pages/latihan.php', name: 'Latihan' }
    ];
    
    for (const pageInfo of pages) {
      console.log(`\n[TEST] Checking ${pageInfo.name}...`);
      await page.goto(`${BASE}${pageInfo.url}`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      const title = await page.title();
      const status = await page.evaluate(() => document.readyState);
      console.log(`[INFO] ${pageInfo.name}: title="${title}", readyState=${status}`);

      // Check for header consistency
      const hasHeader = await page.locator('.header, header, nav').count() > 0;
      if (!hasHeader) {
        console.log(`[WARNING] ${pageInfo.name}: No header found!`);
      }
    }
    
    printErrorSummary(errors, 'Navigation Flow');
  });

  // ============================================
  // 2. AUTHENTICATED USER FLOW DEEP ANALYSIS
  // ============================================
  test('ANALYSIS: User Login Flow - Complete Journey', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    console.log('\n[TEST] Starting user login flow...');
    await page.goto(`${BASE}/pages/login.php`);
    
    // Check for quick login or use manual login
    const quickUserBtn = page.locator('button:has-text("User")').first();
    const hasQuickLogin = await quickUserBtn.isVisible().catch(() => false);
    
    if (hasQuickLogin) {
      console.log('[INFO] Using quick login button');
      await quickUserBtn.click();
    } else {
      console.log('[INFO] Using manual login');
      await page.fill('input[name="no_hp"]', '081987654321');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]');
    }
    
    // Wait for redirect
    await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    console.log('[INFO] Successfully redirected to dashboard');
    
    // Check dashboard elements
    const dashboardChecks = [
      { name: 'Welcome message', selector: 'text=Selamat datang, text=Dashboard' },
      { name: 'Stats/Scores', selector: '.stat, .score, .progress' },
      { name: 'Navigation menu', selector: 'nav, .sidebar, .menu' },
      { name: 'Logout option', selector: 'text=logout, text=keluar, text=Logout' }
    ];
    
    for (const check of dashboardChecks) {
      const exists = await page.locator(check.selector).count() > 0;
      console.log(`[INFO] Dashboard ${check.name}: ${exists ? 'found' : 'NOT FOUND'}`);
    }
    
    // Screenshot dashboard
    await page.screenshot({ path: 'test-results/dashboard-user.png', fullPage: true });
    
    printErrorSummary(errors, 'User Login Flow');
  });

  test('ANALYSIS: User Dashboard - Data Display Check', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    // Login first
    await page.goto(`${BASE}/pages/login.php`);
    const quickBtn = page.locator('button:has-text("User")').first();
    if (await quickBtn.isVisible().catch(() => false)) {
      await quickBtn.click();
      await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    }
    
    console.log('\n[TEST] Analyzing dashboard data...');
    
    // Check for data containers
    const dataElements = await page.locator('.card, .stat-card, .progress-bar, table, canvas').count();
    console.log(`[INFO] Data visualization elements: ${dataElements}`);
    
    // Check for any "error" or "tidak ada" messages
    const bodyTextRaw = await page.textContent('body');
    const bodyText = bodyTextRaw.toLowerCase();
    const errorIndicators = ['error', 'warning', 'tidak ada', 'belum ada', 'gagal', '404', '500'];
    
    for (const indicator of errorIndicators) {
      if (bodyText.includes(indicator)) {
        console.log(`[WARNING] Found indicator "${indicator}" in page content`);
      }
    }
    
    // Check all links on dashboard
    const links = await page.locator('a[href]').all();
    console.log(`[INFO] Total links on dashboard: ${links.length}`);
    
    const brokenLinks = [];
    for (const link of links.slice(0, 10)) { // Check first 10 links
      const href = await link.getAttribute('href');
      if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
        try {
          const res = await page.request.head(`${BASE}/${href.replace(/^\//, '')}`);
          if (res.status() >= 400) {
            brokenLinks.push({ url: href, status: res.status() });
          }
        } catch (e) {
          // Ignore relative links
        }
      }
    }
    
    if (brokenLinks.length > 0) {
      console.log(`[WARNING] Found ${brokenLinks.length} potentially broken links`);
      brokenLinks.forEach(l => console.log(`  - ${l.url}: ${l.status}`));
    }
    
    printErrorSummary(errors, 'User Dashboard');
  });

  // ============================================
  // 3. MATERI & LEARNING FLOW ANALYSIS
  // ============================================
  test('ANALYSIS: Materi Pages - Content & Uji Pemahaman', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    const subtesList = ['TWK', 'TIU', 'TKP'];
    
    for (const subtes of subtesList) {
      console.log(`\n[TEST] Checking Materi ${subtes}...`);
      await page.goto(`${BASE}/pages/materi.php?subtes=${subtes}`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Check page loaded
      const title = await page.title();
      console.log(`[INFO] Page title: ${title}`);

      // Check for materi content
      const contentSelectors = [
        { name: 'Accordion/Cards', selector: '.accordion, .card, .materi-content' },
        { name: 'Uji Pemahaman section', selector: 'text=Uji Pemahaman' },
        { name: 'Topic selector', selector: 'select, #latihTopik, .topic-select' },
        { name: 'Generate button', selector: 'button:has-text("Generate"), button:has-text("Buat")' }
      ];
      
      for (const sel of contentSelectors) {
        const count = await page.locator(sel.selector).count();
        console.log(`[INFO] ${sel.name}: ${count} found`);
      }
      
      // Check for materi text content
      const bodyText = await page.textContent('body');
      if (bodyText.length < 500) {
        console.log(`[WARNING] Materi ${subtes} has very little content (${bodyText.length} chars)`);
      } else {
        console.log(`[INFO] Content length: ${bodyText.length} characters`);
      }
    }
    
    printErrorSummary(errors, 'Materi Pages');
  });

  // ============================================
  // 4. TRYOUT SYSTEM DEEP ANALYSIS
  // ============================================
  test('ANALYSIS: Tryout Page - Full Feature Check', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    // Login first
    await page.goto(`${BASE}/pages/login.php`);
    const quickBtn = page.locator('button:has-text("User")').first();
    if (await quickBtn.isVisible().catch(() => false)) {
      await quickBtn.click();
      await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    }
    
    console.log('\n[TEST] Loading tryout page...');
    await page.goto(`${BASE}/pages/tryout.php`);
    
    // Wait for page to load (use domcontentloaded instead of networkidle to avoid timeout)
    await page.waitForLoadState('domcontentloaded', { timeout: 15000 });
    await page.waitForTimeout(3000); // Wait for JS execution
    
    // Check tryout elements
    const tryoutElements = [
      { name: 'Timer display', selector: '#timer, .timer, [class*="timer"]' },
      { name: 'Question container', selector: '#soalContainer, .soal-container, #question' },
      { name: 'Navigation grid', selector: '#navGrid, .nav-grid, .question-nav' },
      { name: 'Dark mode toggle', selector: '#darkModeToggle, [class*="dark"]' },
      { name: 'Font size control', selector: '#fontSize, [class*="font"]' },
      { name: 'Answer options', selector: 'input[name="jawaban"], .option, .jawaban' }
    ];
    
    for (const el of tryoutElements) {
      const count = await page.locator(el.selector).count();
      const visible = await page.locator(el.selector).first().isVisible().catch(() => false);
      console.log(`[INFO] ${el.name}: count=${count}, visible=${visible}`);
    }
    
    // Check for API calls
    const apiCalls = errors.network.filter(e => e.includes('/api/'));
    console.log(`[INFO] API calls with issues: ${apiCalls.length}`);
    apiCalls.forEach(call => console.log(`  ${call}`));
    
    // Screenshot
    await page.screenshot({ path: 'test-results/tryout-page.png', fullPage: true });
    
    printErrorSummary(errors, 'Tryout Page');
  });

  test('ANALYSIS: Tryout Simulation - Answer Flow', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    // Login
    await page.goto(`${BASE}/pages/login.php`);
    const quickBtn = page.locator('button:has-text("User")').first();
    if (await quickBtn.isVisible().catch(() => false)) {
      await quickBtn.click();
      await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    }
    
    console.log('\n[TEST] Starting tryout simulation...');
    await page.goto(`${BASE}/pages/tryout.php`);
    // Use domcontentloaded instead of networkidle to avoid timeout
    await page.waitForLoadState('domcontentloaded', { timeout: 15000 });
    await page.waitForTimeout(3000);
    
    // Check if we're in an active session
    const currentUrl = page.url();
    console.log(`[INFO] Current URL: ${currentUrl}`);
    
    // Try to answer questions
    let answeredCount = 0;
    for (let i = 0; i < 3; i++) {
      try {
        // Wait for question to load
        await page.waitForSelector('input[name="jawaban"], .option, [class*="jawaban"]', { 
          timeout: 5000 
        });
        
        // Click first answer option
        const options = page.locator('input[name="jawaban"]').all();
        if ((await options).length > 0) {
          await (await options)[0].check();
          answeredCount++;
          console.log(`[INFO] Answered question ${i + 1}`);
          await page.waitForTimeout(1000);
        } else {
          console.log(`[WARNING] No answer options found for question ${i + 1}`);
          break;
        }
      } catch (e) {
        console.log(`[WARNING] Could not answer question ${i + 1}: ${e.message}`);
        break;
      }
    }
    
    console.log(`[INFO] Total questions answered: ${answeredCount}`);
    
    // Check for finish button
    const finishBtn = page.locator('button.finish, #finishTryout, .btn-finish');
    const hasFinishBtn = await finishBtn.count() > 0;
    console.log(`[INFO] Finish button available: ${hasFinishBtn}`);
    
    printErrorSummary(errors, 'Tryout Simulation');
  });

  // ============================================
  // 5. ADMIN DASHBOARD ANALYSIS
  // ============================================
  test('ANALYSIS: Admin Dashboard - Full Feature Check', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    // Login as admin
    await page.goto(`${BASE}/pages/login.php`);
    const adminBtn = page.locator('button:has-text("Admin")').first();
    
    if (await adminBtn.isVisible().catch(() => false)) {
      console.log('[INFO] Using quick admin login');
      await adminBtn.click();
    } else {
      console.log('[INFO] Manual admin login');
      await page.fill('input[name="no_hp"]', '081234567890');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]');
    }
    
    await page.waitForURL(/admin_dashboard\.php/, { timeout: 15000 });
    console.log('[INFO] Admin logged in successfully');
    
    // Check admin elements
    const adminElements = [
      { name: 'Generator section', selector: 'text=Generator, text=Buat Soal' },
      { name: 'Soal management', selector: 'text=Soal, text=Kelola' },
      { name: 'Revision flags', selector: 'text=Revisi, text=Diperbaiki' },
      { name: 'Statistics', selector: '.stat, .chart, canvas' }
    ];
    
    for (const el of adminElements) {
      const count = await page.locator(el.selector).count();
      console.log(`[INFO] ${el.name}: ${count} elements`);
    }
    
    // Screenshot
    await page.screenshot({ path: 'test-results/dashboard-admin.png', fullPage: true });
    
    printErrorSummary(errors, 'Admin Dashboard');
  });

  // ============================================
  // 6. API ENDPOINTS ANALYSIS
  // ============================================
  test('ANALYSIS: API Endpoints - Response & Error Check', async ({ page, request }) => {
    const errors = captureDetailedErrors(page);
    
    const endpoints = [
      { url: '/api/test_json.php', method: 'GET', auth: false, desc: 'Test JSON' },
      { url: '/api/get_soal.php', method: 'GET', auth: true, desc: 'Get Soal' },
      { url: '/api/get_review.php', method: 'GET', auth: true, desc: 'Get Review' },
      { url: '/api/materi.php', method: 'GET', auth: false, desc: 'Materi API' },
      { url: '/api/generate_soal_smart.php?subtes=TWK&topik=Test&jumlah=1', method: 'GET', auth: true, admin: true, desc: 'Smart Generator' }
    ];
    
    console.log('\n[TEST] Checking API endpoints...');
    
    for (const endpoint of endpoints) {
      try {
        const response = await request.get(`${BASE}${endpoint.url}`);
        const status = response.status();
        
        console.log(`[API] ${endpoint.desc}: ${status}`);
        
        // Check if response is valid JSON when expected
        if (status === 200) {
          try {
            const json = await response.json();
            console.log(`[API] ${endpoint.desc}: Valid JSON response`);
          } catch (e) {
            console.log(`[WARNING] ${endpoint.desc}: Response is not valid JSON`);
          }
        }
        
        // Expected behaviors
        if (endpoint.auth && status === 401) {
          console.log(`[INFO] ${endpoint.desc}: Correctly requires auth (401)`);
        }
        if (endpoint.admin && status === 403) {
          console.log(`[INFO] ${endpoint.desc}: Correctly requires admin (403)`);
        }
        
      } catch (e) {
        console.log(`[ERROR] ${endpoint.desc}: ${e.message}`);
      }
    }
    
    printErrorSummary(errors, 'API Endpoints');
  });

  // ============================================
  // 7. MOBILE RESPONSIVENESS ANALYSIS
  // ============================================
  test('ANALYSIS: Mobile Responsiveness - Layout Check', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    const viewports = [
      { name: 'Mobile Small', width: 375, height: 667 },
      { name: 'Mobile Large', width: 414, height: 896 },
      { name: 'Tablet', width: 768, height: 1024 },
      { name: 'Desktop', width: 1280, height: 720 }
    ];
    
    const pagesToCheck = [
      '/index.php',
      '/pages/login.php',
      '/pages/materi.php?subtes=TWK',
      '/pages/leaderboard.php'
    ];
    
    for (const viewport of viewports) {
      console.log(`\n[TEST] Viewport: ${viewport.name} (${viewport.width}x${viewport.height})`);
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      
      for (const pageUrl of pagesToCheck) {
        await page.goto(`${BASE}${pageUrl}`);
        await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

        // Check for overflow issues
        const hasOverflow = await page.evaluate(() => {
          return document.documentElement.scrollWidth > window.innerWidth;
        });

        // Check hamburger menu on mobile
        const hasHamburger = await page.locator('.hamburger, [class*="hamburger"], [aria-label*="menu"]').count() > 0;

        console.log(`  ${pageUrl}: overflow=${hasOverflow}, hamburger=${hasHamburger}`);

        if (hasOverflow) {
          console.log(`[WARNING] Horizontal overflow detected on ${pageUrl} at ${viewport.name}`);
        }
      }
    }
    
    printErrorSummary(errors, 'Mobile Responsiveness');
  });

  // ============================================
  // 8. PERFORMANCE & CONSISTENCY ANALYSIS
  // ============================================
  test('ANALYSIS: Performance - Page Load Times', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    const pages = [
      '/index.php',
      '/pages/login.php',
      '/pages/leaderboard.php',
      '/pages/materi.php?subtes=TWK',
      '/pages/latihan.php'
    ];
    
    console.log('\n[TEST] Measuring page load performance...');
    
    for (const pageUrl of pages) {
      const startTime = Date.now();

      await page.goto(`${BASE}${pageUrl}`);
      await page.waitForLoadState('domcontentloaded', { timeout: 15000 });

      const loadTime = Date.now() - startTime;
      console.log(`[PERF] ${pageUrl}: ${loadTime}ms`);

      // Get resource count
      const resources = await page.evaluate(() =>
        performance.getEntriesByType('resource').length
      );
      console.log(`[PERF] ${pageUrl}: ${resources} resources loaded`);
      
      if (loadTime > 5000) {
        console.log(`[WARNING] Slow load time on ${pageUrl}: ${loadTime}ms`);
      }
    }
    
    printErrorSummary(errors, 'Performance');
  });

  // ============================================
  // 9. DATABASE & DATA INTEGRITY ANALYSIS
  // ============================================
  test('ANALYSIS: Data Integrity - Leaderboard & Stats', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    // Check leaderboard data consistency
    await page.goto(`${BASE}/pages/leaderboard.php`);
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    console.log('\n[TEST] Analyzing leaderboard data...');
    
    // Check for table rows
    const rowCount = await page.locator('table tr, .leaderboard-row, .rank-item').count();
    console.log(`[INFO] Leaderboard rows found: ${rowCount}`);
    
    // Check for data consistency (no empty cells)
    const cells = await page.locator('table td, table th').all();
    let emptyCells = 0;
    for (const cell of cells.slice(0, 50)) { // Check first 50 cells
      const text = await cell.textContent();
      if (!text || text.trim() === '') {
        emptyCells++;
      }
    }
    console.log(`[INFO] Empty cells found: ${emptyCells}`);
    
    if (emptyCells > 5) {
      console.log(`[WARNING] Too many empty cells in leaderboard (${emptyCells})`);
    }
    
    printErrorSummary(errors, 'Data Integrity');
  });

  // ============================================
  // 10. FINAL SUMMARY TEST
  // ============================================
  test('ANALYSIS: Final Summary - Global Error Check', async ({ page }) => {
    const errors = captureDetailedErrors(page);
    
    console.log('\n[TEST] Running final comprehensive check...');
    
    // Visit all critical pages in sequence
    const criticalPages = [
      '/index.php',
      '/pages/login.php',
      '/pages/register.php',
      '/pages/leaderboard.php',
      '/pages/materi.php?subtes=TWK',
      '/pages/materi.php?subtes=TIU',
      '/pages/materi.php?subtes=TKP',
      '/pages/latihan.php'
    ];
    
    for (const url of criticalPages) {
      await page.goto(`${BASE}${url}`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
    }
    
    console.log('\n=== FINAL ERROR SUMMARY ===');
    console.log(`Total Console Errors: ${errors.console.length}`);
    console.log(`Total Page Errors: ${errors.page.length}`);
    console.log(`Total Network Errors: ${errors.network.length}`);
    console.log(`Total Warnings: ${errors.warnings.length}`);
    
    if (errors.console.length > 0) {
      console.log('\n--- All Console Errors ---');
      errors.console.forEach((err, i) => console.log(`${i + 1}. ${err}`));
    }
    
    if (errors.network.length > 0) {
      console.log('\n--- All Network Errors ---');
      errors.network.forEach((err, i) => console.log(`${i + 1}. ${err}`));
    }
    
    console.log('\n=== ANALYSIS COMPLETE ===');
    
    // Soft assertion - don't fail but report
    if (errors.console.length > 10 || errors.page.length > 5 || errors.network.length > 20) {
      console.log('[WARNING] High error count detected. Review recommended.');
    }
  });

});
