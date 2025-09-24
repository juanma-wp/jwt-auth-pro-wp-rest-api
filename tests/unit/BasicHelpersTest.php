<?php

use PHPUnit\Framework\TestCase;

/**
 * Basic unit tests for core JWT helper functions
 * Tests only WordPress-independent functionality
 */
class BasicHelpersTest extends TestCase {

	public function testJWTEncoding(): void {
		$payload = array(
			'user_id' => 1,
			'exp'     => time() + 3600,
		);
		$secret  = 'test-secret';

		$token = wp_auth_jwt_encode( $payload, $secret );

		$this->assertIsString( $token );
		$this->assertStringContainsString( '.', $token );

		// JWT should have 3 parts separated by dots
		$parts = explode( '.', $token );
		$this->assertCount( 3, $parts );
	}

	public function testJWTDecoding(): void {
		$payload = array(
			'user_id' => 123,
			'exp'     => time() + 3600,
		);
		$secret  = 'test-secret';

		$token   = wp_auth_jwt_encode( $payload, $secret );
		$decoded = wp_auth_jwt_decode( $token, $secret );

		$this->assertEquals( $payload['user_id'], $decoded['user_id'] );
		$this->assertEquals( $payload['exp'], $decoded['exp'] );
	}

	public function testJWTDecodingWithWrongSecret(): void {
		$payload = array(
			'user_id' => 123,
			'exp'     => time() + 3600,
		);

		$token   = wp_auth_jwt_encode( $payload, 'secret1' );
		$decoded = wp_auth_jwt_decode( $token, 'secret2' );

		$this->assertFalse( $decoded );
	}

	public function testBase64UrlEncoding(): void {
		$data    = 'test data with special chars +/=';
		$encoded = wp_auth_jwt_base64url_encode( $data );
		$decoded = wp_auth_jwt_base64url_decode( $encoded );

		$this->assertEquals( $data, $decoded );
		$this->assertStringNotContainsString( '+', $encoded );
		$this->assertStringNotContainsString( '/', $encoded );
		$this->assertStringNotContainsString( '=', $encoded );
	}
}
