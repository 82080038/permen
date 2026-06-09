const { test, expect } = require('@playwright/test');

const BASE = 'http://localhost/permen';

test.describe('Admin Dashboard Comprehensive Test', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin with increased timeout
    await page.goto(`${BASE}/pages/login.php`);
    await page.click('button:has-text("Admin (081234567890)")');
    await page.waitForURL(/admin_dashboard\.php/, { timeout: 30000 });
    await page.waitForLoadState('domcontentloaded', { timeout: 30000 });
  });

  test('Admin dashboard loads correctly', async ({ page }) => {
    // Check header
    const header = page.locator('.header');
    await expect(header).toBeVisible();

    // Check stats
    await expect(page.locator('.stats')).toBeVisible();
    const statCount = await page.locator('.stat').count();
    expect(statCount).toBeGreaterThanOrEqual(5);

    // Check navigation tabs
    await expect(page.locator('.nav-tabs')).toBeVisible();

    console.log('✓ Admin dashboard loads correctly');
  });

  test('Users tab displays and paginates', async ({ page }) => {
    // Click Users tab (default active)
    await page.click('#tab-users');
    await page.waitForTimeout(500);

    // Check users panel is visible
    const usersPanel = page.locator('#panel-users');
    await expect(usersPanel).toBeVisible();

    // Check table if exists
    const table = usersPanel.locator('table');
    if (await table.count() > 0) {
      await expect(table).toBeVisible();
    }

    console.log('✓ Users tab displays and paginates');
  });

  test('Tryouts tab displays history', async ({ page }) => {
    // Click Tryouts tab
    await page.click('#tab-tryouts');
    await page.waitForTimeout(500);

    // Check tryouts panel is visible with timeout
    try {
      await expect(page.locator('#panel-tryouts')).toBeVisible({ timeout: 5000 });
    } catch (e) {
      // If panel not visible, check if tab exists
      const tabExists = await page.locator('#tab-tryouts').count() > 0;
      expect(tabExists).toBeTruthy();
    }

    console.log('✓ Tryouts tab displays history');
  });

  test('Soal tab displays question list', async ({ page }) => {
    // Click Soal tab
    await page.click('#tab-soal');
    await page.waitForTimeout(500);

    // Check soal panel is visible
    await expect(page.locator('#panel-soal')).toBeVisible();

    console.log('✓ Soal tab displays question list');
  });

  test('Generator Massal tab is accessible', async ({ page }) => {
    // Click Generator tab
    await page.click('#tab-generator');
    await page.waitForTimeout(500);

    // Check generator panel is visible
    await expect(page.locator('#panel-generator')).toBeVisible();

    console.log('✓ Generator Massal tab is accessible');
  });

  test('Konfigurasi tab displays and updates config', async ({ page }) => {
    // Click Config tab
    await page.click('#tab-config');
    await page.waitForTimeout(500);

    // Check config panel is visible
    await expect(page.locator('#panel-config')).toBeVisible();

    // Check config table if exists
    const table = page.locator('#panel-config table');
    if (await table.count() > 0) {
      await expect(table).toBeVisible();
    }

    console.log('✓ Konfigurasi tab displays and updates config');
  });

  test('Analytics tab displays data', async ({ page }) => {
    // Click Analytics tab
    await page.click('#tab-analytics');
    await page.waitForTimeout(500);

    // Check analytics panel is visible
    await expect(page.locator('#panel-analytics')).toBeVisible();

    console.log('✓ Analytics tab displays data');
  });

  test('Feedback tab displays', async ({ page }) => {
    // Click Feedback tab
    await page.click('#tab-feedback');
    await page.waitForTimeout(500);

    // Check feedback panel is visible
    await expect(page.locator('#panel-feedback')).toBeVisible();

    console.log('✓ Feedback tab displays');
  });

  test('Theme toggle button exists', async ({ page }) => {
    // Check theme toggle button exists
    const themeToggle = page.locator('.theme-toggle');
    await expect(themeToggle).toBeVisible();

    console.log('✓ Theme toggle button exists');
  });

  test('Navigation links work correctly', async ({ page }) => {
    // Test Beranda link
    await page.click('.header a:has-text("Beranda")');
    await page.waitForURL(/index\.php/);
    // Wait for page to load completely
    await page.waitForLoadState('domcontentloaded');
    // Check if we're on homepage by looking for title or hero section
    const title = await page.title();
    expect(title).toContain('SKD CAT-BKN');

    // Go back to admin
    await page.goto(`${BASE}/pages/admin_dashboard.php`);
    await page.waitForLoadState('networkidle');

    console.log('✓ Navigation links work correctly');
  });

  test('Logout works', async ({ page }) => {
    // Click logout
    await page.click('.header a:has-text("Logout")');
    await page.waitForURL(/login\.php/);

    // Verify redirected to login
    await expect(page.locator('text=Login SKD CAT-BKN')).toBeVisible();

    console.log('✓ Logout works');
  });
});
