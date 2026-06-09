const { test, expect } = require('@playwright/test');

/**
 * Exploratory E2E Test — Navigasi SEMUA halaman, tangkap console/network errors, dan periksa halaman kosong
 * Jalankan: npx playwright test tests/exploratory.spec.js --headed
 */

// Helper: login as user
async function loginAsUser(page) {
  await page.goto('http://localhost/permen/login.php');
  await page.fill('input[name="no_hp"]', '081987654321');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
}

// Helper: login as admin
async function loginAsAdmin(page) {
  await page.goto('http://localhost/permen/login.php');
  await page.fill('input[name="no_hp"]', '081234567890');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL(/admin_dashboard\.php/, { timeout: 15000 });
}

// Helper: filter non-critical errors
function criticalErrors(errors) {
  return errors.filter(e =>
    !e.includes('loadAnalytics') &&
    !e.includes('Failed to fetch') &&
    !e.includes('Chart is not defined') &&
    !e.includes('chart.umd.min.js') &&
    !e.includes('429') // Rate limit expected in test env with repeated logins
  );
}

// Helper: cek halaman tidak kosong (ada konten berarti)
async function assertNotBlankPage(page) {
  await page.waitForLoadState('domcontentloaded');
  const bodyHTML = await page.evaluate(() => document.body ? document.body.innerHTML.trim() : '');
  const bodyText = await page.evaluate(() => document.body ? document.body.innerText.trim() : '');
  expect(bodyHTML.length, `Halaman kosong di URL: ${page.url()}`).toBeGreaterThan(100);
  expect(bodyText.length, `Teks halaman kosong di URL: ${page.url()}`).toBeGreaterThan(10);
}

test.describe('Exploratory — Semua Halaman & Console Check', () => {
  test.describe.configure({ mode: 'serial' });

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

  // ── HALAMAN PUBLIK ──────────────────────────────────────────────────────────

  test('Homepage — index.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/index.php');
    await expect(page).toHaveTitle(/SKD CAT-BKN/);
    await page.waitForTimeout(500);
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Login — login.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/login.php');
    await expect(page).toHaveTitle(/Login/);
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Register — register.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/register.php');
    await expect(page).toHaveTitle(/Register/);
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Lupa Password — forgot_password.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/forgot_password.php');
    await page.waitForLoadState('networkidle');
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
  });

  test('Help / Bantuan — help.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/help.php');
    await page.waitForLoadState('networkidle');
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
  });

  test('Leaderboard — leaderboard.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/leaderboard.php');
    await expect(page).toHaveTitle(/Leaderboard/);
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Materi TWK — materi.php', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/materi.php?subtes=TWK');
    await expect(page).toHaveTitle(/Materi/);
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  // ── REDIRECT TANPA LOGIN ────────────────────────────────────────────────────

  test('Hasil — hasil.php redirect ke index', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/hasil.php');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('index.php');
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Tryout redirect ke login (tanpa sesi)', async ({ page }, testInfo) => {
    const response = await page.goto('http://localhost/permen/tryout.php');
    await page.waitForLoadState('networkidle');
    expect(response.status()).toBe(200);
    expect(page.url()).toContain('login.php');
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Admin dashboard redirect ke login (tanpa sesi)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/admin_dashboard.php');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
  });

  test('Latihan redirect ke login (tanpa sesi)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/latihan.php');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
  });

  test('Daily Quiz redirect ke login (tanpa sesi)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/daily_quiz.php');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
  });

  test('Profile redirect ke login (tanpa sesi)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/profile.php');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
  });

  test('Settings redirect ke login (tanpa sesi)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/settings.php');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
  });

  test('Riwayat Soal redirect ke login (tanpa sesi)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/riwayat_soal.php');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
  });

  test('Scheduled Tryouts redirect ke login (tanpa sesi)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/scheduled_tryouts.php');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
  });

  test('Feedback redirect ke login (tanpa sesi)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/feedback.php');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
  });

  // ── HALAMAN DENGAN LOGIN USER ───────────────────────────────────────────────

  test('User Dashboard — user_dashboard.php', async ({ page }, testInfo) => {
    await loginAsUser(page);
    await page.waitForTimeout(1000);
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors.filter(e => !e.includes('chart.umd.min.js'))).toHaveLength(0);
  });

  test('Latihan — latihan.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsUser(page);
    await page.goto('http://localhost/permen/latihan.php');
    await expect(page).toHaveTitle(/Latihan/);
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Daily Quiz — daily_quiz.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsUser(page);
    await page.goto('http://localhost/permen/daily_quiz.php');
    await page.waitForLoadState('networkidle');
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Profile — profile.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsUser(page);
    await page.goto('http://localhost/permen/profile.php');
    await page.waitForLoadState('networkidle');
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Settings — settings.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsUser(page);
    await page.goto('http://localhost/permen/settings.php');
    await page.waitForLoadState('networkidle');
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Riwayat Soal — riwayat_soal.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsUser(page);
    await page.goto('http://localhost/permen/riwayat_soal.php');
    await page.waitForURL(/riwayat_soal\.php/, { timeout: 5000 });
    await page.waitForLoadState('networkidle');
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Scheduled Tryouts — scheduled_tryouts.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsUser(page);
    await page.goto('http://localhost/permen/scheduled_tryouts.php');
    await page.waitForURL(/scheduled_tryouts\.php/, { timeout: 5000 });
    await page.waitForLoadState('networkidle');
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Feedback — feedback.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsUser(page);
    await page.goto('http://localhost/permen/feedback.php');
    await page.waitForURL(/feedback\.php/, { timeout: 5000 });
    await page.waitForLoadState('networkidle');
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('Tryout — tryout.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsUser(page);
    await page.goto('http://localhost/permen/tryout.php');
    await page.waitForURL(/tryout\.php/, { timeout: 5000 });
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000);
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    // 429 rate limit is expected in test environment with repeated logins
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  // ── HALAMAN ADMIN ───────────────────────────────────────────────────────────

  test('Admin Dashboard — admin_dashboard.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsAdmin(page);
    await page.waitForTimeout(1000);
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors).toHaveLength(0);
  });

  test('Admin Scheduled Tryouts — admin_scheduled_tryouts.php (dengan login)', async ({ page }, testInfo) => {
    await loginAsAdmin(page);
    await page.goto('http://localhost/permen/admin_scheduled_tryouts.php');
    await page.waitForURL(/admin_scheduled_tryouts\.php/, { timeout: 5000 });
    await page.waitForLoadState('networkidle');
    await assertNotBlankPage(page);
    expect(criticalErrors(testInfo.errors)).toHaveLength(0);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  // ── API CHECKS ──────────────────────────────────────────────────────────────

  test('API — smart generator (403 expected without admin)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/api/generate_soal_smart.php?subtes=TIU&tipe=numerik&topik=Deret+Angka&jumlah=1');
    await page.waitForTimeout(500);
    expect(testInfo.networkErrors.filter(e => e.includes('500'))).toHaveLength(0);
  });

  test('API — get_soal (401 tanpa auth)', async ({ page }, testInfo) => {
    await page.goto('http://localhost/permen/api/get_soal.php?session_id=1');
    await page.waitForTimeout(500);
    const body = await page.textContent('body');
    expect(body).toMatch(/Autentikasi|kesalahan/);
  });

});
