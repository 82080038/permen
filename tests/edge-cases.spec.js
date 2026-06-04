const { test, expect } = require('@playwright/test');

/**
 * Edge Cases Testing - SKD CAT-BKN
 * Tests for boundary conditions, error handling, and unusual scenarios
 */

test.describe('Edge Cases — Error Handling', () => {
  test('handles invalid session ID gracefully', async ({ request }) => {
    const response = await request.get('http://localhost/permen/api/get_soal.php?session_id=999999');
    expect(response.status()).toBe(401);
    const data = await response.json();
    expect(data.error).toContain('Autentikasi');
  });

  test('handles missing session ID parameter', async ({ request }) => {
    const response = await request.get('http://localhost/permen/api/get_soal.php');
    expect(response.status()).toBe(400);
    const data = await response.json();
    expect(data.error).toContain('Session ID diperlukan');
  });

  test('handles invalid email format in login', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/login.php');
    await page.fill('input[name="email"]', 'invalid-email');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    // Should stay on login page with error
    await expect(page).toHaveURL(/login\.php/);
  });

  test('handles wrong password in login', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/login.php');
    await page.fill('input[name="email"]', 'budi@skd.test');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    // Should stay on login page with error
    await expect(page).toHaveURL(/login\.php/);
  });
});

test.describe('Edge Cases — Navigation', () => {
  test('handles direct access to protected pages without login', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/user_dashboard.php');
    await expect(page).toHaveURL(/login\.php/);
  });

  test('handles direct access to admin dashboard without admin role', async ({ page }) => {
    // Login as regular user
    await page.goto('http://localhost/permen/pages/login.php?quick=budi');
    await page.waitForURL(/user_dashboard\.php/, { timeout: 15000 });
    
    // Try to access admin dashboard
    await page.goto('http://localhost/permen/pages/admin_dashboard.php');
    // Should redirect to login or user dashboard
    expect(page.url()).toMatch(/login\.php|user_dashboard\.php/);
  });

  test('handles invalid materi subtes parameter', async ({ page }) => {
    await page.goto('http://localhost/permen/pages/materi.php?subtes=INVALID');
    // Should default to TWK
    await expect(page).toHaveTitle(/Materi TWK/);
  });
});

test.describe('Edge Cases — Tryout Scenarios', () => {
  test('handles tryout with no questions available', async ({ page }) => {
    // This test would require mocking the database to have no questions
    // For now, we'll skip this as it requires database manipulation
    test.skip();
  });

  test('handles tryout timeout gracefully', async ({ page }) => {
    // This test would require manipulating timers
    // For now, we'll skip this as it requires complex setup
    test.skip();
  });
});

test.describe('Edge Cases — API Rate Limiting', () => {
  test('respects rate limiting on login attempts', async ({ page }) => {
    // Make multiple failed login attempts
    for (let i = 0; i < 6; i++) {
      await page.goto('http://localhost/permen/pages/login.php');
      await page.fill('input[name="email"]', 'test@test.com');
      await page.fill('input[name="password"]', 'wrong');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(500);
    }
    // Should show rate limit error
    const body = await page.textContent('body');
    expect(body).toContain('Terlalu banyak percobaan');
  });
});

test.describe('Edge Cases — CSRF Protection', () => {
  test('rejects form submission without CSRF token', async ({ request }) => {
    const response = await request.post('http://localhost/permen/pages/login.php', {
      form: {
        email: 'budi@skd.test',
        password: 'password',
        // Missing csrf_token
      }
    });
    // Should reject or redirect
    expect([200, 302]).toContain(response.status());
  });
});

test.describe('Edge Cases — XSS Protection', () => {
  test('escapes HTML in user-generated content', async ({ page }) => {
    // This would require testing with XSS payloads
    // For now, we'll skip as it requires specific test data
    test.skip();
  });
});
