/**
 * Same Domain Test Scenarios
 * Tests JWT authentication when client and API are on the same domain
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
} from './test-helpers';

test.describe('Same Domain - HTTP', () => {
  test.beforeEach(async ({ page }) => {
    await navigateToScenario(page, 'same-domain-http');
  });

  test('should login and set HTTPOnly cookie with SameSite=Lax', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    // Verify cookie attributes
    await verifyCookieAttributes(context, 'wordpress.localtest.me', 'Lax', false);
  });

  test('should prevent JavaScript access to HTTPOnly cookie', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    // Verify cookie not accessible via JS
    await verifyCookieNotAccessibleViaJS(page);
  });

  test('should successfully refresh token using HTTPOnly cookie', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    // Perform refresh
    await performRefresh(page);
    await verifyRefreshSuccess(page);

    // Cookie should still exist
    await verifyCookieAttributes(context, 'wordpress.localtest.me', 'Lax', false);
  });

  test('should clear cookie on logout', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    await performLogout(page);

    // Verify cookie cleared
    await verifyCookieCleared(context, 'wordpress.localtest.me');
  });
});

test.describe('Same Domain - HTTPS', () => {
  test.beforeEach(async ({ page }) => {
    await navigateToScenario(page, 'same-domain-https');
  });

  test('should login and set HTTPOnly cookie with SameSite=Lax and Secure=true', async ({
    page,
    context,
  }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    // Verify cookie attributes (Secure should be true for HTTPS)
    await verifyCookieAttributes(context, 'wordpress.localtest.me', 'Lax', true);
  });

  test('should successfully refresh token over HTTPS', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    await performRefresh(page);
    await verifyRefreshSuccess(page);

    // Cookie should still be secure
    await verifyCookieAttributes(context, 'wordpress.localtest.me', 'Lax', true);
  });

  test('should maintain secure flag after logout', async ({ page, context }) => {
    await performLogin(page);
    await verifyLoginSuccess(page);

    await performLogout(page);

    // Verify cookie cleared
    await verifyCookieCleared(context, 'wordpress.localtest.me');
  });
});
