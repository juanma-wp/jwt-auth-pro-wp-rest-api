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

if ( ! defined( 'WP_JWT_ACCESS_TTL' ) ) {
	define( 'WP_JWT_ACCESS_TTL', 3600 );
}

if ( ! defined( 'WP_JWT_REFRESH_TTL' ) ) {
	define( 'WP_JWT_REFRESH_TTL', 2592000 );
}

// Mock WordPress functions for unit tests
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_authenticate' ) ) {
	function wp_authenticate( $username, $password ) {
		return new stdClass();
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return stripslashes( $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value, ...$args ) {
		return $value;
	}
}

// Load only the helpers.php file for basic function testing
require_once dirname( __DIR__ ) . '/includes/helpers.php';

echo "WP REST Auth JWT Unit Test environment loaded successfully!\n";
echo 'PHP version: ' . PHP_VERSION . "\n\n";
