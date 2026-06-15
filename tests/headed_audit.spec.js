import { test, expect } from '@playwright/test';

const BASE_URL = 'https://bimbel.bereng.info';
const USER_CRED = { no_hp: '081987654321', password: 'Sihaloho1982' };
const ADMIN_CRED = { no_hp: '081234567890', password: 'Sihaloho1982' };

// Global error collectors
const consoleErrors = [];
const networkErrors = [];
const pageErrors = [];

function setupMonitoring(page, label) {
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push({
                label,
                url: page.url(),
                text: msg.text(),
                location: msg.location()?.url || ''
            });
        }
    });
    page.on('pageerror', error => {
        pageErrors.push({
            label,
            url: page.url(),
            message: error.message,
            stack: error.stack?.split('\n').slice(0, 3).join('\n') || ''
        });
    });
    page.on('response', response => {
        const status = response.status();
        if (status >= 400) {
            const req = response.request();
            networkErrors.push({
                label,
                pageUrl: page.url(),
                resourceUrl: req.url(),
                status,
                method: req.method(),
                resourceType: req.resourceType()
            });
        }
    });
    page.on('requestfailed', request => {
        networkErrors.push({
            label,
            pageUrl: page.url(),
            resourceUrl: request.url(),
            status: 0,
            method: request.method(),
            resourceType: request.resourceType(),
            failure: request.failure()?.errorText || 'unknown'
        });
    });
}

async function login(page, cred) {
    await page.goto(`${BASE_URL}/pages/login.php`);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="no_hp"]', cred.no_hp);
    await page.fill('input[name="password"]', cred.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function visitPage(page, path, label) {
    await page.goto(`${BASE_URL}${path}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(3000); // Wait for async JS, XHR, service worker
}

test.describe.serial('Full Headed Audit - User', () => {
    let page;

    test.beforeAll(async ({ browser }) => {
        const context = await browser.newContext();
        page = await context.newPage();
        setupMonitoring(page, 'user');
        await login(page, USER_CRED);
    });

    test.afterAll(async () => {
        await page.close();
    });

    const userPages = [
        ['/pages/user_dashboard.php', 'User Dashboard'],
        ['/pages/latihan.php', 'Latihan'],
        ['/pages/profile.php', 'Profile'],
        ['/pages/settings.php', 'Settings'],
        ['/pages/materi.php', 'Materi'],
        ['/pages/daily_quiz.php', 'Daily Quiz'],
        ['/pages/scheduled_tryouts.php', 'Scheduled Tryouts'],
        ['/pages/leaderboard.php', 'Leaderboard'],
        ['/pages/feedback.php', 'Feedback'],
        ['/pages/help.php', 'Help'],
        ['/pages/hasil.php', 'Hasil (no session - redirects)'],
    ];

    for (const [path, name] of userPages) {
        test(`User: ${name}`, async () => {
            await visitPage(page, path, `user:${name}`);
            const body = await page.locator('body').textContent();
            expect(body).not.toContain('Fatal error');
            expect(body).not.toContain('Stack trace');
        });
    }
});

test.describe.serial('Full Headed Audit - Admin', () => {
    let page;

    test.beforeAll(async ({ browser }) => {
        const context = await browser.newContext();
        page = await context.newPage();
        setupMonitoring(page, 'admin');
        await login(page, ADMIN_CRED);
    });

    test.afterAll(async () => {
        await page.close();
    });

    const adminPages = [
        ['/pages/admin_dashboard.php', 'Admin Dashboard'],
        ['/pages/admin_scheduled_tryouts.php', 'Admin Scheduled Tryouts'],
    ];

    for (const [path, name] of adminPages) {
        test(`Admin: ${name}`, async () => {
            await visitPage(page, path, `admin:${name}`);
            const body = await page.locator('body').textContent();
            expect(body).not.toContain('Fatal error');
            expect(body).not.toContain('Stack trace');
        });
    }
});

test.describe.serial('Full Headed Audit - Public', () => {
    let page;

    test.beforeAll(async ({ browser }) => {
        const context = await browser.newContext();
        page = await context.newPage();
        setupMonitoring(page, 'public');
    });

    test.afterAll(async () => {
        await page.close();
    });

    const publicPages = [
        ['/', 'Landing Page'],
        ['/pages/login.php', 'Login'],
        ['/pages/register.php', 'Register'],
        ['/pages/forgot_password.php', 'Forgot Password'],
        ['/pages/materi.php', 'Materi (Public)'],
        ['/pages/leaderboard.php', 'Leaderboard (Public)'],
    ];

    for (const [path, name] of publicPages) {
        test(`Public: ${name}`, async () => {
            await visitPage(page, path, `public:${name}`);
            const body = await page.locator('body').textContent();
            expect(body).not.toContain('Fatal error');
            expect(body).not.toContain('Stack trace');
        });
    }
});

test('=== FINAL AUDIT REPORT ===', async () => {
    console.log('\n');
    console.log('╔══════════════════════════════════════════════════════════════╗');
    console.log('║        COMPLETE CONSOLE & NETWORK AUDIT REPORT              ║');
    console.log('╚══════════════════════════════════════════════════════════════╝');

    // Console Errors
    console.log(`\n\n📛 CONSOLE ERRORS (${consoleErrors.length}):`);
    console.log('─'.repeat(60));
    if (consoleErrors.length === 0) {
        console.log('  ✅ None');
    } else {
        const unique = new Map();
        consoleErrors.forEach(e => {
            const key = `${e.text}|${e.location}`;
            if (!unique.has(key)) unique.set(key, e);
        });
        unique.forEach(e => {
            console.log(`  Page: ${e.url}`);
            console.log(`  Error: ${e.text}`);
            if (e.location) console.log(`  Source: ${e.location}`);
            console.log('');
        });
    }

    // Network Errors
    console.log(`\n📡 NETWORK ERRORS (${networkErrors.length}):`);
    console.log('─'.repeat(60));
    if (networkErrors.length === 0) {
        console.log('  ✅ None');
    } else {
        const unique = new Map();
        networkErrors.forEach(e => {
            const key = `${e.status}|${e.resourceUrl}`;
            if (!unique.has(key)) unique.set(key, e);
        });
        unique.forEach(e => {
            const status = e.status === 0 ? 'FAILED' : e.status;
            console.log(`  [${status}] ${e.method} ${e.resourceUrl}`);
            console.log(`    Page: ${e.pageUrl}`);
            console.log(`    Type: ${e.resourceType}${e.failure ? ', Reason: ' + e.failure : ''}`);
            console.log('');
        });
    }

    // Page Errors (uncaught JS exceptions)
    console.log(`\n💥 PAGE ERRORS / JS EXCEPTIONS (${pageErrors.length}):`);
    console.log('─'.repeat(60));
    if (pageErrors.length === 0) {
        console.log('  ✅ None');
    } else {
        pageErrors.forEach(e => {
            console.log(`  Page: ${e.url}`);
            console.log(`  Error: ${e.message}`);
            if (e.stack) console.log(`  Stack: ${e.stack}`);
            console.log('');
        });
    }

    // Summary
    const totalIssues = consoleErrors.length + networkErrors.length + pageErrors.length;
    console.log('\n' + '═'.repeat(60));
    console.log(`TOTAL ISSUES: ${totalIssues}`);
    console.log(`  Console Errors: ${consoleErrors.length}`);
    console.log(`  Network Errors: ${networkErrors.length}`);
    console.log(`  JS Exceptions:  ${pageErrors.length}`);
    console.log('═'.repeat(60));

    // Always pass - this test is for reporting
    expect(true).toBe(true);
});
