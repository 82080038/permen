<?php
/**
 * Browser Simulation Test
 * Simulates browser behavior to detect client-side issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
header('Content-Type: application/json; charset=utf-8');

// Test results
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test_type' => 'browser_simulation',
    'findings' => []
];

// Function to add finding
function addFinding(&$results, $category, $issue, $severity, $details = '') {
    $results['findings'][] = [
        'category' => $category,
        'issue' => $issue,
        'severity' => $severity,
        'details' => $details,
        'timestamp' => date('H:i:s')
    ];
}

// Function to fetch and analyze page
function analyzePage($url, $context = '') {
    global $results;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        addFinding($results, 'HTTP Request', "Failed to fetch $url", 'high', $error);
        return false;
    }
    
    if ($http_code !== 200) {
        addFinding($results, 'HTTP Response', "HTTP $http_code for $url", 'medium', "Expected 200, got $http_code");
        return false;
    }
    
    // Analyze HTML content
    analyzeHTML($url, $response, $context);
    
    return $response;
}

// Function to analyze HTML content
function analyzeHTML($url, $html, $context) {
    global $results;
    
    // Check for error messages
    $error_patterns = [
        '/Terjadi kesalahan server/i',
        '/Server error/i',
        '/Internal server error/i',
        '/500 Internal Server Error/i',
        '/Connection failed/i',
        '/Database error/i',
        '/SQL error/i',
        '/PHP error/i',
        '/Fatal error/i',
        '/Warning:/i',
        '/Notice:/i'
    ];
    
    foreach ($error_patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            addFinding($results, 'Error Messages', 
                "Error pattern found in $url", 
                'high', 
                "Pattern: $pattern, Match: " . substr($matches[0], 0, 100)
            );
        }
    }
    
    // Check for JavaScript error patterns
    $js_error_patterns = [
        '/console\.error\s*\([^)]*\)/',
        '/console\.warn\s*\([^)]*\)/',
        '/throw\s+new\s+Error\s*\([^)]*\)/',
        '/catch\s*\([^)]*\)\s*{[^}]*}/',
        /\.catch\s*\(/',
        /try\s*{[^}]*}catch/
    ];
    
    foreach ($js_error_patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            addFinding($results, 'JavaScript Error Handling', 
                "JavaScript error handling found in $url", 
                'medium', 
                "Pattern: $pattern"
            );
        }
    }
    
    // Check for form validation
    if (strpos($html, '<form') !== false) {
        if (strpos($html, 'required') !== false) {
            addFinding($results, 'Form Validation', 
                "Form validation found in $url", 
                'low', 
                'HTML5 validation attributes present'
            );
        }
        
        if (strpos($html, 'csrf_token') !== false) {
            addFinding($results, 'Security', 
                "CSRF protection found in $url", 
                'low', 
                'CSRF token present in form'
            );
        }
    }
    
    // Check for AJAX/Fetch calls
    $ajax_patterns = [
        '/fetch\s*\(/',
        '/XMLHttpRequest/',
        '/\.ajax\s*\(/',
        '/\.get\s*\(/',
        '/\.post\s*\(/'
    ];
    
    foreach ($ajax_patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            addFinding($results, 'AJAX Calls', 
                "AJAX/Fetch calls found in $url", 
                'medium', 
                "Pattern: $pattern"
            );
        }
    }
    
    // Check for common JavaScript libraries
    $libraries = [
        'jquery' => '/jquery/i',
        'bootstrap' => '/bootstrap/i',
        'vue' => '/vue\.js/i',
        'react' => '/react/i',
        'angular' => '/angular/i'
    ];
    
    foreach ($libraries as $lib => $pattern) {
        if (preg_match($pattern, $html)) {
            addFinding($results, 'JavaScript Libraries', 
                "$lib library detected in $url", 
                'low', 
                "Library: $lib"
            );
        }
    }
}

// Function to test API endpoints like browser would
function testAPIEndpoints() {
    global $results;
    
    $apis = [
        '/api/health.php',
        '/api/get_landing_stats.php',
        '/api/get_questions_final.php?subtes=TWK&limit=1',
        '/api/generate_user_soal.php?subtes=TWK&jumlah=1'
    ];
    
    foreach ($apis as $api) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://bimbel.bereng.info' . $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            addFinding($results, 'API Error', "API call failed: $api", 'high', $error);
        } elseif ($http_code !== 200) {
            addFinding($results, 'API Error', "API returned HTTP $http_code: $api", 'medium', "Expected 200, got $http_code");
        } else {
            $json = json_decode($response, true);
            if ($json === null) {
                addFinding($results, 'API Error', "Invalid JSON response: $api", 'medium', 'JSON decode failed');
            } elseif (isset($json['error'])) {
                addFinding($results, 'API Error', "API returned error: $api", 'high', $json['error']);
            } elseif (isset($json['success']) && !$json['success']) {
                addFinding($results, 'API Error', "API unsuccessful: $api", 'medium', 'Success flag is false');
            } else {
                addFinding($results, 'API Success', "API working correctly: $api", 'low', 'Response is valid JSON');
            }
        }
    }
}

// Function to simulate user interactions
function simulateUserInteractions() {
    global $results;
    
    // Test login form submission
    $login_url = 'https://bimbel.bereng.info/pages/login.php';
    
    // First, get the login page to extract CSRF token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $login_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
    
    $login_page = curl_exec($ch);
    curl_close($ch);
    
    // Extract CSRF token
    preg_match('/name="csrf_token" value="([^"]*)"/', $login_page, $matches);
    $csrf_token = $matches[1] ?? '';
    
    if (!$csrf_token) {
        addFinding($results, 'Login Form', 'CSRF token not found', 'high', 'Cannot proceed with login test');
        return;
    }
    
    // Now try to submit login form
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $login_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'no_hp' => '081987654321',
        'password' => 'Sihaloho1982',
        'csrf_token' => $csrf_token
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 302) {
        addFinding($results, 'Login Form', 'Login successful (redirect)', 'low', 'Redirect after login');
    } elseif ($http_code === 200) {
        if (strpos($response, 'Terjadi kesalahan server') !== false) {
            addFinding($results, 'Login Form', 'Server error during login', 'high', 'Found "Terjadi kesalahan server" in response');
        } else {
            addFinding($results, 'Login Form', 'Login failed (no redirect)', 'medium', 'Stayed on login page');
        }
    } else {
        addFinding($results, 'Login Form', "Login returned HTTP $http_code", 'medium', "Unexpected HTTP status");
    }
}

// Main execution
addFinding($results, 'Test Start', 'Browser simulation test started', 'info', 'Testing client-side issues');

// Test main pages
$pages = [
    'https://bimbel.bereng.info/',
    'https://bimbel.bereng.info/pages/login.php',
    'https://bimbel.bereng.info/pages/register.php',
    'https://bimbel.bereng.info/pages/user_dashboard.php',
    'https://bimbel.bereng.info/pages/tryout.php',
    'https://bimbel.bereng.info/pages/latihan.php'
];

foreach ($pages as $page) {
    analyzePage($page, 'main_page');
}

// Test API endpoints
testAPIEndpoints();

// Simulate user interactions
simulateUserInteractions();

// Summary
$total_findings = count($results['findings']);
$high_severity = count(array_filter($results['findings'], fn($f) => $f['severity'] === 'high'));
$medium_severity = count(array_filter($results['findings'], fn($f) => $f['severity'] === 'medium'));
$low_severity = count(array_filter($results['findings'], fn($f) => $f['severity'] === 'low'));

$results['summary'] = [
    'total_findings' => $total_findings,
    'high_severity' => $high_severity,
    'medium_severity' => $medium_severity,
    'low_severity' => $low_severity,
    'critical_issues' => $high_severity > 0,
    'needs_attention' => $high_severity > 0 || $medium_severity > 0
];

// Output results
echo json_encode($results, JSON_PRETTY_PRINT);
?>
