<?php
/**
 * Test script for cookie settings sanitization
 *
 * Run this with: php test-cookie-settings.php
 */

// Load WordPress (adjust path if needed)
require_once __DIR__ . '/../../../wp-load.php';

// Ensure our classes are loaded
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/class-jwt-cookie-config.php';
require_once __DIR__ . '/includes/class-admin-settings.php';

echo "=== Testing Cookie Settings Sanitization ===\n\n";

// Create an instance of the admin settings
$admin_settings = new JWT_Auth_Pro_Admin_Settings();

// Test 1: Valid input
echo "Test 1: Valid SameSite='None' input\n";
$test_input = array(
	'samesite' => 'None',
	'secure'   => '1',
	'path'     => '/',
	'domain'   => '',
);

try {
	$result = $admin_settings->sanitize_cookie_settings( $test_input );
	echo "✓ Success! Result: " . print_r( $result, true ) . "\n";
} catch ( Exception $e ) {
	echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Null input
echo "\nTest 2: Null input\n";
try {
	$result = $admin_settings->sanitize_cookie_settings( null );
	echo "✓ Success! Result: " . print_r( $result, true ) . "\n";
} catch ( Exception $e ) {
	echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Secure = '0'
echo "\nTest 3: Secure = '0' (string zero)\n";
$test_input = array(
	'samesite' => 'Lax',
	'secure'   => '0',
	'path'     => '/wp-json/jwt/v1/',
	'domain'   => 'auto',
);

try {
	$result = $admin_settings->sanitize_cookie_settings( $test_input );
	echo "✓ Success! Result: " . print_r( $result, true ) . "\n";
} catch ( Exception $e ) {
	echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: Get configuration
echo "\nTest 4: Get current configuration\n";
try {
	$config = JWT_Cookie_Config::get_config();
	echo "✓ Success! Environment: " . JWT_Cookie_Config::get_environment() . "\n";
	echo "Configuration: " . print_r( $config, true ) . "\n";
} catch ( Exception $e ) {
	echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== All Tests Complete ===\n";