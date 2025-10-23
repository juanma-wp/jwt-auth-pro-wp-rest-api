=== JuanMa JWT Auth Pro ===
Contributors: juanmaguitar
Tags: jwt, authentication, rest-api, security, tokens
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern JWT authentication with refresh tokens - built for SPAs and mobile apps with enterprise-grade security.

== Description ==

Unlike basic JWT plugins that use **single long-lived tokens**, JWT Auth Pro implements **modern OAuth 2.0 security best practices** with short-lived access tokens and secure refresh tokens.

= Why JWT Auth Pro? =

**The Problem with Basic JWT Plugins:**
* Long-lived tokens (24h+) = Higher security risk
* No refresh mechanism = Tokens live until expiry
* XSS vulnerable = Tokens stored in localStorage
* No revocation = Can't invalidate compromised tokens

**JWT Auth Pro Solution:**
* Short-lived access tokens (1h default) = Minimal attack window
* Secure refresh tokens = HTTP-only cookies, XSS protected
* Automatic token rotation = Fresh tokens on each refresh
* Complete session control = Revoke any user session instantly

= Features =

* **Simple JWT Authentication** - Clean, stateless token-based auth
* **HTTPOnly Refresh Tokens** - Secure refresh tokens in HTTP-only cookies
* **Token Rotation** - Automatic refresh token rotation for enhanced security
* **CORS Support** - Proper cross-origin request handling
* **Clean Admin Interface** - Simple configuration in WordPress admin
* **Developer Friendly** - Clear endpoints and documentation

= Security Comparison =

| Feature | Basic JWT Plugins | JWT Auth Pro |
|---------|-------------------|--------------|
| Token Lifetime | Long (hours/days) | Short (1 hour) |
| Refresh Tokens | None | Secure HTTP-only |
| XSS Protection | Limited | HTTP-only cookies |
| Token Revocation | Manual only | Automatic rotation |
| Session Management | None | Database tracking |
| Security Metadata | None | IP + User Agent |

= Perfect for: =

* Single Page Applications (React, Vue, Angular)
* Mobile Applications (iOS, Android)
* API Integrations (Third-party services)
* Headless WordPress (Decoupled architecture)

= API Endpoints =

* `POST /wp-json/jwt/v1/token` - Login and get access token
* `POST /wp-json/jwt/v1/refresh` - Refresh access token
* `GET /wp-json/jwt/v1/verify` - Verify token and get user info
* `POST /wp-json/jwt/v1/logout` - Logout and revoke refresh token

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → JWT Auth Pro to configure the plugin

= Configuration =

**Via wp-config.php (Recommended for production):**
```php
define('JWT_AUTH_PRO_SECRET', 'your-super-secret-key-here');
define('JWT_AUTH_PRO_ACCESS_TTL', 3600);      // 1 hour
define('JWT_AUTH_PRO_REFRESH_TTL', 2592000);  // 30 days
```

**Via WordPress Admin:**
Go to Settings → JWT Auth Pro to configure:
* JWT Secret Key
* Token expiration times
* CORS allowed origins
* Debug logging

== Frequently Asked Questions ==

= How is this different from other JWT plugins? =

JWT Auth Pro implements modern security best practices with short-lived access tokens and secure refresh tokens, unlike basic JWT plugins that use long-lived tokens vulnerable to XSS attacks.

= Is HTTPS required? =

HTTPS is strongly recommended for HTTPOnly cookies to work securely, especially in production environments.

= Can I use this with mobile apps? =

Yes! JWT Auth Pro is designed specifically for modern applications including mobile apps, SPAs, and API integrations.

= How do I revoke a user's session? =

You can revoke individual user sessions through the admin interface or programmatically using the provided API endpoints.

== Screenshots ==

1. Admin configuration interface
2. Security settings panel
3. Token management dashboard
4. CORS configuration

== Changelog ==

= 1.0.0 =
* Initial release
* JWT authentication with access and refresh tokens
* HTTPOnly cookie support for secure refresh tokens
* Automatic token rotation
* CORS configuration
* Admin interface for plugin configuration
* Database session tracking
* IP and User Agent metadata for enhanced security

== Security ==

* **Stateless Authentication** - JWT tokens contain all necessary information
* **HTTPOnly Cookies** - Refresh tokens stored securely, inaccessible to JavaScript
* **Token Rotation** - Refresh tokens automatically rotate on use
* **Configurable Expiration** - Set custom expiration times
* **IP & User Agent Tracking** - Additional security metadata

== Support ==

For support and documentation, visit: https://github.com/juanma-wp/jwt-auth-pro-wp-rest-api

== Privacy Policy ==

This plugin stores user session data including IP addresses and user agent strings for security purposes. This data is used solely for authentication and security monitoring.