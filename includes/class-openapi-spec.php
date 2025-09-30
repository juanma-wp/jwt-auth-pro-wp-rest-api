<?php

/**
 * OpenAPI Specification Generator
 *
 * This class generates the OpenAPI 3.0 specification for the JWT Auth Pro plugin endpoints.
 * It provides a dynamic API documentation that can be consumed by Swagger UI and other OpenAPI tools.
 *
 * @package   JWTAuthPro
 * @author    Juan Manuel Garrido
 * @copyright 2025 Juan Manuel Garrido
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAPI Specification Class.
 *
 * Generates and serves OpenAPI 3.0 specification for JWT endpoints.
 */
class JWT_Auth_Pro_OpenAPI_Spec {

	/**
	 * REST API namespace.
	 */
	private const REST_NAMESPACE = 'jwt/v1';


	/**
	 * Register REST API routes for OpenAPI documentation.
	 */
	public function register_routes(): void {
		// Only register the OpenAPI spec endpoint in REST API.
		register_rest_route(
			self::REST_NAMESPACE,
			'/openapi',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_openapi_spec' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get the OpenAPI specification.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function get_openapi_spec( WP_REST_Request $request ): void {
		$yaml_file = JWT_AUTH_PRO_PLUGIN_DIR . 'openapi.yml';

		if ( ! file_exists( $yaml_file ) ) {
			status_header( 404 );
			header( 'Content-Type: application/json' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wp_json_encode( array( 'error' => 'OpenAPI specification file not found' ) );
			exit;
		}

		// Read and output YAML directly with proper headers.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$yaml_content = file_get_contents( $yaml_file );

		// Send YAML content directly.
		header( 'Content-Type: application/x-yaml' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $yaml_content;
		exit;
	}
}