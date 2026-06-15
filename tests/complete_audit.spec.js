import { test, expect, chromium } from '@playwright/test';

const BASE_URL = 'https://bimbel.bereng.info';
const USER_CRED = { no_hp: '081987654321', password: 'Sihaloho1982' };
const ADMIN_CRED = { no_hp: '081234567890', password: 'Sihaloho1982' };

// Collect all issues
const issues = [];

function logIssue(type, page, message, detail = '') {
    const issue = { type, page: page.url(), message, detail, time: new Date().toISOString() };
    issues.push(issue);
    console.log(`[${type}] ${page.url()}: ${message} ${detail}`);
}

async function setupPageLogging(page, label) {
    page.on('console', msg => {
        if (msg.type() === 'error') {
            logIssue('CONSOLE_ERROR', page, msg.text(), `Source: ${msg.location()?.url || 'unknown'}`);
        } else if (msg.type() === 'warning') {
            logIssue('CONSOLE_WARN', page, msg.text());
        }
    });
    page.on('pageerror', error => {
        logIssue('PAGE_ERROR', page, error.message);
    });
    page.on('response', response => {
        if (response.status() >= 400) {
            const req = response.request();
            logIssue('NETWORK_ERROR', page, `${response.status()} ${req.method()} ${req.url()}`, `Resource type: ${req.resourceType()}`);
        }
    });
    page.on('requestfailed', request => {
        logIssue('REQUEST_FAILED', page, `${request.method()} ${request.url()}`, `Failure: ${request.failure()?.errorText || 'unknown'}`);
    });
}

async function login(page, cred) {
    await page.goto(`${BASE_URL}/pages/login.php`);
    await page.fill('input[name="no_hp"]', cred.no_hp);
    await page.fill('input[name="password"]', cred.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function logout(page) {
    await page.goto(`${BASE_URL}/api/logout.php`);
    await page.waitForLoadState('networkidle');
}

// ============== USER SIMULATION ==============
test.describe('User Simulation', () => {
    let page;

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        await setupPageLogging(page, 'user');
    });

    test.afterAll(async () => {
        await page.close();
    });

    test('1. Login as User', async () => {
        await login(page, USER_CRED);
        expect(page.url()).toContain('dashboard');
    });

    test('2. User Dashboard', async () => {
        await page.goto(`${BASE_URL}/pages/user_dashboard.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/dashboard|peserta/i);
    });

    test('3. Tryout Page', async () => {
        await page.goto(`${BASE_URL}/pages/tryout.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/try out|skd/i);
    });

    test('4. Latihan Page', async () => {
        await page.goto(`${BASE_URL}/pages/latihan.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/latihan|subtes/i);
    });

    test('5. Latihan with TWK', async () => {
        await page.goto(`${BASE_URL}/pages/latihan.php?subtes=TWK`);
        await page.waitForLoadState('networkidle');
        const body = await page.locator('body').textContent();
        // Check no 500 error
        expect(body).not.toContain('Fatal error');
        expect(body).not.toContain('Stack trace');
    });

    test('6. Profile Page', async () => {
        await page.goto(`${BASE_URL}/pages/profile.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/profil|profile/i);
    });

    test('7. Settings Page', async () => {
        await page.goto(`${BASE_URL}/pages/settings.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/pengaturan|settings/i);
    });

    test('8. Materi Page', async () => {
        await page.goto(`${BASE_URL}/pages/materi.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/materi/i);
    });

    test('9. Daily Quiz', async () => {
        await page.goto(`${BASE_URL}/pages/daily_quiz.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/daily quiz|quiz/i);
    });

    test('10. Scheduled Tryouts', async () => {
        await page.goto(`${BASE_URL}/pages/scheduled_tryouts.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/scheduled|tryout/i);
    });

    test('11. Leaderboard', async () => {
        await page.goto(`${BASE_URL}/pages/leaderboard.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/leaderboard|peringkat/i);
    });

    test('12. Feedback Page', async () => {
        await page.goto(`${BASE_URL}/pages/feedback.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/feedback|masukan/i);
    });

    test('13. Help Page', async () => {
        await page.goto(`${BASE_URL}/pages/help.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/help|bantuan/i);
    });

    test('14. Logout', async () => {
        await logout(page);
        expect(page.url()).toContain('login');
    });
});

// ============== ADMIN SIMULATION ==============
test.describe('Admin Simulation', () => {
    let page;

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        await setupPageLogging(page, 'admin');
    });

    test.afterAll(async () => {
        await page.close();
    });

    test('1. Login as Admin', async () => {
        await login(page, ADMIN_CRED);
        expect(page.url()).toContain('admin_dashboard');
    });

    test('2. Admin Dashboard', async () => {
        await page.goto(`${BASE_URL}/pages/admin_dashboard.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/admin|dashboard/i);
    });

    test('3. Admin Scheduled Tryouts', async () => {
        await page.goto(`${BASE_URL}/pages/admin_scheduled_tryouts.php`);
        await page.waitForLoadState('networkidle');
        const body = await page.locator('body').textContent();
        expect(body).not.toContain('Fatal error');
        expect(body).not.toContain('Stack trace');
    });

    test('4. Logout', async () => {
        await logout(page);
        expect(page.url()).toContain('login');
    });
});

// ============== PUBLIC PAGES ==============
test.describe('Public Pages', () => {
    let page;

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        await setupPageLogging(page, 'public');
    });

    test.afterAll(async () => {
        await page.close();
    });

    test('1. Landing Page', async () => {
        await page.goto(`${BASE_URL}/`);
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveTitle(/SKD/);
    });

    test('2. Register Page', async () => {
        await page.goto(`${BASE_URL}/pages/register.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/daftar|register/i);
    });

    test('3. Forgot Password', async () => {
        await page.goto(`${BASE_URL}/pages/forgot_password.php`);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText(/reset|lupa/i);
    });
});

// ============== API ENDPOINTS ==============
test.describe('API Endpoints', () => {
    test('Health Check', async ({ request }) => {
        const response = await request.get(`${BASE_URL}/api/health.php`);
        expect(response.status()).toBe(200);
        const body = await response.json();
        expect(body.status).toBe('healthy');
    });

    test('Landing Stats', async ({ request }) => {
        const response = await request.get(`${BASE_URL}/api/get_landing_stats.php`);
        expect(response.status()).toBe(200);
    });
});

// ============== FINAL REPORT ==============
test('Final Report', async () => {
    console.log('\n\n========== COMPLETE AUDIT REPORT ==========\n');
    
    if (issues.length === 0) {
        console.log('✅ NO ISSUES FOUND! All pages, APIs, and features are working correctly.');
    } else {
        console.log(`⚠️  ${issues.length} ISSUES FOUND:\n`);
        
        const grouped = issues.reduce((acc, issue) => {
            acc[issue.type] = acc[issue.type] || [];
            acc[issue.type].push(issue);
            return acc;
        }, {});
        
        for (const [type, items] of Object.entries(grouped)) {
            console.log(`\n--- ${type} (${items.length}) ---`);
            items.forEach(item => {
                console.log(`  Page: ${item.page}`);
                console.log(`  Message: ${item.message}`);
                if (item.detail) console.log(`  Detail: ${item.detail}`);
                console.log('');
            });
        }
    }
    
    console.log('\n===========================================\n');
    
    // This test always passes - it's just for reporting
    expect(true).toBe(true);
});
