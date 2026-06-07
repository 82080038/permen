<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Basic tests to verify autoloading and core classes are working
 */
class CoreTest extends TestCase
{
    public function testAutoloading(): void
    {
        $this->assertTrue(class_exists('App\Core\App'));
        $this->assertTrue(class_exists('App\Database\Database'));
        $this->assertTrue(class_exists('App\Security\SecurityManager'));
        $this->assertTrue(class_exists('App\Validation\Validator'));
        $this->assertTrue(class_exists('App\Http\Response'));
    }

    public function testSecuritySanitize(): void
    {
        $input = '<script>alert("xss")</script>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';

        $this->assertEquals($expected, \App\Security\SecurityManager::e($input));
    }

    public function testValidatorRequired(): void
    {
        $validator = new \App\Validation\Validator(['email' => '']);
        $validator->rule('email', 'required|email');

        $this->assertFalse($validator->validate());
        $this->assertNotEmpty($validator->errors());
    }

    public function testValidatorEmailValid(): void
    {
        $validator = new \App\Validation\Validator(['email' => 'test@example.com']);
        $validator->rule('email', 'required|email');

        $this->assertTrue($validator->validate());
        $this->assertEmpty($validator->errors());
    }
}
