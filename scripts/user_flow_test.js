const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({
    headless: false,
    slowMo: 1000
  });

  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });

  // Enable logging
  context.on('console', msg => {
    console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`);
  });

  context.on('request', request => {
    console.log(`[REQUEST] ${request.method()} ${request.url()}`);
  });

  context.on('response', response => {
    if (response.status() >= 400) {
      console.error(`[ERROR ${response.status()}] ${response.url()}`);
    }
  });

  const page = await context.newPage();
  const baseUrl = 'https://bimbel.bereng.info';

  console.log('=== USER FLOW SIMULATION TEST ===');
  console.log('Target:', baseUrl);
  console.log('================================\n');

  const results = {
    registration: {},
    login: {},
    dashboard: {},
    tryout: {}
  };

  try {
    // TEST 1: User Registration
    console.log('[TEST 1] User Registration Flow');
    await page.goto(`${baseUrl}/pages/register.php`, { waitUntil: 'networkidle', timeout: 30000 });

    // Fill registration form
    const timestamp = Date.now();
    const testPhone = `08123456${timestamp.toString().slice(-4)}`;
    const testPassword = 'Test123456';

    console.log('Filling registration form...');
    await page.fill('input[name="nama"]', 'Test User Production');
    await page.fill('input[name="no_hp"]', testPhone);
    await page.fill('input[name="password"]', testPassword);
    await page.fill('input[name="password2"]', testPassword);

    // Submit form
    console.log('Submitting registration...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    // Check for success message
    const successMessage = await page.locator('.alert.success').count();
    const errorMessage = await page.locator('.alert.error').count();

    if (successMessage > 0) {
      console.log('✓ Registration successful');
      results.registration = { status: 'SUCCESS', phone: testPhone, password: testPassword };
    } else if (errorMessage > 0) {
      const errorText = await page.locator('.alert.error').textContent();
      console.log('✗ Registration failed:', errorText);
      results.registration = { status: 'FAILED', error: errorText };
    } else {
      console.log('? Registration status unclear');
      results.registration = { status: 'UNCLEAR' };
    }

    // TEST 2: User Login
    console.log('\n[TEST 2] User Login Flow');
    await page.goto(`${baseUrl}/pages/login.php`, { waitUntil: 'networkidle', timeout: 30000 });

    console.log('Filling login form...');
    await page.fill('input[name="no_hp"]', testPhone);
    await page.fill('input[name="password"]', testPassword);

    console.log('Submitting login...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    // Check if redirected to dashboard
    const currentUrl = page.url();
    await page.waitForTimeout(1000);

    if (currentUrl.includes('user_dashboard.php')) {
      console.log('✓ Login successful, redirected to user dashboard');
      results.login = { status: 'SUCCESS', redirected: 'user_dashboard' };
    } else if (currentUrl.includes('login.php')) {
      const errorElement = await page.locator('.alert.error');
      const hasError = await errorElement.count();
      if (hasError > 0) {
        const errorText = await errorElement.textContent();
        console.log('✗ Login failed:', errorText);
        results.login = { status: 'FAILED', error: errorText };
      } else {
        console.log('? Login status unclear - still on login page');
        results.login = { status: 'UNCLEAR', url: currentUrl };
      }
    } else {
      console.log('✓ Login successful, redirected to:', currentUrl);
      results.login = { status: 'SUCCESS', redirected: currentUrl };
    }

    // TEST 3: User Dashboard
    console.log('\n[TEST 3] User Dashboard Access');
    if (results.login.status === 'SUCCESS') {
      await page.waitForTimeout(1000);

      // Check dashboard elements
      const hasStats = await page.locator('.stats-grid').count();
      const hasCharts = await page.locator('.chart-container').count();
      const hasRecentActivity = await page.locator('.recent-activity').count();

      console.log('Dashboard elements:');
      console.log('  - Stats grid:', hasStats > 0 ? 'Present' : 'Missing');
      console.log('  - Charts:', hasCharts > 0 ? 'Present' : 'Missing');
      console.log('  - Recent activity:', hasRecentActivity > 0 ? 'Present' : 'Missing');

      results.dashboard = {
        status: 'SUCCESS',
        hasStats: hasStats > 0,
        hasCharts: hasCharts > 0,
        hasRecentActivity: hasRecentActivity > 0
      };
    } else {
      console.log('⊘ Skipping dashboard test (login failed)');
      results.dashboard = { status: 'SKIPPED' };
    }

    // TEST 4: Tryout Access
    console.log('\n[TEST 4] Tryout Flow');
    await page.goto(`${baseUrl}/pages/tryout.php`, { waitUntil: 'networkidle', timeout: 30000 });

    // Check tryout page elements
    const hasTryoutList = await page.locator('.tryout-list').count();
    const hasStartButton = await page.locator('button').count();

    console.log('Tryout page elements:');
    console.log('  - Tryout list:', hasTryoutList > 0 ? 'Present' : 'Missing');
    console.log('  - Start buttons:', hasStartButton > 0 ? 'Present' : 'Missing');

    results.tryout = {
      status: 'SUCCESS',
      hasTryoutList: hasTryoutList > 0,
      hasStartButton: hasStartButton > 0
    };

    // TEST 5: Logout
    console.log('\n[TEST 5] Logout Flow');
    await page.goto(`${baseUrl}/api/logout.php`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(1000);

    // Verify logged out
    await page.goto(`${baseUrl}/pages/user_dashboard.php`, { waitUntil: 'networkidle', timeout: 30000 });
    const redirectedToLogin = page.url().includes('login.php');

    if (redirectedToLogin) {
      console.log('✓ Logout successful, redirected to login');
    } else {
      console.log('✗ Logout may have failed');
    }

    // TEST 6: Admin Login (if admin credentials exist)
    console.log('\n[TEST 6] Admin Login Flow');
    await page.goto(`${baseUrl}/pages/login.php`, { waitUntil: 'networkidle', timeout: 30000 });

    // Try admin credentials (common default)
    console.log('Attempting admin login with default credentials...');
    await page.fill('input[name="no_hp"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');

    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    const adminUrl = page.url();
    if (adminUrl.includes('admin_dashboard.php')) {
      console.log('✓ Admin login successful');
      results.admin = { status: 'SUCCESS', redirected: 'admin_dashboard' };
    } else {
      console.log('⊘ Admin login failed (credentials may not exist)');
      results.admin = { status: 'FAILED', reason: 'Invalid credentials' };
    }

    // Final Summary
    console.log('\n=== TEST SUMMARY ===');
    console.log('Registration:', results.registration.status);
    console.log('User Login:', results.login.status);
    console.log('User Dashboard:', results.dashboard.status);
    console.log('Tryout:', results.tryout.status);
    console.log('Admin Login:', results.admin?.status || 'N/A');

    if (results.registration.status === 'SUCCESS') {
      console.log('\n📝 Test User Credentials:');
      console.log('   Phone:', testPhone);
      console.log('   Password:', testPassword);
    }

    console.log('\n[STEP] Taking final screenshot...');
    await page.goto(baseUrl, { waitUntil: 'networkidle' });
    await page.screenshot({ path: 'user_flow_test_screenshot.png', fullPage: true });
    console.log('✓ Screenshot saved');

  } catch (error) {
    console.error('\n[FATAL ERROR]:', error.message);
    console.error('Stack:', error.stack);
  } finally {
    console.log('\n[STEP] Closing browser in 10 seconds...');
    await page.waitForTimeout(10000);
    await browser.close();
    console.log('[DONE] Test completed');
  }
})();
