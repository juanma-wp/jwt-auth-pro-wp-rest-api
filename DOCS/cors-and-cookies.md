# CORS & Cookies

Guidance for SPAs/mobile apps using JWT Auth Pro across origins.

## Client (fetch)

- Include credentials for login/refresh/logout so cookies are sent:

```javascript
await fetch('https://api.example.com/wp-json/jwt/v1/token', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',
  body: JSON.stringify({ username, password })
});

await fetch('https://api.example.com/wp-json/jwt/v1/refresh', {
  method: 'POST',
  credentials: 'include'
});
```

- Send access token in Authorization header for API calls:

```javascript
await fetch('https://api.example.com/wp-json/wp/v2/posts', {
  headers: { Authorization: `Bearer ${accessToken}` }
});
```

## Server (CORS headers)

When using cookies cross-site:

- Access-Control-Allow-Origin: https://your-frontend.example
- Access-Control-Allow-Credentials: true
- Access-Control-Allow-Headers: Content-Type, Authorization
- Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS

Notes:
- Do not use `*` for `Access-Control-Allow-Origin` when `Allow-Credentials` is `true`.
- Ensure OPTIONS preflight is handled and returns the same headers.

## Cookie attributes

- HttpOnly; Secure; SameSite=None; Path=/
- Use HTTPS in production (required for `SameSite=None` + `Secure`).

## WordPress plugin settings

- Configure allowed origins in plugin settings (Settings → WP REST Auth JWT).
- Enable debug logging if troubleshooting CORS/cookie issues.


