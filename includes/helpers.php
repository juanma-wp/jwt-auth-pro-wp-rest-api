<?php

/**
 * Helper functions for WP REST Auth JWT
 * Simple, focused utilities for JWT authentication
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encode a JWT token
 */
function wp_auth_jwt_encode( array $claims, string $secret ): string {
	$header = array(
		'typ' => 'JWT',
		'alg' => 'HS256',
	);

	$segments = array(
		wp_auth_jwt_base64url_encode( json_encode( $header ) ),
		wp_auth_jwt_base64url_encode( json_encode( $claims ) ),
	);

	$signing_input = implode( '.', $segments );
	$signature     = hash_hmac( 'sha256', $signing_input, $secret, true );
	$segments[]    = wp_auth_jwt_base64url_encode( $signature );

	return implode( '.', $segments );
}

/**
 * Decode a JWT token
 */
function wp_auth_jwt_decode( string $jwt, string $secret ) {
	$segments = explode( '.', $jwt );

	if ( count( $segments ) !== 3 ) {
		return false;
	}

	[$header64, $payload64, $signature64] = $segments;

	$header    = json_decode( wp_auth_jwt_base64url_decode( $header64 ), true );
	$payload   = json_decode( wp_auth_jwt_base64url_decode( $payload64 ), true );
	$signature = wp_auth_jwt_base64url_decode( $signature64 );

	if ( ! $header || ! $payload || ! $signature ) {
		return false;
	}

	if ( $header['alg'] !== 'HS256' ) {
		return false;
	}

	$signing_input      = $header64 . '.' . $payload64;
	$expected_signature = hash_hmac( 'sha256', $signing_input, $secret, true );

	if ( ! hash_equals( $expected_signature, $signature ) ) {
		return false;
	}

	if ( isset( $payload['exp'] ) && time() >= $payload['exp'] ) {
		return false;
	}

	return $payload;
}

/**
 * Base64URL encode
 */
function wp_auth_jwt_base64url_encode( string $data ): string {
	return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}

/**
 * Base64URL decode
 */
function wp_auth_jwt_base64url_decode( string $data ): string {
	return base64_decode( str_pad( strtr( $data, '-_', '+/' ), strlen( $data ) % 4, '=', STR_PAD_RIGHT ) );
}

/**
 * Generate a secure random token
 */
function wp_auth_jwt_generate_token( int $length = 64 ): string {
	if ( function_exists( 'random_bytes' ) ) {
		return bin2hex( random_bytes( $length / 2 ) );
	}

	return wp_generate_password( $length, false );
}

/**
 * Hash a token for database storage
 */
function wp_auth_jwt_hash_token( string $token, string $secret ): string {
	return hash_hmac( 'sha256', $token, $secret );
}

/**
 * Get client IP address
 */
function wp_auth_jwt_get_ip_address(): string {
	$ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );

	foreach ( $ip_keys as $key ) {
		if ( array_key_exists( $key, $_SERVER ) && ! empty( $_SERVER[ $key ] ) ) {
			$ip = explode( ',', $_SERVER[ $key ] )[0];
			$ip = trim( $ip );

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return $ip;
			}
		}
	}

	// Default to non-routable when only loopback is available (common in test/CLI environments)
	$fallback = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	if ( $fallback === '127.0.0.1' || $fallback === '::1' ) {
		return '0.0.0.0';
	}
	return $fallback;
}

/**
 * Get user agent
 */
function wp_auth_jwt_get_user_agent(): string {
	return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Set HTTPOnly cookie
 */
function wp_auth_jwt_set_cookie(
	string $name,
	string $value,
	int $expires,
	string $path = '/',
	bool $httponly = true,
	bool $secure = null
): bool {
	$secure   = $secure ?? is_ssl();
	$samesite = apply_filters( 'wp_auth_jwt_cookie_samesite', 'Strict' );

	// Avoid header warnings in test/CLI environments
	if ( defined( 'WP_CLI' ) || ( php_sapi_name() === 'cli' && defined( 'WP_DEBUG' ) ) ) {
		return true;
	}

	if ( PHP_VERSION_ID >= 70300 ) {
		return setcookie(
			$name,
			$value,
			array(
				'expires'  => $expires,
				'path'     => $path,
				'domain'   => '',
				'secure'   => $secure,
				'httponly' => $httponly,
				'samesite' => $samesite,
			)
		);
	}

	return setcookie( $name, $value, $expires, $path . '; SameSite=' . $samesite, '', $secure, $httponly );
}

/**
 * Delete cookie
 */
function wp_auth_jwt_delete_cookie( string $name, string $path = '/' ): bool {
	return wp_auth_jwt_set_cookie( $name, '', time() - 3600, $path );
}

/**
 * Check if origin is allowed for CORS
 */
function wp_auth_jwt_is_valid_origin( string $origin ): bool {
	$general_settings = WP_REST_Auth_JWT_Admin_Settings::get_general_settings();
	$allowed_origins  = $general_settings['cors_allowed_origins'] ?? '';

	if ( empty( $allowed_origins ) ) {
		return false;
	}

	$allowed_list = array_map( 'trim', explode( "\n", $allowed_origins ) );

	return in_array( '*', $allowed_list, true ) || in_array( $origin, $allowed_list, true );
}

/**
 * Add CORS headers if needed
 */
function wp_auth_jwt_maybe_add_cors_headers(): void {
	$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

	if ( $origin && wp_auth_jwt_is_valid_origin( $origin ) ) {
		header( 'Access-Control-Allow-Origin: ' . $origin );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With' );
		header( 'Access-Control-Max-Age: 86400' );

		if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
			http_response_code( 200 );
			exit;
		}
	}
}

/**
 * Create success response
 */
function wp_auth_jwt_success_response( array $data = array(), ?string $message = null, int $status = 200 ): WP_REST_Response {
	$response_data = array(
		'success' => true,
		'data'    => $data,
	);

	if ( $message ) {
		$response_data['message'] = $message;
	}

	return new WP_REST_Response( $response_data, $status );
}

/**
 * Create error response
 */
function wp_auth_jwt_error_response( string $code, string $message, int $status = 400, array $data = array() ): WP_Error {
	return new WP_Error( $code, $message, array_merge( array( 'status' => $status ), $data ) );
}

/**
 * Format user data for API responses
 */
function wp_auth_jwt_format_user_data( $user, bool $include_sensitive = false ): array {
	$user_data = array(
		'id'           => $user->ID,
		'username'     => $user->user_login,
		'email'        => $user->user_email,
		'display_name' => $user->display_name,
		'first_name'   => $user->first_name,
		'last_name'    => $user->last_name,
		'registered'   => $user->user_registered,
		'roles'        => $user->roles,
		'avatar_url'   => function_exists( 'get_avatar_url' ) ? get_avatar_url( $user->ID ) : '',
	);

	if ( $include_sensitive ) {
		if ( is_object( $user ) && method_exists( $user, 'get_role_caps' ) ) {
			$user_data['capabilities'] = $user->get_role_caps();
		}
	}

	return apply_filters( 'wp_auth_jwt_user_data', $user_data, $user, $include_sensitive );
}

/**
 * Debug logging helper
 */
function wp_auth_jwt_debug_log( $message, array $context = array() ): void {
	try {
		$settings = array();
		if ( class_exists( 'WP_REST_Auth_JWT_Admin_Settings' ) ) {
			$settings = WP_REST_Auth_JWT_Admin_Settings::get_general_settings();
		}
		$enabled = (bool) ( $settings['enable_debug_logging'] ?? false );
		if ( $enabled || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			$prefix = '[wp-rest-auth-jwt] ';
			$line   = is_scalar( $message ) ? (string) $message : json_encode( $message );
			if ( ! empty( $context ) ) {
				$line .= ' ' . json_encode( $context );
			}
			error_log( $prefix . $line );
		}
	} catch ( \Throwable $e ) {
		// Never let logging break the app/tests
	}
}
