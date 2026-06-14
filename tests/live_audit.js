const https = require('https');
const http = require('http');

const BASE_URL = 'bimbel.bereng.info';
const USE_HTTPS = true;

const pages = [
  '/',
  '/pages/login.php',
  '/pages/register.php',
  '/pages/user_dashboard.php',
  '/pages/admin_dashboard.php',
  '/pages/tryout.php',
  '/pages/latihan.php',
  '/pages/hasil.php',
  '/pages/materi.php',
  '/pages/daily_quiz.php',
  '/pages/leaderboard.php',
  '/pages/profile.php',
  '/pages/settings.php',
  '/pages/help.php',
  '/pages/feedback.php',
  '/pages/scheduled_tryouts.php',
  '/pages/forgot_password.php',
];

const apis = [
  '/api/health.php',
  '/api/get_landing_stats.php',
  '/api/get_dashboard_analytics.php',
  '/api/logout.php',
  '/api/materi.php',
  '/api/list_soal.php',
];

const assets = [
  '/assets/style.css',
  '/assets/login.css',
  '/assets/app.js',
  '/assets/css/bootstrap.min.css',
  '/assets/css/bootstrap-icons.min.css',
  '/assets/js/bootstrap.bundle.min.js',
  '/assets/js/api.js',
];

function checkUrl(path, redirectCount = 0) {
  return new Promise((resolve) => {
    if (redirectCount > 5) {
      resolve({ path, status: 0, error: 'Too many redirects' });
      return;
    }

    const options = {
      hostname: BASE_URL,
      port: USE_HTTPS ? 443 : 80,
      path: path,
      method: 'GET',
      timeout: 10000,
    };

    const client = USE_HTTPS ? https : http;
    const req = client.request(options, (res) => {
      if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
        // Follow redirect
        const redirectPath = res.headers.location.startsWith('http')
          ? new URL(res.headers.location).pathname
          : res.headers.location;
        checkUrl(redirectPath, redirectCount + 1).then(resolve);
        return;
      }

      let body = '';
      res.on('data', (chunk) => body += chunk);
      res.on('end', () => {
        resolve({
          path,
          status: res.statusCode,
          hasError: body.includes('error') || body.includes('Error') || body.includes('Fatal'),
          hasPermenRef: body.includes('/permen/'),
          title: body.match(/<title>(.+?)<\/title>/)?.[1] || 'NO TITLE',
        });
      });
    });

    req.on('error', (err) => {
      resolve({ path, status: 0, error: err.message });
    });

    req.on('timeout', () => {
      req.destroy();
      resolve({ path, status: 0, error: 'TIMEOUT' });
    });

    req.end();
  });
}

function checkRedirect(path) {
  return new Promise((resolve) => {
    const options = {
      hostname: BASE_URL,
      port: USE_HTTPS ? 443 : 80,
      path: path,
      method: 'GET',
      timeout: 10000,
      headers: { 'Accept': 'text/html' }
    };

    const client = USE_HTTPS ? https : http;
    const req = client.request(options, (res) => {
      resolve({
        path,
        status: res.statusCode,
        location: res.headers.location || ''
      });
    });

    req.on('error', (err) => {
      resolve({ path, status: 0, error: err.message });
    });

    req.end();
  });
}

async function runAudit() {
  console.log('=== PRODUCTION AUDIT ===\n');

  console.log('--- Pages ---');
  for (const page of pages) {
    const result = await checkUrl(page);
    const status = result.status === 200 ? '✓' : result.status === 302 ? '→' : result.status === 500 ? '✗500' : `✗${result.status}`;
    const permen = result.hasPermenRef ? ' [PERMEN]' : '';
    console.log(`${status} ${page} | ${result.title}${permen}`);
  }

  console.log('\n--- APIs ---');
  for (const api of apis) {
    const result = await checkUrl(api);
    const status = result.status === 200 ? '✓' : result.status === 302 ? '→' : result.status === 500 ? '✗500' : `✗${result.status}`;
    console.log(`${status} ${api}`);
  }

  console.log('\n--- Assets ---');
  for (const asset of assets) {
    const result = await checkUrl(asset);
    const status = result.status === 200 ? '✓' : `✗${result.status}`;
    console.log(`${status} ${asset}`);
  }
}

runAudit().catch(console.error);
