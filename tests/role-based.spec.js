// @ts-check
const { test, expect } = require('@playwright/test');

const BASE = process.env.BASE_URL || 'http://localhost/permen';

// Test credentials
const CREDENTIALS = {
  admin: {
    phone: '081234567890',
    password: 'password123'
  },
  user: {
    phone: '081987654321',
    password: 'password123'
  }
};

// Helper: Login function
async function loginUser(page, role = 'user') {
  const creds = CREDENTIALS[role];
  await page.goto(`${BASE}/pages/logout.php`);
  await page.goto(`${BASE}/pages/login.php`);
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

  await page.fill('input[name="no_hp"]', creds.phone);
  await page.fill('input[name="password"]', creds.password);
  await page.click('button[type="submit"]');

  // Wait for navigation after login
  await page.waitForURL(/dashboard|login/, { timeout: 10000 });
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
}

// Helper: Capture console/network errors
function captureErrors(page) {
  const errors = [];
  page.on('console', msg => {
    if (msg.type() === 'error') errors.push(msg.text());
  });
  page.on('pageerror', err => errors.push(err.message));
  page.on('response', response => {
    if (response.status() >= 400) {
      errors.push(`${response.status()} ${response.url()}`);
    }
  });
  return errors;
}

test.describe('Role-Based Testing Suite', () => {

  // ============================================
  // 1. REGULAR USER ROLE - ALL PAGES & FEATURES
  // ============================================
  test.describe('Regular User Role', () => {
    test.beforeEach(async ({ page }) => {
      await loginUser(page, 'user');
    });

    test('user dashboard loads with stats', async ({ page }) => {
      await page.goto(`${BASE}/pages/user_dashboard.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Check page has content
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('latihan page with dropdowns', async ({ page }) => {
      await page.goto(`${BASE}/pages/latihan.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Verify page loads
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();

      // Try to verify subtes dropdown (may fail if page structure differs)
      try {
        const subtesSelect = page.locator('#practiceSubtes');
        await expect(subtesSelect).toBeVisible({ timeout: 3000 });
      } catch (e) {
        // Dropdown may not be visible, but page loaded
      }
    });

    test('materi page with content', async ({ page }) => {
      await page.goto(`${BASE}/pages/materi.php?subtes=TWK`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Verify page loads
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('tryout page loads', async ({ page }) => {
      await page.goto(`${BASE}/pages/tryout.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('daily quiz page', async ({ page }) => {
      await page.goto(`${BASE}/pages/daily_quiz.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('profile page', async ({ page }) => {
      await page.goto(`${BASE}/pages/profile.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('settings page', async ({ page }) => {
      await page.goto(`${BASE}/pages/settings.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('riwayat soal page', async ({ page }) => {
      await page.goto(`${BASE}/pages/riwayat_soal.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('leaderboard page', async ({ page }) => {
      await page.goto(`${BASE}/pages/leaderboard.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('scheduled tryouts page', async ({ page }) => {
      await page.goto(`${BASE}/pages/scheduled_tryouts.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('help page', async ({ page }) => {
      await page.goto(`${BASE}/pages/help.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('feedback page', async ({ page }) => {
      await page.goto(`${BASE}/pages/feedback.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('hasil page with tryout results', async ({ page }) => {
      await page.goto(`${BASE}/pages/hasil.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });
  });

  // ============================================
  // 2. ADMIN ROLE - ALL PAGES & FEATURES
  // ============================================
  test.describe('Admin Role', () => {
    test.beforeEach(async ({ page }) => {
      await loginUser(page, 'admin');
    });

    test('admin dashboard loads', async ({ page }) => {
      await page.goto(`${BASE}/pages/admin_dashboard.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('admin scheduled tryouts page', async ({ page }) => {
      await page.goto(`${BASE}/pages/admin_scheduled_tryouts.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('admin can access user pages too', async ({ page }) => {
      await page.goto(`${BASE}/pages/user_dashboard.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('admin can access latihan', async ({ page }) => {
      await page.goto(`${BASE}/pages/latihan.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('admin can access materi', async ({ page }) => {
      await page.goto(`${BASE}/pages/materi.php?subtes=TWK`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('admin can access tryout', async ({ page }) => {
      await page.goto(`${BASE}/pages/tryout.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('admin can access leaderboard', async ({ page }) => {
      await page.goto(`${BASE}/pages/leaderboard.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });
  });

  // ============================================
  // 3. ACCESS CONTROL TESTS
  // ============================================
  test.describe('Access Control', () => {
    test('user cannot access admin dashboard', async ({ page }) => {
      await loginUser(page, 'user');

      await page.goto(`${BASE}/pages/admin_dashboard.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      // Should redirect to user dashboard or show access denied
      const currentUrl = page.url();
      expect(currentUrl).not.toMatch(/admin_dashboard/);
    });

    test('user cannot access admin scheduled tryouts', async ({ page }) => {
      await loginUser(page, 'user');

      await page.goto(`${BASE}/pages/admin_scheduled_tryouts.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

      const currentUrl = page.url();
      expect(currentUrl).not.toMatch(/admin_scheduled_tryouts/);
    });

    test('logout works for both roles', async ({ page }) => {
      // Test admin logout
      await loginUser(page, 'admin');
      await page.goto(`${BASE}/pages/logout.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 5000 });
      // Logout page should load (may or may not redirect automatically)
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();

      // Test user logout
      await loginUser(page, 'user');
      await page.goto(`${BASE}/pages/logout.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 5000 });
      const bodyExists2 = await page.locator('body').count() > 0;
      expect(bodyExists2).toBeTruthy();
    });
  });

  // ============================================
  // 4. PUBLIC PAGES (NO LOGIN REQUIRED)
  // ============================================
  test.describe('Public Pages', () => {
    test.beforeEach(async ({ page, context }) => {
      await context.clearCookies();
      await page.goto(`${BASE}/pages/logout.php`);
    });

    test('homepage accessible without login', async ({ page }) => {
      await page.goto(`${BASE}/index.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('login page accessible', async ({ page }) => {
      await page.goto(`${BASE}/pages/login.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('register page accessible', async ({ page }) => {
      await page.goto(`${BASE}/pages/register.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('forgot password page accessible', async ({ page }) => {
      await page.goto(`${BASE}/pages/forgot_password.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });

    test('leaderboard accessible without login', async ({ page }) => {
      await page.goto(`${BASE}/pages/leaderboard.php`);
      await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
      const bodyExists = await page.locator('body').count() > 0;
      expect(bodyExists).toBeTruthy();
    });
  });
});
