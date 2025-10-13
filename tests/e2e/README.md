# JWT Authentication E2E Tests

Complete end-to-end testing framework for JWT authentication with HTTPOnly cookies across multiple domains and protocols.

## 🎯 Overview

This test suite validates JWT authentication in various scenarios:

- ✅ **Multi-domain testing**: Same domain, cross-origin, subdomains
- ✅ **Protocol testing**: HTTP, HTTPS, and mixed combinations
- ✅ **HTTPOnly cookies**: Security validation
- ✅ **CORS headers**: Cross-origin request handling
- ✅ **Cookie attributes**: SameSite, Secure, Domain, Path
- ✅ **Token lifecycle**: Login, refresh, verify, logout

## 📁 Structure

```
tests/e2e/
├── docker/                      # Docker infrastructure
│   ├── docker-compose.yml       # Services definition
│   └── traefik/                 # Traefik configuration
│       ├── traefik.yml          # Traefik config
│       └── acme/                # SSL certificates
├── client/                      # Test client application
│   ├── index.html               # UI
│   ├── app.js                   # Logic
│   ├── config.js                # Scenarios
│   ├── styles.css               # Styling
│   └── Dockerfile               # Client container
├── tests/                       # Playwright tests
│   ├── test-helpers.ts          # Common test utilities
│   ├── same-domain.spec.ts      # Same domain tests
│   ├── cross-origin.spec.ts     # Cross-origin tests
│   └── protocol-matrix.spec.ts  # Protocol combination tests
├── workflows/                   # GitHub Actions
│   └── e2e-tests.yml            # CI workflow
├── playwright.config.ts         # Playwright config
├── package.json                 # Dependencies
└── README.md                    # This file
```

## 🚀 Quick Start (Local)

### Prerequisites

- Docker & Docker Compose
- Node.js 18+
- npm

### 1. Install Dependencies

```bash
cd tests/e2e
npm install
npx playwright install --with-deps chromium
```

### 2. Start Docker Stack

```bash
cd docker
docker compose up -d
```

Wait ~30 seconds for services to start.

### 3. Verify Services

```bash
# Check Traefik dashboard
open http://traefik.localtest.me:8080

# Check WordPress is running
curl -k https://wordpress.localtest.me/wp-json/

# Check test client
open https://client.localtest.me
```

### 4. Setup WordPress

```bash
# Install WordPress main instance
docker compose exec wordpress wp core install \
  --url=https://wordpress.localtest.me \
  --title="JWT Tests" \
  --admin_user=admin \
  --admin_password=password \
  --admin_email=admin@example.com \
  --skip-email \
  --allow-root

# Activate plugin
docker compose exec wordpress wp plugin activate jwt-auth-pro-wp-rest-api --allow-root

# Configure CORS
docker compose exec wordpress wp option update jwt_auth_pro_general_settings \
  --format=json \
  '{"samesite":"auto","secure":"auto","auto_detect":true,"allowed_origins":"https://client.localtest.me,http://client.localtest.me,https://app.localtest.me"}' \
  --allow-root

# Setup API instance (optional, for subdomain tests)
docker compose exec wordpress-api wp core install \
  --url=https://api.localtest.me \
  --title="JWT Tests API" \
  --admin_user=admin \
  --admin_password=password \
  --admin_email=admin@example.com \
  --skip-email \
  --allow-root

docker compose exec wordpress-api wp plugin activate jwt-auth-pro-wp-rest-api --allow-root

docker compose exec wordpress-api wp option update jwt_auth_pro_general_settings \
  --format=json \
  '{"samesite":"auto","secure":"auto","auto_detect":true,"allowed_origins":"https://app.localtest.me,http://app.localtest.me"}' \
  --allow-root
```

### 5. Run Tests

```bash
# Run all tests
npm test

# Run specific suite
npm run test:same-domain
npm run test:cross-origin
npm run test:protocol-matrix

# Run with UI
npm run test:ui

# Debug mode
npm run test:debug

# View report
npm run report
```

## 🧪 Test Scenarios

### Domain Scenarios

| Scenario | Client Domain | API Domain | SameSite | Secure | Description |
|----------|---------------|------------|----------|--------|-------------|
| Same Domain (HTTP) | wordpress.localtest.me | wordpress.localtest.me | Lax | false | Client and API on same domain |
| Same Domain (HTTPS) | wordpress.localtest.me | wordpress.localtest.me | Lax | true | HTTPS version |
| Cross-Origin (HTTP) | client.localtest.me | wordpress.localtest.me | None | false | Different domains |
| Cross-Origin (HTTPS) | client.localtest.me | wordpress.localtest.me | None | true | HTTPS cross-origin |
| Subdomain (HTTPS) | app.localtest.me | api.localtest.me | None | true | Different subdomains |

### Protocol Matrix

| Client Protocol | API Protocol | Expected Result |
|----------------|--------------|-----------------|
| HTTP | HTTP | ⚠️ Works but insecure |
| HTTPS | HTTPS | ✅ Recommended |
| HTTPS | HTTP | ❌ Mixed content blocked |
| HTTP | HTTPS | ✅ Works |

## 🌐 Available URLs

After starting Docker:

| Service | HTTP URL | HTTPS URL | Purpose |
|---------|----------|-----------|---------|
| WordPress | http://wordpress.localtest.me | https://wordpress.localtest.me | Main API |
| API Instance | http://api.localtest.me | https://api.localtest.me | Alt API |
| Test Client | http://client.localtest.me | https://client.localtest.me | Test UI |
| Alt Client | http://app.localtest.me | https://app.localtest.me | Alt UI |
| Traefik | http://traefik.localtest.me:8080 | - | Dashboard |

## 📋 Test Commands

```bash
# Run all tests
npm test

# Specific suites
npm run test:same-domain      # Same domain scenarios
npm run test:cross-origin     # Cross-origin scenarios
npm run test:protocol-matrix  # Protocol combinations

# Specific browsers
npm run test:firefox          # Firefox only
npm run test:webkit           # Safari/WebKit only
npm run test:chrome-only      # Chrome only

# Development
npm run test:headed           # Show browser
npm run test:ui               # Interactive UI mode
npm run test:debug            # Debug mode

# Reports
npm run report                # View HTML report
```

## 🐛 Troubleshooting

### Services won't start

```bash
# Check Docker
docker ps

# View logs
cd docker
docker compose logs -f

# Restart services
docker compose restart

# Full reset
docker compose down -v
docker compose up -d
```

### SSL Certificate Issues

Traefik uses Let's Encrypt staging for local development. Browsers will show certificate warnings - this is expected. Tests use `ignoreHTTPSErrors: true`.

For production CI, configure real Let's Encrypt:

```yaml
# In traefik.yml
certificatesResolvers:
  letsencrypt:
    acme:
      caServer: https://acme-v02.api.letsencrypt.org/directory  # Production
```

### Domain Resolution

`*.localtest.me` domains automatically resolve to `127.0.0.1`. No `/etc/hosts` configuration needed!

If domains don't resolve:

```bash
# Test DNS
nslookup client.localtest.me

# Should return 127.0.0.1
```

### WordPress not accessible

```bash
# Check WordPress status
docker compose exec wordpress wp --info --allow-root

# Check plugin status
docker compose exec wordpress wp plugin list --allow-root

# Check CORS configuration
docker compose exec wordpress wp option get jwt_auth_pro_general_settings --format=json --allow-root
```

### Tests fail with CORS errors

1. Verify CORS origins are configured:
   ```bash
   docker compose exec wordpress wp option get jwt_auth_pro_general_settings --format=json --allow-root
   ```

2. Check response headers:
   ```bash
   curl -X OPTIONS -H "Origin: https://client.localtest.me" \
     -H "Access-Control-Request-Method: POST" \
     -k https://wordpress.localtest.me/wp-json/jwt/v1/token -v
   ```

3. Update CORS configuration if needed (see Setup WordPress step above)

### Port conflicts

If ports 80, 443, or 8080 are in use:

```bash
# Check what's using the port
lsof -i :80
lsof -i :443
lsof -i :8080

# Stop conflicting services or modify docker-compose.yml ports
```

## 🤖 GitHub Actions (CI)

Tests run automatically on:
- Push to `main`, `develop`, `feature/**`
- Pull requests
- Manual workflow dispatch

### CI Configuration

The workflow is located at `.github/workflows/e2e-tests.yml` (copy from `tests/e2e/workflows/e2e-tests.yml`).

### Viewing CI Results

1. Go to **Actions** tab in GitHub
2. Select latest workflow run
3. View test results by suite
4. Download artifacts for detailed reports

### CI Features

- ✅ Parallel test execution (3 suites)
- ✅ Automatic WordPress setup
- ✅ HTML reports as artifacts
- ✅ PR comments with results
- ✅ Failure screenshots
- ✅ Full Docker logs on failure

## 📊 Test Reports

After running tests locally:

```bash
# View HTML report
npm run report

# Report location
open playwright-report/index.html

# Test results
open test-results/
```

Reports include:
- Test execution timeline
- Screenshots (on failure)
- Videos (on failure)
- Trace files (on retry)
- Network activity logs

## 🔧 Advanced Configuration

### Custom Scenarios

Edit `client/config.js` to add new test scenarios:

```javascript
'my-scenario': {
  name: 'My Custom Scenario',
  apiUrl: 'https://my-api.localtest.me/wp-json/jwt/v1',
  clientUrl: 'https://my-client.localtest.me',
  expectedSameSite: 'None',
  expectedSecure: true,
  description: 'Custom scenario description'
}
```

### Add New Domains

1. Update `docker-compose.yml`:
```yaml
labels:
  - "traefik.http.routers.my-service.rule=Host(`my-domain.localtest.me`)"
```

2. Update Playwright `baseURL` in `playwright.config.ts`

3. Create new test project if needed

### Modify WordPress

Mount custom plugins or themes:

```yaml
volumes:
  - ../../../:/var/www/html/wp-content/plugins/jwt-auth-pro-wp-rest-api
  - ./my-theme:/var/www/html/wp-content/themes/my-theme
```

## 🎯 What Gets Tested

### For Each Scenario

1. **Login Flow**
   - POST to `/jwt/v1/token`
   - Verify access token in response
   - Verify `wp_jwt_refresh_token` cookie set with HttpOnly

2. **Cookie Security**
   - `HttpOnly` = true (not accessible via JS)
   - `SameSite` = correct value for scenario
   - `Secure` = true for HTTPS
   - Cookie domain and path

3. **CORS Headers**
   - `Access-Control-Allow-Origin` matches request origin
   - `Access-Control-Allow-Credentials` = true
   - Preflight (OPTIONS) request works
   - Actual request sends credentials

4. **Token Refresh**
   - POST to `/jwt/v1/token/refresh`
   - Cookie sent automatically by browser
   - New access token received

5. **Token Verification**
   - POST to `/jwt/v1/token/verify`
   - Authorization header validated

6. **Logout**
   - POST to `/jwt/v1/logout`
   - Cookie cleared
   - Subsequent requests fail

## 📚 Additional Resources

- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [JWT Specification](https://jwt.io/)
- [Playwright Documentation](https://playwright.dev/)
- [Traefik Documentation](https://doc.traefik.io/)
- [Cookie Security](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies)
- [CORS Guide](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)

## 🤝 Contributing

When adding new tests:

1. Create test file in `tests/` directory
2. Add corresponding project in `playwright.config.ts`
3. Update documentation
4. Ensure tests pass locally before committing

## 📝 Notes

- Tests use staging Let's Encrypt certificates
- `localtest.me` domains automatically resolve to localhost
- Self-signed certificate warnings are expected and ignored in tests
- Tests are designed to run in isolation
- Each test suite can run independently

---

**Ready to test!** 🚀

Start with: `cd tests/e2e && npm install && cd docker && docker compose up -d`
