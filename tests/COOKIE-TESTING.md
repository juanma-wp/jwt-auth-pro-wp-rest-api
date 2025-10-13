# Cookie Scenario Testing

This directory contains comprehensive end-to-end tests for validating JWT authentication with HTTPOnly cookies across different cross-origin scenarios.

## Overview

The test suite validates:
- ✅ **HTTPOnly cookie** security (cookies not accessible via JavaScript)
- ✅ **CORS headers** for cross-origin requests
- ✅ **SameSite attributes** (Strict, Lax, None)
- ✅ **Secure flag** behavior with HTTP/HTTPS
- ✅ **Token refresh flow** with automatic cookie transmission
- ✅ **Different domain scenarios** (same-domain, cross-origin, subdomain)

## Test Matrix

### Cookie Configurations

| Configuration | SameSite | Secure | Use Case |
|--------------|----------|--------|----------|
| **Strict Production** | Strict | true | Maximum security, same-domain only |
| **Lax Staging** | Lax | true | Relaxed, top-level navigation allowed |
| **None Cross-Origin** | None | true | Cross-origin support with HTTPS |
| **Auto-detect** | auto | auto | Intelligent defaults (recommended) |
| **Localhost Dev** | None | false | Local development only |

### Domain Scenarios

| Scenario | React App | WordPress API | Works With |
|----------|-----------|---------------|------------|
| **Same Domain** | http://localhost:5173 | http://localhost:5173/wp-json | Lax, Strict |
| **Cross-Origin** | http://localhost:5173 | http://api.test | None + Secure |
| **Subdomain** | http://app.test | http://api.app.test | None + Secure |
| **HTTPS** | https://app.test | https://api.test | All configs |

## Quick Start

### Local Testing

1. **Install dependencies**
   ```bash
   npm install
   npx playwright install --with-deps
   ```

2. **Start your WordPress instance**
   ```bash
   # Make sure WordPress is running at http://rest-api-tests.wp.local
   ```

3. **Configure a test scenario**
   ```bash
   # Option 1: Interactive menu
   ./tests/scripts/setup-local.sh

   # Option 2: Direct configuration
   node tests/scripts/configure-scenario.js auto-detect
   ```

4. **Run tests**
   ```bash
   # Run all scenarios
   npm run test:e2e

   # Run specific scenario
   npm run test:e2e:same-domain
   npm run test:e2e:cross-origin
   npm run test:e2e:auto-detect

   # Run with UI (visual test runner)
   npm run test:e2e:ui

   # Debug mode
   npm run test:e2e:debug
   ```

### Docker Testing (CI/Local)

1. **Build and run tests in Docker**
   ```bash
   # Build containers
   npm run docker:test:build

   # Run all tests
   npm run docker:test

   # Clean up
   npm run docker:test:down
   ```

2. **Run specific scenario in Docker**
   ```bash
   docker compose -f tests/docker/docker-compose.test.yml up -d
   docker compose -f tests/docker/docker-compose.test.yml exec playwright npm run test:e2e:same-domain
   ```

## Test Scenarios Explained

### 1. Same Domain (Lax)
**Use case**: React app served from same domain as WordPress

```
React:     http://localhost:5173
WordPress: http://localhost:5173/wp-json
Cookie:    SameSite=Lax, Secure=auto
```

**Expected**: ✅ Works perfectly. Cookies sent automatically.

### 2. Cross-Origin (None + Secure)
**Use case**: React app on different domain than WordPress

```
React:     http://localhost:5173
WordPress: http://rest-api-tests.wp.local
Cookie:    SameSite=None, Secure=true
CORS:      Required
```

**Expected**: ✅ Works with proper CORS and HTTPS.

### 3. Auto-detect (Recommended)
**Use case**: Plugin detects environment and configures automatically

```
Same-origin:  SameSite=Lax
Cross-origin: SameSite=None (with Secure on HTTPS)
```

**Expected**: ✅ Adapts to request context automatically.

### 4. Localhost Development
**Use case**: Local development without HTTPS

```
Cookie: SameSite=None, Secure=false
Note:   Browsers allow this ONLY on localhost
```

**Expected**: ✅ Works on localhost, ❌ fails elsewhere.

## Configuration Scripts

### Setup Script (Bash)
Interactive menu for local configuration:
```bash
./tests/scripts/setup-local.sh
```

Options:
1. Same Domain (Lax)
2. Cross-Origin (None + Secure)
3. Auto-detect (Default)
4. Localhost Development
5. Generate SSL certificates only
6. Reset to defaults

### Configuration Script (Node)
Programmatic configuration via WordPress API:
```bash
node tests/scripts/configure-scenario.js <scenario>
```

Available scenarios:
- `same-domain`
- `cross-origin`
- `auto-detect`
- `localhost-dev`
- `strict-production`

## Test Structure

```
tests/
├── e2e/
│   ├── cookie-scenarios.spec.js      # Main test suite
│   ├── test-matrix.config.js         # Test configuration matrix
│   ├── helpers/
│   │   └── network-inspector.js      # Network/cookie inspection helper
│   ├── global-setup.js               # Global test setup
│   └── global-teardown.js            # Global test teardown
├── docker/
│   ├── docker-compose.test.yml       # Docker test environment
│   ├── Dockerfile.react              # React app container
│   ├── Dockerfile.playwright         # Playwright test runner
│   ├── nginx.conf                    # HTTPS proxy config
│   └── init-db.sql                   # Database initialization
├── scripts/
│   ├── setup-local.sh                # Local setup (bash)
│   └── configure-scenario.js         # Configuration (node)
└── COOKIE-TESTING.md                  # This file
```

## GitHub Actions

Tests run automatically on:
- Push to `main`, `develop`, `feature/cookie-scenario-testing`
- Pull requests to `main`, `develop`
- Manual workflow dispatch

View workflow: `.github/workflows/cookie-scenarios.yml`

## Debugging

### View test results
```bash
npm run test:e2e:report
```

### Debug a specific test
```bash
npm run test:e2e:debug -- --grep "Same Domain"
```

### Check WordPress configuration
```bash
cd /Users/juanmanuelgarrido/STUDIO/rest-api-tests
wp option get jwt_auth_cookie_config --format=json
wp option get jwt_auth_pro_general_settings --format=json
```

### View debug logs
```bash
tail -f /Users/juanmanuelgarrido/STUDIO/rest-api-tests/wp-content/debug.log
```

## Common Issues

### Cookie not set
**Problem**: `refresh_token` cookie not appearing in browser

**Solutions**:
1. Check HTTPS requirement: `SameSite=None` requires `Secure=true` (HTTPS)
2. Verify CORS headers include `Access-Control-Allow-Credentials: true`
3. Ensure WordPress site is accessible

### Cookie not sent on refresh
**Problem**: Cookie exists but not sent with requests

**Solutions**:
1. Check `SameSite` attribute matches scenario:
   - Same-origin: `Lax` or `Strict`
   - Cross-origin: `None` (with HTTPS)
2. Verify CORS origin matches exactly
3. Check browser console for blocked cookie warnings

### CORS errors
**Problem**: `Cross-Origin Request Blocked` errors

**Solutions**:
1. Add React app URL to CORS origins in WordPress settings
2. Ensure `Access-Control-Allow-Credentials: true` header present
3. Verify origin matches exactly (no trailing slashes)

## Test Coverage

Current test coverage includes:

- ✅ HTTPOnly cookie setting
- ✅ Cookie attributes (SameSite, Secure, HttpOnly)
- ✅ Cookie transmission on requests
- ✅ CORS headers validation
- ✅ Preflight requests (OPTIONS)
- ✅ Token refresh flow
- ✅ Logout and cookie cleanup
- ✅ JavaScript access prevention
- ✅ Multiple domain scenarios
- ✅ HTTPS enforcement

## Resources

- [MDN: Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie)
- [MDN: SameSite cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite)
- [OWASP: Cross-Site Request Forgery Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [Playwright Documentation](https://playwright.dev)

## Contributing

When adding new tests:

1. Add test scenario to `test-matrix.config.js`
2. Implement test in `cookie-scenarios.spec.js`
3. Update this documentation with new scenario
4. Ensure tests pass both locally and in CI

## Support

For issues or questions:
1. Check debug logs
2. Review test output in `playwright-report/`
3. Open an issue on GitHub
