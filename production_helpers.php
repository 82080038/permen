<?php
/**
 * Helper Functions — SKD CAT-BKN
 * Fungsi reusable untuk seluruh aplikasi
 */

/**
 * Generate CSRF token
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF token dari form POST
 */
function validateCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF token for API endpoints (double submit cookie pattern)
 * @return bool True if valid, false otherwise
 */
function validateCsrfApi(): bool
{
    $headers = getallheaders();
    $token = $headers['X-CSRF-Token'] ?? $headers['X-CSRF-Token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (empty($token)) {
        return false;
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token for API clients
 * @return string CSRF token
 */
function getCsrfTokenForApi(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get landing statistics for homepage
 * @return array Statistics data
 */
function getLandingStats(): array
{
    try {
        // Database connection
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // Get user count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $userCount = $stmt->fetch()['count'];

        // Get tryout count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tryout_sessions WHERE status = 'completed'");
        $stmt->execute();
        $tryoutCount = $stmt->fetch()['count'];

        // Get question count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions");
        $stmt->execute();
        $questionCount = $stmt->fetch()['count'];

        // Get active users (last 24 hours)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as count FROM tryout_sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        $activeUsers = $stmt->fetch()['count'];

        return [
            'user_count' => $userCount,
            'tryout_count' => $tryoutCount,
            'question_count' => $questionCount,
            'active_users' => $activeUsers
        ];

    } catch (Exception $e) {
        // Return default values on error
        error_log("getLandingStats error: " . $e->getMessage());
        return [
            'user_count' => 0,
            'tryout_count' => 0,
            'question_count' => 0,
            'active_users' => 0
        ];
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("getCurrentUser error: " . $e->getMessage());
        return null;
    }
}

/**
 * Redirect to URL
 * @param string $url
 * @return void
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit();
}

/**
 * Send JSON response
 * @param mixed $data
 * @param int $httpCode
 * @return void
 */
function jsonResponse($data, int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Escape output untuk HTML (XSS prevention shorthand)
 * @param string $text
 * @return string
 */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Send error response
 * @param string $message
 * @param int $httpCode
 * @return void
 */
function errorResponse(string $message, int $httpCode = 400): void
{
    jsonResponse(['error' => $message], $httpCode);
}

/**
 * Send success response
 * @param mixed $data
 * @return void
 */
function successResponse($data = null): void
{
    jsonResponse(['success' => true, 'data' => $data]);
}

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indonesia format)
 * @param string $phone
 * @return bool
 */
function isValidPhone(string $phone): bool
{
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it starts with Indonesian country code or 0
    return (preg_match('/^62[0-9]{9,12}$/', $phone) || preg_match('/^0[0-9]{9,12}$/', $phone));
}

/**
 * Format phone number to standard format
 * @param string $phone
 * @return string
 */
function formatPhone(string $phone): string
{
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // If starts with 0, replace with 62
    if (str_starts_with($phone, '0')) {
        $phone = '62' . substr($phone, 1);
    }
    
    return $phone;
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password
 * @param string $password
 * @return string
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Log activity
 * @param string $action
 * @param string $details
 * @param int|null $userId
 * @return void
 */
function logActivity(string $action, string $details = '', ?int $userId = null): void
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
    } catch (Exception $e) {
        error_log("logActivity error: " . $e->getMessage());
    }
}

/**
 * Get client IP address
 * @return string
 */
function getClientIp(): string
{
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Sanitize input
 * @param string $input
 * @return string
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate required fields
 * @param array $data
 * @param array $required
 * @return array
 */
function validateRequired(array $data, array $required): array
{
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[$field] = "Field $field is required";
        }
    }
    
    return $errors;
}

/**
 * Get pagination info
 * @param int $page
 * @param int $limit
 * @param int $total
 * @return array
 */
function getPaginationInfo(int $page, int $limit, int $total): array
{
    $totalPages = ceil($total / $limit);
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1
    ];
}

/**
 * Format date to Indonesian format
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDateIndo(string $date, string $format = 'd M Y'): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    $months = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    
    switch ($format) {
        case 'd M Y':
            return date('d', $timestamp) . ' ' . $months[date('n', $timestamp)] . ' ' . date('Y', $timestamp);
        case 'd/m/Y':
            return date('d/m/Y', $timestamp);
        default:
            return date($format, $timestamp);
    }
}

/**
 * Check if maintenance mode is active
 * @return bool
 */
function isMaintenanceMode(): bool
{
    $maintenanceFile = __DIR__ . '/.maintenance';
    return file_exists($maintenanceFile);
}

/**
 * Get time ago format
 * @param string $datetime
 * @return string
 */
function timeAgo(string $datetime): string
{
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit yang lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam yang lalu';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' hari yang lalu';
    } else {
        return floor($diff / 2592000) . ' bulan yang lalu';
    }
}

/**
 * Get setting value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting(string $key, $default = null)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['value'] : $default;
        
    } catch (Exception $e) {
        error_log("getSetting error: " . $e->getMessage());
        return $default;
    }
}

/**
 * Apply user settings (dark mode, font size, etc.)
 * @return void
 */
function applyUserSettings(): void
{
    if (isLoggedIn()) {
        try {
            $user = getCurrentUser();
            if ($user && !empty($user['settings'])) {
                $settings = json_decode($user['settings'], true);
                
                // Apply dark mode
                if (!empty($settings['dark_mode']) && $settings['dark_mode']) {
                    echo '<script>document.body.classList.add("dark-mode");</script>';
                }
                
                // Apply font size
                if (!empty($settings['font_size'])) {
                    $fontSizeClass = 'font-' . $settings['font_size'];
                    echo '<script>document.body.classList.add("' . $fontSizeClass . '");</script>';
                }
            }
        } catch (Exception $e) {
            // Silently fail - settings are optional
            error_log("Failed to apply user settings: " . $e->getMessage());
        }
    }
}
?>
