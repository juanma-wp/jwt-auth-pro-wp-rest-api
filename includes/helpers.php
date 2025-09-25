<?php

/**
 * Helper functions for WP REST Auth JWT
 *
 * This file contains utility functions for JWT token operations, security helpers,
 * HTTP handling, and other common functionality used throughout the plugin.
 *
 * All functions in this file are prefixed with 'wp_auth_jwt_' to prevent naming
 * conflicts with other WordPress plugins or themes.
 *
 * @package   WPRESTAuthJWT
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 *
 * @link      https://github.com/juanma-wp/wp-rest-auth-jwt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encode a JWT token.
 *
 * @param array  $claims JWT claims to encode.
 * @param string $secret Secret key for signing.
 * @return string JWT token.
 */
function wp_auth_jwt_encode( array $claims, string $secret ): string {
	$header = array(
		'typ' => 'JWT',
		'alg' => 'HS256',
	);

	$segments = array(
		wp_auth_jwt_base64url_encode( wp_json_encode( $header ) ),
		wp_auth_jwt_base64url_encode( wp_json_encode( $claims ) ),
	);

	$signing_input = implode( '.', $segments );
	$signature     = hash_hmac( 'sha256', $signing_input, $secret, true );
	$segments[]    = wp_auth_jwt_base64url_encode( $signature );

	return implode( '.', $segments );
}

/**
 * Decode a JWT token.
 *
 * @param string $jwt JWT token to decode.
 * @param string $secret Secret key for verification.
 * @return array|false Decoded payload or false on failure.
 */
function wp_auth_jwt_decode( string $jwt, string $secret ) {
	$segments = explode( '.', $jwt );

	if ( count( $segments ) !== 3 ) {
		return false;
	}

	list($header64, $payload64, $signature64) = $segments;

	$header    = json_decode( wp_auth_jwt_base64url_decode( $header64 ), true );
	$payload   = json_decode( wp_auth_jwt_base64url_decode( $payload64 ), true );
	$signature = wp_auth_jwt_base64url_decode( $signature64 );

	if ( ! $header || ! $payload || ! $signature ) {
		return false;
	}

	if ( 'HS256' !== $header['alg'] ) {
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
 * Base64URL encode.
 *
 * @param string $data Data to encode.
 * @return string Base64URL encoded string.
 */
function wp_auth_jwt_base64url_encode( string $data ): string {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for JWT Base64URL encoding per RFC 7515.
	return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}

/**
 * Base64URL decode.
 *
 * @param string $data Data to decode.
 * @return string Decoded string.
 */
function wp_auth_jwt_base64url_decode( string $data ): string {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Required for JWT Base64URL decoding per RFC 7515.
	return base64_decode( str_pad( strtr( $data, '-_', '+/' ), strlen( $data ) % 4, '=', STR_PAD_RIGHT ) );
}

/**
 * Generate a secure random token.
 *
 * @param int $length Token length.
 * @return string Generated token.
 */
function wp_auth_jwt_generate_token( int $length = 64 ): string {
	if ( function_exists( 'random_bytes' ) ) {
		return bin2hex( random_bytes( $length / 2 ) );
	}

	return wp_generate_password( $length, false );
}

/**
 * Hash a token for database storage.
 *
 * @param string $token Token to hash.
 * @param string $secret Secret key for hashing.
 * @return string Hashed token.
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
			$ip = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0];
			$ip = trim( $ip );

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return $ip;
			}
		}
	}

	// Default to non-routable when only loopback is available (common in test/CLI environments).
	$fallback = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	if ( '127.0.0.1' === $fallback || '::1' === $fallback ) {
		return '0.0.0.0';
	}
	return $fallback;
}

/**
 * Get user agent.
 *
 * @return string User agent string.
 */
function wp_auth_jwt_get_user_agent(): string {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'Unknown';
}

/**
 * Set HTTPOnly cookie.
 *
 * @param string $name Cookie name.
 * @param string $value Cookie value.
 * @param int    $expires Expiration time.
 * @param string $path Cookie path.
 * @param bool   $httponly Whether cookie is HTTP only.
 * @param bool   $secure Whether cookie is secure.
 * @return bool Success status.
 */
function wp_auth_jwt_set_cookie(
	string $name,
	string $value,
	int $expires,
	string $path = '/',
	bool $httponly = true,
	?bool $secure = null
): bool {
	$secure   = $secure ?? is_ssl();
	$samesite = apply_filters( 'wp_auth_jwt_cookie_samesite', 'Strict' );

	// Avoid header warnings in test/CLI environments.
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
 * Delete cookie.
 *
 * @param string $name Cookie name.
 * @param string $path Cookie path.
 * @return bool Success status.
 */
function wp_auth_jwt_delete_cookie( string $name, string $path = '/' ): bool {
	return wp_auth_jwt_set_cookie( $name, '', time() - 3600, $path );
}

/**
 * Check if origin is allowed for CORS.
 *
 * @param string $origin Origin to check.
 * @return bool Whether origin is valid.
 */
function wp_auth_jwt_is_valid_origin( string $origin ): bool {
	$general_settings = JWT_Auth_Pro_Admin_Settings::get_general_settings();
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
	$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';

	if ( $origin && wp_auth_jwt_is_valid_origin( $origin ) ) {
		header( 'Access-Control-Allow-Origin: ' . $origin );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With' );
		header( 'Access-Control-Max-Age: 86400' );

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
			http_response_code( 200 );
			exit;
		}
	}
}

/**
 * Create success response.
 *
 * @param array       $data Response data.
 * @param string|null $message Response message.
 * @param int         $status HTTP status code.
 * @return WP_REST_Response Success response.
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
 * Create error response.
 *
 * @param string $code Error code.
 * @param string $message Error message.
 * @param int    $status HTTP status code.
 * @param array  $data Additional error data.
 * @return WP_Error Error response.
 */
function wp_auth_jwt_error_response( string $code, string $message, int $status = 400, array $data = array() ): WP_Error {
	return new WP_Error( $code, $message, array_merge( array( 'status' => $status ), $data ) );
}

/**
 * Format user data for API responses.
 *
 * @param WP_User $user WordPress user object.
 * @param bool    $include_sensitive Whether to include sensitive data.
 * @return array Formatted user data.
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
 * Debug logging helper.
 *
 * @param mixed $message Message to log.
 * @param array $context Additional context.
 * @return void
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
			$line   = is_scalar( $message ) ? (string) $message : wp_json_encode( $message );
			if ( ! empty( $context ) ) {
				$line .= ' ' . wp_json_encode( $context );
			}
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Development/debug logging, gated by user setting or WP_DEBUG.
			error_log( $prefix . $line );
		}
	} catch ( \Throwable $e ) {
		// Never let logging break the app/tests.
		// Intentionally empty - we want to silently fail logging errors.
		unset( $e );
	}
}
