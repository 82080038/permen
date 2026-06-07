<?php

use App\Core\App;
use App\Http\Response;
use App\Security\SecurityManager;
use App\Validation\Validator;

/**
 * Global helper functions that wrap class methods
 * This maintains backward compatibility while enabling modern architecture
 */

if (!function_exists('csrfToken')) {
    function csrfToken(): string
    {
        return App::getInstance()->security()->csrfToken();
    }
}

if (!function_exists('validateCsrf')) {
    function validateCsrf(string $token): bool
    {
        return App::getInstance()->security()->validateCsrf($token);
    }
}

if (!function_exists('validateCsrfApi')) {
    function validateCsrfApi(): bool
    {
        return App::getInstance()->security()->validateCsrfApi();
    }
}

if (!function_exists('checkRateLimit')) {
    function checkRateLimit(string $ip): bool
    {
        return App::getInstance()->security()->checkRateLimit($ip);
    }
}

if (!function_exists('incrementRateLimit')) {
    function incrementRateLimit(string $ip): void
    {
        App::getInstance()->security()->incrementRateLimit($ip);
    }
}

if (!function_exists('e')) {
    function e(string $text): string
    {
        return SecurityManager::e($text);
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput(string $input): string
    {
        return SecurityManager::sanitize($input);
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? '') === 'admin';
    }
}

if (!function_exists('requireAuth')) {
    function requireAuth(): void
    {
        if (!isLoggedIn()) {
            Response::redirect('/permen/pages/login.php');
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin(): void
    {
        requireAuth();
        if (!isAdmin()) {
            Response::error('Akses ditolak. Hanya admin.', 403);
        }
    }
}

if (!function_exists('validateInput')) {
    function validateInput(array $data): Validator
    {
        return new Validator($data);
    }
}
