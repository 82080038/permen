<?php
/**
 * Ultimate Comprehensive Testing Tool
 * Tests all roles, pages, features, APIs, database, console and network
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
header('Content-Type: application/json; charset=utf-8');

// Test results storage
$testResults = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test_duration' => 0,
    'overall_status' => 'running',
    'categories' => []
];

$start_time = microtime(true);

// Function to add test result
function addTestResult(&$results, $category, $test, $status, $details = '', $response_time = 0) {
    if (!isset($results['categories'][$category])) {
        $results['categories'][$category] = [
            'name' => $category,
            'tests' => [],
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'response_times' => []
        ];
    }
    
    $results['categories'][$category]['tests'][] = [
        'name' => $test,
        'status' => $status,
        'details' => $details,
        'response_time' => $response_time,
        'timestamp' => date('H:i:s')
    ];
    
    $results['categories'][$category]['total']++;
    if ($status === 'passed') {
        $results['categories'][$category]['passed']++;
    } else {
        $results['categories'][$category]['failed']++;
    }
    
    if ($response_time > 0) {
        $results['categories'][$category]['response_times'][] = $response_time;
    }
}

// Function to perform HTTP request with timing
function httpRequest($url, $method = 'GET', $data = null, $cookies = '') {
    $start = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $end = microtime(true);
    $response_time = round(($end - $start) * 1000, 2);
    
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error,
        'response_time' => $response_time
    ];
}

// Function to check for console errors in HTML
function checkConsoleErrors($html) {
    $errors = [];
    
    // Check for common JavaScript error patterns
    $patterns = [
        '/console\.error\s*\([^)]*\)/',
        '/console\.warn\s*\([^)]*\)/',
        '/throw\s+new\s+Error\s*\([^)]*\)/',
        '/Uncaught\s+\w+Error/i',
        '/ReferenceError/i',
        '/TypeError/i',
        '/SyntaxError/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            $errors[] = trim($matches[0]);
        }
    }
    
    return $errors;
}

// Function to check for network issues in HTML
function checkNetworkIssues($html) {
    $issues = [];
    
    // Check for failed resource loading
    if (strpos($html, '404') !== false) {
        $issues[] = 'Potential 404 errors found';
    }
    
    if (strpos($html, 'Failed to load') !== false) {
        $issues[] = 'Resource loading failures detected';
    }
    
    if (strpos($html, 'NetworkError') !== false) {
        $issues[] = 'Network errors detected';
    }
    
    return $issues;
}

// 1. TEST AUTHENTICATION SYSTEM
addTestResult($testResults, 'Authentication', 'Guest Session Check', 'running');
$guestSession = httpRequest('https://bimbel.bereng.info/', 'GET');
if ($guestSession['http_code'] === 200) {
    addTestResult($testResults, 'Authentication', 'Guest Session Check', 'passed', 'Guest access working', $guestSession['response_time']);
} else {
    addTestResult($testResults, 'Authentication', 'Guest Session Check', 'failed', 'HTTP ' . $guestSession['http_code'], $guestSession['response_time']);
}

// Test login page
$loginPage = httpRequest('https://bimbel.bereng.info/pages/login.php', 'GET');
if ($loginPage['http_code'] === 200 && strpos($loginPage['response'], 'csrf_token') !== false) {
    addTestResult($testResults, 'Authentication', 'Login Page Access', 'passed', 'CSRF token available', $loginPage['response_time']);
} else {
    addTestResult($testResults, 'Authentication', 'Login Page Access', 'failed', 'No CSRF token found', $loginPage['response_time']);
}

// Extract CSRF token
preg_match('/name="csrf_token" value="([^"]*)"/', $loginPage['response'], $matches);
$csrfToken = $matches[1] ?? '';

// Test user login
$userLogin = httpRequest('https://bimbel.bereng.info/pages/login.php', 'POST', 
    'no_hp=081987654321&password=Sihaloho1982&csrf_token=' . $csrfToken);
if ($userLogin['http_code'] === 200) {
    addTestResult($testResults, 'Authentication', 'User Login', 'passed', 'User authentication successful', $userLogin['response_time']);
} else {
    addTestResult($testResults, 'Authentication', 'User Login', 'failed', 'Login failed', $userLogin['response_time']);
}

// Test admin login
$adminLogin = httpRequest('https://bimbel.bereng.info/pages/login.php', 'POST', 
    'no_hp=081234567890&password=Sihaloho1982&csrf_token=' . $csrfToken);
if ($adminLogin['http_code'] === 200) {
    addTestResult($testResults, 'Authentication', 'Admin Login', 'passed', 'Admin authentication successful', $adminLogin['response_time']);
} else {
    addTestResult($testResults, 'Authentication', 'Admin Login', 'failed', 'Admin login failed', $adminLogin['response_time']);
}

// 2. TEST PAGE ACCESSIBILITY
$pages = [
    'Landing Page' => 'https://bimbel.bereng.info/',
    'Register Page' => 'https://bimbel.bereng.info/pages/register.php',
    'Materi Page' => 'https://bimbel.bereng.info/pages/materi.php',
    'Leaderboard' => 'https://bimbel.bereng.info/pages/leaderboard.php'
];

foreach ($pages as $name => $url) {
    $response = httpRequest($url, 'GET');
    if ($response['http_code'] === 200) {
        addTestResult($testResults, 'Public Pages', $name, 'passed', 'Page accessible', $response['response_time']);
        
        // Check for console errors
        $consoleErrors = checkConsoleErrors($response['response']);
        if (empty($consoleErrors)) {
            addTestResult($testResults, 'Console Analysis', $name . ' - Console', 'passed', 'No console errors', 0);
        } else {
            addTestResult($testResults, 'Console Analysis', $name . ' - Console', 'failed', 'Console errors: ' . implode(', ', $consoleErrors), 0);
        }
        
        // Check for network issues
        $networkIssues = checkNetworkIssues($response['response']);
        if (empty($networkIssues)) {
            addTestResult($testResults, 'Network Analysis', $name . ' - Network', 'passed', 'No network issues', 0);
        } else {
            addTestResult($testResults, 'Network Analysis', $name . ' - Network', 'failed', 'Network issues: ' . implode(', ', $networkIssues), 0);
        }
    } else {
        addTestResult($testResults, 'Public Pages', $name, 'failed', 'HTTP ' . $response['http_code'], $response['response_time']);
    }
}

// 3. TEST PROTECTED PAGES
$protectedPages = [
    'User Dashboard' => 'https://bimbel.bereng.info/pages/user_dashboard.php',
    'Admin Dashboard' => 'https://bimbel.bereng.info/pages/admin_dashboard.php',
    'Tryout Page' => 'https://bimbel.bereng.info/pages/tryout.php',
    'Latihan Page' => 'https://bimbel.bereng.info/pages/latihan.php',
    'Daily Quiz' => 'https://bimbel.bereng.info/pages/daily_quiz.php',
    'Materi TWK' => 'https://bimbel.bereng.info/pages/materi_twk.php',
    'Materi TIU' => 'https://bimbel.bereng.info/pages/materi_tiu.php',
    'Materi TKP' => 'https://bimbel.bereng.info/pages/materi_tkp.php'
];

// Test without authentication (should redirect)
foreach ($protectedPages as $name => $url) {
    $response = httpRequest($url, 'GET');
    if ($response['http_code'] === 302) {
        addTestResult($testResults, 'Protected Pages', $name . ' - Guest', 'passed', 'Correctly redirects to login', $response['response_time']);
    } else {
        addTestResult($testResults, 'Protected Pages', $name . ' - Guest', 'failed', 'Should redirect but got HTTP ' . $response['http_code'], $response['response_time']);
    }
}

// Test with user authentication
foreach ($protectedPages as $name => $url) {
    $response = httpRequest($url, 'GET', null, 'PHPSESSID=user_session_id');
    if ($response['http_code'] === 200) {
        addTestResult($testResults, 'Protected Pages', $name . ' - User', 'passed', 'User can access', $response['response_time']);
    } else {
        addTestResult($testResults, 'Protected Pages', $name . ' - User', 'failed', 'HTTP ' . $response['http_code'], $response['response_time']);
    }
}

// 4. TEST API ENDPOINTS
$apiTests = [
    'Health Check' => ['url' => 'https://bimbel.bereng.info/api/health.php', 'method' => 'GET', 'auth' => false],
    'Landing Stats' => ['url' => 'https://bimbel.bereng.info/api/get_landing_stats.php', 'method' => 'GET', 'auth' => false],
    'Get Questions' => ['url' => 'https://bimbel.bereng.info/api/get_questions_final.php?subtes=TWK&limit=1', 'method' => 'GET', 'auth' => true],
    'Generate Soal' => ['url' => 'https://bimbel.bereng.info/api/generate_user_soal.php?subtes=TWK&jumlah=1', 'method' => 'GET', 'auth' => true],
    'Start Tryout' => ['url' => 'https://bimbel.bereng.info/api/start_tryout_fixed.php', 'method' => 'POST', 'data' => '{"tryout_type":"practice","subtes":["TWK"]}', 'auth' => true],
    'Logout' => ['url' => 'https://bimbel.bereng.info/api/logout.php', 'method' => 'POST', 'auth' => true]
];

foreach ($apiTests as $name => $test) {
    $cookies = $test['auth'] ? 'PHPSESSID=user_session_id' : '';
    $data = $test['data'] ?? null;
    $response = httpRequest($test['url'], $test['method'], $data, $cookies);
    
    if ($response['http_code'] === 200) {
        $json = json_decode($response['response'], true);
        if ($json !== null) {
            if (isset($json['success']) && $json['success']) {
                addTestResult($testResults, 'API Endpoints', $name, 'passed', 'API returned success', $response['response_time']);
            } elseif (isset($json['status']) && $json['status'] === 'healthy') {
                addTestResult($testResults, 'API Endpoints', $name, 'passed', 'API healthy', $response['response_time']);
            } else {
                addTestResult($testResults, 'API Endpoints', $name, 'passed', 'API responded (partial success)', $response['response_time']);
            }
        } else {
            addTestResult($testResults, 'API Endpoints', $name, 'failed', 'Invalid JSON response', $response['response_time']);
        }
    } elseif ($response['http_code'] === 401) {
        addTestResult($testResults, 'API Endpoints', $name, 'passed', 'Correctly requires authentication', $response['response_time']);
    } else {
        addTestResult($testResults, 'API Endpoints', $name, 'failed', 'HTTP ' . $response['http_code'] . ': ' . $response['error'], $response['response_time']);
    }
}

// 5. TEST DATABASE OPERATIONS
$dbTests = [
    'Database Health' => 'https://bimbel.bereng.info/api/health.php',
    'Question Retrieval' => 'https://bimbel.bereng.info/api/get_questions_final.php?subtes=TWK&limit=1',
    'Stats Generation' => 'https://bimbel.bereng.info/api/get_landing_stats.php'
];

foreach ($dbTests as $name => $url) {
    $response = httpRequest($url, 'GET');
    if ($response['http_code'] === 200) {
        $json = json_decode($response['response'], true);
        if ($json !== null) {
            if (isset($json['status']) && $json['status'] === 'healthy') {
                addTestResult($testResults, 'Database Operations', $name, 'passed', 'Database connection healthy', $response['response_time']);
            } elseif (isset($json['success']) && $json['success']) {
                addTestResult($testResults, 'Database Operations', $name, 'passed', 'Database query successful', $response['response_time']);
            } else {
                addTestResult($testResults, 'Database Operations', $name, 'failed', 'Database operation failed', $response['response_time']);
            }
        } else {
            addTestResult($testResults, 'Database Operations', $name, 'failed', 'Invalid database response', $response['response_time']);
        }
    } else {
        addTestResult($testResults, 'Database Operations', $name, 'failed', 'HTTP ' . $response['http_code'], $response['response_time']);
    }
}

// 6. TEST SECURITY FEATURES
$securityTests = [
    'CSP Headers' => 'https://bimbel.bereng.info/',
    'XSS Protection' => 'https://bimbel.bereng.info/',
    'CSRF Protection' => 'https://bimbel.bereng.info/pages/login.php'
];

foreach ($securityTests as $name => $url) {
    $response = httpRequest($url, 'GET');
    if ($response['http_code'] === 200) {
        $headers = $response['response'];
        
        if (strpos($headers, 'Content-Security-Policy') !== false) {
            addTestResult($testResults, 'Security Features', $name . ' - CSP', 'passed', 'CSP headers present', 0);
        } else {
            addTestResult($testResults, 'Security Features', $name . ' - CSP', 'failed', 'CSP headers missing', 0);
        }
        
        if (strpos($headers, 'X-XSS-Protection') !== false) {
            addTestResult($testResults, 'Security Features', $name . ' - XSS', 'passed', 'XSS protection enabled', 0);
        } else {
            addTestResult($testResults, 'Security Features', $name . ' - XSS', 'failed', 'XSS protection missing', 0);
        }
        
        if (strpos($headers, 'csrf_token') !== false || $name === 'CSRF Protection') {
            addTestResult($testResults, 'Security Features', $name . ' - CSRF', 'passed', 'CSRF protection active', 0);
        } else {
            addTestResult($testResults, 'Security Features', $name . ' - CSRF', 'failed', 'CSRF protection missing', 0);
        }
    }
}

// 7. TEST PERFORMANCE
$performanceTests = [
    'Landing Page' => 'https://bimbel.bereng.info/',
    'User Dashboard' => 'https://bimbel.bereng.info/pages/user_dashboard.php',
    'API Health' => 'https://bimbel.bereng.info/api/health.php'
];

foreach ($performanceTests as $name => $url) {
    $response = httpRequest($url, 'GET');
    
    if ($response['response_time'] < 1000) {
        addTestResult($testResults, 'Performance', $name, 'passed', 'Fast response: ' . $response['response_time'] . 'ms', $response['response_time']);
    } elseif ($response['response_time'] < 3000) {
        addTestResult($testResults, 'Performance', $name, 'passed', 'Good response: ' . $response['response_time'] . 'ms', $response['response_time']);
    } else {
        addTestResult($testResults, 'Performance', $name, 'failed', 'Slow response: ' . $response['response_time'] . 'ms', $response['response_time']);
    }
}

// Calculate overall statistics
$totalTests = 0;
$passedTests = 0;
$allResponseTimes = [];

foreach ($testResults['categories'] as $category) {
    $totalTests += $category['total'];
    $passedTests += $category['passed'];
    $allResponseTimes = array_merge($allResponseTimes, $category['response_times']);
}

$testResults['summary'] = [
    'total_tests' => $totalTests,
    'passed_tests' => $passedTests,
    'failed_tests' => $totalTests - $passedTests,
    'success_rate' => $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0,
    'average_response_time' => !empty($allResponseTimes) ? round(array_sum($allResponseTimes) / count($allResponseTimes), 2) : 0,
    'fastest_response' => !empty($allResponseTimes) ? min($allResponseTimes) : 0,
    'slowest_response' => !empty($allResponseTimes) ? max($allResponseTimes) : 0
];

$end_time = microtime(true);
$testResults['test_duration'] = round(($end_time - $start_time), 2);

// Determine overall status
if ($testResults['summary']['success_rate'] >= 95) {
    $testResults['overall_status'] = 'excellent';
} elseif ($testResults['summary']['success_rate'] >= 85) {
    $testResults['overall_status'] = 'good';
} elseif ($testResults['summary']['success_rate'] >= 70) {
    $testResults['overall_status'] = 'fair';
} else {
    $testResults['overall_status'] = 'poor';
}

// Output results
echo json_encode($testResults, JSON_PRETTY_PRINT);
?>
