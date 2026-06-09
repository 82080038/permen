<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Security\SecurityManager;

/**
 * Unit Tests for SecurityManager
 * 
 * Tests CSRF protection, rate limiting, password hashing,
 * and input sanitization functionality.
 */
class SecurityManagerTest extends TestCase
{
    private SecurityManager $securityManager;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->securityManager = SecurityManager::getInstance();
    }
    
    /**
     * Test CSRF token generation
     */
    public function testCsrfTokenGeneration(): void
    {
        $token = $this->securityManager->csrfToken();
        
        // Token should be non-empty string
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // Token should be 32 characters (hex)
        $this->assertEquals(32, strlen($token));
        
        // Token should be hexadecimal
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
    }
    
    /**
     * Test CSRF token validation - valid token
     */
    public function testCsrfTokenValidationValid(): void
    {
        $token = $this->securityManager->csrfToken();
        
        // Token should be valid immediately after generation
        $this->assertTrue($this->securityManager->validateCsrf($token));
    }
    
    /**
     * Test CSRF token validation - invalid token
     */
    public function testCsrfTokenValidationInvalid(): void
    {
        $this->assertFalse($this->securityManager->validateCsrf('invalid_token'));
        $this->assertFalse($this->securityManager->validateCsrf(''));
        $this->assertFalse($this->securityManager->validateCsrf(null));
    }
    
    /**
     * Test CSRF token rotation
     */
    public function testCsrfTokenRotation(): void
    {
        $token1 = $this->securityManager->csrfToken();
        $token2 = $this->securityManager->csrfToken();
        
        // New token should be different from old
        $this->assertNotEquals($token1, $token2);
        
        // Only latest token should be valid
        $this->assertFalse($this->securityManager->validateCsrf($token1));
        $this->assertTrue($this->securityManager->validateCsrf($token2));
    }
    
    /**
     * Test password hashing
     */
    public function testPasswordHashing(): void
    {
        $password = 'TestPassword123!';
        $hash = $this->securityManager->hashPassword($password);
        
        // Hash should be non-empty string
        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
        
        // Hash should start with $2y$ (bcrypt)
        $this->assertStringStartsWith('$2y$', $hash);
        
        // Hash should be different each time (salt)
        $hash2 = $this->securityManager->hashPassword($password);
        $this->assertNotEquals($hash, $hash2);
    }
    
    /**
     * Test password verification - correct password
     */
    public function testPasswordVerificationCorrect(): void
    {
        $password = 'TestPassword123!';
        $hash = $this->securityManager->hashPassword($password);
        
        $this->assertTrue($this->securityManager->verifyPassword($password, $hash));
    }
    
    /**
     * Test password verification - incorrect password
     */
    public function testPasswordVerificationIncorrect(): void
    {
        $password = 'TestPassword123!';
        $hash = $this->securityManager->hashPassword($password);
        
        $this->assertFalse($this->securityManager->verifyPassword('WrongPassword', $hash));
        $this->assertFalse($this->securityManager->verifyPassword('', $hash));
    }
    
    /**
     * Test input sanitization - HTML entities
     */
    public function testSanitizeInputHtml(): void
    {
        $input = '<script>alert("xss")</script>';
        $sanitized = $this->securityManager->sanitizeInput($input);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('&lt;script&gt;', $sanitized);
    }
    
    /**
     * Test input sanitization - trim whitespace
     */
    public function testSanitizeInputTrim(): void
    {
        $input = '  test input  ';
        $sanitized = $this->securityManager->sanitizeInput($input);
        
        $this->assertEquals('test input', $sanitized);
    }
    
    /**
     * Test rate limit check - within limit
     */
    public function testRateLimitWithinLimit(): void
    {
        // Reset rate limit for test IP
        $ip = '127.0.0.1';
        
        // First request should pass
        $this->assertTrue($this->securityManager->checkRateLimit($ip));
    }
    
    /**
     * Test rate limit check - exceeded
     */
    public function testRateLimitExceeded(): void
    {
        $ip = '192.168.1.1';
        
        // Simulate many requests
        for ($i = 0; $i < 101; $i++) {
            $this->securityManager->checkRateLimit($ip);
        }
        
        // 101st request should fail
        $this->assertFalse($this->securityManager->checkRateLimit($ip));
    }
    
    /**
     * Test singleton pattern
     */
    public function testSingleton(): void
    {
        $instance1 = SecurityManager::getInstance();
        $instance2 = SecurityManager::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }
    
    /**
     * Test account lockout check - not locked
     */
    public function testAccountLockoutNotLocked(): void
    {
        $status = $this->securityManager->checkAccountLockout('testuser123');
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('locked', $status);
        $this->assertFalse($status['locked']);
    }
}
