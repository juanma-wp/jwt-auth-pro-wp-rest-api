# Cookie Configuration Guide

Complete guide to understanding and configuring JWT refresh token cookies in JWT Auth Pro.

## Table of Contents

1. [Overview](#overview)
2. [Understanding the Refresh Token Cookie](#understanding-the-refresh-token-cookie)
3. [Cookie Attributes Explained](#cookie-attributes-explained)
4. [Environment Auto-Detection](#environment-auto-detection)
5. [Configuration Scenarios](#configuration-scenarios)
6. [Troubleshooting](#troubleshooting)

---

## Overview

JWT Auth Pro uses **HTTP-only cookies** to store refresh tokens, following OAuth 2.0 security best practices. This guide explains:

- What the refresh token cookie is and why it's used
- What each cookie attribute means
- How environment auto-detection works
- Recommended settings for different scenarios

---

## Understanding the Refresh Token Cookie

### What is it?

When you log in via `/wp-json/jwt/v1/token`, the plugin returns:

1. **Access Token (JWT)** - Stored by your frontend app (localStorage/memory)
2. **Refresh Token Cookie** - Automatically stored by the browser

```
Cookie Name: wp_jwt_refresh_token
Cookie Value: [64-character random hex string]
Example: a3f2e1d4c5b6a7f8e9d0c1b2a3f4e5d6c7b8a9f0e1d2c3b4a5f6e7d8c9f0a1b2
```

### Why use a cookie?

**Security advantages:**
- **HttpOnly** - JavaScript cannot access it (prevents XSS attacks)
- **Secure** - Only sent over HTTPS (prevents MITM attacks)
- **Automatic** - Browser handles storage and transmission
- **Short access tokens** - Access tokens expire in 1 hour; refresh tokens in 30 days

### How it works

```
1. Login
   POST /wp-json/jwt/v1/token
   ← Response: { access_token: "..." }
   ← Set-Cookie: wp_jwt_refresh_token=abc123...; HttpOnly; Secure

2. Use access token
   GET /wp-json/wp/v2/posts
   Authorization: Bearer <access_token>

3. Token expires (after 1 hour)
   Access token no longer valid

4. Refresh token
   POST /wp-json/jwt/v1/refresh
   Cookie: wp_jwt_refresh_token=abc123...  ← Sent automatically
   ← Response: { access_token: "new_token..." }
```

---

## Cookie Attributes Explained

### Complete cookie structure

```
Set-Cookie: wp_jwt_refresh_token=abc123...;
  HttpOnly;
  Secure;
  SameSite=None;
  Path=/;
  Domain=;
  Max-Age=2592000
```

### HttpOnly

```
HttpOnly
```

**What it does:**
- Prevents JavaScript from accessing the cookie via `document.cookie`
- The cookie is invisible to client-side scripts

**Security benefit:**
- Protects against XSS (Cross-Site Scripting) attacks
- Even if malicious JavaScript runs on your site, it cannot steal the refresh token

**Configuration:**
- **Always enabled** by default
- Can only be disabled in `WP_DEBUG` mode (not recommended)

---

### Secure

```
Secure
```

**What it does:**
- Cookie is only sent over HTTPS connections
- Browser will NOT send the cookie over HTTP

**Security benefit:**
- Protects against MITM (Man-in-the-Middle) attacks
- Token cannot be intercepted over unencrypted connections

**When required:**
- **Mandatory** when `SameSite=None` (browser requirement)
- Recommended for all production environments

**Configuration:**
- Auto-detected based on environment (see below)
- Override with constant `JWT_AUTH_COOKIE_SECURE` or filter `jwt_auth_cookie_secure`

---

### SameSite

```
SameSite=None | Lax | Strict
```

**What it does:**
Controls when the browser sends the cookie in cross-site requests.

#### SameSite=None

```
SameSite=None
```

**Behavior:** Cookie sent on all requests, including cross-site
**Use case:** SPA on different domain than backend
**Requirement:** Must have `Secure=true`

**Example:**
```
Frontend: http://localhost:5174
Backend:  https://wordpress.local
→ SameSite=None allows cross-domain cookies
```

#### SameSite=Lax

```
SameSite=Lax
```

**Behavior:** Cookie sent on:
- Top-level navigation (clicking links)
- NOT sent on cross-site AJAX/fetch requests

**Use case:** Staging environments, subdomains
**Security:** Moderate protection against CSRF

**Example:**
```
Frontend: https://app.example.com
Backend:  https://api.example.com
→ SameSite=Lax works for subdomains
```

#### SameSite=Strict

```
SameSite=Strict
```

**Behavior:** Cookie NEVER sent on cross-site requests
**Use case:** Production same-domain apps
**Security:** Maximum protection against CSRF

**Example:**
```
Frontend: https://example.com
Backend:  https://example.com/wp-json
→ SameSite=Strict for maximum security
```

**Configuration:**
- Auto-detected based on environment (see below)
- Override with constant `JWT_AUTH_COOKIE_SAMESITE` or filter `jwt_auth_cookie_samesite`

---

### Path

```
Path=/
```

**What it does:**
Cookie is only sent to URLs matching this path.

**Examples:**
- `Path=/` - Sent to all URLs on the domain
- `Path=/wp-json/jwt/v1/` - Only sent to JWT endpoints

**Security benefit:**
- Restricts cookie exposure to specific endpoints
- Reduces attack surface

**Auto-detection:**
- **Development:** `/` (broad access)
- **Staging/Production:** `/wp-json/jwt/v1/` (restricted)

**Configuration:**
- Override with constant `JWT_AUTH_COOKIE_PATH` or filter `jwt_auth_cookie_path`

---

### Domain

```
Domain=
```

**What it does:**
Specifies which domain(s) can access the cookie.

**Examples:**
- `Domain=` (empty) - Only exact domain (e.g., `api.example.com`)
- `Domain=.example.com` - All subdomains (e.g., `api.example.com`, `app.example.com`)

**Use case:**
Share cookies across subdomains (e.g., `app.example.com` ↔ `api.example.com`)

**Security consideration:**
- Broader domain = more attack surface
- Only use when necessary

**Auto-detection:**
- Empty by default (current domain only)

**Configuration:**
- Override with constant `JWT_AUTH_COOKIE_DOMAIN` or filter `jwt_auth_cookie_domain`

---

### Max-Age

```
Max-Age=2592000
```

**What it does:**
Cookie lifetime in seconds (2,592,000 seconds = 30 days).

**What happens:**
After 30 days, browser automatically deletes the cookie.

**Configuration:**
- Controlled by: **Settings → JWT Auth Pro → JWT Settings → Refresh Token Expiry**

---

## Environment Auto-Detection

### How Environments Are Detected

The plugin uses `JWT_Cookie_Config::detect_environment()` to determine your environment:

#### Development Indicators

Environment = **"development"** if **any** of these match:
- Domain is `localhost`, `127.0.0.1`, or `::1`
- Domain ends with `.local` (e.g., `wordpress.local`)
- Domain ends with `.test` (e.g., `wordpress.test`)
- Domain ends with `.localhost` (e.g., `wordpress.localhost`)
- `WP_DEBUG` constant is `true`

#### Staging Indicators

Environment = **"staging"** if domain contains:
- `staging` (e.g., `staging.example.com`)
- `dev` (e.g., `dev.example.com`)
- `test` (e.g., `test.example.com`)

#### Production

Environment = **"production"** if none of the above match.

### Environment-Specific Defaults

```php
Development (localhost, .local, .test):
  SameSite: None    // Allow cross-domain (SPA on different port)
  Secure: is_ssl()  // true if HTTPS, false if HTTP
  Path: /           // Broad path for debugging
  Domain: ""        // Current domain only

Staging (dev.example.com):
  SameSite: Lax     // Relaxed for testing
  Secure: true      // Always require HTTPS
  Path: /wp-json/jwt/v1/  // Restricted path
  Domain: ""        // Current domain only

Production (example.com):
  SameSite: Strict  // Maximum security
  Secure: true      // Always require HTTPS
  Path: /wp-json/jwt/v1/  // Restricted path
  Domain: ""        // Current domain only
```

### Overriding Environment Detection

You can force a specific environment using the `wp_get_environment_type()` function (WordPress 5.5+):

**In wp-config.php:**
```php
define('WP_ENVIRONMENT_TYPE', 'development');
// Options: 'local', 'development', 'staging', 'production'
```

---

## Configuration Scenarios

### Scenario 1: Local Development (Cross-Domain)

**Setup:**
```
Frontend: http://localhost:5174 (React/Vue/Angular)
Backend:  https://wordpress.local (LocalWP/Valet/MAMP)
```

**Challenge:**
- Different domains (localhost vs wordpress.local)
- Frontend on HTTP, backend on HTTPS
- Need cross-domain cookies

**WordPress Settings:**

```
Cookie Settings:
  SameSite: None (Cross-site allowed)
  Secure: Enabled (HTTPS required) ⚠️
  Path: / (or auto)
  Domain: auto

General Settings:
  CORS Allowed Origins:
    http://localhost:5174
```

**Why Secure=Enabled with HTTP frontend?**

The cookie is:
- **Set by** your HTTPS backend (`https://wordpress.local`)
- **Sent to** your HTTPS backend
- `Secure` means "only send over HTTPS **to the backend**"

Modern browsers **require** `Secure=true` when `SameSite=None`.

**Frontend Configuration:**

```javascript
// Login
await fetch('https://wordpress.local/wp-json/jwt/v1/token', {
  method: 'POST',
  credentials: 'include',  // ✅ REQUIRED
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username, password })
});

// Refresh
await fetch('https://wordpress.local/wp-json/jwt/v1/refresh', {
  method: 'POST',
  credentials: 'include'  // ✅ REQUIRED
});
```

**Verify:**
Open DevTools → Application → Cookies → `https://wordpress.local` → Look for `wp_jwt_refresh_token`

---

### Scenario 2: Local Development (Same Domain via Proxy)

**Setup:**
```
Frontend: http://localhost:5174
Backend:  http://localhost:5174/api (proxied to WordPress)
```

**Vite Configuration:**

```javascript
// vite.config.js
export default {
  server: {
    proxy: {
      '/wp-json': {
        target: 'https://wordpress.local',
        changeOrigin: true,
        secure: false
      }
    }
  }
}
```

**WordPress Settings:**

```
Cookie Settings:
  SameSite: Lax (same-origin, no cross-site issues)
  Secure: Auto (will use HTTP)
  Path: /
  Domain: auto

General Settings:
  CORS Allowed Origins:
    (empty - not needed, same-origin)
```

**Frontend Configuration:**

```javascript
// All requests go through proxy (same-origin)
await fetch('/wp-json/jwt/v1/token', {
  method: 'POST',
  credentials: 'include',
  body: JSON.stringify({ username, password })
});
```

**Advantages:**
- No CORS issues (same-origin)
- Can use `SameSite=Lax` or `Strict`
- Mimics production behavior

---

### Scenario 3: Production (Cross-Domain)

**Setup:**
```
Frontend: https://app.example.com
Backend:  https://api.example.com
```

**WordPress Settings:**

```
Cookie Settings:
  SameSite: None (Cross-site allowed)
  Secure: Enabled (HTTPS required)
  Path: /wp-json/jwt/v1/
  Domain: auto

General Settings:
  CORS Allowed Origins:
    https://app.example.com
```

**Why SameSite=None in production?**
- Frontend and backend are on **different domains**
- Browser treats them as cross-site
- `SameSite=None` is required for cross-domain cookies

**Frontend Configuration:**

```javascript
const API_BASE = 'https://api.example.com';

await fetch(`${API_BASE}/wp-json/jwt/v1/token`, {
  method: 'POST',
  credentials: 'include',
  body: JSON.stringify({ username, password })
});
```

---

### Scenario 4: Production (Same Domain)

**Setup:**
```
Frontend: https://example.com
Backend:  https://example.com/wp-json (WordPress)
```

**WordPress Settings:**

```
Cookie Settings:
  SameSite: Strict (Maximum security)
  Secure: Enabled (HTTPS required)
  Path: /wp-json/jwt/v1/
  Domain: auto

General Settings:
  CORS Allowed Origins:
    (empty - not needed, same-origin)
```

**Frontend Configuration:**

```javascript
// Same-origin requests
await fetch('/wp-json/jwt/v1/token', {
  method: 'POST',
  credentials: 'include',
  body: JSON.stringify({ username, password })
});
```

**Advantages:**
- Maximum security (`SameSite=Strict`)
- No CORS configuration needed
- Cookies automatically work

---

### Scenario 5: Production (Subdomains)

**Setup:**
```
Frontend: https://app.example.com
Backend:  https://api.example.com
```

**Goal:** Share cookies across subdomains

**WordPress Settings:**

```
Cookie Settings:
  SameSite: Lax (same-site, different subdomains)
  Secure: Enabled (HTTPS required)
  Path: /
  Domain: .example.com  ⚠️ Note the leading dot

General Settings:
  CORS Allowed Origins:
    https://app.example.com
```

**Why Domain=.example.com?**
- Leading dot makes cookie available to all subdomains
- `api.example.com` and `app.example.com` can both access it

**Security consideration:**
- All subdomains can access the cookie
- Only use if you control all subdomains

---

## Troubleshooting

### Issue 1: Cookie Not Being Set

**Symptoms:**
- Login returns 200 OK
- No cookie appears in DevTools → Application → Cookies

**Check 1: Browser Console Warnings**

Look for:
```
Cookie "wp_jwt_refresh_token" has been rejected because it is in a
cross-site context and its "SameSite" is "None" and "Secure" is false.
```

**Solution:** Enable Secure attribute via constant or filter
```php
// wp-config.php
define('JWT_AUTH_COOKIE_SECURE', true);

// Or via filter
add_filter('jwt_auth_cookie_secure', '__return_true');
```

**Check 2: CORS Headers**

Open Network tab → Click `/token` request → Check response headers:
```
✅ Access-Control-Allow-Origin: http://localhost:5174
✅ Access-Control-Allow-Credentials: true
✅ Set-Cookie: wp_jwt_refresh_token=...
```

**Solution:** Add origin to CORS allowed origins
```
Settings → JWT Auth Pro → General Settings → CORS Allowed Origins
Add: http://localhost:5174
```

**Check 3: Credentials in Request**

Verify fetch/axios includes credentials:
```javascript
// ❌ Wrong
fetch(url)

// ✅ Correct
fetch(url, { credentials: 'include' })
```

---

### Issue 2: Cookie Not Being Sent on Refresh

**Symptoms:**
- Cookie exists in DevTools
- `/refresh` returns 401 "Refresh token not found"

**Check 1: Cookie Domain**

Open DevTools → Application → Cookies → Check "Domain" column

```
If cookie domain: api.example.com
And you request:   backend.example.com
→ Cookie won't be sent (domain mismatch)
```

**Solution:** Use matching domain or set Domain to `.example.com`

**Check 2: Cookie Path**

Check "Path" column in cookies:

```
If cookie path: /wp-json/jwt/v1/
And you request:  /wp-json/v2/users
→ Cookie won't be sent (path mismatch)
```

**Solution:** Change path to `/` or request matching path

**Check 3: Credentials in Request**

```javascript
// ❌ Wrong - cookie won't be sent
fetch('https://api.example.com/wp-json/jwt/v1/refresh', {
  method: 'POST'
})

// ✅ Correct
fetch('https://api.example.com/wp-json/jwt/v1/refresh', {
  method: 'POST',
  credentials: 'include'
})
```

---

### Issue 3: CORS Errors

**Symptoms:**
```
Access to fetch at 'https://api.example.com/wp-json/jwt/v1/token'
from origin 'http://localhost:5174' has been blocked by CORS policy
```

**Solution 1: Add Origin to Allowed List**

```
Settings → JWT Auth Pro → General Settings → CORS Allowed Origins
Add: http://localhost:5174
```

**Solution 2: Verify Exact Match**

Origins must **exactly** match (protocol + domain + port):

```
❌ http://localhost → http://localhost:5174
❌ http://localhost:5174/ → http://localhost:5174
✅ http://localhost:5174 → http://localhost:5174
```

**Solution 3: Check for Wildcards**

```
❌ Cannot use * with credentials:
    Access-Control-Allow-Origin: *
    Access-Control-Allow-Credentials: true

✅ Must specify exact origin:
    Access-Control-Allow-Origin: http://localhost:5174
    Access-Control-Allow-Credentials: true
```

---

### Issue 4: Works in Postman but Not Browser

**Cause:** API clients (Postman/Insomnia) don't enforce browser security policies.

**What to check:**
1. ✅ CORS headers present in browser Network tab
2. ✅ `credentials: 'include'` in fetch/axios
3. ✅ `SameSite=None` requires `Secure=true`
4. ✅ Cookies visible in DevTools → Application → Cookies

---

### Issue 5: Cookie Disappears After Page Refresh

**Symptoms:**
- Cookie present immediately after login
- Cookie missing after page refresh

**Check 1: Cookie Expiration**

Open DevTools → Application → Cookies → Check "Expires / Max-Age" column

```
❌ Expires: Session (deleted on browser close)
✅ Expires: [Future date]
```

**Solution:** Check refresh token expiry setting
```
Settings → JWT Auth Pro → JWT Settings → Refresh Token Expiry
Default: 2592000 seconds (30 days)
```

**Check 2: Cookie Secure Mismatch**

If cookie has `Secure=true` but you reload via HTTP:
```
Cookie set:    https://wordpress.local (Secure=true)
Page reloaded: http://wordpress.local
→ Cookie won't be sent (protocol mismatch)
```

**Solution:** Always use HTTPS for backend

---

### Issue 6: Different Behavior in Chrome vs Firefox

**Cause:** Browsers have different default behaviors for `SameSite`.

**Solution:** Always set SameSite explicitly (don't rely on defaults)

```
Chrome default: SameSite=Lax
Firefox default: SameSite=None (but changing)

→ Set explicitly in WordPress settings to ensure consistency
```

---

### Debugging Checklist

Use this checklist to debug cookie issues:

1. ✅ **Login Request**
   - Network tab → `/token` request
   - Status: 200 OK
   - Response Headers: `Set-Cookie: wp_jwt_refresh_token=...`

2. ✅ **CORS Headers**
   - `Access-Control-Allow-Origin: [your-origin]` (not `*`)
   - `Access-Control-Allow-Credentials: true`

3. ✅ **Cookie Stored**
   - DevTools → Application → Cookies
   - Look under backend domain (e.g., `https://wordpress.local`)
   - Cookie name: `wp_jwt_refresh_token`

4. ✅ **Cookie Attributes**
   - HttpOnly: ✓
   - Secure: ✓ (if using HTTPS)
   - SameSite: `None` (cross-domain) or `Lax`/`Strict` (same-domain)
   - Path: `/` or `/wp-json/jwt/v1/`
   - Expires: Future date (not "Session")

5. ✅ **Refresh Request**
   - Network tab → `/refresh` request
   - Request Headers: `Cookie: wp_jwt_refresh_token=...`
   - Status: 200 OK (not 401)

6. ✅ **Frontend Configuration**
   - `credentials: 'include'` on all JWT endpoints
   - Correct backend URL
   - No trailing slashes

7. ✅ **Browser Console**
   - No cookie warnings
   - No CORS errors

---

## Quick Reference

### Local Development Settings

```
Scenario: React on http://localhost:5174, WordPress on https://wordpress.local

WordPress Settings:
  SameSite: None
  Secure: Enabled (HTTPS required)
  Path: /
  Domain: auto
  CORS: http://localhost:5174

Frontend:
  credentials: 'include'
```

### Production Settings (Cross-Domain)

```
Scenario: Frontend on https://app.example.com, Backend on https://api.example.com

WordPress Settings:
  SameSite: None
  Secure: Enabled
  Path: /wp-json/jwt/v1/
  Domain: auto
  CORS: https://app.example.com

Frontend:
  credentials: 'include'
```

### Production Settings (Same-Domain)

```
Scenario: Frontend and Backend on https://example.com

WordPress Settings:
  SameSite: Strict
  Secure: Enabled
  Path: /wp-json/jwt/v1/
  Domain: auto
  CORS: (empty)

Frontend:
  credentials: 'include'
```

---

## Additional Resources

- [MDN: Using HTTP Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies)
- [MDN: SameSite Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite)
- [Chrome SameSite Changes](https://www.chromium.org/updates/same-site)
- [CORS Documentation](cors-and-cookies.md)

---

**Need help?** Check the plugin's Help tab: **Settings → JWT Auth Pro → Help & Documentation**