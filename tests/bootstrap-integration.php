<?php

/**
 * PHPUnit Bootstrap for Integration Tests
 *
 * This bootstrap file is designed for full WordPress integration testing using wp-env.
 * It loads the WordPress test framework and initializes the plugin within a complete
 * WordPress environment, allowing for testing of database operations, REST API
 * endpoints, user authentication, and other WordPress-dependent functionality.
 *
 * Integration tests using this bootstrap can test the full plugin functionality
 * including WordPress hooks, database queries, REST API responses, and user
 * authentication flows.
 *
 * @package   JWTAuthProWPRestAPI
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 *
 * @link      https://github.com/juanma-wp/jwt-auth-pro-wp-rest-api
 */

// Define testing environment constants
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

// Load Composer autoloader
$composer_autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $composer_autoloader ) ) {
	require_once $composer_autoloader;
} else {
	echo "Warning: Composer autoloader not found. Please run 'composer install'.\n";
}

// WordPress test environment paths for wp-env
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/wordpress-phpunit/wp-tests';
}

// WordPress core directory for wp-env
$wp_core_dir = getenv( 'WP_CORE_DIR' );
if ( ! $wp_core_dir ) {
	$wp_core_dir = '/var/www/html';
}

// Give access to tests_add_filter() function
if ( file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	require_once $_tests_dir . '/includes/functions.php';
}

/**
 * Manually load the plugin being tested
 */
function _manually_load_jwt_plugin() {
	// Define test constants for JWT
	if ( ! defined( 'JWT_AUTH_PRO_SECRET' ) ) {
		define( 'JWT_AUTH_PRO_SECRET', 'test-secret-key-for-testing-purposes-only-never-use-in-production-environment-this-should-be-long-and-random' );
	}

	if ( ! defined( 'JWT_AUTH_ACCESS_TTL' ) ) {
		define( 'JWT_AUTH_ACCESS_TTL', 3600 );
	}

	if ( ! defined( 'JWT_AUTH_REFRESH_TTL' ) ) {
		define( 'JWT_AUTH_REFRESH_TTL', 86400 );
	}

	// Load the plugin
	require dirname( __DIR__ ) . '/jwt-auth-pro-wp-rest-api.php';
}

if ( function_exists( 'tests_add_filter' ) ) {
	tests_add_filter( 'muplugins_loaded', '_manually_load_jwt_plugin' );
}

/**
 * Set up WordPress test environment
 */
if ( file_exists( $_tests_dir . '/includes/bootstrap.php' ) ) {
	require $_tests_dir . '/includes/bootstrap.php';
} else {
	// Fallback bootstrap for cases where wp-env is not fully set up
	echo "Warning: WordPress test environment not found. Some tests may not work correctly.\n";

	// Define minimal WordPress constants
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', $wp_core_dir . '/' );
	}

	if ( ! defined( 'WP_DEBUG' ) ) {
		define( 'WP_DEBUG', true );
	}

	if ( ! defined( 'WP_DEBUG_LOG' ) ) {
		define( 'WP_DEBUG_LOG', true );
	}

	// Load our plugin manually
	_manually_load_jwt_plugin();
}

// Load test helpers
require_once __DIR__ . '/helpers/TestCase.php';

// Mock additional WordPress functions if needed for unit tests
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action ) {
		return 'test-nonce-' . md5( $action . wp_salt() );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action ) {
		return $nonce === wp_create_nonce( $action );
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( $scheme = 'auth' ) {
		return 'test-salt-' . $scheme;
	}
}

// Set up REST API testing environment
if ( function_exists( 'rest_get_server' ) ) {
	global $wp_rest_server;
	$wp_rest_server = rest_get_server();
}

// Ensure predictable general settings for tests (CORS)
if ( function_exists( 'update_option' ) ) {
	update_option(
		'wp_rest_auth_jwt_general_settings',
		array(
			'enable_debug_logging' => true,
			'cors_allowed_origins' => "https://example.com\nhttps://app.example.com",
		)
	);
}

// Force a predictable avatar URL for unit tests
if ( function_exists( 'add_filter' ) ) {
	add_filter(
		'get_avatar_url',
		function ( $url ) {
			return 'https://example.com/avatar.jpg';
		},
		10,
		1
	);
}

echo "JWT Auth Pro WP REST API Integration Test environment loaded successfully!\n";
echo 'WordPress version: ' . ( defined( 'WP_VERSION' ) ? esc_html( WP_VERSION ) : 'Unknown' ) . "\n";
echo 'PHP version: ' . esc_html( PHP_VERSION ) . "\n";
echo 'Test directory: ' . esc_html( $_tests_dir ) . "\n";
echo 'WordPress directory: ' . esc_html( $wp_core_dir ) . "\n\n";
