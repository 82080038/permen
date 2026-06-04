const { test, expect } = require('@playwright/test');

/**
 * Visual Regression Testing - SKD CAT-BKN
 * Tests for visual consistency across pages
 * Requires Playwright to be configured with screenshot options
 * Note: First run requires --update-snapshots flag to create baselines
 */

test.describe.skip('Visual Regression — Page Layouts', () => {
  test.beforeEach(async ({ page }) => {
    // Set viewport size for consistent screenshots
    await page.setViewportSize({ width: 1280, height: 720 });
  });

  test('homepage visual consistency', async ({ page }) => {
    await page.goto('http://localhost/permen/index.php');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot('homepage.png', {
      maxDiffPixels: 100,
      threshold: 0.2
    });
  });

  test('login page visual consistency', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/login.php');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot('login-page.png', {
      maxDiffPixels: 100,
      threshold: 0.2
    });
  });

  test('user dashboard visual consistency', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/login.php?quick=budi');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot('user-dashboard.png', {
      maxDiffPixels: 100,
      threshold: 0.2
    });
  });

  test('materi page visual consistency', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/materi.php?subtes=TWK');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot('materi-twk.png', {
      maxDiffPixels: 100,
      threshold: 0.2
    });
  });

  test('latihan page visual consistency', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/login.php?quick=budi');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    await page.goto('http://localhost/permen/pages/latihan.php');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot('latihan-page.png', {
      maxDiffPixels: 100,
      threshold: 0.2
    });
  });
});

test.describe.skip('Visual Regression — Responsive Design', () => {
  test('mobile view - homepage', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('http://localhost/permen/index.php');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot('homepage-mobile.png', {
      maxDiffPixels: 100,
      threshold: 0.2
    });
  });

  test('tablet view - homepage', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.goto('http://localhost/permen/index.php');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot('homepage-tablet.png', {
      maxDiffPixels: 100,
      threshold: 0.2
    });
  });
});

test.describe.skip('Visual Regression — Dark Mode', () => {
  test('dark mode toggle on tryout page', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/login.php?quick=budi');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    await page.goto('http://localhost/permen/pages/tryout.php');
    await page.waitForLoadState('networkidle');
    
    // Toggle dark mode
    await page.evaluate(() => {
      document.documentElement.setAttribute('data-theme', 'dark');
    });
    
    await expect(page).toHaveScreenshot('tryout-dark-mode.png', {
      maxDiffPixels: 100,
      threshold: 0.2
    });
  });
});

test.describe.skip('Visual Regression — Component States', () => {
  test('button hover states', async ({ page }) => {
    await page.goto('http://localhost/permen/index.php');
    await page.waitForLoadState('networkidle');
    
    const button = page.locator('a[href="pages/login.php"]').first();
    await button.hover();
    await expect(page).toHaveScreenshot('button-hover.png', {
      maxDiffPixels: 50,
      threshold: 0.1
    });
  });

  test('form focus states', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/login.php');
    await page.waitForLoadState('networkidle');
    
    const emailInput = page.locator('input[name="email"]');
    await emailInput.focus();
    await expect(page).toHaveScreenshot('input-focus.png', {
      maxDiffPixels: 50,
      threshold: 0.1
    });
  });
});
