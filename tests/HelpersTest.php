<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers.php';

class HelpersTest extends TestCase
{
    public function testValidatePasswordStrength()
    {
        // Test valid password (6+ characters)
        $result = validatePasswordStrength('123456');
        $this->assertTrue($result['valid']);
        $this->assertEquals('', $result['error']);

        $result = validatePasswordStrength('abcdef');
        $this->assertTrue($result['valid']);
        $this->assertEquals('', $result['error']);

        $result = validatePasswordStrength('abc123');
        $this->assertTrue($result['valid']);
        $this->assertEquals('', $result['error']);

        // Test invalid passwords (less than 6 characters)
        $result = validatePasswordStrength('12345');
        $this->assertFalse($result['valid']);

        $result = validatePasswordStrength('abcde');
        $this->assertFalse($result['valid']);

        $result = validatePasswordStrength('123');
        $this->assertFalse($result['valid']);
    }
    
    public function testSanitizeInput()
    {
        // Test that sanitizeInput removes control characters and null bytes
        $input = "test\x00string\x1F";
        $sanitized = sanitizeInput($input);
        $this->assertStringNotContainsString("\x00", $sanitized);
        $this->assertStringNotContainsString("\x1F", $sanitized);
        $this->assertEquals('teststring', $sanitized);

        // Test trimming
        $input = "  test  ";
        $sanitized = sanitizeInput($input);
        $this->assertEquals('test', $sanitized);
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
