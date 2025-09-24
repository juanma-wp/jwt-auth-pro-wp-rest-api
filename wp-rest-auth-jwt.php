<?php

/**
 * Plugin Name: WP REST Auth JWT
 * Description: Simple, secure JWT authentication for WordPress REST API with HttpOnly refresh tokens
 * Version: 1.0.0
 * Author: WordPress Developer
 * Author URI: https://github.com/juanma-wp/wp-rest-auth-jwt
 * Plugin URI: https://github.com/juanma-wp/wp-rest-auth-jwt
 * Text Domain: wp-rest-auth-jwt
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * WP REST Auth JWT - Simple, secure JWT authentication for WordPress REST API
 *
 * This plugin provides JWT (JSON Web Token) authentication for WordPress REST API
 * endpoints, designed specifically for Single Page Applications (SPAs) and mobile apps
 * that need stateless authentication without the complexity of OAuth2.
 *
 * Features:
 * - JWT access tokens with configurable expiration
 * - HTTP-only refresh tokens for enhanced security
 * - User authentication and authorization
 * - Token refresh and revocation
 * - CORS support for cross-origin requests
 * - Built-in security best practices
 * - WordPress coding standards compliant
 *
 * @package   WPRESTAuthJWT
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @link      https://github.com/juanma-wp/wp-rest-auth-jwt
 * @since     1.0.0
 *
 * @wordpress-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_REST_AUTH_JWT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_REST_AUTH_JWT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_REST_AUTH_JWT_VERSION', '1.0.0' );

/**
 * Main plugin class for WP REST Auth JWT.
 *
 * @package WPRESTAuthJWT
 */
class WP_REST_Auth_JWT {


	/**
	 * Auth JWT instance.
	 *
	 * @var Auth_JWT
	 */
	private $auth_jwt;

	/**
	 * Admin settings instance.
	 *
	 * @var WP_REST_Auth_JWT_Admin_Settings
	 */
	private $admin_settings;

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
	public function init() {
		$this->load_dependencies();
		$this->setup_constants();
		$this->init_hooks();
	}

	/**
	 * Load plugin dependencies.
	 */
	private function load_dependencies() {
		require_once WP_REST_AUTH_JWT_PLUGIN_DIR . 'includes/helpers.php';
		require_once WP_REST_AUTH_JWT_PLUGIN_DIR . 'includes/class-admin-settings.php';
		require_once WP_REST_AUTH_JWT_PLUGIN_DIR . 'includes/class-auth-jwt.php';

		// Initialize admin settings.
		if ( is_admin() ) {
			$this->admin_settings = new WP_REST_Auth_JWT_Admin_Settings();
		}

		$this->auth_jwt = new Auth_JWT();
	}

	/**
	 * Setup plugin constants.
	 */
	private function setup_constants() {
		$jwt_settings = WP_REST_Auth_JWT_Admin_Settings::get_jwt_settings();

		// Setup JWT constants from admin settings or fallback to wp-config.php.
		if ( ! defined( 'WP_JWT_AUTH_SECRET' ) ) {
			$secret = $jwt_settings['secret_key'] ?? '';
			if ( ! empty( $secret ) ) {
				define( 'WP_JWT_AUTH_SECRET', $secret );
			} else {
				// Check if it's defined in wp-config.php as fallback.
				if ( ! defined( 'WP_JWT_AUTH_SECRET' ) ) {
					add_action( 'admin_notices', array( $this, 'missing_config_notice' ) );
					return;
				}
			}
		}

		// Back-compat constant expected by some tests.
		if ( ! defined( 'WP_JWT_SECRET' ) ) {
			define( 'WP_JWT_SECRET', WP_JWT_AUTH_SECRET );
		}

		// Set token expiration times from admin settings.
		if ( ! defined( 'WP_JWT_ACCESS_TTL' ) ) {
			define( 'WP_JWT_ACCESS_TTL', $jwt_settings['access_token_expiry'] ?? 3600 );
		}

		if ( ! defined( 'WP_JWT_REFRESH_TTL' ) ) {
			define( 'WP_JWT_REFRESH_TTL', $jwt_settings['refresh_token_expiry'] ?? 2592000 );
		}
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_filter( 'rest_authentication_errors', array( $this, 'maybe_auth_bearer' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		$this->auth_jwt->register_routes();
	}

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
	private function get_auth_header() {
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
	public function activate() {
		$this->create_refresh_tokens_table();
	}

	/**
	 * Deactivate the plugin.
	 */
	public function deactivate() {
		// Clean up refresh tokens on deactivation.
		global $wpdb;
		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}jwt_refresh_tokens WHERE expires_at < %d", time() ) );
	}

	/**
	 * Create the refresh tokens table.
	 */
	private function create_refresh_tokens_table() {
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
	public function create_jwt_tables() {
		$this->create_refresh_tokens_table();
	}

	/**
	 * Display missing configuration notice.
	 */
	public function missing_config_notice() {
		$settings_url = admin_url( 'options-general.php?page=wp-rest-auth-jwt' );
		echo '<div class="notice notice-error"><p>';
		echo '<strong>WP REST Auth JWT:</strong> JWT Secret Key is required for the plugin to work. ';
		echo '<a href="' . esc_url( $settings_url ) . '">Configure it in the plugin settings</a> ';
		echo 'or define <code>WP_JWT_AUTH_SECRET</code> in your wp-config.php file.';
		echo '</p></div>';
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( is_admin() ) {
			wp_enqueue_script(
				'wp-rest-auth-jwt-admin',
				WP_REST_AUTH_JWT_PLUGIN_URL . 'assets/admin.js',
				array( 'jquery' ),
				WP_REST_AUTH_JWT_VERSION,
				true
			);

			wp_localize_script(
				'wp-rest-auth-jwt-admin',
				'wpRestAuthJWT',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wp_rest_auth_jwt_nonce' ),
					'restUrl' => rest_url(),
				)
			);
		}
	}
}

new WP_REST_Auth_JWT();
