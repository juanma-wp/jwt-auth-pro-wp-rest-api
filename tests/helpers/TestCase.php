<?php

namespace WPRestAuthJWT\Tests\Helpers;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case for WP REST Auth JWT plugin tests
 */
class TestCase extends BaseTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Reset global state before each test
		$this->resetGlobalState();

		// Set up test constants if not already defined
		$this->setupTestConstants();
	}

	protected function tearDown(): void {
		// Clean up after each test
		$this->cleanupTestData();

		parent::tearDown();
	}

	/**
	 * Reset global state between tests
	 */
	protected function resetGlobalState(): void {
		// Reset $_SERVER variables
		$_SERVER['HTTP_AUTHORIZATION'] = null;
		$_SERVER['REQUEST_METHOD']     = 'GET';
		$_SERVER['REQUEST_URI']        = '/';

		// Reset WordPress globals if they exist
		if ( isset( $GLOBALS['wp_rest_server'] ) ) {
			unset( $GLOBALS['wp_rest_server'] );
		}
	}

	/**
	 * Setup test constants
	 */
	protected function setupTestConstants(): void {
		if ( ! defined( 'WP_JWT_AUTH_SECRET' ) ) {
			define( 'WP_JWT_AUTH_SECRET', 'test-secret-key-for-testing-purposes-only-jwt-never-use-in-production' );
		}

		if ( ! defined( 'WP_JWT_ACCESS_TTL' ) ) {
			define( 'WP_JWT_ACCESS_TTL', 3600 );
		}

		if ( ! defined( 'WP_JWT_REFRESH_TTL' ) ) {
			define( 'WP_JWT_REFRESH_TTL', 86400 );
		}

		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', '/tmp/wordpress/' );
		}
	}

	/**
	 * Clean up test data
	 */
	protected function cleanupTestData(): void {
		// Remove any test transients or options
		// This would normally use WordPress functions, but for unit tests we'll mock it
	}

	/**
	 * Create a mock WordPress user
	 */
	protected function createMockUser( $user_id = 1, $user_login = 'testuser', $user_email = 'test@example.com' ): \stdClass {
		$user               = new \stdClass();
		$user->ID           = $user_id;
		$user->user_login   = $user_login;
		$user->user_email   = $user_email;
		$user->display_name = 'Test User';
		$user->roles        = array( 'subscriber' );

		return $user;
	}

	/**
	 * Create a test JWT token
	 */
	protected function createTestJWT( $user_id = 1, $exp = null ): string {
		if ( $exp === null ) {
			$exp = time() + 3600; // 1 hour from now
		}

		$header  = json_encode(
			array(
				'typ' => 'JWT',
				'alg' => 'HS256',
			)
		);
		$payload = json_encode(
			array(
				'iss'  => 'wp-rest-auth-jwt',
				'iat'  => time(),
				'exp'  => $exp,
				'data' => array( 'user' => array( 'id' => $user_id ) ),
			)
		);

		$headerEncoded  = $this->base64UrlEncode( $header );
		$payloadEncoded = $this->base64UrlEncode( $payload );

		$signature        = hash_hmac( 'sha256', $headerEncoded . '.' . $payloadEncoded, WP_JWT_AUTH_SECRET, true );
		$signatureEncoded = $this->base64UrlEncode( $signature );

		return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
	}

	/**
	 * Base64 URL encode
	 */
	protected function base64UrlEncode( $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64 URL decode
	 */
	protected function base64UrlDecode( $data ): string {
		return base64_decode( str_pad( strtr( $data, '-_', '+/' ), strlen( $data ) % 4, '=', STR_PAD_RIGHT ) );
	}

	/**
	 * Mock WordPress option functions
	 */
	protected function mockWordPressOptions(): void {
		if ( ! function_exists( 'get_option' ) ) {
			function get_option( $option, $default = false ) {
				static $options = array();
				return $options[ $option ] ?? $default;
			}
		}

		if ( ! function_exists( 'update_option' ) ) {
			function update_option( $option, $value ) {
				static $options     = array();
				$options[ $option ] = $value;
				return true;
			}
		}

		if ( ! function_exists( 'delete_option' ) ) {
			function delete_option( $option ) {
				static $options = array();
				unset( $options[ $option ] );
				return true;
			}
		}
	}

	/**
	 * Assert that a JWT token is valid
	 */
	protected function assertValidJWT( $token ): void {
		$parts = explode( '.', $token );
		$this->assertCount( 3, $parts, 'JWT should have 3 parts' );

		$header = json_decode( $this->base64UrlDecode( $parts[0] ), true );
		$this->assertArrayHasKey( 'typ', $header );
		$this->assertEquals( 'JWT', $header['typ'] );

		$payload = json_decode( $this->base64UrlDecode( $parts[1] ), true );
		$this->assertArrayHasKey( 'exp', $payload );
		$this->assertGreaterThan( time(), $payload['exp'], 'Token should not be expired' );
	}

	/**
	 * Create a mock HTTP request
	 */
	protected function createMockRequest( $method = 'GET', $url = '/', $headers = array(), $body = null ): array {
		return array(
			'method'  => $method,
			'url'     => $url,
			'headers' => $headers,
			'body'    => $body,
		);
	}

	/**
	 * Simulate an authenticated request
	 */
	protected function setAuthorizationHeader( $token, $type = 'Bearer' ): void {
		$_SERVER['HTTP_AUTHORIZATION'] = $type . ' ' . $token;
	}
}
