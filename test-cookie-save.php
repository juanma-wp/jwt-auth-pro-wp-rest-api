<?php
/**
 * Test cookie settings save process
 */

// Simulate WordPress environment constants
define( 'ABSPATH', '/fake/path/' );

// Load the classes
require_once __DIR__ . '/includes/class-jwt-cookie-config.php';
require_once __DIR__ . '/includes/class-admin-settings.php';

echo "=== Testing Cookie Save Process ===\n\n";

// Test 1: Environment detection
echo "Test 1: Environment Detection\n";
try {
	$env = JWT_Cookie_Config::get_environment();
	echo "✓ Environment detected: $env\n";
} catch ( Exception $e ) {
	echo "✗ Error: " . $e->getMessage() . "\n";
	echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 2: Get default config
echo "\nTest 2: Get Default Config\n";
try {
	$config = JWT_Cookie_Config::get_config();
	echo "✓ Config retrieved successfully\n";
	print_r( $config );
} catch ( Exception $e ) {
	echo "✗ Error: " . $e->getMessage() . "\n";
	echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 3: Sanitize settings (simulating form submission)
echo "\nTest 3: Sanitize Cookie Settings\n";
try {
	$admin_settings = new JWT_Auth_Pro_Admin_Settings();

	$test_input = array(
		'samesite' => 'None',
		'secure'   => '1',
		'path'     => '/wp-json/jwt/v1/',
		'domain'   => '',
	);

	$result = $admin_settings->sanitize_cookie_settings( $test_input );
	echo "✓ Sanitization successful\n";
	print_r( $result );
} catch ( Error $e ) {
	echo "✗ Fatal Error: " . $e->getMessage() . "\n";
	echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
	echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch ( Exception $e ) {
	echo "✗ Exception: " . $e->getMessage() . "\n";
	echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Tests Complete ===\n";