const { test, expect } = require('@playwright/test');

/**
 * Register Page Navigation Test
 * Test all navigation links on register.php
 * Jalankan: npx playwright test tests/register_navigation.spec.js --headed
 */

test.describe('Register Page Navigation Links', () => {
  test.beforeEach(async ({ page }, testInfo) => {
    testInfo.errors = [];
    testInfo.networkErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        testInfo.errors.push(`[CONSOLE] ${msg.text()}`);
      }
    });
    page.on('pageerror', error => {
      testInfo.errors.push(`[PAGEERROR] ${error.message}`);
    });
    page.on('response', response => {
      if (response.status() >= 400) {
        testInfo.networkErrors.push(`[NETWORK] ${response.status()} ${response.url()}`);
      }
    });
  });

  test.afterEach(async ({ }, testInfo) => {
    if (testInfo.errors.length > 0) {
      console.log(`=== ${testInfo.title} — Console/Page Errors ===`);
      testInfo.errors.forEach(e => console.log(e));
    }
    if (testInfo.networkErrors.length > 0) {
      console.log(`=== ${testInfo.title} — Network Errors ===`);
      testInfo.networkErrors.forEach(e => console.log(e));
    }
  });

  test('Register page loads correctly', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/register.php');
    await expect(page).toHaveTitle(/Register/);
    await page.waitForTimeout(500);
    expect(testInfo.errors).toHaveLength(0);
  });

  test('All navigation links on register page', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/register.php');
    await page.waitForLoadState('networkidle');
    
    // Get all navigation links
    const navLinks = await page.locator('nav[aria-label="Main navigation"] a').all();
    
    const expectedLinks = [
      { text: 'Beranda', href: 'index.php' },
      { text: 'Latihan', href: 'latihan.php' },
      { text: 'Try Out', href: 'tryout.php' },
      { text: 'Leaderboard', href: 'leaderboard.php' },
      { text: 'Bantuan', href: 'help.php' },
      { text: 'Login', href: 'login.php' },
      { text: 'Daftar', href: 'register.php' }
    ];

    console.log(`Found ${navLinks.length} navigation links`);
    
    for (let i = 0; i < navLinks.length; i++) {
      const link = navLinks[i];
      const text = await link.textContent();
      const href = await link.getAttribute('href');
      
      console.log(`Testing link ${i + 1}: "${text}" -> "${href}"`);
      
      // Check if link matches expected
      const expected = expectedLinks[i];
      if (expected) {
        expect(text.trim()).toBe(expected.text);
        expect(href).toBe(expected.href);
      }
      
      // Test if link resolves correctly (without clicking)
      const linkHref = await link.getAttribute('href');
      if (linkHref) {
        // Navigate to the link URL directly
        const fullUrl = `http://localhost/permen/${linkHref}`;
        const response = await page.goto(fullUrl);
        
        // Check if page loads (200 or redirect)
        const status = response ? response.status() : 0;
        console.log(`  Status: ${status}, URL: ${page.url()}`);
        
        // Allow 200 or 3xx redirects
        expect(status).toBeLessThan(400);
        
        // Go back to register page
        await page.goto('http://localhost/permen/register.php');
        await page.waitForLoadState('networkidle');
      }
    }
    
    // Only check for console errors, ignore 404s from failed resource loads
    const consoleErrors = testInfo.errors.filter(e => !e.includes('404'));
    expect(consoleErrors).toHaveLength(0);
  });

  test('Logged-in user redirected from register page', async ({ page }, testInfo) => {
    // Login first
    await page.goto('http://localhost/permen/login.php');
    await page.fill('input[name="no_hp"]', '081987654321');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    
    // Try to access register page
    await page.goto('http://localhost/permen/register.php');
    
    // Should be redirected to user_dashboard
    expect(page.url()).toContain('user_dashboard.php');
  });
});
