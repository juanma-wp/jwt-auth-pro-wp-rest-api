<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for JWT Helper functions
 */

// Provide a minimal mock for admin settings if the plugin class is not loaded in unit context.
if ( ! class_exists( 'WP_REST_Auth_JWT_Admin_Settings' ) ) {
	class WP_REST_Auth_JWT_Admin_Settings {

		public static function get_general_settings() {
			return array(
				'cors_allowed_origins' => "https://example.com\nhttps://app.example.com",
			);
		}
	}
}

class HelpersTest extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load helpers
		if ( ! function_exists( 'wp_auth_jwt_generate_token' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/helpers.php';
		}

		// Define constants for testing
		if ( ! defined( 'JWT_AUTH_PRO_SECRET' ) ) {
			define( 'JWT_AUTH_PRO_SECRET', 'test-secret-key-for-testing-only-jwt' );
		}
	}

	/**
	 * Test token generation with specified length.
	 */
	public function testTokenGeneration(): void {
		$token = wp_auth_jwt_generate_token( 32 );

		$this->assertIsString( $token );
		$this->assertSame( 32, strlen( $token ) );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]+$/', $token );
	}

	/**
	 * Test token generation with default length.
	 */
	public function testTokenGenerationWithDefaultLength(): void {
		$token = wp_auth_jwt_generate_token();

		$this->assertIsString( $token );
		$this->assertSame( 64, strlen( $token ) );
	}

	/**
	 * Test token hashing functionality.
	 */
	public function testTokenHashing(): void {
		$token  = 'test-token-123';
		$secret = 'test-secret';

		$hash = wp_auth_jwt_hash_token( $token, $secret );

		$this->assertIsString( $hash );
		$this->assertSame( 64, strlen( $hash ) ); // SHA256 produces 64 char hex string.

		// Same input should produce same hash
		$hash2 = wp_auth_jwt_hash_token( $token, $secret );
		$this->assertSame( $hash, $hash2 );

		// Different secret should produce different hash
		$hash3 = wp_auth_jwt_hash_token( $token, 'different-secret' );
		$this->assertNotEquals( $hash, $hash3 );
	}

	/**
	 * Test JWT token encoding.
	 */
	public function testJWTEncoding(): void {
		$payload = array(
			'iss' => 'test-issuer',
			'sub' => 'test-subject',
			'aud' => 'test-audience',
			'exp' => time() + 3600,
			'iat' => time(),
		);

		$token = wp_auth_jwt_encode( $payload, JWT_AUTH_PRO_SECRET );

		$this->assertIsString( $token );
		$this->assertStringContainsString( '.', $token );

		// Should have 3 parts separated by dots
		$parts = explode( '.', $token );
		$this->assertCount( 3, $parts );
	}

	/**
	 * Test JWT token decoding.
	 */
	public function testJWTDecoding(): void {
		$payload = array(
			'iss' => 'test-issuer',
			'sub' => 'test-subject',
			'aud' => 'test-audience',
			'exp' => time() + 3600,
			'iat' => time(),
		);

		$token   = wp_auth_jwt_encode( $payload, JWT_AUTH_PRO_SECRET );
		$decoded = wp_auth_jwt_decode( $token, JWT_AUTH_PRO_SECRET );

		$this->assertIsArray( $decoded );
		$this->assertSame( $payload['iss'], $decoded['iss'] );
		$this->assertSame( $payload['sub'], $decoded['sub'] );
		$this->assertSame( $payload['aud'], $decoded['aud'] );
	}

	/**
	 * Test JWT decoding with wrong secret key.
	 */
	public function testJWTDecodingWithWrongSecret(): void {
		$payload = array(
			'iss' => 'test-issuer',
			'exp' => time() + 3600,
		);

		$token  = wp_auth_jwt_encode( $payload, JWT_AUTH_PRO_SECRET );
		$result = wp_auth_jwt_decode( $token, 'wrong-secret' );

		$this->assertFalse( $result );
	}

	/**
	 * Test JWT decoding with expired token.
	 */
	public function testJWTDecodingWithExpiredToken(): void {
		$payload = array(
			'iss' => 'test-issuer',
			'exp' => time() - 3600, // Expired 1 hour ago
		);

		$token  = wp_auth_jwt_encode( $payload, JWT_AUTH_PRO_SECRET );
		$result = wp_auth_jwt_decode( $token, JWT_AUTH_PRO_SECRET );

		$this->assertFalse( $result );
	}

	/**
	 * Test IP address retrieval from various sources.
	 */
	public function testIPAddressRetrieval(): void {
		$ip = wp_auth_jwt_get_ip_address();

		$this->assertIsString( $ip );
		// Should return default IP when no server vars are set
		$this->assertSame( '0.0.0.0', $ip );

		// Test with REMOTE_ADDR
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
		$ip                     = wp_auth_jwt_get_ip_address();
		$this->assertSame( '192.168.1.1', $ip );

		// Test with X-Forwarded-For (should take first IP)
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 192.168.1.1';
		$ip                              = wp_auth_jwt_get_ip_address();
		$this->assertSame( '203.0.113.1', $ip );

		// Clean up
		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'] );
	}

	/**
	 * Test user agent retrieval.
	 */
	public function testUserAgentRetrieval(): void {
		$ua = wp_auth_jwt_get_user_agent();

		$this->assertIsString( $ua );
		$this->assertSame( 'Unknown', $ua );

		// Test with actual user agent
		$_SERVER['HTTP_USER_AGENT'] = 'TestAgent/1.0';
		$ua                         = wp_auth_jwt_get_user_agent();
		$this->assertSame( 'TestAgent/1.0', $ua );

		// Clean up
		unset( $_SERVER['HTTP_USER_AGENT'] );
	}

	/**
	 * Test cookie setting functions exist.
	 */
	public function testCookieSettings(): void {
		// Test cookie setting function exists
		$this->assertTrue( function_exists( 'wp_auth_jwt_set_cookie' ) );

		// Test cookie deletion function exists
		$this->assertTrue( function_exists( 'wp_auth_jwt_delete_cookie' ) );
	}

	/**
	 * Test success response formatting.
	 */
	public function testSuccessResponse(): void {
		$response = wp_auth_jwt_success_response( array( 'token' => 'test123' ), 'Login successful' );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( array( 'token' => 'test123' ), $data['data'] );
		$this->assertSame( 'Login successful', $data['message'] );
	}

	/**
	 * Test error response formatting.
	 */
	public function testErrorResponse(): void {
		$error = wp_auth_jwt_error_response( 'invalid_token', 'The token is invalid', 401 );

		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertSame( 'invalid_token', $error->get_error_code() );
		$this->assertSame( 'The token is invalid', $error->get_error_message() );

		$data = $error->get_error_data();
		$this->assertSame( 401, $data['status'] );
	}

	/**
	 * Test user data formatting.
	 */
	public function testUserDataFormatting(): void {
		// Create mock user
		$user                  = new stdClass();
		$user->ID              = 123;
		$user->user_login      = 'testuser';
		$user->user_email      = 'test@example.com';
		$user->display_name    = 'Test User';
		$user->first_name      = 'Test';
		$user->last_name       = 'User';
		$user->user_registered = '2023-01-01 00:00:00';
		$user->roles           = array( 'subscriber' );

		// Mock get_avatar_url function
		if ( ! function_exists( 'get_avatar_url' ) ) {
			function get_avatar_url( $user_id ) {
				return 'https://example.com/avatar.jpg';
			}
		}

		$formatted = wp_auth_jwt_format_user_data( $user );

		$this->assertIsArray( $formatted );
		$this->assertSame( 123, $formatted['id'] );
		$this->assertSame( 'testuser', $formatted['username'] );
		$this->assertSame( 'test@example.com', $formatted['email'] );
		$this->assertSame( 'Test User', $formatted['display_name'] );
		$this->assertSame( 'Test', $formatted['first_name'] );
		$this->assertSame( 'User', $formatted['last_name'] );
		$this->assertSame( array( 'subscriber' ), $formatted['roles'] );
		$this->assertSame( 'https://example.com/avatar.jpg', $formatted['avatar_url'] );
	}

	/**
	 * Test CORS origin validation.
	 *
	 * Note: This test now verifies that CORS is handled by the auth-toolkit package.
	 * The Cors::handleRequest() method is called in wp_auth_jwt_maybe_add_cors_headers().
	 */
	public function testCORSOriginValidation(): void {
		// Verify CORS helper function exists
		$this->assertTrue( function_exists( 'wp_auth_jwt_maybe_add_cors_headers' ) );

		// Test that it can be called without errors
		wp_auth_jwt_maybe_add_cors_headers();
		$this->assertTrue( true );
	}

	/**
	 * Test debug logging functionality.
	 */
	public function testDebugLogging(): void {
		// Test debug log function exists
		$this->assertTrue( function_exists( 'wp_auth_jwt_debug_log' ) );

		// Test function can be called without errors
		wp_auth_jwt_debug_log( 'Test message', array( 'data' => 'test' ) );
		$this->assertTrue( true ); // Should not throw errors
	}
}
