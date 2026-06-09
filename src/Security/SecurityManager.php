<?php

declare(strict_types=1);

namespace App\Security;

use PDO;

/**
 * Security utilities: CSRF, rate limiting, sanitization
 */
class SecurityManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Generate CSRF token
     */
    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token from form POST
     */
    public function validateCsrf(?string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }

    /**
     * Validate CSRF token for API (header based)
     */
    public function validateCsrfApi(): bool
    {
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        return $this->validateCsrf($token);
    }

    /**
     * Check rate limit (max 5 attempts per 15 minutes)
     */
    public function checkRateLimit(string $ip): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT count, created_at FROM rate_limits WHERE ip = ? ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->execute([$ip]);
            $data = $stmt->fetch();

            if (!$data || time() - strtotime($data['created_at']) > 900) {
                return true;
            }

            return (int)$data['count'] < 5;
        } catch (\PDOException $e) {
            error_log("Rate limit error: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Increment rate limit counter
     */
    public function incrementRateLimit(string $ip): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT count, created_at FROM rate_limits WHERE ip = ? ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->execute([$ip]);
            $data = $stmt->fetch();

            if (!$data || time() - strtotime($data['created_at']) > 900) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO rate_limits (ip, count, created_at) VALUES (?, 1, NOW())"
                );
                $stmt->execute([$ip]);
            } else {
                $stmt = $this->pdo->prepare(
                    "UPDATE rate_limits SET count = count + 1 WHERE ip = ? AND created_at = ?"
                );
                $stmt->execute([$ip, $data['created_at']]);
            }
        } catch (\PDOException $e) {
            error_log("Rate limit increment error: " . $e->getMessage());
        }
    }

    /**
     * Sanitize HTML output
     */
    public static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize input string
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash password securely
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
