<?php

/**
 * Admin Settings for JWT Auth Pro
 *
 * This class handles the WordPress admin interface for configuring JWT authentication
 * settings with advanced security features including refresh token management.
 * It provides options for JWT token configuration, CORS settings, security
 * options, and other plugin-related settings through the WordPress settings API.
 *
 * The class creates admin pages, registers settings, validates input, and provides
 * methods to retrieve configuration values used throughout the plugin.
 *
 * @package   JWTAuthPro
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 *
 * @link      https://github.com/juanma-wp/jwt-auth-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings class for JWT Auth Pro plugin.
 */
class JWT_Auth_Pro_Admin_Settings {


	const OPTION_GROUP            = 'jwt_auth_pro_settings';
	const OPTION_JWT_SETTINGS     = 'jwt_auth_pro_settings';
	const OPTION_GENERAL_SETTINGS = 'jwt_auth_pro_general_settings';

	/**
	 * Constructor. Initialize admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_filter( 'wp_redirect', array( $this, 'preserve_tab_on_redirect' ), 10, 2 );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu(): void {
		add_options_page(
			'JWT Auth Pro Settings',
			'JWT Auth Pro',
			'activate_plugins',
			'jwt-auth-pro-wp-rest-api',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Register WordPress settings and fields.
	 */
	public function register_settings(): void {
		// Register setting groups.
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

		register_setting(
			self::OPTION_GROUP,
			'jwt_auth_cookie_config',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_cookie_settings' ),
				'default'           => array(
					'samesite' => 'auto',
					'secure'   => 'auto',
					'path'     => 'auto',
					'domain'   => 'auto',
				),
			)
		);

		// JWT Settings Section.
		add_settings_section(
			'jwt_settings',
			'JWT Authentication Settings',
			array( $this, 'jwt_settings_section' ),
			'jwt-auth-pro-wp-rest-api-settings'
		);

		add_settings_field(
			'jwt_secret_key',
			'JWT Secret Key',
			array( $this, 'jwt_secret_key_field' ),
			'jwt-auth-pro-wp-rest-api-settings',
			'jwt_settings'
		);

		add_settings_field(
			'jwt_access_token_expiry',
			'Access Token Expiry (seconds)',
			array( $this, 'jwt_access_token_expiry_field' ),
			'jwt-auth-pro-wp-rest-api-settings',
			'jwt_settings'
		);

		add_settings_field(
			'jwt_refresh_token_expiry',
			'Refresh Token Expiry (seconds)',
			array( $this, 'jwt_refresh_token_expiry_field' ),
			'jwt-auth-pro-wp-rest-api-settings',
			'jwt_settings'
		);

		// General Settings Section.
		add_settings_section(
			'general_settings',
			'General Settings',
			array( $this, 'general_settings_section' ),
			'jwt-auth-pro-wp-rest-api-general'
		);

		add_settings_field(
			'enable_debug_logging',
			'Enable Debug Logging',
			array( $this, 'enable_debug_logging_field' ),
			'jwt-auth-pro-wp-rest-api-general',
			'general_settings'
		);

		add_settings_field(
			'cors_allowed_origins',
			'CORS Allowed Origins',
			array( $this, 'cors_allowed_origins_field' ),
			'jwt-auth-pro-wp-rest-api-general',
			'general_settings'
		);

		// Cookie Configuration Section (on its own tab).
		add_settings_section(
			'cookie_config_section',
			'Cookie Configuration',
			array( $this, 'cookie_config_section' ),
			'jwt-auth-pro-wp-rest-api-cookies'
		);

		add_settings_field(
			'cookie_samesite',
			'SameSite Attribute',
			array( $this, 'cookie_samesite_field' ),
			'jwt-auth-pro-wp-rest-api-cookies',
			'cookie_config_section'
		);

		add_settings_field(
			'cookie_secure',
			'Secure Attribute',
			array( $this, 'cookie_secure_field' ),
			'jwt-auth-pro-wp-rest-api-cookies',
			'cookie_config_section'
		);

		add_settings_field(
			'cookie_path',
			'Cookie Path',
			array( $this, 'cookie_path_field' ),
			'jwt-auth-pro-wp-rest-api-cookies',
			'cookie_config_section'
		);

		add_settings_field(
			'cookie_domain',
			'Cookie Domain',
			array( $this, 'cookie_domain_field' ),
			'jwt-auth-pro-wp-rest-api-cookies',
			'cookie_config_section'
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( string $hook ): void {
		if ( 'settings_page_jwt-auth-pro-wp-rest-api' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'jwt-auth-pro-wp-rest-api-admin',
			plugin_dir_url( __DIR__ ) . 'assets/admin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'jwt-auth-pro-wp-rest-api-admin',
			'wpRestAuthJWT',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp_rest_auth_jwt_nonce' ),
			)
		);
	}

	/**
	 * Render the admin settings page.
	 */
	public function admin_page(): void {
		// Check for valid admin page access - requires plugin management permissions.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'jwt-auth-pro-wp-rest-api' ) );
		}

		// For tab navigation, we'll validate the tab parameter directly instead of requiring nonce.
		$allowed_tabs = array( 'jwt', 'general', 'cookies', 'help', 'api-docs' );
		$active_tab   = 'jwt'; // Default tab.

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation in admin doesn't require nonce.
		if ( isset( $_GET['tab'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation in admin doesn't require nonce.
			$requested_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
			if ( in_array( $requested_tab, $allowed_tabs, true ) ) {
				$active_tab = $requested_tab;
			}
		}
		?>
		<div class="wrap">
			<h1>🚀 JWT Auth Pro Settings</h1>
			<p class="description">Modern JWT authentication with secure refresh tokens for WordPress REST API</p>

			<nav class="nav-tab-wrapper">
				<a href="?page=jwt-auth-pro-wp-rest-api&tab=jwt" class="nav-tab <?php echo 'jwt' === $active_tab ? 'nav-tab-active' : ''; ?>">JWT Settings</a>
				<a href="?page=jwt-auth-pro-wp-rest-api&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">General Settings</a>
				<a href="?page=jwt-auth-pro-wp-rest-api&tab=cookies" class="nav-tab <?php echo 'cookies' === $active_tab ? 'nav-tab-active' : ''; ?>">Cookie Settings</a>
				<a href="?page=jwt-auth-pro-wp-rest-api&tab=api-docs" class="nav-tab <?php echo 'api-docs' === $active_tab ? 'nav-tab-active' : ''; ?>">API Documentation</a>
				<a href="?page=jwt-auth-pro-wp-rest-api&tab=help" class="nav-tab <?php echo 'help' === $active_tab ? 'nav-tab-active' : ''; ?>">Help & Documentation</a>
			</nav>

			<?php if ( 'api-docs' === $active_tab ) : ?>
				<?php $this->render_api_docs_tab(); ?>
			<?php elseif ( 'help' === $active_tab ) : ?>
				<?php $this->render_help_tab(); ?>
			<?php else : ?>
				<form method="post" action="options.php">
					<?php
					settings_fields( self::OPTION_GROUP );

					if ( 'jwt' === $active_tab ) {
						do_settings_sections( 'jwt-auth-pro-wp-rest-api-settings' );
						submit_button();
					} elseif ( 'general' === $active_tab ) {
						do_settings_sections( 'jwt-auth-pro-wp-rest-api-general' );
						submit_button();
					} elseif ( 'cookies' === $active_tab ) {
						do_settings_sections( 'jwt-auth-pro-wp-rest-api-cookies' );
						submit_button();
					}
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the API documentation tab with Swagger UI.
	 */
	private function render_api_docs_tab(): void {
		$openapi_url = rest_url( 'jwt/v1/openapi' );
		?>
		<style>
			.api-docs-container {
				margin-top: 20px;
				background: #fff;
				border: 1px solid #ccc;
				border-radius: 4px;
			}
			#swagger-ui {
				max-width: 100%;
			}
			.topbar {
				display: none;
			}
		</style>
		<div class="api-docs-container">
			<div id="swagger-ui"></div>
		</div>
		<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.0/swagger-ui-bundle.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.0/swagger-ui-standalone-preset.js"></script>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.0/swagger-ui.css" />
		<script>
			window.onload = function() {
				window.ui = SwaggerUIBundle({
					url: "<?php echo esc_url( $openapi_url ); ?>",
					dom_id: "#swagger-ui",
					deepLinking: true,
					presets: [
						SwaggerUIBundle.presets.apis,
						SwaggerUIStandalonePreset
					],
					plugins: [
						SwaggerUIBundle.plugins.DownloadUrl
					],
					layout: "StandaloneLayout",
					persistAuthorization: true,
					tryItOutEnabled: true
				});
			};
		</script>
		<?php
	}

	/**
	 * Render the help and documentation tab.
	 */
	private function render_help_tab(): void {
		?>
		<div class="help-tab">
			<h2>Help & Documentation</h2>

			<div class="help-section">
				<h3>🚀 What makes JWT Auth Pro different?</h3>
				<p>JWT Auth Pro implements modern OAuth 2.0 security best practices with refresh tokens - unlike basic JWT plugins that use single long-lived tokens. Perfect for:</p>
				<ul>
					<li><strong>Single Page Applications (SPAs)</strong> - React, Vue, Angular apps</li>
					<li><strong>Mobile Applications</strong> - iOS, Android apps with secure token storage</li>
					<li><strong>API Integrations</strong> - Third-party services requiring enterprise security</li>
					<li><strong>Headless WordPress</strong> - Decoupled architectures with enhanced security</li>
				</ul>
				<p><strong>Key Security Advantage:</strong> Short-lived access tokens (1 hour) + secure refresh tokens (30 days) = Better security than single long-lived JWT tokens!</p>
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
				<p><strong>Plugin Version:</strong> <?php echo esc_html( JWT_AUTH_PRO_VERSION ); ?></p>
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

	/**
	 * Section callbacks.
	 */

	/**
	 * Render JWT settings section description.
	 */
	public function jwt_settings_section(): void {
		echo '<p>Configure JWT authentication settings. JWT tokens provide stateless authentication for your WordPress REST API.</p>';
	}

	/**
	 * Render general settings section description.
	 */
	public function general_settings_section(): void {
		echo '<p>General plugin settings and security options.</p>';
	}

	/**
	 * Field callbacks.
	 */

	/**
	 * Render the JWT secret key field.
	 */
	public function jwt_secret_key_field(): void {
		$settings        = get_option( self::OPTION_JWT_SETTINGS, array() );
		$database_secret = $settings['secret_key'] ?? '';

		// Check if JWT_AUTH_PRO_SECRET is defined in wp-config.php.
		$config_secret = defined( 'JWT_AUTH_PRO_SECRET' ) ? JWT_AUTH_PRO_SECRET : '';
		$using_config  = ! empty( $config_secret );

		// Show the active secret (config takes priority).
		$active_secret = $using_config ? $config_secret : $database_secret;

		if ( $using_config ) {
			?>
			<input type="password" id="jwt_secret_key" value="<?php echo esc_attr( $active_secret ); ?>" class="regular-text" readonly />
			<button type="button" id="toggle_jwt_secret" class="button">Show/Hide</button>
			<p class="description">
				<strong>✅ JWT Secret Key is defined in wp-config.php</strong><br>
				This secret key from your wp-config.php file takes priority over database settings.
				To use a different secret, remove the <code>JWT_AUTH_PRO_SECRET</code> constant from wp-config.php.
			</p>
			<?php
		} else {
			?>
			<input type="password" id="jwt_secret_key" name="<?php echo esc_attr( self::OPTION_JWT_SETTINGS ); ?>[secret_key]" value="<?php echo esc_attr( $database_secret ); ?>" class="regular-text" />
			<button type="button" id="generate_jwt_secret" class="button">Generate New Secret</button>
			<button type="button" id="toggle_jwt_secret" class="button">Show/Hide</button>
			<p class="description">
				A secure random string used to sign JWT tokens. Generate a new one or enter your own (minimum 32 characters recommended).<br>
				<strong>Tip:</strong> For better security, define <code>JWT_AUTH_PRO_SECRET</code> in your wp-config.php file instead.
			</p>
			<?php
		}
		?>

		<script>
			jQuery(document).ready(function($) {
				// Only bind generate secret if button exists (not readonly)
				$('#generate_jwt_secret').click(function() {
					const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
					let secret = '';
					for (let i = 0; i < 64; i++) {
						secret += chars.charAt(Math.floor(Math.random() * chars.length));
					}
					$('#jwt_secret_key').val(secret);
				});

				// Toggle show/hide for both readonly and editable fields
				$('#toggle_jwt_secret').click(function() {
					const field = $('#jwt_secret_key');
					field.attr('type', 'password' === field.attr('type') ? 'text' : 'password');
				});
			});
		</script>
		<?php
	}

	/**
	 * Render the JWT access token expiry field.
	 */
	public function jwt_access_token_expiry_field(): void {
		$settings       = get_option( self::OPTION_JWT_SETTINGS, array() );
		$database_value = $settings['access_token_expiry'] ?? 3600;

		// Check if JWT_AUTH_PRO_ACCESS_TTL is defined in wp-config.php.
		$config_value = defined( 'JWT_AUTH_PRO_ACCESS_TTL' ) ? JWT_AUTH_PRO_ACCESS_TTL : null;
		$using_config = null !== $config_value;

		// Show the active value (config takes priority).
		$active_value = $using_config ? $config_value : $database_value;

		if ( $using_config ) {
			?>
			<input type="number" id="jwt_access_token_expiry" value="<?php echo esc_attr( $active_value ); ?>" min="300" max="86400" readonly />
			<p class="description">
				<strong>✅ Access Token TTL is defined in wp-config.php (<?php echo esc_html( $active_value ); ?> seconds = <?php echo esc_html( human_time_diff( 0, $active_value ) ); ?>)</strong><br>
				This value from your wp-config.php file takes priority over database settings.
			</p>
			<?php
		} else {
			?>
			<input type="number" id="jwt_access_token_expiry" name="<?php echo esc_attr( self::OPTION_JWT_SETTINGS ); ?>[access_token_expiry]" value="<?php echo esc_attr( $database_value ); ?>" min="300" max="86400" />
			<p class="description">
				How long access tokens remain valid in seconds. Default: 3600 (1 hour). Range: 300-86400 seconds.<br>
				<strong>Tip:</strong> Define <code>JWT_AUTH_PRO_ACCESS_TTL</code> in wp-config.php for better control.
			</p>
			<?php
		}
	}

	/**
	 * Render the JWT refresh token expiry field.
	 */
	public function jwt_refresh_token_expiry_field(): void {
		$settings       = get_option( self::OPTION_JWT_SETTINGS, array() );
		$database_value = $settings['refresh_token_expiry'] ?? 2592000;

		// Check if JWT_AUTH_PRO_REFRESH_TTL is defined in wp-config.php.
		$config_value = defined( 'JWT_AUTH_PRO_REFRESH_TTL' ) ? JWT_AUTH_PRO_REFRESH_TTL : null;
		$using_config = null !== $config_value;

		// Show the active value (config takes priority).
		$active_value = $using_config ? $config_value : $database_value;

		if ( $using_config ) {
			?>
			<input type="number" id="jwt_refresh_token_expiry" value="<?php echo esc_attr( $active_value ); ?>" min="3600" max="31536000" readonly />
			<p class="description">
				<strong>✅ Refresh Token TTL is defined in wp-config.php (<?php echo esc_html( $active_value ); ?> seconds = <?php echo esc_html( human_time_diff( 0, $active_value ) ); ?>)</strong><br>
				This value from your wp-config.php file takes priority over database settings.
			</p>
			<?php
		} else {
			?>
			<input type="number" id="jwt_refresh_token_expiry" name="<?php echo esc_attr( self::OPTION_JWT_SETTINGS ); ?>[refresh_token_expiry]" value="<?php echo esc_attr( $database_value ); ?>" min="3600" max="31536000" />
			<p class="description">
				How long refresh tokens remain valid in seconds. Default: 2592000 (30 days). Range: 3600-31536000 seconds.<br>
				<strong>Tip:</strong> Define <code>JWT_AUTH_PRO_REFRESH_TTL</code> in wp-config.php for better control.
			</p>
			<?php
		}
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
	 * @param array|null $input Raw input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_jwt_settings( $input ): array {
		// Get existing settings to preserve them when saving other tabs
		$existing = get_option( self::OPTION_JWT_SETTINGS, array() );

		// If no input or not an array (saving from a different tab), return existing
		if ( ! is_array( $input ) || empty( $input ) ) {
			return $existing;
		}

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
	 * @param array|null $input Raw input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_general_settings( $input ): array {
		// Get existing settings to preserve them when saving other tabs
		$existing = get_option( self::OPTION_GENERAL_SETTINGS, array() );

		// If no input or not an array (saving from a different tab), return existing
		if ( ! is_array( $input ) || empty( $input ) ) {
			return $existing;
		}

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

	/**
	 * Preserve tab parameter on settings save redirect.
	 *
	 * @param string $location Redirect location.
	 * @param int    $status   HTTP status code.
	 * @return string Modified redirect location.
	 */
	public function preserve_tab_on_redirect( string $location, int $status ): string {
		// Only modify redirects to our settings page.
		if ( ! str_contains( $location, 'page=jwt-auth-pro-wp-rest-api' ) ) {
			return $location;
		}

		// Check if we have a tab parameter in the referer.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading referer for tab navigation.
		if ( isset( $_POST['_wp_http_referer'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Parse URL for tab parameter.
			$referer = wp_unslash( $_POST['_wp_http_referer'] );
			$parts   = wp_parse_url( $referer );
			if ( isset( $parts['query'] ) ) {
				parse_str( $parts['query'], $query );
				if ( isset( $query['tab'] ) ) {
					$location = add_query_arg( 'tab', sanitize_text_field( $query['tab'] ), $location );
				}
			}
		}

		return $location;
	}

	/**
	 * Render cookie configuration section description.
	 */
	public function cookie_config_section(): void {
		// Check if JWT_Cookie_Config class exists.
		if ( ! class_exists( 'JWT_Cookie_Config' ) ) {
			?>
			<div class="notice notice-error inline">
				<p><?php esc_html_e( 'Cookie configuration class not loaded. Please check plugin installation.', 'jwt-auth-pro-wp-rest-api' ); ?></p>
			</div>
			<?php
			return;
		}

		$environment    = JWT_Cookie_Config::get_environment();
		$current_config = JWT_Cookie_Config::get_config();
		?>
		<p><?php esc_html_e( 'Configure cookie security settings for JWT refresh tokens. Settings are automatically configured based on your environment. Use "Auto" to let the plugin detect appropriate settings.', 'jwt-auth-pro-wp-rest-api' ); ?></p>

		<div class="notice notice-info inline">
			<p>
				<strong><?php esc_html_e( 'Current Environment:', 'jwt-auth-pro-wp-rest-api' ); ?></strong>
				<code><?php echo esc_html( $environment ); ?></code>
			</p>
		</div>

		<div class="notice notice-warning inline">
			<h4><?php esc_html_e( 'Active Cookie Configuration', 'jwt-auth-pro-wp-rest-api' ); ?></h4>
			<table class="widefat" style="max-width: 600px;">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'SameSite:', 'jwt-auth-pro-wp-rest-api' ); ?></strong></td>
						<td><code><?php echo esc_html( $current_config['samesite'] ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Secure:', 'jwt-auth-pro-wp-rest-api' ); ?></strong></td>
						<td><code><?php echo esc_html( $current_config['secure'] ? 'true' : 'false' ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Path:', 'jwt-auth-pro-wp-rest-api' ); ?></strong></td>
						<td><code><?php echo esc_html( $current_config['path'] ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Domain:', 'jwt-auth-pro-wp-rest-api' ); ?></strong></td>
						<td><code><?php echo esc_html( $current_config['domain'] ?: '(current domain)' ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'HttpOnly:', 'jwt-auth-pro-wp-rest-api' ); ?></strong></td>
						<td><code><?php echo esc_html( $current_config['httponly'] ? 'true' : 'false' ); ?></code></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="notice notice-info inline">
			<h4><?php esc_html_e( 'Environment Detection Logic', 'jwt-auth-pro-wp-rest-api' ); ?></h4>
			<ul>
				<li><strong><?php esc_html_e( 'Development:', 'jwt-auth-pro-wp-rest-api' ); ?></strong>
					<?php esc_html_e( 'localhost, *.local, *.test domains OR WP_DEBUG enabled', 'jwt-auth-pro-wp-rest-api' ); ?>
				</li>
				<li><strong><?php esc_html_e( 'Staging:', 'jwt-auth-pro-wp-rest-api' ); ?></strong>
					<?php esc_html_e( 'Domains containing "staging", "dev", or "test"', 'jwt-auth-pro-wp-rest-api' ); ?>
				</li>
				<li><strong><?php esc_html_e( 'Production:', 'jwt-auth-pro-wp-rest-api' ); ?></strong>
					<?php esc_html_e( 'All other domains', 'jwt-auth-pro-wp-rest-api' ); ?>
				</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render SameSite field.
	 */
	public function cookie_samesite_field(): void {
		$defaults = class_exists( 'JWT_Cookie_Config' ) ? JWT_Cookie_Config::get_defaults() : array( 'samesite' => 'auto' );
		$config   = get_option( 'jwt_auth_cookie_config', $defaults );
		$value    = $config['samesite'] ?? 'auto';
		?>
		<select name="jwt_auth_cookie_config[samesite]">
			<option value="auto" <?php selected( $value, 'auto' ); ?>>
				<?php esc_html_e( 'Auto (Recommended)', 'jwt-auth-pro-wp-rest-api' ); ?>
			</option>
			<option value="None" <?php selected( $value, 'None' ); ?>>
				<?php esc_html_e( 'None (Cross-site allowed)', 'jwt-auth-pro-wp-rest-api' ); ?>
			</option>
			<option value="Lax" <?php selected( $value, 'Lax' ); ?>>
				<?php esc_html_e( 'Lax (Relaxed)', 'jwt-auth-pro-wp-rest-api' ); ?>
			</option>
			<option value="Strict" <?php selected( $value, 'Strict' ); ?>>
				<?php esc_html_e( 'Strict (Maximum security)', 'jwt-auth-pro-wp-rest-api' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Auto: None (development), Lax (staging), Strict (production)', 'jwt-auth-pro-wp-rest-api' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Secure field.
	 */
	public function cookie_secure_field(): void {
		$defaults = class_exists( 'JWT_Cookie_Config' ) ? JWT_Cookie_Config::get_defaults() : array( 'secure' => 'auto' );
		$config   = get_option( 'jwt_auth_cookie_config', $defaults );
		$value    = $config['secure'] ?? 'auto';
		?>
		<select name="jwt_auth_cookie_config[secure]">
			<option value="auto" <?php selected( $value, 'auto' ); ?>>
				<?php esc_html_e( 'Auto (Recommended)', 'jwt-auth-pro-wp-rest-api' ); ?>
			</option>
			<option value="1" <?php selected( $value, '1' ); ?>>
				<?php esc_html_e( 'Enabled (HTTPS required)', 'jwt-auth-pro-wp-rest-api' ); ?>
			</option>
			<option value="0" <?php selected( $value, '0' ); ?>>
				<?php esc_html_e( 'Disabled (HTTP allowed)', 'jwt-auth-pro-wp-rest-api' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Auto: Enabled for staging/production, disabled for development without HTTPS', 'jwt-auth-pro-wp-rest-api' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Path field.
	 */
	public function cookie_path_field(): void {
		$defaults = class_exists( 'JWT_Cookie_Config' ) ? JWT_Cookie_Config::get_defaults() : array( 'path' => 'auto' );
		$config   = get_option( 'jwt_auth_cookie_config', $defaults );
		$value    = $config['path'] ?? 'auto';
		?>
		<input type="text"
			name="jwt_auth_cookie_config[path]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="auto"
		/>
		<p class="description">
			<?php esc_html_e( 'Auto: "/" (development), "/wp-json/jwt/v1/" (staging/production)', 'jwt-auth-pro-wp-rest-api' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Domain field.
	 */
	public function cookie_domain_field(): void {
		$defaults = class_exists( 'JWT_Cookie_Config' ) ? JWT_Cookie_Config::get_defaults() : array( 'domain' => 'auto' );
		$config   = get_option( 'jwt_auth_cookie_config', $defaults );
		$value    = $config['domain'] ?? 'auto';
		?>
		<input type="text"
			name="jwt_auth_cookie_config[domain]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="auto"
		/>
		<p class="description">
			<?php esc_html_e( 'Auto: Empty (current domain only). Use for subdomain sharing (e.g., ".example.com")', 'jwt-auth-pro-wp-rest-api' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize cookie settings.
	 *
	 * @param array<string, mixed>|null $input Input settings.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public function sanitize_cookie_settings( $input ): array {
		// Get existing settings or defaults
		$defaults = class_exists( 'JWT_Cookie_Config' ) ? JWT_Cookie_Config::get_defaults() : array(
			'samesite' => 'auto',
			'secure'   => 'auto',
			'path'     => 'auto',
			'domain'   => 'auto',
		);
		$existing = get_option( 'jwt_auth_cookie_config', $defaults );

		// Handle null or invalid input - return existing settings
		if ( ! is_array( $input ) ) {
			return $existing;
		}

		// Start with existing settings to preserve all fields
		$sanitized = $existing;

		// Sanitize SameSite
		if ( isset( $input['samesite'] ) ) {
			$valid_samesite        = array( 'auto', 'None', 'Lax', 'Strict' );
			$sanitized['samesite'] = in_array( $input['samesite'], $valid_samesite, true ) ? $input['samesite'] : 'auto';
		}

		// Sanitize Secure
		if ( isset( $input['secure'] ) ) {
			if ( 'auto' === $input['secure'] ) {
				$sanitized['secure'] = 'auto';
			} else {
				$sanitized['secure'] = in_array( $input['secure'], array( '1', 1, true ), true ) ? '1' : '0';
			}
		}

		// Sanitize Path
		if ( isset( $input['path'] ) ) {
			$sanitized['path'] = 'auto' === $input['path'] ? 'auto' : sanitize_text_field( $input['path'] );
		}

		// Sanitize Domain
		if ( isset( $input['domain'] ) ) {
			$sanitized['domain'] = 'auto' === $input['domain'] ? 'auto' : sanitize_text_field( $input['domain'] );
		}

		// Clear cache after saving (if class exists)
		if ( class_exists( 'JWT_Cookie_Config' ) && method_exists( 'JWT_Cookie_Config', 'clear_cache' ) ) {
			JWT_Cookie_Config::clear_cache();
		}

		return $sanitized;
	}
}
