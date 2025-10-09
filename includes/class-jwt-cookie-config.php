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
	 * Get environment-specific defaults from the toolkit.
	 *
	 * This method is kept for backwards compatibility with tests and existing code.
	 * It delegates to the toolkit's internal environment detection.
	 *
	 * @param string $environment Environment name (development, staging, production).
	 * @return array<string, mixed>
	 */
	public static function get_environment_defaults( string $environment = '' ): array {
		// Get base defaults from toolkit.
		$defaults = CookieConfig::getDefaults();

		// If no environment specified, use current environment.
		if ( empty( $environment ) ) {
			$environment = self::get_environment();
		}

		// Apply environment-specific overrides.
		// The toolkit now handles cross-origin detection, so we just apply environment-specific settings.
		switch ( $environment ) {
			case 'development':
				// For development, resolve auto values to sensible defaults.
				if ( 'auto' === $defaults['samesite'] ) {
					$defaults['samesite'] = 'Lax'; // Default for development without cross-origin.
				}
				if ( 'auto' === $defaults['secure'] ) {
					$defaults['secure'] = false; // Allow HTTP in development.
				}
				if ( 'auto' === $defaults['path'] ) {
					$defaults['path'] = '/';
				}
				if ( 'auto' === $defaults['domain'] ) {
					$defaults['domain'] = '';
				}
				break;
			case 'staging':
				// For staging, use more secure defaults.
				if ( 'auto' === $defaults['samesite'] ) {
					$defaults['samesite'] = 'Lax';
				}
				if ( 'auto' === $defaults['secure'] ) {
					$defaults['secure'] = true;
				}
				if ( 'auto' === $defaults['path'] ) {
					$defaults['path'] = '/';
				}
				if ( 'auto' === $defaults['domain'] ) {
					$defaults['domain'] = '';
				}
				break;
			case 'production':
				$defaults['secure']   = true;
				$defaults['samesite'] = 'Strict';
				$defaults['path']     = '/wp-json/';
				break;
			case 'base':
			default:
				// For base environment, replace 'auto' with actual values.
				if ( 'auto' === $defaults['samesite'] ) {
					$defaults['samesite'] = 'Lax';
				}
				if ( 'auto' === $defaults['secure'] ) {
					$defaults['secure'] = true;
				}
				if ( 'auto' === $defaults['path'] ) {
					$defaults['path'] = '/';
				}
				if ( 'auto' === $defaults['domain'] ) {
					$defaults['domain'] = '';
				}
				break;
		}

		// Override cookie name for JWT Auth.
		$defaults['name'] = Auth_JWT::REFRESH_COOKIE_NAME;

		return $defaults;
	}

	/**
	 * Get cookie configuration for current environment.
	 *
	 * Delegates to the shared CookieConfig implementation from wp-rest-auth-toolkit.
	 * The toolkit handles environment detection and provides appropriate defaults.
	 *
	 * Priority order (handled by toolkit):
	 * 1. Constants (JWT_AUTH_COOKIE_*)
	 * 2. Filters (jwt_auth_cookie_config / jwt_auth_cookie_{key})
	 * 3. Saved options (admin panel)
	 * 4. Environment-based defaults (auto-detected)
	 * 5. Base defaults
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
		// Get config from toolkit with our custom prefixes.
		$config = CookieConfig::getConfig(
			self::OPTION_NAME,
			self::FILTER_PREFIX,
			self::CONSTANT_PREFIX
		);

		// Set JWT-specific cookie name as default if not customized by filters/constants.
		// Check if name is still the toolkit default.
		if ( 'auth_session' === $config['name'] ) {
			$config['name'] = Auth_JWT::REFRESH_COOKIE_NAME;
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
		$defaults['name'] = Auth_JWT::REFRESH_COOKIE_NAME; // Override default name for JWT Auth.
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
