<?php

/**
 * JWT Authentication Handler Class
 *
 * This class handles all JWT token operations including authentication, token generation,
 * validation, and refresh token management. It provides REST API endpoints for user
 * authentication and token management operations.
 *
 * The class implements a secure JWT authentication system with:
 * - Access tokens for API authentication
 * - HTTP-only refresh tokens for enhanced security
 * - Token validation and expiration handling
 * - User session management
 * - Database storage for refresh tokens
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
 * JWT Authentication Handler Class.
 *
 * Handles all JWT token operations including authentication, token generation,
 * validation, and refresh token management.
 */
class Auth_JWT {

	const ISSUER                 = 'wp-rest-auth-jwt';
	const REFRESH_COOKIE_NAME    = 'wp_jwt_refresh_token';
	private const REST_NAMESPACE = 'jwt/v1';
	private const COOKIE_PATH    = '/wp-json/jwt/v1/';

	/**
	 * Register REST API routes for JWT authentication.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/token',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => array( $this, 'issue_token' ),
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
			self::REST_NAMESPACE,
			'/refresh',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => array( $this, 'refresh_access_token' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/logout',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => array( $this, 'logout' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/verify',
			array(
				'methods'             => array( 'GET' ),
				'callback'            => array( $this, 'verify_token' ),
				'permission_callback' => '__return_true',
			)
		);

		// Add CORS support.
		add_action( 'rest_api_init', array( $this, 'add_cors_support' ) );
	}

	/**
	 * Add CORS support for REST API requests.
	 */
	public function add_cors_support(): void {
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter(
			'rest_pre_serve_request',
			function ( $served, $_result, $_request, $_server ) {
				wp_auth_jwt_maybe_add_cors_headers();
				return $served;
			},
			15,
			4
		);
	}

	/**
	 * Compatibility: generate an access token for a user id.
	 *
	 * @param int   $user_id      User ID to generate token for.
	 * @param array $extra_claims Optional extra claims to merge into the token.
	 * @return string Generated JWT access token.
	 */
	public function generate_access_token( int $user_id, array $extra_claims = array() ): string {
		$now    = time();
		$claims = array(
			'iss' => self::ISSUER,
			'sub' => (string) $user_id,
			'iat' => $now,
			'exp' => $now + JWT_AUTH_PRO_ACCESS_TTL,
			'jti' => wp_auth_jwt_generate_token( 16 ),
		);
		if ( ! empty( $extra_claims ) ) {
			$claims = array_merge( $claims, $extra_claims );
		}
		return wp_auth_jwt_encode( $claims, JWT_AUTH_PRO_SECRET );
	}

	/**
	 * Issue a new access token.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function issue_token( WP_REST_Request $request ) {
		wp_auth_jwt_maybe_add_cors_headers();

		$username = $request->get_param( 'username' );
		$password = $request->get_param( 'password' );

		if ( empty( $username ) || empty( $password ) ) {
			return wp_auth_jwt_error_response(
				'missing_credentials',
				'Username and password are required',
				400
			);
		}

		$user = wp_authenticate( $username, $password );

		if ( is_wp_error( $user ) ) {
			return wp_auth_jwt_error_response(
				'invalid_credentials',
				'Invalid username or password',
				403
			);
		}

		// Generate access token (JWT).
		$now           = time();
		$access_claims = array(
			'roles' => array_values( $user->roles ),
		);
		$access_token  = $this->generate_access_token( (int) $user->ID, $access_claims );

		// Generate refresh token.
		$refresh_token   = wp_auth_jwt_generate_token( 64 );
		$refresh_expires = $now + JWT_AUTH_PRO_REFRESH_TTL;

		// Store refresh token.
		$this->store_refresh_token( $user->ID, $refresh_token, $refresh_expires );

		// Set refresh token as HTTPOnly cookie with environment-aware configuration.
		// Path, httponly, and secure are auto-detected based on environment.
		wp_auth_jwt_set_cookie(
			self::REFRESH_COOKIE_NAME,
			$refresh_token,
			$refresh_expires
		);

		return wp_auth_jwt_success_response(
			array(
				'access_token' => $access_token,
				'token_type'   => 'Bearer',
				'expires_in'   => JWT_AUTH_PRO_ACCESS_TTL,
				'user'         => wp_auth_jwt_format_user_data( $user ),
			),
			'Authentication successful'
		);
	}

	/**
	 * Refresh an access token using a refresh token.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function refresh_access_token( WP_REST_Request $request ) {
		wp_auth_jwt_maybe_add_cors_headers();

		$refresh_token = isset( $_COOKIE[ self::REFRESH_COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::REFRESH_COOKIE_NAME ] ) ) : '';

		if ( empty( $refresh_token ) ) {
			return wp_auth_jwt_error_response(
				'missing_refresh_token',
				'Refresh token not found',
				401
			);
		}

		$token_data = $this->validate_refresh_token( $refresh_token );

		if ( is_wp_error( $token_data ) ) {
			return $token_data;
		}

		$user = get_user_by( 'id', $token_data['user_id'] );
		if ( ! $user ) {
			return wp_auth_jwt_error_response(
				'invalid_user',
				'User not found',
				401
			);
		}

		// Generate new access token.
		$now           = time();
		$access_claims = array(
			'roles' => array_values( $user->roles ),
		);
		$access_token  = $this->generate_access_token( (int) $user->ID, $access_claims );

		// Optionally rotate refresh token for better security.
		if ( apply_filters( 'wp_auth_jwt_rotate_refresh_token', true ) ) {
			$new_refresh_token = wp_auth_jwt_generate_token( 64 );
			$refresh_expires   = $now + JWT_AUTH_PRO_REFRESH_TTL;

			// Update refresh token.
			$this->update_refresh_token( $token_data['id'], $new_refresh_token, $refresh_expires );

			// Set new refresh token cookie with environment-aware configuration.
			// Path, httponly, and secure are auto-detected based on environment.
			wp_auth_jwt_set_cookie(
				self::REFRESH_COOKIE_NAME,
				$new_refresh_token,
				$refresh_expires
			);
		}

		return wp_auth_jwt_success_response(
			array(
				'access_token' => $access_token,
				'token_type'   => 'Bearer',
				'expires_in'   => JWT_AUTH_PRO_ACCESS_TTL,
			),
			'Token refreshed successfully'
		);
	}

	/**
	 * Logout and revoke refresh token.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response Success response.
	 */
	public function logout( WP_REST_Request $request ): WP_REST_Response {
		wp_auth_jwt_maybe_add_cors_headers();

		$refresh_token = isset( $_COOKIE[ self::REFRESH_COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::REFRESH_COOKIE_NAME ] ) ) : '';

		if ( ! empty( $refresh_token ) ) {
			$this->revoke_refresh_token( $refresh_token );
		}

		// Delete refresh token cookie with environment-aware path detection.
		wp_auth_jwt_delete_cookie( self::REFRESH_COOKIE_NAME );

		return wp_auth_jwt_success_response( array(), 'Logout successful' );
	}

	/**
	 * Verify a JWT token.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function verify_token( WP_REST_Request $request ) {
		wp_auth_jwt_maybe_add_cors_headers();

		// Support bearer header directly on verify.
		$auth_header = $request->get_header( 'Authorization' );
		if ( $auth_header && 0 === stripos( $auth_header, 'Bearer ' ) ) {
			$token       = trim( substr( $auth_header, 7 ) );
			$auth_result = $this->authenticate_bearer( $token );
			if ( is_wp_error( $auth_result ) ) {
				return $auth_result;
			}
		}

		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return wp_auth_jwt_error_response(
				'not_authenticated',
				'No valid token provided',
				401
			);
		}

		return wp_auth_jwt_success_response(
			array(
				'valid' => true,
				'user'  => wp_auth_jwt_format_user_data( $user, true ),
			),
			'Token is valid'
		);
	}

	/**
	 * Authenticate using a bearer token.
	 *
	 * @param string $token The JWT token.
	 * @return WP_User|WP_Error User object or error.
	 */
	public function authenticate_bearer( string $token ) {
		$payload = wp_auth_jwt_decode( $token, JWT_AUTH_PRO_SECRET );

		if ( ! $payload ) {
			return new WP_Error(
				'invalid_token',
				'Invalid or expired JWT token',
				array( 'status' => 401 )
			);
		}

		$user_id = intval( $payload['sub'] ?? 0 );
		if ( ! $user_id ) {
			return new WP_Error(
				'invalid_token_subject',
				'Invalid token subject',
				array( 'status' => 401 )
			);
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'invalid_token_user',
				'User not found',
				array( 'status' => 401 )
			);
		}

		wp_set_current_user( $user->ID );
		return $user;
	}

	/**
	 * Store a refresh token in the database.
	 *
	 * @param int    $user_id      User ID to associate with token.
	 * @param string $refresh_token Raw refresh token.
	 * @param int    $expires_at   Token expiration timestamp.
	 * @return bool True on success, false on failure.
	 */
	public function store_refresh_token( int $user_id, string $refresh_token, int $expires_at ): bool {
		global $wpdb;

		$token_hash = wp_auth_jwt_hash_token( $refresh_token, JWT_AUTH_PRO_SECRET );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Purposeful direct insert for plugin-managed JWT storage; values are parameterized.
		$result = $wpdb->insert(
			$wpdb->prefix . 'jwt_refresh_tokens',
			array(
				'user_id'    => $user_id,
				'token_hash' => $token_hash,
				'expires_at' => $expires_at,
				'issued_at'  => time(),
				'created_at' => time(),
				'is_revoked' => 0,
				'token_type' => 'jwt',
				'user_agent' => wp_auth_jwt_get_user_agent(),
				'ip_address' => wp_auth_jwt_get_ip_address(),
			),
			array(
				'%d',
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			)
		);

		return false !== $result;
	}

	/**
	 * Validate refresh token.
	 *
	 * @param string $refresh_token The refresh token to validate.
	 * @return array|WP_Error Token data or error if invalid.
	 */
	private function validate_refresh_token( string $refresh_token ) {
		global $wpdb;

		$token_hash = wp_auth_jwt_hash_token( $refresh_token, JWT_AUTH_PRO_SECRET );
		$now        = time();

		$cache_key  = 'jwt_token_' . md5( $token_hash );
		$token_data = wp_cache_get( $cache_key, 'wp_rest_auth_jwt' );

		if ( false === $token_data ) {
			// Direct database query required for JWT token validation - no WordPress equivalent exists.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$token_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}jwt_refresh_tokens WHERE token_hash = %s AND expires_at > %d AND is_revoked = 0 AND token_type = 'jwt'",
					$token_hash,
					$now
				),
				ARRAY_A
			);

			if ( $token_data ) {
				wp_cache_set( $cache_key, $token_data, 'wp_rest_auth_jwt', 300 ); // Cache for 5 minutes.
			}
		}

		if ( ! $token_data ) {
			return wp_auth_jwt_error_response(
				'invalid_refresh_token',
				'Invalid or expired refresh token',
				401
			);
		}

		return $token_data;
	}

	/**
	 * Update an existing refresh token with new values.
	 *
	 * @param int    $token_id          Token record ID.
	 * @param string $new_refresh_token New refresh token value.
	 * @param int    $expires_at        New expiration timestamp.
	 * @return bool True on success, false on failure.
	 */
	private function update_refresh_token( int $token_id, string $new_refresh_token, int $expires_at ): bool {
		global $wpdb;

		$token_hash = wp_auth_jwt_hash_token( $new_refresh_token, JWT_AUTH_PRO_SECRET );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Purposeful direct update for plugin-managed JWT storage; values are parameterized.
		$result = $wpdb->update(
			$wpdb->prefix . 'jwt_refresh_tokens',
			array(
				'token_hash' => $token_hash,
				'expires_at' => $expires_at,
				'created_at' => time(),
				'user_agent' => wp_auth_jwt_get_user_agent(),
				'ip_address' => wp_auth_jwt_get_ip_address(),
			),
			array( 'id' => $token_id ),
			array( '%s', '%d', '%d', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Revoke a refresh token by marking it as revoked.
	 *
	 * @param string $refresh_token Token to revoke.
	 * @return bool True on success, false on failure.
	 */
	public function revoke_refresh_token( string $refresh_token ): bool {
		global $wpdb;

		$token_hash = wp_auth_jwt_hash_token( $refresh_token, JWT_AUTH_PRO_SECRET );

		// Clear cache first.
		$cache_key = 'jwt_token_' . md5( $token_hash );
		wp_cache_delete( $cache_key, 'wp_rest_auth_jwt' );

		// Direct database query required for JWT token revocation - no WordPress equivalent exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$wpdb->prefix . 'jwt_refresh_tokens',
			array( 'is_revoked' => 1 ),
			array(
				'token_hash' => $token_hash,
				'token_type' => 'jwt',
			),
			array( '%d' ),
			array( '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Compatibility: expose simple getters for tests.
	 *
	 * @param int $user_id User ID to get tokens for.
	 * @return array List of refresh tokens for the user.
	 */
	public function get_user_refresh_tokens( int $user_id ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Purposeful direct query for plugin-managed JWT storage; caching not applicable for short-lived token rows.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}jwt_refresh_tokens WHERE user_id = %d AND token_type = 'jwt'",
				$user_id
			),
			ARRAY_A
		);
		return $results ? $results : array();
	}

	/**
	 * Revoke a specific token for a user.
	 *
	 * @param int $user_id  User ID that owns the token.
	 * @param int $token_id Token record ID to revoke.
	 * @return bool True on success, false on failure.
	 */
	public function revoke_user_token( int $user_id, int $token_id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Purposeful direct query for plugin-managed JWT storage; values are parameterized.
		$updated = $wpdb->update(
			$wpdb->prefix . 'jwt_refresh_tokens',
			array( 'is_revoked' => 1 ),
			array(
				'id'         => $token_id,
				'user_id'    => $user_id,
				'token_type' => 'jwt',
			),
			array( '%d' ),
			array( '%d', '%d', '%s' )
		);
		return false !== $updated;
	}

	/**
	 * Compatibility: whoami-like endpoint for tests.
	 */
	/**
	 * Check if user is authenticated.
	 *
	 * @param WP_REST_Request|null $request Optional request object.
	 * @return bool True if authenticated, false otherwise.
	 */
	public function whoami( ?WP_REST_Request $request = null ): bool {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return false;
		}
		return true;
	}

	/**
	 * Clean up expired tokens from the database.
	 */
	public function clean_expired_tokens(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Purposeful cleanup of expired JWT rows; not a candidate for persistent caching.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}jwt_refresh_tokens WHERE expires_at < %d AND token_type = 'jwt'",
				time()
			)
		);
	}
}
