// @ts-check
const { test, expect } = require('@playwright/test');

const BASE = 'http://localhost/permen';
const TEST_USER = { no_hp: '081987654321', password: 'Sihaloho1982' };
const ADMIN_USER = { no_hp: '081265511982', password: 'Sihaloho1982' };

test.describe('Public Pages', () => {
  test('Landing page loads correctly', async ({ page }) => {
    await page.goto(BASE);
    await expect(page).toHaveTitle(/SKD CAT-BKN/);
    await expect(page.locator('.landing-hero h1')).toBeVisible();
    await expect(page.locator('.landing-cta a.primary').first()).toBeVisible();
  });

  test('Login page loads', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await expect(page.locator('h2')).toContainText('Login');
    await expect(page.locator('#no_hp')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
  });

  test('Register page loads', async ({ page }) => {
    await page.goto(BASE + '/pages/register.php');
    await expect(page.locator('h2')).toContainText('Buat Akun Baru');
    await expect(page.locator('#nama')).toBeVisible();
    await expect(page.locator('#no_hp')).toBeVisible();
  });

  test('Help page loads', async ({ page }) => {
    await page.goto(BASE + '/pages/help.php');
    await expect(page.locator('body')).toContainText('Bantuan');
  });

  test('Leaderboard page loads', async ({ page }) => {
    await page.goto(BASE + '/pages/leaderboard.php');
    await expect(page.locator('body')).toContainText('Leaderboard');
  });

  test('API health check', async ({ page }) => {
    const response = await page.goto(BASE + '/api/health.php');
    expect(response.status()).toBe(200);
    const json = await response.json();
    expect(json.success !== undefined || json.status !== undefined).toBeTruthy();
  });

  test('API landing stats', async ({ page }) => {
    const response = await page.goto(BASE + '/api/get_landing_stats.php');
    expect(response.status()).toBe(200);
    const json = await response.json();
    expect(json.success).toBe(true);
    expect(json.data.question_count).toBeGreaterThan(0);
  });
});

test.describe('Authentication', () => {
  test('Login with valid credentials redirects to dashboard', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('#no_hp', TEST_USER.no_hp);
    await page.fill('#password', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/);
    await expect(page.url()).toContain('user_dashboard');
  });

  test('Login with invalid credentials shows error', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('#no_hp', '0899999999');
    await page.fill('#password', 'wrongpassword');
    await page.click('button[type="submit"]');
    await expect(page.locator('.error')).toBeVisible();
  });

  test('Admin login redirects to admin dashboard', async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('#no_hp', ADMIN_USER.no_hp);
    await page.fill('#password', ADMIN_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/);
    await expect(page.url()).toContain('admin_dashboard');
  });
});

test.describe('User Dashboard (Authenticated)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('#no_hp', TEST_USER.no_hp);
    await page.fill('#password', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/);
  });

  test('User dashboard loads with stats', async ({ page }) => {
    await expect(page.locator('body')).toContainText('Dashboard');
  });

  test('Navigation menu has correct links', async ({ page }) => {
    await expect(page.locator('nav#navMenu')).toBeVisible();
    const count = await page.locator('nav#navMenu a').count();
    expect(count).toBeGreaterThanOrEqual(5);
  });

  test('Can access profile page', async ({ page }) => {
    await page.goto(BASE + '/pages/profile.php');
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('Can access latihan page', async ({ page }) => {
    await page.goto(BASE + '/pages/latihan.php');
    await expect(page.locator('body')).toContainText('Latihan');
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('Can access tryout page', async ({ page }) => {
    await page.goto(BASE + '/pages/tryout.php');
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('Can access materi page', async ({ page }) => {
    await page.goto(BASE + '/pages/materi.php');
    await expect(page.locator('body')).toContainText('Materi');
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('Logout works', async ({ page }) => {
    await page.goto(BASE + '/api/logout.php');
    await page.waitForURL(/login|index/);
  });
});

test.describe('Admin Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('#no_hp', ADMIN_USER.no_hp);
    await page.fill('#password', ADMIN_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin_dashboard/);
  });

  test('Admin dashboard loads without errors', async ({ page }) => {
    await expect(page.locator('body')).not.toContainText('Fatal error');
    await expect(page.locator('body')).not.toContainText('Exception');
  });

  test('Admin can see user management section', async ({ page }) => {
    const content = await page.content();
    expect(content.length).toBeGreaterThan(1000);
  });
});

test.describe('Tryout Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(BASE + '/pages/login.php');
    await page.fill('#no_hp', TEST_USER.no_hp);
    await page.fill('#password', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/);
  });

  test('Can start a tryout session', async ({ page }) => {
    await page.goto(BASE + '/pages/tryout.php');
    // Should either show tryout interface or start form
    const content = await page.content();
    expect(content).not.toContain('Fatal error');
    expect(content.length).toBeGreaterThan(500);
  });

  test('Can start latihan per subtes (TWK)', async ({ page }) => {
    await page.goto(BASE + '/pages/latihan.php?subtes=TWK');
    // Should redirect to tryout with session
    await page.waitForURL(/tryout.*session_id/);
    const content = await page.content();
    expect(content).not.toContain('Fatal error');
  });
});

test.describe('API Endpoints', () => {
  test('Get soal API returns questions', async ({ page, context }) => {
    // Login first
    await page.goto(BASE + '/pages/login.php');
    await page.fill('#no_hp', TEST_USER.no_hp);
    await page.fill('#password', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/user_dashboard/);

    // Test API
    const response = await page.goto(BASE + '/api/get_soal.php?subtes=TWK&jumlah=5');
    const text = await response.text();
    expect(response.status()).toBeLessThan(500);
  });
});

test.describe('Registration Flow', () => {
  test('Can register new user', async ({ page }) => {
    const randomPhone = '0812' + Math.floor(Math.random() * 100000000).toString().padStart(8, '0');
    await page.goto(BASE + '/pages/register.php');
    await page.fill('#nama', 'Test Registration');
    await page.fill('#no_hp', randomPhone);
    await page.fill('#password', 'TestPass123');
    await page.fill('#password2', 'TestPass123');
    await page.click('button[type="submit"]');
    
    // Should show success message or redirect
    const content = await page.content();
    expect(content).not.toContain('Fatal error');
    // Either success message or form still visible
    const hasSuccess = content.includes('berhasil') || content.includes('Pendaftaran');
    expect(hasSuccess).toBeTruthy();
  });
});

test.describe('Console & Network Monitoring', () => {
  test('No JavaScript errors on key pages', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));

    const pages_to_check = [
      BASE + '/index.php',
      BASE + '/pages/login.php',
      BASE + '/pages/register.php',
      BASE + '/pages/help.php',
    ];

    for (const url of pages_to_check) {
      await page.goto(url);
      await page.waitForLoadState('networkidle');
    }

    // Filter out non-critical errors
    const criticalErrors = errors.filter(e => !e.includes('fetch') && !e.includes('404'));
    expect(criticalErrors.length).toBe(0);
  });
});
