const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ 
    headless: false,
    slowMo: 1000 // Slow down for better visibility
  });
  
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  
  // Enable console logging
  context.on('console', msg => {
    console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`);
  });
  
  // Enable network logging
  context.on('request', request => {
    console.log(`[REQUEST] ${request.method()} ${request.url()}`);
  });
  
  context.on('response', response => {
    console.log(`[RESPONSE] ${response.status()} ${response.url()}`);
  });
  
  const page = await context.newPage();
  
  console.log('=== Starting Playwright Smoke Test ===');
  console.log('Target: https://bimbel.bereng.info/');
  console.log('Mode: Headed (visible browser)');
  console.log('Monitoring: Console, Network, Terminal');
  console.log('=====================================\n');
  
  try {
    console.log('[STEP 1] Navigating to homepage...');
    await page.goto('https://bimbel.bereng.info/', { 
      waitUntil: 'networkidle',
      timeout: 30000 
    });
    
    console.log('[STEP 2] Checking page title...');
    const title = await page.title();
    console.log(`Page Title: ${title}`);
    
    console.log('[STEP 3] Checking for errors...');
    const errors = await page.evaluate(() => {
      const errors = [];
      // Check for common error indicators
      if (document.body.innerHTML.includes('500')) errors.push('HTTP 500 error detected');
      if (document.body.innerHTML.includes('Error')) errors.push('Error text detected');
      if (document.body.innerHTML.includes('Terjadi kesalahan')) errors.push('Indonesian error message detected');
      return errors;
    });
    
    if (errors.length > 0) {
      console.error('[ERRORS FOUND]:', errors);
    } else {
      console.log('[OK] No obvious errors detected in page content');
    }
    
    console.log('[STEP 4] Checking for critical elements...');
    const hasContent = await page.evaluate(() => {
      return {
        hasHero: document.querySelector('.landing-hero') !== null,
        hasFeatures: document.querySelector('.landing-features') !== null,
        hasStats: document.querySelector('.landing-stats') !== null,
        hasFooter: document.querySelector('.landing-footer') !== null
      };
    });
    
    console.log('Page Elements:', hasContent);
    
    console.log('[STEP 5] Taking screenshot...');
    await page.screenshot({ path: 'playwright_screenshot.png', fullPage: true });
    console.log('[OK] Screenshot saved to playwright_screenshot.png');
    
    console.log('[STEP 6] Waiting 5 seconds for observation...');
    await page.waitForTimeout(5000);
    
    console.log('\n=== Test Summary ===');
    console.log('Status: Test completed');
    console.log('Page loaded:', title !== 'Error');
    console.log('Elements present:', Object.values(hasContent).filter(v => v).length, '/', Object.values(hasContent).length);
    
  } catch (error) {
    console.error('[FATAL ERROR]:', error.message);
    console.error('Stack:', error.stack);
  } finally {
    console.log('\n[STEP 7] Closing browser in 10 seconds...');
    await page.waitForTimeout(10000);
    await browser.close();
    console.log('[DONE] Browser closed');
  }
})();
