import { defineConfig, devices } from '@playwright/test';

const isCI = process.env.CI === 'true';
const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:5173';
const apiURL = process.env.PLAYWRIGHT_API_URL || 'http://localhost:8080';

/**
 * Playwright configuration for cookie scenario testing
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './tests/e2e',

  // Maximum time one test can run
  timeout: 30 * 1000,

  // Test execution settings
  fullyParallel: !isCI, // Run tests in parallel locally, serial in CI for stability
  forbidOnly: isCI, // Fail build if test.only is left in CI
  retries: isCI ? 2 : 0, // Retry failed tests in CI
  workers: isCI ? 1 : undefined, // Single worker in CI, auto-detect locally

  // Reporter to use
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['json', { outputFile: 'test-results/results.json' }],
    ['junit', { outputFile: 'test-results/junit.xml' }],
    ['list'],
  ],

  // Shared settings for all projects
  use: {
    // Base URL for navigation
    baseURL,

    // Collect trace on failure
    trace: 'on-first-retry',

    // Screenshot on failure
    screenshot: 'only-on-failure',

    // Video on failure
    video: 'retain-on-failure',

    // Accept all cookies
    acceptDownloads: true,

    // Ignore HTTPS errors (for self-signed certificates)
    ignoreHTTPSErrors: true,
  },

  // Test projects for different scenarios
  projects: [
    // Same Domain scenario
    {
      name: 'same-domain',
      use: {
        ...devices['Desktop Chrome'],
        baseURL,
        storageState: undefined, // No pre-saved auth
      },
      testMatch: /cookie-scenarios\.spec\.js/,
      grep: /Same Domain|Security Checks|CORS Headers/,
    },

    // Cross-Origin scenario
    {
      name: 'cross-origin',
      use: {
        ...devices['Desktop Chrome'],
        baseURL,
        storageState: undefined,
      },
      testMatch: /cookie-scenarios\.spec\.js/,
      grep: /Cross-Origin/,
    },

    // Auto-detect scenario (most important - default behavior)
    {
      name: 'auto-detect',
      use: {
        ...devices['Desktop Chrome'],
        baseURL,
        storageState: undefined,
      },
      testMatch: /cookie-scenarios\.spec\.js/,
      grep: /Auto Development/,
    },

    // Strict Production scenario
    {
      name: 'strict-production',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: baseURL.replace('http://', 'https://'),
        storageState: undefined,
      },
      testMatch: /cookie-scenarios\.spec\.js/,
      grep: /Strict Production/,
    },

    // HTTPS scenarios
    {
      name: 'https',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: baseURL.replace('http://', 'https://'),
        ignoreHTTPSErrors: true,
      },
      testMatch: /cookie-scenarios\.spec\.js/,
      grep: /HTTPS|Security/,
    },

    // Mobile tests (optional)
    {
      name: 'mobile-chrome',
      use: {
        ...devices['Pixel 5'],
        baseURL,
      },
      testMatch: /cookie-scenarios\.spec\.js/,
      grep: /Auto Development.*Same Domain/,
    },

    // Firefox tests
    {
      name: 'firefox',
      use: {
        ...devices['Desktop Firefox'],
        baseURL,
      },
      testMatch: /cookie-scenarios\.spec\.js/,
      grep: /Auto Development.*Same Domain/,
    },

    // WebKit/Safari tests
    {
      name: 'webkit',
      use: {
        ...devices['Desktop Safari'],
        baseURL,
      },
      testMatch: /cookie-scenarios\.spec\.js/,
      grep: /Auto Development.*Same Domain/,
    },
  ],

  // Web server configuration (for local development)
  // Disabled: Assumes React app is already running or will be started separately
  webServer: undefined,

  // Global setup/teardown
  globalSetup: './tests/e2e/global-setup.js',
  globalTeardown: './tests/e2e/global-teardown.js',
});
