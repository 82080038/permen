const { test, expect } = require('@playwright/test');
const BASE = 'http://localhost/permen';

const PAGES_AUTH = [
  { url: `${BASE}/pages/user_dashboard.php`, name: 'Dashboard' },
  { url: `${BASE}/pages/tryout.php`,          name: 'Tryout' },
  { url: `${BASE}/pages/latihan.php`,         name: 'Latihan' },
  { url: `${BASE}/pages/leaderboard.php`,     name: 'Leaderboard' },
  { url: `${BASE}/pages/feedback.php`,        name: 'Feedback' },
  { url: `${BASE}/pages/profile.php`,         name: 'Profile' },
  { url: `${BASE}/pages/settings.php`,        name: 'Settings' },
  { url: `${BASE}/pages/daily_quiz.php`,      name: 'Daily Quiz' },
  { url: `${BASE}/pages/materi.php`,          name: 'Materi' },
];

const PAGES_PUBLIC = [
  { url: `${BASE}/index.php`,              name: 'Landing' },
  { url: `${BASE}/pages/login.php`,        name: 'Login' },
  { url: `${BASE}/pages/register.php`,     name: 'Register' },
  { url: `${BASE}/pages/leaderboard.php`,  name: 'Leaderboard (publik)' },
];

async function checkBootstrap(page, url, name) {
  await page.goto(url);
  await page.waitForLoadState('domcontentloaded');

  const cssLoaded = await page.evaluate(() =>
    Array.from(document.querySelectorAll('link[rel=stylesheet]'))
      .some(l => l.href.includes('bootstrap.min.css'))
  );
  const iconsLoaded = await page.evaluate(() =>
    Array.from(document.querySelectorAll('link[rel=stylesheet]'))
      .some(l => l.href.includes('bootstrap-icons'))
  );
  const jsLoaded = await page.evaluate(() => typeof window.bootstrap !== 'undefined');

  const ok = cssLoaded && iconsLoaded && jsLoaded;
  console.log(`[${name}] CSS:${cssLoaded?'✅':'❌'} Icons:${iconsLoaded?'✅':'❌'} JS:${jsLoaded?'✅':'❌'} → ${ok?'✅ OK':'❌ FAIL'}`);
  return { name, cssLoaded, iconsLoaded, jsLoaded };
}

test('Bootstrap tersedia di halaman publik', async ({ page }) => {
  const results = [];
  for (const p of PAGES_PUBLIC) {
    results.push(await checkBootstrap(page, p.url, p.name));
  }
  const failed = results.filter(r => !r.cssLoaded || !r.iconsLoaded);
  expect(failed, `Halaman tanpa Bootstrap CSS/Icons: ${failed.map(r=>r.name).join(', ')}`).toHaveLength(0);
});

test('Bootstrap tersedia di halaman authenticated', async ({ page }) => {
  // Login dulu
  await page.goto(`${BASE}/pages/login.php`);
  await page.fill('#no_hp', '081300001122');
  await page.fill('#password', 'Budi1234');
  await page.click('button[type="submit"]');
  await page.waitForURL(/user_dashboard/, { timeout: 15000 });

  const results = [];
  for (const p of PAGES_AUTH) {
    results.push(await checkBootstrap(page, p.url, p.name));
  }
  const failed = results.filter(r => !r.cssLoaded || !r.iconsLoaded);
  expect(failed, `Halaman tanpa Bootstrap CSS/Icons: ${failed.map(r=>r.name).join(', ')}`).toHaveLength(0);
});
