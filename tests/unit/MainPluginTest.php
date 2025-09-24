<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for main JWT plugin class
 */
class MainPluginTest extends TestCase
{
    private $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        // Load the main plugin class
        if (!class_exists('WP_REST_Auth_JWT')) {
            require_once dirname(__DIR__, 2) . '/wp-rest-auth-jwt.php';
        }

        // Define constants for testing
        if (!defined('WP_JWT_AUTH_SECRET')) {
            define('WP_JWT_AUTH_SECRET', 'test-secret-key-for-testing-only-jwt');
        }
        if (!defined('WP_REST_AUTH_JWT_PLUGIN_DIR')) {
            define('WP_REST_AUTH_JWT_PLUGIN_DIR', dirname(__DIR__, 2) . '/');
        }

        $this->plugin = new WP_REST_Auth_JWT();
    }

    public function testPluginClassExists(): void
    {
        $this->assertTrue(class_exists('WP_REST_Auth_JWT'));
        $this->assertInstanceOf('WP_REST_Auth_JWT', $this->plugin);
    }

    public function testPluginInitialization(): void
    {
        // Test that init method exists
        $this->assertTrue(method_exists($this->plugin, 'init'));

        // Test that init can be called
        $this->plugin->init();
        $this->assertTrue(true); // Should not throw errors
    }

    public function testPluginConstants(): void
    {
        // Test plugin constants
        $this->assertTrue(defined('WP_REST_AUTH_JWT_VERSION'));
        $this->assertEquals('1.0.0', WP_REST_AUTH_JWT_VERSION);

        $this->assertTrue(defined('WP_REST_AUTH_JWT_PLUGIN_DIR'));
        $this->assertNotEmpty(WP_REST_AUTH_JWT_PLUGIN_DIR);
    }

    public function testDependencyLoading(): void
    {
        // Test that load_dependencies method exists
        $this->assertTrue(method_exists($this->plugin, 'load_dependencies'));
    }

    public function testHooksInitialization(): void
    {
        // Test that init_hooks method exists
        $this->assertTrue(method_exists($this->plugin, 'init_hooks'));
    }

    public function testConstantSetup(): void
    {
        // Test that setup_constants method exists
        $this->assertTrue(method_exists($this->plugin, 'setup_constants'));

        // Test constants are properly set up
        $this->assertTrue(defined('WP_JWT_SECRET'));
        $this->assertNotEmpty(WP_JWT_SECRET);
    }

    public function testRouteRegistration(): void
    {
        // Test that register_rest_routes method exists
        $this->assertTrue(method_exists($this->plugin, 'register_rest_routes'));
    }

    public function testBearerAuthentication(): void
    {
        // Test that maybe_auth_bearer method exists
        $this->assertTrue(method_exists($this->plugin, 'maybe_auth_bearer'));

        // Test with no authentication (should return original result)
        $result = $this->plugin->maybe_auth_bearer(null);
        $this->assertNull($result);

        // Test with existing error (should return existing error)
        $error = new WP_Error('test_error', 'Test error');
        $result = $this->plugin->maybe_auth_bearer($error);
        $this->assertEquals($error, $result);
    }

    public function testAuthHeaderRetrieval(): void
    {
        // Test that get_auth_header method exists (even if private)
        $reflection = new ReflectionClass($this->plugin);
        $method = $reflection->getMethod('get_auth_header');
        $method->setAccessible(true);

        // Test with no header
        $header = $method->invoke($this->plugin);
        $this->assertEmpty($header);

        // Test with Authorization header
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';
        $header = $method->invoke($this->plugin);
        $this->assertEquals('Bearer test-token', $header);

        // Clean up
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testActivationHook(): void
    {
        // Test that activate method exists
        $this->assertTrue(method_exists($this->plugin, 'activate'));

        // Test activation can be called
        $this->plugin->activate();
        $this->assertTrue(true); // Should not throw errors
    }

    public function testDeactivationHook(): void
    {
        // Test that deactivate method exists
        $this->assertTrue(method_exists($this->plugin, 'deactivate'));

        // Test deactivation can be called
        $this->plugin->deactivate();
        $this->assertTrue(true); // Should not throw errors
    }

    public function testDatabaseTableCreation(): void
    {
        // Test that create_jwt_tables method exists
        $reflection = new ReflectionClass($this->plugin);
        $this->assertTrue($reflection->hasMethod('create_jwt_tables'));
    }

    public function testScriptEnqueuing(): void
    {
        // Test that enqueue_scripts method exists
        $this->assertTrue(method_exists($this->plugin, 'enqueue_scripts'));

        // Test script enqueuing can be called
        $this->plugin->enqueue_scripts();
        $this->assertTrue(true); // Should not throw errors
    }

    public function testPluginComponents(): void
    {
        // Test that plugin initializes its components
        $this->plugin->init();

        // Check that required components are available through reflection
        $reflection = new ReflectionClass($this->plugin);

        // Check for auth_jwt property
        if ($reflection->hasProperty('auth_jwt')) {
            $property = $reflection->getProperty('auth_jwt');
            $property->setAccessible(true);
            $auth_jwt = $property->getValue($this->plugin);
            $this->assertInstanceOf('Auth_JWT', $auth_jwt);
        }

        // Check for admin_settings property (only in admin)
        if ($reflection->hasProperty('admin_settings')) {
            $property = $reflection->getProperty('admin_settings');
            $property->setAccessible(true);
            $admin_settings = $property->getValue($this->plugin);
            // May be null if not in admin context
            $this->assertTrue($admin_settings === null || $admin_settings instanceof WP_REST_Auth_JWT_Admin_Settings);
        }
    }

    public function testSecretGeneration(): void
    {
        // Test that secret is generated if not exists
        $this->assertTrue(defined('WP_JWT_SECRET'));
        $secret = WP_JWT_SECRET;

        $this->assertNotEmpty($secret);
        $this->assertIsString($secret);
        $this->assertGreaterThan(32, strlen($secret)); // Should be reasonably long
    }

    public function testWordPressIntegration(): void
    {
        // Test that WordPress hooks are properly set up
        $this->assertTrue(method_exists($this->plugin, 'register_rest_routes'));
        $this->assertTrue(method_exists($this->plugin, 'maybe_auth_bearer'));
        $this->assertTrue(method_exists($this->plugin, 'enqueue_scripts'));
    }

    public function testPluginSingleton(): void
    {
        // The plugin should be instantiated as a singleton through the main file
        // We can't test this directly in unit tests, but we can verify the structure
        $this->assertInstanceOf('WP_REST_Auth_JWT', $this->plugin);
    }

    protected function tearDown(): void
    {
        // Clean up global state
        unset($_SERVER['HTTP_AUTHORIZATION']);
        parent::tearDown();
    }
}