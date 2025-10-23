# JWT Auth Pro WP REST API - Secure Refresh Tokens

[![Plugin Check](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/plugin-check.yml/badge.svg?branch=main)](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/plugin-check.yml)
[![Unit Tests](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/unit-tests.yml/badge.svg?branch=main)](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/unit-tests.yml)
[![Integration Tests](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/integration-tests.yml/badge.svg?branch=main)](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/integration-tests.yml)
[![Behat Tests](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/behat-tests.yml/badge.svg?branch=main)](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/behat-tests.yml)
[![PHPCS](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/phpcs.yml/badge.svg?branch=main)](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/phpcs.yml)
[![PHPStan](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/phpstan.yml/badge.svg?branch=main)](https://github.com/juanma-wp/wp-rest-auth-jwt/actions/workflows/phpstan.yml)

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html) [![WordPress](https://img.shields.io/badge/WordPress-%3E%3D5.6-blue.svg)](https://wordpress.org) [![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue.svg)](https://php.net)

Modern JWT authentication with refresh tokens for WordPress REST API - built for SPAs and mobile apps


## 🚀 Why JWT Auth Pro?

Unlike basic JWT plugins that use **single long-lived tokens**, JWT Auth Pro implements **modern OAuth 2.0 security best practices** with short-lived access tokens and secure refresh tokens.

### ⚡ Security Comparison

| Feature | Basic JWT Plugins | JWT Auth Pro |
|---------|-------------------|--------------|
| **Token Lifetime** | Long (hours/days) ❌ | Short (1 hour) ✅ |
| **Refresh Tokens** | None ❌ | Secure HTTP-only ✅ |
| **XSS Protection** | Limited ❌ | HTTP-only cookies ✅ |
| **Token Revocation** | Manual only ❌ | Automatic rotation ✅ |
| **Session Management** | None ❌ | Database tracking ✅ |
| **Security Metadata** | None ❌ | IP + User Agent ✅ |

### 🔒 **The Problem with Basic JWT Plugins:**
- **Long-lived tokens** (24h+) = Higher security risk
- **No refresh mechanism** = Tokens live until expiry
- **XSS vulnerable** = Tokens stored in localStorage
- **No revocation** = Can't invalidate compromised tokens

### ✅ **JWT Auth Pro Solution:**
- **Short-lived access tokens** (1h default) = Minimal attack window
- **Secure refresh tokens** = HTTP-only cookies, XSS protected
- **Automatic token rotation** = Fresh tokens on each refresh
- **Complete session control** = Revoke any user session instantly

🔐 **The most secure JWT authentication plugin for WordPress.**

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
2. Set token expiration times if needed (or leave the defaults)
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

## 🔧 Cross-Origin Development Setup

### ⚠️ Important: HTTPS Required on WordPress Server

When developing with a separate frontend (React, Vue, etc.) on a different port, **the WordPress server must use HTTPS** for refresh tokens to work properly:

- **Cross-origin cookies require `SameSite=None`** to work across different origins
- **`SameSite=None` requires `Secure=true`** (HTTPS only) per browser specifications
- Without HTTPS on the server, refresh tokens stored in HttpOnly cookies **will not be sent** on cross-origin requests

**Note:** Your frontend can use either HTTP or HTTPS - only the WordPress server needs HTTPS.

### 🎯 Recommended Solution: WordPress Studio

[**WordPress Studio**](https://developer.wordpress.com/studio/) is the easiest way to set up a local WordPress development environment with HTTPS enabled:

1. **Download WordPress Studio** (free from Automattic)
2. **Create a new site** or import your existing one
3. **Enable HTTPS** in site settings (one click)
4. **Configure CORS** in the plugin settings for your frontend URL

Example working setup:
```
WordPress API: https://localhost:8881 (WordPress Studio with HTTPS) ✅
React App:     http://localhost:5173  (Can be HTTP or HTTPS) ✅
```


### CORS Configuration

Add your frontend URL to the CORS allowed origins in the plugin settings:
```
https://localhost:5173
https://localhost:3000
https://your-app.com
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
define('JWT_AUTH_PRO_SECRET', 'your-super-secret-key-here');
define('JWT_AUTH_PRO_ACCESS_TTL', 3600);      // 1 hour
define('JWT_AUTH_PRO_REFRESH_TTL', 2592000);  // 30 days

// Cookie configuration (optional)
define('JWT_AUTH_COOKIE_SAMESITE', 'Strict'); // 'Strict', 'Lax', or 'None'
define('JWT_AUTH_COOKIE_SECURE', true);       // Require HTTPS
define('JWT_AUTH_COOKIE_LIFETIME', 7 * DAY_IN_SECONDS);
define('JWT_AUTH_COOKIE_AUTO_DETECT', true);  // Auto-configure based on environment
```

### Via WordPress Admin
Go to **Settings → WP REST Auth JWT** to configure:
- JWT Secret Key
- Token expiration times
- Cookie security settings (with environment auto-detection)
- CORS allowed origins
- Debug logging

### Environment-Aware Configuration
The plugin automatically detects your environment and adjusts cookie settings:
- **Development**: Relaxed settings for local testing
- **Staging**: Balanced settings for testing
- **Production**: Maximum security settings

See [Cookie Configuration Guide](DOCS/cookie-configuration.md) for advanced options using constants and filters.

## 🚨 Troubleshooting

### Refresh Token Not Working (Cookie Not Set)

**Symptom:** Login works but refresh token returns 401 Unauthorized

**Common Causes & Solutions:**

1. **WordPress server using HTTP instead of HTTPS**
   - Solution: Enable HTTPS on WordPress (use WordPress Studio or self-signed certificates)
   - Check: The `Set-Cookie` header should include `Secure` attribute

2. **Wrong SameSite configuration**
   - For cross-origin: Needs `SameSite=None` with `Secure=true`
   - Check browser DevTools → Network → Response Headers for `Set-Cookie`

3. **Browser blocking third-party cookies**
   - Some browsers block all third-party cookies regardless of SameSite
   - Solution: Use same domain/subdomain or configure exceptions

### CORS Errors

**Symptom:** "Access to fetch at ... has been blocked by CORS policy"

**Solutions:**
1. Add your frontend URL to CORS allowed origins in plugin settings
2. Ensure the exact origin is listed (including protocol and port)
3. Check that CORS headers are present in response

### Cookie Not Visible in Browser

**This is expected!** Refresh tokens use HttpOnly cookies which are:
- Not accessible via JavaScript (security feature)
- Automatically sent by the browser with requests
- Only visible in DevTools → Application → Cookies

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

### 📊 Visual Workflow Diagrams

For detailed visual representations of the authentication workflows, see our comprehensive [Workflow Diagrams](DOCS/diagrams.md) which include:

- **Authentication Flow**: Login process and JWT token generation
- **Token Validation Flow**: API request validation with JWT
- **Token Renewal Flow**: Refresh token rotation and renewal process
- **Logout Flow**: Token revocation and session cleanup
- **Complete Session Lifecycle**: Full user session state diagram
- **Architecture & Security Model**: Component and security diagrams

## 🛠️ Advanced Usage

- [Cookie Configuration Guide](DOCS/cookie-configuration.md) - Environment detection, constants, and filters
- [JavaScript Client Example](DOCS/advanced-usage.md) - Full client-side implementation
- [CORS and Cookies Setup](DOCS/cors-and-cookies.md) - Cross-origin configuration

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

# Behat E2E tests
npm run test:behat

# All tests (unit + integration + behat)
npm run test
```

## 🔧 Cross-Origin Development

Developing a frontend on a different port (e.g., React on `:5173`, WordPress on `:8884`)?

**The plugin automatically configures cookies for cross-origin development!**

Just add your frontend origin to **Settings → JWT Auth Pro → CORS Allowed Origins**:
```
http://localhost:5173
http://localhost:3000
```

The plugin will automatically:
- Set `SameSite=None` to allow cross-origin cookies
- Configure cookies for HTTP localhost development
- Handle CORS headers properly

**📖 For detailed setup instructions**, see [DEVELOPMENT.md](./DOCS/DEVELOPMENT.md)

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
- HTTPS (recommended for production; localhost HTTP supported for development)

## 📄 License

GPL v2 or later

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📚 References

- **OAuth 2.0 Security Best Current Practice (IETF)**: [datatracker draft](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-security-topics)
- **OAuth 2.0 for Browser-Based Apps (IETF)**: [datatracker draft](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-browser-based-apps)
- **OAuth 2.0 Token Revocation (RFC 7009)**: [RFC 7009](https://datatracker.ietf.org/doc/html/rfc7009)
- **JWT storage guidance (OWASP)**: [OWASP JWT Cheat Sheet — Where to store JWTs](https://cheatsheetseries.owasp.org/cheatsheets/JSON_Web_Token_for_Java_Cheat_Sheet.html#where-to-store-jwts)
- **Session management guidance (OWASP)**: [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- **HttpOnly cookies not readable by JS (MDN)**: [MDN Set-Cookie — HttpOnly](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#httponly)
- **Refresh token rotation & reuse detection (Auth0)**: [Auth0 Docs — Refresh Token Rotation](https://auth0.com/docs/secure/tokens/refresh-tokens/refresh-token-rotation)

---

**Simple. Secure. JWT.** 🔐