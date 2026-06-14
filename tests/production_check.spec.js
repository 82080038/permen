import { test, expect } from '@playwright/test';

const BASE_URL = 'http://bimbel.bereng.info';

// Collect all console errors and network failures
let consoleErrors = [];
let networkFailures = [];

test.beforeEach(async ({ page }) => {
  consoleErrors = [];
  networkFailures = [];
  
  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push({
        url: page.url(),
        type: msg.type(),
        text: msg.text()
      });
    }
  });
  
  page.on('pageerror', error => {
    consoleErrors.push({
      url: page.url(),
      type: 'pageerror',
      text: error.message
    });
  });
  
  page.on('response', response => {
    if (response.status() >= 400) {
      networkFailures.push({
        url: response.url(),
        status: response.status(),
        page: page.url()
      });
    }
  });
});

test.afterEach(async () => {
  if (consoleErrors.length > 0) {
    console.log('\n=== CONSOLE ERRORS ===');
    consoleErrors.forEach(e => console.log(`[${e.type}] ${e.url}: ${e.text}`));
  }
  if (networkFailures.length > 0) {
    console.log('\n=== NETWORK FAILURES ===');
    networkFailures.forEach(e => console.log(`[${e.status}] ${e.url} (page: ${e.page})`));
  }
});

test('1. Landing page', async ({ page }) => {
  await page.goto(`${BASE_URL}/`);
  await expect(page).toHaveTitle(/SKD/);
  
  // Check for broken images
  const brokenImages = await page.evaluate(() => {
    return Array.from(document.images)
      .filter(img => !img.complete || img.naturalWidth === 0)
      .map(img => img.src);
  });
  
  expect(brokenImages).toEqual([]);
});

test('2. Login page loads without errors', async ({ page }) => {
  await page.goto(`${BASE_URL}/pages/login.php`);
  await expect(page).toHaveTitle(/Login/);
  
  // Check form exists
  await expect(page.locator('input[name="no_hp"]')).toBeVisible();
  await expect(page.locator('input[name="password"]')).toBeVisible();
});

test('3. Login with test user', async ({ page }) => {
  await page.goto(`${BASE_URL}/pages/login.php`);
  
  await page.fill('input[name="no_hp"]', '081987654321');
  await page.fill('input[name="password"]', 'Sihaloho1982');
  
  // Get CSRF token value
  const csrfToken = await page.locator('input[name="csrf_token"]').inputValue();
  expect(csrfToken).toBeTruthy();
  
  await page.click('button[type="submit"]');
  
  // Should redirect to dashboard
  await page.waitForURL(/user_dashboard/);
  
  // Verify logged in
  await expect(page.locator('body')).toContainText(/dashboard|beranda/i);
});

test('4. User dashboard', async ({ page }) => {
  // Login first
  await page.goto(`${BASE_URL}/pages/login.php`);
  await page.fill('input[name="no_hp"]', '081987654321');
  await page.fill('input[name="password"]', 'Sihaloho1982');
  await page.click('button[type="submit"]');
  await page.waitForURL(/user_dashboard/);
  
  // Check dashboard elements
  await expect(page.locator('body')).toBeVisible();
  
  // Check for 404s on dashboard
  const page404 = networkFailures.filter(n => n.status === 404);
  expect(page404).toEqual([]);
});

test('5. Tryout page accessible', async ({ page }) => {
  // Login first
  await page.goto(`${BASE_URL}/pages/login.php`);
  await page.fill('input[name="no_hp"]', '081987654321');
  await page.fill('input[name="password"]', 'Sihaloho1982');
  await page.click('button[type="submit"]');
  await page.waitForURL(/user_dashboard/);
  
  await page.goto(`${BASE_URL}/pages/tryout.php`);
  await expect(page).toHaveTitle(/Try Out|Tryout/i);
});

test('6. Register page', async ({ page }) => {
  await page.goto(`${BASE_URL}/pages/register.php`);
  await expect(page).toHaveTitle(/Daftar|Register/i);
});

test('7. API endpoints', async ({ request }) => {
  const response = await request.get(`${BASE_URL}/api/health.php`);
  expect(response.status()).toBe(200);
  
  const statsResponse = await request.get(`${BASE_URL}/api/get_landing_stats.php`);
  expect(statsResponse.status()).toBe(200);
});

test('8. Logout works', async ({ page }) => {
  // Login
  await page.goto(`${BASE_URL}/pages/login.php`);
  await page.fill('input[name="no_hp"]', '081987654321');
  await page.fill('input[name="password"]', 'Sihaloho1982');
  await page.click('button[type="submit"]');
  await page.waitForURL(/user_dashboard/);
  
  // Logout
  await page.goto(`${BASE_URL}/api/logout.php`);
  
  // Check redirected or logged out
  const url = page.url();
  expect(url).toMatch(/login|index/);
});

test('9. No mixed content (HTTP on HTTPS page)', async ({ page }) => {
  await page.goto(`${BASE_URL}/`);
  
  const mixedContent = await page.evaluate(() => {
    const httpResources = Array.from(document.querySelectorAll('img, script, link, iframe'))
      .filter(el => {
        const src = el.src || el.href;
        return src && src.startsWith('http:') && window.location.protocol === 'https:';
      })
      .map(el => el.src || el.href);
    return httpResources;
  });
  
  expect(mixedContent).toEqual([]);
});

test('10. All navigation links work', async ({ page }) => {
  await page.goto(`${BASE_URL}/`);
  
  const links = await page.locator('a[href^="/"]').all();
  const brokenLinks = [];
  
  for (const link of links.slice(0, 20)) { // Test first 20 links
    const href = await link.getAttribute('href');
    if (href && !href.startsWith('#') && !href.startsWith('mailto:')) {
      const response = await page.request.get(`${BASE_URL}${href}`);
      if (response.status() >= 400) {
        brokenLinks.push({ url: href, status: response.status() });
      }
    }
  }
  
  expect(brokenLinks).toEqual([]);
});
