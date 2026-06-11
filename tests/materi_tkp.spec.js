const { test, expect } = require('@playwright/test');

test.describe('Materi TKP Page Testing', () => {

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

  test('load materi TKP page and verify content', async ({ page }) => {
    console.log('Testing materi TKP page loading...');

    // Navigate to materi TKP page
    await page.goto('http://localhost/permen/pages/materi.php?subtes=TKP');
    await page.waitForTimeout(2000);

    // Verify page title
    const title = await page.title();
    console.log('Page title:', title);
    expect(title).toContain('Materi TKP');

    // Verify TKP tab is active
    const tkpTab = page.locator('a[href="?subtes=TKP"]');
    const tkpTabClass = await tkpTab.getAttribute('class');
    expect(tkpTabClass).toContain('active');
    console.log('✓ TKP tab is active');

    // Verify materi cards are loaded
    const cards = page.locator('#materiContainer .card');
    const cardCount = await cards.count();
    console.log(`Found ${cardCount} materi cards`);
    expect(cardCount).toBeGreaterThan(0);

    // Verify search input exists
    const searchInput = page.locator('#searchMateri');
    expect(await searchInput.isVisible()).toBeTruthy();
    console.log('✓ Search input exists');

    // Test search functionality
    await searchInput.fill('profesionalisme');
    await page.waitForTimeout(500);
    const visibleCards = page.locator('#materiContainer .card[style*="block"]');
    const visibleCount = await visibleCards.count();
    console.log(`Search results: ${visibleCount} cards visible`);

    // Clear search
    await searchInput.fill('');
    await page.waitForTimeout(500);

    console.log('✓ Materi TKP page loaded successfully');
  });

  test('test uji pemahaman - generate TKP questions', async ({ page }) => {
    console.log('Testing Uji Pemahaman - Generate TKP questions...');

    // Login first (required for API - production security)
    await page.goto('http://localhost/permen/pages/login.php');
    await page.click('button:has-text("User (081987654321)")');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 10000 });
    console.log('✓ Logged in');

    // Navigate to materi TKP page
    await page.goto('http://localhost/permen/pages/materi.php?subtes=TKP');
    await page.waitForTimeout(2000);

    // Find and click the Uji Pemahaman section
    const ujiPemahaman = page.locator('.card-header:has-text("Uji Pemahaman")');
    await ujiPemahaman.click();
    await page.waitForTimeout(500);

    // Verify dropdown is populated with TKP topics
    const topikSelect = page.locator('#latihTopik');
    expect(await topikSelect.isVisible()).toBeTruthy();

    const options = await topikSelect.locator('option').allTextContents();
    console.log('Available topics:', options);
    expect(options).toContain('Profesionalisme');
    expect(options).toContain('Pelayanan Publik');

    // Select a topic
    await topikSelect.selectOption('Profesionalisme');

    // Select number of questions
    const jumlahSelect = page.locator('#latihJumlah');
    await jumlahSelect.selectOption('5');

    // Click generate button
    const generateBtn = page.locator('button:has-text("Generate Soal")');
    await generateBtn.click();

    // Wait for questions to load
    await page.waitForTimeout(3000);

    // Verify questions are generated
    const latihanContainer = page.locator('#latihanContainer');
    expect(await latihanContainer.isVisible()).toBeTruthy();

    // Check container content for debugging
    const containerText = await latihanContainer.textContent();
    console.log('Container content:', containerText.substring(0, 200));

    // Check if questions are displayed
    const questions = latihanContainer.locator('div:has-text("Soal")');
    const questionCount = await questions.count();
    console.log(`Generated ${questionCount} questions`);

    // If no questions, check for error message
    if (questionCount === 0) {
      const errorText = await latihanContainer.textContent();
      console.log('No questions generated. Container text:', errorText);
    }

    expect(questionCount).toBeGreaterThan(0);

    // Verify answer options are in A/B/C/D/E format
    const radioButtons = latihanContainer.locator('input[type="radio"]');
    const radioCount = await radioButtons.count();
    console.log(`Found ${radioCount} radio buttons`);
    expect(radioCount).toBeGreaterThan(0);

    // Check first question's answer format
    const firstRadio = radioButtons.first();
    const firstValue = await firstRadio.getAttribute('value');
    const firstKey = await firstRadio.getAttribute('data-key');
    console.log(`First question - value: ${firstValue}, key: ${firstKey}`);
    expect(['A', 'B', 'C', 'D', 'E']).toContain(firstValue);
    expect(['A', 'B', 'C', 'D', 'E']).toContain(firstKey);

    console.log('✓ TKP questions generated with correct A/B/C/D/E format');
  });

  test('test answer checking with new format', async ({ page }) => {
    console.log('Testing answer checking with new A/B/C/D/E format...');

    // Login first (required for API)
    await page.goto('http://localhost/permen/pages/login.php');
    await page.click('button:has-text("User (081987654321)")');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 10000 });
    console.log('✓ Logged in');

    // Navigate to materi TKP page
    await page.goto('http://localhost/permen/pages/materi.php?subtes=TKP');
    await page.waitForTimeout(2000);

    // Open Uji Pemahaman section
    const ujiPemahaman = page.locator('.card-header:has-text("Uji Pemahaman")');
    await ujiPemahaman.click();
    await page.waitForTimeout(500);

    // Generate questions
    await page.locator('#latihTopik').selectOption('Profesionalisme');
    await page.locator('#latihJumlah').selectOption('5');
    await page.locator('button:has-text("Generate Soal")').click();
    await page.waitForTimeout(3000);

    // Answer first question with option A
    const firstQuestionRadio = page.locator('input[name="soal_0"][value="A"]');
    if (await firstQuestionRadio.isVisible({ timeout: 2000 })) {
      await firstQuestionRadio.click();
      console.log('Answered first question with A');
    }

    // Answer second question with option B
    const secondQuestionRadio = page.locator('input[name="soal_1"][value="B"]');
    if (await secondQuestionRadio.isVisible({ timeout: 2000 })) {
      await secondQuestionRadio.click();
      console.log('Answered second question with B');
    }

    // Click Periksa Jawaban
    const periksaBtn = page.locator('button:has-text("Periksa Jawaban")');
    await periksaBtn.click();
    await page.waitForTimeout(1000);

    // Verify pembahasan sections are shown
    const pembahasanDivs = page.locator('[class*="pembahasan-"]');
    const pembahasanCount = await pembahasanDivs.count();
    console.log(`Pembahasan sections shown: ${pembahasanCount}`);
    expect(pembahasanCount).toBeGreaterThan(0);

    // Verify result message is shown
    const resultDiv = page.locator('#latihanContainer div:has-text("Hasil")');
    expect(await resultDiv.isVisible()).toBeTruthy();
    console.log('✓ Answer checking works with new format');
  });

  test('verify materi content files exist', async ({ page }) => {
    console.log('Verifying materi content files...');

    // Check if materi_tkp.php exists by trying to load the page
    await page.goto('http://localhost/permen/pages/materi.php?subtes=TKP');
    await page.waitForTimeout(2000);

    // Verify no error message about missing file
    const errorMessage = page.locator('text=/materi.*not found/i');
    expect(await errorMessage.isVisible({ timeout: 2000 })).toBeFalsy();

    console.log('✓ Materi TKP content file exists and loads correctly');
  });
});
