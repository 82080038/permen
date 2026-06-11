const { test, expect } = require('@playwright/test');

test.describe('Diversified Tryout Simulation', () => {

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

  test('complete single tryout with 30 questions', async ({ page }) => {
    console.log('Starting single tryout simulation...');

    // Login
    console.log('Step 1: Login as user...');
    await page.goto('http://localhost/permen/pages/login.php');
    await page.click('button:has-text("User (081987654321)")');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 10000 });
    console.log('✓ Login successful');

    // Navigate to tryout
    console.log('Step 2: Navigate to tryout page...');
    await page.goto('http://localhost/permen/pages/tryout.php');
    await page.waitForTimeout(2000);

    // Start tryout
    console.log('Step 3: Start tryout...');
    let startButton = page.locator('button:has-text("Mulai"), button:has-text("Start")');
    if (await startButton.isVisible({ timeout: 3000 })) {
      await startButton.click();
      console.log('✓ Tryout started');
      await page.waitForTimeout(3000);
    } else {
      console.log('No start button found, checking for existing session');
      const finishButton = page.locator('button.finish');
      if (await finishButton.isVisible({ timeout: 2000 })) {
        console.log('Finishing existing session...');
        await finishButton.click();
        const confirmButton = page.locator('button:has-text("Ya")').first();
        if (await confirmButton.isVisible({ timeout: 2000 })) {
          await confirmButton.click();
        }
        await page.waitForTimeout(3000);
        await page.goto('http://localhost/permen/pages/tryout.php');
        await page.waitForTimeout(3000);
        // Try to start again
        startButton = page.locator('button:has-text("Mulai"), button:has-text("Start")');
        if (await startButton.isVisible({ timeout: 5000 })) {
          await startButton.click();
          await page.waitForTimeout(3000);
        }
      }
    }

    // Answer 30 questions with varied pattern
    console.log('Step 4: Answer 30 questions...');
    const options = ['A', 'B', 'C', 'D', 'E'];

    for (let i = 0; i < 30; i++) {
      await page.waitForTimeout(600);

      const currentUrl = page.url();
      if (!currentUrl.includes('tryout.php')) {
        console.log('Tryout finished early at question', i + 1);
        break;
      }

      // Varied answer pattern
      const optionIndex = (i * 3 + 2) % 5;
      const option = options[optionIndex];
      const optionButton = page.locator(`.options label input[type="radio"][value="${option}"]`);

      if (await optionButton.isVisible({ timeout: 3000 })) {
        await optionButton.click();
        if ((i + 1) % 10 === 0) {
          console.log(`  Answered ${i + 1} questions...`);
        }
        await page.waitForTimeout(400);
      } else {
        console.log(`  Q${i + 1}: No option found, trying next`);
        const nextButton = page.locator('#btnNext');
        if (await nextButton.isVisible({ timeout: 1000 })) {
          await nextButton.click();
        }
      }
    }

    // Finish tryout
    console.log('Step 5: Finish tryout...');
    const finishBtn = page.locator('button.finish');
    if (await finishBtn.isVisible({ timeout: 3000 })) {
      await finishBtn.click();

      const confirmButton = page.locator('button:has-text("Ya")').first();
      if (await confirmButton.isVisible({ timeout: 2000 })) {
        await confirmButton.click();
        console.log('✓ Tryout finished');
      }
    }

    await page.waitForTimeout(3000);

    // Check results
    const currentUrl = page.url();
    console.log('Current URL after finish:', currentUrl);

    // Check dashboard
    console.log('\n=== Checking Dashboard ===');
    await page.goto('http://localhost/permen/pages/user_dashboard.php');
    await page.waitForTimeout(3000);

    const pieChartSection = page.locator('h2:has-text("Distribusi Skor Subtes")');
    if (await pieChartSection.isVisible({ timeout: 5000 })) {
      console.log('✓ Pie chart section visible');

      const pieCanvas = page.locator('#pieChart');
      if (await pieCanvas.isVisible({ timeout: 3000 })) {
        console.log('✓ Pie chart canvas rendered with data');
      } else {
        console.log('⚠ Pie chart shows empty message');
      }
    } else {
      console.log('⚠ Pie chart section not visible (no completed tryouts)');
    }

    await page.screenshot({ path: 'test-results/single-tryout-dashboard.png', fullPage: true });
    console.log('✓ Screenshot saved');

    console.log('\n=== Simulation Complete ===');
  });
});
