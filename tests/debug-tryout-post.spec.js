const { test, expect } = require('@playwright/test');

const BASE = 'http://localhost/permen';

test('Debug: Tryout page with POST to create session', async ({ context, page }) => {
  // Login
  await page.goto(`${BASE}/pages/login.php`);
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
  
  const csrfInput = page.locator('input[name="csrf_token"]');
  const csrfToken = await csrfInput.count() > 0 ? await csrfInput.inputValue() : '';
  
  await page.fill('input[name="no_hp"]', '081987654321');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  
  await page.waitForURL(/user_dashboard\.php|admin_dashboard\.php/, { timeout: 10000 });
  
  console.log('Login successful, URL:', page.url());
  
  // Try POST to tryout page to create new session
  const response = await page.goto(`${BASE}/pages/tryout.php`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'strict_mode=0',
  });
  
  console.log('POST response status:', response.status());
  console.log('POST response URL:', page.url());
  
  // Wait for redirect
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
  
  const currentUrl = page.url();
  console.log('Final URL after POST:', currentUrl);
  
  // Check for session ID
  const sessionIdMatch = currentUrl.match(/session_id=(\d+)/);
  const sessionId = sessionIdMatch ? sessionIdMatch[1] : null;
  console.log('Session ID from URL:', sessionId);
  
  // Check page content
  const bodyText = await page.textContent('body');
  console.log('Page body length:', bodyText.length);
  
  if (bodyText.length > 0) {
    console.log('Page has content!');
    console.log('First 500 chars:', bodyText.substring(0, 500));
  }
});
