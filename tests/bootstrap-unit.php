<?php

/**
 * PHPUnit Bootstrap for Unit Tests
 *
 * This bootstrap file is designed for testing isolated PHP functions without WordPress
 * dependencies. It loads only the minimum required components to test core JWT helper
 * functions and other standalone utilities.
 *
 * Unit tests using this bootstrap should focus on testing individual functions and
 * methods without relying on WordPress core functionality, database connections,
 * or complex integrations.
 *
 * @package   JWTAuthProWPRestAPI
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 *
 * @link      https://github.com/juanma-wp/jwt-auth-pro-wp-rest-api
 */

// Load Composer autoloader
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define minimal constants needed for helpers.php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

if ( ! defined( 'JWT_AUTH_PRO_SECRET' ) ) {
	define( 'JWT_AUTH_PRO_SECRET', 'test-secret-for-unit-testing' );
}

if ( ! defined( 'JWT_AUTH_ACCESS_TTL' ) ) {
	define( 'JWT_AUTH_ACCESS_TTL', 3600 );
}

if ( ! defined( 'JWT_AUTH_REFRESH_TTL' ) ) {
	define( 'JWT_AUTH_REFRESH_TTL', 2592000 );
}

// Mock only essential WordPress functions needed by helpers.php
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

// Load only the helpers.php file for basic function testing
require_once dirname( __DIR__ ) . '/includes/helpers.php';

echo "JWT Auth Pro WP REST API Unit Test environment loaded successfully!\n";
echo 'PHP version: ' . PHP_VERSION . "\n\n";
