import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for JWT E2E tests
 * Tests multiple domain and protocol scenarios
 */

export default defineConfig({
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['list'],
    ['junit', { outputFile: 'test-results/junit.xml' }],
  ],
  timeout: 60 * 1000,
  expect: {
    timeout: 10 * 1000,
  },
  use: {
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    ignoreHTTPSErrors: true, // For self-signed certs in local development
  },

  projects: [
    // Same Domain Tests
    {
      name: 'same-domain-http',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://client.localtest.me:8000',
      },
      testMatch: /same-domain\.spec\.ts/,
      grep: /Same Domain - HTTP/,
    },
    {
      name: 'same-domain-https',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://client.localtest.me:8443',
      },
      testMatch: /same-domain\.spec\.ts/,
      grep: /Same Domain - HTTPS/,
    },

    // Cross-Origin Tests
    {
      name: 'cross-origin-http',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://client.localtest.me:8000',
      },
      testMatch: /cross-origin\.spec\.ts/,
      grep: /Cross-Origin - HTTP/,
    },
    {
      name: 'cross-origin-https',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://client.localtest.me:8443',
      },
      testMatch: /cross-origin\.spec\.ts/,
      grep: /Cross-Origin - HTTPS/,
    },
    {
      name: 'cross-origin-subdomain',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://app.localtest.me:8443',
      },
      testMatch: /cross-origin\.spec\.ts/,
      grep: /Cross-Origin - Subdomain/,
    },

    // Protocol Matrix Tests
    {
      name: 'protocol-matrix',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://client.localtest.me:8443',
      },
      testMatch: /protocol-matrix\.spec\.ts/,
    },

    // Firefox tests (subset for cross-browser testing)
    {
      name: 'firefox-cross-origin-https',
      use: {
        ...devices['Desktop Firefox'],
        baseURL: 'https://client.localtest.me:8443',
      },
      testMatch: /cross-origin\.spec\.ts/,
      grep: /Cross-Origin - HTTPS/,
    },

    // WebKit tests (subset for cross-browser testing)
    {
      name: 'webkit-cross-origin-https',
      use: {
        ...devices['Desktop Safari'],
        baseURL: 'https://client.localtest.me:8443',
      },
      testMatch: /cross-origin\.spec\.ts/,
      grep: /Cross-Origin - HTTPS/,
    },
  ],
});
