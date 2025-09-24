<?php

/**
 * PHPUnit bootstrap for Unit Tests
 *
 * This bootstrap is designed for testing isolated PHP functions without WordPress dependencies.
 * It only loads the minimum required to test core JWT helper functions.
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

// Load only the helpers.php file for basic function testing
require_once dirname( __DIR__ ) . '/includes/helpers.php';

echo "WP REST Auth JWT Unit Test environment loaded successfully!\n";
echo 'PHP version: ' . PHP_VERSION . "\n\n";
