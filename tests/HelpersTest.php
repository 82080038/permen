<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers.php';

class HelpersTest extends TestCase
{
    public function testValidatePasswordStrength()
    {
        // Test valid password
        $this->assertTrue(validatePasswordStrength('Password123'));
        
        // Test invalid passwords
        $this->assertFalse(validatePasswordStrength('short')); // Too short
        $this->assertFalse(validatePasswordStrength('nouppercase123')); // No uppercase
        $this->assertFalse(validatePasswordStrength('NOLOWERCASE123')); // No lowercase
        $this->assertFalse(validatePasswordStrength('NoDigits')); // No digits
    }
    
    public function testSanitizeInput()
    {
        // Test XSS prevention
        $input = '<script>alert("xss")</script>';
        $sanitized = sanitizeInput($input);
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('alert', $sanitized);
    }
    
    public function testCsrfTokenGeneration()
    {
        $token1 = csrfToken();
        $token2 = csrfToken();
        
        // Tokens should be the same within the same session
        $this->assertEquals($token1, $token2);
        
        // Token should be a non-empty string
        $this->assertNotEmpty($token1);
        $this->assertIsString($token1);
    }
}
