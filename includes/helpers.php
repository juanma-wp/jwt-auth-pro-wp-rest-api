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

use WPRestAuth\AuthToolkit\JWT\Encoder;
use WPRestAuth\AuthToolkit\Token\Generator;
use WPRestAuth\AuthToolkit\Token\Hasher;
use WPRestAuth\AuthToolkit\Token\RefreshTokenManager;
use WPRestAuth\AuthToolkit\Http\Cookie;
use WPRestAuth\AuthToolkit\Http\Response;

/**
 * Encode a JWT token.
 *
 * @param array  $claims JWT claims to encode.
 * @param string $secret Secret key for signing.
 * @return string JWT token.
 */
function wp_auth_jwt_encode( array $claims, string $secret ): string {
	$encoder = new Encoder( $secret );
	return $encoder->encode( $claims );
}

/**
 * Decode a JWT token.
 *
 * @param string $jwt JWT token to decode.
 * @param string $secret Secret key for verification.
 * @return array|false Decoded payload or false on failure.
 */
function wp_auth_jwt_decode( string $jwt, string $secret ) {
	$encoder = new Encoder( $secret );
	return $encoder->decode( $jwt );
}

/**
 * Base64URL encode.
 *
 * @param string $data Data to encode.
 * @return string Base64URL encoded string.
 * @deprecated Use WPRestAuth\AuthToolkit\JWT\Base64Url::encode() directly
 */
function wp_auth_jwt_base64url_encode( string $data ): string {
	return \WPRestAuth\AuthToolkit\JWT\Base64Url::encode( $data );
}

/**
 * Base64URL decode.
 *
 * @param string $data Data to decode.
 * @return string Decoded string.
 * @deprecated Use WPRestAuth\AuthToolkit\JWT\Base64Url::decode() directly
 */
function wp_auth_jwt_base64url_decode( string $data ): string {
	return \WPRestAuth\AuthToolkit\JWT\Base64Url::decode( $data );
}

/**
 * Generate a secure random token.
 *
 * @param int $length Token length.
 * @return string Generated token.
 */
function wp_auth_jwt_generate_token( int $length = 64 ): string {
	return Generator::generate( $length );
}

/**
 * Hash a token for database storage.
 *
 * @param string $token Token to hash.
 * @param string $secret Secret key for hashing.
 * @return string Hashed token.
 */
function wp_auth_jwt_hash_token( string $token, string $secret ): string {
	return Hasher::make( $token, $secret );
}

/**
 * Get client IP address
 */
function wp_auth_jwt_get_ip_address(): string {
	return \WPRestAuth\AuthToolkit\Security\IpResolver::get();
}

/**
 * Get user agent.
 *
 * @return string User agent string.
 */
function wp_auth_jwt_get_user_agent(): string {
	return \WPRestAuth\AuthToolkit\Security\UserAgent::get();
}

/**
 * Set HTTPOnly cookie with environment-aware configuration.
 *
 * Uses JWT_Cookie_Config to automatically determine appropriate cookie settings
 * based on environment (development/staging/production). Configuration can be
 * overridden via WordPress admin settings or explicit parameters.
 *
 * @param string      $name     Cookie name.
 * @param string      $value    Cookie value.
 * @param int         $expires  Expiration time.
 * @param string|null $path     Cookie path (null = use auto-detected).
 * @param bool|null   $httponly Whether cookie is HTTP only (null = use auto-detected).
 * @param bool|null   $secure   Whether cookie is secure (null = use auto-detected).
 * @return bool Success status.
 */
function wp_auth_jwt_set_cookie(
	string $name,
	string $value,
	int $expires,
	?string $path = null,
	?bool $httponly = null,
	?bool $secure = null
): bool {
	// Get environment-aware configuration.
	$config = JWT_Cookie_Config::get_config();

	// Use provided values or fall back to auto-detected config.
	$path     = $path ?? $config['path'];
	$httponly = $httponly ?? $config['httponly'];
	$secure   = $secure ?? $config['secure'];
	$samesite = apply_filters( 'wp_auth_jwt_cookie_samesite', $config['samesite'] );
	$domain   = $config['domain'];

	// Delegate to Cookie class which handles CLI detection and PHP version compatibility.
	return Cookie::set(
		$name,
		$value,
		array(
			'expires'  => $expires,
			'path'     => $path,
			'domain'   => $domain,
			'secure'   => $secure,
			'httponly' => $httponly,
			'samesite' => $samesite,
		)
	);
}

/**
 * Delete cookie with environment-aware configuration.
 *
 * Uses the same path detection as wp_auth_jwt_set_cookie to ensure
 * the cookie is properly deleted across all environments.
 *
 * @param string      $name Cookie name.
 * @param string|null $path Cookie path (null = use auto-detected).
 * @return bool Success status.
 */
function wp_auth_jwt_delete_cookie( string $name, ?string $path = null ): bool {
	return wp_auth_jwt_set_cookie( $name, '', time() - 3600, $path );
}

/**
 * Add CORS headers if needed
 */
function wp_auth_jwt_maybe_add_cors_headers(): void {
	$general_settings = JWT_Auth_Pro_Admin_Settings::get_general_settings();
	$allowed_origins  = $general_settings['cors_allowed_origins'] ?? '';

	if ( ! empty( $allowed_origins ) ) {
		\WPRestAuth\AuthToolkit\Http\Cors::handleRequest(
			$allowed_origins,
			array(
				'methods' => array( 'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS' ),
				'headers' => array( 'Authorization', 'Content-Type', 'X-Requested-With' ),
			)
		);
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
	return Response::success( $data, $message, $status );
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
	return Response::error( $code, $message, $status, $data );
}

/**
 * Format user data for API responses.
 *
 * @param WP_User $user WordPress user object.
 * @param bool    $include_sensitive Whether to include sensitive data.
 * @return array Formatted user data.
 */
function wp_auth_jwt_format_user_data( $user, bool $include_sensitive = false ): array {
	// Use the Response::formatUser() method which has the same logic.
	$user_data = Response::formatUser( $user, $include_sensitive );

	// Apply WordPress filter to maintain backward compatibility.
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
