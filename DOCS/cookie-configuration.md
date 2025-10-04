# Cookie Configuration

JWT Auth Pro provides a flexible, environment-aware cookie configuration system that can be controlled through multiple layers:

1. **Constants** (highest priority)
2. **Filters**
3. **Environment-based defaults** (if auto-detection is enabled)
4. **Hard-coded defaults** (fallback)

## Quick Start

### Using Constants (wp-config.php)

The simplest way to configure cookie settings across environments:

```php
// wp-config.php - Production
define('JWT_AUTH_COOKIE_ENABLED', true);
define('JWT_AUTH_COOKIE_NAME', 'auth_session');
define('JWT_AUTH_COOKIE_SAMESITE', 'Strict');
define('JWT_AUTH_COOKIE_SECURE', true);
define('JWT_AUTH_COOKIE_HTTPONLY', true);
define('JWT_AUTH_COOKIE_PATH', '/wp-json/jwt/v1/');
define('JWT_AUTH_COOKIE_DOMAIN', '');
define('JWT_AUTH_COOKIE_LIFETIME', 7 * DAY_IN_SECONDS);

// Disable auto-detection to use only manual settings
define('JWT_AUTH_COOKIE_AUTO_DETECT', false);
```

```php
// wp-config.php - Development
define('JWT_AUTH_COOKIE_SECURE', false); // Allow HTTP
define('JWT_AUTH_COOKIE_SAMESITE', 'None'); // Allow cross-origin
define('JWT_AUTH_COOKIE_AUTO_DETECT', true); // Use environment defaults
```

### Using Filters (functions.php or plugin)

For programmatic control:

```php
// Modify entire configuration
add_filter('jwt_auth_cookie_config', function($config) {
    // Override all settings for this request
    $config['samesite'] = 'Lax';
    $config['secure'] = true;
    $config['lifetime'] = 3 * DAY_IN_SECONDS;

    return $config;
});

// Modify individual fields
add_filter('jwt_auth_cookie_secure', function($secure, $config) {
    // Force secure cookies in production
    if ($config['environment'] === 'production') {
        return true;
    }
    return $secure;
}, 10, 2);

// Conditional configuration based on user
add_filter('jwt_auth_cookie_lifetime', function($lifetime, $config) {
    // Longer session for admin users
    if (current_user_can('manage_options')) {
        return 30 * DAY_IN_SECONDS;
    }
    return $lifetime;
}, 10, 2);
```

## Available Constants

| Constant | Type | Description | Example |
|----------|------|-------------|---------|
| `JWT_AUTH_COOKIE_ENABLED` | bool | Enable/disable cookie authentication | `true` |
| `JWT_AUTH_COOKIE_NAME` | string | Cookie name (will be sanitized) | `'auth_session'` |
| `JWT_AUTH_COOKIE_SAMESITE` | string | SameSite attribute | `'Strict'`, `'Lax'`, or `'None'` |
| `JWT_AUTH_COOKIE_SECURE` | bool | Require HTTPS | `true` |
| `JWT_AUTH_COOKIE_HTTPONLY` | bool | Prevent JavaScript access | `true` (always recommended) |
| `JWT_AUTH_COOKIE_PATH` | string | Cookie path | `'/'` or `'/wp-json/jwt/v1/'` |
| `JWT_AUTH_COOKIE_DOMAIN` | string | Cookie domain | `''` or `'.example.com'` |
| `JWT_AUTH_COOKIE_LIFETIME` | int | Cookie lifetime in seconds | `DAY_IN_SECONDS` |
| `JWT_AUTH_COOKIE_AUTO_DETECT` | bool | Enable environment auto-detection | `true` |

## Available Filters

### Global Configuration Filter

**`jwt_auth_cookie_config`** - Modify entire configuration array

```php
apply_filters('jwt_auth_cookie_config', array $config): array
```

**Parameters:**
- `$config` (array) - Complete configuration array

**Returns:** Modified configuration array

**Example:**
```php
add_filter('jwt_auth_cookie_config', function($config) {
    // Add custom logic
    if ($_SERVER['HTTP_HOST'] === 'api.example.com') {
        $config['path'] = '/';
        $config['domain'] = '.example.com';
    }
    return $config;
});
```

### Individual Field Filters

Each configuration field has its own filter:

- **`jwt_auth_cookie_enabled`** - `(bool $enabled, array $config): bool`
- **`jwt_auth_cookie_name`** - `(string $name, array $config): string`
- **`jwt_auth_cookie_samesite`** - `(string $samesite, array $config): string`
- **`jwt_auth_cookie_secure`** - `(bool $secure, array $config): bool`
- **`jwt_auth_cookie_httponly`** - `(bool $httponly, array $config): bool`
- **`jwt_auth_cookie_path`** - `(string $path, array $config): string`
- **`jwt_auth_cookie_domain`** - `(string $domain, array $config): string`
- **`jwt_auth_cookie_lifetime`** - `(int $lifetime, array $config): int`

**Example:**
```php
// Force specific SameSite for staging
add_filter('jwt_auth_cookie_samesite', function($samesite, $config) {
    if ($config['environment'] === 'staging') {
        return 'Lax';
    }
    return $samesite;
}, 10, 2);
```

### Environment Defaults Filter

**`jwt_auth_cookie_environment_defaults`** - Customize environment-specific defaults

```php
apply_filters('jwt_auth_cookie_environment_defaults', array $defaults, string $environment): array
```

**Example:**
```php
add_filter('jwt_auth_cookie_environment_defaults', function($defaults, $environment) {
    if ($environment === 'development') {
        // Custom development defaults
        $defaults['lifetime'] = HOUR_IN_SECONDS;
    }
    return $defaults;
}, 10, 2);
```

## Environment Detection

The plugin automatically detects your environment using:

1. **`wp_get_environment_type()`** (WordPress 5.5+)
2. **Domain patterns**:
   - Development: `localhost`, `*.local`, `*.test`, or `WP_DEBUG` enabled
   - Staging: domains containing "staging", "dev", or "test"
   - Production: all others

### Environment-Specific Defaults

When auto-detection is enabled:

#### Development
```php
'secure'   => is_ssl(),  // Only if using HTTPS
'samesite' => 'None',    // Allow cross-origin for SPAs
'path'     => '/',
```

#### Staging
```php
'secure'   => true,
'samesite' => 'Lax',  // Relaxed for testing
'path'     => '/',
```

#### Production
```php
'secure'   => true,
'samesite' => 'Strict',  // Maximum security
'path'     => '/wp-json/jwt/v1/',  // Restricted path
```

## Configuration Priority

Understanding the order helps you override settings correctly:

```
1. Constants (JWT_AUTH_COOKIE_*)           ← Highest priority
   ↓
2. Filters (jwt_auth_cookie_*)
   ↓
3. Environment Defaults (if auto-detect enabled)
   ↓
4. Hard-coded Defaults                      ← Fallback
```

## Debug Mode

Enable debug logging to see which configuration is being used:

```php
// wp-config.php
define('JWT_AUTH_DEBUG', true);
```

This will log configuration details to `wp-content/debug.log`:

```
[JWT Auth Cookie Config] env=development, auto_detect=yes, samesite=None, secure=no, httponly=yes, path=/
```

## Common Use Cases

### 1. Subdomain Cookie Sharing

Share cookies across subdomains (e.g., `app.example.com` and `api.example.com`):

```php
define('JWT_AUTH_COOKIE_DOMAIN', '.example.com');
define('JWT_AUTH_COOKIE_PATH', '/');
```

### 2. Development with HTTPS

If you're using HTTPS in development (e.g., with Laravel Valet):

```php
// Let auto-detection handle it, or force:
define('JWT_AUTH_COOKIE_SECURE', true);
```

### 3. Different Lifetimes per Role

```php
add_filter('jwt_auth_cookie_lifetime', function($lifetime) {
    if (current_user_can('administrator')) {
        return 30 * DAY_IN_SECONDS; // 30 days for admins
    }
    return 7 * DAY_IN_SECONDS; // 7 days for others
});
```

### 4. Disable Cookies Entirely

```php
define('JWT_AUTH_COOKIE_ENABLED', false);
```

### 5. Force Manual Configuration

Disable auto-detection and use only your settings:

```php
define('JWT_AUTH_COOKIE_AUTO_DETECT', false);
// Then set all other constants manually
```

## Security Best Practices

### ✅ DO:

- **Always use `HttpOnly`** (prevents XSS attacks)
- **Use `Secure` in production** (HTTPS only)
- **Use `SameSite=Strict`** in production (prevents CSRF)
- **Keep lifetimes short** (1-7 days for access tokens)
- **Restrict cookie path** when possible

### ❌ DON'T:

- Don't set `HttpOnly=false` (major security risk)
- Don't use `SameSite=None` in production unless necessary
- Don't use overly broad paths (`/`) in production
- Don't share cookies across unrelated domains
- Don't use long lifetimes (>30 days) for authentication cookies

## Validation Rules

The configuration system enforces these rules:

1. **SameSite=None requires Secure=true** (browser requirement)
2. **SameSite must be** `'Strict'`, `'Lax'`, or `'None'`
3. **Lifetime must be positive** (defaults to 1 day if invalid)
4. **Cookie name cannot be empty** (defaults to `'jwtauth_session'`)

## Troubleshooting

### Cookies not being set?

1. Check if cookies are enabled: `JWT_AUTH_COOKIE_ENABLED`
2. Verify HTTPS if `secure=true`
3. Check browser console for SameSite warnings
4. Enable debug mode to see configuration

### Cross-origin issues?

1. Set `SameSite=None` and `Secure=true`
2. Ensure CORS headers are properly configured
3. Check cookie domain includes both origins

### Cookies not persisting?

1. Verify lifetime is long enough
2. Check cookie path matches your API routes
3. Ensure domain is correct (or empty)

## Example Configurations

### Headless WordPress (SPA on different domain)

```php
// Production setup for SPA at https://app.example.com
// API at https://api.example.com

define('JWT_AUTH_COOKIE_SAMESITE', 'None');  // Required for cross-origin
define('JWT_AUTH_COOKIE_SECURE', true);       // Required with SameSite=None
define('JWT_AUTH_COOKIE_DOMAIN', '');         // Don't share across domains
define('JWT_AUTH_COOKIE_PATH', '/');
```

### Multi-site Network

```php
// Share authentication across all sites
define('JWT_AUTH_COOKIE_DOMAIN', '.' . DOMAIN_CURRENT_SITE);
define('JWT_AUTH_COOKIE_PATH', '/');
define('JWT_AUTH_COOKIE_SAMESITE', 'Lax');
```

### Mobile App API

```php
// Strict security for mobile app backend
define('JWT_AUTH_COOKIE_SAMESITE', 'Strict');
define('JWT_AUTH_COOKIE_SECURE', true);
define('JWT_AUTH_COOKIE_PATH', '/wp-json/jwt/v1/');
define('JWT_AUTH_COOKIE_LIFETIME', 7 * DAY_IN_SECONDS);
```

## API Reference

### `JWT_Cookie_Config::get_config()`

Get the current active configuration.

```php
$config = JWT_Cookie_Config::get_config();

// Returns:
array(
    'enabled'     => true,
    'name'        => 'jwtauth_session',
    'samesite'    => 'Strict',
    'secure'      => true,
    'httponly'    => true,
    'path'        => '/wp-json/jwt/v1/',
    'domain'      => '',
    'lifetime'    => 86400,
    'environment' => 'production',
    'auto_detect' => true,
)
```

### `JWT_Cookie_Config::get_environment()`

Get the current detected environment.

```php
$env = JWT_Cookie_Config::get_environment();
// Returns: 'development', 'staging', or 'production'
```

### `JWT_Cookie_Config::is_development()`

Check if current environment is development.

```php
if (JWT_Cookie_Config::is_development()) {
    // Development-specific code
}
```

### `JWT_Cookie_Config::clear_cache()`

Clear the configuration cache (automatically called when settings are saved).

```php
JWT_Cookie_Config::clear_cache();
$config = JWT_Cookie_Config::get_config(); // Fresh configuration
```

## Further Reading

- [CORS and Cookies Guide](./cors-and-cookies.md)
- [Security Best Practices](../README.md#-security)
- [WordPress Environment Types](https://make.wordpress.org/core/2020/07/24/new-wp_get_environment_type-function-in-wordpress-5-5/)
- [SameSite Cookies Explained](https://web.dev/samesite-cookies-explained/)
