<?php
/**
 * Cookie Configuration Defaults per Environment
 *
 * Defines cookie security settings for each auto-detected environment.
 * These values are used when auto-detection is enabled and no custom
 * configuration is provided via constants or filters.
 *
 * @package   JWTAuthPro
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	/**
	 * Development Environment
	 *
	 * Optimized for local development with SPAs on different domains.
	 * - Secure flag is dynamic based on whether HTTPS is actually used
	 * - SameSite=Lax allows cross-origin requests in development
	 *   (Note: SameSite=None requires Secure=true, so we use Lax for HTTP development)
	 * - Path=/ makes cookie available to entire site
	 */
	'development' => array(
		'enabled'  => true,
		'name'     => 'wp_jwt_refresh_token',
		'secure'   => null, // Dynamic - will be set based on is_ssl()
		'samesite' => 'Lax',
		'path'     => '/',
		'domain'   => '',
		'httponly' => true,
		'lifetime' => DAY_IN_SECONDS,
	),

	/**
	 * Staging Environment
	 *
	 * Balanced security for testing with realistic conditions.
	 * - Secure flag always true (staging should use HTTPS)
	 * - SameSite=Lax allows some cross-site requests (e.g., external links)
	 * - Path=/ for full site access during testing
	 */
	'staging' => array(
		'enabled'  => true,
		'name'     => 'wp_jwt_refresh_token',
		'secure'   => true,
		'samesite' => 'Lax',
		'path'     => '/',
		'domain'   => '',
		'httponly' => true,
		'lifetime' => DAY_IN_SECONDS,
	),

	/**
	 * Production Environment
	 *
	 * Maximum security for production deployments.
	 * - Secure flag always true (production must use HTTPS)
	 * - SameSite=Strict prevents all cross-site requests
	 * - Path=/wp-json/ restricts cookie to REST API endpoints only
	 */
	'production' => array(
		'enabled'  => true,
		'name'     => 'wp_jwt_refresh_token',
		'secure'   => true,
		'samesite' => 'Strict',
		'path'     => '/wp-json/',
		'domain'   => '',
		'httponly' => true,
		'lifetime' => DAY_IN_SECONDS,
	),

	/**
	 * Base Defaults
	 *
	 * Fallback values used when environment cannot be detected
	 * or when specific settings are not defined in environment config.
	 */
	'base' => array(
		'enabled'  => true,
		'name'     => 'wp_jwt_refresh_token',
		'secure'   => true,
		'samesite' => 'Lax',
		'path'     => '/',
		'domain'   => '',
		'httponly' => true,
		'lifetime' => DAY_IN_SECONDS,
	),
);
