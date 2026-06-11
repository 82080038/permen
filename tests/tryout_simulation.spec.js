const { test, expect } = require('@playwright/test');

test.describe('Tryout Simulation - Complete Flow', () => {

  test.beforeEach(async ({ page }) => {
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log(`[BROWSER CONSOLE ERROR] ${msg.text()}`);
      }
    });
    page.on('pageerror', error => {
      console.log(`[BROWSER PAGE ERROR] ${error.message}`);
    });
    page.on('response', response => {
      if (response.status() >= 400) {
        console.log(`[BROWSER NETWORK ERROR] ${response.status()} ${response.url()}`);
      }
    });
  });

  test('complete tryout flow - login to finish', async ({ page }) => {
    // Step 1: Login
    console.log('Step 1: Logging in...');
    await page.goto('http://localhost/permen/pages/login.php');
    await expect(page).toHaveTitle(/Login/);

    // Use quick login (development mode)
    await page.click('button:has-text("User (081987654321)")');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 10000 });
    console.log('✓ Login successful');

    // Step 2: Navigate to tryout page
    console.log('Step 2: Navigating to tryout page...');
    await page.goto('http://localhost/permen/pages/tryout.php');
    await expect(page).toHaveTitle(/Try Out/);
    console.log('✓ Tryout page loaded');

    // Step 3: Start tryout
    console.log('Step 3: Starting tryout...');
    const startButton = page.locator('button:has-text("Mulai"), button:has-text("Start")');
    if (await startButton.isVisible({ timeout: 5000 })) {
      await startButton.click();
      console.log('✓ Tryout started');
      await page.waitForTimeout(2000); // Wait for session creation
    } else {
      console.log('Tryout already in progress or no start button found');
    }

    // Step 4: Answer questions (simulate answering 10 questions)
    console.log('Step 4: Answering questions...');
    for (let i = 0; i < 10; i++) {
      await page.waitForTimeout(1000);

      const currentUrl = page.url();
      if (!currentUrl.includes('tryout.php')) {
        console.log('Tryout finished or redirected');
        break;
      }

      const optionA = page.locator('.options label:first-child input[type="radio"]');
      if (await optionA.isVisible({ timeout: 3000 })) {
        await optionA.click();
        console.log(`  ✓ Answered question ${i + 1}`);
        await page.waitForTimeout(500);
      } else {
        console.log(`  ✗ No option found for question ${i + 1}`);
        break;
      }
    }

    // Step 5: Finish tryout
    console.log('Step 5: Finishing tryout...');
    const finishButton = page.locator('button.finish');
    if (await finishButton.isVisible({ timeout: 5000 })) {
      await finishButton.click();
      console.log('✓ Finish button clicked');

      const confirmButton = page.locator('button:has-text("Ya")').first();
      if (await confirmButton.isVisible({ timeout: 2000 })) {
        await confirmButton.click();
        console.log('✓ Confirmed finish');
      }
    }

    // Step 6: Wait for results
    console.log('Step 6: Waiting for results...');
    await page.waitForTimeout(3000);

    const currentUrl = page.url();
    console.log('Current URL after finish:', currentUrl);

    // Step 7: Check dashboard
    console.log('Step 7: Checking dashboard...');
    await page.goto('http://localhost/permen/pages/user_dashboard.php');
    await expect(page).toHaveTitle(/Dashboard/);

    const pieChartSection = page.locator('h2:has-text("Distribusi Skor Subtes")');
    if (await pieChartSection.isVisible({ timeout: 5000 })) {
      console.log('✓ Pie chart section visible');
    } else {
      console.log('⚠ Pie chart section not visible (no completed tryouts)');
    }

    await page.screenshot({ path: 'test-results/dashboard-after-tryout.png', fullPage: true });
    console.log('✓ Screenshot saved');

    console.log('\n=== Simulation Complete ===');
  });

  test('quick tryout with 10 questions', async ({ page }) => {
    console.log('Starting quick tryout simulation (10 questions)...');

    // Login
    await page.goto('http://localhost/permen/pages/login.php');
    await page.click('button:has-text("User (081987654321)")');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 10000 });

    // Go to tryout
    await page.goto('http://localhost/permen/pages/tryout.php');

    // Start tryout
    const startButton = page.locator('button:has-text("Mulai"), button:has-text("Start")');
    if (await startButton.isVisible({ timeout: 5000 })) {
      await startButton.click();
    }

    // Answer 10 questions with random options
    const options = ['A', 'B', 'C', 'D', 'E'];
    for (let i = 0; i < 10; i++) {
      await page.waitForTimeout(800);

      const currentUrl = page.url();
      if (!currentUrl.includes('tryout.php')) break;

      const randomOption = options[Math.floor(Math.random() * options.length)];
      const optionButton = page.locator(`.options label input[type="radio"][value="${randomOption}"]`);

      if (await optionButton.isVisible({ timeout: 2000 })) {
        await optionButton.click();
        console.log(`  Answered Q${i + 1}: ${randomOption}`);
        await page.waitForTimeout(400);
      } else {
        break;
      }
    }

    // Finish
    const finishButton = page.locator('button:has-text("Selesai"), button:has-text("Finish")');
    if (await finishButton.isVisible({ timeout: 5000 })) {
      await finishButton.click();
      const confirmButton = page.locator('button:has-text("Ya")').first();
      if (await confirmButton.isVisible({ timeout: 2000 })) {
        await confirmButton.click();
      }
    }

    await page.waitForTimeout(2000);
    console.log('✓ Quick tryout simulation complete');
  });
});
