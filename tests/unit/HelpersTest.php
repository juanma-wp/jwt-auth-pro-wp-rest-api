<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for JWT Helper functions
 */
// Provide a minimal mock for admin settings if the plugin class is not loaded in unit context
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

	protected function setUp(): void {
		parent::setUp();

		// Load helpers
		if ( ! function_exists( 'wp_auth_jwt_generate_token' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/helpers.php';
		}

		// Define constants for testing
		if ( ! defined( 'WP_JWT_AUTH_SECRET' ) ) {
			define( 'WP_JWT_AUTH_SECRET', 'test-secret-key-for-testing-only-jwt' );
		}
	}

	public function testTokenGeneration(): void {
		$token = wp_auth_jwt_generate_token( 32 );

		$this->assertIsString( $token );
		$this->assertEquals( 32, strlen( $token ) );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]+$/', $token );
	}

	public function testTokenGenerationWithDefaultLength(): void {
		$token = wp_auth_jwt_generate_token();

		$this->assertIsString( $token );
		$this->assertEquals( 64, strlen( $token ) );
	}

	public function testTokenHashing(): void {
		$token  = 'test-token-123';
		$secret = 'test-secret';

		$hash = wp_auth_jwt_hash_token( $token, $secret );

		$this->assertIsString( $hash );
		$this->assertEquals( 64, strlen( $hash ) ); // SHA256 produces 64 char hex string

		// Same input should produce same hash
		$hash2 = wp_auth_jwt_hash_token( $token, $secret );
		$this->assertEquals( $hash, $hash2 );

		// Different secret should produce different hash
		$hash3 = wp_auth_jwt_hash_token( $token, 'different-secret' );
		$this->assertNotEquals( $hash, $hash3 );
	}

	public function testJWTEncoding(): void {
		$payload = array(
			'iss' => 'test-issuer',
			'sub' => 'test-subject',
			'aud' => 'test-audience',
			'exp' => time() + 3600,
			'iat' => time(),
		);

		$token = wp_auth_jwt_encode( $payload, WP_JWT_AUTH_SECRET );

		$this->assertIsString( $token );
		$this->assertStringContainsString( '.', $token );

		// Should have 3 parts separated by dots
		$parts = explode( '.', $token );
		$this->assertCount( 3, $parts );
	}

	public function testJWTDecoding(): void {
		$payload = array(
			'iss' => 'test-issuer',
			'sub' => 'test-subject',
			'aud' => 'test-audience',
			'exp' => time() + 3600,
			'iat' => time(),
		);

		$token   = wp_auth_jwt_encode( $payload, WP_JWT_AUTH_SECRET );
		$decoded = wp_auth_jwt_decode( $token, WP_JWT_AUTH_SECRET );

		$this->assertIsArray( $decoded );
		$this->assertEquals( $payload['iss'], $decoded['iss'] );
		$this->assertEquals( $payload['sub'], $decoded['sub'] );
		$this->assertEquals( $payload['aud'], $decoded['aud'] );
	}

	public function testJWTDecodingWithWrongSecret(): void {
		$payload = array(
			'iss' => 'test-issuer',
			'exp' => time() + 3600,
		);

		$token  = wp_auth_jwt_encode( $payload, WP_JWT_AUTH_SECRET );
		$result = wp_auth_jwt_decode( $token, 'wrong-secret' );

		$this->assertFalse( $result );
	}

	public function testJWTDecodingWithExpiredToken(): void {
		$payload = array(
			'iss' => 'test-issuer',
			'exp' => time() - 3600, // Expired 1 hour ago
		);

		$token  = wp_auth_jwt_encode( $payload, WP_JWT_AUTH_SECRET );
		$result = wp_auth_jwt_decode( $token, WP_JWT_AUTH_SECRET );

		$this->assertFalse( $result );
	}

	public function testIPAddressRetrieval(): void {
		$ip = wp_auth_jwt_get_ip_address();

		$this->assertIsString( $ip );
		// Should return default IP when no server vars are set
		$this->assertEquals( '0.0.0.0', $ip );

		// Test with REMOTE_ADDR
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
		$ip                     = wp_auth_jwt_get_ip_address();
		$this->assertEquals( '192.168.1.1', $ip );

		// Test with X-Forwarded-For (should take first IP)
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 192.168.1.1';
		$ip                              = wp_auth_jwt_get_ip_address();
		$this->assertEquals( '203.0.113.1', $ip );

		// Clean up
		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'] );
	}

	public function testUserAgentRetrieval(): void {
		$ua = wp_auth_jwt_get_user_agent();

		$this->assertIsString( $ua );
		$this->assertEquals( 'Unknown', $ua );

		// Test with actual user agent
		$_SERVER['HTTP_USER_AGENT'] = 'TestAgent/1.0';
		$ua                         = wp_auth_jwt_get_user_agent();
		$this->assertEquals( 'TestAgent/1.0', $ua );

		// Clean up
		unset( $_SERVER['HTTP_USER_AGENT'] );
	}

	public function testCookieSettings(): void {
		// Test cookie setting function exists
		$this->assertTrue( function_exists( 'wp_auth_jwt_set_cookie' ) );

		// Test cookie deletion function exists
		$this->assertTrue( function_exists( 'wp_auth_jwt_delete_cookie' ) );
	}

	public function testSuccessResponse(): void {
		$response = wp_auth_jwt_success_response( array( 'token' => 'test123' ), 'Login successful' );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( array( 'token' => 'test123' ), $data['data'] );
		$this->assertEquals( 'Login successful', $data['message'] );
	}

	public function testErrorResponse(): void {
		$error = wp_auth_jwt_error_response( 'invalid_token', 'The token is invalid', 401 );

		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertEquals( 'invalid_token', $error->get_error_code() );
		$this->assertEquals( 'The token is invalid', $error->get_error_message() );

		$data = $error->get_error_data();
		$this->assertEquals( 401, $data['status'] );
	}

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
		$this->assertEquals( 123, $formatted['id'] );
		$this->assertEquals( 'testuser', $formatted['username'] );
		$this->assertEquals( 'test@example.com', $formatted['email'] );
		$this->assertEquals( 'Test User', $formatted['display_name'] );
		$this->assertEquals( 'Test', $formatted['first_name'] );
		$this->assertEquals( 'User', $formatted['last_name'] );
		$this->assertEquals( array( 'subscriber' ), $formatted['roles'] );
		$this->assertEquals( 'https://example.com/avatar.jpg', $formatted['avatar_url'] );
	}

	public function testCORSOriginValidation(): void {
		// Test valid origin
		$this->assertTrue( wp_auth_jwt_is_valid_origin( 'https://example.com' ) );
		$this->assertTrue( wp_auth_jwt_is_valid_origin( 'https://app.example.com' ) );

		// Test invalid origin
		$this->assertFalse( wp_auth_jwt_is_valid_origin( 'https://malicious.com' ) );
	}

	public function testDebugLogging(): void {
		// Test debug log function exists
		$this->assertTrue( function_exists( 'wp_auth_jwt_debug_log' ) );

		// Test function can be called without errors
		wp_auth_jwt_debug_log( 'Test message', array( 'data' => 'test' ) );
		$this->assertTrue( true ); // Should not throw errors
	}
}
