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
 * @package   WPRESTAuthJWT
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 *
 * @link      https://github.com/juanma-wp/wp-rest-auth-jwt
 */

// Load Composer autoloader
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define minimal constants needed for helpers.php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

if ( ! defined( 'WP_JWT_AUTH_SECRET' ) ) {
	define( 'WP_JWT_AUTH_SECRET', 'test-secret-for-unit-testing' );
}

// Mock WordPress functions for unit tests
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

// Load only the helpers.php file for basic function testing
require_once dirname( __DIR__ ) . '/includes/helpers.php';

echo "WP REST Auth JWT Unit Test environment loaded successfully!\n";
echo 'PHP version: ' . PHP_VERSION . "\n\n";
