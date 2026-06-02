const { test, expect } = require('@playwright/test');

/**
 * Exploratory E2E Test — Navigasi semua halaman dan tangkap console/network errors
 * Jalankan: npx playwright test tests/exploratory.spec.js --headed
 */

test.describe('Exploratory — Semua Halaman & Console Check', () => {
  test.describe.configure({ mode: 'serial' }); // Run sequentially to avoid shared state issues

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
      // Skip expected 401s from API security endpoints
      const url = response.url();
      if (response.status() >= 400 && !url.includes('get_soal.php')) {
        testInfo.networkErrors.push(`[NETWORK] ${response.status()} ${url}`);
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

  test('Homepage — index.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/index.php');
    await expect(page).toHaveTitle(/SKD CAT-BKN/);
    await page.waitForTimeout(500);
    expect(testInfo.errors).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Login page — login.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/pages/login.php');
    await expect(page).toHaveTitle(/Login/);
    await page.waitForTimeout(500);
    expect(testInfo.errors).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Register page — register.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/pages/register.php');
    await expect(page).toHaveTitle(/Register/);
    await page.waitForTimeout(500);
    expect(testInfo.errors).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Materi TWK — materi.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/pages/materi.php?subtes=TWK');
    await expect(page).toHaveTitle(/Materi/);
    await page.waitForTimeout(500);
    expect(testInfo.errors).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Latihan — latihan.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/pages/latihan.php');
    await expect(page).toHaveTitle(/Latihan/);
    await page.waitForTimeout(500);
    expect(testInfo.errors).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Tryout — tryout.php', async ({ page }, testInfo) => {
    const response = await page.goto('http://localhost/permen/pages/tryout.php');
    await page.waitForTimeout(800);
    // Halaman utama harus 200; background AJAX mungkin 401 tanpa session (expected)
    expect(response.status()).toBe(200);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Hasil — hasil.php (redirect test)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/pages/hasil.php');
    await page.waitForTimeout(500);
    expect(page.url()).toContain('index.php');
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Admin dashboard — admin_dashboard.php (redirect when not logged in)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/pages/admin_dashboard.php');
    await page.waitForTimeout(500);
    expect(page.url()).toContain('login.php');
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Admin dashboard — admin_dashboard.php (with login)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/pages/login.php?quick=admin');
    await page.waitForURL(/admin_dashboard\.php/, { timeout: 15000 });
    await page.waitForTimeout(1000);
    expect(testInfo.errors).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('User dashboard — user_dashboard.php (with login)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/pages/login.php?quick=budi');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    await page.waitForTimeout(1000);
    expect(testInfo.errors).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('API — smart generator', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/api/generate_soal_smart.php?subtes=TIU&tipe=numerik&topik=Deret+Angka&jumlah=1');
    await page.waitForTimeout(500);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('API — get_soal (401 without auth)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/api/get_soal.php?session_id=1');
    await page.waitForTimeout(500);
    const body = await page.textContent('body');
    expect(body).toContain('Autentikasi');
  });

});
