<?php

namespace BlockeraAI\SiteToolkit\Tests;

use Blockera\Dev\PHPUnit\AppTestCase;

class FunctionsTest extends AppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Mock WordPress functions
        if (!function_exists('wp_generate_uuid4')) {
            function wp_generate_uuid4()
            {
                return '12345678-1234-5678-1234-567812345678';
            }
        }

        if (!function_exists('wp_generate_password')) {
            function wp_generate_password()
            {
                return 'test_password';
            }
        }

        if (!function_exists('home_url')) {
            function home_url($path = '')
            {
                return 'https://example.com' . $path;
            }
        }
    }

    public function testBsaGetRegisterClientParams()
    {
        // Test case 1: With referer
        $_SERVER['HTTP_REFERER'] = 'https://example.com?redirect_to=/test?redirect_uri=https://client.com';

        $result = bsaGetRegisterClientParams();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('client_id', $result);
        $this->assertArrayHasKey('client_secret', $result);
        $this->assertArrayHasKey('domain', $result);

        // Test case 2: Without referer
        $_POST['domain'] = 'https://test.com';
        $_POST['redirect_uri'] = 'https://test.com/callback';

        $result = bsaGetRegisterClientParams(false);

        $this->assertEquals('https://test.com', $result['domain']);
        $this->assertEquals('https://test.com/callback', $result['redirect_uri']);
    }

    public function testBsaGetAccessTokenIdentifier()
    {
        // Create a mock JWT token
        $payload = ['jti' => 'test_identifier'];
        $encodedPayload = base64_encode(json_encode($payload));
        $mockToken = "header.{$encodedPayload}.signature";

        $result = bsaGetAccessTokenIdentifier($mockToken);

        $this->assertEquals('test_identifier', $result);
    }

    public function testBsaGetEnv()
    {
        $_ENV['TEST_KEY'] = 'test_value';

        $result = bsaGetEnv('TEST_KEY');

        $this->assertEquals('test_value', $result);
        $this->assertEquals('', bsaGetEnv('NON_EXISTENT_KEY'));
    }

    public function testBsaFilterActiveLicenses()
    {
        $licenses = [
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => 'deleted'],
            ['id' => 3, 'status' => 'active'],
        ];

        $result = bsaFilterActiveLicenses($licenses);

        $this->assertCount(2, $result);
        $this->assertEquals('active', reset($result)['status']);
    }

    public function testBsaGetConfig()
    {
        // Test ENV variable
        $_ENV['TEST_CONFIG'] = 'env_value';
        $this->assertEquals('env_value', bsaGetConfig('TEST_CONFIG'));

        // Test constant
        define('TEST_CONSTANT', 'constant_value');
        $this->assertEquals('constant_value', bsaGetConfig('TEST_CONSTANT'));

        // Test non-existent value
        $this->assertEquals('', bsaGetConfig('NON_EXISTENT'));
    }
}
