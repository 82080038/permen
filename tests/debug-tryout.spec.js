const { test, expect } = require('@playwright/test');

const BASE = 'http://localhost/permen';

test('Debug: Check tryout page session creation and question loading', async ({ context, page }) => {
  // Login
  await page.goto(`${BASE}/pages/login.php`);
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

  const csrfInput = page.locator('input[name="csrf_token"]');
  const csrfToken = await csrfInput.count() > 0 ? await csrfInput.inputValue() : '';

  await page.fill('input[name="no_hp"]', '081987654321');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');

  // Wait for redirect to dashboard
  await page.waitForURL(/user_dashboard\.php|admin_dashboard\.php/, { timeout: 10000 });

  console.log('Login successful, URL:', page.url());

  // Check cookies
  const cookies = await context.cookies();
  console.log('Cookies after login:', cookies.map(c => `${c.name}=${c.value}`));

  // Go to tryout page
  const response = await page.goto(`${BASE}/pages/tryout.php`, { waitUntil: 'domcontentloaded' });

  console.log('Tryout page URL:', page.url());
  console.log('Tryout page response status:', response.status());
  console.log('Tryout page response headers:', response.headers());

  // Check if there was a redirect
  const redirects = response.request().redirectedFrom();
  console.log('Was redirected:', redirects !== null);
  if (redirects) {
    console.log('Redirected from:', redirects.url());
  }

  // Check page content
  const bodyText = await page.textContent('body');
  console.log('Page body length:', bodyText.length);

  // Check for session ID in URL
  const currentUrl = page.url();
  const sessionIdMatch = currentUrl.match(/session_id=(\d+)/);
  const sessionId = sessionIdMatch ? sessionIdMatch[1] : null;
  console.log('Session ID from URL:', sessionId);

  // Check for loading indicator
  const loadingIndicator = page.locator('#loadingIndicator, [class*="loading"], [id*="loading"]');
  const hasLoading = await loadingIndicator.count() > 0;
  console.log('Has loading indicator:', hasLoading);

  // Check for question container
  const questionContainer = page.locator('#soalContainer, [id*="soal"], [class*="soal"]');
  const hasQuestionContainer = await questionContainer.count() > 0;
  console.log('Has question container:', hasQuestionContainer);

  // Wait for JavaScript to execute
  await page.waitForTimeout(5000);

  // Check if questions are loaded after waiting
  const questionElement = page.locator('.soal, .question, [class*="soal"], [class*="question"]');
  const hasQuestion = await questionElement.count() > 0;
  console.log('Has questions after 5 seconds:', hasQuestion);

  // Check for any error messages
  const errorElement = page.locator('.error, [class*="error"]');
  if (await errorElement.count() > 0) {
    const errorText = await errorElement.first().textContent();
    console.log('Error message:', errorText);
  }

  // Try to call API directly
  if (sessionId) {
    console.log('Testing API call directly...');
    try {
      const apiResponse = await page.goto(`${BASE}/api/get_soal.php?session_id=${sessionId}`);
      console.log('API response status:', apiResponse.status());
      const apiText = await apiResponse.text();
      console.log('API response length:', apiText.length);
      console.log('API response (first 500 chars):', apiText.substring(0, 500));

      // Try to parse as JSON
      try {
        const apiJson = JSON.parse(apiText);
        console.log('API JSON parsed successfully');
        console.log('API data structure:', Object.keys(apiJson));
        if (apiJson.data) {
          console.log('API data keys:', Object.keys(apiJson.data));
          if (apiJson.data.soal) {
            console.log('Number of questions in API response:', apiJson.data.soal.length);
          }
        }
      } catch (e) {
        console.log('Failed to parse API response as JSON:', e.message);
      }
    } catch (e) {
      console.log('API call error:', e.message);
    }
  }

  // Check console logs for JavaScript errors
  page.on('console', msg => {
    console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`);
  });

  // Wait a bit more and check again
  await page.waitForTimeout(3000);

  // Check for answer options specifically
  const answerOptions = await page.locator('.options label').count();
  console.log('Answer options found:', answerOptions);

  // Check for number grid buttons
  const gridButtons = await page.locator('.number-grid button').count();
  console.log('Number grid buttons:', gridButtons);

  // Check if sessionId is set in JavaScript
  const sessionIdJS = await page.evaluate(() => {
    return typeof sessionId !== 'undefined' ? sessionId : 'undefined';
  });
  console.log('sessionId in JavaScript:', sessionIdJS);

  // Manually trigger loadSoal if not already called
  if (sessionIdJS && sessionIdJS !== 'undefined') {
    console.log('Manually calling loadSoal()...');
    await page.evaluate(() => {
      if (typeof loadSoal === 'function') {
        loadSoal();
      }
    });
    await page.waitForTimeout(3000);

    // Check again after manual call
    const answerOptionsAfter = await page.locator('.options label').count();
    console.log('Answer options found after manual loadSoal:', answerOptionsAfter);

    // Check if soal array is populated
    const soalLength = await page.evaluate(() => {
      return typeof soal !== 'undefined' ? soal.length : 'undefined';
    });
    console.log('soal array length:', soalLength);

    // Manually call renderSoal(0)
    console.log('Manually calling renderSoal(0)...');
    await page.evaluate(() => {
      if (typeof renderSoal === 'function') {
        renderSoal(0);
      }
    });
    await page.waitForTimeout(2000);

    // Check again after manual renderSoal
    const answerOptionsAfterRender = await page.locator('.options label').count();
    console.log('Answer options found after manual renderSoal:', answerOptionsAfterRender);

    // Check the soalContainer HTML
    const soalContainerHtml = await page.locator('#soalContainer').innerHTML();
    console.log('soalContainer HTML length:', soalContainerHtml.length);
    console.log('soalContainer HTML (first 500 chars):', soalContainerHtml.substring(0, 500));

    // Check if the question has pilihan fields
    const firstQuestionFields = await page.evaluate(() => {
      if (typeof soal !== 'undefined' && soal.length > 0) {
        return {
          hasPilihanA: typeof soal[0].pilihan_a !== 'undefined',
          hasPilihanB: typeof soal[0].pilihan_b !== 'undefined',
          hasPilihanC: typeof soal[0].pilihan_c !== 'undefined',
          hasPilihanD: typeof soal[0].pilihan_d !== 'undefined',
          hasPilihanE: typeof soal[0].pilihan_e !== 'undefined',
          pilihanA: soal[0].pilihan_a,
          pilihanB: soal[0].pilihan_b,
        };
      }
      return null;
    });
    console.log('First question pilihan fields:', firstQuestionFields);
  }

  // Take screenshot for debugging
  await page.screenshot({ path: '/tmp/tryout-debug.png' });
  console.log('Screenshot saved to /tmp/tryout-debug.png');
});
