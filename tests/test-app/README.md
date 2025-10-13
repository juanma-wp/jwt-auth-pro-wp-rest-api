# JWT Test Client App

A simple HTML/JavaScript application for testing JWT authentication with HTTPOnly cookies.

## Features

- ✅ Test login with username/password
- ✅ View access token (stored in JS)
- ✅ Inspect HTTPOnly refresh token cookie (via DevTools)
- ✅ Test token refresh flow (automatic cookie sending)
- ✅ Verify token endpoint
- ✅ Test logout and cookie cleanup
- ✅ Network activity logging
- ✅ Multiple scenario support (same-domain, cross-origin, localhost)

## Quick Start

1. **Install http-server** (if not already installed):
   ```bash
   npm install -g http-server
   ```

2. **Start the test app**:
   ```bash
   cd tests/test-app
   npm start
   ```

   This will start the app at: **http://localhost:5173**

3. **Open in browser**:
   ```
   http://localhost:5173
   ```

4. **Configure and test**:
   - Set WordPress API URL (default: `https://rest-api-tests.wp.local/wp-json/jwt/v1`)
   - Enter credentials (default: `admin` / `S$genSlH%24GLe0k1hy(C6r3`)
   - Select test scenario
   - Click "Login" to test

## Testing Scenarios

### Same Domain
- App: `http://localhost:5173`
- API: `http://localhost:5173/wp-json` (not applicable for this setup)
- Cookie: `SameSite=Lax`

### Cross-Origin (Default)
- App: `http://localhost:5173`
- API: `https://rest-api-tests.wp.local`
- Cookie: `SameSite=None; Secure`
- **Requires**: CORS configured in WordPress

### Localhost Development
- App: `http://localhost:5173`
- API: `http://localhost:8080` (or any local WP)
- Cookie: `SameSite=None` (without Secure - only works on localhost)

## How to Use

### 1. Configure
- Enter your WordPress API URL
- Select the test scenario that matches your setup
- Save configuration (persists in localStorage)

### 2. Login
- Enter username and password
- Click "Login"
- Access token will be displayed
- Refresh token cookie will be set (HTTPOnly - not visible in JS)

### 3. Check Cookies
- Click "Check Cookies" to verify HTTPOnly behavior
- Open DevTools → Application → Cookies to see the actual cookie
- Cookie should have: `HttpOnly`, `SameSite`, and optionally `Secure` flags

### 4. Refresh Token
- Click "Refresh Token" to get a new access token
- The HTTPOnly cookie is sent automatically by the browser
- You won't see the cookie in JavaScript (this is correct!)

### 5. Verify Token
- Click "Verify Current Token" to check if token is valid
- Returns user information from WordPress

### 6. Logout
- Click "Logout" to clear tokens and cookies
- HTTPOnly cookie will be removed by the server

## CORS Setup

For **cross-origin testing**, add this app's URL to WordPress CORS settings:

```bash
# Via WP-CLI
cd /path/to/wordpress
wp option update jwt_auth_pro_general_settings --format=json '{"cors_allowed_origins":"http://localhost:5173"}'

# Or via configuration script
node tests/scripts/configure-scenario.js cross-origin
```

## Network Activity

The app logs all network requests and responses:
- 🔵 Blue = Request
- 🟢 Green = Response/Success
- 🔴 Red = Error
- ⚫ Gray = Info

Check the "Network Activity" section to debug issues.

## Understanding HTTPOnly Cookies

### What You'll See
- ✅ Cookie is set (visible in DevTools)
- ✅ Cookie is sent with requests (visible in Network tab)
- ❌ Cookie is NOT visible in `document.cookie` (JavaScript blocked)

### Why This Matters
- **Security**: Protects against XSS attacks
- **Automatic**: Browser sends cookie without JavaScript
- **Transparent**: Works without manual cookie management

### How to Inspect
1. Open Browser DevTools (F12)
2. Go to **Application** tab (Chrome) or **Storage** tab (Firefox)
3. Click **Cookies** → `http://localhost:5173`
4. Look for `refresh_token` cookie
5. Check the attributes:
   - ✅ `HttpOnly` = true
   - ✅ `SameSite` = Lax/None/Strict
   - ✅ `Secure` = true (for HTTPS)

## HTTPS Testing

To test with HTTPS (required for `SameSite=None` in production):

1. **Generate self-signed certificate**:
   ```bash
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
     -keyout key.pem -out cert.pem \
     -subj "/CN=localhost"
   ```

2. **Start with SSL**:
   ```bash
   npm run start:ssl
   ```

3. **Access**:
   ```
   https://localhost:5173
   ```

4. **Accept certificate warning** in browser

## Troubleshooting

### Cookie not set
- Check CORS headers in response
- Ensure `Access-Control-Allow-Credentials: true`
- Verify WordPress plugin is active

### Cookie not sent on refresh
- Check `SameSite` attribute matches scenario
- For cross-origin: Need `SameSite=None; Secure` (HTTPS required)
- For same-origin: `SameSite=Lax` is fine

### CORS errors
- Add app URL to WordPress CORS settings
- Check WordPress debug logs
- Ensure credentials: 'include' in fetch requests

### Token expired
- Use "Refresh Token" to get a new one
- Check token expiry time (default: 1 hour)

## Files

```
tests/test-app/
├── index.html          # Main UI
├── app.js             # Application logic
├── package.json       # npm scripts
└── README.md          # This file
```

## Integration with Playwright Tests

This app is designed to work with the Playwright test suite:

```bash
# Start test app
cd tests/test-app
npm start

# Run Playwright tests (in another terminal)
cd ../..
npm run test:e2e
```

The tests will interact with this app to verify cookie behavior, CORS, and token flows.
