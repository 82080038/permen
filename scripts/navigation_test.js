const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({
    headless: false,
    slowMo: 500
  });

  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });

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

  console.log('=== NAVIGATION LINKS TEST ===');
  console.log('Target:', baseUrl);
  console.log('=============================\n');

  const results = [];

  // Test homepage first
  console.log('[TEST] Homepage');
  await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 30000 });
  const homeStatus = page.url().includes('bimbel.bereng.info') ? 'OK' : 'ERROR';
  results.push({ page: 'Homepage', url: baseUrl, status: homeStatus });
  console.log(`Homepage: ${homeStatus}\n`);

  // Test navigation links from homepage
  const navLinks = [
    { name: 'Beranda', url: `${baseUrl}/` },
    { name: 'Latihan', url: `${baseUrl}/pages/latihan.php` },
    { name: 'Try Out', url: `${baseUrl}/pages/tryout.php` },
    { name: 'Leaderboard', url: `${baseUrl}/pages/leaderboard.php` },
    { name: 'Bantuan', url: `${baseUrl}/pages/help.php` },
    { name: 'Login', url: `${baseUrl}/pages/login.php` },
    { name: 'Register', url: `${baseUrl}/pages/register.php` }
  ];

  for (const link of navLinks) {
    console.log(`[TEST] ${link.name}: ${link.url}`);
    try {
      const response = await page.goto(link.url, { waitUntil: 'load', timeout: 10000 });
      const status = response ? response.status() : 'ERROR';
      const finalStatus = status >= 200 && status < 400 ? 'OK' : `ERROR ${status}`;
      results.push({ page: link.name, url: link.url, status: finalStatus });
      console.log(`${link.name}: ${finalStatus}\n`);
    } catch (error) {
      results.push({ page: link.name, url: link.url, status: 'ERROR' });
      console.log(`${link.name}: ERROR - ${error.message}\n`);
    }
  }

  // Test authenticated pages (after login)
  console.log('[TEST] Testing authenticated pages');
  await page.goto(`${baseUrl}/pages/login.php`, { waitUntil: 'networkidle' });

  // Try to login with test user
  await page.fill('input[name="no_hp"]', '081234567353');
  await page.fill('input[name="password"]', 'Test123456');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(2000);

  const authLinks = [
    { name: 'User Dashboard', url: `${baseUrl}/pages/user_dashboard.php` },
    { name: 'Profile', url: `${baseUrl}/pages/profile.php` },
    { name: 'Settings', url: `${baseUrl}/pages/settings.php` },
    { name: 'Daily Quiz', url: `${baseUrl}/pages/daily_quiz.php` },
    { name: 'Scheduled Tryout', url: `${baseUrl}/pages/scheduled_tryouts.php` }
  ];

  for (const link of authLinks) {
    console.log(`[TEST] ${link.name}: ${link.url}`);
    try {
      const response = await page.goto(link.url, { waitUntil: 'load', timeout: 10000 });
      const status = response ? response.status() : 'ERROR';
      const finalStatus = status >= 200 && status < 400 ? 'OK' : `ERROR ${status}`;
      results.push({ page: link.name, url: link.url, status: finalStatus });
      console.log(`${link.name}: ${finalStatus}\n`);
    } catch (error) {
      results.push({ page: link.name, url: link.url, status: 'ERROR' });
      console.log(`${link.name}: ERROR - ${error.message}\n`);
    }
  }

  // Print summary
  console.log('\n=== TEST SUMMARY ===');
  const okCount = results.filter(r => r.status === 'OK').length;
  const errorCount = results.filter(r => r.status !== 'OK').length;

  console.log(`Total Links Tested: ${results.length}`);
  console.log(`OK: ${okCount}`);
  console.log(`Errors: ${errorCount}`);

  if (errorCount > 0) {
    console.log('\n=== FAILED LINKS ===');
    results.filter(r => r.status !== 'OK').forEach(r => {
      console.log(`${r.page}: ${r.url} - ${r.status}`);
    });
  }

  console.log('\n[STEP] Taking final screenshot...');
  await page.goto(baseUrl, { waitUntil: 'networkidle' });
  await page.screenshot({ path: 'navigation_test_screenshot.png', fullPage: true });
  console.log('✓ Screenshot saved');

  console.log('\n[STEP] Closing browser in 5 seconds...');
  await page.waitForTimeout(5000);
  await browser.close();
  console.log('[DONE] Test completed');
})();
