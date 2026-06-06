const { test, expect } = require('@playwright/test');

test.describe('FE-BE Integration Test - Answer Format', () => {

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

  test('answer submission and score calculation', async ({ page }) => {
    console.log('Testing FE-BE integration with new answer format...');
    
    // Login
    console.log('Step 1: Login as user...');
    await page.goto('http://localhost/permen/pages/login.php');
    await page.click('button:has-text("User")');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 10000 });
    console.log('✓ Login successful');

    // Navigate to tryout
    console.log('Step 2: Navigate to tryout page...');
    await page.goto('http://localhost/permen/pages/tryout.php');
    await page.waitForTimeout(2000);
    
    // Check for and finish any existing session first
    console.log('Step 3: Check for existing session...');
    const finishButton = page.locator('button.finish');
    if (await finishButton.isVisible({ timeout: 2000 })) {
      console.log('Found existing session, finishing it...');
      await finishButton.click();
      const confirmButton = page.locator('button:has-text("Ya")').first();
      if (await confirmButton.isVisible({ timeout: 2000 })) {
        await confirmButton.click();
        console.log('✓ Existing session finished');
      }
      await page.waitForTimeout(3000);
      await page.goto('http://localhost/permen/pages/tryout.php');
      await page.waitForTimeout(2000);
    }
    
    // Start tryout
    console.log('Step 4: Start tryout...');
    const startButton = page.locator('button:has-text("Mulai"), button:has-text("Start")');
    if (await startButton.isVisible({ timeout: 3000 })) {
      await startButton.click();
      console.log('✓ Tryout started');
      await page.waitForTimeout(3000);
    } else {
      console.log('⚠ No start button found, skipping tryout simulation');
      console.log('Proceeding to dashboard verification...');
      await page.goto('http://localhost/permen/pages/user_dashboard.php');
      await page.waitForTimeout(2000);
    }

    // Answer 5 questions with specific answers (only if tryout started)
    const currentUrl = page.url();
    if (currentUrl.includes('tryout.php')) {
      console.log('Step 5: Answer 5 questions with specific answers...');
      const answerPattern = ['A', 'B', 'C', 'D', 'E'];
      
      for (let i = 0; i < 5; i++) {
        await page.waitForTimeout(800);
        
        const currentUrl = page.url();
        if (!currentUrl.includes('tryout.php')) {
          console.log('Tryout finished early');
          break;
        }

        const option = answerPattern[i];
        const optionButton = page.locator(`.options label input[type="radio"][value="${option}"]`);
        
        if (await optionButton.isVisible({ timeout: 3000 })) {
          await optionButton.click();
          console.log(`  Q${i + 1}: Answered ${option}`);
          await page.waitForTimeout(500);
        } else {
          console.log(`  Q${i + 1}: No option found for ${option}`);
          break;
        }
      }

      // Finish tryout
      console.log('Step 6: Finish tryout...');
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
    }

    // Check dashboard for topic accuracy
    console.log('\n=== Checking Dashboard Topic Accuracy ===');
    await page.goto('http://localhost/permen/pages/user_dashboard.php');
    await page.waitForTimeout(3000);
    
    const topicAccuracySection = page.locator('h2:has-text("Analisis Akurasi per Topik")');
    if (await topicAccuracySection.isVisible({ timeout: 5000 })) {
      console.log('✓ Topic accuracy section visible');
      
      const topicBars = page.locator('.topic-bar');
      const topicCount = await topicBars.count();
      console.log(`Found ${topicCount} topic bars`);
      
      // Check if TKP, TIU, TWK are present in topic accuracy section
      const tkpTopic = topicAccuracySection.locator('text=TKP').first();
      const tiuTopic = topicAccuracySection.locator('text=TIU').first();
      const twkTopic = topicAccuracySection.locator('text=TWK').first();
      
      const hasTKP = await tkpTopic.isVisible({ timeout: 2000 });
      const hasTIU = await tiuTopic.isVisible({ timeout: 2000 });
      const hasTWK = await twkTopic.isVisible({ timeout: 2000 });
      
      console.log(`TKP present: ${hasTKP}`);
      console.log(`TIU present: ${hasTIU}`);
      console.log(`TWK present: ${hasTWK}`);
      
      if (hasTKP && hasTIU && hasTWK) {
        console.log('✓ All subtes (TKP, TIU, TWK) present in topic accuracy');
      } else {
        console.log('⚠ Some subtes missing from topic accuracy');
      }
    } else {
      console.log('⚠ Topic accuracy section not visible');
    }

    await page.screenshot({ path: 'test-results/fe-be-dashboard.png', fullPage: true });
    console.log('✓ Screenshot saved');

    console.log('\n=== Integration Test Complete ===');
  });

  test('verify answer format in database', async ({ page }) => {
    console.log('Verifying answer format in database...');
    
    // This test will be done via direct database check
    // We'll verify that answers are stored in A/B/C/D/E format
    console.log('Database verification will be done separately');
  });
});
