<?php
/**
 * Basic Helper Functions Unit Tests
 *
 * Unit tests for core JWT helper functions that don't require WordPress.
 * These tests focus on WordPress-independent functionality like JWT encoding,
 * decoding, and base64 URL encoding/decoding.
 *
 * @package   WPRESTAuthJWT
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 *
 * @link      https://github.com/juanma-wp/wp-rest-auth-jwt
 */

use PHPUnit\Framework\TestCase;

/**
 * Basic unit tests for core JWT helper functions.
 *
 * Tests only WordPress-independent functionality.
 */
class BasicHelpersTest extends TestCase {

	/**
	 * Test JWT token encoding functionality.
	 */
	public function testJWTEncoding(): void {
		$payload = array(
			'user_id' => 1,
			'exp'     => time() + 3600,
		);
		$secret  = 'test-secret';

		$token = wp_auth_jwt_encode( $payload, $secret );

		$this->assertIsString( $token );
		$this->assertStringContainsString( '.', $token );

		// JWT should have 3 parts separated by dots.
		$parts = explode( '.', $token );
		$this->assertCount( 3, $parts );
	}

	/**
	 * Test JWT token decoding functionality.
	 */
	public function testJWTDecoding(): void {
		$payload = array(
			'user_id' => 123,
			'exp'     => time() + 3600,
		);
		$secret  = 'test-secret';

		$token   = wp_auth_jwt_encode( $payload, $secret );
		$decoded = wp_auth_jwt_decode( $token, $secret );

		$this->assertSame( $payload['user_id'], $decoded['user_id'] );
		$this->assertSame( $payload['exp'], $decoded['exp'] );
	}

	/**
	 * Test JWT decoding with incorrect secret key.
	 */
	public function testJWTDecodingWithWrongSecret(): void {
		$payload = array(
			'user_id' => 123,
			'exp'     => time() + 3600,
		);

		$token   = wp_auth_jwt_encode( $payload, 'secret1' );
		$decoded = wp_auth_jwt_decode( $token, 'secret2' );

		$this->assertFalse( $decoded );
	}

	/**
	 * Test Base64 URL encoding/decoding functionality.
	 */
	public function testBase64UrlEncoding(): void {
		$data    = 'test data with special chars +/=';
		$encoded = wp_auth_jwt_base64url_encode( $data );
		$decoded = wp_auth_jwt_base64url_decode( $encoded );

		$this->assertSame( $data, $decoded );
		$this->assertStringNotContainsString( '+', $encoded );
		$this->assertStringNotContainsString( '/', $encoded );
		$this->assertStringNotContainsString( '=', $encoded );
	}
}
