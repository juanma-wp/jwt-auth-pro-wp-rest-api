<?php
/**
 * Plugin Name: WP OAuth Login
 * Plugin URI: https://github.com/myorg/wp-oauth-login
 * Description: WordPress OAuth login with JWT authentication using shared JWT core library
 * Version: 1.0.0
 * Author: Your Organization
 * Author URI: https://yourorganization.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-oauth-login
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * @package MyOrg\WPOAuthLogin
 */

namespace MyOrg\WPOAuthLogin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'WP_OAUTH_LOGIN_VERSION', '1.0.0' );
define( 'WP_OAUTH_LOGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_OAUTH_LOGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader.
if ( file_exists( WP_OAUTH_LOGIN_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once WP_OAUTH_LOGIN_PLUGIN_DIR . 'vendor/autoload.php';
}

use MyOrg\JWTAuthCore\TokenManager;

/**
 * Main plugin class
 */
class WP_OAuth_Login {

	/**
	 * Token manager instance
	 *
	 * @var TokenManager
	 */
	private $token_manager;

	/**
	 * OAuth providers configuration
	 *
	 * @var array
	 */
	private $providers = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize the token manager with WordPress secret
		$secret = defined( 'JWT_AUTH_SECRET' ) ? JWT_AUTH_SECRET : wp_salt( 'auth' );
		$this->token_manager = new TokenManager( $secret, 7200 ); // 2 hour expiration for OAuth

		// Register hooks
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'init', array( $this, 'load_providers' ) );
	}

	/**
	 * Load OAuth providers
	 */
	public function load_providers(): void {
		// Example providers configuration
		$this->providers = apply_filters(
			'wp_oauth_login_providers',
			array(
				'google' => array(
					'name'          => 'Google',
					'client_id'     => get_option( 'wp_oauth_google_client_id', '' ),
					'client_secret' => get_option( 'wp_oauth_google_client_secret', '' ),
					'redirect_uri'  => rest_url( 'wp-oauth-login/v1/callback/google' ),
				),
				'github' => array(
					'name'          => 'GitHub',
					'client_id'     => get_option( 'wp_oauth_github_client_id', '' ),
					'client_secret' => get_option( 'wp_oauth_github_client_secret', '' ),
					'redirect_uri'  => rest_url( 'wp-oauth-login/v1/callback/github' ),
				),
			)
		);
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes(): void {
		// OAuth authorization endpoint
		register_rest_route(
			'wp-oauth-login/v1',
			'/authorize/(?P<provider>[a-z]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'authorize' ),
				'permission_callback' => '__return_true',
			)
		);

		// OAuth callback endpoint
		register_rest_route(
			'wp-oauth-login/v1',
			'/callback/(?P<provider>[a-z]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Validate token endpoint
		register_rest_route(
			'wp-oauth-login/v1',
			'/validate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'validate' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

		// Refresh token endpoint
		register_rest_route(
			'wp-oauth-login/v1',
			'/refresh',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'refresh' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * OAuth authorization redirect
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function authorize( $request ) {
		$provider = $request->get_param( 'provider' );

		if ( ! isset( $this->providers[ $provider ] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid OAuth provider',
				),
				400
			);
		}

		// This is a simplified example - in production, you'd redirect to the OAuth provider
		return new \WP_REST_Response(
			array(
				'success'      => true,
				'provider'     => $provider,
				'redirect_uri' => $this->providers[ $provider ]['redirect_uri'],
				'message'      => 'Redirect to OAuth provider authorization page',
			),
			200
		);
	}

	/**
	 * OAuth callback handler
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function callback( $request ) {
		$provider = $request->get_param( 'provider' );
		$code     = $request->get_param( 'code' );

		if ( ! isset( $this->providers[ $provider ] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid OAuth provider',
				),
				400
			);
		}

		// This is a simplified example - in production, you'd exchange the code for a token
		// and get user information from the OAuth provider

		// For demonstration, create a mock user payload
		$payload = array(
			'user_id'   => 1, // In production, get from OAuth provider or create user
			'username'  => 'oauth_user',
			'provider'  => $provider,
			'oauth_id'  => 'oauth_user_id_from_provider',
		);

		$token = $this->token_manager->generate_token( $payload );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'token'   => $token,
				'provider' => $provider,
			),
			200
		);
	}

	/**
	 * Validate JWT token
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function validate( $request ) {
		$token = $request->get_param( 'token' );

		$payload = $this->token_manager->validate_token( $token );

		if ( false === $payload ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid or expired token',
				),
				401
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'payload' => $payload,
			),
			200
		);
	}

	/**
	 * Refresh JWT token
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function refresh( $request ) {
		$token = $request->get_param( 'token' );

		$new_token = $this->token_manager->refresh_token( $token );

		if ( false === $new_token ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid or expired token',
				),
				401
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'token'   => $new_token,
			),
			200
		);
	}
}

// Initialize the plugin
new WP_OAuth_Login();
