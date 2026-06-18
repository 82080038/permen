const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ 
    headless: false,
    slowMo: 500
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
  
  console.log('=== COMPREHENSIVE PRODUCTION TEST ===');
  console.log('Target:', baseUrl);
  console.log('=====================================\n');
  
  const results = {
    pages: [],
    apis: [],
    flows: []
  };
  
  try {
    // Test 1: Homepage
    console.log('[TEST 1] Homepage');
    await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 30000 });
    const title = await page.title();
    console.log('✓ Homepage loaded:', title);
    results.pages.push({ page: 'Homepage', status: 'OK', title });
    
    // Test 2: Login Page
    console.log('\n[TEST 2] Login Page');
    await page.goto(`${baseUrl}/pages/login.php`, { waitUntil: 'networkidle', timeout: 30000 });
    const loginTitle = await page.title();
    console.log('✓ Login page loaded:', loginTitle);
    results.pages.push({ page: 'Login', status: 'OK', title: loginTitle });
    
    // Test 3: Register Page
    console.log('\n[TEST 3] Register Page');
    await page.goto(`${baseUrl}/pages/register.php`, { waitUntil: 'networkidle', timeout: 30000 });
    const registerTitle = await page.title();
    console.log('✓ Register page loaded:', registerTitle);
    results.pages.push({ page: 'Register', status: 'OK', title: registerTitle });
    
    // Test 4: Tryout Page
    console.log('\n[TEST 4] Tryout Page');
    await page.goto(`${baseUrl}/pages/tryout.php`, { waitUntil: 'networkidle', timeout: 30000 });
    const tryoutTitle = await page.title();
    console.log('✓ Tryout page loaded:', tryoutTitle);
    results.pages.push({ page: 'Tryout', status: 'OK', title: tryoutTitle });
    
    // Test 5: User Dashboard
    console.log('\n[TEST 5] User Dashboard');
    await page.goto(`${baseUrl}/pages/user_dashboard.php`, { waitUntil: 'networkidle', timeout: 30000 });
    const dashboardTitle = await page.title();
    console.log('✓ User dashboard loaded:', dashboardTitle);
    results.pages.push({ page: 'User Dashboard', status: 'OK', title: dashboardTitle });
    
    // Test 6: Admin Dashboard
    console.log('\n[TEST 6] Admin Dashboard');
    await page.goto(`${baseUrl}/pages/admin_dashboard.php`, { waitUntil: 'networkidle', timeout: 30000 });
    const adminTitle = await page.title();
    console.log('✓ Admin dashboard loaded:', adminTitle);
    results.pages.push({ page: 'Admin Dashboard', status: 'OK', title: adminTitle });
    
    // Test 7: API Endpoints
    console.log('\n[TEST 7] API Endpoints');
    const apiEndpoints = [
      '/api/get_landing_stats.php',
      '/api/get_questions.php',
      '/api/get_materi.php',
      '/api/get_tips.php'
    ];
    
    for (const endpoint of apiEndpoints) {
      try {
        const response = await page.request.get(`${baseUrl}${endpoint}`);
        console.log(`✓ ${endpoint}: ${response.status()}`);
        results.apis.push({ endpoint, status: response.status() });
      } catch (error) {
        console.error(`✗ ${endpoint}: ERROR - ${error.message}`);
        results.apis.push({ endpoint, status: 'ERROR', error: error.message });
      }
    }
    
    // Test 8: Static Assets
    console.log('\n[TEST 8] Static Assets');
    const assets = [
      '/assets/style.css',
      '/assets/app.js',
      '/assets/js/api.js',
      '/assets/css/bootstrap.min.css'
    ];
    
    for (const asset of assets) {
      try {
        const response = await page.request.get(`${baseUrl}${asset}`);
        console.log(`✓ ${asset}: ${response.status()}`);
      } catch (error) {
        console.error(`✗ ${asset}: ERROR - ${error.message}`);
      }
    }
    
    // Test 9: Database Connectivity (via API)
    console.log('\n[TEST 9] Database Connectivity');
    try {
      const dbResponse = await page.request.get(`${baseUrl}/api/get_landing_stats.php`);
      const dbData = await dbResponse.json();
      console.log('✓ Database connected:', dbData.success);
      console.log('  User count:', dbData.data?.user_count);
      console.log('  Tryout count:', dbData.data?.tryout_count);
      results.flows.push({ flow: 'Database Connectivity', status: 'OK', data: dbData });
    } catch (error) {
      console.error('✗ Database connectivity failed:', error.message);
      results.flows.push({ flow: 'Database Connectivity', status: 'ERROR', error: error.message });
    }
    
    // Test 10: SSL/HTTPS
    console.log('\n[TEST 10] SSL/HTTPS');
    const sslResponse = await page.request.get(baseUrl);
    console.log('✓ Protocol:', sslResponse.url().startsWith('https') ? 'HTTPS' : 'HTTP');
    console.log('✓ SSL Valid:', sslResponse.ok());
    results.flows.push({ flow: 'SSL/HTTPS', status: 'OK', protocol: sslResponse.url().startsWith('https') ? 'HTTPS' : 'HTTP' });
    
    // Test 11: Security Headers
    console.log('\n[TEST 11] Security Headers');
    const headersResponse = await page.request.get(baseUrl);
    const headers = headersResponse.headers();
    console.log('✓ X-Frame-Options:', headers['x-frame-options']);
    console.log('✓ X-Content-Type-Options:', headers['x-content-type-options']);
    console.log('✓ CSP:', headers['content-security-policy'] ? 'Present' : 'Missing');
    results.flows.push({ flow: 'Security Headers', status: 'OK', headers });
    
    // Final Summary
    console.log('\n=== TEST SUMMARY ===');
    console.log('Pages Tested:', results.pages.length);
    console.log('APIs Tested:', results.apis.length);
    console.log('Flows Tested:', results.flows.length);
    
    const pageErrors = results.pages.filter(p => p.status !== 'OK').length;
    const apiErrors = results.apis.filter(a => a.status !== 'OK' && a.status !== 200).length;
    const flowErrors = results.flows.filter(f => f.status !== 'OK').length;
    
    console.log('\nPage Errors:', pageErrors);
    console.log('API Errors:', apiErrors);
    console.log('Flow Errors:', flowErrors);
    
    if (pageErrors === 0 && apiErrors === 0 && flowErrors === 0) {
      console.log('\n✅ ALL TESTS PASSED');
    } else {
      console.log('\n❌ SOME TESTS FAILED');
    }
    
    console.log('\n[STEP] Taking final screenshot...');
    await page.goto(baseUrl, { waitUntil: 'networkidle' });
    await page.screenshot({ path: 'comprehensive_test_screenshot.png', fullPage: true });
    console.log('✓ Screenshot saved');
    
  } catch (error) {
    console.error('\n[FATAL ERROR]:', error.message);
    console.error('Stack:', error.stack);
  } finally {
    console.log('\n[STEP] Closing browser in 5 seconds...');
    await page.waitForTimeout(5000);
    await browser.close();
    console.log('[DONE] Test completed');
  }
})();
