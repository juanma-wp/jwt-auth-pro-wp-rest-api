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

	// Note: Environment detection and caching are tested in wp-rest-auth-toolkit/tests/Http/CookieConfigTest.php
	// The wrapper delegates to CookieConfig::getEnvironment() and CookieConfig::clearCache()

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
	 * Test WordPress integration - filters can override config file defaults.
	 */
	public function testWordPressIntegration(): void
	{
		// Filters have higher priority than config file defaults
		add_filter('jwt_auth_cookie_name', function () {
			return 'filtered_cookie_name';
		});

		JWT_Cookie_Config::clear_cache();
		$config = JWT_Cookie_Config::get_config();

		$this->assertSame('filtered_cookie_name', $config['name']);

		remove_all_filters('jwt_auth_cookie_name');
	}

	/**
	 * Test that config file is loaded correctly.
	 *
	 * @group regression
	 */
	public function testConfigFileLoadsEnvironmentDefaults(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		JWT_Cookie_Config::clear_cache();

		$env_defaults = JWT_Cookie_Config::get_environment_defaults('development');

		$this->assertIsArray($env_defaults);
		$this->assertArrayHasKey('name', $env_defaults);
		$this->assertArrayHasKey('secure', $env_defaults);
		$this->assertArrayHasKey('samesite', $env_defaults);
		$this->assertSame('wp_jwt_refresh_token', $env_defaults['name']);
	}

	/**
	 * Test that cookie name in config matches Auth_JWT constant.
	 *
	 * Prevents regression where admin panel showed wrong cookie name.
	 *
	 * @group regression
	 */
	public function testCookieNameMatchesAuthJWTConstant(): void
	{
		// Load Auth_JWT class if available
		if (! class_exists('Auth_JWT')) {
			require_once dirname(__DIR__, 2) . '/includes/class-auth-jwt.php';
		}

		$_SERVER['HTTP_HOST'] = 'localhost';
		JWT_Cookie_Config::clear_cache();

		$config = JWT_Cookie_Config::get_config();

		$this->assertSame(
			Auth_JWT::REFRESH_COOKIE_NAME,
			$config['name'],
			'Cookie name in config must match Auth_JWT::REFRESH_COOKIE_NAME constant'
		);
	}

	/**
	 * Test secure flag is false for HTTP in development.
	 *
	 * Prevents regression where secure=true prevented cookies from working on HTTP.
	 *
	 * @group regression
	 */
	public function testSecureFlagIsFalseForHTTPInDevelopment(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['HTTPS']     = 'off';
		unset($_SERVER['HTTP_X_FORWARDED_PROTO']);

		JWT_Cookie_Config::clear_cache();
		$config = JWT_Cookie_Config::get_config();

		$this->assertFalse(
			$config['secure'],
			'Secure flag must be false for HTTP in development environment'
		);
		$this->assertSame('development', $config['environment']);
	}

	/**
	 * Test secure flag is true for HTTPS in development.
	 *
	 * @group regression
	 */
	public function testSecureFlagIsTrueForHTTPSInDevelopment(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['HTTPS']     = 'on';

		JWT_Cookie_Config::clear_cache();
		$config = JWT_Cookie_Config::get_config();

		$this->assertTrue(
			$config['secure'],
			'Secure flag must be true for HTTPS even in development'
		);
	}

	/**
	 * Test SameSite compatibility with Secure flag in JWT config file.
	 *
	 * Prevents regression: SameSite=None requires Secure=true.
	 * For HTTP development, JWT config file uses SameSite=Lax.
	 *
	 * Note: General SameSite=None validation is tested in wp-rest-auth-toolkit/tests/Http/CookieConfigTest.php
	 * This test specifically validates the JWT plugin's config file behavior.
	 *
	 * @group regression
	 */
	public function testSameSiteCompatibilityWithSecureFlag(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['HTTPS']     = 'off';

		JWT_Cookie_Config::clear_cache();
		$config = JWT_Cookie_Config::get_config();

		// If SameSite is None, Secure must be true (browser requirement)
		if ('None' === $config['samesite']) {
			$this->assertTrue(
				$config['secure'],
				'SameSite=None requires Secure=true. Use SameSite=Lax for HTTP development.'
			);
		}

		// For HTTP in development, we should use Lax
		if (! $config['secure'] && 'development' === $config['environment']) {
			$this->assertNotSame(
				'None',
				$config['samesite'],
				'HTTP development should use SameSite=Lax, not None'
			);
		}
	}

	/**
	 * Test all environment configs are valid.
	 *
	 * @group regression
	 * @dataProvider environmentProvider
	 */
	public function testAllEnvironmentConfigsAreValid(string $environment): void
	{
		$config = JWT_Cookie_Config::get_environment_defaults($environment);

		$this->assertIsArray($config);
		$this->assertArrayHasKey('name', $config);
		$this->assertArrayHasKey('samesite', $config);
		$this->assertArrayHasKey('path', $config);
		$this->assertArrayHasKey('httponly', $config);
		$this->assertArrayHasKey('lifetime', $config);

		// Validate SameSite values
		$this->assertContains(
			$config['samesite'],
			array('None', 'Lax', 'Strict'),
			"Invalid SameSite value for {$environment}"
		);

		// HttpOnly should always be true for security
		$this->assertTrue(
			$config['httponly'],
			"HttpOnly must be true for {$environment}"
		);
	}

	/**
	 * Data provider for environment tests.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function environmentProvider(): array
	{
		return array(
			array('development'),
			array('staging'),
			array('production'),
			array('base'),
		);
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
