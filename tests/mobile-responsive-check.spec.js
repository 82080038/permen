// @ts-nocheck
/**
 * Mobile Responsive Check - SKD CAT-BKN
 * Tests all peserta pages at mobile viewport (375x812 iPhone X)
 * Checks: overflow, hamburger menu, touch targets, font sizes
 */
const { test, expect } = require('@playwright/test');

const BASE = process.env.TEST_BASE_URL || 'http://localhost/permen';
const PESERTA = { no_hp: '081200001111', password: 'Simulasi2025!' };

test.use({
  viewport: { width: 375, height: 812 },
  actionTimeout: 10000,
});

test.describe('Mobile Responsive Check', () => {

  test('All peserta pages - mobile layout audit', async ({ page }) => {
    // Login once
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(PESERTA.no_hp);
    await page.locator('input[name="password"]').fill(PESERTA.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/user_dashboard/, { timeout: 15000 });

    const pagesToCheck = [
      { name: 'Dashboard', url: '/pages/user_dashboard.php' },
      { name: 'Profile', url: '/pages/profile.php' },
      { name: 'Latihan', url: '/pages/latihan.php' },
      { name: 'Daily Quiz', url: '/pages/daily_quiz.php' },
      { name: 'Leaderboard', url: '/pages/leaderboard.php' },
      { name: 'Feedback', url: '/pages/feedback.php' },
      { name: 'Help', url: '/pages/help.php' },
      { name: 'Materi', url: '/pages/materi.php' },
      { name: 'Riwayat Soal', url: '/pages/riwayat_soal.php' },
      { name: 'Settings', url: '/pages/settings.php' },
      { name: 'Scheduled Tryout', url: '/pages/scheduled_tryouts.php' },
    ];

    const results = [];
    let failures = [];

    for (const p of pagesToCheck) {
      await page.goto(`${BASE}${p.url}`, { waitUntil: 'load', timeout: 15000 });

      const checks = await page.evaluate(() => {
        const body = document.body;
        const scrollW = body.scrollWidth;
        const viewW = window.innerWidth;
        const overflows = scrollW > viewW + 5;

        // Hamburger visible
        const hamburger = document.getElementById('navHamburger');
        const hamburgerVisible = hamburger ? (getComputedStyle(hamburger).display !== 'none' && hamburger.offsetWidth > 0) : false;

        // Nav collapsed
        const navMenu = document.getElementById('navMenu');
        const navCollapsed = navMenu ? getComputedStyle(navMenu).display === 'none' : true;

        // Tiny fonts
        let tinyFonts = 0;
        document.querySelectorAll('p, span, a, li, td, th, label, h1, h2, h3, button').forEach(el => {
          const fs = parseFloat(getComputedStyle(el).fontSize);
          if (fs < 10 && el.offsetWidth > 0 && el.offsetHeight > 0) tinyFonts++;
        });

        // Small touch targets (skip inputs inside labels - label is the tap target)
        let smallTargets = [];
        document.querySelectorAll('a, button, select, textarea').forEach(el => {
          const rect = el.getBoundingClientRect();
          if (rect.width > 0 && rect.height > 0 && (rect.width < 30 || rect.height < 30)) {
            smallTargets.push({
              tag: el.tagName,
              text: (el.textContent || '').trim().substring(0, 25),
              w: Math.round(rect.width),
              h: Math.round(rect.height)
            });
          }
        });

        return { scrollW, viewW, overflows, hamburgerVisible, navCollapsed, tinyFonts, smallTargets };
      });

      // Screenshot
      await page.screenshot({
        path: `tests/screenshots/mobile-${p.name.replace(/\s+/g, '_').toLowerCase()}.png`,
        fullPage: true
      });

      const status = !checks.overflows && checks.hamburgerVisible && checks.navCollapsed;
      results.push({ ...p, ...checks, pass: status });

      if (!status) {
        failures.push(p.name);
      }

      // Log
      const ov = checks.overflows ? `❌ overflow (${checks.scrollW}>${checks.viewW})` : '✅';
      const hb = checks.hamburgerVisible ? '✅' : '❌ no hamburger';
      const nc = checks.navCollapsed ? '✅' : '❌ nav open';
      const tf = checks.tinyFonts === 0 ? '✅' : `⚠️ ${checks.tinyFonts} tiny`;
      const st = checks.smallTargets.length === 0 ? '✅' : `⚠️ ${checks.smallTargets.length} small`;
      console.log(`📱 ${p.name}: overflow=${ov} hamburger=${hb} nav=${nc} fonts=${tf} targets=${st}`);
      if (checks.smallTargets.length > 0 && checks.smallTargets.length <= 10) {
        checks.smallTargets.forEach(t => console.log(`     ↳ <${t.tag}> "${t.text}" ${t.w}x${t.h}px`));
      }
    }

    // Summary
    console.log(`\n${'='.repeat(60)}`);
    console.log(`📊 SUMMARY: ${results.filter(r => r.pass).length}/${results.length} pages pass mobile checks`);
    if (failures.length > 0) {
      console.log(`❌ Failed: ${failures.join(', ')}`);
    }
    console.log(`${'='.repeat(60)}`);

    // Assert no horizontal overflow on any page
    for (const r of results) {
      expect(r.overflows, `${r.name} has horizontal overflow (${r.scrollW} > ${r.viewW})`).toBe(false);
    }
  });

  test('Tryout page - mobile exam experience', async ({ page }) => {
    // Login
    await page.goto(`${BASE}/pages/login.php`);
    await page.locator('input[name="no_hp"]').fill(PESERTA.no_hp);
    await page.locator('input[name="password"]').fill(PESERTA.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/user_dashboard/, { timeout: 15000 });

    // Go to tryout
    await page.goto(`${BASE}/pages/tryout.php`, { waitUntil: 'load', timeout: 15000 });

    const checks = await page.evaluate(() => {
      const scrollW = document.body.scrollWidth;
      const viewW = window.innerWidth;
      const overflows = scrollW > viewW + 5;

      // Timer
      const timer = document.querySelector('.timer');
      const timerVisible = timer ? timer.offsetWidth > 0 : false;

      // Sidebar toggle
      const toggle = document.getElementById('sidebarToggle');
      const toggleVisible = toggle ? (getComputedStyle(toggle).display !== 'none' && toggle.offsetWidth > 0) : false;

      // Options height check
      let optionsTooSmall = 0;
      document.querySelectorAll('.options label').forEach(el => {
        if (el.getBoundingClientRect().height < 44) optionsTooSmall++;
      });
      const totalOptions = document.querySelectorAll('.options label').length;

      // Buttons check
      let btnsTooSmall = 0;
      document.querySelectorAll('.btn-group .btn').forEach(el => {
        const rect = el.getBoundingClientRect();
        if (rect.height < 44 || rect.width < 44) btnsTooSmall++;
      });

      return { scrollW, viewW, overflows, timerVisible, toggleVisible, optionsTooSmall, totalOptions, btnsTooSmall };
    });

    await page.screenshot({ path: 'tests/screenshots/mobile-tryout.png', fullPage: true });

    console.log(`\n📱 Tryout Page:`);
    console.log(`  Overflow: ${checks.overflows ? '❌ YES (' + checks.scrollW + '>' + checks.viewW + ')' : '✅ No'}`);
    console.log(`  Timer visible: ${checks.timerVisible ? '✅' : '⚠️ No'}`);
    console.log(`  Sidebar toggle: ${checks.toggleVisible ? '✅' : '⚠️ Not visible'}`);
    console.log(`  Options too small: ${checks.optionsTooSmall}/${checks.totalOptions}`);
    console.log(`  Buttons too small: ${checks.btnsTooSmall}`);

    expect(checks.overflows, 'Tryout page has horizontal overflow').toBe(false);
    expect(checks.timerVisible, 'Timer not visible on mobile').toBe(true);
  });
});
