<?php
/**
 * Admin Settings for WP REST Auth JWT
 *
 * This class handles the WordPress admin interface for configuring JWT authentication
 * settings. It provides options for JWT token configuration, CORS settings, security
 * options, and other plugin-related settings through the WordPress settings API.
 *
 * The class creates admin pages, registers settings, validates input, and provides
 * methods to retrieve configuration values used throughout the plugin.
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

class WP_REST_Auth_JWT_Admin_Settings {

	const OPTION_GROUP            = 'wp_rest_auth_jwt_settings';
	const OPTION_JWT_SETTINGS     = 'wp_rest_auth_jwt_settings';
	const OPTION_GENERAL_SETTINGS = 'wp_rest_auth_jwt_general_settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function add_admin_menu(): void {
		add_options_page(
			'WP REST Auth JWT Settings',
			'WP REST Auth JWT',
			'manage_options',
			'wp-rest-auth-jwt',
			array( $this, 'admin_page' )
		);
	}

	public function register_settings(): void {
		// Register setting groups
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_JWT_SETTINGS,
			array(
				'sanitize_callback' => array( $this, 'sanitize_jwt_settings' ),
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_GENERAL_SETTINGS,
			array(
				'sanitize_callback' => array( $this, 'sanitize_general_settings' ),
			)
		);

		// JWT Settings Section
		add_settings_section(
			'jwt_settings',
			'JWT Authentication Settings',
			array( $this, 'jwt_settings_section' ),
			'wp-rest-auth-jwt-settings'
		);

		add_settings_field(
			'jwt_secret_key',
			'JWT Secret Key',
			array( $this, 'jwt_secret_key_field' ),
			'wp-rest-auth-jwt-settings',
			'jwt_settings'
		);

		add_settings_field(
			'jwt_access_token_expiry',
			'Access Token Expiry (seconds)',
			array( $this, 'jwt_access_token_expiry_field' ),
			'wp-rest-auth-jwt-settings',
			'jwt_settings'
		);

		add_settings_field(
			'jwt_refresh_token_expiry',
			'Refresh Token Expiry (seconds)',
			array( $this, 'jwt_refresh_token_expiry_field' ),
			'wp-rest-auth-jwt-settings',
			'jwt_settings'
		);

		// General Settings Section
		add_settings_section(
			'general_settings',
			'General Settings',
			array( $this, 'general_settings_section' ),
			'wp-rest-auth-jwt-general'
		);

		add_settings_field(
			'enable_debug_logging',
			'Enable Debug Logging',
			array( $this, 'enable_debug_logging_field' ),
			'wp-rest-auth-jwt-general',
			'general_settings'
		);

		add_settings_field(
			'cors_allowed_origins',
			'CORS Allowed Origins',
			array( $this, 'cors_allowed_origins_field' ),
			'wp-rest-auth-jwt-general',
			'general_settings'
		);
	}

	public function enqueue_admin_scripts( string $hook ): void {
		if ( $hook !== 'settings_page_wp-rest-auth-jwt' ) {
			return;
		}

		wp_enqueue_script(
			'wp-rest-auth-jwt-admin',
			plugin_dir_url( __DIR__ ) . 'assets/admin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'wp-rest-auth-jwt-admin',
			'wpRestAuthJWT',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp_rest_auth_jwt_nonce' ),
			)
		);
	}

	public function admin_page(): void {
		$active_tab = $_GET['tab'] ?? 'jwt';
		?>
		<div class="wrap">
			<h1>🔐 WP REST Auth JWT Settings</h1>
			<p class="description">Simple, secure JWT authentication for WordPress REST API</p>

			<nav class="nav-tab-wrapper">
				<a href="?page=wp-rest-auth-jwt&tab=jwt" class="nav-tab <?php echo $active_tab == 'jwt' ? 'nav-tab-active' : ''; ?>">JWT Settings</a>
				<a href="?page=wp-rest-auth-jwt&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General Settings</a>
				<a href="?page=wp-rest-auth-jwt&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help & Documentation</a>
			</nav>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );

				if ( $active_tab == 'jwt' ) {
					do_settings_sections( 'wp-rest-auth-jwt-settings' );
					submit_button();
				} elseif ( $active_tab == 'general' ) {
					do_settings_sections( 'wp-rest-auth-jwt-general' );
					submit_button();
				} elseif ( $active_tab == 'help' ) {
					$this->render_help_tab();
				}
				?>
			</form>
		</div>
		<?php
	}

	private function render_help_tab() {
		?>
		<div class="help-tab">
			<h2>Help & Documentation</h2>

			<div class="help-section">
				<h3>🔐 What is JWT Authentication?</h3>
				<p>JSON Web Tokens (JWT) provide a simple, stateless way to authenticate users with your WordPress REST API. Perfect for:</p>
				<ul>
					<li><strong>Single Page Applications (SPAs)</strong> - React, Vue, Angular apps</li>
					<li><strong>Mobile Applications</strong> - iOS, Android apps</li>
					<li><strong>API Integrations</strong> - Third-party services</li>
					<li><strong>Headless WordPress</strong> - Decoupled architectures</li>
				</ul>
			</div>

			<div class="help-section">
				<h3>🚀 Quick Start</h3>
				<h4>1. Login to get tokens:</h4>
				<pre><code>POST /wp-json/jwt/v1/token
{
	"username": "your_username",
	"password": "your_password"
}

Response:
{
	"success": true,
	"data": {
		"access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
		"token_type": "Bearer",
		"expires_in": 3600,
		"user": { ... }
	}
}</code></pre>

				<h4>2. Use the access token for API calls:</h4>
				<pre><code>GET /wp-json/wp/v2/posts
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...</code></pre>

				<h4>3. Refresh token when needed:</h4>
				<pre><code>POST /wp-json/jwt/v1/refresh
// Uses HTTPOnly cookie automatically</code></pre>
			</div>

			<div class="help-section">
				<h3>🔒 Security Features</h3>
				<ul>
					<li><strong>HTTPOnly Refresh Tokens:</strong> Refresh tokens stored in secure, HTTPOnly cookies</li>
					<li><strong>Token Rotation:</strong> Refresh tokens automatically rotate for better security</li>
					<li><strong>Configurable Expiration:</strong> Set custom expiration times for tokens</li>
					<li><strong>CORS Support:</strong> Proper CORS handling for cross-domain requests</li>
					<li><strong>IP & User Agent Tracking:</strong> Additional security metadata for tokens</li>
				</ul>
			</div>

			<div class="help-section">
				<h3>🛠️ Available Endpoints</h3>
				<ul>
					<li><code>POST /wp-json/jwt/v1/token</code> - Login and get access token</li>
					<li><code>POST /wp-json/jwt/v1/refresh</code> - Refresh access token</li>
					<li><code>GET /wp-json/jwt/v1/verify</code> - Verify current token and get user info</li>
					<li><code>POST /wp-json/jwt/v1/logout</code> - Logout and revoke refresh token</li>
				</ul>
			</div>

			<div class="help-section">
				<h3>⚙️ Configuration</h3>
				<p><strong>JWT Secret Key:</strong> A secure random string used to sign JWT tokens. Keep this secret and never share it.</p>
				<p><strong>Access Token Expiry:</strong> How long access tokens remain valid (default: 3600 seconds / 1 hour).</p>
				<p><strong>Refresh Token Expiry:</strong> How long refresh tokens remain valid (default: 2592000 seconds / 30 days).</p>
				<p><strong>CORS Origins:</strong> Domains allowed to make cross-origin requests to your API.</p>
			</div>

			<div class="help-section">
				<h3>🔧 Troubleshooting</h3>
				<h4>Common Issues:</h4>
				<ul>
					<li><strong>Invalid JWT Token:</strong> Check that your JWT secret key is properly configured</li>
					<li><strong>Token Expired:</strong> Implement proper token refresh logic in your application</li>
					<li><strong>CORS Errors:</strong> Add your frontend domain to the CORS allowed origins</li>
					<li><strong>Cookie Issues:</strong> Ensure your site uses HTTPS for HTTPOnly cookies to work properly</li>
				</ul>

				<h4>Debug Information:</h4>
				<p><strong>Plugin Version:</strong> <?php echo esc_html( WP_REST_AUTH_JWT_VERSION ); ?></p>
				<p><strong>WordPress Version:</strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></p>
				<p><strong>PHP Version:</strong> <?php echo esc_html( PHP_VERSION ); ?></p>
				<p><strong>SSL Enabled:</strong> <?php echo is_ssl() ? '✅ Yes' : '❌ No (HTTPOnly cookies may not work)'; ?></p>
			</div>

			<div class="help-section">
				<h3>📚 Need OAuth2?</h3>
				<p>This plugin provides simple JWT authentication. If you need more advanced features like:</p>
				<ul>
					<li>OAuth2 authorization flows</li>
					<li>Scoped permissions</li>
					<li>Third-party app authorization</li>
					<li>API Proxy for enhanced security</li>
				</ul>
				<p>Consider installing our companion plugin: <strong>WP REST Auth OAuth2</strong></p>
			</div>
		</div>
		<?php
	}

	// Section callbacks
	public function jwt_settings_section(): void {
		echo '<p>Configure JWT authentication settings. JWT tokens provide stateless authentication for your WordPress REST API.</p>';
	}

	public function general_settings_section(): void {
		echo '<p>General plugin settings and security options.</p>';
	}

	// Field callbacks
	public function jwt_secret_key_field(): void {
		$settings = get_option( self::OPTION_JWT_SETTINGS, array() );
		$value    = $settings['secret_key'] ?? '';
		?>
		<input type="password" id="jwt_secret_key" name="<?php echo esc_attr( self::OPTION_JWT_SETTINGS ); ?>[secret_key]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<button type="button" id="generate_jwt_secret" class="button">Generate New Secret</button>
		<button type="button" id="toggle_jwt_secret" class="button">Show/Hide</button>
		<p class="description">A secure random string used to sign JWT tokens. Generate a new one or enter your own (minimum 32 characters recommended).</p>

		<script>
		jQuery(document).ready(function($) {
			$('#generate_jwt_secret').click(function() {
				const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
				let secret = '';
				for (let i = 0; i < 64; i++) {
					secret += chars.charAt(Math.floor(Math.random() * chars.length));
				}
				$('#jwt_secret_key').val(secret);
			});

			$('#toggle_jwt_secret').click(function() {
				const field = $('#jwt_secret_key');
				field.attr('type', field.attr('type') === 'password' ? 'text' : 'password');
			});
		});
		</script>
		<?php
	}

	/**
	 * Render the JWT access token expiry field.
	 */
	public function jwt_access_token_expiry_field(): void {
		$settings = get_option( self::OPTION_JWT_SETTINGS, array() );
		$value    = $settings['access_token_expiry'] ?? 3600;
		?>
		<input type="number" id="jwt_access_token_expiry" name="<?php echo esc_attr( self::OPTION_JWT_SETTINGS ); ?>[access_token_expiry]" value="<?php echo esc_attr( $value ); ?>" min="300" max="86400" />
		<p class="description">How long access tokens remain valid in seconds. Default: 3600 (1 hour). Range: 300-86400 seconds.</p>
		<?php
	}

	/**
	 * Render the JWT refresh token expiry field.
	 */
	public function jwt_refresh_token_expiry_field(): void {
		$settings = get_option( self::OPTION_JWT_SETTINGS, array() );
		$value    = $settings['refresh_token_expiry'] ?? 2592000;
		?>
		<input type="number" id="jwt_refresh_token_expiry" name="<?php echo esc_attr( self::OPTION_JWT_SETTINGS ); ?>[refresh_token_expiry]" value="<?php echo esc_attr( $value ); ?>" min="3600" max="31536000" />
		<p class="description">How long refresh tokens remain valid in seconds. Default: 2592000 (30 days). Range: 3600-31536000 seconds.</p>
		<?php
	}

	/**
	 * Render the debug logging enable field.
	 */
	public function enable_debug_logging_field(): void {
		$settings = get_option( self::OPTION_GENERAL_SETTINGS, array() );
		$checked  = isset( $settings['enable_debug_logging'] ) && $settings['enable_debug_logging'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_GENERAL_SETTINGS ); ?>[enable_debug_logging]" value="1" <?php checked( $checked ); ?> />
			Enable detailed logging for authentication events
		</label>
		<p class="description">Logs will be written to your WordPress debug log. Ensure WP_DEBUG_LOG is enabled.</p>
		<?php
	}

	/**
	 * Render the CORS allowed origins field.
	 */
	public function cors_allowed_origins_field(): void {
		$settings = get_option( self::OPTION_GENERAL_SETTINGS, array() );
		$value    = $settings['cors_allowed_origins'] ?? "http://localhost:3000\nhttp://localhost:5173\nhttp://localhost:5174\nhttp://localhost:5175";
		?>
		<textarea name="<?php echo esc_attr( self::OPTION_GENERAL_SETTINGS ); ?>[cors_allowed_origins]" class="large-text" rows="5"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">One origin per line. Use * to allow all origins (not recommended for production).</p>
		<?php
	}

	/**
	 * Sanitization callbacks.
	 */
	/**
	 * Sanitize JWT settings input.
	 *
	 * @param array $input Raw input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_jwt_settings( array $input ): array {
		$sanitized = array();

		if ( isset( $input['secret_key'] ) ) {
			$secret_key = sanitize_text_field( $input['secret_key'] );
			if ( strlen( $secret_key ) < 32 ) {
				add_settings_error( self::OPTION_JWT_SETTINGS, 'jwt_secret_key', 'JWT Secret Key must be at least 32 characters long.' );
			} else {
				$sanitized['secret_key'] = $secret_key;
			}
		}

		if ( isset( $input['access_token_expiry'] ) ) {
			$expiry                           = intval( $input['access_token_expiry'] );
			$sanitized['access_token_expiry'] = max( 300, min( 86400, $expiry ) );
		}

		if ( isset( $input['refresh_token_expiry'] ) ) {
			$expiry                            = intval( $input['refresh_token_expiry'] );
			$sanitized['refresh_token_expiry'] = max( 3600, min( 31536000, $expiry ) );
		}

		return $sanitized;
	}

	/**
	 * Sanitize general settings input.
	 *
	 * @param array $input Raw input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_general_settings( array $input ): array {
		$sanitized = array();

		$sanitized['enable_debug_logging'] = isset( $input['enable_debug_logging'] ) && $input['enable_debug_logging'];

		if ( isset( $input['cors_allowed_origins'] ) ) {
			$origins                           = sanitize_textarea_field( $input['cors_allowed_origins'] );
			$sanitized['cors_allowed_origins'] = $origins;
		}

		return $sanitized;
	}

	/**
	 * Helper methods to get settings.
	 */
	/**
	 * Get JWT settings with default values.
	 *
	 * @return array JWT settings.
	 */
	public static function get_jwt_settings(): array {
		return get_option(
			self::OPTION_JWT_SETTINGS,
			array(
				'secret_key'           => '',
				'access_token_expiry'  => 3600,
				'refresh_token_expiry' => 2592000,
			)
		);
	}

	/**
	 * Get general settings with default values.
	 *
	 * @return array General settings.
	 */
	public static function get_general_settings(): array {
		return get_option(
			self::OPTION_GENERAL_SETTINGS,
			array(
				'enable_debug_logging' => false,
				'cors_allowed_origins' => "http://localhost:3000\nhttp://localhost:5173\nhttp://localhost:5174\nhttp://localhost:5175",
			)
		);
	}
}
