/**
 * Test Helpers for JWT E2E Tests
 */

import { Page, expect, BrowserContext } from '@playwright/test';

export const TEST_CREDENTIALS = {
  username: 'admin',
  password: 'password'
};

export interface JWTTestScenario {
  name: string;
  clientUrl: string;
  apiUrl: string;
  expectedSameSite: 'Lax' | 'None' | 'Strict';
  expectedSecure: boolean;
  shouldWork: boolean;
}

/**
 * Navigate to test client with specific scenario
 */
export async function navigateToScenario(page: Page, scenarioKey: string): Promise<void> {
  await page.goto(`/?scenario=${scenarioKey}`);
  await page.waitForLoadState('networkidle');
}

/**
 * Perform login action
 */
export async function performLogin(
  page: Page,
  username: string = TEST_CREDENTIALS.username,
  password: string = TEST_CREDENTIALS.password
): Promise<void> {
  await page.fill('[data-testid="username"]', username);
  await page.fill('[data-testid="password"]', password);

  const responsePromise = page.waitForResponse(
    (response) => response.url().includes('/jwt/v1/token') && response.request().method() === 'POST'
  );

  await page.click('[data-testid="login-button"]');

  const response = await responsePromise;
  expect(response.status()).toBe(200);
}

/**
 * Verify login was successful
 */
export async function verifyLoginSuccess(page: Page): Promise<void> {
  const statusElement = page.locator('[data-testid="login-status"]');
  await expect(statusElement).toContainText('Login successful');
}

/**
 * Get HTTPOnly cookie from browser context
 */
export async function getRefreshTokenCookie(context: BrowserContext, domain: string) {
  const cookies = await context.cookies();
  return cookies.find(
    (cookie) =>
      cookie.name === 'wp_jwt_refresh_token' &&
      (cookie.domain === domain || cookie.domain === `.${domain}`)
  );
}

/**
 * Verify cookie attributes
 */
export async function verifyCookieAttributes(
  context: BrowserContext,
  domain: string,
  expectedSameSite: 'Lax' | 'None' | 'Strict',
  expectedSecure: boolean
): Promise<void> {
  const cookie = await getRefreshTokenCookie(context, domain);

  expect(cookie, 'HTTPOnly refresh token cookie should exist').toBeDefined();
  expect(cookie!.httpOnly, 'Cookie should be HTTPOnly').toBe(true);
  expect(cookie!.sameSite, `Cookie SameSite should be ${expectedSameSite}`).toBe(expectedSameSite);
  expect(cookie!.secure, `Cookie Secure should be ${expectedSecure}`).toBe(expectedSecure);
}

/**
 * Verify cookie is NOT accessible via JavaScript
 */
export async function verifyCookieNotAccessibleViaJS(page: Page): Promise<void> {
  const cookieAccessible = await page.evaluate(() => {
    return document.cookie.includes('wp_jwt_refresh_token');
  });

  expect(cookieAccessible, 'HTTPOnly cookie should NOT be accessible via JavaScript').toBe(false);
}

/**
 * Perform token refresh
 */
export async function performRefresh(page: Page): Promise<void> {
  const responsePromise = page.waitForResponse(
    (response) => response.url().includes('/jwt/v1/token/refresh')
  );

  await page.click('[data-testid="refresh-button"]');

  const response = await responsePromise;
  expect(response.status()).toBe(200);

  const data = await response.json();
  expect(data.data.access_token).toBeDefined();
}

/**
 * Verify refresh was successful
 */
export async function verifyRefreshSuccess(page: Page): Promise<void> {
  const statusElement = page.locator('[data-testid="refresh-status"]');
  await expect(statusElement).toContainText('refreshed successfully');
}

/**
 * Perform logout
 */
export async function performLogout(page: Page): Promise<void> {
  const responsePromise = page.waitForResponse(
    (response) => response.url().includes('/jwt/v1/logout')
  );

  await page.click('[data-testid="logout-button"]');

  const response = await responsePromise;
  expect(response.status()).toBe(200);
}

/**
 * Verify cookie was cleared after logout
 */
export async function verifyCookieCleared(context: BrowserContext, domain: string): Promise<void> {
  const cookie = await getRefreshTokenCookie(context, domain);
  expect(cookie, 'Cookie should be cleared after logout').toBeUndefined();
}

/**
 * Verify CORS headers
 */
export async function verifyCORSHeaders(
  page: Page,
  apiUrl: string,
  expectedOrigin: string
): Promise<void> {
  const responsePromise = page.waitForResponse(
    (response) => response.url().includes(apiUrl)
  );

  await page.click('[data-testid="login-button"]');

  const response = await responsePromise;
  const headers = response.headers();

  expect(headers['access-control-allow-origin']).toBe(expectedOrigin);
  expect(headers['access-control-allow-credentials']).toBe('true');
}
