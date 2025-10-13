# Automated Cookie Scenario Testing

Complete automated testing for JWT authentication with HTTPOnly cookies - runs both **locally** and in **GitHub Actions**.

## 🎯 Overview

This test suite validates:
- ✅ HTTPOnly cookie security (not accessible via JavaScript)
- ✅ CORS headers for cross-origin requests
- ✅ SameSite attributes (Strict, Lax, None)
- ✅ Token refresh flow with automatic cookie transmission
- ✅ Cookie cleanup on logout

## 🚀 Run Tests Locally (1 Command)

```bash
npm run test:scenarios:local
```

**That's it!** The script will:
1. Check WordPress is running
2. Configure WordPress with test settings
3. Start the test app automatically
4. Run all Playwright tests
5. Generate HTML report
6. Clean up when done

### What You Need

- ✅ WordPress running at `https://rest-api-tests.wp.local`
- ✅ Plugin activated
- ✅ Node.js and npm installed

### Output

```
======================================
  JWT Cookie Scenario Tests (Local)
======================================

1. Checking WordPress accessibility...
✓ WordPress API accessible

2. Configuring WordPress for tests...
✓ WordPress configured

3. Starting test app...
✓ Test app running at http://localhost:5173

4. Running Playwright tests...

Running 8 tests using 1 worker

  ✓ [auto-detect] Auto Development Config › Same Domain - Login and Refresh
  ✓ [auto-detect] Auto Development Config › Cross-Origin - Login and Refresh
  ✓ [auto-detect] Security Checks › HTTPOnly prevents JavaScript access
  ✓ [auto-detect] Security Checks › Secure flag set for HTTPS
  ✓ [auto-detect] Security Checks › Cookie cleared on logout
  ✓ [auto-detect] CORS Headers › Preflight request has correct headers
  ✓ [auto-detect] CORS Headers › Actual request has credentials
  ✓ [auto-detect] Cookie Information › Check cookies

  8 passed (45s)

======================================
  ✓ All tests passed!
======================================
```

## 🤖 GitHub Actions (Automatic)

Tests run automatically on every:
- Push to `main`, `develop`, `feature/*`
- Pull request
- Manual workflow dispatch

### CI Test Matrix

Tests run for **4 scenarios** in parallel:

| Scenario | SameSite | Secure | Purpose |
|----------|----------|--------|---------|
| **auto-detect** | auto | auto | Default behavior (recommended) |
| **cross-origin** | None | true | React on different domain |
| **same-domain** | Lax | auto | React on same domain |
| **strict-production** | Strict | true | Maximum security |

### CI Workflow

```yaml
# .github/workflows/cookie-scenarios.yml

- Checkout code
- Install dependencies
- Start Docker (WordPress + Test App)
- Configure WordPress
- Run Playwright tests
- Upload reports
- Comment on PR
```

### View Results

1. **GitHub Actions tab** → Latest workflow run
2. **Artifacts** → Download HTML reports
3. **PR comments** → Test summary

## 📊 Test Reports

### Local Report

After tests complete:

```bash
npm run test:e2e:report
```

Opens HTML report with:
- ✅ Test results
- ✅ Screenshots (on failure)
- ✅ Test traces
- ✅ Network activity

### CI Artifacts

Download from GitHub Actions:
- `playwright-results-auto-detect/`
- `playwright-results-cross-origin/`
- `playwright-results-same-domain/`
- `playwright-results-strict-production/`

## 🧪 Manual Testing

Want to test manually first?

```bash
# Start test app
npm run test:app

# Open browser
open http://localhost:5173

# Test manually:
# 1. Click Login
# 2. Check Cookies (should show "not visible")
# 3. Refresh Token
# 4. Logout
```

## 🔧 Advanced Usage

### Run Specific Scenario

```bash
# Auto-detect only
npm run test:e2e:auto-detect

# Cross-origin only
npm run test:e2e:cross-origin

# With UI (interactive)
npm run test:e2e:ui

# Debug mode (pauses on failure)
npm run test:e2e:debug
```

### Configure Different Scenario

```bash
# Configure WordPress for specific scenario
node tests/scripts/configure-scenario.js cross-origin
node tests/scripts/configure-scenario.js same-domain
node tests/scripts/configure-scenario.js auto-detect
```

### Docker Testing

Run everything in Docker (no local WordPress needed):

```bash
# Start all containers and run tests
npm run docker:test

# Clean up
npm run docker:test:down
```

## 🐛 Troubleshooting

### Test Fails: WordPress Not Accessible

```bash
# Check WordPress is running
curl -k https://rest-api-tests.wp.local/wp-json/

# If not running, start it
cd /Users/juanmanuelgarrido/STUDIO/rest-api-tests
# (use your local WordPress start command)
```

### Test Fails: Cookie Not Set

```bash
# Check plugin is active
cd /Users/juanmanuelgarrido/STUDIO/rest-api-tests
wp plugin list | grep jwt

# Activate if needed
wp plugin activate jwt-auth-pro-wp-rest-api

# Check CORS configuration
wp option get jwt_auth_pro_general_settings --format=json
```

### Test Fails: Port 5173 In Use

```bash
# Kill existing server
lsof -ti:5173 | xargs kill -9

# Try again
npm run test:scenarios:local
```

### View Detailed Logs

```bash
# WordPress debug log
tail -f /Users/juanmanuelgarrido/STUDIO/rest-api-tests/wp-content/debug.log

# Test output with verbose
npm run test:e2e -- --reporter=line
```

## 📁 File Structure

```
tests/
├── scripts/
│   └── run-tests-local.sh          ← Main local test script
├── test-app/
│   ├── index.html                  ← Test client UI
│   ├── app.js                      ← Test client logic
│   └── package.json
├── e2e/
│   ├── cookie-scenarios.spec.js    ← Playwright tests
│   └── helpers/
└── README-AUTOMATED.md             ← This file
```

## 🎓 How It Works

### Local Testing

```
┌─────────────────────────────────────┐
│ npm run test:scenarios:local       │
└──────────┬──────────────────────────┘
           │
           ├─► Check WordPress running
           ├─► Configure WordPress (CORS, cookies)
           ├─► Start test app (http-server on :5173)
           ├─► Run Playwright tests
           ├─► Generate HTML report
           └─► Cleanup (kill test app)
```

### GitHub Actions

```
┌─────────────────────────────────────┐
│ Push to repo / Open PR             │
└──────────┬──────────────────────────┘
           │
           ├─► Start Docker containers
           │   ├─► WordPress
           │   ├─► Database
           │   └─► Test app
           ├─► Install WordPress
           ├─► Activate plugin
           ├─► Configure scenario
           ├─► Run Playwright tests (matrix)
           │   ├─► auto-detect
           │   ├─► cross-origin
           │   ├─► same-domain
           │   └─► strict-production
           ├─► Upload artifacts
           └─► Comment on PR
```

## ✅ What Gets Tested

For each scenario:

1. **Login Flow**
   - POST to `/jwt/v1/token`
   - Verify access token in response
   - Verify `refresh_token` cookie set with `HttpOnly`

2. **Cookie Attributes**
   - `HttpOnly` = true (not accessible via JS)
   - `SameSite` = correct value for scenario
   - `Secure` = true for HTTPS

3. **CORS Headers**
   - `Access-Control-Allow-Origin` matches request origin
   - `Access-Control-Allow-Credentials` = true
   - Preflight (OPTIONS) request works

4. **Token Refresh**
   - POST to `/jwt/v1/token/refresh`
   - Cookie sent automatically by browser
   - New access token received

5. **Logout**
   - POST to `/jwt/v1/logout`
   - Cookie cleared
   - Subsequent requests fail

## 🎯 Success Criteria

All tests pass when:
- ✅ Login returns access token + sets HTTPOnly cookie
- ✅ Cookie NOT visible in `document.cookie`
- ✅ Cookie IS visible in DevTools → Application → Cookies
- ✅ Cookie sent automatically on refresh
- ✅ CORS headers correct for cross-origin
- ✅ Cookie cleared on logout

## 📚 Related Documentation

- [Full Testing Guide](TESTING-GUIDE.md) - Detailed troubleshooting
- [Cookie Testing Doc](COOKIE-TESTING.md) - Cookie scenarios explained
- [Test App README](test-app/README.md) - Manual testing guide

## 🚀 Next Steps

1. **Run locally**: `npm run test:scenarios:local`
2. **Push to GitHub**: Tests run automatically
3. **View results**: Check GitHub Actions tab
4. **Debug failures**: Use testing guide

That's it! Automated cookie testing ready to go. 🎉
