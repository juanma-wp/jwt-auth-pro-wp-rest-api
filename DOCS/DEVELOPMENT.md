# Development Guide

## Cross-Origin Development Setup

When developing a frontend application (React, Vue, Angular, etc.) on a different port or domain than your WordPress installation, you need to configure CORS and cookie settings properly.

### Quick Setup for Cross-Origin Development

The plugin automatically detects development environments and configures cookies for cross-origin requests. The default settings are:

- **SameSite**: `None` (allows cross-origin cookie sending)
- **Secure**: `false` (allows HTTP for localhost)
- **HttpOnly**: `true` (XSS protection)
- **Path**: `/` (available site-wide)

### Example Setup

**Frontend**: `http://localhost:5173` (Vite/React)
**Backend**: `http://localhost:8884` (WordPress)

#### 1. Configure CORS Origins

In your WordPress admin panel, go to:
**Settings → JWT Auth Pro → General Settings**

Add your frontend origin to **CORS Allowed Origins**:
```
http://localhost:5173
http://localhost:3000
http://localhost:5174
```

#### 2. That's it!

The plugin automatically:
- Sets `SameSite=None` to allow cross-origin cookies
- Allows HTTP for localhost development
- Configures proper CORS headers

### Frontend Configuration

Make sure your frontend includes credentials in requests:

#### Fetch API
```javascript
fetch('http://localhost:8884/wp-json/jwt/v1/refresh', {
  method: 'POST',
  credentials: 'include', // Important: includes cookies
  headers: {
    'Content-Type': 'application/json'
  }
})
```

#### Axios
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8884/wp-json',
  withCredentials: true // Important: includes cookies
});

// Refresh token
api.post('/jwt/v1/refresh');
```

### Alternative: Proxy Configuration

If you prefer to avoid cross-origin issues entirely, configure your frontend dev server to proxy API requests:

#### Vite
```javascript
// vite.config.js
export default {
  server: {
    proxy: {
      '/wp-json': {
        target: 'http://localhost:8884',
        changeOrigin: true,
        secure: false,
      }
    }
  }
}
```

#### Create React App
```javascript
// package.json
{
  "proxy": "http://localhost:8884"
}
```

#### Next.js
```javascript
// next.config.js
module.exports = {
  async rewrites() {
    return [
      {
        source: '/wp-json/:path*',
        destination: 'http://localhost:8884/wp-json/:path*',
      },
    ]
  },
}
```

With proxy configuration, make requests to `/wp-json/jwt/v1/refresh` instead of the full URL.

### Custom Cookie Configuration

If you need different cookie settings, you can override them in `wp-config.php`:

```php
// Force specific cookie settings
define('JWT_AUTH_COOKIE_SAMESITE', 'None');
define('JWT_AUTH_COOKIE_SECURE', false);
define('JWT_AUTH_COOKIE_PATH', '/');
```

Or use filters in your theme/plugin:

```php
// Override SameSite for development
add_filter('jwt_auth_cookie_samesite', function() {
    return 'None';
});

// Override Secure flag
add_filter('jwt_auth_cookie_secure', function() {
    return false;
});
```

### Environment Detection

The plugin automatically detects your environment:

- **Development**: localhost, *.local, *.test domains, or `WP_DEBUG=true`
- **Staging**: domains containing "staging", "dev", or "test"
- **Production**: all other domains

### Troubleshooting

#### Cookies not being sent?

1. Check browser console for CORS errors
2. Verify `credentials: 'include'` (fetch) or `withCredentials: true` (axios)
3. Ensure frontend origin is in CORS allowed origins
4. Check cookie settings in browser DevTools → Application → Cookies

#### Still getting "missing_refresh_token"?

1. Verify the cookie `wp_jwt_refresh_token` exists in browser DevTools
2. Check that requests include the cookie in Network tab → Headers → Cookie
3. Ensure you're using the same protocol (both HTTP or both HTTPS)
4. Try clearing browser cookies and logging in again

### Production Considerations

**Important**: These development settings are **NOT suitable for production**!

In production:
- Always use HTTPS
- Set `SameSite=Strict` or `SameSite=Lax`
- Set `Secure=true`
- Restrict CORS origins to your actual production domains
- Consider using a reverse proxy instead of CORS

The plugin automatically uses secure settings in production environments.
