<?php

/**
 * Plugin Name: JuanMa JWT Auth Pro
 * Description: Modern JWT authentication with refresh tokens for WordPress REST API - built for SPAs and mobile apps
 * Version: 1.1.0
 * Author: Juan Manuel Garrido
 * Author URI: https://juanma.codes
 * Plugin URI: https://github.com/juanma-wp/jwt-auth-pro-wp-rest-api
 * Text Domain: juanma-jwt-auth-pro
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * JWT Auth Pro - Advanced JWT authentication with secure refresh token architecture
 *
 * Unlike basic JWT plugins that use single long-lived tokens, JWT Auth Pro implements
 * modern OAuth 2.0 best practices with short-lived access tokens and secure refresh tokens.
 * This dramatically improves security for Single Page Applications (SPAs) and mobile apps.
 *
 * Key Security Advantages:
 * - Short-lived JWT access tokens (configurable, default 1 hour)
 * - Secure HTTP-only refresh tokens stored in database
 * - Automatic token rotation and revocation capabilities
 * - Protection against XSS attacks via HTTP-only cookies
 * - Complete token lifecycle management with user session tracking
 * - CORS support optimized for modern web applications
 * - WordPress security standards compliant
 *
 * Perfect for developers building modern applications that require enterprise-grade
 * JWT security without the complexity of full OAuth 2.0 implementations.
 *
 * @package   JM_JWTAuthPro
 * @author    Juan Manuel Garrido
 * @copyright 2025 Juan Manuel Garrido
 * @license   GPL-2.0-or-later
 * @link      https://github.com/juanma-wp/jwt-auth-pro-wp-rest-api
 * @since     1.0.0
 *
 * @wordpress-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

define( 'JWT_AUTH_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JWT_AUTH_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JWT_AUTH_PRO_VERSION', '1.1.0' );

// Debug: Add a constant to check if plugin is loaded.
if ( ! defined( 'JWT_AUTH_PRO_LOADED' ) ) {
	define( 'JWT_AUTH_PRO_LOADED', true );
}

/**
 * Main plugin class for JWT Auth Pro.
 *
 * @package JM_JWTAuthPro
 */
class JWT_Auth_Pro {




	/**
	 * Auth JWT instance.
	 *
	 * @var Auth_JWT
	 */
	private $auth_jwt;

	/**
	 * OpenAPI Spec instance.
	 *
	 * @var JWT_Auth_Pro_OpenAPI_Spec
	 */
	private $openapi_spec;


	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init(): void {
		$this->load_dependencies();
		$this->setup_constants();
		$this->init_components();
		$this->init_hooks();
	}

	/**
	 * Load plugin dependencies.
	 */
	private function load_dependencies(): void {
		// Load non-class files that contain helper functions.
		require_once JWT_AUTH_PRO_PLUGIN_DIR . 'includes/helpers.php';

		// The JWT_Auth_Pro_Admin_Settings class is namespaced but uses WordPress naming convention.
		// It will be autoloaded via Composer classmap when needed (JM_JWTAuthPro\JWT_Auth_Pro_Admin_Settings).

		// Legacy files without namespaces still need require_once.
		require_once JWT_AUTH_PRO_PLUGIN_DIR . 'includes/class-jwt-cookie-config.php';
		require_once JWT_AUTH_PRO_PLUGIN_DIR . 'includes/class-auth-jwt.php';
		require_once JWT_AUTH_PRO_PLUGIN_DIR . 'includes/class-openapi-spec.php';
	}

	/**
	 * Setup plugin constants.
	 *
	 * These constants can be defined in wp-config.php for early availability.
	 * Admin panel settings will be used at runtime when actually needed.
	 */
	private function setup_constants(): void {
		// Don't load admin settings here - use wp-config.php constants or defaults.
		// Admin settings will be checked lazily when needed (e.g., in Auth_JWT class).

		// The secret is critical and must be set either in wp-config.php or admin.
		// We'll check for it when actually needed, not during initialization.
		// Don't define JWT_AUTH_PRO_SECRET here - let Auth_JWT check admin settings when needed.
		// This avoids loading admin classes during plugin initialization.

		// Set default token expiration times if not defined in wp-config.php.
		if ( ! defined( 'JWT_AUTH_PRO_ACCESS_TTL' ) ) {
			define( 'JWT_AUTH_PRO_ACCESS_TTL', 3600 ); // 1 hour default
		}

		if ( ! defined( 'JWT_AUTH_PRO_REFRESH_TTL' ) ) {
			define( 'JWT_AUTH_PRO_REFRESH_TTL', 2592000 ); // 30 days default
		}
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_components(): void {
		// Initialize admin settings.
		if ( is_admin() ) {
			// Check if the base class exists before trying to instantiate.
			if ( class_exists( 'WPRestAuth\AuthToolkit\Admin\BaseAdminSettings' ) ) {
				new JM_JWTAuthPro\JWT_Auth_Pro_Admin_Settings();
			} else {
				// Log error or show admin notice about missing dependency.
				add_action(
					'admin_notices',
					function () {
						?>
					<div class="notice notice-error">
						<p><?php esc_html_e( 'JWT Auth Pro: Required dependency "wp-rest-auth-toolkit" is not loaded. Please check your installation.', 'juanma-jwt-auth-pro' ); ?></p>
					</div>
						<?php
					}
				);
			}
		}

		$this->auth_jwt     = new Auth_JWT();
		$this->openapi_spec = new JWT_Auth_Pro_OpenAPI_Spec();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_filter( 'rest_authentication_errors', array( $this, 'maybe_auth_bearer' ), 20 );

		// Check if JWT secret is configured and show admin notice if not.
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'check_jwt_secret' ) );
		}

		// Initialize CORS support.
		$this->init_cors();
	}

	/**
	 * Initialize CORS support using Cors from auth-toolkit.
	 *
	 * Uses the clean Cors implementation that properly handles:
	 * - Origin validation
	 * - Preflight OPTIONS requests
	 * - Header management
	 * - Pattern matching for origins
	 */
	private function init_cors(): void {
		// Get CORS settings directly from database (lazy loading).
		// Avoid loading admin settings class during initialization.
		$general_settings = get_option( 'jwt_auth_pro_general_settings', array() );
		$allowed_origins  = $general_settings['cors_allowed_origins'] ?? '';

		// Enable CORS using the toolkit's Cors class.
		// This handles everything: validation, preflight, headers.
		if ( class_exists( '\WPRestAuth\AuthToolkit\Http\Cors' ) ) {
			\WPRestAuth\AuthToolkit\Http\Cors::enableForWordPress( $allowed_origins );
		}
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		$this->auth_jwt->register_routes();
		$this->openapi_spec->register_routes();
	}

	/**
	 * Maybe authenticate with bearer token.
	 *
	 * @param mixed $result The current authentication result.
	 * @return mixed Authentication result.
	 */
	/**
	 * Maybe authenticate with bearer token.
	 *
	 * @param mixed $result The current authentication result.
	 * @return mixed Authentication result.
	 */
	public function maybe_auth_bearer( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		$auth_header = $this->get_auth_header();
		if ( ! $auth_header || stripos( $auth_header, 'Bearer ' ) !== 0 ) {
			return $result;
		}

		$token = trim( substr( $auth_header, 7 ) );

		// Try JWT authentication.
		$jwt_result = $this->auth_jwt->authenticate_bearer( $token );
		if ( ! is_wp_error( $jwt_result ) ) {
			return $jwt_result;
		}

		return $jwt_result;
	}

	/**
	 * Get the authorization header.
	 *
	 * @return string Authorization header value.
	 */
	private function get_auth_header(): string {
		$auth_header = '';

		if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$auth_header = sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) );
		} elseif ( isset( $_SERVER['Authorization'] ) ) {
			$auth_header = sanitize_text_field( wp_unslash( $_SERVER['Authorization'] ) );
		} elseif ( function_exists( 'apache_request_headers' ) ) {
			$headers     = apache_request_headers();
			$auth_header = $headers['Authorization'] ?? '';
		}

		return $auth_header;
	}

	/**
	 * Activate the plugin.
	 */
	public function activate(): void {
		$this->create_refresh_tokens_table();
	}

	/**
	 * Deactivate the plugin.
	 */
	public function deactivate(): void {
		global $wpdb;

		// Delete all refresh tokens from database table.
		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );

		// Drop the refresh tokens table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		// Delete WordPress options.
		delete_option( JM_JWTAuthPro\JWT_Auth_Pro_Admin_Settings::OPTION_JWT_SETTINGS );
		delete_option( JM_JWTAuthPro\JWT_Auth_Pro_Admin_Settings::OPTION_GENERAL_SETTINGS );
		delete_option( 'jwt_auth_cookie_config' );

		// Clear any transients that might have been set.
		delete_transient( 'jwt_auth_pro_version' );
	}

	/**
	 * Create the refresh tokens table.
	 */
	private function create_refresh_tokens_table(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            token_hash varchar(255) NOT NULL,
            expires_at bigint(20) NOT NULL,
            revoked_at bigint(20) DEFAULT NULL,
            issued_at bigint(20) NOT NULL,
            user_agent varchar(500) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at bigint(20) DEFAULT NULL,
            is_revoked tinyint(1) DEFAULT 0,
            token_type varchar(50) DEFAULT 'jwt',
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY token_hash (token_hash),
            KEY expires_at (expires_at),
            KEY token_type (token_type)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Back-compat public wrapper expected by tests.
	 */
	public function create_jwt_tables(): void {
		$this->create_refresh_tokens_table();
	}

	/**
	 * Check if JWT secret is configured.
	 * Shows admin notice if not configured.
	 */
	public function check_jwt_secret(): void {
		// Check if secret is defined in constant.
		if ( defined( 'JWT_AUTH_PRO_SECRET' ) && JWT_AUTH_PRO_SECRET !== '' ) {
			return;
		}

		// Check if secret is in admin settings (lazy check without loading admin class).
		$jwt_settings = get_option( 'jwt_auth_pro_settings', array() );
		if ( ! empty( $jwt_settings['secret_key'] ) ) {
			return;
		}

		// No secret found - show admin notice.
		add_action( 'admin_notices', array( $this, 'missing_config_notice' ) );
	}

	/**
	 * Display missing configuration notice.
	 */
	public function missing_config_notice(): void {
		$settings_url = admin_url( 'options-general.php?page=juanma-jwt-auth-pro' );
		echo '<div class="notice notice-error"><p>';
		echo '<strong>JuanMa JWT Auth Pro:</strong> JWT Secret Key is required for the plugin to work. ';
		echo '<a href="' . esc_url( $settings_url ) . '">Configure it in the plugin settings</a> ';
		echo 'or define <code>JWT_AUTH_PRO_SECRET</code> in your wp-config.php file.';
		echo '</p></div>';
	}

}

new JWT_Auth_Pro();
