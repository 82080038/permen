const { test, expect } = require('@playwright/test');

test.describe('Session Persistence & User Journey Tests', () => {
  
  test('should load landing page successfully', async ({ page }) => {
    console.log('🔍 Testing landing page load...');
    
    await page.goto('/');
    
    // Check if landing page loads
    await expect(page).toHaveTitle(/SKD CAT-BKN/);
    
    // Check for key elements
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('a[href*="login"]')).toBeVisible();
    
    // Take screenshot for evidence
    await page.screenshot({ path: 'test-results/landing-page.png' });
    
    console.log('✅ Landing page loaded successfully');
  });

  test('should load login page and display form', async ({ page }) => {
    console.log('🔍 Testing login page load...');
    
    await page.goto('/pages/login.php');
    
    // Check login page title and form
    await expect(page).toHaveTitle(/Login/);
    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('input[name="no_hp"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[name="csrf_token"]')).toBeVisible();
    
    // Check autocomplete attribute
    const passwordInput = page.locator('input[name="password"]');
    await expect(passwordInput).toHaveAttribute('autocomplete', 'current-password');
    
    // Take screenshot
    await page.screenshot({ path: 'test-results/login-page.png' });
    
    console.log('✅ Login page loaded successfully with all elements');
  });

  test('should perform login successfully', async ({ page }) => {
    console.log('🔍 Testing login process...');
    
    await page.goto('/pages/login.php');
    
    // Fill login form
    await page.fill('input[name="no_hp"]', '081987654321');
    await page.fill('input[name="password"]', 'Sihaloho1982');
    
    // Get CSRF token
    const csrfToken = await page.inputValue('input[name="csrf_token"]');
    console.log(`🔑 CSRF Token: ${csrfToken.substring(0, 20)}...`);
    
    // Submit form
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);
    
    // Check if login successful (should redirect to dashboard)
    await expect(page).toHaveURL(/user_dashboard\.php/);
    
    // Take screenshot after login
    await page.screenshot({ path: 'test-results/after-login.png' });
    
    console.log('✅ Login successful - redirected to dashboard');
  });

  test('should maintain session and display dashboard', async ({ page }) => {
    console.log('🔍 Testing session persistence and dashboard display...');
    
    // Perform login first
    await page.goto('/pages/login.php');
    await page.fill('input[name="no_hp"]', '081987654321');
    await page.fill('input[name="password"]', 'Sihaloho1982');
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);
    
    // Check dashboard content
    await expect(page).toHaveURL(/user_dashboard\.php/);
    
    // Look for user-specific content
    const pageContent = await page.content();
    console.log('📄 Page content length:', pageContent.length);
    
    // Check for dashboard elements
    const title = await page.title();
    console.log('📋 Page title:', title);
    
    // Look for user data or dashboard elements
    const possibleElements = [
      'h1', 'h2', '.welcome', '.dashboard', '.user-info',
      '[data-user]', '[class*="user"]', '[class*="dashboard"]'
    ];
    
    let foundElements = [];
    for (const selector of possibleElements) {
      const elements = await page.locator(selector).all();
      if (elements.length > 0) {
        for (const element of elements.slice(0, 3)) {
          const text = await element.textContent();
          if (text && text.trim().length > 0) {
            foundElements.push(`${selector}: "${text.trim().substring(0, 50)}..."`);
          }
        }
      }
    }
    
    console.log('🎯 Found elements:', foundElements);
    
    // Check for session indicators
    const cookies = await page.context().cookies();
    console.log('🍪 Session cookies:', cookies.filter(c => c.name.includes('PHPSESSID')));
    
    // Take screenshot of dashboard
    await page.screenshot({ path: 'test-results/dashboard-content.png', fullPage: true });
    
    // Verify we're not redirected back to login
    await expect(page).not.toHaveURL(/login\.php/);
    
    console.log('✅ Session maintained - dashboard displayed successfully');
  });

  test('should handle session validation across multiple requests', async ({ page }) => {
    console.log('🔍 Testing session validation across multiple requests...');
    
    // Login
    await page.goto('/pages/login.php');
    await page.fill('input[name="no_hp"]', '081987654321');
    await page.fill('input[name="password"]', 'Sihaloho1982');
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);
    
    // Navigate to dashboard multiple times
    for (let i = 0; i < 3; i++) {
      console.log(`🔄 Navigation attempt ${i + 1}`);
      
      await page.goto('/pages/user_dashboard.php');
      
      // Check we're still on dashboard (not redirected to login)
      await expect(page).not.toHaveURL(/login\.php/);
      
      // Wait a bit to simulate real user behavior
      await page.waitForTimeout(1000);
    }
    
    // Final screenshot
    await page.screenshot({ path: 'test-results/multiple-requests.png' });
    
    console.log('✅ Session validation successful across multiple requests');
  });

  test('should test complete user journey end-to-end', async ({ page }) => {
    console.log('🚀 Testing complete user journey end-to-end...');
    
    // Step 1: Landing page
    await page.goto('/');
    await expect(page).toHaveTitle(/SKD CAT-BKN/);
    console.log('✅ Step 1: Landing page accessed');
    
    // Step 2: Navigate to login
    await page.click('a[href*="login"]');
    await expect(page).toHaveURL(/login\.php/);
    console.log('✅ Step 2: Navigated to login page');
    
    // Step 3: Perform login
    await page.fill('input[name="no_hp"]', '081987654321');
    await page.fill('input[name="password"]', 'Sihaloho1982');
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);
    console.log('✅ Step 3: Login form submitted');
    
    // Step 4: Verify dashboard access
    await expect(page).toHaveURL(/user_dashboard\.php/);
    await expect(page).not.toHaveURL(/login\.php/);
    console.log('✅ Step 4: Dashboard accessed successfully');
    
    // Step 5: Check session persistence
    const sessionCookies = await page.context().cookies();
    const hasSessionCookie = sessionCookies.some(cookie => cookie.name.includes('PHPSESSID'));
    expect(hasSessionCookie).toBeTruthy();
    console.log('✅ Step 5: Session cookie verified');
    
    // Final screenshot
    await page.screenshot({ path: 'test-results/complete-journey.png', fullPage: true });
    
    console.log('🎉 Complete user journey test PASSED');
  });

});
