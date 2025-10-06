<?php
/**
 * Plugin Name: WP JWT Login
 * Plugin URI: https://github.com/myorg/wp-jwt-login
 * Description: WordPress login with JWT authentication using shared JWT core library
 * Version: 1.0.0
 * Author: Your Organization
 * Author URI: https://yourorganization.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-jwt-login
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * @package MyOrg\WPJWTLogin
 */

namespace MyOrg\WPJWTLogin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'WP_JWT_LOGIN_VERSION', '1.0.0' );
define( 'WP_JWT_LOGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_JWT_LOGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader.
if ( file_exists( WP_JWT_LOGIN_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once WP_JWT_LOGIN_PLUGIN_DIR . 'vendor/autoload.php';
}

use MyOrg\JWTAuthCore\TokenManager;

/**
 * Main plugin class
 */
class WP_JWT_Login {

	/**
	 * Token manager instance
	 *
	 * @var TokenManager
	 */
	private $token_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize the token manager with WordPress secret
		$secret = defined( 'JWT_AUTH_SECRET' ) ? JWT_AUTH_SECRET : wp_salt( 'auth' );
		$this->token_manager = new TokenManager( $secret, 3600 );

		// Register hooks
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'wp-jwt-login/v1',
			'/authenticate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'authenticate' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'username' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_user',
					),
					'password' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

		register_rest_route(
			'wp-jwt-login/v1',
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
	}

	/**
	 * Authenticate user and generate JWT token
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function authenticate( $request ) {
		$username = $request->get_param( 'username' );
		$password = $request->get_param( 'password' );

		// Authenticate user
		$user = wp_authenticate( $username, $password );

		if ( is_wp_error( $user ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid credentials',
				),
				401
			);
		}

		// Generate JWT token
		$payload = array(
			'user_id'   => $user->ID,
			'username'  => $user->user_login,
			'user_email' => $user->user_email,
		);

		$token = $this->token_manager->generate_token( $payload );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'token'   => $token,
				'user'    => array(
					'id'       => $user->ID,
					'username' => $user->user_login,
					'email'    => $user->user_email,
				),
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
}

// Initialize the plugin
new WP_JWT_Login();
