const { chromium } = require('playwright');

async function runSimulation(baseUrl, testName) {
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
  const results = {
    testName,
    baseUrl,
    tests: []
  };

  console.log(`=== ${testName} ===`);
  console.log(`Target: ${baseUrl}`);
  console.log('=============================\n');

  // Test credentials
  const testPhone = '081999888777';
  const testPassword = 'password';

  try {
    // TEST 1: Homepage
    console.log('[TEST 1] Homepage');
    await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 30000 });
    const homeTitle = await page.title();
    results.tests.push({ test: 'Homepage', status: 'OK', title: homeTitle });
    console.log(`✓ Homepage loaded: ${homeTitle}\n`);

    // TEST 2: Login
    console.log('[TEST 2] Login');
    await page.goto(`${baseUrl}/pages/login.php`, { waitUntil: 'networkidle' });
    await page.fill('input[name="no_hp"]', testPhone);
    await page.fill('input[name="password"]', testPassword);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    const currentUrl = page.url();
    if (currentUrl.includes('user_dashboard.php') || currentUrl.includes('dashboard')) {
      results.tests.push({ test: 'Login', status: 'OK', redirected: currentUrl });
      console.log(`✓ Login successful, redirected to: ${currentUrl}\n`);
    } else {
      results.tests.push({ test: 'Login', status: 'FAILED', url: currentUrl });
      console.log(`✗ Login failed, current URL: ${currentUrl}\n`);
    }

    // TEST 3: User Dashboard
    console.log('[TEST 3] User Dashboard');
    await page.goto(`${baseUrl}/pages/user_dashboard.php`, { waitUntil: 'networkidle' });
    const dashboardTitle = await page.title();
    results.tests.push({ test: 'User Dashboard', status: 'OK', title: dashboardTitle });
    console.log(`✓ Dashboard loaded: ${dashboardTitle}\n`);

    // TEST 4: Profile Page
    console.log('[TEST 4] Profile Page');
    try {
      await page.goto(`${baseUrl}/pages/profile.php`, { waitUntil: 'load', timeout: 10000 });
      const profileTitle = await page.title();
      results.tests.push({ test: 'Profile', status: 'OK', title: profileTitle });
      console.log(`✓ Profile loaded: ${profileTitle}\n`);
    } catch (error) {
      results.tests.push({ test: 'Profile', status: 'ERROR', error: error.message });
      console.log(`✗ Profile error: ${error.message}\n`);
    }

    // TEST 5: Settings Page
    console.log('[TEST 5] Settings Page');
    try {
      await page.goto(`${baseUrl}/pages/settings.php`, { waitUntil: 'load', timeout: 10000 });
      const settingsTitle = await page.title();
      results.tests.push({ test: 'Settings', status: 'OK', title: settingsTitle });
      console.log(`✓ Settings loaded: ${settingsTitle}\n`);
    } catch (error) {
      results.tests.push({ test: 'Settings', status: 'ERROR', error: error.message });
      console.log(`✗ Settings error: ${error.message}\n`);
    }

    // TEST 6: Tryout Page
    console.log('[TEST 6] Tryout Page');
    try {
      await page.goto(`${baseUrl}/pages/tryout.php`, { waitUntil: 'load', timeout: 10000 });
      const tryoutTitle = await page.title();
      results.tests.push({ test: 'Tryout', status: 'OK', title: tryoutTitle });
      console.log(`✓ Tryout loaded: ${tryoutTitle}\n`);
    } catch (error) {
      results.tests.push({ test: 'Tryout', status: 'ERROR', error: error.message });
      console.log(`✗ Tryout error: ${error.message}\n`);
    }

    // TEST 7: Latihan Page
    console.log('[TEST 7] Latihan Page');
    try {
      await page.goto(`${baseUrl}/pages/latihan.php`, { waitUntil: 'load', timeout: 10000 });
      const latihanTitle = await page.title();
      results.tests.push({ test: 'Latihan', status: 'OK', title: latihanTitle });
      console.log(`✓ Latihan loaded: ${latihanTitle}\n`);
    } catch (error) {
      results.tests.push({ test: 'Latihan', status: 'ERROR', error: error.message });
      console.log(`✗ Latihan error: ${error.message}\n`);
    }

    // TEST 8: Daily Quiz Page
    console.log('[TEST 8] Daily Quiz Page');
    try {
      await page.goto(`${baseUrl}/pages/daily_quiz.php`, { waitUntil: 'load', timeout: 10000 });
      const dailyQuizTitle = await page.title();
      results.tests.push({ test: 'Daily Quiz', status: 'OK', title: dailyQuizTitle });
      console.log(`✓ Daily Quiz loaded: ${dailyQuizTitle}\n`);
    } catch (error) {
      results.tests.push({ test: 'Daily Quiz', status: 'ERROR', error: error.message });
      console.log(`✗ Daily Quiz error: ${error.message}\n`);
    }

    // TEST 9: Scheduled Tryouts Page
    console.log('[TEST 9] Scheduled Tryouts Page');
    try {
      await page.goto(`${baseUrl}/pages/scheduled_tryouts.php`, { waitUntil: 'load', timeout: 10000 });
      const scheduledTitle = await page.title();
      results.tests.push({ test: 'Scheduled Tryouts', status: 'OK', title: scheduledTitle });
      console.log(`✓ Scheduled Tryouts loaded: ${scheduledTitle}\n`);
    } catch (error) {
      results.tests.push({ test: 'Scheduled Tryouts', status: 'ERROR', error: error.message });
      console.log(`✗ Scheduled Tryouts error: ${error.message}\n`);
    }

    // TEST 10: Leaderboard Page
    console.log('[TEST 10] Leaderboard Page');
    try {
      await page.goto(`${baseUrl}/pages/leaderboard.php`, { waitUntil: 'load', timeout: 10000 });
      const leaderboardTitle = await page.title();
      results.tests.push({ test: 'Leaderboard', status: 'OK', title: leaderboardTitle });
      console.log(`✓ Leaderboard loaded: ${leaderboardTitle}\n`);
    } catch (error) {
      results.tests.push({ test: 'Leaderboard', status: 'ERROR', error: error.message });
      console.log(`✗ Leaderboard error: ${error.message}\n`);
    }

    // TEST 11: Help Page
    console.log('[TEST 11] Help Page');
    try {
      await page.goto(`${baseUrl}/pages/help.php`, { waitUntil: 'load', timeout: 10000 });
      const helpTitle = await page.title();
      results.tests.push({ test: 'Help', status: 'OK', title: helpTitle });
      console.log(`✓ Help loaded: ${helpTitle}\n`);
    } catch (error) {
      results.tests.push({ test: 'Help', status: 'ERROR', error: error.message });
      console.log(`✗ Help error: ${error.message}\n`);
    }

    // TEST 12: Logout
    console.log('[TEST 12] Logout');
    try {
      await page.goto(`${baseUrl}/api/logout.php`, { waitUntil: 'load', timeout: 10000 });
      await page.waitForTimeout(1000);
      const logoutUrl = page.url();
      if (logoutUrl.includes('login.php')) {
        results.tests.push({ test: 'Logout', status: 'OK', redirected: logoutUrl });
        console.log(`✓ Logout successful, redirected to: ${logoutUrl}\n`);
      } else {
        results.tests.push({ test: 'Logout', status: 'UNCLEAR', url: logoutUrl });
        console.log(`? Logout status unclear, current URL: ${logoutUrl}\n`);
      }
    } catch (error) {
      results.tests.push({ test: 'Logout', status: 'ERROR', error: error.message });
      console.log(`✗ Logout error: ${error.message}\n`);
    }

  } catch (error) {
    console.error(`Simulation error: ${error.message}`);
    results.tests.push({ test: 'Simulation', status: 'ERROR', error: error.message });
  }

  // Print summary
  console.log('\n=== TEST SUMMARY ===');
  const okCount = results.tests.filter(t => t.status === 'OK').length;
  const errorCount = results.tests.filter(t => t.status !== 'OK').length;

  console.log(`Total Tests: ${results.tests.length}`);
  console.log(`OK: ${okCount}`);
  console.log(`Errors: ${errorCount}`);

  if (errorCount > 0) {
    console.log('\n=== FAILED TESTS ===');
    results.tests.filter(t => t.status !== 'OK').forEach(t => {
      console.log(`${t.test}: ${t.status} - ${t.error || t.url || t.redirected}`);
    });
  }

  console.log('\n[STEP] Taking final screenshot...');
  await page.goto(baseUrl, { waitUntil: 'networkidle' });
  await page.screenshot({ path: `${testName.replace(/\s+/g, '_')}_screenshot.png`, fullPage: true });
  console.log('✓ Screenshot saved');

  console.log('\n[STEP] Closing browser in 5 seconds...');
  await page.waitForTimeout(5000);
  await browser.close();
  console.log('[DONE] Test completed\n');

  return results;
}

(async () => {
  // Run local first as it's the mirror of production
  const localResults = await runSimulation('http://localhost/permen', 'Local Environment');
  const productionResults = await runSimulation('https://bimbel.bereng.info', 'Production Environment');

  console.log('\n=== COMPARISON SUMMARY ===');
  console.log('Local Results:');
  console.log(`  Total: ${localResults.tests.length}, OK: ${localResults.tests.filter(t => t.status === 'OK').length}, Errors: ${localResults.tests.filter(t => t.status !== 'OK').length}`);

  console.log('Production Results:');
  console.log(`  Total: ${productionResults.tests.length}, OK: ${productionResults.tests.filter(t => t.status === 'OK').length}, Errors: ${productionResults.tests.filter(t => t.status !== 'OK').length}`);

  console.log('\n=== DIFFERENCES ===');
  const maxTests = Math.max(localResults.tests.length, productionResults.tests.length);
  for (let i = 0; i < maxTests; i++) {
    const t = localResults.tests[i];
    const pTest = productionResults.tests[i];
    if (t && pTest && t.status !== pTest.status) {
      console.log(`${t.test}: Local=${t.status}, Production=${pTest.status}`);
    } else if (t && !pTest) {
      console.log(`${t.test}: Local=${t.status}, Production=NOT_RUN`);
    } else if (!t && pTest) {
      console.log(`${pTest.test}: Local=NOT_RUN, Production=${pTest.status}`);
    }
  }

  console.log('\n=== FINAL VERDICT ===');
  const localOk = localResults.tests.filter(t => t.status === 'OK').length === localResults.tests.length;
  const prodOk = productionResults.tests.filter(t => t.status === 'OK').length === productionResults.tests.length;

  if (localOk && prodOk) {
    console.log('✅ Both environments are working correctly');
  } else if (localOk && !prodOk) {
    console.log('⚠️ Local working, Production has errors');
  } else if (!localOk && prodOk) {
    console.log('⚠️ Production working, Local has errors');
  } else {
    console.log('❌ Both environments have errors');
  }
})();
