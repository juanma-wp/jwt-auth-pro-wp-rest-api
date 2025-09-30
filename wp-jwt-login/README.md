# WP JWT Login

A WordPress plugin that provides JWT-based authentication using the shared `jwt-auth-core` library.

## Features

- JWT token-based authentication
- REST API endpoints for login and validation
- Uses shared JWT core library for token management
- WordPress user authentication integration

## Installation

### Development Setup

1. Install dependencies:
```bash
composer install
```

This will create a symlink to the shared `jwt-auth-core` package.

2. Copy to your WordPress plugins directory:
```bash
cp -r . /path/to/wordpress/wp-content/plugins/wp-jwt-login/
```

3. Activate the plugin in WordPress admin

### Configuration

Define the JWT secret in `wp-config.php` (optional):

```php
define( 'JWT_AUTH_SECRET', 'your-super-secret-key-here' );
```

If not defined, the plugin will use WordPress's auth salt.

## API Endpoints

### POST /wp-json/wp-jwt-login/v1/authenticate

Authenticate a user and get a JWT token.

**Request:**
```json
{
  "username": "admin",
  "password": "password123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com"
  }
}
```

### POST /wp-json/wp-jwt-login/v1/validate

Validate a JWT token.

**Request:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Response (Success):**
```json
{
  "success": true,
  "payload": {
    "user_id": 1,
    "username": "admin",
    "user_email": "admin@example.com",
    "exp": 1234567890,
    "iat": 1234564290
  }
}
```

## Usage Example

### JavaScript Client

```javascript
// Login
const response = await fetch('/wp-json/wp-jwt-login/v1/authenticate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    username: 'admin',
    password: 'password123',
  }),
});

const data = await response.json();
if (data.success) {
  // Store token
  localStorage.setItem('jwt_token', data.token);
  
  // Use token for authenticated requests
  const authResponse = await fetch('/wp-json/wp/v2/posts', {
    headers: {
      'Authorization': `Bearer ${data.token}`,
    },
  });
}
```

## Development

The plugin uses the shared `jwt-auth-core` library. Any changes made to the core library will be immediately reflected due to Composer symlinks.

### Testing

```bash
# Test authentication
curl -X POST http://localhost:8888/wp-json/wp-jwt-login/v1/authenticate \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Test validation
curl -X POST http://localhost:8888/wp-json/wp-jwt-login/v1/validate \
  -H "Content-Type: application/json" \
  -d '{"token":"YOUR_TOKEN_HERE"}'
```

## Building for Production

Before releasing to WordPress.org:

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Create distribution ZIP
zip -r wp-jwt-login.zip . -x "*.git*" "tests/*" "*.md"
```

The `vendor/` directory must be included in the distribution ZIP.

## License

GPL-2.0-or-later
