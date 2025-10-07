<?php
/**
 * JWT Cookie Configuration Class
 *
 * Wrapper for WP REST Auth Toolkit's CookieConfig class.
 * Provides environment-aware cookie configuration for JWT refresh tokens.
 * Automatically adjusts cookie security settings based on environment (development/production)
 * with optional manual overrides via WordPress admin settings.
 *
 * This is a backwards-compatible wrapper around the shared CookieConfig implementation
 * from wp-rest-auth-toolkit package.
 *
 * @package   WPRESTAuthJWT
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPRestAuth\AuthToolkit\Http\CookieConfig;

/**
 * JWT Cookie Configuration Class.
 *
 * Wrapper for the shared CookieConfig implementation from wp-rest-auth-toolkit.
 * Manages cookie security settings for JWT refresh tokens with environment detection.
 */
class JWT_Cookie_Config {

	/**
	 * Option name for storing cookie configuration.
	 */
	private const OPTION_NAME = 'jwt_auth_cookie_config';

	/**
	 * Filter prefix for WordPress hooks.
	 */
	private const FILTER_PREFIX = 'jwt_auth_cookie';

	/**
	 * Constant prefix for wp-config.php constants.
	 */
	private const CONSTANT_PREFIX = 'JWT_AUTH_COOKIE';

	/**
	 * Cached environment defaults from config file.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $environment_defaults = null;

	/**
	 * Load environment defaults from config file.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function load_environment_defaults(): array {
		if ( null !== self::$environment_defaults ) {
			return self::$environment_defaults;
		}

		$config_file = dirname( __DIR__ ) . '/config/cookie-defaults.php';

		if ( ! file_exists( $config_file ) ) {
			self::$environment_defaults = array();
			return self::$environment_defaults;
		}

		$defaults = require $config_file;

		if ( ! is_array( $defaults ) ) {
			self::$environment_defaults = array();
			return self::$environment_defaults;
		}

		self::$environment_defaults = $defaults;
		return self::$environment_defaults;
	}

	/**
	 * Get environment-specific defaults.
	 *
	 * @param string $environment Environment name (development, staging, production).
	 * @return array<string, mixed>
	 */
	public static function get_environment_defaults( string $environment = '' ): array {
		if ( empty( $environment ) ) {
			$environment = self::get_environment();
		}

		$all_defaults = self::load_environment_defaults();

		// Get environment-specific defaults or fall back to base
		$defaults = $all_defaults[ $environment ] ?? $all_defaults['base'] ?? array();

		// Handle dynamic secure flag in development
		if ( 'development' === $environment && null === ( $defaults['secure'] ?? null ) ) {
			$defaults['secure'] = is_ssl();
		}

		return $defaults;
	}

	/**
	 * Get cookie configuration for current environment.
	 *
	 * Priority order:
	 * 1. Constants (JWT_AUTH_COOKIE_*)
	 * 2. Filters (jwt_auth_cookie_config / jwt_auth_cookie_{key})
	 * 3. Saved options (admin panel)
	 * 4. Environment-based defaults from config file (if auto-detection enabled)
	 * 5. Toolkit base defaults
	 *
	 * @return array{
	 *     enabled: bool,
	 *     name: string,
	 *     samesite: string,
	 *     secure: bool,
	 *     path: string,
	 *     domain: string,
	 *     httponly: bool,
	 *     lifetime: int,
	 *     environment: string,
	 *     auto_detect: bool
	 * }
	 */
	public static function get_config(): array {
		// Start with our environment-specific defaults from config file
		$environment        = self::get_environment();
		$environment_config = self::get_environment_defaults( $environment );

		// Get saved options from database
		$saved_config = get_option( self::OPTION_NAME, array() );
		$auto_detect  = ! isset( $saved_config['auto_detect'] ) || $saved_config['auto_detect'];

		// Start with our base config
		$config = $environment_config;

		// Add metadata
		$config['environment'] = $environment;
		$config['auto_detect'] = $auto_detect;

		// Apply saved options from admin panel (if not using auto-detect)
		if ( ! $auto_detect && is_array( $saved_config ) ) {
			$config = array_merge( $config, $saved_config );
		}

		// Apply constants (highest priority before filters)
		foreach ( $config as $key => $value ) {
			$constant = strtoupper( self::CONSTANT_PREFIX . '_' . $key );
			if ( defined( $constant ) ) {
				$config[ $key ] = constant( $constant );
			}
		}

		// Apply global filter
		$config = apply_filters( self::FILTER_PREFIX . '_config', $config );

		// Apply individual field filters
		foreach ( $config as $key => $value ) {
			$config[ $key ] = apply_filters( self::FILTER_PREFIX . '_' . $key, $value, $config );
		}

		return $config;
	}

	/**
	 * Update cookie configuration.
	 *
	 * @param array<string, mixed> $config New configuration.
	 * @return bool True on success, false on failure.
	 */
	public static function update_config( array $config ): bool {
		return CookieConfig::updateConfig( $config, self::OPTION_NAME );
	}

	/**
	 * Get default configuration values for admin panel.
	 *
	 * @return array{
	 *     enabled: bool,
	 *     name: string,
	 *     samesite: string,
	 *     secure: string,
	 *     path: string,
	 *     domain: string,
	 *     httponly: bool,
	 *     lifetime: int,
	 *     auto_detect: bool
	 * }
	 */
	public static function get_defaults(): array {
		$defaults         = CookieConfig::getDefaults();
		$defaults['name'] = 'jwtauth_session'; // Override default name for JWT Auth.
		return $defaults;
	}

	/**
	 * Get current environment type.
	 *
	 * @return string
	 */
	public static function get_environment(): string {
		return CookieConfig::getEnvironment();
	}

	/**
	 * Check if current environment is development.
	 *
	 * @return bool
	 */
	public static function is_development(): bool {
		return CookieConfig::isDevelopment();
	}

	/**
	 * Check if current environment is production.
	 *
	 * @return bool
	 */
	public static function is_production(): bool {
		return CookieConfig::isProduction();
	}

	/**
	 * Clear configuration cache.
	 */
	public static function clear_cache(): void {
		CookieConfig::clearCache();
	}
}
