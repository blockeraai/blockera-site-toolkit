<?php

namespace Blockera\SiteToolkit\Tests;

use BlockeraAI\SiteToolkit\Setup;
use Blockera\Dev\PHPUnit\AppTestCase;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\AuthorizationServer;

class SetupTest extends AppTestCase
{
    private Setup $setup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setup = new Setup();
    }

    public function testPluginDirectoryManagement(): void
    {
        $dir = '/test/plugin/dir';
        $this->setup->setPluginDir($dir);
        $this->assertEquals($dir, $this->setup->getPath());
    }

    public function testPluginUrlManagement(): void
    {
        $url = 'https://example.com/plugin';
        $this->setup->setPluginUrl($url);
        $this->assertEquals($url, $this->setup->getURL());
    }

    public function testPluginModeManagement(): void
    {
        // Test development mode
        $this->setup->setPluginMode('development');
        $this->assertEquals('development', $this->setup->getPluginMode());
        $this->assertTrue($this->setup->isDebug());

        // Test production mode
        $this->setup->setPluginMode('production');
        $this->assertEquals('production', $this->setup->getPluginMode());
        $this->assertFalse($this->setup->isDebug());
    }

    public function testPluginFileManagement(): void
    {
        $file = 'plugin.php';
        $this->setup->setPluginFile($file);
        $this->assertEquals($file, $this->setup->getPluginFile());
    }

    public function testAuthorizationServerManagement(): void
    {
		/**
		 * @var League\OAuth2\Server\AuthorizationServer $server
		 */
        $server = $this->createMock(AuthorizationServer::class);
        $this->setup->setAuthorizationServer($server);
        $this->assertSame($server, $this->setup->getAuthorizationServer());
    }

    public function testResourceServerManagement(): void
    {
		/**
		 * @var League\OAuth2\Server\ResourceServer $server
		 */
        $server = $this->createMock(ResourceServer::class);
        $this->setup->setResourceServer($server);
        $this->assertSame($server, $this->setup->getResourceServer());
    }

    public function testMountAndUnmount(): void
    {
        $file = 'plugin.php';
        $this->setup->setPluginFile($file);

        // Test mount
        $result = $this->setup->mount();
        $this->assertInstanceOf(Setup::class, $result);
        $this->assertEquals(10, has_action('activate_' . $file, [$this->setup, 'activate']));

        // Test unmount
        $result = $this->setup->unmount();
        $this->assertInstanceOf(Setup::class, $result);
        $this->assertEquals(10, has_action('deactivate_' . $file, 'flush_rewrite_rules'));
    }

    public function testRewriteRules(): void
    {
        global $wp_rewrite;

        // Create a mock for $wp_rewrite if it doesn't exist
        if (!isset($wp_rewrite)) {
            $wp_rewrite = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['flush_rules'])
                ->getMock();

            $wp_rewrite->expects($this->once())
                ->method('flush_rules');
        }

        // Test that rewrite rules are added
        $this->setup->rewriteRules();

        // Verify the rewrite rules were added
        $this->assertNotEmpty($wp_rewrite->extra_rules_top);
        $this->assertArrayHasKey('^authorize/?$', $wp_rewrite->extra_rules_top);
        $this->assertArrayHasKey('^consent-form/?$', $wp_rewrite->extra_rules_top);
        $this->assertEquals('index.php?authorize=true', $wp_rewrite->extra_rules_top['^authorize/?$']);
        $this->assertEquals('index.php?consent-form=true', $wp_rewrite->extra_rules_top['^consent-form/?$']);
    }

    public function testRegisterRoutes(): void
    {
		global $wp_rewrite;

        $this->setup->setPluginDir(dirname(__PACKAGES_DIR__));

        $this->setup->registerRoutes();

        // Test REST API routes registration
        $this->assertEquals(10 ,has_action('rest_api_init', [$this->setup, 'registerRestRoutes']));

        // Verify rewrite rules are set
        $this->assertArrayHasKey('^authorize/?$', $wp_rewrite->extra_rules_top);
        $this->assertArrayHasKey('^consent-form/?$', $wp_rewrite->extra_rules_top);
    }

    public function testActivation(): void
    {
        global $wp_rewrite;

        // Create a mock for $wp_rewrite if it doesn't exist
        if (!isset($wp_rewrite)) {
            $wp_rewrite = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['flush_rules'])
                ->getMock();
        }

        $this->setup->activate();

        // Verify rewrite rules are set
        $this->assertArrayHasKey('^authorize/?$', $wp_rewrite->extra_rules_top);
        $this->assertArrayHasKey('^consent-form/?$', $wp_rewrite->extra_rules_top);
    }
}
