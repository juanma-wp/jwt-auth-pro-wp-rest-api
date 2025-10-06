<?php
/**
 * Token Manager - Shared JWT Logic
 *
 * This class provides shared JWT token management functionality
 * that can be used across multiple WordPress plugins.
 *
 * @package   MyOrg\JWTAuthCore
 * @author    Your Organization
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

namespace MyOrg\JWTAuthCore;

/**
 * Token Manager Class
 *
 * Provides shared JWT token generation, validation, and management logic.
 */
class TokenManager {

	/**
	 * JWT secret key
	 *
	 * @var string
	 */
	private $secret;

	/**
	 * Token expiration time in seconds
	 *
	 * @var int
	 */
	private $expiration;

	/**
	 * Constructor
	 *
	 * @param string $secret JWT secret key.
	 * @param int    $expiration Token expiration time in seconds (default: 3600).
	 */
	public function __construct( string $secret, int $expiration = 3600 ) {
		$this->secret     = $secret;
		$this->expiration = $expiration;
	}

	/**
	 * Generate a JWT token
	 *
	 * @param array $payload Token payload data.
	 * @return string JWT token.
	 */
	public function generate_token( array $payload ): string {
		$header = array(
			'alg' => 'HS256',
			'typ' => 'JWT',
		);

		// Add expiration to payload
		$payload['exp'] = time() + $this->expiration;
		$payload['iat'] = time();

		$header_encoded  = $this->base64_url_encode( $this->json_encode( $header ) );
		$payload_encoded = $this->base64_url_encode( $this->json_encode( $payload ) );

		$signature = hash_hmac(
			'sha256',
			$header_encoded . '.' . $payload_encoded,
			$this->secret,
			true
		);

		$signature_encoded = $this->base64_url_encode( $signature );

		return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
	}

	/**
	 * Validate a JWT token
	 *
	 * @param string $token JWT token to validate.
	 * @return array|false Token payload if valid, false otherwise.
	 */
	public function validate_token( string $token ) {
		$token_parts = explode( '.', $token );

		if ( count( $token_parts ) !== 3 ) {
			return false;
		}

		list( $header_encoded, $payload_encoded, $signature_encoded ) = $token_parts;

		// Verify signature
		$signature = hash_hmac(
			'sha256',
			$header_encoded . '.' . $payload_encoded,
			$this->secret,
			true
		);

		if ( $this->base64_url_encode( $signature ) !== $signature_encoded ) {
			return false;
		}

		// Decode payload
		$payload = json_decode( $this->base64_url_decode( $payload_encoded ), true );

		// Check expiration
		if ( isset( $payload['exp'] ) && $payload['exp'] < time() ) {
			return false;
		}

		return $payload;
	}

	/**
	 * Refresh a token with a new expiration time
	 *
	 * @param string $token JWT token to refresh.
	 * @return string|false New token if valid, false otherwise.
	 */
	public function refresh_token( string $token ) {
		$payload = $this->validate_token( $token );

		if ( false === $payload ) {
			return false;
		}

		// Remove old exp and iat
		unset( $payload['exp'], $payload['iat'] );

		return $this->generate_token( $payload );
	}

	/**
	 * Base64 URL encode
	 *
	 * @param string $data Data to encode.
	 * @return string Encoded data.
	 */
	private function base64_url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64 URL decode
	 *
	 * @param string $data Data to decode.
	 * @return string Decoded data.
	 */
	private function base64_url_decode( string $data ): string {
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}

	/**
	 * Get token expiration time
	 *
	 * @return int Expiration time in seconds.
	 */
	public function get_expiration(): int {
		return $this->expiration;
	}

	/**
	 * Set token expiration time
	 *
	 * @param int $expiration Expiration time in seconds.
	 */
	public function set_expiration( int $expiration ): void {
		$this->expiration = $expiration;
	}

	/**
	 * JSON encode helper - uses wp_json_encode if available, otherwise json_encode
	 *
	 * @param mixed $data Data to encode.
	 * @return string JSON encoded string.
	 */
	private function json_encode( $data ): string {
		if ( function_exists( 'wp_json_encode' ) ) {
			return wp_json_encode( $data );
		}
		return json_encode( $data );
	}
}
