# WP REST Auth JWT

[![CI](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/ci.yml/badge.svg)](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/ci.yml)

🔐 **Simple, secure JWT authentication for WordPress REST API**

A focused, lightweight plugin providing JWT authentication with HTTPOnly refresh tokens for WordPress REST API. Perfect for SPAs, mobile apps, and headless WordPress implementations.

## ✨ Features

- **Simple JWT Authentication** - Clean, stateless token-based auth
- **HTTPOnly Refresh Tokens** - Secure refresh tokens in HTTP-only cookies
- **Token Rotation** - Automatic refresh token rotation for enhanced security
- **CORS Support** - Proper cross-origin request handling
- **Clean Admin Interface** - Simple configuration in WordPress admin
- **Developer Friendly** - Clear endpoints and documentation

## 🚀 Quick Start

### 1. Install & Activate
1. Upload the plugin to `/wp-content/plugins/`
2. Activate through WordPress admin
3. Go to Settings → WP REST Auth JWT

### 2. Configure
1. Generate a JWT Secret Key (or add to wp-config.php)
2. Set token expiration times
3. Configure CORS origins for your frontend

### 3. Start Using
```javascript
// Login
const response = await fetch('/wp-json/jwt/v1/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        username: 'your_username',
        password: 'your_password'
    })
});

const { access_token } = await response.json();

// Use token for API calls
const posts = await fetch('/wp-json/wp/v2/posts', {
    headers: { 'Authorization': `Bearer ${access_token}` }
});
```

## 📍 Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/wp-json/jwt/v1/token` | Login and get access token |
| `POST` | `/wp-json/jwt/v1/refresh` | Refresh access token |
| `GET` | `/wp-json/jwt/v1/verify` | Verify token and get user info |
| `POST` | `/wp-json/jwt/v1/logout` | Logout and revoke refresh token |

## 🔒 Security

- **Stateless Authentication** - JWT tokens contain all necessary information
- **HTTPOnly Cookies** - Refresh tokens stored securely, inaccessible to JavaScript
- **Token Rotation** - Refresh tokens automatically rotate on use
- **Configurable Expiration** - Set custom expiration times
- **IP & User Agent Tracking** - Additional security metadata

## ⚙️ Configuration

### Via wp-config.php (Recommended for production)
```php
define('WP_JWT_AUTH_SECRET', 'your-super-secret-key-here');
define('WP_JWT_ACCESS_TTL', 3600);      // 1 hour
define('WP_JWT_REFRESH_TTL', 2592000);  // 30 days
```

### Via WordPress Admin
Go to **Settings → WP REST Auth JWT** to configure:
- JWT Secret Key
- Token expiration times
- CORS allowed origins
- Debug logging

## 💡 Use Cases

Perfect for:
- **Single Page Applications** (React, Vue, Angular)
- **Mobile Applications** (iOS, Android)
- **API Integrations** (Third-party services)
- **Headless WordPress** (Decoupled architecture)

## 🔄 Token Flow

1. **Login** → Get access token + refresh token (HTTPOnly cookie)
2. **API Calls** → Use access token in Authorization header
3. **Token Expires** → Use refresh endpoint to get new access token
4. **Logout** → Revoke refresh token

## 🛠️ Advanced Usage

### JavaScript Client Example
```javascript
class JWTAuth {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
        this.accessToken = null;
    }

    async login(username, password) {
        const response = await fetch(`${this.baseUrl}/wp-json/jwt/v1/token`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include', // Important for HTTPOnly cookies
            body: JSON.stringify({ username, password })
        });

        if (response.ok) {
            const data = await response.json();
            this.accessToken = data.data.access_token;
            return data;
        }
        throw new Error('Login failed');
    }

    async apiCall(endpoint, options = {}) {
        const response = await fetch(`${this.baseUrl}${endpoint}`, {
            ...options,
            headers: {
                ...options.headers,
                'Authorization': `Bearer ${this.accessToken}`
            }
        });

        if (response.status === 401) {
            // Try to refresh token
            await this.refreshToken();
            // Retry original request
            return this.apiCall(endpoint, options);
        }

        return response;
    }

    async refreshToken() {
        const response = await fetch(`${this.baseUrl}/wp-json/jwt/v1/refresh`, {
            method: 'POST',
            credentials: 'include' // HTTPOnly cookie sent automatically
        });

        if (response.ok) {
            const data = await response.json();
            this.accessToken = data.data.access_token;
            return data;
        }

        // Refresh failed, need to login again
        this.accessToken = null;
        throw new Error('Please login again');
    }

    async logout() {
        await fetch(`${this.baseUrl}/wp-json/jwt/v1/logout`, {
            method: 'POST',
            credentials: 'include'
        });
        this.accessToken = null;
    }
}

// Usage
const auth = new JWTAuth('https://your-wordpress-site.com');
await auth.login('username', 'password');

// Make authenticated API calls
const posts = await auth.apiCall('/wp-json/wp/v2/posts');
```

## 🧪 Testing (wp-env)

Run tests using the NPM scripts which leverage wp-env:

```bash
# Start environment
npm run env:start

# Install Composer deps inside container (first run)
npm run composer:install

# Unit tests
npm run test:unit

# Integration tests
npm run test:integration

# All tests (unit + integration)
npm run test
```

## ❓ Need More Features?

This plugin provides simple JWT authentication. If you need:
- OAuth2 authorization flows
- Scoped permissions
- Third-party app authorization
- API Proxy for enhanced security

Check out our companion plugin: **WP REST Auth OAuth2**

## 📝 Requirements

- WordPress 5.6+
- PHP 7.4+
- HTTPS (recommended for HTTPOnly cookies)

## 📄 License

GPL v2 or later

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

**Simple. Secure. JWT.** 🔐