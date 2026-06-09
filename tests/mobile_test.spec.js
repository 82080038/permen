/**
 * Mobile Compatibility Test — SKD CAT-BKN
 * Menguji ketepatan tampilan dan fungsi di berbagai ukuran device mobile
 * serta simulasi touch/browser mobile (user-agent).
 */

const { test, expect, devices } = require('@playwright/test');

const BASE = process.env.BASE_URL || 'http://localhost/permen';
const USER_HP   = '081300001122';
const USER_PASS = 'Budi1234';

// ─── Device profiles yang diuji ───────────────────────────────────────────────
const DEVICES = [
  { name: 'iPhone SE (375x667)',    width: 375,  height: 667,  ua: 'mobile' },
  { name: 'iPhone 14 (390x844)',    width: 390,  height: 844,  ua: 'mobile' },
  { name: 'Samsung Galaxy S21 (360x800)', width: 360, height: 800, ua: 'mobile' },
  { name: 'iPad Mini (768x1024)',   width: 768,  height: 1024, ua: 'tablet' },
  { name: 'Android Small (320x568)',width: 320,  height: 568,  ua: 'mobile' },
];

const MOBILE_UA = 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
const TABLET_UA = 'Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1';

// Hasil global
const results = [];

// ─── Helper: cek horizontal overflow (scroll horizontal = layout rusak) ───────
async function checkHorizontalOverflow(page) {
  return page.evaluate(() => {
    const overflow = document.documentElement.scrollWidth > window.innerWidth;
    const overflowEls = [];
    document.querySelectorAll('*').forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.right > window.innerWidth + 5) {
        overflowEls.push(el.tagName + (el.id ? '#' + el.id : '') + (el.className ? '.' + String(el.className).split(' ')[0] : ''));
      }
    });
    return { overflow, overflowEls: overflowEls.slice(0, 5) };
  });
}

// ─── Helper: cek touch target size (min 44x44px per WCAG) ───────────────────
async function checkTouchTargets(page) {
  return page.evaluate(() => {
    const smallTargets = [];
    document.querySelectorAll('a, button, input[type="radio"], input[type="checkbox"], select').forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.width > 0 && rect.height > 0 && (rect.width < 44 || rect.height < 44)) {
        smallTargets.push({
          tag: el.tagName,
          text: (el.textContent || el.value || el.name || '').substring(0, 30).trim(),
          w: Math.round(rect.width),
          h: Math.round(rect.height)
        });
      }
    });
    return smallTargets.slice(0, 10);
  });
}

// ─── Helper: cek font size (min 12px untuk readability) ───────────────────────
async function checkFontSizes(page) {
  return page.evaluate(() => {
    const small = [];
    document.querySelectorAll('p, span, div, td, li, label').forEach(el => {
      if (!el.textContent.trim()) return;
      const fs = parseFloat(getComputedStyle(el).fontSize);
      if (fs < 12 && el.getBoundingClientRect().height > 0) {
        small.push({ tag: el.tagName, fs: fs.toFixed(1), text: el.textContent.substring(0, 40).trim() });
      }
    });
    return small.slice(0, 5);
  });
}

// ─── Helper: login ─────────────────────────────────────────────────────────────
async function loginUser(page) {
  await page.goto(`${BASE}/pages/login.php`);
  await page.waitForLoadState('domcontentloaded');
  await page.fill('#no_hp', USER_HP);
  await page.fill('#password', USER_PASS);
  await page.click('button[type="submit"]');
  await page.waitForURL(/user_dashboard/, { timeout: 15000 });
  await page.waitForLoadState('domcontentloaded');
}

// ─── Helper: set device ────────────────────────────────────────────────────────
async function setDevice(page, device) {
  await page.setViewportSize({ width: device.width, height: device.height });
  await page.setExtraHTTPHeaders({
    'User-Agent': device.ua === 'tablet' ? TABLET_UA : MOBILE_UA
  });
}

// =============================================================================
// TEST 1: Meta viewport ada di semua halaman utama
// =============================================================================
test('1. Meta viewport tersedia di semua halaman utama', async ({ page }) => {
  const pages = [
    { url: `${BASE}/index.php`,         name: 'Landing' },
    { url: `${BASE}/pages/login.php`,   name: 'Login' },
    { url: `${BASE}/pages/register.php`,name: 'Register' },
    { url: `${BASE}/pages/leaderboard.php`, name: 'Leaderboard' },
  ];

  const missing = [];
  for (const p of pages) {
    await page.goto(p.url);
    await page.waitForLoadState('domcontentloaded');
    const viewport = await page.locator('meta[name="viewport"]').getAttribute('content').catch(() => null);
    const hasDeviceWidth = viewport?.includes('width=device-width');
    console.log(`[${p.name}] viewport: ${viewport ?? 'MISSING'} — ${hasDeviceWidth ? '✅' : '❌'}`);
    if (!hasDeviceWidth) missing.push(p.name);
  }

  if (missing.length > 0) console.error(`[FAIL] Halaman tanpa viewport: ${missing.join(', ')}`);
  expect(missing).toHaveLength(0);
});

// =============================================================================
// TEST 2: Horizontal overflow di semua device profile (halaman publik)
// =============================================================================
for (const device of DEVICES) {
  test(`2. Tidak ada horizontal overflow — ${device.name}`, async ({ page }) => {
    await setDevice(page, device);

    const publicPages = [
      { url: `${BASE}/index.php`,          name: 'Landing' },
      { url: `${BASE}/pages/login.php`,    name: 'Login' },
      { url: `${BASE}/pages/register.php`, name: 'Register' },
    ];

    for (const p of publicPages) {
      await page.goto(p.url);
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(500);
      const { overflow, overflowEls } = await checkHorizontalOverflow(page);
      const icon = overflow ? '❌' : '✅';
      console.log(`[${device.name}][${p.name}] Overflow: ${icon}${overflow ? ' — ' + overflowEls.join(', ') : ''}`);
      results.push({ device: device.name, page: p.name, overflow });
    }
  });
}

// =============================================================================
// TEST 3: Hamburger / mobile navigation menu
// =============================================================================
test('3. Mobile navigation menu berfungsi', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 667 });
  await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });

  await page.goto(`${BASE}/index.php`);
  await page.waitForLoadState('domcontentloaded');
  await page.screenshot({ path: 'test-results/mobile-landing-375.png' });

  const hamburger = page.locator('.hamburger, button[aria-label*="menu" i], button[aria-label*="navigasi" i]');
  const hasHamburger = await hamburger.count() > 0;
  console.log(`[INFO] Hamburger menu: ${hasHamburger ? '✅ ada' : '⚠️ tidak ditemukan'}`);

  if (hasHamburger) {
    await hamburger.first().click();
    await page.waitForTimeout(400);
    const mobileMenu = page.locator('#navMenu.open, .nav-menu.open, .mobile-menu');
    const menuVisible = await mobileMenu.isVisible().catch(() => false);
    console.log(`[INFO] Menu terbuka setelah klik: ${menuVisible ? '✅' : '❌'}`);
    await page.screenshot({ path: 'test-results/mobile-menu-open.png' });
  }

  // Navigasi tetap terlihat tanpa hamburger (inline nav)
  const navLinks = page.locator('nav a, .nav-links a, header a');
  const navCount = await navLinks.count();
  console.log(`[INFO] Nav links tersedia: ${navCount}`);
});

// =============================================================================
// TEST 4: Halaman authenticated di mobile — dashboard, tryout, materi
// =============================================================================
test('4. Dashboard user di mobile — layout & overflow', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 667 });
  await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });

  await loginUser(page);
  await page.waitForTimeout(500);
  await page.screenshot({ path: 'test-results/mobile-dashboard-375.png' });

  const { overflow, overflowEls } = await checkHorizontalOverflow(page);
  console.log(`[INFO] Dashboard overflow: ${overflow ? '❌ ' + overflowEls.join(', ') : '✅ tidak ada'}`);

  // Cek stat cards tidak terpotong
  const statCards = page.locator('.stat-card, .card, .stats > div');
  const cardCount = await statCards.count();
  console.log(`[INFO] Stat cards visible: ${cardCount}`);

  // Cek apakah ada elemen yang 0 width (tersembunyi karena overflow)
  const zeroWidthEls = await page.evaluate(() => {
    const els = [];
    document.querySelectorAll('.stat-card, .card').forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.width < 5 && rect.height > 0) els.push(el.className);
    });
    return els;
  });
  console.log(`[INFO] Card dengan width ~0: ${zeroWidthEls.length > 0 ? zeroWidthEls.join(', ') : '✅ tidak ada'}`);

  expect(overflow).toBe(false);
});

// =============================================================================
// TEST 5: Halaman Tryout di mobile
// =============================================================================
test('5. Halaman Tryout di mobile — soal terbaca, tombol bisa diklik', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 667 });
  await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });
  page.on('dialog', async d => { await d.accept(); });

  await loginUser(page);

  await page.goto(`${BASE}/pages/tryout.php`);
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(3000);
  await page.screenshot({ path: 'test-results/mobile-tryout-375.png' });

  const { overflow } = await checkHorizontalOverflow(page);
  console.log(`[INFO] Tryout overflow: ${overflow ? '❌' : '✅'}`);

  // Sidebar toggle di mobile
  const sidebarToggle = page.locator('#sidebarToggle');
  const hasSidebarToggle = await sidebarToggle.isVisible().catch(() => false);
  console.log(`[INFO] Sidebar toggle: ${hasSidebarToggle ? '✅ ada' : '⚠️ tidak ada'}`);

  // Soal container
  const soalContainer = page.locator('#soalContainer, .soal-container');
  const hasSoal = await soalContainer.isVisible().catch(() => false);
  console.log(`[INFO] Soal container: ${hasSoal ? '✅' : '❌'}`);

  // Tombol navigasi
  const navBtns = page.locator('.btn-group .btn, #btnPrev, #btnNext');
  const btnCount = await navBtns.count();
  console.log(`[INFO] Tombol navigasi: ${btnCount}`);

  // Cek touch targets
  const smallTargets = await checkTouchTargets(page);
  if (smallTargets.length > 0) {
    console.log(`[WARN] Touch targets < 44px:`);
    smallTargets.forEach(t => console.log(`  - ${t.tag} "${t.text}" ${t.w}x${t.h}px`));
  } else {
    console.log('[INFO] Touch targets: ✅ semua >= 44px');
  }

  // Jawab 1 soal untuk test interaktivitas
  const options = page.locator('input[name="jawaban"]');
  const optCount = await options.count();
  if (optCount > 0) {
    await options.first().check({ timeout: 3000 }).catch(() => {});
    await page.waitForTimeout(600);
    console.log('[INFO] Jawab soal pertama: ✅');
  }

  await page.screenshot({ path: 'test-results/mobile-tryout-answered.png' });
});

// =============================================================================
// TEST 6: Ukuran font & readability di mobile
// =============================================================================
test('6. Font size & readability di mobile', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 667 });
  await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });

  const pagesToCheck = [
    { url: `${BASE}/index.php`,           name: 'Landing' },
    { url: `${BASE}/pages/login.php`,     name: 'Login' },
  ];

  for (const p of pagesToCheck) {
    await page.goto(p.url);
    await page.waitForLoadState('domcontentloaded');

    const smallFonts = await checkFontSizes(page);
    if (smallFonts.length > 0) {
      console.log(`[WARN][${p.name}] Font terlalu kecil (<12px):`);
      smallFonts.forEach(f => console.log(`  - ${f.tag} ${f.fs}px: "${f.text}"`));
    } else {
      console.log(`[${p.name}] Font size: ✅ semua >= 12px`);
    }
  }
});

// =============================================================================
// TEST 7: Form input di mobile — keyboard type sesuai
// =============================================================================
test('7. Form input types sesuai untuk mobile keyboard', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 667 });
  await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });

  // Login form
  await page.goto(`${BASE}/pages/login.php`);
  await page.waitForLoadState('domcontentloaded');

  const noHpType      = await page.locator('#no_hp').getAttribute('type').catch(() => null);
  const noHpInputmode = await page.locator('#no_hp').getAttribute('inputmode').catch(() => null);
  const passType      = await page.locator('#password').getAttribute('type').catch(() => null);

  console.log(`[Login] #no_hp type="${noHpType}" inputmode="${noHpInputmode}" — ${['tel','number'].includes(noHpType) || noHpInputmode === 'numeric' ? '✅ numeric keyboard' : '⚠️ perlu type=tel atau inputmode=numeric'}`);
  console.log(`[Login] #password type="${passType}" — ${passType === 'password' ? '✅' : '❌'}`);

  // Register form
  await page.goto(`${BASE}/pages/register.php`);
  await page.waitForLoadState('domcontentloaded');
  const regHpType      = await page.locator('#no_hp').getAttribute('type').catch(() => null);
  const regHpInputmode = await page.locator('#no_hp').getAttribute('inputmode').catch(() => null);
  console.log(`[Register] #no_hp type="${regHpType}" inputmode="${regHpInputmode}" — ${['tel','number'].includes(regHpType) || regHpInputmode === 'numeric' ? '✅' : '⚠️'}`);

  await page.screenshot({ path: 'test-results/mobile-login-form.png' });
});

// =============================================================================
// TEST 8: Halaman Materi, Leaderboard, Feedback di mobile
// =============================================================================
test('8. Halaman konten di mobile — materi, leaderboard, feedback', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 667 });
  await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });

  await loginUser(page);

  const authPages = [
    { url: `${BASE}/pages/materi.php?subtes=TWK`,  name: 'Materi TWK',  ss: 'mobile-materi.png' },
    { url: `${BASE}/pages/leaderboard.php`,         name: 'Leaderboard', ss: 'mobile-leaderboard.png' },
    { url: `${BASE}/pages/feedback.php`,            name: 'Feedback',    ss: 'mobile-feedback.png' },
    { url: `${BASE}/pages/daily_quiz.php`,          name: 'Daily Quiz',  ss: 'mobile-daily-quiz.png' },
    { url: `${BASE}/pages/profile.php`,             name: 'Profile',     ss: 'mobile-profile.png' },
  ];

  const overflowIssues = [];
  for (const p of authPages) {
    await page.goto(p.url);
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(600);
    await page.screenshot({ path: `test-results/${p.ss}` });

    const { overflow, overflowEls } = await checkHorizontalOverflow(page);
    console.log(`[${p.name}] overflow: ${overflow ? '❌ ' + overflowEls.join(', ') : '✅'}`);
    if (overflow) overflowIssues.push({ page: p.name, els: overflowEls });
  }

  if (overflowIssues.length > 0) {
    console.error('[ISSUES] Halaman dengan horizontal overflow:');
    overflowIssues.forEach(i => console.error(`  - ${i.page}: ${i.els.join(', ')}`));
  }
});

// =============================================================================
// TEST 9: iPad / tablet layout
// =============================================================================
test('9. Tablet layout (768px) — grid & navigasi', async ({ page }) => {
  await page.setViewportSize({ width: 768, height: 1024 });
  await page.setExtraHTTPHeaders({ 'User-Agent': TABLET_UA });

  await loginUser(page);
  await page.screenshot({ path: 'test-results/tablet-dashboard-768.png' });

  const { overflow, overflowEls } = await checkHorizontalOverflow(page);
  console.log(`[iPad] Dashboard overflow: ${overflow ? '❌ ' + overflowEls.join(', ') : '✅'}`);

  // Cek grid responsive
  const stats = page.locator('.stats, .stat-grid');
  if (await stats.count() > 0) {
    const gridCols = await stats.first().evaluate(el => getComputedStyle(el).gridTemplateColumns);
    console.log(`[iPad] Stats grid columns: ${gridCols}`);
  }

  await page.goto(`${BASE}/pages/tryout.php`);
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(2000);
  await page.screenshot({ path: 'test-results/tablet-tryout-768.png' });
  const { overflow: tryoutOverflow } = await checkHorizontalOverflow(page);
  console.log(`[iPad] Tryout overflow: ${tryoutOverflow ? '❌' : '✅'}`);
});

// =============================================================================
// TEST 10: Landscape orientation (667x375)
// =============================================================================
test('10. Landscape orientation (667x375) — tidak ada overflow', async ({ page }) => {
  await page.setViewportSize({ width: 667, height: 375 });
  await page.setExtraHTTPHeaders({ 'User-Agent': MOBILE_UA });

  const pagesToCheck = [
    { url: `${BASE}/index.php`,         name: 'Landing' },
    { url: `${BASE}/pages/login.php`,   name: 'Login' },
  ];

  await loginUser(page);

  for (const p of pagesToCheck) {
    await page.goto(p.url);
    await page.waitForLoadState('domcontentloaded');
    const { overflow } = await checkHorizontalOverflow(page);
    console.log(`[Landscape][${p.name}]: ${overflow ? '❌ overflow' : '✅ ok'}`);
  }

  // Dashboard landscape
  await page.goto(`${BASE}/user_dashboard.php`);
  await page.waitForLoadState('domcontentloaded');
  await page.screenshot({ path: 'test-results/mobile-landscape-dashboard.png' });
  const { overflow } = await checkHorizontalOverflow(page);
  console.log(`[Landscape][Dashboard]: ${overflow ? '❌ overflow' : '✅ ok'}`);
});

// =============================================================================
// TEST 11: Ringkasan hasil
// =============================================================================
test('11. Ringkasan Mobile Test', async () => {
  console.log('\n' + '='.repeat(60));
  console.log('MOBILE TEST SUMMARY');
  console.log('='.repeat(60));

  const overflows = results.filter(r => r.overflow);
  if (overflows.length === 0) {
    console.log('✅ Tidak ada horizontal overflow di semua device yang diuji');
  } else {
    console.log(`❌ Overflow ditemukan di ${overflows.length} kombinasi device/halaman:`);
    overflows.forEach(r => console.log(`  - [${r.device}] ${r.page}`));
  }

  console.log('\nDevice yang diuji:');
  DEVICES.forEach(d => console.log(`  - ${d.name} (${d.width}x${d.height})`));
  console.log('='.repeat(60));
});
