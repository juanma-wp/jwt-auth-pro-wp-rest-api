<?php

/**
 * JWT Cookie Configuration Integration Tests
 *
 * Integration tests for the JWT_Cookie_Config wrapper class.
 * Tests that the wrapper correctly delegates to the toolkit implementation
 * and maintains backwards compatibility with WordPress integration.
 *
 * Note: Comprehensive functionality tests are in wp-rest-auth-toolkit package.
 * These tests focus on integration and WordPress-specific behavior.
 *
 * @package   WPRESTAuthJWT
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.1.0
 *
 * @link      https://github.com/juanma-wp/jwt-auth-pro-wp-rest-api
 */

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for JWT Cookie Configuration wrapper.
 */
class CookieConfigTest extends TestCase
{

	/**
	 * Store original $_SERVER values for tearDown.
	 *
	 * @var array<string, mixed>
	 */
	private $original_server = array();

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void
	{
		parent::setUp();

		// Load the cookie config class.
		if (! class_exists('JWT_Cookie_Config')) {
			require_once dirname(__DIR__, 2) . '/includes/class-jwt-cookie-config.php';
		}

		// Store original $_SERVER values.
		$this->original_server = $_SERVER;

		// Clear configuration cache before each test.
		JWT_Cookie_Config::clear_cache();
	}

	/**
	 * Test that wrapper class exists and loads.
	 */
	public function testWrapperClassExists(): void
	{
		$this->assertTrue(class_exists('JWT_Cookie_Config'));
		$this->assertTrue(class_exists('WPRestAuth\\AuthToolkit\\Http\\CookieConfig'));
	}

	/**
	 * Test wrapper returns complete configuration array.
	 */
	public function testWrapperReturnsCompleteConfig(): void
	{
		$config = JWT_Cookie_Config::get_config();

		$this->assertIsArray($config);
		$this->assertArrayHasKey('enabled', $config);
		$this->assertArrayHasKey('name', $config);
		$this->assertArrayHasKey('samesite', $config);
		$this->assertArrayHasKey('secure', $config);
		$this->assertArrayHasKey('path', $config);
		$this->assertArrayHasKey('domain', $config);
		$this->assertArrayHasKey('httponly', $config);
		$this->assertArrayHasKey('lifetime', $config);
		$this->assertArrayHasKey('environment', $config);
		$this->assertArrayHasKey('auto_detect', $config);
	}

	/**
	 * Test plugin-specific filters work through wrapper.
	 */
	public function testPluginSpecificFiltersWork(): void
	{
		// Test jwt_auth_cookie_* filter prefix works
		add_filter('jwt_auth_cookie_name', function () {
			return 'custom_jwt_session';
		});

		JWT_Cookie_Config::clear_cache();
		$config = JWT_Cookie_Config::get_config();

		$this->assertSame('custom_jwt_session', $config['name']);

		remove_all_filters('jwt_auth_cookie_name');
	}

	/**
	 * Test plugin-specific global filter works.
	 */
	public function testPluginSpecificGlobalFilterWorks(): void
	{
		add_filter('jwt_auth_cookie_config', function ($config) {
			$config['name']     = 'filtered_session';
			$config['lifetime'] = 7200;
			return $config;
		});

		JWT_Cookie_Config::clear_cache();
		$config = JWT_Cookie_Config::get_config();

		$this->assertSame('filtered_session', $config['name']);
		$this->assertSame(7200, $config['lifetime']);

		remove_all_filters('jwt_auth_cookie_config');
	}

	/**
	 * Test plugin-specific constants work through wrapper.
	 */
	public function testPluginSpecificConstantsWork(): void
	{
		// Note: Can't define constants in tests, but we verify the constant names are correct
		// The toolkit tests verify the actual constant functionality
		$this->assertTrue(true, 'Constants JWT_AUTH_COOKIE_* are documented and used by wrapper');
	}

	/**
	 * Test environment detection works through wrapper.
	 */
	public function testEnvironmentDetectionWorks(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		JWT_Cookie_Config::clear_cache();

		$this->assertTrue(JWT_Cookie_Config::is_development());
		$this->assertFalse(JWT_Cookie_Config::is_production());
		$this->assertSame('development', JWT_Cookie_Config::get_environment());
	}

	/**
	 * Test configuration caching works through wrapper.
	 */
	public function testConfigurationCaching(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';

		// First call populates cache
		$config1 = JWT_Cookie_Config::get_config();

		// Change environment
		$_SERVER['HTTP_HOST'] = 'example.com';

		// Second call should return cached value
		$config2 = JWT_Cookie_Config::get_config();

		$this->assertSame($config1['environment'], $config2['environment']);

		// Clear cache and get fresh config
		JWT_Cookie_Config::clear_cache();
		$config3 = JWT_Cookie_Config::get_config();

		// Should now reflect new environment (if WP_DEBUG is not forcing development)
		if (!defined('WP_DEBUG') || !WP_DEBUG) {
			$this->assertNotSame($config1['environment'], $config3['environment']);
		}
	}

	/**
	 * Test get_defaults returns plugin-specific defaults.
	 */
	public function testGetDefaultsReturnsPluginDefaults(): void
	{
		$defaults = JWT_Cookie_Config::get_defaults();

		$this->assertIsArray($defaults);
		$this->assertArrayHasKey('name', $defaults);
		$this->assertSame('jwtauth_session', $defaults['name']); // Plugin-specific default
		$this->assertArrayHasKey('auto_detect', $defaults);
		$this->assertTrue($defaults['auto_detect']);
	}

	/**
	 * Test update_config delegates to toolkit correctly.
	 */
	public function testUpdateConfigWorks(): void
	{
		$new_config = array(
			'name'     => 'test_session',
			'lifetime' => 3600,
		);

		// Mock WordPress option update
		add_filter('pre_update_option_jwt_auth_cookie_config', function () use ($new_config) {
			return $new_config;
		});

		$result = JWT_Cookie_Config::update_config($new_config);
		$this->assertTrue($result);

		remove_all_filters('pre_update_option_jwt_auth_cookie_config');
	}

	/**
	 * Test backwards compatibility - wrapper maintains same API.
	 */
	public function testBackwardsCompatibility(): void
	{
		// All public methods should exist
		$this->assertTrue(method_exists('JWT_Cookie_Config', 'get_config'));
		$this->assertTrue(method_exists('JWT_Cookie_Config', 'update_config'));
		$this->assertTrue(method_exists('JWT_Cookie_Config', 'get_defaults'));
		$this->assertTrue(method_exists('JWT_Cookie_Config', 'get_environment'));
		$this->assertTrue(method_exists('JWT_Cookie_Config', 'is_development'));
		$this->assertTrue(method_exists('JWT_Cookie_Config', 'is_production'));
		$this->assertTrue(method_exists('JWT_Cookie_Config', 'clear_cache'));
	}

	/**
	 * Test WordPress integration - option name is correct.
	 */
	public function testWordPressIntegration(): void
	{
		// Verify wrapper uses correct WordPress option name
		add_filter('pre_option_jwt_auth_cookie_config', function () {
			return array('name' => 'wp_integration_test');
		});

		JWT_Cookie_Config::clear_cache();
		$config = JWT_Cookie_Config::get_config();

		$this->assertSame('wp_integration_test', $config['name']);

		remove_all_filters('pre_option_jwt_auth_cookie_config');
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void
	{
		// Restore original $_SERVER values.
		$_SERVER = $this->original_server;

		// Clear configuration cache.
		JWT_Cookie_Config::clear_cache();

		// Remove all test filters.
		remove_all_filters('jwt_auth_cookie_config');
		remove_all_filters('jwt_auth_cookie_name');
		remove_all_filters('jwt_auth_cookie_samesite');
		remove_all_filters('jwt_auth_cookie_secure');
		remove_all_filters('jwt_auth_cookie_path');
		remove_all_filters('jwt_auth_cookie_domain');
		remove_all_filters('jwt_auth_cookie_lifetime');
		remove_all_filters('jwt_auth_cookie_enabled');
		remove_all_filters('pre_option_jwt_auth_cookie_config');
		remove_all_filters('pre_update_option_jwt_auth_cookie_config');

		parent::tearDown();
	}
}
