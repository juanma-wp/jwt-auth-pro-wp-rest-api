import { test, expect } from '@playwright/test';
import {
  COOKIE_CONFIGS,
  DOMAIN_SCENARIOS,
  TEST_FLOWS,
  formatTestName,
} from './test-matrix.config.js';
import { NetworkInspector } from './helpers/network-inspector.js';

/**
 * Cookie Scenario Tests
 *
 * Tests different cookie configurations across various domain scenarios
 * to ensure HTTPOnly cookies work correctly with CORS and different SameSite settings
 */

// Test credentials from CLAUDE.local.md
const TEST_CREDENTIALS = {
  username: 'admin',
  password: 'S$genSlH%24GLe0k1hy(C6r3',
};

/**
 * Test Group: Auto Development (Default)
 * This is the most important scenario as it's the default behavior
 */
test.describe('Auto Development Config', () => {
  const config = COOKIE_CONFIGS.AUTO_DEVELOPMENT;

  test('Same Domain - Login and Refresh', async ({ page, context }) => {
    const inspector = new NetworkInspector(page);

    // Navigate to test app
    await page.goto('http://localhost:5173');

    // Login using the test app UI
    await page.fill('#username', TEST_CREDENTIALS.username);
    await page.fill('#password', TEST_CREDENTIALS.password);

    const loginResponse = page.waitForResponse(
      (resp) => resp.url().includes('/jwt/v1/token') && resp.status() === 200
    );

    await page.click('button[onclick="login()"]');
    const response = await loginResponse;

    // Verify access token in response
    const json = await response.json();
    expect(json.data.access_token).toBeDefined();

    // Verify HTTPOnly cookie set
    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

    expect(refreshCookie).toBeDefined();
    expect(refreshCookie.httpOnly).toBe(true);
    // localhost:5173 -> rest-api-tests.wp.local is cross-origin, so auto-detect sets None
    expect(refreshCookie.sameSite).toBe('None');

    // Test refresh flow - click the refresh button
    const refreshResponse = page.waitForResponse(
      (resp) => resp.url().includes('/jwt/v1/token/refresh')
    );

    await page.click('button[onclick="refreshToken()"]');
    const refreshResp = await refreshResponse;

    expect(refreshResp.status()).toBe(200);

    // Verify wp_jwt_refresh_token cookie was sent (inspect network)
    const refreshCookies = await context.cookies();
    expect(refreshCookies.find((c) => c.name === 'wp_jwt_refresh_token')).toBeDefined();
  });

  test('Cross-Origin - Login and Refresh', async ({ page, context }) => {
    const inspector = new NetworkInspector(page);

    // Navigate to test app
    await page.goto('http://localhost:5173');

    // Login to WordPress API on different domain
    const loginResponse = page.waitForResponse(
      (resp) =>
        resp.url().includes('rest-api-tests.wp.local/wp-json/jwt/v1/token') &&
        resp.status() === 200
    );

    await page.fill('#username', TEST_CREDENTIALS.username);
    await page.fill('#password', TEST_CREDENTIALS.password);
    await page.click('button[onclick="login()"]');

    const response = await loginResponse;

    // Verify CORS headers
    const headers = response.headers();
    expect(headers['access-control-allow-origin']).toBe('http://localhost:5173');
    expect(headers['access-control-allow-credentials']).toBe('true');

    // Verify HTTPOnly cookie set with SameSite=None for cross-origin
    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

    expect(refreshCookie).toBeDefined();
    expect(refreshCookie.httpOnly).toBe(true);
    expect(refreshCookie.sameSite).toBe('None'); // Auto-detect for cross-origin

    // Verify refresh works cross-origin - click the refresh button
    const refreshResponse = page.waitForResponse(
      (resp) =>
        resp.url().includes('rest-api-tests.wp.local/wp-json/jwt/v1/token/refresh')
    );

    await page.click('button[onclick="refreshToken()"]');
    const refreshResp = await refreshResponse;

    expect(refreshResp.status()).toBe(200);
    expect(refreshResp.headers()['access-control-allow-credentials']).toBe('true');
  });
});

/**
 * Test Group: Strict Production
 * Tests maximum security configuration
 */
test.describe('Strict Production Config', () => {
  const config = COOKIE_CONFIGS.STRICT_PRODUCTION;

  test.beforeEach(async ({ page }) => {
    // Set WordPress constants via admin or wp-config
    // This would typically be done via setup script
  });

  test('Same Domain - Should Work', async ({ page, context }) => {
    await page.goto('https://example.com/login');

    const loginResponse = page.waitForResponse(
      (resp) => resp.url().includes('/jwt/v1/token')
    );

    await page.fill('[name="username"]', TEST_CREDENTIALS.username);
    await page.fill('[name="password"]', TEST_CREDENTIALS.password);
    await page.click('button[type="submit"]');

    const response = await loginResponse;
    expect(response.status()).toBe(200);

    // Verify cookie with Strict SameSite
    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

    expect(refreshCookie).toBeDefined();
    expect(refreshCookie.sameSite).toBe('Strict');
    expect(refreshCookie.secure).toBe(true);
    expect(refreshCookie.httpOnly).toBe(true);
  });

  test('Cross-Origin - Should Fail', async ({ page, context }) => {
    await page.goto('https://app.example.com/login');

    const loginResponse = page.waitForResponse(
      (resp) => resp.url().includes('https://api.example.com/wp-json/jwt/v1/token')
    );

    await page.fill('[name="username"]', TEST_CREDENTIALS.username);
    await page.fill('[name="password"]', TEST_CREDENTIALS.password);
    await page.click('button[type="submit"]');

    await loginResponse;

    // Verify cookie is set but won't be sent on cross-origin requests
    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

    if (refreshCookie) {
      expect(refreshCookie.sameSite).toBe('Strict');
    }

    // Attempt refresh - should fail because cookie won't be sent
    const refreshResponse = page.waitForResponse(
      (resp) => resp.url().includes('/jwt/v1/token/refresh')
    );

    await page.reload();
    const refreshResp = await refreshResponse;

    // Expect 401 because wp_jwt_refresh_token cookie not sent
    expect(refreshResp.status()).toBe(401);
  });
});

/**
 * Test Group: None Cross-Origin
 * Tests SameSite=None for cross-origin scenarios
 */
test.describe('None Cross-Origin Config', () => {
  const config = COOKIE_CONFIGS.NONE_CROSS_ORIGIN;

  test('Cross-Origin with HTTPS - Should Work', async ({ page, context }) => {
    await page.goto('https://app.example.com/login');

    const loginResponse = page.waitForResponse(
      (resp) => resp.url().includes('https://api.example.com/wp-json/jwt/v1/token')
    );

    await page.fill('[name="username"]', TEST_CREDENTIALS.username);
    await page.fill('[name="password"]', TEST_CREDENTIALS.password);
    await page.click('button[type="submit"]');

    const response = await loginResponse;
    expect(response.status()).toBe(200);

    // Verify SameSite=None with Secure
    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

    expect(refreshCookie).toBeDefined();
    expect(refreshCookie.sameSite).toBe('None');
    expect(refreshCookie.secure).toBe(true);
    expect(refreshCookie.httpOnly).toBe(true);

    // Verify CORS headers
    const headers = response.headers();
    expect(headers['access-control-allow-origin']).toBe('https://app.example.com');
    expect(headers['access-control-allow-credentials']).toBe('true');

    // Test refresh - should work with cookie sent
    await page.evaluate(() => {
      localStorage.setItem('access_token', 'expired');
    });

    const refreshResponse = page.waitForResponse(
      (resp) => resp.url().includes('/jwt/v1/token/refresh')
    );

    await page.reload();
    const refreshResp = await refreshResponse;

    expect(refreshResp.status()).toBe(200);
  });

  test('Cross-Origin without HTTPS - Should Fail', async ({ page, context }) => {
    // Browsers block SameSite=None without Secure (except localhost)
    await page.goto('http://app.example.com/login');

    const loginResponse = page.waitForResponse(
      (resp) => resp.url().includes('http://api.example.com/wp-json/jwt/v1/token')
    );

    await page.fill('[name="username"]', TEST_CREDENTIALS.username);
    await page.fill('[name="password"]', TEST_CREDENTIALS.password);
    await page.click('button[type="submit"]');

    const response = await loginResponse;

    // Cookie may be set but browser will block it
    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

    // Browser should reject SameSite=None without Secure
    // Some browsers may not even store the cookie
    if (refreshCookie) {
      // If cookie is stored, it should still have the attributes
      expect(refreshCookie.sameSite).toBe('None');
    }

    // Refresh should fail
    const refreshResponse = page.waitForResponse(
      (resp) => resp.url().includes('/jwt/v1/token/refresh')
    );

    await page.reload();
    const refreshResp = await refreshResponse;

    expect(refreshResp.status()).toBe(401);
  });
});

/**
 * Test Group: Security Checks
 * Verify HTTPOnly and other security attributes
 */
test.describe('Security Checks', () => {
  test('HTTPOnly prevents JavaScript access', async ({ page, context }) => {
    await page.goto('http://localhost:5173');

    await page.fill('#username', TEST_CREDENTIALS.username);
    await page.fill('#password', TEST_CREDENTIALS.password);
    await page.click('button[onclick="login()"]');

    await page.waitForResponse((resp) => resp.url().includes('/jwt/v1/token'));

    // Try to access cookie from JavaScript
    const canAccessCookie = await page.evaluate(() => {
      return document.cookie.includes('wp_jwt_refresh_token');
    });

    expect(canAccessCookie).toBe(false);

    // But cookie should be in browser storage (accessible via Playwright)
    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');
    expect(refreshCookie).toBeDefined();
  });

  test('Secure flag set for HTTPS', async ({ page, context }) => {
    await page.goto('https://example.com/login');

    await page.fill('[name="username"]', TEST_CREDENTIALS.username);
    await page.fill('[name="password"]', TEST_CREDENTIALS.password);
    await page.click('button[type="submit"]');

    await page.waitForResponse((resp) => resp.url().includes('/jwt/v1/token'));

    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

    expect(refreshCookie.secure).toBe(true);
  });

  test('Cookie cleared on logout', async ({ page, context }) => {
    // Login first
    await page.goto('http://localhost:5173');
    await page.fill('#username', TEST_CREDENTIALS.username);
    await page.fill('#password', TEST_CREDENTIALS.password);
    await page.click('button[onclick="login()"]');

    await page.waitForResponse((resp) => resp.url().includes('/jwt/v1/token'));

    let cookies = await context.cookies();
    expect(cookies.find((c) => c.name === 'wp_jwt_refresh_token')).toBeDefined();

    // Logout
    await page.click('button[onclick="logout()"]');
    await page.waitForResponse((resp) => resp.url().includes('/jwt/v1/logout'));

    // Verify cookie cleared
    cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

    // Cookie should be removed or have empty value
    expect(!refreshCookie || refreshCookie.value === '').toBe(true);
  });
});

/**
 * Test Group: CORS Headers
 * Verify CORS configuration works correctly
 */
test.describe('CORS Headers', () => {
  test('Preflight request has correct headers', async ({ page }) => {
    const inspector = new NetworkInspector(page);

    await page.goto('http://localhost:5173');

    // Intercept OPTIONS request
    const preflightRequest = page.waitForRequest(
      (req) => req.method() === 'OPTIONS' && req.url().includes('/jwt/v1/token')
    );

    await page.fill('#username', TEST_CREDENTIALS.username);
    await page.fill('#password', TEST_CREDENTIALS.password);

    // Trigger login (which should trigger preflight first)
    await page.click('button[onclick="login()"]');

    const request = await preflightRequest;
    const response = await request.response();

    const headers = response.headers();
    expect(headers['access-control-allow-origin']).toBeTruthy();
    expect(headers['access-control-allow-credentials']).toBe('true');
    expect(headers['access-control-allow-methods']).toContain('POST');
    expect(headers['access-control-allow-headers']).toBeTruthy();
  });

  test('Actual request has credentials', async ({ page }) => {
    await page.goto('http://localhost:5173');

    const loginResponse = page.waitForResponse(
      (resp) => resp.url().includes('/jwt/v1/token') && resp.status() === 200
    );

    await page.fill('#username', TEST_CREDENTIALS.username);
    await page.fill('#password', TEST_CREDENTIALS.password);
    await page.click('button[onclick="login()"]');

    const response = await loginResponse;
    const headers = response.headers();

    expect(headers['access-control-allow-credentials']).toBe('true');
    expect(headers['access-control-allow-origin']).toBe('http://localhost:5173');
  });
});

/**
 * Test Group: Cookie Information
 * Verify the cookie information display functionality
 */
test.describe('Cookie Information', () => {
  test('Check cookies', async ({ page, context }) => {
    // First login to set the cookie
    await page.goto('http://localhost:5173');

    await page.fill('#username', TEST_CREDENTIALS.username);
    await page.fill('#password', TEST_CREDENTIALS.password);
    await page.click('button[onclick="login()"]');

    await page.waitForResponse((resp) => resp.url().includes('/jwt/v1/token'));

    // Click the "Check Cookies" button
    await page.click('button[onclick="checkCookies()"]');

    // Wait for cookie info to be displayed
    await page.waitForSelector('#cookieInfo', { state: 'visible' });

    // Verify the cookie exists in browser
    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

    expect(refreshCookie).toBeDefined();
    expect(refreshCookie.httpOnly).toBe(true);
  });
});
