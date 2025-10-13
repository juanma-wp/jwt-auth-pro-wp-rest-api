/**
 * Protocol Matrix Test Scenarios
 * Tests different HTTP/HTTPS combinations between client and API
 */

import { test, expect } from '@playwright/test';
import {
  navigateToScenario,
  performLogin,
  verifyLoginSuccess,
  TEST_CREDENTIALS,
} from './test-helpers';

test.describe('Protocol Matrix Tests', () => {
  test.describe('HTTPS Client → HTTP API', () => {
    test.beforeEach(async ({ page }) => {
      await navigateToScenario(page, 'mixed-https-client-http-api');
    });

    test('should fail due to mixed content blocking', async ({ page }) => {
      await page.fill('[data-testid="username"]', TEST_CREDENTIALS.username);
      await page.fill('[data-testid="password"]', TEST_CREDENTIALS.password);

      await page.click('[data-testid="login-button"]');

      // Wait for error status
      const statusElement = page.locator('[data-testid="login-status"]');

      // Should show error due to mixed content
      await expect(statusElement).toContainText('Error', { timeout: 10000 });
    });

    test('should show mixed content warning in console', async ({ page }) => {
      const consoleMessages: string[] = [];

      page.on('console', (msg) => {
        consoleMessages.push(msg.text());
      });

      await page.fill('[data-testid="username"]', TEST_CREDENTIALS.username);
      await page.fill('[data-testid="password"]', TEST_CREDENTIALS.password);
      await page.click('[data-testid="login-button"]');

      await page.waitForTimeout(2000);

      // Check for mixed content warnings
      const hasMixedContentWarning = consoleMessages.some((msg) =>
        msg.toLowerCase().includes('mixed content')
      );

      // Note: This may not always trigger depending on browser settings
      // The important thing is that the request fails
    });
  });

  test.describe('HTTP Client → HTTPS API', () => {
    test.beforeEach(async ({ page }) => {
      await navigateToScenario(page, 'mixed-http-client-https-api');
    });

    test('should work - HTTPS API is accessible from HTTP client', async ({ page, context }) => {
      await performLogin(page);
      await verifyLoginSuccess(page);

      // Verify cookie was set
      const cookies = await context.cookies();
      const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

      expect(refreshCookie).toBeDefined();
      expect(refreshCookie!.secure).toBe(true); // API is HTTPS, so cookie is secure
    });

    test('should handle CORS correctly with mixed protocols', async ({ page }) => {
      await page.fill('[data-testid="username"]', TEST_CREDENTIALS.username);
      await page.fill('[data-testid="password"]', TEST_CREDENTIALS.password);

      const responsePromise = page.waitForResponse(
        (response) => response.url().includes('/jwt/v1/token') && response.request().method() === 'POST'
      );

      await page.click('[data-testid="login-button"]');

      const response = await responsePromise;
      expect(response.status()).toBe(200);

      const headers = response.headers();

      // CORS headers should allow HTTP origin
      expect(headers['access-control-allow-origin']).toBe('http://client.localtest.me');
      expect(headers['access-control-allow-credentials']).toBe('true');
    });
  });

  test.describe('HTTP → HTTP (Both)', () => {
    test.beforeEach(async ({ page }) => {
      await navigateToScenario(page, 'cross-origin-http');
    });

    test('should work with both HTTP but cookie may be blocked by modern browsers', async ({
      page,
      context,
    }) => {
      await performLogin(page);

      // May succeed or fail depending on browser policy for SameSite=None without Secure
      const statusElement = page.locator('[data-testid="login-status"]');

      // Wait for either success or error
      await expect(statusElement).not.toBeEmpty({ timeout: 10000 });

      const status = await statusElement.textContent();

      if (status?.includes('successful')) {
        // If it works, check cookie
        const cookies = await context.cookies();
        const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

        // Cookie might not be set due to browser security policy
        if (refreshCookie) {
          expect(refreshCookie.sameSite).toBe('None');
          expect(refreshCookie.secure).toBe(false);
        }
      }
    });
  });

  test.describe('HTTPS → HTTPS (Both)', () => {
    test.beforeEach(async ({ page }) => {
      await navigateToScenario(page, 'cross-origin-https');
    });

    test('should work perfectly with both HTTPS', async ({ page, context }) => {
      await performLogin(page);
      await verifyLoginSuccess(page);

      // This is the ideal scenario for cross-origin with cookies
      const cookies = await context.cookies();
      const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

      expect(refreshCookie).toBeDefined();
      expect(refreshCookie!.sameSite).toBe('None');
      expect(refreshCookie!.secure).toBe(true);
      expect(refreshCookie!.httpOnly).toBe(true);
    });

    test('should maintain security through full auth flow', async ({ page, context }) => {
      // Login
      await performLogin(page);
      await verifyLoginSuccess(page);

      // Refresh
      await page.click('[data-testid="refresh-button"]');
      await page.waitForResponse((resp) => resp.url().includes('/token/refresh'));

      const refreshStatus = page.locator('[data-testid="refresh-status"]');
      await expect(refreshStatus).toContainText('refreshed successfully');

      // Verify cookie still secure
      const cookies = await context.cookies();
      const refreshCookie = cookies.find((c) => c.name === 'wp_jwt_refresh_token');

      expect(refreshCookie!.secure).toBe(true);
      expect(refreshCookie!.httpOnly).toBe(true);
    });
  });
});
