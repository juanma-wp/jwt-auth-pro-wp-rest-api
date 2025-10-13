/**
 * Cross-Origin Test Scenarios
 * Tests JWT authentication when client and API are on different domains
 */

import { test, expect } from '@playwright/test';
import {
  navigateToScenario,
  performLogin,
  verifyLoginSuccess,
  verifyCookieAttributes,
  verifyCookieNotAccessibleViaJS,
  performRefresh,
  verifyRefreshSuccess,
  performLogout,
  verifyCookieCleared,
  TEST_CREDENTIALS,
} from './test-helpers';

test.describe('Cross-Origin - HTTP', () => {
  test.beforeEach(async ({ page }) => {
    await navigateToScenario(page, 'cross-origin-http');
  });

  test('should login and set HTTPOnly cookie with SameSite=None', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    // For cross-origin HTTP, SameSite=None but Secure=false
    // Note: Modern browsers may block this
    await verifyCookieAttributes(context, 'wordpress.localtest.me', 'None', false);
  });

  test('should handle CORS preflight correctly', async ({ page }) => {
    // Fill in credentials
    await page.fill('[data-testid="username"]', TEST_CREDENTIALS.username);
    await page.fill('[data-testid="password"]', TEST_CREDENTIALS.password);

    // Wait for preflight OPTIONS request
    const preflightPromise = page.waitForRequest(
      (request) => request.method() === 'OPTIONS' && request.url().includes('/jwt/v1/token')
    );

    // Trigger login
    await page.click('[data-testid="login-button"]');

    const preflightRequest = await preflightPromise;
    const response = await preflightRequest.response();

    expect(response).toBeDefined();
    const headers = response!.headers();

    // Verify CORS headers
    expect(headers['access-control-allow-origin']).toBe('http://client.localtest.me');
    expect(headers['access-control-allow-credentials']).toBe('true');
    expect(headers['access-control-allow-methods']).toContain('POST');
  });

  test('should send cookie automatically on refresh', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    // Perform refresh - cookie sent automatically
    await performRefresh(page);
    await verifyRefreshSuccess(page);

    // Cookie should still exist
    const cookies = await context.cookies();
    const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');
    expect(refreshCookie).toBeDefined();
  });
});

test.describe('Cross-Origin - HTTPS', () => {
  test.beforeEach(async ({ page }) => {
    await navigateToScenario(page, 'cross-origin-https');
  });

  test('should login and set HTTPOnly cookie with SameSite=None and Secure=true', async ({
    page,
    context,
  }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    // For cross-origin HTTPS, SameSite=None with Secure=true
    await verifyCookieAttributes(context, 'wordpress.localtest.me', 'None', true);
  });

  test('should prevent JavaScript access even with cross-origin', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    // Verify HTTPOnly protection
    await verifyCookieNotAccessibleViaJS(page);
  });

  test('should successfully refresh token across origins with HTTPS', async ({
    page,
    context,
  }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    await performRefresh(page);
    await verifyRefreshSuccess(page);

    // Cookie should maintain secure settings
    await verifyCookieAttributes(context, 'wordpress.localtest.me', 'None', true);
  });

  test('should handle logout and clear cookie across origins', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    await performLogout(page);

    // Verify cookie cleared
    await verifyCookieCleared(context, 'wordpress.localtest.me');
  });

  test('should verify CORS headers include correct origin', async ({ page }) => {
    // Fill credentials
    await page.fill('[data-testid="username"]', TEST_CREDENTIALS.username);
    await page.fill('[data-testid="password"]', TEST_CREDENTIALS.password);

    // Wait for actual POST request
    const responsePromise = page.waitForResponse(
      (response) => response.url().includes('/jwt/v1/token') && response.request().method() === 'POST'
    );

    await page.click('[data-testid="login-button"]');

    const response = await responsePromise;
    const headers = response.headers();

    // Verify CORS response headers
    expect(headers['access-control-allow-origin']).toBe('https://client.localtest.me');
    expect(headers['access-control-allow-credentials']).toBe('true');
  });
});

test.describe('Cross-Origin - Subdomain', () => {
  test.beforeEach(async ({ page }) => {
    await navigateToScenario(page, 'subdomain-https');
  });

  test('should handle subdomain to subdomain communication', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    // Different subdomains are still cross-origin
    await verifyCookieAttributes(context, 'api.localtest.me', 'None', true);
  });

  test('should allow token refresh between subdomains', async ({ page }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    await performRefresh(page);
    await verifyRefreshSuccess(page);
  });
});
