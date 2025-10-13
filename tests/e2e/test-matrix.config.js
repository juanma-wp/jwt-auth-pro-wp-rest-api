/**
 * Test Matrix Configuration
 *
 * Defines different cookie configuration scenarios and their expected behaviors
 * across different domain setups (same-domain, cross-origin, subdomain, HTTPS)
 */

export const COOKIE_CONFIGS = {
  /**
   * Strict (Production) - Maximum security
   * Works: Same domain only
   * Fails: Cross-origin, subdomain
   */
  STRICT_PRODUCTION: {
    name: 'Strict Production',
    constants: {
      JWT_AUTH_COOKIE_SAMESITE: 'Strict',
      JWT_AUTH_COOKIE_SECURE: true,
      JWT_AUTH_COOKIE_AUTO_DETECT: false,
    },
    expectedBehavior: {
      sameDomain: { works: true, https: 'required' },
      crossOrigin: { works: false, reason: 'SameSite=Strict blocks cross-origin' },
      subdomain: { works: false, reason: 'SameSite=Strict is strict even for subdomains' },
      httpToHttps: { works: false, reason: 'Secure flag requires HTTPS' },
    },
  },

  /**
   * Lax (Staging) - Relaxed security
   * Works: Same domain, top-level navigation
   * Fails: Cross-origin POST requests
   */
  LAX_STAGING: {
    name: 'Lax Staging',
    constants: {
      JWT_AUTH_COOKIE_SAMESITE: 'Lax',
      JWT_AUTH_COOKIE_SECURE: true,
      JWT_AUTH_COOKIE_AUTO_DETECT: false,
    },
    expectedBehavior: {
      sameDomain: { works: true, https: 'required' },
      crossOrigin: { works: false, reason: 'Lax blocks cross-origin POST' },
      subdomain: { works: true, https: 'required', note: 'May work if domain is set correctly' },
      httpToHttps: { works: false, reason: 'Secure flag requires HTTPS' },
    },
  },

  /**
   * None (Cross-Origin) - Cross-domain support
   * Works: Everywhere with HTTPS
   * Requires: HTTPS (except localhost in development)
   */
  NONE_CROSS_ORIGIN: {
    name: 'None Cross-Origin',
    constants: {
      JWT_AUTH_COOKIE_SAMESITE: 'None',
      JWT_AUTH_COOKIE_SECURE: true,
      JWT_AUTH_COOKIE_AUTO_DETECT: false,
    },
    expectedBehavior: {
      sameDomain: { works: true, https: 'required' },
      crossOrigin: { works: true, https: 'required', note: 'Requires Secure=true (HTTPS)' },
      subdomain: { works: true, https: 'required' },
      httpToHttps: { works: false, reason: 'SameSite=None requires Secure=true' },
    },
  },

  /**
   * Development (Auto-detect) - Intelligent defaults
   * Detects cross-origin and adjusts SameSite accordingly
   */
  AUTO_DEVELOPMENT: {
    name: 'Auto Development',
    constants: {
      JWT_AUTH_COOKIE_AUTO_DETECT: true,
    },
    expectedBehavior: {
      sameDomain: { works: true, https: 'optional', note: 'Auto sets SameSite=Lax' },
      crossOrigin: {
        works: true,
        https: 'optional-on-localhost',
        note: 'Auto detects cross-origin and sets SameSite=None'
      },
      subdomain: { works: true, https: 'optional' },
      httpToHttps: { works: true, note: 'Development mode allows HTTP' },
    },
  },

  /**
   * Localhost Development - Permissive for local dev
   * SameSite=None without Secure is allowed by browsers on localhost
   */
  LOCALHOST_DEV: {
    name: 'Localhost Development',
    constants: {
      JWT_AUTH_COOKIE_SAMESITE: 'None',
      JWT_AUTH_COOKIE_SECURE: false,
      JWT_AUTH_COOKIE_AUTO_DETECT: false,
    },
    expectedBehavior: {
      sameDomain: { works: true, https: 'optional', localhostOnly: true },
      crossOrigin: {
        works: true,
        https: 'optional',
        localhostOnly: true,
        note: 'Browsers allow SameSite=None without Secure on localhost'
      },
      subdomain: { works: false, reason: 'Only works on localhost' },
      httpToHttps: { works: false, localhostOnly: true },
    },
  },
};

export const DOMAIN_SCENARIOS = {
  /**
   * Same Domain - React app and WordPress on same domain
   * Example: https://example.com (React) → https://example.com/wp-json (WordPress)
   */
  SAME_DOMAIN: {
    name: 'Same Domain',
    setup: {
      react: 'https://example.com',
      wordpress: 'https://example.com',
      local: {
        react: 'http://localhost:5173',
        wordpress: 'http://localhost:8080',
      },
    },
    corsRequired: false,
    recommendedSameSite: 'Lax',
  },

  /**
   * Cross-Origin - React app on different domain than WordPress
   * Example: https://app.com (React) → https://api.com (WordPress)
   */
  CROSS_ORIGIN: {
    name: 'Cross-Origin',
    setup: {
      react: 'https://app.example.com',
      wordpress: 'https://api.example.com',
      local: {
        react: 'http://localhost:5173',
        wordpress: 'http://rest-api-tests.wp.local',
      },
    },
    corsRequired: true,
    recommendedSameSite: 'None',
  },

  /**
   * Subdomain - React on subdomain, WordPress on main domain
   * Example: https://app.example.com (React) → https://example.com (WordPress)
   */
  SUBDOMAIN: {
    name: 'Subdomain',
    setup: {
      react: 'https://app.example.com',
      wordpress: 'https://example.com',
      local: {
        react: 'http://app.test',
        wordpress: 'http://api.test',
      },
    },
    corsRequired: true,
    recommendedSameSite: 'None',
    note: 'Can use domain=.example.com to share cookies, but still requires CORS',
  },

  /**
   * React on main domain, WordPress on subdomain
   * Example: https://example.com (React) → https://api.example.com (WordPress)
   */
  SUBDOMAIN_REVERSE: {
    name: 'Subdomain Reverse',
    setup: {
      react: 'https://example.com',
      wordpress: 'https://api.example.com',
      local: {
        react: 'http://app.test',
        wordpress: 'http://api.app.test',
      },
    },
    corsRequired: true,
    recommendedSameSite: 'None',
  },

  /**
   * HTTPS WordPress, HTTP React - Security mismatch
   * Should fail in production, may work in development
   */
  MIXED_CONTENT: {
    name: 'Mixed Content (HTTP → HTTPS)',
    setup: {
      react: 'http://app.example.com',
      wordpress: 'https://api.example.com',
      local: {
        react: 'http://localhost:5173',
        wordpress: 'https://rest-api-tests.wp.local',
      },
    },
    corsRequired: true,
    recommendedSameSite: 'None',
    warning: 'Secure cookies cannot be sent from HTTP origin',
  },
};

export const TEST_FLOWS = {
  LOGIN: {
    name: 'Login Flow',
    steps: [
      'Navigate to login page',
      'Fill username and password',
      'Submit login form',
      'Check access token in response',
      'Check refresh_token HTTPOnly cookie is set',
      'Verify cookie attributes (SameSite, Secure, HttpOnly)',
    ],
    expectedCookies: ['refresh_token'],
    expectedHeaders: ['Set-Cookie', 'Access-Control-Allow-Credentials'],
  },

  REFRESH: {
    name: 'Token Refresh Flow',
    steps: [
      'Login first',
      'Expire access token (simulate)',
      'Make authenticated request',
      'Verify refresh endpoint called',
      'Verify refresh_token cookie sent',
      'Check new access token received',
      'Verify refresh_token rotated (new cookie)',
    ],
    expectedCookies: ['refresh_token'],
    expectedBehavior: 'Cookie sent automatically',
  },

  LOGOUT: {
    name: 'Logout Flow',
    steps: [
      'Login first',
      'Call logout endpoint',
      'Verify refresh_token cookie cleared',
      'Verify subsequent requests fail',
    ],
    expectedBehavior: 'Cookie removed',
  },

  CORS_PREFLIGHT: {
    name: 'CORS Preflight',
    steps: [
      'Send OPTIONS request',
      'Check Access-Control-Allow-Origin',
      'Check Access-Control-Allow-Credentials',
      'Check Access-Control-Allow-Methods',
      'Check Access-Control-Allow-Headers',
    ],
    expectedHeaders: [
      'Access-Control-Allow-Origin',
      'Access-Control-Allow-Credentials',
      'Access-Control-Allow-Methods',
      'Access-Control-Allow-Headers',
    ],
  },
};

export const ASSERTIONS = {
  COOKIE_SET: {
    name: 'Cookie Set Correctly',
    checks: [
      'Cookie exists in browser',
      'Cookie has correct name',
      'HttpOnly flag is true',
      'SameSite attribute matches config',
      'Secure flag matches config',
      'Domain matches config',
      'Path matches config',
    ],
  },

  COOKIE_SENT: {
    name: 'Cookie Sent on Request',
    checks: [
      'Cookie present in browser',
      'Cookie sent with request',
      'Cookie value not empty',
      'No console errors about blocked cookies',
    ],
  },

  CORS_HEADERS: {
    name: 'CORS Headers Correct',
    checks: [
      'Access-Control-Allow-Origin matches request origin',
      'Access-Control-Allow-Credentials is true',
      'No CORS errors in console',
    ],
  },

  SECURITY: {
    name: 'Security Checks',
    checks: [
      'HttpOnly prevents JavaScript access',
      'Secure flag set for HTTPS',
      'SameSite prevents CSRF',
      'Cookie not visible in document.cookie',
    ],
  },
};

/**
 * Get test matrix: combination of configs, scenarios, and flows
 */
export function getTestMatrix() {
  const matrix = [];

  for (const [configKey, config] of Object.entries(COOKIE_CONFIGS)) {
    for (const [scenarioKey, scenario] of Object.entries(DOMAIN_SCENARIOS)) {
      for (const [flowKey, flow] of Object.entries(TEST_FLOWS)) {
        const behavior = config.expectedBehavior[
          scenarioKey.toLowerCase().replace('_', '')
        ] || config.expectedBehavior.crossOrigin;

        matrix.push({
          configKey,
          scenarioKey,
          flowKey,
          config,
          scenario,
          flow,
          behavior,
          shouldRun: !behavior.localhostOnly || process.env.CI !== 'true',
        });
      }
    }
  }

  return matrix;
}

/**
 * Helper to format test name
 */
export function formatTestName(configKey, scenarioKey, flowKey) {
  return `${COOKIE_CONFIGS[configKey].name} - ${DOMAIN_SCENARIOS[scenarioKey].name} - ${TEST_FLOWS[flowKey].name}`;
}
