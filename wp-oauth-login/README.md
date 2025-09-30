# WP OAuth Login

A WordPress plugin that provides OAuth-based authentication with JWT tokens using the shared `jwt-auth-core` library.

## Features

- OAuth 2.0 authentication (Google, GitHub, etc.)
- JWT token generation after OAuth flow
- Token validation and refresh
- Uses shared JWT core library
- Extensible provider system

## Installation

### Development Setup

1. Install dependencies:
```bash
composer install
```

This will create a symlink to the shared `jwt-auth-core` package.

2. Copy to your WordPress plugins directory:
```bash
cp -r . /path/to/wordpress/wp-content/plugins/wp-oauth-login/
```

3. Activate the plugin in WordPress admin

### Configuration

Add OAuth provider credentials to WordPress options or define in `wp-config.php`:

```php
// Google OAuth
define( 'WP_OAUTH_GOOGLE_CLIENT_ID', 'your-google-client-id' );
define( 'WP_OAUTH_GOOGLE_CLIENT_SECRET', 'your-google-client-secret' );

// GitHub OAuth
define( 'WP_OAUTH_GITHUB_CLIENT_ID', 'your-github-client-id' );
define( 'WP_OAUTH_GITHUB_CLIENT_SECRET', 'your-github-client-secret' );

// JWT Secret (optional)
define( 'JWT_AUTH_SECRET', 'your-super-secret-key-here' );
```

## API Endpoints

### GET /wp-json/wp-oauth-login/v1/authorize/{provider}

Initiate OAuth flow for a provider.

**Example:**
```
GET /wp-json/wp-oauth-login/v1/authorize/google
```

### GET /wp-json/wp-oauth-login/v1/callback/{provider}

OAuth callback endpoint (configured in OAuth provider settings).

**Example:**
```
GET /wp-json/wp-oauth-login/v1/callback/google?code=AUTHORIZATION_CODE
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "provider": "google"
}
```

### POST /wp-json/wp-oauth-login/v1/validate

Validate a JWT token.

**Request:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Response:**
```json
{
  "success": true,
  "payload": {
    "user_id": 1,
    "username": "oauth_user",
    "provider": "google",
    "oauth_id": "123456789",
    "exp": 1234567890,
    "iat": 1234560290
  }
}
```

### POST /wp-json/wp-oauth-login/v1/refresh

Refresh an existing JWT token.

**Request:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

## Supported Providers

- **Google**: OAuth 2.0 authentication
- **GitHub**: OAuth 2.0 authentication

### Adding Custom Providers

Use the `wp_oauth_login_providers` filter:

```php
add_filter( 'wp_oauth_login_providers', function( $providers ) {
    $providers['facebook'] = array(
        'name'          => 'Facebook',
        'client_id'     => get_option( 'wp_oauth_facebook_client_id' ),
        'client_secret' => get_option( 'wp_oauth_facebook_client_secret' ),
        'redirect_uri'  => rest_url( 'wp-oauth-login/v1/callback/facebook' ),
    );
    return $providers;
} );
```

## Usage Example

### JavaScript Client

```javascript
// Initiate OAuth flow
window.location.href = '/wp-json/wp-oauth-login/v1/authorize/google';

// After OAuth callback, you'll receive a token
// Store and use it for authenticated requests
const token = 'token-from-callback';
localStorage.setItem('jwt_token', token);

// Make authenticated requests
const response = await fetch('/wp-json/wp/v2/posts', {
  headers: {
    'Authorization': `Bearer ${token}`,
  },
});

// Refresh token when needed
const refreshResponse = await fetch('/wp-json/wp-oauth-login/v1/refresh', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ token }),
});
```

## Development

The plugin uses the shared `jwt-auth-core` library. Any changes made to the core library will be immediately reflected due to Composer symlinks.

### Token Expiration

OAuth tokens have a default expiration of 2 hours (7200 seconds), which is configured in the plugin constructor:

```php
$this->token_manager = new TokenManager( $secret, 7200 );
```

## Building for Production

Before releasing to WordPress.org:

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Create distribution ZIP
zip -r wp-oauth-login.zip . -x "*.git*" "tests/*" "*.md"
```

The `vendor/` directory must be included in the distribution ZIP.

## Security Notes

- Always use HTTPS in production
- Store OAuth credentials securely
- Implement proper token refresh logic
- Validate OAuth callbacks
- Consider implementing rate limiting

## License

GPL-2.0-or-later
