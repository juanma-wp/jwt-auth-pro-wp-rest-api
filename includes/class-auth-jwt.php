<?php

/**
 * Simple JWT Authentication class with refresh token support
 * Focused, clean implementation without OAuth2 complexity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Auth_JWT {


	const ISSUER              = 'wp-rest-auth-jwt';
	const REFRESH_COOKIE_NAME = 'wp_jwt_refresh_token';

	public function register_routes(): void {
		register_rest_route(
			'jwt/v1',
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
			'jwt/v1',
			'/refresh',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => array( $this, 'refresh_access_token' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'jwt/v1',
			'/logout',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => array( $this, 'logout' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'jwt/v1',
			'/verify',
			array(
				'methods'             => array( 'GET' ),
				'callback'            => array( $this, 'verify_token' ),
				'permission_callback' => '__return_true',
			)
		);

		// Add CORS support
		add_action( 'rest_api_init', array( $this, 'add_cors_support' ) );
	}

	public function add_cors_support(): void {
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter(
			'rest_pre_serve_request',
			function ( $served, $result, $request, $server ) {
				wp_auth_jwt_maybe_add_cors_headers();
				return $served;
			},
			15,
			4
		);
	}

	// Compatibility: generate an access token for a user id
	public function generate_access_token( int $user_id ): string {
		$now    = time();
		$claims = array(
			'iss' => self::ISSUER,
			'sub' => (string) $user_id,
			'iat' => $now,
			'exp' => $now + WP_JWT_ACCESS_TTL,
			'jti' => wp_auth_jwt_generate_token( 16 ),
		);
		return wp_auth_jwt_encode( $claims, WP_JWT_AUTH_SECRET );
	}

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

		// Generate access token (JWT)
		$now           = time();
		$access_claims = array(
			'iss'   => self::ISSUER,
			'sub'   => (string) $user->ID,
			'iat'   => $now,
			'exp'   => $now + WP_JWT_ACCESS_TTL,
			'roles' => array_values( $user->roles ),
			'jti'   => wp_auth_jwt_generate_token( 16 ),
		);

		$access_token = wp_auth_jwt_encode( $access_claims, WP_JWT_AUTH_SECRET );

		// Generate refresh token
		$refresh_token   = wp_auth_jwt_generate_token( 64 );
		$refresh_expires = $now + WP_JWT_REFRESH_TTL;

		// Store refresh token
		$this->store_refresh_token( $user->ID, $refresh_token, $refresh_expires );

		// Set refresh token as HTTPOnly cookie
		wp_auth_jwt_set_cookie(
			self::REFRESH_COOKIE_NAME,
			$refresh_token,
			$refresh_expires,
			'/wp-json/jwt/v1/',
			true, // HTTPOnly
			true  // Secure
		);

		return wp_auth_jwt_success_response(
			array(
				'access_token' => $access_token,
				'token_type'   => 'Bearer',
				'expires_in'   => WP_JWT_ACCESS_TTL,
				'user'         => wp_auth_jwt_format_user_data( $user ),
			),
			'Authentication successful'
		);
	}

	public function refresh_access_token( WP_REST_Request $request ) {
		wp_auth_jwt_maybe_add_cors_headers();

		$refresh_token = $_COOKIE[ self::REFRESH_COOKIE_NAME ] ?? '';

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

		// Generate new access token
		$now           = time();
		$access_claims = array(
			'iss'   => self::ISSUER,
			'sub'   => (string) $user->ID,
			'iat'   => $now,
			'exp'   => $now + WP_JWT_ACCESS_TTL,
			'roles' => array_values( $user->roles ),
			'jti'   => wp_auth_jwt_generate_token( 16 ),
		);

		$access_token = wp_auth_jwt_encode( $access_claims, WP_JWT_AUTH_SECRET );

		// Optionally rotate refresh token for better security
		if ( apply_filters( 'wp_auth_jwt_rotate_refresh_token', true ) ) {
			$new_refresh_token = wp_auth_jwt_generate_token( 64 );
			$refresh_expires   = $now + WP_JWT_REFRESH_TTL;

			// Update refresh token
			$this->update_refresh_token( $token_data['id'], $new_refresh_token, $refresh_expires );

			// Set new refresh token cookie
			wp_auth_jwt_set_cookie(
				self::REFRESH_COOKIE_NAME,
				$new_refresh_token,
				$refresh_expires,
				'/wp-json/jwt/v1/',
				true, // HTTPOnly
				true  // Secure
			);
		}

		return wp_auth_jwt_success_response(
			array(
				'access_token' => $access_token,
				'token_type'   => 'Bearer',
				'expires_in'   => WP_JWT_ACCESS_TTL,
			),
			'Token refreshed successfully'
		);
	}

	public function logout( WP_REST_Request $request ) {
		wp_auth_jwt_maybe_add_cors_headers();

		$refresh_token = $_COOKIE[ self::REFRESH_COOKIE_NAME ] ?? '';

		if ( ! empty( $refresh_token ) ) {
			$this->revoke_refresh_token( $refresh_token );
		}

		// Delete refresh token cookie
		wp_auth_jwt_delete_cookie( self::REFRESH_COOKIE_NAME, '/wp-json/jwt/v1/' );

		return wp_auth_jwt_success_response( array(), 'Logout successful' );
	}

	public function verify_token( WP_REST_Request $request ) {
		wp_auth_jwt_maybe_add_cors_headers();

		// Support bearer header directly on verify
		$auth_header = $request->get_header( 'Authorization' );
		if ( $auth_header && stripos( $auth_header, 'Bearer ' ) === 0 ) {
			$token       = trim( substr( $auth_header, 7 ) );
			$auth_result = $this->authenticate_bearer( $token );
			if ( is_wp_error( $auth_result ) ) {
				return $auth_result;
			}
		}

		$user = wp_get_current_user();

		if ( ! $user || ! $user->ID ) {
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

	public function authenticate_bearer( string $token ) {
		$payload = wp_auth_jwt_decode( $token, WP_JWT_AUTH_SECRET );

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

	public function store_refresh_token( int $user_id, string $refresh_token, int $expires_at ): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';
		$token_hash = wp_auth_jwt_hash_token( $refresh_token, WP_JWT_AUTH_SECRET );

		$result = $wpdb->insert(
			$table_name,
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

		return $result !== false;
	}

	private function validate_refresh_token( string $refresh_token ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';
		$token_hash = wp_auth_jwt_hash_token( $refresh_token, WP_JWT_AUTH_SECRET );
		$now        = time();

		$token_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE token_hash = %s AND expires_at > %d AND is_revoked = 0 AND token_type = 'jwt'",
				$token_hash,
				$now
			),
			ARRAY_A
		);

		if ( ! $token_data ) {
			return wp_auth_jwt_error_response(
				'invalid_refresh_token',
				'Invalid or expired refresh token',
				401
			);
		}

		return $token_data;
	}

	private function update_refresh_token( int $token_id, string $new_refresh_token, int $expires_at ): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';
		$token_hash = wp_auth_jwt_hash_token( $new_refresh_token, WP_JWT_AUTH_SECRET );

		$result = $wpdb->update(
			$table_name,
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

		return $result !== false;
	}

	public function revoke_refresh_token( string $refresh_token ): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';
		$token_hash = wp_auth_jwt_hash_token( $refresh_token, WP_JWT_AUTH_SECRET );

		$result = $wpdb->update(
			$table_name,
			array( 'is_revoked' => 1 ),
			array(
				'token_hash' => $token_hash,
				'token_type' => 'jwt',
			),
			array( '%d' ),
			array( '%s', '%s' )
		);

		return $result !== false;
	}

	// Compatibility: expose simple getters for tests
	public function get_user_refresh_tokens( int $user_id ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';
		$results    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE user_id = %d AND token_type = 'jwt'",
				$user_id
			),
			ARRAY_A
		);
		return $results ?: array();
	}

	public function revoke_user_token( int $user_id, int $token_id ): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';
		$updated    = $wpdb->update(
			$table_name,
			array( 'is_revoked' => 1 ),
			array(
				'id'         => $token_id,
				'user_id'    => $user_id,
				'token_type' => 'jwt',
			),
			array( '%d' ),
			array( '%d', '%d', '%s' )
		);
		return $updated !== false;
	}

	// Compatibility: whoami-like endpoint for tests
	public function whoami( WP_REST_Request $request = null ) {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->ID ) {
			return false;
		}
		return true;
	}

	public function clean_expired_tokens(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwt_refresh_tokens';

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE expires_at < %d AND token_type = 'jwt'",
				time()
			)
		);
	}
}
